# Architecture

## 1. Overview

Jira Worklog Bot is a personal web application for logging work to Jira Cloud.

A single user signs in with a personal password, submits a small Vue form, and Laravel creates the Jira worklog. After Jira succeeds, Laravel attempts to send a best-effort notification to a Google Chat space through an incoming webhook.

The application remains intentionally small and single-user.

## 2. System Context

```text
┌────────────────────────────┐
│          Browser           │
│      Vue + Vite UI         │
└─────────────┬──────────────┘
              │ authenticated session / HTTPS
              ▼
┌────────────────────────────┐
│          Laravel           │
│      Render / Docker       │
└─────────────┬──────────────┘
              │ Jira REST API
              ▼
┌────────────────────────────┐
│         Jira Cloud         │
│          Worklogs          │
└─────────────┬──────────────┘
              │ success
              ▼
┌────────────────────────────┐
│ Google Chat Incoming Hook  │
│   best-effort notification │
└────────────────────────────┘
```

Laravel is the trusted boundary. Jira and Google Chat credentials never reach the browser.

## 3. Architectural Goals

The architecture should be:

- simple
- secure for a publicly deployed single-user tool
- easy to test
- inexpensive to host
- easy to understand
- isolated from external integrations
- independent of application database state

This application does not require enterprise architecture.

## 4. Deployment

Target deployment:

```text
Developer
    │ git push
    ▼
GitHub
    │
    ▼
Render
    │ build Dockerfile
    │ install PHP/JS dependencies
    │ compile Vite assets
    ▼
Laravel Container
```

Target hosting is a Render Web Service for development and personal usage.

The application must not depend on persistent local filesystem state. Environment variables provide deployment-specific configuration and secrets.

## 5. Layers

Use three conceptual layers.

### Presentation / HTTP Layer

Responsibilities:

- render the Blade/Vue entry point
- present the login and worklog forms
- validate HTTP request shape
- enforce session authentication and CSRF protection
- translate requests into application commands
- format safe JSON responses

Examples:

```text
Vue components
Authentication controller/middleware
StoreWorklogRequest
WorklogController
```

This layer must not contain Jira or Google Chat HTTP implementation details.

### Application Layer

Coordinates the `LogWork` use case.

```text
WorklogController
    ↓
LogWorkCommand
    ↓
LogWorkHandler
    ├── JiraClient
    └── GoogleChatNotifier after Jira success
```

The handler owns operation ordering and the distinction between the primary Jira operation and secondary notification.

### Integration / Service Layer

Handles external systems and reusable parsing utilities.

```text
JiraClient
GoogleChatNotifier
DurationParser
WorklogDateParser
```

Jira-specific HTTP calls belong only in the Jira integration. Google Chat webhook payload and delivery logic belong only in the Google Chat notifier.

## 6. Application Flow

The canonical flow is:

```text
Authenticated Vue form
  │
  ├── ticket
  ├── duration
  ├── optional date
  └── optional time
  │
  ▼
POST /api/worklogs
  │
  ▼
Server-side validation
  │
  ├── normalize ticket
  ├── parse duration to seconds
  └── parse started time in configured timezone
  │
  ▼
LogWorkCommand
  │
  ▼
LogWorkHandler
  │
  ▼
JiraClient
  │
  ├── failure ──> safe error response
  │
  └── success
        │
        ▼
  GoogleChatNotifier
        │
        ├── success ──> notificationSent = true
        └── failure ──> log warning, notificationSent = false
                         │
                         ▼
                    successful UI response
```

The application must never create a second Jira worklog merely to retry a failed notification.

## 7. Project Structure

Target structure:

```text
app/
├── Application/
│   └── Worklog/
│       ├── LogWorkCommand.php
│       └── LogWorkHandler.php
├── Http/
│   ├── Controllers/
│   │   ├── AuthenticationController.php
│   │   └── WorklogController.php
│   ├── Middleware/
│   │   └── RequirePersonalAccess.php
│   └── Requests/
│       ├── LoginRequest.php
│       └── StoreWorklogRequest.php
├── Services/
│   ├── Jira/
│   │   └── JiraClient.php
│   └── GoogleChat/
│       └── GoogleChatNotifier.php
└── Support/
    ├── DurationParser.php
    └── WorklogDateParser.php

resources/
├── css/
│   └── app.css
├── js/
│   ├── app.js
│   ├── App.vue
│   └── components/
│       ├── LoginForm.vue
│       └── WorklogForm.vue
└── views/
    └── app.blade.php
```

Names may follow Laravel conventions discovered during implementation. Create only the components needed by the current phase.

The former `GoogleChatCommandParser`, `ParsedGoogleChatCommand`, `InvalidGoogleChatCommandException`, and `GoogleChatResponseBuilder` belong to the abandoned inbound slash-command design and should be removed during the Vue UI phase.

## 8. Worklog Model

A worklog operation consists conceptually of:

```text
ticket
duration
durationSeconds
started
```

Example:

```text
ticket          = BKM4-1234
duration         = 2h15m
durationSeconds  = 8100
started          = 2026-09-05T14:30:00+07:00
```

No database model is required.

## 9. Duration, Date, and Time

Supported duration grammar:

```text
<hours>h
<minutes>m
<hours>h<minutes>m
```

Duration must be greater than zero and is normalized to seconds.

Timezone:

```text
Asia/Ho_Chi_Minh
```

Supported input modes:

- no explicit date/time: use the current configured date/time
- time only: use today at the supplied time
- explicit date and time: use the supplied values

The Vue UI may prefill date and time for convenience, but Laravel remains authoritative. Never infer time from the server/container timezone.

## 10. Jira Integration

Jira Cloud is the system of record for worklogs.

Conceptual request:

```text
POST /rest/api/3/issue/{issueKey}/worklog
```

Conceptual payload:

```json
{
  "started": "...",
  "timeSpentSeconds": 8100
}
```

Credentials are supplied through `JIRA_BASE_URL`, `JIRA_EMAIL`, and `JIRA_API_TOKEN`. The exact HTTP implementation belongs in `JiraClient`.

## 11. Google Chat Notification

Google Chat is not an input adapter. The current architecture does not include a Chat app, slash commands, Google request verification, or OAuth.

After Jira creates a worklog, `GoogleChatNotifier` posts a concise message to the configured incoming webhook.

```text
✅ Jira Worklog Added

🎫 BKM4-1234
⏱ 2h 15m
🕐 05/09/2026 14:30
```

The webhook URL comes from `config('services.google_chat.webhook_url')` backed by `GOOGLE_CHAT_WEBHOOK_URL`.

### Failure Semantics

```text
Jira failure
    └── worklog request fails; do not notify

Jira success + webhook success
    └── worklog request succeeds; notificationSent = true

Jira success + webhook failure
    └── worklog request succeeds; notificationSent = false; log safe warning
```

Do not rollback Jira, retry by recreating the worklog, or expose the webhook URL.

## 12. Authentication and Security

The Render URL will be public, but the application is private to one user.

Use a minimal personal-password login backed by a configured password hash and Laravel session. Protect both the Vue page and worklog API. Use Laravel CSRF protection, secure HTTP-only cookies in production, session regeneration on login/logout, and rate limiting on login attempts.

No users table is required. Do not add registration, password reset, email verification, OAuth, or Google identity.

Authentication must be implemented and verified before Render deployment.

Secrets include:

- Jira API token
- Google Chat webhook URL
- personal access password hash
- Laravel application key

Never expose these in browser bundles, logs, API responses, repository files, or Docker image layers.

## 13. Persistence

No application database is required. Jira remains the source of truth.

Do not introduce MySQL, PostgreSQL, SQLite, Redis, or persistent worklog history unless a future requirement explicitly requires it. Session storage must use a deployment-compatible non-database driver for the single-instance MVP.

## 14. Error Handling

Convert failures into concise application-friendly responses.

Examples:

- invalid ticket, duration, date, or time
- unauthenticated or rate-limited access
- Jira authentication, authorization, validation, timeout, or network failure
- Google Chat notification unavailable or failed

Do not expose tokens, credentials, sensitive headers, webhook URLs, or unnecessary external stack traces.

## 15. Testing Strategy

### Unit Tests

Test pure behavior including duration parsing, date/time parsing, notification formatting, and authentication helpers when applicable.

### Feature Tests

Test:

- authenticated and guest page/API access
- login, logout, incorrect password, and rate limiting
- `POST /api/worklogs` with Jira mocked
- Google Chat webhook success and failure with HTTP mocked
- Jira success remains successful when notification fails
- external secrets are not returned

### Frontend Verification

Verify the Vite production build and the form's loading, validation, success, notification-warning, and Jira-error states.

Real Jira and Google Chat calls are manual integration tests and must not run in the normal automated test suite.

## 16. Explicit Non-Goals

For the current product:

- no database
- no multi-user support
- no user registration or recovery
- no Jira OAuth
- no Google Chat app or slash commands
- no Google OAuth
- no Vue Router or Pinia
- no general dashboard
- no worklog history storage
- no queues or Redis
- no scheduler
- no microservices
- no DDD aggregates or event sourcing

## 17. Architectural Principle

The central rule is:

```text
Vue and HTTP are inputs.
Jira worklog creation is the primary output.
Google Chat notification is a secondary output.
LogWork remains the application use case.
```

Changing the presentation or notification channel must not require rewriting Jira integration or core parsing behavior.

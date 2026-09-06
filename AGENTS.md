# Repository Guidelines

## Project Overview

This project is a personal Jira Worklog application built with Laravel and Vue.

One user opens a small web UI, enters a Jira ticket and duration, and creates a Jira Cloud worklog. After Jira accepts the worklog, Laravel attempts to notify a Google Chat space through an incoming webhook.

```text
Browser
    ↓
Vue + Vite
    ↓
Laravel API
    ↓
Jira Cloud REST API
    ↓
Google Chat Incoming Webhook notification
```

The application is intended for a single user only. Do not introduce multi-user architecture unless explicitly requested.

## Required Reading

Before implementing or modifying code, read:

1. `ARCHITECTURE.md`
2. `DESIGN.md`
3. `TASKS.md`

These files define the architecture, behavior, constraints, and current implementation plan.

If implementation conflicts with these documents, do not silently change the architecture. Explain the conflict and propose an update.

## Technology Stack

Use:

- PHP 8.3+
- Laravel 12+
- Vue 3
- Vite
- Composer and npm
- PHPUnit / Pest according to the Laravel project default
- Laravel HTTP Client for external HTTP requests
- Laravel session and CSRF protection
- Docker
- Render for deployment
- Jira Cloud REST API
- Google Chat Incoming Webhook

Do not introduce additional frontend frameworks, state-management libraries, databases, or infrastructure unless required.

## Architecture Principles

Keep the application simple. This is a small single-user integration application, not a multi-user product or enterprise platform.

Do not introduce:

- Domain-Driven Design aggregates
- repositories without persistence
- event sourcing
- CQRS frameworks
- Redis
- queues
- databases
- message brokers
- microservices
- unnecessary abstractions

unless a future requirement explicitly requires them.

Use a lightweight layered architecture:

```text
Vue UI / HTTP Adapter
        ↓
Application
        ↓
Services / External Clients
```

Controllers must remain thin. Business logic must not be implemented directly inside controllers or Vue components.

External Jira HTTP logic must remain inside the Jira integration layer. Google Chat webhook formatting and delivery must remain isolated from the Jira client.

Jira worklog creation is the primary business operation. Google Chat notification is a secondary best-effort side effect. A notification failure must never roll back or report the already-created Jira worklog as failed.

## Expected Project Structure

Prefer the following structure:

```text
app/
├── Application/
│   └── Worklog/
│       ├── LogWorkCommand.php
│       └── LogWorkHandler.php
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   └── Requests/
├── Services/
│   ├── Jira/
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

Do not reorganize the project substantially without a concrete reason.

Remove the obsolete Google Chat command input classes when implementing the Vue UI. Do not retain dead slash-command parsing code for possible future use.

## Coding Guidelines

Follow Laravel and Vue conventions where possible.

Prefer:

- constructor dependency injection
- typed PHP properties, parameters, and return values
- immutable DTO/command objects where practical
- Laravel configuration instead of direct environment access
- small focused classes and Vue components
- browser-native form controls where they provide good UX
- explicit loading, success, validation, and failure states
- descriptive method and component names

Avoid:

- static service classes
- global helper functions for business logic
- accessing `env()` outside configuration files
- duplicated parsing or validation logic
- large controllers or Vue components
- Vue Router, Pinia, or a UI framework for the single-page MVP
- catching `Throwable` without a concrete reason
- unnecessary comments that repeat the code

Prefer simple code over clever code.

## Configuration

Environment-specific configuration must live in configuration files.

```php
config('services.jira.url');
config('services.jira.email');
config('services.jira.token');
config('services.google_chat.webhook_url');
```

Do not use `env()` inside application or service classes.

Secrets must never be committed. Expected environment variables include:

```text
APP_TIMEZONE=Asia/Ho_Chi_Minh
JIRA_BASE_URL=
JIRA_EMAIL=
JIRA_API_TOKEN=
GOOGLE_CHAT_WEBHOOK_URL=
WORKLOG_TOTP_SECRET=
```

The Google Chat webhook URL and TOTP secret are sensitive server-side credentials. Never expose either value to the browser, logs, or source control.

Update `.env.example` whenever an environment variable is implemented. Never place real credentials or hashes in it.

## Authentication

The deployed UI and worklog API must not be public.

Use a minimal single-user Laravel session flow protected by one configured TOTP secret. Protect both the UI and `POST /api/worklogs`; hiding the form alone is insufficient.

Use Laravel's existing session, cookie, rate-limiting, and CSRF capabilities. Do not add:

- a users table
- registration
- password reset
- email verification
- OAuth
- Google login
- Sanctum unless a later requirement needs token-based clients

Authentication must be complete before public Render deployment.

## Worklog Input Rules

The Vue form accepts:

```text
ticket    required
duration  required
date      optional/default today
time      optional/default now
```

All worklog times are interpreted using `Asia/Ho_Chi_Minh` unless configuration explicitly overrides it. Never rely on the server/container timezone.

Supported duration examples:

```text
15m
30m
1h
2h
1h30m
2h15m
8h
```

Duration must be normalized into seconds for Jira. Do not duplicate duration or date parsing in controllers, Vue components, or integrations. Client-side validation improves UX but server-side validation remains authoritative.

## Jira Integration

All Jira communication must go through the dedicated Jira client/service.

Do not call Jira directly from controllers, Vue-related code, or Google Chat services.

Worklog creation uses Jira Cloud's issue worklog endpoint. Jira credentials must remain server-side.

## Google Chat Integration

Google Chat is an output notification integration only.

```text
Jira success
    ↓
GoogleChatNotifier
    ↓
Incoming Webhook
    ↓
Google Chat space
```

Do not implement a Google Chat app, slash command, command parser, Google request verification, or Google OAuth for the current product direction.

If the webhook is missing or delivery fails, log a safe warning and return a successful worklog result with notification status. Do not expose the webhook URL or convert the Jira success into an API failure.

## API

The Vue UI uses `POST /api/worklogs`.

```json
{
  "ticket": "BKM4-1234",
  "duration": "2h15m",
  "date": "05/09/2026",
  "time": "14:30"
}
```

The endpoint must require the authenticated Laravel session and CSRF protection.

## Testing Requirements

Add tests for behavior rather than implementation details. At minimum test:

- duration and date/time parsing
- valid and invalid worklog requests
- Jira success and failure mapping
- guest access rejection
- login success, failure, logout, and rate limiting
- Vue-facing response contract
- Google Chat notification success and failure
- Jira success remaining successful when notification delivery fails

Mock Jira and Google Chat HTTP calls in automated tests. Never call real external services from the normal test suite.

## Docker and Render

The application must be runnable locally with Docker, including compiled Vite assets, and compatible with Render deployment.

Keep the image minimal and reproducible. Do not add database, Redis, or queue containers.

Before public deployment, verify authentication protects the UI and API and all secrets are supplied through Render environment variables.

## Before Completing a Task

Before considering implementation complete:

1. Run formatting/linting if configured.
2. Run the relevant frontend build and test commands.
3. Run the complete PHP test suite.
4. Verify no secrets were committed.
5. Verify `.env.example` is current.
6. Verify implementation follows `ARCHITECTURE.md` and `DESIGN.md`.
7. Update `TASKS.md` when implementation progress changes.
8. Do not mark a task complete if tests or the production build fail.

## Scope Control

Implement only the current phase in `TASKS.md`.

Do not proactively implement future phases. In particular, do not add multi-user support, database persistence, Jira OAuth, worklog history, worklog editing/deletion, Google Chat interactive commands, queues, or deployment infrastructure unless the current task explicitly includes them.

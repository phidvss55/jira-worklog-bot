# Architecture

## 1. Overview

Jira Worklog Bot is a personal integration service for logging work from Google Chat into Jira Cloud.

The target interaction is:

```text
/log BKM4-1234 2h15m
```

The application is intentionally designed for one user.

## 2. System Context

```text
┌──────────────────────┐
│     Google Chat      │
│                      │
│ /log BKM4-1234 2h15m │
└──────────┬───────────┘
           │
           │ HTTPS interaction event
           ▼
┌──────────────────────┐
│       Laravel        │
│                      │
│   Render / Docker    │
└──────────┬───────────┘
           │
           │ Jira REST API
           ▼
┌──────────────────────┐
│      Jira Cloud      │
│                      │
│      Worklogs        │
└──────────────────────┘
```

Laravel is the trusted backend between Google Chat and Jira.

## 3. Architectural Goals

The architecture should be:

- simple
- stateless
- easy to test
- inexpensive to host
- easy to understand
- isolated from external integrations
- suitable for a single user

This application does not require enterprise architecture.

## 4. Deployment

Target deployment:

```text
Developer
    │
    │ git push
    ▼
GitHub
    │
    ▼
Render
    │
    │ build Dockerfile
    ▼
Laravel Container
```

Target hosting is Render Free Web Service during development/personal usage.

The application should not depend on persistent local filesystem state.

## 5. Layers

Use three conceptual layers.

### HTTP / Integration Layer

Responsibilities:

- receive HTTP requests
- validate request shape
- authenticate external requests
- translate external protocols into application commands
- format responses

Examples:

```text
WorklogController
GoogleChatController
VerifyGoogleChatRequest
GoogleChatCommandParser
```

This layer must not contain Jira HTTP implementation details.

### Application Layer

Coordinates the use case.

Primary use case:

```text
LogWork
```

Components:

```text
LogWorkCommand
LogWorkHandler
```

Example flow:

```text
Controller
    ↓
LogWorkCommand
    ↓
LogWorkHandler
    ↓
JiraClient
```

The application layer should not know whether the request originated from Google Chat or the manual API.

### Integration / Service Layer

Handles external systems and reusable parsing utilities.

Examples:

```text
JiraClient
DurationParser
WorklogDateParser
```

Jira-specific HTTP calls belong only in the Jira integration.

## 6. Application Flow

The canonical use case is:

```text
Input
  │
  ├── ticket
  ├── duration
  ├── optional date
  └── optional time
  │
  ▼
Validate
  │
  ▼
Parse Duration
  │
  └── 2h15m → 8100 seconds
  │
  ▼
Parse Started Time
  │
  └── Asia/Ho_Chi_Minh
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
  ▼
Jira Cloud
```

## 7. Project Structure

Target structure:

```text
app/
├── Application/
│   └── Worklog/
│       ├── LogWorkCommand.php
│       └── LogWorkHandler.php
│
├── Http/
│   ├── Controllers/
│   │   ├── WorklogController.php
│   │   └── GoogleChatController.php
│   │
│   ├── Middleware/
│   │   └── VerifyGoogleChatRequest.php
│   │
│   └── Requests/
│       └── StoreWorklogRequest.php
│
├── Services/
│   ├── Jira/
│   │   └── JiraClient.php
│   │
│   └── GoogleChat/
│       ├── GoogleChatCommandParser.php
│       └── GoogleChatResponseBuilder.php
│
└── Support/
    ├── DurationParser.php
    └── WorklogDateParser.php
```

Not every class needs to exist immediately.

Create components only when their implementation phase requires them.

## 8. Worklog Model

A worklog operation consists conceptually of:

```text
ticket
duration
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

## 9. Duration Parsing

Input:

```text
2h15m
```

Output:

```text
8100
```

Supported grammar:

```text
<hours>h
<minutes>m
<hours>h<minutes>m
```

Examples:

```text
15m
30m
1h
2h
1h30m
2h15m
8h
```

Duration must be greater than zero.

## 10. Date and Time

Timezone:

```text
Asia/Ho_Chi_Minh
```

Supported input modes:

### No explicit date/time

```text
/log BKM4-1234 2h15m
```

Started:

```text
now
```

### Time only

```text
/log BKM4-1234 2h15m 14:30
```

Started:

```text
today at 14:30
```

### Date and time

```text
/log BKM4-1234 2h15m 04/09/2026 14:30
```

Started:

```text
2026-09-04 14:30 Asia/Ho_Chi_Minh
```

Never infer these values from server timezone.

## 11. Jira Integration

Jira Cloud is the system of record for worklogs.

Laravel communicates with Jira through REST API.

Conceptual request:

```text
POST /rest/api/3/issue/{issueKey}/worklog
```

Payload conceptually contains:

```json
{
  "started": "...",
  "timeSpentSeconds": 8100
}
```

Authentication credentials:

```text
JIRA_BASE_URL
JIRA_EMAIL
JIRA_API_TOKEN
```

The exact Jira HTTP implementation belongs in `JiraClient`.

## 12. Google Chat Integration

Google Chat will be added after the core Laravel/Jira flow is operational.

Google Chat responsibilities:

```text
Receive event
    ↓
Verify Google request
    ↓
Verify allowed user
    ↓
Parse /log command
    ↓
Call existing LogWork use case
    ↓
Format response
```

The Google Chat adapter must not duplicate worklog logic.

## 13. Authentication

### Jira

Server-to-server authentication using the personal Jira credential configured on the deployment.

### Google Chat

Requests must be verified as authentic Google Chat requests.

Additionally, because this is a single-user application, requests must be restricted to the configured allowed Google identity.

## 14. Persistence

No application database is required.

Do not introduce:

```text
MySQL
PostgreSQL
SQLite
Redis
```

unless future product requirements require persistence.

Jira remains the source of truth for worklogs.

## 15. Error Handling

External integration errors must be converted into application-friendly errors.

Examples:

```text
Ticket not found
Invalid duration
Invalid date
Invalid time
Jira authentication failed
Jira rejected worklog
Google request verification failed
Unauthorized Google user
```

Do not expose:

- API tokens
- raw credentials
- sensitive headers
- unnecessary external stack traces

## 16. Testing Strategy

### Unit Tests

Test pure behavior:

```text
DurationParser
WorklogDateParser
GoogleChatCommandParser
```

### Feature Tests

Test:

```text
POST /api/worklogs
```

with Jira mocked.

Later test Google Chat HTTP event handling separately.

### Integration Testing

Real Jira calls are manual/integration tests and must not run in the normal automated test suite.

## 17. Explicit Non-Goals

For the initial product:

- no database
- no frontend
- no dashboard
- no Jira OAuth
- no multi-user support
- no Google login page
- no queues
- no Redis
- no scheduler
- no microservices
- no DDD aggregates
- no event sourcing

These can be reconsidered only when a concrete requirement appears.

## 18. Architectural Principle

The central architectural rule is:

```text
Google Chat and HTTP are inputs.

Jira is an output.

LogWork is the application use case.
```

Therefore:

```text
Google Chat ──┐
              │
HTTP API ─────┼──> LogWork ──> Jira
              │
Future CLI ───┘
```

Adding or changing an input adapter must not require rewriting the Jira integration or the core worklog use case.

# Repository Guidelines

## Project Overview

This project is a personal Jira Worklog Bot built with Laravel.

The application allows a single user to log work to Jira using a simple command such as:

```text
/log BKM4-1234 2h15m
/log BKM4-1234 2h15m 14:30
/log BKM4-1234 2h15m 04/09/2026 14:30
```

The final integration flow will be:

```text
Google Chat
    ↓
Laravel API
    ↓
Jira Cloud REST API
```

The application is intended for a single user only.

Do not introduce multi-user architecture unless explicitly requested.

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
- Composer
- PHPUnit / Pest according to the Laravel project default
- Laravel HTTP Client for external HTTP requests
- Docker
- Render for deployment
- Jira Cloud REST API
- Google Chat API in a later implementation phase

Do not introduce additional frameworks or infrastructure unless required.

## Architecture Principles

Keep the application simple.

This is a small stateless integration service, not a large enterprise application.

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
HTTP / Integration Adapter
        ↓
Application
        ↓
Services / External Clients
```

Controllers must remain thin.

Business/application logic must not be implemented directly inside controllers.

External Jira-specific HTTP logic must remain inside the Jira integration layer.

Google Chat-specific parsing and response formatting must remain isolated from Jira integration.

## Expected Project Structure

Prefer the following structure:

```text
app/
├── Application/
│   └── Worklog/
│       ├── LogWorkCommand.php
│       └── LogWorkHandler.php
│
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   └── Requests/
│
├── Services/
│   ├── Jira/
│   └── GoogleChat/
│
└── Support/
    ├── DurationParser.php
    └── WorklogDateParser.php
```

Do not reorganize the project substantially without a concrete reason.

## Coding Guidelines

Follow Laravel conventions where possible.

Prefer:

- constructor dependency injection
- typed properties
- typed parameters
- typed return values
- immutable DTO/command objects where practical
- Laravel configuration instead of direct environment access
- small focused classes
- descriptive method names

Avoid:

- static service classes
- global helper functions for business logic
- accessing `env()` outside configuration files
- duplicated parsing logic
- large controllers
- catching `Throwable` without a concrete reason
- unnecessary comments that simply repeat the code

Prefer simple code over clever code.

## Configuration

Environment-specific configuration must live in configuration files.

For example:

```php
config('services.jira.url');
config('services.jira.email');
config('services.jira.token');
```

Do not use:

```php
env('JIRA_TOKEN')
```

inside application or service classes.

Secrets must never be committed.

Expected future environment variables include:

```text
APP_TIMEZONE=Asia/Ho_Chi_Minh

JIRA_BASE_URL=
JIRA_EMAIL=
JIRA_API_TOKEN=

GOOGLE_ALLOWED_USER=
GOOGLE_CHAT_AUDIENCE=
```

Update `.env.example` whenever a new environment variable is introduced.

## Date and Time Rules

All user-entered worklog times are interpreted using:

```text
Asia/Ho_Chi_Minh
```

unless configuration explicitly overrides it.

Never rely on the server/container timezone.

The application must support:

```text
/log BKM4-1234 2h15m
/log BKM4-1234 2h15m 14:30
/log BKM4-1234 2h15m 04/09/2026 14:30
```

See `DESIGN.md` for exact behavior.

## Duration Rules

Supported examples:

```text
15m
30m
1h
2h
1h30m
2h15m
8h
```

Duration must eventually be normalized into seconds for Jira.

Examples:

```text
15m    → 900
1h     → 3600
1h30m  → 5400
2h15m  → 8100
```

Do not duplicate duration parsing across controllers or integrations.

## Jira Integration

All Jira communication must go through a dedicated Jira client/service.

Do not call Jira directly from:

- controllers
- Google Chat services
- command parsers

The application will use Jira Cloud REST API.

Worklog creation will use Jira's issue worklog endpoint.

Jira credentials must remain server-side.

## Google Chat Integration

Google Chat is an adapter around the existing worklog application flow.

Google Chat integration must not contain Jira business logic.

Expected future flow:

```text
Google Chat Event
       ↓
Verify Google Request
       ↓
Parse /log command
       ↓
LogWorkCommand
       ↓
LogWorkHandler
       ↓
JiraClient
       ↓
Google Chat Response
```

Google Chat integration is not part of the initial implementation phase unless `TASKS.md` indicates otherwise.

## API

During development, expose a temporary/manual endpoint:

```text
POST /api/worklogs
```

Example:

```json
{
  "ticket": "BKM4-1234",
  "duration": "2h15m",
  "date": "05/09/2026",
  "time": "14:30"
}
```

This endpoint exists to test the core worklog flow independently of Google Chat.

## Testing Requirements

Add tests for behavior rather than implementation details.

At minimum test:

### DurationParser

Valid:

```text
15m
1h
1h30m
2h15m
8h
```

Invalid examples:

```text
abc
2hours
-1h
0h
```

### WorklogDateParser

Test:

- no date/time
- time only
- explicit date and time
- invalid date
- invalid time
- configured timezone

### API

Test:

- valid worklog request
- invalid ticket
- invalid duration
- invalid date
- invalid time
- Jira service failure

Mock external Jira HTTP calls in automated tests.

Never call real Jira from the normal test suite.

## Docker

The application must be runnable locally with Docker.

The Docker image should also be compatible with Render deployment.

Keep the image minimal and reproducible.

Do not add database containers, Redis, or other services unless required by future requirements.

## Before Completing a Task

Before considering implementation complete:

1. Run formatting/linting if configured.
2. Run the test suite.
3. Verify no secrets were committed.
4. Verify `.env.example` is current.
5. Verify implementation follows `ARCHITECTURE.md`.
6. Update `TASKS.md` when implementation progress changes.
7. Do not mark a task complete if tests are failing.

## Scope Control

When implementing a task, implement only what is necessary for that task.

Do not proactively implement future phases.

For example, while implementing the Laravel core:

Do not also implement:

- Google Chat integration
- Jira OAuth
- database persistence
- user management
- deployment infrastructure

unless the task explicitly asks for them.

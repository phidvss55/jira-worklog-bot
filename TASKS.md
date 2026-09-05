# Implementation Tasks

## Status Legend

```text
[ ] Not started
[x] Completed
[-] In progress
[~] Superseded; remove during the indicated phase
```

---

# Phase 1 — Laravel Core

Goal: Build and test the core Laravel worklog flow without real Jira credentials.

[x] Create the Laravel 12+ project.

[x] Configure `Asia/Ho_Chi_Minh` as the application timezone.

[x] Add the `GET /health` endpoint.

[x] Implement and test `DurationParser`.

[x] Implement and test `WorklogDateParser`.

[x] Implement `LogWorkCommand` and `LogWorkHandler`.

[x] Implement `POST /api/worklogs` with request validation and ticket normalization.

[x] Create a fake Jira implementation and feature-test the complete core flow.

---

# Phase 2 — Jira Cloud Integration

Goal: Replace the fake boundary with real Jira Cloud HTTP communication while keeping automated tests isolated.

[ ] Create a Jira API token manually.

[x] Configure `JIRA_BASE_URL`, `JIRA_EMAIL`, and `JIRA_API_TOKEN` through Laravel configuration.

[x] Update `.env.example` without real credentials.

[x] Implement Jira worklog creation through the dedicated Jira client.

[x] Send issue key, `timeSpentSeconds`, and `started`.

[x] Safely handle success, not found, unauthorized, forbidden, validation, network, and timeout responses.

[x] Add mocked Jira HTTP tests and verify secrets are not exposed.

[ ] Manually test against one real Jira issue.

---

# Superseded Direction — Google Chat Command Input

The previous Phase 3 implemented Google Chat `/log` parsing before the product direction changed.

[x] Remove `GoogleChatCommandParser`.

[x] Remove `ParsedGoogleChatCommand`.

[x] Remove `InvalidGoogleChatCommandException`.

[x] Remove `GoogleChatResponseBuilder`.

[x] Remove their obsolete unit tests and configuration.

Do not implement a Google Chat app, slash-command endpoint, Google request verification, or Google OAuth. Google Chat is now an outgoing notification only.

---

# Phase 3 — Vue UI and Personal Access

Current target.

Goal: Replace command-based input with a small authenticated Vue UI that uses the existing worklog API and application flow.

## Documentation and Cleanup

[x] Update `AGENTS.md`, `ARCHITECTURE.md`, `DESIGN.md`, and `TASKS.md` for the new direction.

[x] Remove the superseded Google Chat command parser, response builder, related DTO/exception, tests, and unused configuration.

## Vue and Vite

[x] Add Vue 3 and the Vue Vite plugin using the existing Laravel Vite setup.

[x] Create the Blade entry point and mount one Vue application.

[ ] Create focused login and worklog form components.

[x] Do not add Vue Router, Pinia, or a UI framework.

[x] Add responsive styling for mobile and desktop.

## Worklog Form

[x] Implement required ticket and duration inputs.

[x] Implement date and time inputs defaulted in the configured application timezone.

[x] Normalize ticket input to uppercase without duplicating authoritative server validation.

[x] Submit to the existing `POST /api/worklogs` endpoint.

[x] Implement loading state and duplicate-submit prevention.

[x] Implement field validation, Jira error, success, notification-warning, and session-expired states.

[x] Preserve form values after a failed submission.

## Personal Session Authentication

[ ] Add a server-configured `APP_ACCESS_PASSWORD_HASH` placeholder to `.env.example`.

[ ] Implement a minimal login endpoint and screen using the configured password hash.

[ ] Regenerate the Laravel session after successful login and logout.

[ ] Protect both the application page and `POST /api/worklogs` with session authentication.

[ ] Use Laravel CSRF protection for state-changing browser requests.

[ ] Rate-limit failed login attempts.

[ ] Do not add a users table, registration, password recovery, OAuth, Google login, or Sanctum.

## Verification

[ ] Add feature tests for guest rejection, successful login, invalid password, logout, rate limiting, and authenticated worklog submission.

[x] Verify relevant accessibility behavior and responsive layout.

[x] Run the Vite production build.

[x] Run formatting and the complete PHP test suite.

[x] Stop after Phase 3 unless explicitly instructed to continue.

---

# Phase 4 — Google Chat Notification

Goal: Notify a Google Chat space after Jira successfully creates a worklog.

[ ] Add `GOOGLE_CHAT_WEBHOOK_URL` to `.env.example` without a real URL.

[ ] Map the environment variable through `config/services.php`.

[ ] Implement `GoogleChatNotifier` using Laravel HTTP Client.

[ ] Send a concise message containing ticket, human-readable duration, and started date/time.

[ ] Invoke the notifier only after Jira succeeds.

[ ] Return `notificationSent: true` after successful delivery.

[ ] On missing configuration or delivery failure, log a safe warning and return `notificationSent: false` while keeping the worklog response successful.

[ ] Never retry by creating another Jira worklog.

[ ] Add mocked HTTP tests for notification success, missing configuration, timeout, rejection, and safe logging.

[ ] Test explicitly that Jira failure does not notify and notification failure does not change Jira success.

---

# Phase 5 — Docker

Goal: Produce a minimal reproducible image that serves Laravel and compiled Vue assets.

[x] Add a production-ready `Dockerfile`.

[x] Add `.dockerignore`.

[x] Install PHP and JavaScript dependencies reproducibly.

[x] Compile Vite assets during the image build without embedding runtime secrets.

[x] Publish `phidinh/jira-worklog-bot:latest` and an immutable commit SHA tag on every push to `main` through GitHub Actions.

[ ] Build and run the container locally.

[ ] Verify `GET /health`, login, the Vue page, and authenticated `POST /api/worklogs`.

[x] Run automated tests before deployment.

---

# Phase 6 — Render

Goal: Deploy the protected application and configure external integrations.

[ ] Create a Render Web Service and connect the GitHub repository.

[ ] Configure Docker deployment.

[ ] Configure `APP_KEY`, secure production session settings, `APP_ACCESS_PASSWORD_HASH`, Jira credentials, and `GOOGLE_CHAT_WEBHOOK_URL` as Render secrets.

[ ] Deploy and verify the public health endpoint.

[ ] Verify unauthenticated users cannot access the UI or worklog API.

[ ] Verify login and the Vue production build.

[ ] Verify Jira integration from Render.

[ ] Verify Google Chat incoming-webhook delivery from Render.

---

# Phase 7 — End-to-End Verification

[ ] Verify normal daily usage with ticket and duration only.

[ ] Verify explicit date and time input.

[ ] Verify ticket normalization.

[ ] Verify invalid ticket, duration, date, and time behavior.

[ ] Verify login rejection, rate limiting, logout, session expiry, and protected API behavior.

[ ] Verify Jira success with Google Chat notification success.

[ ] Verify Jira success remains successful when Google Chat notification fails.

[ ] Verify Jira failure does not send a Google Chat notification.

[ ] Verify repeated clicks cannot accidentally create duplicate worklogs.

[ ] Verify credentials and secrets are absent from the repository, browser bundle, logs, responses, and committed Docker configuration.

---

# Current Scope

The agent must not implement the following unless explicitly requested:

```text
database persistence
Redis
queues
multi-user support
registration or password recovery
Jira OAuth
Google Chat app or slash commands
Google OAuth
Vue Router
Pinia
UI framework
dashboard or worklog history
worklog editing, deletion, or undo
comments/descriptions
```

---

# Current Milestone

Current target:

```text
Phase 3 — Vue UI and Personal Access
```

Phase 3 is complete when:

```text
obsolete Google Chat input code is removed
        +
the authenticated Vue UI works on mobile and desktop
        +
POST /api/worklogs is protected and used by the UI
        +
loading, validation, success, and error states work
        +
the Vite production build passes
        +
the complete PHP test suite passes
```

Do not begin Google Chat notification, Docker, or Render work until Phase 3 is complete and the user explicitly requests the next phase.

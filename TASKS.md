# Implementation Tasks

## Status Legend

```text
[ ] Not started
[x] Completed
[-] In progress
```

---

# Phase 1 — Laravel Core

Goal:

Build and test the core Laravel worklog flow without Google Chat or real Jira credentials.

## Bootstrap

[x] Create Laravel 12+ project.

[x] Configure application timezone using:

```text
Asia/Ho_Chi_Minh
```

[x] Add `/health` endpoint.

Expected:

```text
GET /health
```

returns HTTP `200`.

[x] Ensure `.env.example` contains required non-secret configuration.

---

## Duration Parser

[x] Implement:

```text
app/Support/DurationParser.php
```

Support:

```text
15m
30m
1h
2h
1h30m
2h15m
8h
```

Return duration in seconds.

Examples:

```text
15m    → 900
1h     → 3600
1h30m  → 5400
2h15m  → 8100
```

Reject invalid values such as:

```text
abc
2hours
-1h
0h
```

[x] Add unit tests.

---

## Worklog Date Parser

[x] Implement:

```text
app/Support/WorklogDateParser.php
```

Support:

```text
no date/time
time only
date + time
```

Examples:

```text
null + null
→ now

null + 14:30
→ today 14:30

04/09/2026 + 14:30
→ 2026-09-04 14:30
```

Use configured timezone.

Do not depend on server timezone.

[x] Add unit tests.

---

## Worklog Application Use Case

[x] Implement:

```text
app/Application/Worklog/LogWorkCommand.php
```

Command should contain the normalized values needed by the worklog use case.

[x] Implement:

```text
app/Application/Worklog/LogWorkHandler.php
```

Responsibilities:

```text
receive worklog command
    ↓
normalize/coordinate worklog data
    ↓
call JiraClient
    ↓
return result
```

Keep HTTP-specific behavior outside the handler.

---

## Manual Worklog API

[x] Implement:

```text
POST /api/worklogs
```

[x] Create:

```text
StoreWorklogRequest
WorklogController
```

Example request:

```json
{
  "ticket": "BKM4-1234",
  "duration": "2h15m",
  "date": "05/09/2026",
  "time": "14:30"
}
```

Validation:

```text
ticket   required
duration required
date     optional
time     optional
```

[x] Normalize Jira ticket key to uppercase.

---

## Mock Jira Client

[x] Create initial Jira client abstraction/service.

The first implementation must not require real Jira credentials.

It should allow the complete application flow to be tested.

Do not call real Jira during Phase 1.

[x] Add Feature tests for:

```text
valid request
invalid ticket format
invalid duration
invalid date
invalid time
```

---

# Phase 2 — Jira Cloud Integration

Do not begin this phase until Phase 1 tests pass.

[ ] Create Jira API token manually.

[x] Configure:

```text
JIRA_BASE_URL
JIRA_EMAIL
JIRA_API_TOKEN
```

[x] Update `.env.example` without real credentials.

[x] Implement real Jira HTTP communication in:

```text
JiraClient
```

[x] Implement worklog creation using Jira Cloud REST API.

[x] Send:

```text
issue key
timeSpentSeconds
started
```

[x] Handle:

```text
success
ticket not found
unauthorized
forbidden
validation failure
network failure
timeout
```

[x] Ensure sensitive Jira data is never exposed in application responses.

[x] Add mocked HTTP tests.

[ ] Manually test against one real Jira issue.

---

# Phase 3 — Google Chat Command Parser

Do not configure Google Chat yet.

First implement and test command parsing independently.

[ ] Implement:

```text
GoogleChatCommandParser
```

Support:

```text
/log BKM4-1234 2h15m

/log BKM4-1234 2h15m 14:30

/log BKM4-1234 2h15m 04/09/2026 14:30
```

[ ] Normalize parsed data into the same worklog application input used by the manual API.

[ ] Add parser unit tests.

[ ] Implement Google Chat response builder.

---

# Phase 4 — Google Chat Integration

Do not begin until Jira integration works.

[ ] Create/configure Google Cloud project.

[ ] Enable Google Chat API.

[ ] Configure Google Chat App.

[ ] Configure HTTP interaction endpoint.

[ ] Implement:

```text
POST /api/google-chat
```

[ ] Implement Google Chat request verification.

[ ] Restrict requests to configured single Google user.

[ ] Configure:

```text
GOOGLE_ALLOWED_USER
GOOGLE_CHAT_AUDIENCE
```

[ ] Connect command parsing to:

```text
LogWorkHandler
```

Expected flow:

```text
Google Chat
    ↓
GoogleChatController
    ↓
GoogleChatCommandParser
    ↓
LogWorkHandler
    ↓
JiraClient
    ↓
GoogleChatResponseBuilder
```

[ ] Test valid command.

[ ] Test invalid command.

[ ] Test unauthorized user.

[ ] Test invalid Google request.

---

# Phase 5 — Docker

[ ] Add production-ready `Dockerfile`.

[ ] Add `.dockerignore`.

[ ] Build locally.

[ ] Run container locally.

[ ] Verify:

```text
GET /health
```

[ ] Verify:

```text
POST /api/worklogs
```

[ ] Run automated tests before deployment.

---

# Phase 6 — Render

[ ] Create Render Web Service.

[ ] Connect GitHub repository.

[ ] Configure Docker deployment.

[ ] Configure environment variables.

[ ] Deploy.

[ ] Verify public `/health`.

[ ] Verify Jira integration from Render.

[ ] Configure Render URL as Google Chat endpoint.

[ ] Perform end-to-end test:

```text
Google Chat
    ↓
Render
    ↓
Laravel
    ↓
Jira
```

---

# Phase 7 — Final Verification

[ ] Verify:

```text
/log BKM4-1234 30m
```

[ ] Verify:

```text
/log BKM4-1234 2h15m
```

[ ] Verify:

```text
/log BKM4-1234 2h15m 14:30
```

[ ] Verify:

```text
/log BKM4-1234 2h15m 04/09/2026 14:30
```

[ ] Verify invalid ticket behavior.

[ ] Verify invalid duration behavior.

[ ] Verify invalid date/time behavior.

[ ] Verify unauthorized Google user cannot use the bot.

[ ] Verify Jira credentials are not present in:

```text
repository
logs
responses
Docker image configuration committed to Git
```

---

# Current Scope

The agent must not implement the following unless explicitly requested:

```text
database
Redis
queues
multi-user support
Jira OAuth
frontend
dashboard
worklog history
Google login UI
undo worklog
worklog editing
worklog deletion
comments/descriptions
```

---

# Current Milestone

Current target:

```text
Phase 1 — Laravel Core
```

Stop after Phase 1 unless explicitly instructed to continue.

Phase 1 is complete when:

```text
Laravel runs locally
        +
/health works
        +
duration parsing works
        +
date/time parsing works
        +
POST /api/worklogs works
        +
Jira is mocked
        +
tests pass
```

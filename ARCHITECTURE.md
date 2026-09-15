# MVP2 Architecture

## Goal

MVP2 optimizes Jira work logging for a single user by minimizing keyboard input. The application lists the user's work in the active sprint, groups subtasks under their parent Jira issues, and allows worklogs to be created from subtasks only.

## Core Business Rule

Worklogs MUST only be created against Jira subtasks.

Parent issues are display/grouping context only. They are never selectable worklog targets and the backend must reject attempts to quick-log against a non-subtask even if the frontend is bypassed.

## System Flow

Browser / Vue UI
-> Laravel authenticated API
-> Jira read API (active sprint, current user, issues/subtasks)
-> normalized sprint view model
-> user selects a subtask
-> quick duration picker
-> existing worklog application flow
-> Jira worklog creation
-> Google Chat webhook notification on Jira success

## Existing Components to Preserve

- TOTP authentication and Laravel session protection
- Existing POST /api/worklogs behavior where still needed for manual/internal compatibility
- Jira client authentication/configuration
- Google Chat notifier and failure semantics
- Docker/Render deployment
- No database

## Read Side

Introduce a sprint/application read use case that returns UI-oriented data rather than raw Jira payloads.

Suggested components:

- Application/Sprint/GetActiveSprintHandler
- Application/Sprint DTOs/value objects as needed
- Http/Controllers/SprintController
- Jira read methods behind the existing Jira integration boundary

Suggested endpoint:

- GET /api/sprint

The endpoint must be protected by the existing TOTP session middleware.

## Sprint Resolution

The implementation should determine the active sprint relevant to the configured Jira board/project. Board/project identifiers should be runtime configuration rather than frontend constants.

Expected configuration may include values such as:

- JIRA_BOARD_ID
- JIRA_PROJECT_KEY (only if needed by the selected Jira API strategy)

Do not expose Jira credentials or unnecessary Jira metadata to Vue.

## Issue Selection Rules

The UI should show parent issues when they provide context for at least one relevant subtask.

A subtask is relevant when it belongs to the active sprint context and is assigned to the authenticated Jira account/current user according to the Jira query strategy.

Parent issues may be assigned to another person. They should still be shown if they contain at least one relevant subtask assigned to the current user.

Only relevant subtasks are selectable.

## Normalized API Shape

Conceptual response:

{
"sprint": {
"id": 123,
"name": "Sprint 24",
"startDate": "2026-09-09",
"endDate": "2026-09-22"
},
"issues": [
{
"key": "BKM4-1201",
"summary": "DSOP Action Validation",
"status": "In Progress",
"subtasks": [
{
"key": "BKM4-1234",
"summary": "Implement validation",
"status": "In Progress",
"issueType": "Sub-task",
"estimateMinutes": 120
}
]
}
]
}

The frontend must not depend on Jira's raw REST response shape.

## Quick Worklog

Quick logging must reuse the existing worklog application use case rather than create a parallel Jira-writing implementation.

The quick-log path must enforce the subtask-only rule server-side. Prefer a dedicated application validation/boundary that confirms the selected Jira issue is a subtask before the write is executed.

Do not trust a frontend `issueType` value as proof that an issue is a subtask.

## Duration Rules

Quick picker rules:

- minimum: 15 minutes
- maximum per quick worklog: 7 hours / 420 minutes
- increment/decrement step: 15 minutes

Frontend should represent selection internally as integer minutes.

Common presets:

- 15m
- 30m
- 45m
- 1h
- 1h30m
- 2h
- 3h
- 4h
- 5h
- 6h
- 7h

Other quarter-hour values are reached using +/- 15m.

The 7-hour value is a quick-worklog maximum and standard-day UX target. It is not a rule that daily accumulated Jira worklogs can never exceed 7 hours.

## Today's Summary

MVP2 should display today's logged time for the current Jira user and a 7-hour target.

Example:

- 5h 30m / 7h

Going above 7 hours should be displayed as an informational/warning state, not blocked.

Today's summary must refresh after a successful worklog.

The read use case calculates the configured product-local day server-side. It first uses Jira enhanced JQL search for issues with the authenticated user's worklogs in that date range, uses the embedded worklogs when complete, and fetches paginated issue worklogs only when Jira reports an incomplete embedded page. The Jira integration then filters by the authenticated account ID and the local start/end boundary before returning normalized data to the application layer.

## Frontend Architecture

Keep Vue small and local-state driven. Do not add Pinia/Vuex unless future requirements justify it.

Suggested conceptual components:

- SprintHeader
- IssueGroup
- SubtaskItem
- QuickWorklog
- DurationPicker
- TodaySummary
- ManualWorklog fallback if retained

Parent IssueGroup components are not selectable for logging.

## Security

All MVP2 APIs are protected by the existing TOTP session authentication except public health/login routes.

Unauthenticated requests must never reach Jira or Google Chat.

CSRF/session behavior from MVP1 must remain intact.

## Error Handling

Handle at least:

- no active sprint
- no assigned subtasks
- Jira authentication/permission errors
- Jira rate limiting
- Jira unavailable
- selected issue is not a subtask
- worklog failure
- session expiry

Do not expose Jira tokens, Google Chat webhook URL, TOTP secret, or raw sensitive upstream errors.

## Non-Goals

MVP2 does not include:

- logging work on parent issues
- creating/editing Jira issues
- changing status
- comments
- sprint administration
- board administration
- drag and drop
- multi-user support
- database persistence
- edit/delete worklogs
- cloning Jira's board UI

## MVP2 Completion Flow

TOTP login
-> active sprint loads
-> parent issues group the user's subtasks
-> select subtask
-> choose duration with presets or +/- 15m
-> log work
-> Jira succeeds
-> Google Chat notification succeeds or safely fails as secondary side effect
-> today's total refreshes

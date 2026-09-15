Read `AGENTS.md`, `ARCHITECTURE.md`, `DESIGN.md`, and `TASKS.md`.

MVP2 Phase 1 is complete and the real Jira integration now returns the active sprint successfully.

Implement **MVP2 Phase 2 — Active Sprint Task UI only**.

Do not implement quick worklog/duration selection yet.

Before modifying code:

1. Inspect the existing Vue/Vite application.
2. Inspect the current MVP1 manual worklog UI.
3. Inspect the actual `/api/sprint` contract.
4. Inspect `DESIGN.md`.
5. Produce a short implementation plan.

## Real API Contract

The backend currently returns:

- `sprint.id`
- `sprint.name`
- `sprint.startDate`
- `sprint.endDate`
- `issues[]`

Each parent issue contains:

- `key`
- `summary`
- `status`
- `issueType`
- `subtasks[]`

Each sub-task contains:

- `key`
- `summary`
- `status`
- `issueType`
- `loggable`

Real Jira sub-task issue types include custom names such as:

- `Sub-Imp`
- `Sub Automation`

Do NOT assume the issue type name is literally `Sub-task`.

The backend is the source of truth for whether an item is loggable.

## Important Business Rule

Only sub-tasks may eventually receive worklogs.

Parent issues are display/grouping context only.

In this phase there is no worklog interaction yet.

Do not make parent issue cards look selectable or actionable.

## Goal

Render a minimal active-sprint view from:

`GET /api/sprint`

The UI should make it easy to visually scan:

Parent issue
→ its relevant sub-tasks
→ each sub-task's status

Do not clone the Jira board UI.

This application is a focused personal worklog tool.

## Layout

Show a compact sprint header:

- sprint name
- start date
- end date
- optional refresh action

Then render parent issue groups.

Each parent group should show:

- issue key
- summary
- status
- issue type only when useful and visually subtle

Nested beneath it, render its sub-tasks.

Each sub-task should show:

- key
- summary
- status

Sub-task issue type may be shown subtly if it improves clarity, but it should not dominate the UI.

## Status Presentation

The real Jira data contains inconsistent casing, for example:

- `TO DO`
- `IN DEVELOPMENT`
- `Done`
- `In Progress`

Do not modify backend/domain values solely for presentation.

Create a small frontend presentation formatter that renders these consistently:

- `TO DO` → `To Do`
- `IN DEVELOPMENT` → `In Development`
- `Done` → `Done`
- `In Progress` → `In Progress`

Status matching/styling must be case-insensitive.

Do not assume Jira has only three statuses.

Unknown/new Jira statuses must still render safely.

## Done Sub-tasks

Do NOT hide completed sub-tasks.

Done sub-tasks must remain visible because the user may finish a task before logging the work.

Conceptually all backend-returned `loggable: true` sub-tasks remain candidates for the later quick-worklog phase regardless of current status.

## States

Implement clean states for:

- loading
- loaded sprint
- no active sprint
- active sprint with no relevant tasks
- Jira/API error
- authentication/session expiration

If the API returns `401`, preserve the existing behavior of navigating back to `/login`.

Provide a manual Refresh action.

Do not add aggressive polling.

## Responsive Behavior

Optimize primarily for:

- desktop browser
- mobile browser

The user should be able to scan tasks quickly.

Avoid:

- large cards
- excessive whitespace
- tables
- horizontal scrolling
- Jira-style multi-column boards

Use a compact vertical hierarchy.

## Components

Refactor into small components where useful, conceptually:

- `SprintHeader`
- `IssueGroup`
- `SubtaskItem`

Follow the existing project conventions rather than forcing these exact names.

Do not introduce:

- Pinia
- Vuex
- Vue Router
- UI frameworks
- large new dependencies

## Existing Manual Worklog

Preserve the existing MVP1 manual worklog capability.

Do not delete it.

Integrate the sprint view into the current application in the simplest design consistent with `DESIGN.md`.

The manual form can remain available as a fallback, but do not redesign it extensively in this phase.

## Security

Do not expose:

- Jira token
- Jira email if not already intentionally public
- Google Chat webhook
- TOTP secret

All Jira access continues through Laravel.

Vue calls only the Laravel API.

## Tests / Verification

Do not add a large frontend testing framework if one does not already exist.

At minimum verify:

- Vite production build
- existing Laravel tests
- sprint endpoint tests remain passing
- UI handles the real response structure
- status formatter handles inconsistent casing
- parent issues are visually non-actionable
- done sub-tasks remain visible
- 401 returns user to login
- refresh reloads sprint data

Run:

1. Pint
2. Composer validation
3. complete Laravel tests
4. Vite production build

Update only genuinely completed Phase 2 tasks in `TASKS.md`.

Report:

- files created
- files modified
- component structure
- sprint UI structure
- status presentation approach
- loading/empty/error behavior
- how MVP1 manual worklog was preserved
- build/test results

Do not implement duration picker or quick worklog yet.

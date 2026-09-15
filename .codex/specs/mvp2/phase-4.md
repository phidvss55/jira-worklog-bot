Read `AGENTS.md`, `ARCHITECTURE.md`, `DESIGN.md`, and `TASKS.md` completely before making changes.

MVP2 Phases 1–4 are complete and have been verified against real Jira.

Current working capabilities include:

- TOTP authentication
- active Jira sprint retrieval
- parent issues grouped with nested relevant sub-tasks
- only Jira sub-tasks are loggable
- Quick Worklog with duration presets and ±15m
- MVP1 Manual Worklog fallback
- Jira worklog creation
- Google Chat notification after successful Jira worklog
- today's worklog summary
- 7-hour daily target

The real production-like `/api/worklogs/today` endpoint has been verified and currently returns 420 minutes / 7 hours correctly.

Implement **MVP2 Phase 5 — UX Polish, Regression Review, Production Readiness, and Release Preparation only**.

Do not introduce major new product features.

Before modifying code:

1. Read all project documentation.
2. Inspect all MVP1 and MVP2 implementation.
3. Inspect current Vue component structure.
4. Inspect Laravel routes/controllers/application services.
5. Inspect Jira integration.
6. Inspect Google Chat integration.
7. Inspect TOTP authentication.
8. Inspect Docker/Render configuration.
9. Inspect current tests.
10. Produce a concise review/implementation plan before changing anything.

# Goal

Finish MVP2 as a stable daily-use release.

The primary user journey should feel:

Login
→ see active sprint
→ scan my sub-tasks
→ select sub-task
→ choose duration
→ log work
→ see success
→ today's total refreshes
→ Google Chat notification arrives

The common worklog flow should require minimal keyboard usage.

# 1. UX Review

Review the complete authenticated page as one product rather than isolated components.

Ensure the visual hierarchy prioritizes:

1. today's progress
2. active sprint
3. parent issue context
4. loggable sub-tasks
5. quick duration selection
6. manual fallback

Avoid turning the page into a dashboard.

Keep the design compact and minimal.

# 2. Parent vs Sub-task Clarity

Parent issues must clearly behave as grouping/context only.

They must not:

- appear clickable for worklog
- show Log Work actions
- open duration picker
- imply they are selectable

Sub-tasks with `loggable: true` should clearly feel interactive.

Do not rely on color alone to communicate interactivity.

# 3. Status Presentation

Review status formatting from Phase 2.

Real Jira values include:

- `TO DO`
- `IN DEVELOPMENT`
- `Done`
- `In Progress`

Ensure presentation is consistent and case-insensitive.

Unknown Jira statuses must degrade gracefully.

Do not introduce a hardcoded assumption that Jira has only To Do / In Progress / Done.

# 4. Quick Worklog UX

Review Phase 3 interaction.

Required behavior remains:

- select one sub-task
- default duration 30m
- presets:
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

- ±15m
- minimum 15m
- maximum single worklog 7h

Ensure the selected duration is visually obvious.

Ensure the primary button communicates the operation clearly, for example:

`Log 1h 30m`

Prevent duplicate submissions.

Do not require keyboard input in the normal Quick Worklog flow.

# 5. Quick Worklog Success UX

After successful worklog:

- show concise confirmation containing issue key and duration
- refresh today's total from Jira
- do not leave the UI in an ambiguous loading state
- prevent accidental repeated submission

Example:

`✓ Logged 1h 30m to BKM4-5259`

Do not use browser alerts.

Use the existing application's inline feedback pattern.

# 6. Error UX

Review errors across:

- sprint loading
- daily summary
- worklog creation
- Jira failure
- authentication expiration

Failures should be isolated.

Examples:

Daily summary failure
→ sprint remains usable

Sprint refresh failure
→ existing rendered data may remain visible if safe

Worklog failure
→ selected task/duration remain available for retry

401
→ return user to `/login`

Do not expose raw Jira errors, stack traces, tokens, webhook URLs, or TOTP details.

# 7. Today's Progress

Review Phase 4 UI.

Expected states:

0m / 7h
partial
7h / 7h

> 7h

7 hours is a target, not a daily worklog limit.

For exactly 420 minutes:

`7h / 7h`

may display a subtle:

`Target reached`

For >420:

`7h 30m / 7h`
`+30m over target`

Do not treat this as an error.

Cap the visual progress indicator at 100%.

# 8. Refresh Behavior

Provide one predictable refresh behavior.

If a Refresh action already exists, it should refresh the useful read-side data:

- active sprint
- today's summary

Do not introduce polling.

Avoid duplicate Jira requests caused by component lifecycle/re-render mistakes.

# 9. Manual Worklog Fallback

Preserve MVP1 Manual Worklog.

It remains useful for:

- custom date
- custom start time
- manually entering a sub-task not shown in active sprint

The backend must continue enforcing:

**sub-task only**

A parent issue manually entered must be rejected.

Do not add duplicate sub-task validation logic to Vue.

# 10. Authentication Review

Review the existing TOTP authentication.

Verify:

PUBLIC:

- `/health`
- `/login` routes as required

PROTECTED:

- `/`
- `/api/sprint`
- `/api/worklogs/today`
- `POST /api/worklogs`
- logout as appropriate

Unauthenticated API requests must never reach Jira.

Do not redesign authentication.

# 11. Secret Review

Search the tracked repository for accidental credentials.

Ensure none of the following are present in tracked source:

- Jira API token
- Google Chat webhook URL
- Google Chat key/token
- TOTP secret
- real `.env`
- production session secrets

Check that none can enter the Vite frontend bundle.

Do not print secret values in the final report.

# 12. Google Chat Regression

Do not redesign Google Chat.

Verify:

Jira worklog SUCCESS
→ Google Chat notification

Jira worklog FAILURE
→ no success notification

Google Chat FAILURE
→ Jira worklog remains successful

Read-only operations:

GET /api/sprint
GET /api/worklogs/today

must NEVER send Google Chat notifications.

# 13. Jira Read-side Review

Review Phase 1 and Phase 4 query strategies for obvious unnecessary repeated calls.

Do not introduce caching/database unless there is a demonstrated need.

Look specifically for:

- accidental N+1 requests
- duplicate current-user lookups within one request
- repeated sprint fetches
- Vue causing duplicate requests

Make small optimizations only when clearly safe.

Report the resulting Jira request strategy.

# 14. Accessibility / Responsive Review

Verify:

- mobile layout
- desktop layout
- tap targets
- visible keyboard focus
- button labels
- disabled states
- loading states
- status text not communicated solely through color
- duration controls usable on mobile

Do not perform a broad redesign.

# 15. Empty States

Ensure useful states exist for:

- no active sprint
- active sprint with no relevant sub-tasks
- no work logged today
- Jira temporarily unavailable

Keep messages concise.

# 16. Testing / Regression

Run and preserve the complete test suite.

Review coverage for critical MVP2 rules:

- only sub-tasks can receive worklogs
- custom Jira sub-task types work
- parent issues rejected
- unauthenticated requests rejected
- sprint read-side
- daily total
- timezone boundary
- > 7h daily total allowed
- Quick Worklog uses existing write-side
- Google Chat semantics remain correct

Add missing focused tests only where an important rule is currently uncovered.

Do not create tests purely to increase test count.

# 17. Production Build

Verify:

- Pint
- Composer validation
- complete Laravel tests
- Vite production build
- Docker production build

Then run the final image locally using Render-like configuration.

Verify at minimum:

GET /health
→ 200

GET /
unauthenticated
→ login behavior

authenticated flow
→ UI available

Do not use real credentials in committed Docker configuration.

# 18. Documentation

Bring:

- `AGENTS.md`
- `ARCHITECTURE.md`
- `DESIGN.md`
- `TASKS.md`

into sync with the actual final MVP2 implementation.

Do not document planned features as implemented.

Mark only genuinely completed tasks complete.

If useful, add a concise MVP2 section to the existing README, but do not rewrite unrelated documentation.

# 19. Scope Boundary

Do NOT add:

- Jira issue creation
- Jira issue editing
- Jira transitions
- comments
- worklog history UI
- edit/delete worklogs
- weekly/monthly analytics
- charts
- database
- Redis
- multiple users
- OAuth
- PWA
- notifications beyond existing Google Chat
- drag and drop
- Jira board clone

Those belong to future iterations.

# 20. Final Report

Report:

- files changed
- UX polish performed
- regressions/issues discovered
- fixes applied
- Jira request strategy
- security review result
- secret scan result
- authentication route review
- Docker build result
- Vite build result
- Laravel test count/assertions
- remaining known limitations
- exact production smoke-test checklist
- whether the repository is ready for MVP2 release

Do not proceed to another feature phase.

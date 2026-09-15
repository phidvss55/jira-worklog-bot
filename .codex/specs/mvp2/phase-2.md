Read `AGENTS.md`, `ARCHITECTURE.md`, `DESIGN.md`, and `TASKS.md` completely.

MVP2 Phase 1 and Phase 2 are complete:

- Laravel can retrieve the authenticated user's relevant issues/sub-tasks from the active Jira sprint.
- Vue renders the active sprint as parent issue groups with nested sub-tasks.
- Parent issues are display-only.
- The existing MVP1 manual worklog flow still works.

Implement **MVP2 Phase 3 — Quick Worklog + Duration Picker only**.

Before modifying code:

1. Inspect the existing sprint Vue components.
2. Inspect the existing MVP1 manual worklog component.
3. Inspect the existing `POST /api/worklogs` contract.
4. Inspect the backend worklog validation and Jira client.
5. Inspect the Google Chat notification flow.
6. Inspect the real `/api/sprint` contract.
7. Produce a short implementation plan.

# Primary Goal

Optimize the common worklog flow for minimal keyboard usage.

Expected normal flow:

Active Sprint
→ click a sub-task
→ choose duration
→ click Log Work
→ Jira worklog created
→ Google Chat notification sent

The common case should require approximately 3 clicks after the sprint page has loaded.

# Critical Business Rule

**Worklogs are allowed ONLY on Jira sub-tasks.**

Parent issues must NEVER be loggable.

This rule must be enforced at both:

1. Vue interaction layer
2. Laravel backend

Do not rely solely on hiding/disabling parent issue controls.

A manually crafted request attempting to log work against a parent Story/Task/Work/Tech Solution must be rejected before Jira worklog creation.

Do NOT determine sub-task status by comparing issue type names such as:

`issueType === "Sub-task"`

Real project sub-task types include custom Jira types such as:

- `Sub-Imp`
- `Sub Automation`

Use Jira's actual hierarchy/sub-task metadata or parent relationship as the source of truth.

# Sub-task Selection

Only items returned by the backend as:

`loggable: true`

should expose the quick-worklog interaction.

Clicking/selecting a sub-task should reveal the duration picker associated with that sub-task.

Parent issue containers must remain non-actionable.

Only one sub-task should be actively selected for quick logging at a time.

Selecting another sub-task should move the quick-worklog UI to the newly selected item.

Do not open multiple duration pickers simultaneously.

# Interaction Design

Prefer an inline expandable quick-worklog area beneath the selected sub-task rather than a modal.

Conceptually:

BKM4-5259
[DEV] Update UT for BKM4-4547
In Progress

---

```
        1h 30m

   [ −15m ] [ +15m ]
```

[ 15m ] [ 30m ] [ 45m ]
[ 1h ] [ 1h30 ] [ 2h ]
[ 3h ] [ 4h ] [ 5h ]
[ 6h ] [ 7h ]

```
      [ Log 1h 30m ]
```

---

The exact styling must follow `DESIGN.md` and the existing Phase 2 visual language.

Keep it compact.

Do not introduce a modal/dialog library.

# Duration Domain

Represent duration internally in Vue as integer minutes.

Example:

`90`

not:

`"1h30m"`

Rules:

- minimum: 15 minutes
- maximum: 420 minutes / 7 hours
- step: 15 minutes

The duration picker must never produce values outside these bounds.

# Presets

Provide these quick presets:

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

Do NOT render every 15-minute combination as a separate button.

The user can reach intermediate values through ±15m.

Examples:

1h30
→ +15m
→ 1h45

2h
→ +15m
→ 2h15

# Increment / Decrement

Provide:

`−15m`

and:

`+15m`

Behavior:

- decrement by 15 minutes
- increment by 15 minutes
- cannot go below 15m
- cannot exceed 7h
- disable the relevant control at min/max

Do not silently wrap values.

# Initial Duration

Choose a sensible default consistent with `DESIGN.md`.

If the documentation does not explicitly specify one, default to:

`30 minutes`

when a sub-task is first selected.

If the user changes the duration and then selects another task, reset the new selection to the default rather than accidentally carrying the previous task's duration.

# Duration Formatting

Create/reuse a small presentation utility:

15 → `15m`
30 → `30m`
45 → `45m`
60 → `1h`
75 → `1h 15m`
90 → `1h 30m`
120 → `2h`
420 → `7h`

Keep internal state numeric.

At the API boundary, adapt the minutes into whatever duration format the existing `POST /api/worklogs` contract expects.

Do not change the existing API contract unnecessarily.

Do not duplicate the backend duration parser/business validation in Vue.

# Date and Started Time

Quick Worklog is optimized for logging work for the current day.

Reuse the current MVP1 behavior and existing API contract for date/start time.

For Phase 3:

- default date to today
- default started time appropriately using the application's configured timezone
- do not require keyboard input in the normal quick-log flow

If the existing backend already provides sensible defaults, reuse them rather than duplicating logic.

Do NOT remove the MVP1 manual form, because it remains the fallback for cases where the user needs a custom date/time.

# Submission

Quick Worklog must reuse:

`POST /api/worklogs`

Do NOT introduce:

- `/api/quick-worklogs`
- a second worklog handler
- duplicate Jira write logic

Conceptually:

Quick Worklog ─┐
├── POST /api/worklogs
Manual Worklog ┘
↓
existing application flow
↓
Jira
↓
Google Chat

# Backend Sub-task Guard

Strengthen the existing worklog application flow so it rejects parent issues.

Before creating a Jira worklog, verify that the target Jira issue is actually a sub-task according to Jira metadata/hierarchy.

Expected:

Sub-Imp
with Jira sub-task metadata
→ allowed

Sub Automation
with Jira sub-task metadata
→ allowed

Story
→ rejected

Task
→ rejected

Work
→ rejected

Tech Solution
→ rejected

Do not infer this from display names.

Return a safe validation/domain error for attempts to log against non-sub-task issues.

The guard must apply to BOTH:

- MVP2 Quick Worklog
- MVP1 Manual Worklog

This ensures the domain rule cannot be bypassed through the old manual form or direct API calls.

# Duplicate Submission Protection

While a worklog request is pending:

- disable Log Work
- disable or appropriately lock controls that could create ambiguous state
- prevent repeated submission

Do not allow double-clicking to create duplicate Jira worklogs.

# Success Behavior

On success:

- show concise confirmation
- retain enough information for the user to verify what was logged
- collapse the selected quick-worklog area after an appropriate short/explicit interaction if consistent with `DESIGN.md`
- do not automatically trigger another Jira request

Example:

`✓ Logged 1h 30m to BKM4-5259`

Google Chat notification behavior remains unchanged.

Do not add a second frontend Google Chat call.

# Error Behavior

Handle safe existing backend errors.

Examples:

- target is not a sub-task
- Jira permission failure
- Jira issue not found
- Jira unavailable
- invalid duration
- session expired

If API returns `401`:

→ navigate to `/login`

For normal errors:

- keep selected sub-task
- keep selected duration
- show concise error
- allow retry

Do not expose raw Jira responses, stack traces, credentials, or webhook information.

# Done Sub-tasks

Done sub-tasks remain loggable.

Do NOT disable them merely because their status is `Done`.

A user may complete a Jira sub-task before logging their time.

Eligibility comes from:

`loggable: true`

and backend Jira sub-task validation,

not status.

# Existing Manual Worklog

Preserve MVP1 manual worklog.

However, because the business rule is now explicitly:

**sub-task only**

the backend guard must also apply to manual ticket entry.

If a user manually enters a parent issue key, the backend must reject it safely.

Do not duplicate sub-task validation inside the manual Vue form.

# Accessibility / Mobile

Duration controls should have comfortable tap targets.

Ensure:

- selected preset is visually distinguishable
- disabled ± controls are clear
- keyboard focus remains visible
- buttons have accessible labels
- loading state is accessible

The UI should work well on mobile because quick logging should be practical without a keyboard.

# Tests — Backend

Use Jira HTTP fakes.

Add coverage for at least:

1. valid Jira sub-task can create worklog
2. custom `Sub-Imp` issue recognized through Jira metadata rather than its name
3. custom `Sub Automation` recognized through Jira metadata rather than its name
4. Story is rejected
5. Task/non-sub-task is rejected
6. Work parent issue is rejected
7. Tech Solution parent issue is rejected
8. rejected parent issue never reaches Jira worklog creation
9. rejected request never sends Google Chat notification
10. existing valid sub-task worklog still sends Google Chat after Jira success
11. manual endpoint receives the same sub-task enforcement
12. Jira lookup/validation failures are handled safely

Preserve all existing MVP1/MVP2 tests.

Automated tests must never call real Jira or Google Chat.

# Frontend Verification

If no frontend test framework exists, do not introduce a large one solely for this phase.

Verify at minimum:

- selecting a loggable sub-task opens picker
- selecting another sub-task moves picker
- parent issue cannot open picker
- default is 30m
- presets set correct minute values
- +15m works
- −15m works
- minimum is 15m
- maximum is 7h
- Log button reflects formatted selected duration
- duplicate submission is prevented
- successful submission shows confirmation
- error preserves selection
- 401 navigates to login
- Done sub-task remains selectable/loggable

# Scope

Do NOT implement yet:

- today's 7h progress
- today's worklog summary
- worklog history
- edit/delete worklogs
- Jira transitions
- Jira issue editing
- sprint management
- polling
- database
- Redis
- new authentication mechanisms
- Google Chat changes

Implement only MVP2 Phase 3.

# Verification

After implementation:

1. Run Pint.
2. Run Composer validation.
3. Run complete Laravel test suite.
4. Run Vite production build.
5. Verify Jira/Google Chat HTTP calls are faked in automated tests.
6. Verify parent issue worklog attempts cannot reach Jira.
7. Verify existing manual worklog still works for valid sub-tasks.
8. Update only genuinely completed Phase 3 tasks in `TASKS.md`.

Report:

- files created
- files modified
- duration picker component structure
- duration state/formatting approach
- quick-worklog interaction
- backend sub-task validation approach
- how custom Jira sub-task types are detected
- duplicate submission protection
- success/error behavior
- test count/assertions
- remaining Phase 3 concerns

Do not proceed to Phase 4.

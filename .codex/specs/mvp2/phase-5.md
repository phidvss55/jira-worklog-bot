Read `AGENTS.md`, `ARCHITECTURE.md`, `DESIGN.md`, and `TASKS.md`.

MVP2 Phases 1–4 are working.

Before finalizing MVP2, implement this focused UX/data enhancement.

Do not add unrelated features.

## 1. Sub-task Original Estimate

The active sprint UI currently shows:

- issue key
- summary
- status
- issue type
- loggable

Extend the Jira read-side so each returned sub-task also exposes its Jira original estimate normalized as minutes.

Prefer a normalized contract such as:

`estimateMinutes: number | null`

Example:

{
"key": "BKM4-5240",
"summary": "[DEV] Cross review (1) for BKM4-4398",
"status": "TO DO",
"issueType": "Sub-Imp",
"loggable": true,
"estimateMinutes": 120
}

Inspect the actual Jira fields/API currently being requested and use Jira's proper time-tracking/original-estimate field.

Do not expose raw Jira time-tracking structures to Vue.

If Jira has no estimate:

`estimateMinutes: null`

The sprint endpoint must continue working normally.

### Important business semantics

Estimate is informational.

Do NOT introduce:

`worklog duration <= estimate`

as a backend validation rule.

Real work may exceed the original estimate.

The existing rules remain:

- only Jira sub-tasks are loggable
- single quick worklog: 15m–7h

## 2. Estimate Presentation

Show the estimate compactly on each sub-task.

Examples:

15 → `15m`
60 → `1h`
90 → `1h 30m`
120 → `2h`
null → omit estimate or display a subtle `No estimate` only if DESIGN.md favors explicit missing state

Reuse the existing duration formatter.

Do not create another duration formatting implementation.

The estimate should be secondary metadata, not visually compete with the issue summary.

Conceptually:

BKM4-5240 To Do
[DEV] Cross review (1) for BKM4-4398

Estimate 2h

## 3. Estimate-aware Duration Picker

When a selected sub-task has an estimate matching one of the available presets, visually identify that preset as the estimated duration.

Example:

Estimate: 2h

[15m] [30m] [45m] [1h] [1h30] [2h ★]
[3h] [4h] [5h] [6h] [7h]

Do not automatically set the selected worklog duration to the estimate.

Keep the existing default duration behavior.

The estimate indicator should be subtle.

If selected duration exceeds the estimate, an optional small informational label such as:

`30m over estimate`

is acceptable if it fits the current design cleanly.

Do NOT block submission.

## 4. Redesign Quick Worklog Placement

The current implementation places the duration picker as a narrow right-side column inside the parent issue card.

This creates:

- excessive empty whitespace
- visual imbalance
- poor relationship between the selected sub-task and its controls
- an awkward narrow duration grid
- less natural mobile responsiveness

Remove the right-side duration-picker layout.

Use an **inline expandable section directly below the selected sub-task**.

Conceptual structure:

Parent issue
────────────────────────────────

Sub-task A
Estimate 2h

Sub-task B To Do
Summary...
Estimate 1h 30m

┌ Quick Worklog ────────────────────────┐

```
             30m

         [−15m] [+15m]

  [15m] [30m] [45m] [1h] [1h30] [2h]
  [3h]  [4h]  [5h]  [6h] [7h]

                          [ Log 30m ]
```

└───────────────────────────────────────┘

Sub-task C
...

The picker belongs visually to the selected sub-task.

Only one picker remains open at a time.

Selecting another sub-task moves the expanded picker beneath that sub-task.

## 5. Layout Principles

Prioritize:

- alignment
- consistent spacing
- visual rhythm
- compact vertical density
- obvious parent → sub-task → quick-log hierarchy

Avoid:

- split pane inside issue cards
- large unused white areas
- narrow vertical control columns
- excessive borders
- oversized cards
- dashboard-like styling

The UI should feel intentionally aligned and minimal.

## 6. Duration Control Layout

Now that the picker has horizontal space, improve the control layout.

Do not force every preset into a narrow 3-column grid.

Prefer a compact wrapping row/grid appropriate to available width.

Maintain:

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
- −15m
- +15m

The selected duration must remain visually obvious.

The Log button should remain the clear primary action.

## 7. Responsive Behavior

Desktop:

Use available horizontal space naturally.

Mobile:

Picker should remain inline below the selected task and wrap controls cleanly.

Do not introduce horizontal scrolling.

Do not maintain a separate mobile implementation.

## 8. Preserve Existing Behavior

Do not change:

- TOTP authentication
- sub-task-only backend enforcement
- Quick Worklog API
- Manual Worklog
- Jira worklog creation
- Google Chat notification
- today's 7h progress
- duration boundaries
- duplicate submission protection

Quick Worklog must still reuse:

POST /api/worklogs

## 9. Backend Tests

Add/update tests covering:

- original estimate returned as normalized minutes
- 2h estimate → 120
- fractional-hour estimate where applicable
- missing estimate → null
- parent/sub-task structure unchanged
- custom sub-task types remain supported
- estimate has no effect on loggable eligibility
- worklog exceeding estimate is not rejected solely because of estimate

Use Jira HTTP fakes.

No test may contact real Jira.

## 10. Frontend Verification

Verify:

- estimate displays correctly
- missing estimate behaves cleanly
- selected sub-task expands picker underneath itself
- no right-side picker remains
- only one picker opens
- selecting another task moves picker
- estimate preset is identifiable
- selected duration remains distinct from estimated duration
- > estimate remains allowed
- Done sub-task remains loggable
- desktop layout has no large artificial empty region
- mobile layout wraps correctly
- existing Quick Worklog success/error behavior remains intact

## 11. Documentation

Update `DESIGN.md` to reflect the new inline Quick Worklog layout.

Update `ARCHITECTURE.md` only if the sprint API contract documentation contains sub-task fields and needs `estimateMinutes`.

Update only genuinely completed tasks in `TASKS.md`.

## Verification

Run:

1. Pint
2. Composer validation
3. complete Laravel test suite
4. Vite production build

Report:

- Jira estimate field used
- API contract change
- backend files changed
- frontend files changed
- new inline layout
- estimate presentation
- estimate-aware preset behavior
- tests/assertions
- build result

Do not proceed to unrelated features.

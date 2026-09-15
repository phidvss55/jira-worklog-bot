# MVP2 Product Design

## Product Goal

Reduce daily Jira work logging to a few clicks with almost no keyboard usage.

## Primary Rule

Only Jira subtasks can receive worklogs from this product.

Parent tasks/stories/bugs are shown only to organize and explain their subtasks. They must not have a Log Work action.

## Main Screen

After TOTP login, the default screen shows:

- application title
- active sprint name/date range
- today's logged-time summary
- parent Jira issues as minimal groups/cards
- relevant assigned subtasks nested under each parent
- refresh action
- graceful loading/empty/error states

Do not recreate Jira board columns. The screen is optimized for work logging, not sprint management.

## Parent Issue Presentation

Parent issue displays:

- key
- summary
- small status indicator if useful

Parent issue is not clickable/selectable for work logging.

A parent should appear if it contains at least one relevant subtask for the current user, even if the parent itself is assigned to someone else.

## Subtask Presentation

Each subtask displays:

- key
- summary
- status
- optional today's logged time if available without excessive Jira requests
- original estimate when Jira provides one

Subtasks are the only selectable worklog targets.

Selecting a subtask opens/expands the quick-worklog controls inline where practical. Prefer inline expansion over a heavy modal.

The quick-worklog section sits directly beneath the selected subtask. If its original estimate matches a duration preset, mark that preset subtly. Estimates are informational and never block a longer worklog.

## Quick Duration Picker

Selected duration is represented in 15-minute increments.

Constraints:

- min: 15m
- max: 7h
- step: 15m

Controls:

- -15m
- current duration display
- +15m

Presets:

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

Do not display every possible 15-minute value as a preset. Use +/- for intermediate values such as 1h15m, 1h45m, 2h15m, etc.

The Log button should include the selected duration, e.g.:

- Log 1h 30m

Prevent double submission while a worklog is pending.

## Date and Start Time

For the normal quick-log path, default to today/current configured product time according to existing worklog behavior.

Avoid requiring keyboard input for date/time during normal daily use.

If MVP1 manual logging remains available, it can be used for exceptional historical/custom date/time worklogs.

## Today's Summary

Show a compact daily total near the top of the page:

- Today 5h 30m / 7h

7h is the standard-day target.

Do not block totals above 7h. Show a subtle warning/over-target state instead.

Refresh the summary after a successful worklog.

## Success Behavior

After successful Jira worklog creation:

- show concise success feedback
- keep user on the sprint screen
- update today's total
- optionally update the selected subtask's displayed logged-today value
- Google Chat notification continues as a secondary side effect

Do not force a full-page reload unless necessary.

## Error Behavior

Use concise safe messages for:

- Jira unavailable
- Jira permission/authentication issue
- issue no longer available
- issue is not a subtask
- invalid duration
- session expired

If session expires, route the user back to login.

## Empty States

No active sprint:

- "No active sprint found."

Active sprint but no relevant subtasks:

- "No subtasks assigned to you in the active sprint."

## Responsive Behavior

Desktop and mobile must both be usable.

On narrow screens:

- stack metadata naturally
- keep duration buttons large enough to tap
- avoid horizontal board layouts
- keep primary Log action obvious

## Keyboard-Minimization Principle

Normal flow target:

1. Open app
2. Tap subtask
3. Tap duration preset (or +/-)
4. Tap Log

No ticket key typing should be required for active-sprint work.

## Manual Log Fallback

Retain the existing manual worklog UI as a secondary/fallback path if current documentation/product direction still requires it.

It must not undermine the company rule: if the rule applies globally, manual logging must also reject non-subtask Jira issues server-side.

## Visual Direction

Minimal, functional, calm.

Prioritize:

- whitespace
- readable issue hierarchy
- clear selected state
- compact status labels
- obvious duration controls
- minimal decorative UI

Avoid:

- Jira clone styling
- kanban columns
- dashboards full of metrics
- complex navigation
- unnecessary animations

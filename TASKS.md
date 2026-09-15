# MVP2 Tasks — Active Sprint Quick Worklog

## Status Legend

- [ ] Not started
- [-] In progress
- [x] Completed

## MVP2 Business Rule

All worklogs created by this application must target Jira subtasks only.

Parent issues are display/grouping context and must never be valid worklog targets.

---

# Phase 1 — Jira Active Sprint Read API

Goal: return a normalized view of the current user's relevant subtasks in the active sprint.

- [x] Inspect Jira board/sprint APIs supported by the current Jira Cloud integration.
- [x] Add runtime configuration for board/project identification where required.
- [x] Resolve current Jira user/account through the existing authenticated Jira client.
- [x] Fetch the relevant active sprint.
- [x] Fetch sprint issues required to discover the current user's subtasks.
- [x] Include a parent issue when it contains at least one relevant subtask assigned to the current user.
- [x] Return only relevant/selectable subtasks to the quick-log UI.
- [x] Normalize Jira responses into application DTOs; do not expose raw Jira payloads.
- [x] Implement protected `GET /api/sprint` (or the endpoint defined by the final implementation).
- [x] Handle no-active-sprint state.
- [x] Handle active sprint with no relevant subtasks.
- [x] Handle Jira 401/403/404/429/5xx and connection failures safely.
- [x] Add HTTP-fake/unit/feature tests.
- [x] Confirm unauthenticated calls cannot reach Jira.

Acceptance:

- Authenticated request returns active sprint + grouped parent issues + current user's relevant subtasks.
- Parent issues are context only.

---

# Phase 2 — Enforce Subtask-Only Worklogs

Goal: make the company rule a backend invariant, not merely a UI restriction.

- [x] Identify the cleanest boundary for verifying that a Jira issue is a subtask before creating a worklog.
- [x] Reuse Jira issue metadata/read capabilities where possible.
- [x] Reject attempts to log work against Story/Task/Bug/Epic/other non-subtask issue types.
- [x] Ensure quick-log requests cannot trust a frontend-provided issue type.
- [x] Decide/document whether the existing manual worklog path is also subject to the same rule; default to enforcing the company rule globally.
- [x] Ensure rejected non-subtask requests do not create Jira worklogs.
- [x] Ensure rejected non-subtask requests do not send Google Chat notifications.
- [x] Add tests for valid subtask and invalid parent/non-subtask targets.

Acceptance:

- Direct API bypass cannot create a worklog on a parent issue.

---

# Phase 3 — Active Sprint UI

Goal: render a minimal Jira-like hierarchy optimized for work logging.

- [x] Add sprint header with active sprint name/date range.
- [x] Add loading state/skeleton.
- [x] Add no-active-sprint state.
- [x] Add no-subtasks state.
- [x] Add safe Jira error state.
- [x] Render parent issue groups/cards.
- [x] Render nested relevant subtasks.
- [x] Show compact status information.
- [x] Ensure parent issues have no Log Work action.
- [x] Make only subtasks selectable.
- [x] Add manual refresh action.
- [x] Ensure responsive desktop/mobile layout.
- [x] Preserve TOTP session-expiry behavior.

Acceptance:

- User can visually find their sprint subtasks without typing ticket keys.

---

# Phase 4 — Quick Duration Picker

Goal: log time with minimal keyboard usage.

Rules:

- min 15m
- max 7h
- step 15m

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

Tasks:

- [x] Implement reusable duration picker.
- [x] Store selection internally as integer minutes.
- [x] Implement `-15m` control.
- [x] Implement `+15m` control.
- [x] Enforce 15m minimum.
- [x] Enforce 7h maximum.
- [x] Implement preset buttons.
- [x] Convert selected minutes into the existing worklog duration contract.
- [x] Expand/show picker only for a selected subtask.
- [x] Display selected subtask context clearly.
- [x] Submit through the existing worklog application flow.
- [x] Prevent duplicate submissions while pending.
- [x] Show concise success feedback.
- [x] Show safe Jira/worklog errors.
- [x] Keep Google Chat notification behavior unchanged.

Acceptance:

- Typical log flow requires selecting a subtask, selecting a preset, and pressing Log.

---

# Phase 5 — Today's Worklog Summary

Goal: show progress against a standard 7-hour workday.

- [x] Add Jira read support for current user's worklogs for the current product-local day.
- [x] Calculate total logged minutes.
- [x] Return normalized `totalMinutes`/equivalent data.
- [x] Display `Today X / 7h` summary.
- [x] Add compact progress visualization if consistent with DESIGN.md.
- [x] Refresh total after successful quick worklog.
- [x] Allow total above 7h.
- [x] Display over-7h state as informational/warning only.
- [x] Avoid excessive Jira API requests.
- [x] Add tests around date/timezone boundaries.

Acceptance:

- Today's total updates after a successful worklog.

---

# Phase 6 — MVP2 Integration & Polish

- [ ] Verify existing manual worklog fallback remains usable if retained.
- [x] Verify all write paths enforce subtask-only rule.
- [x] Verify Jira success triggers Google Chat notification.
- [x] Verify Google Chat failure does not turn a successful Jira worklog into a failure.
- [x] Verify unauthenticated calls never reach Jira/Google Chat.
- [x] Verify TOTP login/session expiry/logout.
- [ ] Verify mobile UX.
- [x] Run Pint.
- [x] Run Composer validation.
- [x] Run complete Laravel tests.
- [x] Run Vite production build.
- [ ] Build Docker image.
- [ ] Run local production smoke test.
- [ ] Deploy to Render.
- [ ] Production E2E: active sprint load.
- [ ] Production E2E: subtask quick worklog.
- [ ] Production E2E: Google Chat notification.
- [ ] Production E2E: today's total refresh.
- [x] Update architecture/design documentation for final implementation differences.
- [ ] Tag MVP2 release when complete.

---

# Explicit Non-Goals

Do not implement in MVP2:

- worklogs on parent/non-subtask issues
- create/edit Jira issues
- transition Jira status
- Jira comments
- sprint/board administration
- drag-and-drop board UI
- multi-user support
- database/Redis unless a new requirement explicitly demands it
- edit/delete worklogs
- offline mode

# MVP2 Definition of Done

TOTP login
-> active sprint loads
-> parent issues group current user's relevant subtasks
-> only subtask is selectable
-> choose duration with presets or +/-15m
-> log work
-> backend verifies target is a Jira subtask
-> Jira worklog created
-> Google Chat notification sent as secondary side effect
-> today's total refreshes

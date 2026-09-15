Read `AGENTS.md`, `ARCHITECTURE.md`, `DESIGN.md`, and `TASKS.md` completely.

MVP2 Phases 1–3 are complete:

* Active sprint read API works against real Jira.
* Vue renders parent issues with nested loggable sub-tasks.
* Quick Worklog supports presets and ±15-minute adjustments.
* Worklogs are allowed ONLY on Jira sub-tasks.
* Both Quick Worklog and MVP1 Manual Worklog reuse `POST /api/worklogs`.
* Backend enforces the sub-task-only rule.
* Successful Jira worklogs continue to send Google Chat notifications.

Implement **MVP2 Phase 4 — Today's Worklog Summary / Daily Progress only**.

Do not proceed to general worklog history or other features.

Before modifying code:

1. Read all current project documentation.
2. Inspect the Jira client/read-side implementation.
3. Inspect the authenticated-current-user Jira logic from Phase 1.
4. Inspect the current Quick Worklog flow.
5. Inspect the existing Vue sprint header/layout.
6. Inspect how dates/timezones are currently normalized.
7. Produce a short implementation plan.

# Product Goal

Show how much work the authenticated Jira user has logged today relative to the standard 7-hour workday.

Conceptually:

Today
5h 30m / 7h
██████████████░░░░░░

This should help the user answer immediately:

"How much time have I logged today?"

The summary is informational.

**7 hours is a target, NOT a business limit.**

Never prevent worklogs merely because today's total reaches or exceeds 7 hours.

# Source of Truth

Jira must remain the source of truth.

Do NOT calculate today's total solely from Vue state.

Do NOT persist a separate worklog total in Laravel.

Do NOT add a database.

The application should retrieve the current user's relevant worklogs from Jira and calculate the daily total server-side.

This ensures the total remains correct if worklogs were created through:

* Quick Worklog
* Manual Worklog
* Jira UI
* another valid Jira client

# Date / Timezone Rule

"Today" must use the application's configured timezone:

`Asia/Ho_Chi_Minh`

or the existing project timezone configuration if it has been abstracted.

Do not use UTC date boundaries blindly.

The daily window conceptually is:

00:00:00
through
23:59:59

in the configured application timezone.

Be careful when interacting with Jira timestamps, which may include timezone offsets.

Do not duplicate timezone rules in Vue.

Laravel should return the date/summary already normalized for presentation.

# Backend API

Introduce a focused authenticated read endpoint following current conventions.

Prefer:

`GET /api/worklogs/today`

unless the existing architecture/docs specify a different contract.

Conceptual response:

{
"date": "2026-09-15",
"totalMinutes": 330,
"targetMinutes": 420
}

Optionally include presentation-friendly derived information only if it clearly reduces frontend duplication, but keep the contract small.

At minimum:

* date
* totalMinutes
* targetMinutes

The frontend can format `totalMinutes`.

Do not return raw Jira worklog payloads.

# Jira Query Strategy

Inspect Jira's available APIs and the existing Jira client before choosing the implementation.

The backend needs the worklogs belonging to the authenticated/current Jira user for the current local day.

Avoid an obviously inefficient N+1 strategy if Jira provides a better way.

However, do not introduce unnecessary caching/database infrastructure solely for this MVP.

Document the Jira API/query strategy chosen.

Correctness is more important than premature optimization.

# Ownership

Only count worklogs belonging to the same Jira user represented by this personal application.

Do not count other users' worklogs on the same issues.

The current Jira credential/current-user identity should remain the source of truth.

# Scope of Daily Total

The daily total should represent the user's Jira worklogs for today, not merely worklogs from currently displayed active-sprint sub-tasks.

For example:

Active Sprint:
BKM4-5259 → 2h

Another valid Jira sub-task:
BKM4-5000 → 1h

Manual Jira worklog:
BKM4-4900 → 30m

Today's summary:

3h 30m

Do not artificially limit the total to the current `/api/sprint` response unless Jira API limitations make this impossible and the documentation explicitly approves that trade-off.

# 7-Hour Target

Use:

`targetMinutes = 420`

The target should be represented as an application/product configuration

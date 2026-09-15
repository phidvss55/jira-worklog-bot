# AGENTS.md — MVP2 Addendum

Merge these rules into the repository's existing AGENTS.md where they are not already covered.

## MVP2 Primary Business Rule
The application may create Jira worklogs only on Jira subtasks.

This is a backend invariant, not only a frontend UX rule.

Never implement a worklog action on a parent Story, Task, Bug, Epic, or other non-subtask issue.

Never trust frontend-provided issue type metadata to authorize a worklog. Validate through trusted Jira/application data before the write when necessary.

## MVP2 Goal
Optimize daily work logging for minimum keyboard usage:
- load current user's subtasks from the active sprint
- group them under parent issues for context
- select a subtask
- choose time using presets or +/-15-minute controls
- reuse the existing worklog use case

## Scope Discipline
Implement MVP2 in phases from TASKS.md.

Do not implement all phases in one task unless explicitly requested.

Parent issues are read/display context only.

## Duration Picker Rules
Quick worklog duration:
- minimum 15 minutes
- maximum 7 hours
- step 15 minutes

Frontend may keep duration as integer minutes, but the backend remains the source of truth for worklog validation.

## Jira Read Integration
Keep Jira-specific REST payloads inside the Jira integration layer.

Application/UI layers should consume normalized DTOs rather than raw Jira responses.

Avoid excessive Jira requests and N+1 issue lookups where a Jira search/expand strategy can retrieve the required metadata efficiently.

## Existing Security
All new sprint/worklog APIs must use the existing TOTP session authentication and CSRF model.

Do not weaken authentication to simplify MVP2.

## Existing Side Effects
Google Chat notification remains secondary to Jira worklog creation.

A Google Chat failure must not cause the UI to imply that a successful Jira worklog failed.

## Testing
Automated tests must not call real Jira or Google Chat.

Add tests proving that non-subtask worklog attempts are rejected even when the frontend is bypassed.

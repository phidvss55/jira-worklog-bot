Read `AGENTS.md`, `ARCHITECTURE.md`, `DESIGN.md`, and `TASKS.md` completely before making any changes.

The project architecture has recently changed. Treat the current documentation as the source of truth and ignore the previous Google Chat slash-command architecture.

We are now implementing the **Vue + Vite worklog UI phase only**.

Before modifying code:

1. Inspect the existing Laravel 12 application.
2. Inspect the current `POST /api/worklogs` API contract and validation.
3. Inspect the updated architecture/design/task documentation.
4. Preserve all working Jira integration and existing tests.
5. Produce a short implementation plan before coding.

## Goal

Build a simple single-page UI served by the existing Laravel application.

The normal user flow should be:

Browser
→ Vue worklog form
→ POST `/api/worklogs`
→ existing Laravel application flow
→ Jira
→ show result in the UI

Do not create a separate frontend application or repository.

Use the existing Laravel Vite setup with Vue 3.

## Frontend Stack

Use only what is necessary:

- Vue 3
- Vite
- native `fetch` or the minimal existing HTTP mechanism
- normal CSS

Do not introduce unless already required by current documentation:

- Vue Router
- Pinia
- Axios
- Tailwind
- Bootstrap
- Vuetify
- other UI frameworks
- frontend state-management libraries

Keep the frontend intentionally small.

## Page

Serve the application from:

`GET /`

Laravel should render the Blade/Vite entry point that mounts the Vue application.

The page should contain a centered worklog form.

Required fields:

- Jira ticket
- Duration
- Date
- Start time

Primary action:

`Log Work`

## Default Values

When the UI opens:

- Date defaults to today.
- Start time defaults to the current local time.
- Use the product timezone defined in the current documentation/configuration.
- Do not rely blindly on the Render/server timezone.

Ticket and duration start empty.

## Ticket Field

Example:

`BKM4-1234`

Use a text input.

Allow normal typing and normalize consistently with the existing backend behavior.

Do not duplicate Jira existence validation in the frontend.

## Duration Field

Use a text input.

Example:

`2h15m`

Display concise helper text such as:

`Examples: 30m, 1h, 1h30m, 2h15m`

Do not duplicate the complete backend duration parser in Vue.

Backend remains the source of truth.

## Date

Prefer a native date input for usability.

The backend currently has an established date contract. Inspect it carefully.

If the browser input uses `YYYY-MM-DD` while the existing API expects another format, perform a small explicit conversion at the frontend/API boundary rather than changing core date behavior unnecessarily.

## Time

Use a native time input.

Expected conceptual value:

`14:30`

## Submission

On submit:

1. Disable the submit button.
2. Show a clear loading state.
3. POST to the existing `/api/worklogs`.
4. Use the existing backend API contract.
5. Prevent accidental duplicate submission while a request is pending.
6. Re-enable the form after completion.

Do not bypass the existing Laravel worklog application flow.

## Success State

After a successful Jira worklog:

Show a concise success message in the page.

Example:

`✓ Worklog added`

and useful details such as:

`BKM4-1234 · 2h 15m · 05/09/2026 14:30`

Do not automatically clear all fields immediately.

Keep the result visible so the user can verify what was logged.

A reasonable behavior is:

- keep date
- keep time or update it only if the documented design specifies this
- clear ticket/duration only if the current `DESIGN.md` explicitly requires it

Follow the updated `DESIGN.md` as source of truth.

## Error State

Display useful backend errors.

Examples:

- invalid ticket
- invalid duration
- invalid date/time
- Jira ticket not found
- Jira authentication/permission failure
- Jira unavailable

Do not expose:

- stack traces
- Jira credentials
- authorization headers
- raw internal exception details

Prefer backend-provided safe messages instead of reimplementing error mapping in Vue.

## Responsive Design

The page should work well on both desktop and mobile.

This is particularly important because the tool may be opened quickly during the workday.

Keep the form compact and focused.

Avoid unnecessary navigation, sidebars, dashboards, tables, or decorative sections.

## Accessibility

Use:

- actual `<label>` elements
- appropriate input types
- visible focus states
- disabled/loading button state
- accessible success/error messaging

## Architecture

Keep frontend responsibilities limited to:

UI state
→ collect input
→ submit API request
→ render response

Business rules remain in Laravel.

Do not move duration/date/Jira logic into Vue.

## Testing

Preserve the complete existing PHP test suite.

Add frontend tests only if a frontend testing framework already exists or the current documentation explicitly requires one.

Do not introduce a large frontend testing stack solely for this phase unless necessary.

At minimum:

- run the production Vite build
- run Laravel tests
- run Pint
- run Composer validation

## Cleanup

The previous Google Chat slash-command implementation is obsolete under the new architecture.

Inspect whether these old Phase 3 files still exist:

- `GoogleChatCommandParser`
- `ParsedGoogleChatCommand`
- `InvalidGoogleChatCommandException`
- `GoogleChatResponseBuilder`

If the updated documentation marks them obsolete and they have no remaining references, remove them and their tests.

Do not remove anything still required by the current architecture.

## Scope

Do NOT implement yet:

- Google Chat incoming webhook notification
- Google Chat slash commands
- Google Cloud project integration
- authentication/login
- database
- Docker
- Render deployment
- additional worklog features
- worklog history
- edit/delete worklog

Implement only the Vue UI phase defined by the current `TASKS.md`.

## Verification

After implementation:

1. Run Pint.
2. Run Composer validation.
3. Run the complete Laravel test suite.
4. Run the Vite production build.
5. Verify no Phase 1/2 behavior regressed.
6. Verify no secrets are present in frontend bundles.
7. Update only genuinely completed tasks in `TASKS.md`.

Report:

- files created
- files modified
- packages added
- UI structure
- API integration behavior
- obsolete files removed
- build result
- test count/assertions
- remaining tasks

Do not proceed to the next phase.

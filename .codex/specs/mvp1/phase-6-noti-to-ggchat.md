Read `AGENTS.md`, `ARCHITECTURE.md`, `DESIGN.md`, and `TASKS.md` completely before making changes.

The application is now successfully deployed to Render.

We are implementing the **Google Chat Incoming Webhook notification phase only**.

The architecture is:

Vue UI
→ Laravel
→ existing LogWorkHandler
→ Jira Cloud
→ on Jira success, send Google Chat notification through an Incoming Webhook

Google Chat is an OUTPUT notification adapter only.

Do not reintroduce the previous Google Chat slash-command architecture.

Before coding:

1. Inspect the existing `LogWorkHandler`.
2. Inspect the Jira client abstraction and success/failure flow.
3. Inspect the existing worklog result/response objects.
4. Inspect `config/services.php`.
5. Inspect the updated architecture documentation.
6. Produce a short implementation plan before making changes.

## Configuration

Use:

`GOOGLE_CHAT_WEBHOOK_URL`

Expose it through Laravel configuration, for example through:

`config('services.google_chat.webhook_url')`

Never call `env()` directly from application/service classes.

Update `.env.example` with:

`GOOGLE_CHAT_WEBHOOK_URL=`

Do not add a real webhook URL.

The webhook URL is a secret because it contains credentials.

It must never be:

- committed
- logged
- included in exceptions returned to clients
- exposed to Vue/Vite
- included in frontend bundles
- printed by tests
- included in test snapshots

## Design

Introduce a small notification abstraction so the application layer does not depend directly on Laravel HTTP or Google Chat implementation details.

Prefer something conceptually similar to:

WorklogNotifier
↑
GoogleChatWorklogNotifier

The exact naming/location should follow the current architecture and project conventions.

Avoid unnecessary generic notification frameworks.

We currently have one notification requirement: notify Google Chat after a successful Jira worklog.

## Flow

The required behavior is:

LogWorkHandler
↓
JiraClient creates worklog
↓
Jira SUCCESS
↓
WorklogNotifier
↓
GoogleChatWorklogNotifier
↓
Google Chat Incoming Webhook

Do NOT send a Google Chat notification if Jira worklog creation fails.

## Notification Failure Semantics

This is important.

Jira worklog creation is the primary business operation.

Google Chat notification is a secondary side effect.

Therefore:

Jira SUCCESS + Google Chat SUCCESS
→ worklog operation is successful

Jira SUCCESS + Google Chat FAILURE
→ worklog operation is STILL successful

Jira FAILURE
→ worklog operation fails
→ Google Chat must not be called

Never make the user retry a successful Jira worklog merely because Google Chat notification failed, because retrying could create a duplicate Jira worklog.

If the current API response can safely communicate notification status without breaking the existing contract, include something conceptually equivalent to:

`notificationSent: true|false`

Do not make a large response-contract refactor solely for this field.

## Google Chat HTTP Request

Use Laravel HTTP Client.

Send an HTTP POST to the configured Incoming Webhook URL.

Use a simple Google Chat text message payload.

Conceptual payload:

```json
{
    "text": "✅ Jira Worklog Added\n\n🎫 BKM4-1234\n⏱ 2h 15m\n🕐 06/09/2026 15:30"
}
```

Use actual normalized worklog values from the existing application flow/result.

Do not reconstruct business values from raw HTTP request data if normalized values are already available.

Use the application's configured timezone when formatting the displayed started time.

## Message Format

Keep the MVP notification concise:

```text
✅ Jira Worklog Added

🎫 BKM4-1234
⏱ 2h 15m
🕐 06/09/2026 15:30
```

Reuse existing duration/date formatting logic where appropriate.

Do not introduce Google Chat Cards yet.

## HTTP Safety

Use a reasonable timeout.

Handle safely:

- connection failure
- timeout
- Google Chat 4xx
- Google Chat 429
- Google Chat 5xx

Do not leak the webhook URL in logs or API responses.

Be especially careful because Laravel HTTP exceptions may contain request URLs.

If notification fails, log only safe contextual information such as:

- notification provider
- issue key
- safe error category/status code

Never log the complete webhook URL.

## Missing Configuration

Handle a missing `GOOGLE_CHAT_WEBHOOK_URL` safely.

Local/test environments should not accidentally call Google Chat.

Choose behavior consistent with the architecture:

- Jira worklog remains successful.
- Notification reports/skips safely.
- No exception should cause a successful Jira worklog to appear failed.

## Dependency Injection

Bind the notification abstraction through Laravel's service container following existing project conventions.

`LogWorkHandler` should not instantiate the Google Chat notifier directly.

Do not use static/global helpers for application notification logic.

## Tests

The automated test suite must NEVER call the real Google Chat webhook.

Use Laravel HTTP fakes/mocks.

Add tests covering at least:

1. Jira success → Google Chat webhook called once.
2. Notification contains the correct Jira issue key.
3. Notification contains the normalized duration.
4. Notification contains the normalized started date/time.
5. Jira failure → Google Chat is NOT called.
6. Google Chat 2xx → notification success.
7. Google Chat 400/403 → Jira worklog remains successful.
8. Google Chat 429 → Jira worklog remains successful.
9. Google Chat 5xx → Jira worklog remains successful.
10. Google Chat connection/timeout failure → Jira worklog remains successful.
11. Missing webhook configuration → Jira worklog remains successful and no external call occurs.
12. Real webhook URL is never required by tests.

Preserve all existing Jira tests.

## Manual Testing Support

Provide a safe way to manually test the Google Chat webhook WITHOUT creating a Jira worklog.

Prefer an Artisan command such as:

`php artisan google-chat:test`

The command should:

- use the same notifier/configuration as production
- send a clearly identifiable test message
- perform no Jira operation
- report success/failure
- never print the webhook URL
- never print its key/token

Example message:

`🧪 Jira Worklog Bot — Google Chat webhook test successful.`

This allows us to verify:

Laravel/Render
→ Google Chat webhook
→ Google Chat group

independently before testing Jira.

## Scope

Do NOT implement:

- Google Chat slash commands
- Google Cloud Chat app
- OAuth
- Google Chat REST API authentication
- cards
- threads
- database
- Redis
- queues
- authentication/login changes
- Docker architecture changes
- Render architecture changes
- worklog history
- edit/delete worklogs

Do not modify the working Jira integration except for the minimal orchestration needed to trigger notification after success.

## Verification

After implementation:

1. Run Pint.
2. Run Composer validation.
3. Run the complete test suite.
4. Run the Vite production build.
5. Confirm HTTP tests use fakes.
6. Confirm no test contacts Google Chat.
7. Search tracked source files for accidental webhook credentials.
8. Confirm the webhook URL cannot enter the frontend build.
9. Update only genuinely completed Google Chat webhook tasks in `TASKS.md`.

Report:

- files created
- files modified
- notifier abstraction
- Google Chat implementation
- DI registration
- failure semantics
- API response changes, if any
- Artisan test command
- tests/assertions
- exact local manual-test command
- exact Render configuration required

Do not proceed to another phase.

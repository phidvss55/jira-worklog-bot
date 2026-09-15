Read `AGENTS.md`, `ARCHITECTURE.md`, `DESIGN.md`, and `TASKS.md` completely before making changes.

Phase 1 is complete and all tests currently pass.

We are now implementing **Phase 2 — Jira Cloud Integration only**.

Before modifying code, inspect the existing Phase 1 implementation, especially the current Jira abstraction/client and `LogWorkHandler`. Preserve the existing architecture and do not rewrite working Phase 1 behavior unnecessarily.

Implement the real Jira Cloud integration.

Requirements:

- Use Laravel's HTTP Client.
- Read Jira configuration through Laravel config, never call `env()` directly from application/service classes.
- Expected environment variables:
    - `JIRA_BASE_URL`
    - `JIRA_EMAIL`
    - `JIRA_API_TOKEN`

- Update `.env.example` with empty values only.
- Never add, print, log, or commit real credentials.

Implement Jira worklog creation using the Jira Cloud REST API:

`POST /rest/api/3/issue/{issueKey}/worklog`

The request must include the normalized values already produced by the Phase 1 application flow:

- issue key
- `timeSpentSeconds`
- `started`

Preserve the existing `LogWorkHandler` boundary. The handler should depend on the Jira abstraction rather than Laravel HTTP implementation details.

Handle Jira failures safely, including:

- 400 validation failure
- 401 authentication failure
- 403 permission failure
- 404 issue not found/inaccessible
- 429 rate limiting
- network/connection failure
- timeout
- unexpected 5xx responses

Do not expose credentials, authorization headers, or unnecessary Jira response details to API consumers.

Add tests using Laravel HTTP fakes/mocks.

The normal automated test suite must NEVER call real Jira.

Add test coverage for at least:

- successful Jira worklog creation
- correct issue key in the URL
- correct `timeSpentSeconds`
- correct `started`
- authentication configuration
- Jira 400
- Jira 401
- Jira 403
- Jira 404
- Jira 429
- Jira 5xx
- connection/timeout failure

Also provide a safe way to manually verify Jira connectivity before creating a worklog, preferably through an Artisan command that calls a read-only Jira endpoint such as `/rest/api/3/myself`.

The connectivity command must:

- use the same Jira client/configuration as the application
- perform no write operation
- clearly report success or failure
- never print the API token

Do not implement:

- Google Chat
- OAuth
- database
- Redis
- queues
- Docker/Render changes
- multi-user support

After implementation:

1. Run Pint.
2. Run Composer validation.
3. Run the complete test suite.
4. Confirm no real credentials are tracked.
5. Update only completed Phase 2 items in `TASKS.md`.
6. Report changed files, test results, and the exact command I can use to verify Jira connectivity.

Do not perform a real Jira worklog automatically and do not proceed to Phase 3.

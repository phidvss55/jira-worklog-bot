Read `AGENTS.md`, `ARCHITECTURE.md`, `DESIGN.md`, and `TASKS.md` completely before making changes.

The application is already working end-to-end:

Browser
→ Vue/Vite UI
→ Laravel
→ Jira Cloud
→ Google Chat Incoming Webhook

It is deployed publicly on Render.

We now need the final required MVP security phase:

**single-user TOTP authentication without a database.**

Do not implement password authentication.

Before coding:

1. Inspect the current Laravel 12 application.
2. Inspect `routes/web.php` and `routes/api.php`.
3. Inspect the current Vue app and `/api/worklogs` request flow.
4. Inspect session and CSRF configuration.
5. Inspect Docker/Render configuration.
6. Inspect existing public routes.
7. Produce a short implementation plan before modifying code.

## Goal

Protect the application from unauthorized users who discover the public Render URL.

Authentication flow:

GET /
→ unauthenticated
→ /login

User enters 6-digit TOTP
→ Laravel verifies TOTP
→ Laravel regenerates session
→ worklog_authenticated=true
→ redirect to /

Authenticated user
→ Vue UI
→ POST /api/worklogs
→ authentication middleware
→ existing Jira flow

Logout
→ invalidate session
→ regenerate CSRF token
→ /login

## Authentication Model

Use TOTP only.

Do NOT implement:

- password login
- database
- users table
- migrations
- Breeze
- Jetstream
- Fortify
- Sanctum
- Passport
- OAuth
- registration
- forgot-password
- email verification

Use a single TOTP secret stored server-side.

Expected environment variable:

`WORKLOG_TOTP_SECRET`

Expose it through Laravel config.

Never call `env()` directly from controllers, middleware, services, or application classes.

Update `.env.example` with:

`WORKLOG_TOTP_SECRET=`

Never commit a real secret.

## TOTP Library

Use a maintained RFC 6238-compatible PHP library.

Prefer:

`spomky-labs/otphp`

if compatible with the current PHP/Laravel constraints.

Do not implement TOTP cryptography manually.

The application should use:

- 6-digit codes
- 30-second period
- SHA-1 unless the library/default configuration requires otherwise for Google Authenticator compatibility

Allow a small clock drift window, approximately:

- previous period
- current period
- next period

Do not allow an unnecessarily large validation window.

## Secret Handling

`WORKLOG_TOTP_SECRET` is highly sensitive.

It must never appear in:

- source control
- Vue/Vite code
- frontend bundles
- logs
- exceptions
- HTTP responses
- test output
- Dockerfile
- screenshots
- Google Chat notifications

Treat it similarly to the Jira API token.

If the secret is missing or invalid in production:

**fail closed.**

The application must not allow authentication.

## Session Authentication

After successful TOTP verification:

- regenerate the Laravel session ID
- store only a minimal flag such as:

`worklog_authenticated = true`

Do not store:

- TOTP code
- TOTP secret

For logout:

- invalidate the session
- regenerate the CSRF token

## Middleware

Create focused middleware such as:

`EnsureWorklogAuthenticated`

or equivalent.

Protect all sensitive routes.

At minimum:

PROTECTED:

- GET `/`
- POST `/api/worklogs`
- POST `/logout`
- any other Jira-backed write route

PUBLIC:

- GET `/health`
- GET `/login`
- POST `/login`

For browser navigation:

- redirect unauthenticated users to `/login`

For API requests:

- return HTTP 401
- do not redirect to an HTML login page

Most importantly:

unauthenticated POST `/api/worklogs`
→ must NOT reach Jira
→ must NOT reach Google Chat

## Login UI

Add a minimal login page.

Keep it simple and consistent with the existing app UI.

Fields:

- 6-digit authenticator code
- Sign In button

Example:

Jira Worklog

Authenticator Code
[ 123456 ]

[ Sign In ]

Use:

- numeric input where appropriate
- `inputmode="numeric"`
- autocomplete suitable for one-time codes
- max length 6

Do not expose configuration details.

Invalid code response:

`Invalid authentication code.`

Do not reveal whether the secret exists or configuration is valid.

## Rate Limiting

Protect `POST /login`.

Use Laravel's existing rate limiter.

A reasonable policy:

approximately 5 failed attempts per minute per IP.

Do not introduce Redis.

Ensure throttled requests return a safe response.

## CSRF

Authentication is session-based.

State-changing browser requests must remain CSRF protected.

Do NOT globally disable CSRF.

Review the existing `/api/worklogs` route carefully.

If it currently lives under the stateless API middleware group and therefore bypasses normal CSRF/session behavior, adjust the route architecture cleanly so browser worklog requests use Laravel session authentication + CSRF.

Do not use a permanent API secret in Vue.

Ensure Vue sends requests in a way compatible with Laravel session cookies and CSRF.

## Session Cookie Security

Ensure Render production uses secure session behavior.

Expected characteristics:

- HttpOnly
- Secure over HTTPS
- SameSite=Lax unless the current architecture requires otherwise

Do not hardcode settings that unnecessarily break local development.

## Session Driver

Remain database-free.

Use a simple session driver appropriate for the single-instance MVP.

File sessions are acceptable if compatible with the current Docker/Render runtime.

Document the limitation:

- redeploy/restart can log the user out
- horizontal scaling would require shared session storage later

Do not introduce Redis or database.

## TOTP Setup Command

Provide an Artisan command such as:

`php artisan worklog:totp-setup`

The command should:

1. generate a secure TOTP secret
2. generate an `otpauth://` provisioning URI compatible with Google Authenticator
3. clearly show the account/application label, for example:
   `Jira Worklog Bot`
4. optionally render a terminal QR code only if this can be done with a small, appropriate dependency

If adding QR support introduces unnecessary complexity, printing the provisioning URI and secret is acceptable.

The command must:

- never save the secret automatically
- never commit anything
- make it clear that the user must copy the generated secret into Render
- never send it externally

Example Render variable:

`WORKLOG_TOTP_SECRET=<generated-secret>`

## TOTP Replay Trade-off

Do not add a database/cache just to record used TOTP time steps.

Document that same-window replay prevention is not persisted in this MVP.

Mitigations already present:

- HTTPS
- short 30-second validity
- small drift window
- login rate limiting
- session after successful login
- single-user use case

Do not overengineer this.

## Vue Behavior

If an authenticated browser session expires and:

`POST /api/worklogs`

returns `401`:

- handle it gracefully
- redirect/navigate the user to `/login`

Do not display raw internal authentication errors.

## Existing Google Chat Behavior

Do not modify Google Chat notification behavior.

Expected flow:

unauthenticated request
→ rejected
→ Jira NOT called
→ Google Chat NOT called

authenticated request
→ Jira success
→ Google Chat notification as currently implemented

## Tests

Add focused tests covering at least:

1. `/health` remains public
2. `/login` is public
3. unauthenticated `/` redirects to `/login`
4. valid TOTP authenticates
5. invalid TOTP fails
6. malformed/non-6-digit code fails
7. login regenerates/creates authenticated session correctly
8. login route is rate limited
9. authenticated user can access `/`
10. unauthenticated POST `/api/worklogs` returns 401
11. unauthenticated worklog request does NOT call Jira
12. unauthenticated worklog request does NOT call Google Chat
13. authenticated POST `/api/worklogs` preserves current behavior
14. logout invalidates authentication
15. after logout protected routes are inaccessible
16. missing TOTP secret fails closed
17. invalid TOTP secret fails closed
18. TOTP validation accepts intended small time drift
19. tests never require a real secret
20. CSRF is not globally disabled

Use deterministic time control in tests so TOTP tests are reliable.

Automated tests must never call real Jira or Google Chat.

## Dependency Safety

If adding `spomky-labs/otphp`:

- add it through Composer
- do not pin an unnecessarily old version
- confirm compatibility with PHP 8.3
- run Composer validation
- include dependency change in the final report

Do not add a large Laravel authentication package.

## Production / Render

Document the exact Render environment variable:

`WORKLOG_TOTP_SECRET`

After deployment the intended setup is:

Google Authenticator
│
│ 6-digit code
▼
https://jira-worklog-bot.onrender.com/login
│
▼
Laravel session
│
▼
Vue UI
│
▼
Jira
│
▼
Google Chat

## Verification

After implementation:

1. Run Pint.
2. Run Composer validation.
3. Run the complete Laravel test suite.
4. Run Vite production build.
5. Build Docker image if runtime dependencies changed.
6. Verify no real TOTP secret exists in tracked files.
7. Verify no TOTP secret enters frontend assets.
8. Verify Jira/Google Chat credentials remain server-side.
9. Update only genuinely completed TOTP-auth tasks in `TASKS.md`.

## Final Report

Report:

- files created
- files modified
- Composer dependency added
- authentication flow
- protected/public routes
- middleware
- session strategy
- CSRF strategy
- login throttling
- TOTP drift policy
- setup command
- tests/assertions
- exact local setup steps
- exact Render configuration steps
- known limitations

Do not implement additional features or proceed to another phase.

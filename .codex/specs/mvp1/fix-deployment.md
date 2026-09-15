The Render deployment is live at:

`https://jira-worklog-bot.onrender.com/`

but external requests currently return:

`400 Bad Request`

The container now starts successfully, so this is no longer the previous FrankenPHP execution problem.

Inspect the current Dockerfile, FrankenPHP/Caddy configuration, Laravel middleware, trusted host configuration, proxy configuration, and runtime environment assumptions.

Focus on why requests with host:

`jira-worklog-bot.onrender.com`

would be rejected while local Docker access works.

Verify especially:

* FrankenPHP/Caddy is listening on `0.0.0.0:${PORT}` or `:${PORT}`
* it is not restricted to `localhost`
* no Caddy site block requires a different hostname
* Laravel trusted-host middleware is not rejecting the Render hostname
* proxy/forwarded headers from Render are handled correctly
* `APP_URL` can be set to `https://jira-worklog-bot.onrender.com`
* `/health` and `/` both work through the Render public URL

Do not make unrelated changes.

After fixing, verify locally with a non-localhost Host header if useful, for example:

`curl -i -H "Host: jira-worklog-bot.onrender.com" http://localhost:10000/health`

Report the exact root cause and files changed.

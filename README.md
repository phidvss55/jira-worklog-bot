# Jira Worklog Bot

A small single-user application for logging work to Jira Cloud from a Vue interface. Laravel validates the request and creates the Jira worklog, then sends a best-effort notification to Google Chat through an incoming webhook.

```text
Vue + Vite → Laravel → Jira Cloud → Google Chat webhook
```

## MVP2 Workflow

After TOTP login, the application loads the active sprint and groups the user's relevant Jira subtasks beneath their parent issues. Only subtasks can be logged: Laravel verifies Jira's subtask metadata for every manual and quick worklog request before creating it.

The default flow is select a subtask, select a duration, and log work. Today's Jira-backed total is shown against the 7-hour informational target and refreshes after successful worklogs.

## Stack

- Laravel 12 and PHP 8.3+
- Vue 3 and Vite
- Jira Cloud REST API
- Google Chat Incoming Webhook
- Docker and Render

## Local Development

Requirements: PHP, Composer, Node.js, and npm.

```bash
composer run setup
composer run dev
```

Configure the application in `.env` using `.env.example` as the template. Jira credentials and webhook URLs must never be committed.

Run the checks with:

```bash
php artisan test
npm run build
./vendor/bin/pint --test
composer validate --no-check-publish
```

## Docker Image

Pushes to `main` build and publish these Docker Hub tags through GitHub Actions:

```text
phidinh/jira-worklog-bot:latest
phidinh/jira-worklog-bot:sha-<commit-sha>
```

The repository requires `DOCKERHUB_USERNAME` and `DOCKERHUB_TOKEN` GitHub Actions secrets.

## Documentation

See [ARCHITECTURE.md](ARCHITECTURE.md), [DESIGN.md](DESIGN.md), and [TASKS.md](TASKS.md) for architecture, product behavior, and implementation progress.

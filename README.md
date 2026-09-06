# Jira Worklog Bot

A small single-user application for logging work to Jira Cloud from a Vue interface. Laravel validates the request and creates the Jira worklog, then sends a best-effort notification to Google Chat through an incoming webhook.

```text
Vue + Vite → Laravel → Jira Cloud → Google Chat webhook
```

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

Read AGENTS.md, ARCHITECTURE.md, DESIGN.md, and TASKS.md completely before making any changes.

We are changing the implementation order slightly: Docker and Render deployment will be completed before the Google Chat incoming webhook integration.

Implement the Docker phase only.

Do not implement Render-specific infrastructure beyond what is necessary to make the Docker image compatible with Render.

Before coding:

Inspect the existing Laravel 12 + Vue/Vite application.

Inspect composer.json, package.json, Vite configuration, Laravel configuration, public entry point, and existing environment requirements.

Inspect the current PHP extensions/dependencies.

Preserve the working Jira integration and Vue UI.

Produce a short implementation plan before making changes.

Goal

Create a production-oriented Docker image that can run the complete application:

Browser
→ Laravel + built Vue assets
→ existing /api/worklogs
→ Jira Cloud

The same image should later be deployable as a Render Web Service.

Runtime

Use PHP 8.3 as the production target documented by the project.

Use a multi-stage Docker build where appropriate.

The final runtime image should NOT require Node.js/npm to serve the application.

Conceptually:

Node build stage
→ build Vue/Vite production assets

Composer/PHP build stage
→ install production PHP dependencies

Runtime stage
→ Laravel + production assets

Keep the image reasonably small and understandable. Avoid unnecessary infrastructure.

Web Server

Choose the simplest production-suitable approach compatible with the project's architecture and Render.

The container MUST:

listen on 0.0.0.0

support Render's PORT environment variable

expose Laravel through HTTP

serve Vite production assets correctly

route Laravel requests correctly

not depend on php artisan serve as the preferred production server unless there is a strong documented reason

Do not introduce nginx + PHP-FPM complexity unless it is genuinely necessary.

Prefer a simple production-capable single-container setup.

Explain the selected server/runtime approach in the final report.

Laravel Production Setup

Ensure the container supports normal production Laravel behavior.

Consider:

APP_ENV=production

APP_DEBUG=false

APP_KEY supplied at runtime, never baked into the image

config cache

route cache where safe

view cache where safe

writable Laravel runtime directories

storage permissions

Do not bake secrets into the Docker image.

Jira Configuration

Jira configuration MUST remain runtime environment configuration:

JIRA_BASE_URL

JIRA_EMAIL

JIRA_API_TOKEN

Do not put real values in:

Dockerfile

image layers

source files

frontend environment variables

built Vite assets

The Jira API token must remain server-side only.

Frontend Build

Run the Vite production build during Docker image creation.

The resulting assets should be served by Laravel/public assets at runtime.

Do not expose server-only environment variables to Vite.

Verify the production build works without the Vite development server.

Health Check

Preserve the existing:

GET /health

It must be accessible from inside the running container and later usable by Render.

Do not make the health endpoint depend on Jira availability.

.dockerignore

Add an appropriate .dockerignore.

At minimum consider excluding:

.git

.env

node_modules

local vendor directory when appropriate

test artifacts

IDE files

local logs

unnecessary development files

Do not accidentally exclude files required for Composer/Vite builds.

Local Verification

The completed image must be testable locally.

Provide exact commands to:

build the image

run the container

provide the required runtime environment variables

open the application

call /health

Prefer a safe approach such as using an env file rather than putting secrets directly into shell history.

Example conceptual usage:

docker build -t jira-worklog-bot .

docker run --rm
--env-file .env.docker
-p 8080:8080
jira-worklog-bot

But adapt this to the actual implementation.

Do not commit .env.docker containing secrets.

Tests / Verification

After implementation:

Run Pint.

Run Composer validation.

Run the complete Laravel test suite.

Run the normal Vite production build.

Build the Docker image.

Start the Docker container.

Verify GET /health.

Verify GET / serves the production Vue application.

Confirm the container does not require the Vite dev server.

Confirm no real credentials exist in Docker layers/configuration/source-controlled files.

If practical, inspect the final image/container environment enough to confirm Jira secrets were not baked into the image.

Scope

Do NOT implement:

Google Chat webhook

Google Chat slash command

Google Cloud integration

database

Redis

queues

worklog history

edit/delete worklog

Do NOT deploy to Render yet.

Only make changes necessary for the Docker phase.

Update only genuinely completed Docker tasks in TASKS.md.

Final Report

Report:

files created

files modified

Docker architecture

base images used

production web server approach

final exposed/listening port behavior

how Render's PORT will be supported

frontend build strategy

local Docker build result

local container smoke-test result

/health result

/ result

Laravel test count/assertions

exact local build/run commands

remaining Docker or deployment concerns

Do not proceed to Render deployment.

  

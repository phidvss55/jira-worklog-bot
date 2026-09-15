Read `AGENTS.md`, `ARCHITECTURE.md`, `DESIGN.md`, and `TASKS.md` completely before making any changes.

Phase 1 and Phase 2 are complete and the existing test suite passes.

We are now implementing **Phase 3 — Google Chat Command Parser only**.

Before modifying code:

1. Inspect the existing Phase 1/2 implementation.
2. Understand how `POST /api/worklogs` creates and executes the existing `LogWorkCommand`.
3. Inspect `DurationParser`, `WorklogDateParser`, `LogWorkHandler`, and the Jira abstraction.
4. Preserve the existing application flow and avoid duplicating validation or normalization logic.

## Goal

Convert a Google Chat command string into input that can be passed through the existing worklog application flow.

Supported commands:

```text
/log BKM4-1234 2h15m
/log BKM4-1234 2h15m 14:30
/log BKM4-1234 2h15m 04/09/2026 14:30
```

The parser should conceptually produce:

```text
ticket
duration
date?
time?
```

Examples:

```text
/log BKM4-1234 2h15m
```

becomes:

```text
ticket   = BKM4-1234
duration = 2h15m
date     = null
time     = null
```

---

```text
/log BKM4-1234 2h15m 14:30
```

becomes:

```text
ticket   = BKM4-1234
duration = 2h15m
date     = null
time     = 14:30
```

---

```text
/log BKM4-1234 2h15m 04/09/2026 14:30
```

becomes:

```text
ticket   = BKM4-1234
duration = 2h15m
date     = 04/09/2026
time     = 14:30
```

## Responsibilities

Implement `GoogleChatCommandParser` under the Google Chat service/integration area defined by `ARCHITECTURE.md`.

The parser is responsible only for:

- recognizing the `/log` command
- separating command arguments
- identifying ticket
- identifying duration
- identifying optional date/time arguments
- normalizing harmless input formatting such as leading/trailing or repeated whitespace
- returning a small typed/immutable parsed-command object if appropriate

Do NOT duplicate the business validation already owned by:

- `DurationParser`
- `WorklogDateParser`
- existing ticket normalization/validation
- `LogWorkHandler`

For example, the Google Chat parser does not need its own implementation for converting `2h15m` to seconds.

It should extract `2h15m` and allow the existing application flow to process it.

## Command Grammar

Support exactly these MVP shapes:

```text
/log <ticket> <duration>

/log <ticket> <duration> <time>

/log <ticket> <duration> <date> <time>
```

Do not implement additional commands or syntax.

Do not implement yet:

```text
/log BKM4-1234 2h yesterday
/log BKM4-1234 2h "comment"
/undo
/worklogs
/help
```

## Invalid Command Structure

The parser must fail cleanly for malformed command structures such as:

```text
/log
/log BKM4-1234
/log BKM4-1234 2h 14:30 unexpected
/test BKM4-1234 2h
random text
```

Use a dedicated application/integration exception or result type if that fits the existing project style.

Do not return raw PHP errors.

## Whitespace

These should behave equivalently:

```text
/log BKM4-1234 2h15m

   /log   BKM4-1234    2h15m
```

Do not make whitespace handling unnecessarily strict.

## Case Handling

The command name may be handled case-insensitively if it keeps the implementation simple.

Ticket normalization should continue to use the existing application behavior rather than introducing a second normalization implementation.

## Response Builder

Implement the Google Chat response builder specified in `TASKS.md`, but keep it independent from actual Google Chat HTTP integration.

It should be able to build concise success/error message content.

Target success representation:

```text
✅ Worklog added

BKM4-1234
Time: 2h 15m
Started: 05/09/2026 14:30
```

Target invalid-command representation should be concise and show the supported syntax, for example:

```text
❌ Invalid command

Usage:
/log BKM4-1234 2h15m
/log BKM4-1234 2h15m 14:30
/log BKM4-1234 2h15m 04/09/2026 14:30
```

Do not couple the response builder to an HTTP controller yet.

## Tests

Add focused unit tests for the parser.

At minimum cover:

- `/log BKM4-1234 2h15m`
- `/log BKM4-1234 2h15m 14:30`
- `/log BKM4-1234 2h15m 04/09/2026 14:30`
- leading/trailing whitespace
- repeated whitespace
- missing ticket
- missing duration
- unsupported command
- too many arguments

Also verify that invalid duration/date/time values remain handled by the existing appropriate parsers/application flow rather than introducing duplicated parsing rules in `GoogleChatCommandParser`.

Add tests for the response builder where useful.

## Architecture Constraint

The intended future Phase 4 flow is:

```text
Google Chat HTTP event
        ↓
GoogleChatController
        ↓
GoogleChatCommandParser
        ↓
existing worklog application flow
        ↓
LogWorkHandler
        ↓
JiraClient
        ↓
GoogleChatResponseBuilder
```

Phase 3 should prepare for this flow but must NOT implement the Google Chat HTTP endpoint.

## Scope

Do NOT implement:

- `POST /api/google-chat`
- Google Cloud configuration
- Google Chat API calls
- Google request authentication
- Google user authorization
- Jira changes
- database
- Redis
- queues
- Docker
- Render deployment
- OAuth
- additional bot commands

Do not modify working Phase 1/2 behavior unless necessary for clean reuse. If a refactor is necessary, keep it minimal and explain why.

## Verification

After implementation:

1. Run Pint.
2. Run Composer validation.
3. Run the complete test suite.
4. Confirm all existing Phase 1 and Phase 2 tests still pass.
5. Confirm no external Google service is contacted by tests.
6. Update only genuinely completed Phase 3 tasks in `TASKS.md`.

Report:

- files created
- files modified
- parser design
- response builder design
- any minimal refactoring performed
- total tests/assertions
- any remaining Phase 3 work

Do not proceed to Phase 4.

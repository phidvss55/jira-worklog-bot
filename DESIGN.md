# Product Design

## 1. Product Goal

Provide the fastest possible way for a single user to log working time into Jira from Google Chat.

The primary design goal is:

> Logging work should require one short command and no additional UI interaction.

## 2. Primary Command

```text
/log <ticket> <duration> [time]
/log <ticket> <duration> [date] [time]
```

Examples:

```text
/log BKM4-1234 2h15m
/log BKM4-1234 2h15m 14:30
/log BKM4-1234 2h15m 04/09/2026 14:30
```

## 3. Ticket Format

Ticket identifiers should follow normal Jira issue-key format.

Examples:

```text
BKM4-1234
ABC-10
OPS-999
```

Ticket input should be normalized to uppercase where appropriate.

Example:

```text
bkm4-1234
```

becomes:

```text
BKM4-1234
```

Jira remains responsible for determining whether the ticket actually exists.

## 4. Duration

Supported:

```text
15m
30m
45m

1h
2h
8h

1h15m
1h30m
2h15m
7h30m
```

Whitespace inside duration is not supported.

Valid:

```text
2h15m
```

Invalid:

```text
2h 15m
```

Duration must be greater than zero.

## 5. Started Time

### Default

Command:

```text
/log BKM4-1234 2h15m
```

means:

```text
started = current date/time
```

### Explicit Time

```text
/log BKM4-1234 2h15m 14:30
```

means:

```text
started = today at 14:30
```

### Explicit Date and Time

```text
/log BKM4-1234 2h15m 04/09/2026 14:30
```

means:

```text
started = 04/09/2026 14:30
```

All values are interpreted in:

```text
Asia/Ho_Chi_Minh
```

unless application configuration overrides the timezone.

## 6. Google Chat Success Response

Example command:

```text
/log BKM4-1234 2h15m 14:30
```

Expected response:

```text
✅ Worklog added

BKM4-1234
Time: 2h 15m
Started: 05/09/2026 14:30
```

Keep the response concise.

## 7. Invalid Command

Example:

```text
/log BKM4-1234 abc
```

Response:

```text
❌ Invalid duration: "abc"

Examples:
30m
1h
1h30m
2h15m
```

Do not display long technical explanations.

## 8. Invalid Ticket

Example:

```text
/log BKM4-999999 2h
```

Response:

```text
❌ Unable to log work

BKM4-999999
Ticket was not found or is not accessible.
```

## 9. Jira Rejection

When Jira rejects a worklog:

```text
❌ Unable to log work

BKM4-1234
Jira rejected the worklog.
```

If Jira provides a safe and useful validation reason, the application may display it.

Never expose internal credentials, HTTP authorization headers, or sensitive debugging data.

## 10. Manual Development API

Before Google Chat integration is implemented, the application exposes:

```text
POST /api/worklogs
```

Request:

```json
{
  "ticket": "BKM4-1234",
  "duration": "2h15m",
  "date": "05/09/2026",
  "time": "14:30"
}
```

`date` and `time` are optional.

Response during the mock phase:

```json
{
  "success": true,
  "data": {
    "ticket": "BKM4-1234",
    "duration": "2h15m",
    "durationSeconds": 8100,
    "started": "2026-09-05T14:30:00+07:00"
  }
}
```

Once real Jira integration exists, the endpoint should execute the same application use case used by Google Chat.

## 11. Product Constraints

The product is designed for one user.

Therefore the MVP intentionally avoids:

- registration
- login UI
- user profiles
- Jira account selection
- organization management
- dashboards
- worklog history storage

## 12. Future Enhancements

Possible future commands may include:

```text
/log BKM4-1234 2h yesterday
/log BKM4-1234 2h "Fix validation bug"
/worklogs today
/undo
```

These are not part of the current scope.

Do not implement them unless explicitly added to `TASKS.md`.

## 13. Design Principle

Optimize for:

```text
minimum typing
+
predictable behavior
+
clear confirmation
```

The normal workflow should remain:

```text
type command
    ↓
press Enter
    ↓
worklog created
    ↓
confirmation
```

No additional confirmation step should be required for a valid command in the MVP.

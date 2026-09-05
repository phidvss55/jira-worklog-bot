# Product Design

## 1. Product Goal

Provide a fast, secure web interface for one user to log working time into Jira and receive a confirmation in Google Chat.

The primary design goal is:

> A normal daily worklog should require only a ticket, a duration, and one submit action.

## 2. Primary Experience

```text
Open application
    ↓
Sign in when the session is absent
    ↓
Enter ticket and duration
    ↓
Optionally adjust date and time
    ↓
Select Log Work
    ↓
Jira worklog created
    ↓
UI confirmation + Google Chat notification attempt
```

The MVP is a small Laravel + Vue application, not a dashboard. It does not require Vue Router, Pinia, or a UI component framework.

## 3. Authentication Experience

When no authenticated session exists, show a minimal password screen.

```text
┌─────────────────────────────────────┐
│ Jira Worklog                        │
│                                     │
│ Personal password                   │
│ ┌─────────────────────────────────┐ │
│ │ ••••••••••••                    │ │
│ └─────────────────────────────────┘ │
│                                     │
│              [ Sign in ]            │
└─────────────────────────────────────┘
```

Requirements:

- do not reveal whether configuration or password details are wrong
- disable the submit button and show progress while signing in
- show a concise error for rejected or rate-limited attempts
- never store the plaintext password in local storage
- provide a simple logout action after authentication

No registration, password reset, profile, account management, or Google login is needed.

## 4. Worklog Form

The authenticated page contains one primary form.

```text
┌─────────────────────────────────────────┐
│ Jira Worklog                    Log out │
│ Log your working time to Jira          │
│                                         │
│ Jira Ticket                             │
│ ┌─────────────────────────────────────┐ │
│ │ BKM4-1234                           │ │
│ └─────────────────────────────────────┘ │
│                                         │
│ Duration                                │
│ ┌─────────────────────────────────────┐ │
│ │ 2h15m                               │ │
│ └─────────────────────────────────────┘ │
│ Examples: 30m, 1h, 1h30m, 2h15m       │
│                                         │
│ Date                     Start time     │
│ ┌───────────────────┐   ┌─────────────┐ │
│ │ 05/09/2026        │   │ 14:30       │ │
│ └───────────────────┘   └─────────────┘ │
│                                         │
│              [ Log Work ]               │
│                                         │
│ ✓ Worklog added                         │
│   BKM4-1234 · 2h 15m · 14:30            │
└─────────────────────────────────────────┘
```

The layout must remain usable on mobile and desktop. On narrow screens, date and time may stack vertically.

## 5. Field Behavior

### Jira Ticket

Required. Accept normal Jira issue-key format such as:

```text
BKM4-1234
ABC-10
OPS-999
```

Normalize lowercase input to uppercase. Jira remains responsible for determining whether the ticket exists and is accessible.

### Duration

Required. Supported examples:

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

Whitespace inside a duration is not supported. Duration must be greater than zero.

### Date and Time

Default date to today and time to the current time in the configured application timezone. The user may adjust either value before submitting.

All values are interpreted in `Asia/Ho_Chi_Minh` unless application configuration overrides it. Browser display must not change the server-side interpretation.

Client-side validation may give immediate feedback, but Laravel validation and parsers are authoritative.

## 6. Submission Behavior

On submit:

1. Prevent duplicate submissions.
2. Disable the primary action and show a clear loading label.
3. Send the form to `POST /api/worklogs` using the authenticated session and CSRF protection.
4. Display server validation beside the relevant field where possible.
5. Preserve the entered values after a failure so they can be corrected.
6. Display success only after Jira confirms creation.

The MVP does not require a confirmation dialog before a valid submission.

## 7. Success States

### Jira and Notification Succeeded

```text
✓ Worklog added
BKM4-1234 · 2h 15m · 05/09/2026 14:30
Google Chat notified.
```

### Jira Succeeded but Notification Failed

```text
✓ Worklog added
BKM4-1234 · 2h 15m · 05/09/2026 14:30
Google Chat notification could not be sent.
```

The second state is still a success. It must not invite the user to submit the worklog again because doing so could create a duplicate Jira worklog.

## 8. Failure States

### Invalid Input

Use concise field-level messages, for example:

```text
Enter a ticket such as BKM4-1234.
Enter a duration such as 30m, 1h, or 2h15m.
Enter a valid date.
Enter a valid time.
```

### Jira Rejection

```text
Unable to log work
BKM4-1234 was not found, is not accessible, or Jira rejected the worklog.
```

Use a more specific reason only when Jira provides one that is safe and useful. Never expose credentials, authorization headers, raw webhook URLs, or internal stack traces.

### Session Expired

Return the user to the password screen with a concise message. Preserve worklog input in memory when practical, but do not store sensitive authentication data.

## 9. API Contract

Request:

```http
POST /api/worklogs
```

```json
{
  "ticket": "BKM4-1234",
  "duration": "2h15m",
  "date": "05/09/2026",
  "time": "14:30"
}
```

Successful response:

```json
{
  "success": true,
  "data": {
    "ticket": "BKM4-1234",
    "duration": "2h15m",
    "durationSeconds": 8100,
    "started": "2026-09-05T14:30:00+07:00"
  },
  "notificationSent": true
}
```

If Jira succeeds and the notification fails, return the same successful worklog data with `notificationSent: false`.

The API must reject unauthenticated requests. It must never expose Jira credentials, the personal password hash, or the Google Chat webhook URL.

## 10. Google Chat Notification

After Jira succeeds, send a concise incoming-webhook message:

```text
✅ Jira Worklog Added

🎫 BKM4-1234
⏱ 2h 15m
🕐 05/09/2026 14:30
```

Google Chat is notification-only. The product no longer supports entering `/log` commands in Google Chat.

## 11. Accessibility and Usability

- every input has a visible label
- keyboard submission and logical focus order work
- loading and result messages are understandable without relying only on color
- validation errors are associated with their fields
- primary controls have usable touch targets
- the page remains readable at common mobile widths

## 12. Product Constraints

The MVP intentionally avoids:

- multi-user accounts
- registration and password recovery
- database-backed profiles or sessions
- Jira account selection
- worklog history storage
- a general dashboard
- Vue Router, Pinia, and UI frameworks
- Google Chat slash commands or an interactive Chat app
- Google OAuth
- worklog editing, deletion, and undo
- comments/descriptions

## 13. Design Principle

Optimize for:

```text
minimum input
+
predictable behavior
+
clear confirmation
+
no accidental duplicate worklogs
```

The normal daily workflow should remain:

```text
ticket + duration
        ↓
select Log Work
        ↓
Jira worklog created
        ↓
UI confirmation
        ↓
best-effort Google Chat notification
```

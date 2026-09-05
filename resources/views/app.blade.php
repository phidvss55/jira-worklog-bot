<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#17233c">
    <title>Jira Worklog</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div
        id="app"
        data-default-date="{{ $defaultDate }}"
        data-default-time="{{ $defaultTime }}"
    ></div>
</body>
</html>

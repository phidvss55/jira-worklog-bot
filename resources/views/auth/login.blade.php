<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#17233c">
    <title>Sign in · Jira Worklog</title>
    @vite(['resources/css/app.css'])
</head>
<body>
    <main class="page-shell">
        <section class="worklog-card login-card" aria-labelledby="login-title">
            <header class="card-header">
                <div class="brand-mark" aria-hidden="true">JW</div>
                <div>
                    <p class="eyebrow">Personal workspace</p>
                    <h1 id="login-title">Jira Worklog</h1>
                    <p class="subtitle">Sign in to log your working time.</p>
                </div>
            </header>

            <form id="login-form" class="worklog-form" method="POST" action="{{ route('login.store') }}">
                @csrf

                <div class="field-group">
                    <label for="code">Authenticator code</label>
                    <input
                        id="code"
                        name="code"
                        type="text"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        pattern="[0-9]{6}"
                        maxlength="6"
                        required
                        autofocus
                        aria-invalid="{{ $errors->has('code') || isset($rateLimitError) ? 'true' : 'false' }}"
                        @if ($errors->has('code') || isset($rateLimitError)) aria-describedby="code-error" @endif
                    >
                    @if ($errors->has('code') || isset($rateLimitError))
                        <p id="code-error" class="field-error" role="alert">
                            {{ $rateLimitError ?? $errors->first('code') }}
                        </p>
                    @endif
                </div>

                <button id="login-button" class="primary-button" type="submit">Sign In</button>
            </form>
        </section>
    </main>

    <script>
        document.getElementById('login-form').addEventListener('submit', () => {
            const button = document.getElementById('login-button');
            button.disabled = true;
            button.textContent = 'Signing in…';
        });
    </script>
</body>
</html>

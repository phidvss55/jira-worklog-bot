<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureWorklogAuthenticated;
use App\Http\Requests\LoginRequest;
use App\Services\Authentication\TotpAuthenticator;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class AuthenticationController extends Controller
{
    public function __construct(
        private readonly TotpAuthenticator $authenticator,
        private readonly RateLimiter $rateLimiter,
    ) {}

    public function create(Request $request): View|RedirectResponse
    {
        if ($request->session()->get(EnsureWorklogAuthenticated::SESSION_KEY) === true) {
            return redirect()->route('worklog');
        }

        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse|Response
    {
        $rateLimitKey = $this->rateLimitKey($request);
        $maxAttempts = (int) config('worklog.auth.max_attempts');

        if ($this->rateLimiter->tooManyAttempts($rateLimitKey, $maxAttempts)) {
            return response()->view('auth.login', [
                'rateLimitError' => 'Too many login attempts. Try again in one minute.',
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        if (! $this->authenticator->verify($request->string('code')->toString())) {
            $this->rateLimiter->hit(
                $rateLimitKey,
                (int) config('worklog.auth.decay_seconds'),
            );

            return back()->withErrors(['code' => 'Invalid authentication code.']);
        }

        $this->rateLimiter->clear($rateLimitKey);
        $request->session()->regenerate();
        $request->session()->put(EnsureWorklogAuthenticated::SESSION_KEY, true);

        return redirect()->intended(route('worklog'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function rateLimitKey(Request $request): string
    {
        return 'worklog-login:'.$request->ip();
    }
}

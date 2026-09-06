<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureWorklogAuthenticated
{
    public const SESSION_KEY = 'worklog_authenticated';

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get(self::SESSION_KEY) === true) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Authentication required.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return new RedirectResponse(route('login'));
    }
}

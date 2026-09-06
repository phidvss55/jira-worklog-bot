<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class HealthCheckTest extends TestCase
{
    public function test_health_endpoint_is_available(): void
    {
        $this->get('/health')->assertOk();
    }

    public function test_render_forwarded_headers_are_trusted(): void
    {
        Route::get('/test-forwarded-request', function (Request $request): array {
            return [
                'host' => $request->getHost(),
                'secure' => $request->isSecure(),
            ];
        });

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
            ->withHeaders([
                'Host' => 'internal-service:10000',
                'X-Forwarded-Host' => 'jira-worklog-bot.onrender.com',
                'X-Forwarded-Proto' => 'https',
            ])
            ->get('/test-forwarded-request')
            ->assertOk()
            ->assertJson([
                'host' => 'jira-worklog-bot.onrender.com',
                'secure' => true,
            ]);
    }
}

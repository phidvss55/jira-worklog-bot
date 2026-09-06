<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureWorklogAuthenticated;
use Carbon\CarbonImmutable;
use Tests\TestCase;

final class WorklogPageTest extends TestCase
{
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_page_mounts_the_vue_app_with_defaults_from_the_configured_timezone(): void
    {
        $this->withoutVite();
        config(['app.timezone' => 'Asia/Ho_Chi_Minh']);
        CarbonImmutable::setTestNow('2026-09-05T16:24:00+07:00');

        $this->withSession([EnsureWorklogAuthenticated::SESSION_KEY => true])
            ->get('/')
            ->assertOk()
            ->assertSee('id="app"', false)
            ->assertSee('data-default-date="2026-09-05"', false)
            ->assertSee('data-default-time="16:24"', false)
            ->assertDontSee('JIRA_API_TOKEN')
            ->assertDontSee('JIRA_EMAIL');
    }
}

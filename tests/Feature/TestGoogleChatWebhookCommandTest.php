<?php

namespace Tests\Feature;

use App\Services\Jira\JiraClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

final class TestGoogleChatWebhookCommandTest extends TestCase
{
    private const WEBHOOK_URL = 'https://google-chat.example.test/webhook';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.google_chat.webhook_url' => self::WEBHOOK_URL]);
        Http::preventStrayRequests();

        $jiraClient = Mockery::mock(JiraClient::class);
        $jiraClient->shouldNotReceive('logWork');
        $jiraClient->shouldNotReceive('checkConnection');
        $this->app->instance(JiraClient::class, $jiraClient);
    }

    public function test_command_sends_an_identifiable_message_without_using_jira(): void
    {
        Http::fake([
            self::WEBHOOK_URL => Http::response([], 200),
        ]);

        $this->artisan('google-chat:test')
            ->expectsOutputToContain('Google Chat notification sent successfully.')
            ->doesntExpectOutputToContain(self::WEBHOOK_URL)
            ->assertSuccessful();

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->data() === [
            'text' => '🧪 Jira Worklog Bot — Google Chat webhook test successful.',
        ]);
    }

    public function test_command_reports_failure_without_exposing_the_webhook_url(): void
    {
        Http::fake([
            self::WEBHOOK_URL => Http::response([], 500),
        ]);

        $this->artisan('google-chat:test')
            ->expectsOutputToContain('Google Chat notification failed.')
            ->doesntExpectOutputToContain(self::WEBHOOK_URL)
            ->assertFailed();
    }
}

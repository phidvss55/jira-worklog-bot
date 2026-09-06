<?php

namespace Tests\Feature;

use App\Application\Worklog\LogWorkCommand;
use App\Application\Worklog\WorklogNotifier;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class GoogleChatWorklogNotifierTest extends TestCase
{
    private const WEBHOOK_URL = 'https://google-chat.example.test/webhook';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.timezone' => 'Asia/Ho_Chi_Minh',
            'services.google_chat.webhook_url' => self::WEBHOOK_URL,
            'services.google_chat.timeout' => 3,
        ]);

        Http::preventStrayRequests();
    }

    public function test_success_posts_the_normalized_worklog_message_once(): void
    {
        Http::fake([
            self::WEBHOOK_URL => Http::response([], 200),
        ]);

        $sent = $this->notifier()->notify($this->command());

        $this->assertTrue($sent);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === self::WEBHOOK_URL
            && $request->data() === [
                'text' => "✅ Jira Worklog Added\n\n🎫 BKM4-1234\n⏱ 2h 15m\n🕐 06/09/2026 15:30",
            ]);
    }

    #[DataProvider('failedHttpStatuses')]
    public function test_http_failure_is_safe_and_returns_false(int $status): void
    {
        Log::spy();
        Http::fake([
            self::WEBHOOK_URL => Http::response(['sensitive' => self::WEBHOOK_URL], $status),
        ]);

        $sent = $this->notifier()->notify($this->command());

        $this->assertFalse($sent);
        Http::assertSentCount(1);
        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Google Chat notification was not sent.', [
                'provider' => 'google_chat',
                'category' => 'http',
                'ticket' => 'BKM4-1234',
                'status' => $status,
            ]);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function failedHttpStatuses(): array
    {
        return [
            'bad request' => [400],
            'forbidden' => [403],
            'rate limited' => [429],
            'server error' => [503],
        ];
    }

    public function test_connection_failure_is_safe_and_returns_false(): void
    {
        Log::spy();
        Http::fake(Http::failedConnection('Failure included '.self::WEBHOOK_URL));

        $sent = $this->notifier()->notify($this->command());

        $this->assertFalse($sent);
        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Google Chat notification was not sent.', [
                'provider' => 'google_chat',
                'category' => 'connection',
                'ticket' => 'BKM4-1234',
            ]);
    }

    public function test_missing_configuration_skips_http_and_returns_false(): void
    {
        Log::spy();
        config(['services.google_chat.webhook_url' => null]);

        $sent = $this->notifier()->notify($this->command());

        $this->assertFalse($sent);
        Http::assertNothingSent();
        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Google Chat notification was not sent.', [
                'provider' => 'google_chat',
                'category' => 'missing_configuration',
                'ticket' => 'BKM4-1234',
            ]);
    }

    private function notifier(): WorklogNotifier
    {
        return $this->app->make(WorklogNotifier::class);
    }

    private function command(): LogWorkCommand
    {
        return new LogWorkCommand(
            ticket: 'BKM4-1234',
            duration: '2h15m',
            durationSeconds: 8100,
            started: CarbonImmutable::parse('2026-09-06T08:30:00+00:00'),
        );
    }
}

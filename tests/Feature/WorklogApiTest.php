<?php

namespace Tests\Feature;

use App\Application\Worklog\LogWorkCommand;
use App\Application\Worklog\WorklogNotifier;
use App\Http\Middleware\EnsureWorklogAuthenticated;
use App\Services\Jira\JiraClient;
use App\Services\Jira\JiraClientException;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class WorklogApiTest extends TestCase
{
    private const GOOGLE_CHAT_WEBHOOK_URL = 'https://google-chat.example.test/webhook';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withSession([EnsureWorklogAuthenticated::SESSION_KEY => true]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_valid_worklog_request_is_normalized_and_sent_to_jira_client(): void
    {
        $jiraClient = Mockery::mock(JiraClient::class);
        $jiraClient->shouldReceive('logWork')
            ->once()
            ->withArgs(function (string $ticket, int $durationSeconds, CarbonImmutable $started): bool {
                return $ticket === 'BKM4-1234'
                    && $durationSeconds === 8100
                    && $started->toIso8601String() === '2026-09-05T14:30:00+07:00';
            });
        $this->app->instance(JiraClient::class, $jiraClient);

        $notifier = Mockery::mock(WorklogNotifier::class);
        $notifier->shouldReceive('notify')
            ->once()
            ->withArgs(fn (LogWorkCommand $command): bool => $command->ticket === 'BKM4-1234'
                && $command->duration === '2h15m'
                && $command->durationSeconds === 8100
                && $command->started->toIso8601String() === '2026-09-05T14:30:00+07:00')
            ->andReturn(true);
        $this->app->instance(WorklogNotifier::class, $notifier);

        $this->postJson('/api/worklogs', [
            'ticket' => 'bkm4-1234',
            'duration' => '2h15m',
            'date' => '05/09/2026',
            'time' => '14:30',
        ])->assertOk()->assertExactJson([
            'success' => true,
            'data' => [
                'ticket' => 'BKM4-1234',
                'duration' => '2h15m',
                'durationSeconds' => 8100,
                'started' => '2026-09-05T14:30:00+07:00',
            ],
            'notificationSent' => true,
        ]);
    }

    public function test_real_client_completes_the_application_flow_through_a_fake_http_response(): void
    {
        CarbonImmutable::setTestNow('2026-09-05 10:15:30', 'Asia/Ho_Chi_Minh');
        config([
            'services.jira.base_url' => 'https://jira.example.test',
            'services.jira.email' => 'developer@example.test',
            'services.jira.api_token' => 'api-token',
            'services.google_chat.webhook_url' => self::GOOGLE_CHAT_WEBHOOK_URL,
        ]);
        Http::preventStrayRequests();
        Http::fake([
            'jira.example.test/rest/api/3/issue/OPS-999/worklog' => Http::response([], 201),
            self::GOOGLE_CHAT_WEBHOOK_URL => Http::response([], 200),
        ]);

        $this->postJson('/api/worklogs', [
            'ticket' => 'OPS-999',
            'duration' => '30m',
        ])->assertOk()
            ->assertJsonPath('data.started', '2026-09-05T10:15:30+07:00')
            ->assertJsonPath('notificationSent', true);

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request): bool => $request->url() === self::GOOGLE_CHAT_WEBHOOK_URL
            && $request['text'] === "✅ Jira Worklog Added\n\n🎫 OPS-999\n⏱ 30m\n🕐 05/09/2026 10:15");
    }

    public function test_invalid_ticket_format_is_rejected(): void
    {
        $this->postJson('/api/worklogs', $this->validPayload(['ticket' => 'not-a-ticket']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ticket');
    }

    public function test_invalid_duration_is_rejected(): void
    {
        $this->postJson('/api/worklogs', $this->validPayload(['duration' => '2hours']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('duration');
    }

    public function test_invalid_date_is_rejected(): void
    {
        $this->postJson('/api/worklogs', $this->validPayload(['date' => '31/02/2026']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date');
    }

    public function test_invalid_time_is_rejected(): void
    {
        $this->postJson('/api/worklogs', $this->validPayload(['time' => '25:30']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('time');
    }

    public function test_date_without_time_is_rejected(): void
    {
        $this->postJson('/api/worklogs', [
            'ticket' => 'OPS-999',
            'duration' => '30m',
            'date' => '05/09/2026',
        ])->assertUnprocessable()->assertJsonValidationErrors('time');
    }

    public function test_jira_service_failure_returns_a_safe_error(): void
    {
        $jiraClient = Mockery::mock(JiraClient::class);
        $jiraClient->shouldReceive('logWork')
            ->once()
            ->andThrow(new JiraClientException('Sensitive upstream details'));
        $this->app->instance(JiraClient::class, $jiraClient);

        $notifier = Mockery::mock(WorklogNotifier::class);
        $notifier->shouldNotReceive('notify');
        $this->app->instance(WorklogNotifier::class, $notifier);

        $this->postJson('/api/worklogs', $this->validPayload())
            ->assertStatus(502)
            ->assertExactJson([
                'success' => false,
                'message' => 'Unable to log work. Jira rejected the worklog.',
            ]);
    }

    #[DataProvider('googleChatHttpFailures')]
    public function test_notification_http_failure_does_not_change_jira_success(int $status): void
    {
        Log::spy();
        config(['services.google_chat.webhook_url' => self::GOOGLE_CHAT_WEBHOOK_URL]);
        Http::preventStrayRequests();
        Http::fake([
            self::GOOGLE_CHAT_WEBHOOK_URL => Http::response([], $status),
        ]);

        $jiraClient = Mockery::mock(JiraClient::class);
        $jiraClient->shouldReceive('logWork')->once();
        $this->app->instance(JiraClient::class, $jiraClient);

        $this->postJson('/api/worklogs', $this->validPayload())
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('notificationSent', false)
            ->assertJsonPath('data.ticket', 'BKM4-1234');

        Http::assertSentCount(1);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function googleChatHttpFailures(): array
    {
        return [
            'bad request' => [400],
            'forbidden' => [403],
            'rate limited' => [429],
            'server error' => [503],
        ];
    }

    public function test_notification_connection_failure_does_not_change_jira_success(): void
    {
        Log::spy();
        config(['services.google_chat.webhook_url' => self::GOOGLE_CHAT_WEBHOOK_URL]);
        Http::preventStrayRequests();
        Http::fake(Http::failedConnection('Operation timed out.'));

        $jiraClient = Mockery::mock(JiraClient::class);
        $jiraClient->shouldReceive('logWork')->once();
        $this->app->instance(JiraClient::class, $jiraClient);

        $this->postJson('/api/worklogs', $this->validPayload())
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('notificationSent', false)
            ->assertJsonPath('data.ticket', 'BKM4-1234');
    }

    public function test_missing_notification_configuration_does_not_change_jira_success(): void
    {
        Log::spy();
        config(['services.google_chat.webhook_url' => null]);
        Http::preventStrayRequests();

        $jiraClient = Mockery::mock(JiraClient::class);
        $jiraClient->shouldReceive('logWork')->once();
        $this->app->instance(JiraClient::class, $jiraClient);

        $this->postJson('/api/worklogs', $this->validPayload())
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('notificationSent', false)
            ->assertJsonPath('data.ticket', 'BKM4-1234');

        Http::assertNothingSent();
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'ticket' => 'BKM4-1234',
            'duration' => '2h15m',
            'date' => '05/09/2026',
            'time' => '14:30',
        ], $overrides);
    }
}

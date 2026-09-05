<?php

namespace Tests\Feature;

use App\Services\Jira\JiraClient;
use App\Services\Jira\JiraClientException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

final class WorklogApiTest extends TestCase
{
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
        ]);
    }

    public function test_real_client_completes_the_application_flow_through_a_fake_http_response(): void
    {
        CarbonImmutable::setTestNow('2026-09-05 10:15:30', 'Asia/Ho_Chi_Minh');
        config([
            'services.jira.base_url' => 'https://jira.example.test',
            'services.jira.email' => 'developer@example.test',
            'services.jira.api_token' => 'api-token',
        ]);
        Http::preventStrayRequests();
        Http::fake([
            'jira.example.test/rest/api/3/issue/OPS-999/worklog' => Http::response([], 201),
        ]);

        $this->postJson('/api/worklogs', [
            'ticket' => 'OPS-999',
            'duration' => '30m',
        ])->assertOk()->assertJsonPath('data.started', '2026-09-05T10:15:30+07:00');
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

        $this->postJson('/api/worklogs', $this->validPayload())
            ->assertStatus(502)
            ->assertExactJson([
                'success' => false,
                'message' => 'Unable to log work. Jira rejected the worklog.',
            ]);
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

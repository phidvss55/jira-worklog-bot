<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureWorklogAuthenticated;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class TodayWorklogApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-09-15T00:30:00+07:00');
        config([
            'app.timezone' => 'Asia/Ho_Chi_Minh',
            'services.jira.base_url' => 'https://jira.example.test',
            'services.jira.email' => 'developer@example.test',
            'services.jira.api_token' => 'api-token',
            'worklog.daily_target_minutes' => 420,
        ]);
        $this->withSession([EnsureWorklogAuthenticated::SESSION_KEY => true]);
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_it_returns_the_authenticated_users_total_for_the_configured_local_day(): void
    {
        Http::fake([
            'jira.example.test/rest/api/3/myself' => Http::response(['accountId' => 'account-me']),
            'jira.example.test/rest/api/3/search/jql' => Http::response([
                'issues' => [[
                    'key' => 'BKM4-1234',
                    'fields' => [
                        'worklog' => [
                            'total' => 4,
                            'worklogs' => [
                                $this->worklog('account-me', 7200, '2026-09-14T17:30:00.000+0000'),
                                $this->worklog('account-me', 12600, '2026-09-15T09:00:00.000+0700'),
                                $this->worklog('other-user', 3600, '2026-09-15T10:00:00.000+0700'),
                                $this->worklog('account-me', 3600, '2026-09-16T00:00:00.000+0700'),
                            ],
                        ],
                    ],
                ]],
            ]),
        ]);

        $this->getJson('/api/worklogs/today')
            ->assertOk()
            ->assertExactJson([
                'date' => '2026-09-15',
                'totalMinutes' => 330,
                'targetMinutes' => 420,
            ]);

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://jira.example.test/rest/api/3/search/jql'
            && $request['jql'] === 'worklogAuthor = currentUser() AND worklogDate >= "2026/09/15" AND worklogDate < "2026/09/16"'
            && $request['fields'] === ['worklog']);
    }

    public function test_it_fetches_complete_issue_worklogs_when_jira_embeds_only_a_partial_page(): void
    {
        Http::fake([
            'jira.example.test/rest/api/3/myself' => Http::response(['accountId' => 'account-me']),
            'jira.example.test/rest/api/3/search/jql' => Http::response([
                'issues' => [[
                    'key' => 'BKM4-1234',
                    'fields' => ['worklog' => ['total' => 21, 'worklogs' => []]],
                ]],
            ]),
            'jira.example.test/rest/api/3/issue/BKM4-1234/worklog*' => Http::response([
                'total' => 1,
                'worklogs' => [$this->worklog('account-me', 1800, '2026-09-15T10:00:00.000+0700')],
            ]),
        ]);

        $this->getJson('/api/worklogs/today')
            ->assertOk()
            ->assertJsonPath('totalMinutes', 30);

        Http::assertSentCount(3);
    }

    public function test_jira_failure_returns_a_safe_error(): void
    {
        Http::fake(['*' => Http::response(['errorMessages' => ['Sensitive response']], 503)]);

        $this->getJson('/api/worklogs/today')
            ->assertStatus(502)
            ->assertExactJson([
                'success' => false,
                'message' => "Unable to load today's worklog summary from Jira.",
            ]);
    }

    public function test_total_above_the_daily_target_is_returned_without_being_limited(): void
    {
        Http::fake([
            'jira.example.test/rest/api/3/myself' => Http::response(['accountId' => 'account-me']),
            'jira.example.test/rest/api/3/search/jql' => Http::response([
                'issues' => [[
                    'key' => 'BKM4-1234',
                    'fields' => ['worklog' => [
                        'total' => 1,
                        'worklogs' => [$this->worklog('account-me', 27000, '2026-09-15T10:00:00.000+0700')],
                    ]],
                ]],
            ]),
        ]);

        $this->getJson('/api/worklogs/today')
            ->assertOk()
            ->assertJsonPath('totalMinutes', 450)
            ->assertJsonPath('targetMinutes', 420);
    }

    public function test_guests_cannot_reach_jira_for_their_worklog_summary(): void
    {
        $this->flushSession();

        $this->getJson('/api/worklogs/today')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Authentication required.');

        Http::assertNothingSent();
    }

    /** @return array<string, mixed> */
    private function worklog(string $accountId, int $seconds, string $started): array
    {
        return [
            'author' => ['accountId' => $accountId],
            'timeSpentSeconds' => $seconds,
            'started' => $started,
        ];
    }
}

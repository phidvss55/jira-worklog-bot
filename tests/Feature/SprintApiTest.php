<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureWorklogAuthenticated;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class SprintApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.jira.base_url' => 'https://jira.example.test',
            'services.jira.email' => 'developer@example.test',
            'services.jira.api_token' => 'api-token',
            'services.jira.board_id' => 42,
        ]);

        Http::preventStrayRequests();
    }

    public function test_authenticated_request_returns_normalized_grouped_subtasks(): void
    {
        Http::fake($this->activeSprintResponses());

        $this->authenticated()
            ->getJson('/api/sprint')
            ->assertOk()
            ->assertExactJson([
                'sprint' => [
                    'id' => 24,
                    'name' => 'Sprint 24',
                    'startDate' => '2026-09-09',
                    'endDate' => '2026-09-22',
                ],
                'issues' => [[
                    'key' => 'BKM4-1201',
                    'summary' => 'DSOP Action Validation',
                    'status' => 'In Progress',
                    'issueType' => 'Story',
                    'subtasks' => [[
                        'key' => 'BKM4-1234',
                        'summary' => 'Implement validation',
                        'status' => 'In Progress',
                        'issueType' => 'Sub-task',
                        'loggable' => true,
                    ]],
                ]],
            ]);

        Http::assertSentCount(3);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://jira.example.test/rest/api/3/myself');
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://jira.example.test/rest/agile/1.0/board/42/sprint?state=active');
        Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://jira.example.test/rest/agile/1.0/sprint/24/issue?'));
    }

    public function test_parent_is_context_only_and_only_my_subtasks_are_loggable(): void
    {
        Http::fake($this->activeSprintResponses());

        $response = $this->authenticated()->getJson('/api/sprint')->assertOk();
        $parent = $response->json('issues.0');

        $this->assertArrayNotHasKey('loggable', $parent);
        $this->assertSame('BKM4-1234', $parent['subtasks'][0]['key']);
        $this->assertTrue($parent['subtasks'][0]['loggable']);
        $this->assertCount(1, $parent['subtasks']);
    }

    public function test_no_active_sprint_returns_an_empty_normalized_view(): void
    {
        Http::fake([
            'jira.example.test/rest/api/3/myself' => Http::response(['accountId' => 'account-me']),
            'jira.example.test/rest/agile/1.0/board/42/sprint*' => Http::response(['values' => []]),
        ]);

        $this->authenticated()->getJson('/api/sprint')
            ->assertOk()
            ->assertExactJson(['sprint' => null, 'issues' => []]);

        Http::assertSentCount(2);
    }

    public function test_active_sprint_with_no_relevant_subtasks_returns_the_sprint_and_no_issues(): void
    {
        $responses = $this->activeSprintResponses();
        $responses['jira.example.test/rest/agile/1.0/sprint/24/issue*'] = Http::response([
            'total' => 1,
            'issues' => [$this->parentIssue()],
        ]);
        Http::fake($responses);

        $this->authenticated()->getJson('/api/sprint')
            ->assertOk()
            ->assertJsonPath('sprint.id', 24)
            ->assertJsonPath('issues', []);
    }

    #[DataProvider('jiraFailureStatuses')]
    public function test_jira_http_failures_return_a_safe_api_error(int $status): void
    {
        Http::fake(['*' => Http::response(['errorMessages' => ['Sensitive upstream response']], $status)]);

        $this->authenticated()->getJson('/api/sprint')
            ->assertStatus(502)
            ->assertExactJson([
                'success' => false,
                'message' => 'Unable to load the active sprint from Jira.',
            ]);
    }

    /** @return array<string, array{int}> */
    public static function jiraFailureStatuses(): array
    {
        return [
            'unauthorized' => [401],
            'forbidden' => [403],
            'not found' => [404],
            'rate limited' => [429],
            'server error' => [503],
        ];
    }

    public function test_jira_connection_failure_returns_a_safe_api_error(): void
    {
        Http::fake(Http::failedConnection('Sensitive connection error.'));

        $this->authenticated()->getJson('/api/sprint')
            ->assertStatus(502)
            ->assertJsonPath('message', 'Unable to load the active sprint from Jira.');
    }

    public function test_unauthenticated_request_is_rejected_before_jira_is_called(): void
    {
        $this->getJson('/api/sprint')
            ->assertUnauthorized()
            ->assertExactJson([
                'success' => false,
                'message' => 'Authentication required.',
            ]);

        Http::assertNothingSent();
    }

    private function authenticated(): static
    {
        return $this->withSession([EnsureWorklogAuthenticated::SESSION_KEY => true]);
    }

    /** @return array<string, Response> */
    private function activeSprintResponses(): array
    {
        return [
            'jira.example.test/rest/api/3/myself' => Http::response(['accountId' => 'account-me']),
            'jira.example.test/rest/agile/1.0/board/42/sprint*' => Http::response([
                'values' => [[
                    'id' => 24,
                    'name' => 'Sprint 24',
                    'startDate' => '2026-09-09T00:00:00.000Z',
                    'endDate' => '2026-09-22T00:00:00.000Z',
                ]],
            ]),
            'jira.example.test/rest/agile/1.0/sprint/24/issue*' => Http::response([
                'total' => 4,
                'issues' => [
                    $this->parentIssue(),
                    $this->mySubtaskIssue(),
                    $this->otherUsersSubtaskIssue(),
                    $this->unrelatedParentIssue(),
                ],
            ]),
        ];
    }

    /** @return array<string, mixed> */
    private function parentIssue(): array
    {
        return [
            'key' => 'BKM4-1201',
            'fields' => [
                'summary' => 'DSOP Action Validation',
                'status' => ['name' => 'In Progress'],
                'issuetype' => ['name' => 'Story', 'subtask' => false],
                'assignee' => ['accountId' => 'another-user'],
                'parent' => null,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function mySubtaskIssue(): array
    {
        return [
            'key' => 'BKM4-1234',
            'fields' => [
                'summary' => 'Implement validation',
                'status' => ['name' => 'In Progress'],
                'issuetype' => ['name' => 'Sub-task', 'subtask' => true],
                'assignee' => ['accountId' => 'account-me'],
                'parent' => ['key' => 'BKM4-1201'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function otherUsersSubtaskIssue(): array
    {
        $issue = $this->mySubtaskIssue();
        $issue['key'] = 'BKM4-1235';
        $issue['fields']['assignee'] = ['accountId' => 'another-user'];

        return $issue;
    }

    /** @return array<string, mixed> */
    private function unrelatedParentIssue(): array
    {
        $issue = $this->parentIssue();
        $issue['key'] = 'BKM4-1202';
        $issue['fields']['summary'] = 'Unrelated parent';

        return $issue;
    }
}

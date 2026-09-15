<?php

namespace Tests\Feature;

use App\Application\Worklog\WorklogNotifier;
use App\Http\Middleware\EnsureWorklogAuthenticated;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class SubtaskOnlyWorklogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.jira.base_url' => 'https://jira.example.test',
            'services.jira.email' => 'developer@example.test',
            'services.jira.api_token' => 'api-token',
        ]);
        $this->withSession([EnsureWorklogAuthenticated::SESSION_KEY => true]);
        Http::preventStrayRequests();
    }

    #[DataProvider('customSubtaskTypes')]
    public function test_custom_jira_subtask_types_are_allowed_when_metadata_marks_them_as_subtasks(string $issueType): void
    {
        $notifier = Mockery::mock(WorklogNotifier::class);
        $notifier->shouldReceive('notify')->once()->andReturn(true);
        $this->app->instance(WorklogNotifier::class, $notifier);

        Http::fake([
            'jira.example.test/rest/api/3/issue/BKM4-1234*' => Http::response([
                'fields' => [
                    'issuetype' => ['name' => $issueType, 'subtask' => true],
                    'parent' => ['key' => 'BKM4-1201'],
                ],
            ]),
            'jira.example.test/rest/api/3/issue/BKM4-1234/worklog' => Http::response([], 201),
        ]);

        $this->postJson('/api/worklogs', $this->payload())
            ->assertOk()
            ->assertJsonPath('success', true);

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://jira.example.test/rest/api/3/issue/BKM4-1234/worklog');
    }

    /** @return array<string, array{string}> */
    public static function customSubtaskTypes(): array
    {
        return [
            'custom implementation subtask' => ['Sub-Imp'],
            'custom automation subtask' => ['Sub Automation'],
        ];
    }

    #[DataProvider('parentIssueTypes')]
    public function test_parent_issue_types_are_rejected_before_jira_worklog_or_google_chat(string $issueType): void
    {
        $notifier = Mockery::mock(WorklogNotifier::class);
        $notifier->shouldNotReceive('notify');
        $this->app->instance(WorklogNotifier::class, $notifier);

        Http::fake([
            'jira.example.test/rest/api/3/issue/BKM4-1201*' => Http::response([
                'fields' => [
                    'issuetype' => ['name' => $issueType, 'subtask' => false],
                    'parent' => null,
                ],
            ]),
        ]);

        $this->postJson('/api/worklogs', $this->payload(['ticket' => 'BKM4-1201']))
            ->assertUnprocessable()
            ->assertExactJson([
                'success' => false,
                'message' => 'Worklogs can only be logged against Jira sub-tasks.',
            ]);

        Http::assertSentCount(1);
        Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), '/worklog'));
    }

    /** @return array<string, array{string}> */
    public static function parentIssueTypes(): array
    {
        return [
            'story' => ['Story'],
            'task' => ['Task'],
            'work' => ['Work'],
            'tech solution' => ['Tech Solution'],
        ];
    }

    public function test_jira_subtask_lookup_failure_returns_a_safe_error_without_creating_a_worklog(): void
    {
        $notifier = Mockery::mock(WorklogNotifier::class);
        $notifier->shouldNotReceive('notify');
        $this->app->instance(WorklogNotifier::class, $notifier);
        Http::fake(['*' => Http::response(['errorMessages' => ['Sensitive Jira response']], 503)]);

        $this->postJson('/api/worklogs', $this->payload())
            ->assertStatus(502)
            ->assertJsonPath('message', 'Unable to log work. Jira rejected the worklog.');

        Http::assertSentCount(1);
    }

    /** @param array<string, string> $overrides
     * @return array<string, string>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'ticket' => 'BKM4-1234',
            'duration' => '30m',
            'date' => '05/09/2026',
            'time' => '14:30',
        ], $overrides);
    }
}

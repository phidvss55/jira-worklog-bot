<?php

namespace Tests\Feature;

use App\Services\Jira\JiraClient;
use App\Services\Jira\JiraClientException;
use Mockery;
use Tests\TestCase;

final class CheckJiraConnectionCommandTest extends TestCase
{
    public function test_command_reports_a_successful_connection(): void
    {
        $jiraClient = Mockery::mock(JiraClient::class);
        $jiraClient->shouldReceive('checkConnection')->once();
        $this->app->instance(JiraClient::class, $jiraClient);

        $this->artisan('jira:check')
            ->expectsOutputToContain('Jira connection successful.')
            ->assertSuccessful();
    }

    public function test_command_reports_a_safe_failure_without_printing_a_token(): void
    {
        $jiraClient = Mockery::mock(JiraClient::class);
        $jiraClient->shouldReceive('checkConnection')
            ->once()
            ->andThrow(new JiraClientException('Jira authentication failed.'));
        $this->app->instance(JiraClient::class, $jiraClient);

        $this->artisan('jira:check')
            ->expectsOutputToContain('Jira connection failed: Jira authentication failed.')
            ->doesntExpectOutputToContain('api-token')
            ->assertFailed();
    }
}

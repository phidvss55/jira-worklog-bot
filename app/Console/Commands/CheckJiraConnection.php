<?php

namespace App\Console\Commands;

use App\Services\Jira\JiraClient;
use App\Services\Jira\JiraClientException;
use Illuminate\Console\Command;

final class CheckJiraConnection extends Command
{
    protected $signature = 'jira:check';

    protected $description = 'Verify Jira credentials using the read-only current-user endpoint';

    public function handle(JiraClient $jiraClient): int
    {
        try {
            $jiraClient->checkConnection();
        } catch (JiraClientException $exception) {
            $this->components->error('Jira connection failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Jira connection successful.');

        return self::SUCCESS;
    }
}

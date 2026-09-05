<?php

namespace App\Application\Worklog;

use App\Services\Jira\JiraClient;

final readonly class LogWorkHandler
{
    public function __construct(private JiraClient $jiraClient) {}

    /**
     * @return array{ticket: string, duration: string, durationSeconds: int, started: string}
     */
    public function handle(LogWorkCommand $command): array
    {
        $this->jiraClient->logWork(
            $command->ticket,
            $command->durationSeconds,
            $command->started,
        );

        return $command->toArray();
    }
}

<?php

namespace App\Application\Worklog;

use App\Services\Jira\JiraClient;

final readonly class LogWorkHandler
{
    public function __construct(
        private JiraClient $jiraClient,
        private WorklogNotifier $notifier,
    ) {}

    /**
     * @return array{
     *     data: array{ticket: string, duration: string, durationSeconds: int, started: string},
     *     notificationSent: bool
     * }
     */
    public function handle(LogWorkCommand $command): array
    {
        if (! $this->jiraClient->isSubtask($command->ticket)) {
            throw new WorklogTargetNotSubtaskException('Worklogs can only be logged against Jira sub-tasks.');
        }

        $this->jiraClient->logWork(
            $command->ticket,
            $command->durationSeconds,
            $command->started,
        );

        return [
            'data' => $command->toArray(),
            'notificationSent' => $this->notifier->notify($command),
        ];
    }
}

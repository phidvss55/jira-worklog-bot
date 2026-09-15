<?php

namespace App\Services\Jira;

use Carbon\CarbonImmutable;

final class MockJiraClient implements JiraClient
{
    public function logWork(string $ticket, int $durationSeconds, CarbonImmutable $started): void
    {
        // Phase 1 deliberately performs no external request.
    }

    public function isSubtask(string $ticket): bool
    {
        return true;
    }

    public function checkConnection(): void
    {
        // Phase 1 deliberately performs no external request.
    }

    public function currentUserAccountId(): string
    {
        return 'mock-account-id';
    }

    public function worklogsForUserBetween(string $accountId, CarbonImmutable $start, CarbonImmutable $end): array
    {
        return [];
    }

    public function activeSprint(): ?JiraActiveSprint
    {
        return null;
    }

    public function sprintIssues(int $sprintId): array
    {
        return [];
    }
}

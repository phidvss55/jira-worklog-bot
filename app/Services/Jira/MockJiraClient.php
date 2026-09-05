<?php

namespace App\Services\Jira;

use Carbon\CarbonImmutable;

final class MockJiraClient implements JiraClient
{
    public function logWork(string $ticket, int $durationSeconds, CarbonImmutable $started): void
    {
        // Phase 1 deliberately performs no external request.
    }

    public function checkConnection(): void
    {
        // Phase 1 deliberately performs no external request.
    }
}

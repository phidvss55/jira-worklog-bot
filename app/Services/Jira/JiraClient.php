<?php

namespace App\Services\Jira;

use Carbon\CarbonImmutable;

interface JiraClient
{
    public function logWork(string $ticket, int $durationSeconds, CarbonImmutable $started): void;

    public function isSubtask(string $ticket): bool;

    public function checkConnection(): void;

    public function currentUserAccountId(): string;

    public function activeSprint(): ?JiraActiveSprint;

    /**
     * @return list<JiraIssue>
     */
    public function sprintIssues(int $sprintId): array;
}

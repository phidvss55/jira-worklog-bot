<?php

namespace App\Services\Jira;

use Carbon\CarbonImmutable;

interface JiraClient
{
    public function logWork(string $ticket, int $durationSeconds, CarbonImmutable $started): void;

    public function checkConnection(): void;
}

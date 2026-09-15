<?php

namespace App\Services\Jira;

use Carbon\CarbonImmutable;

final readonly class JiraWorklog
{
    public function __construct(
        public string $authorAccountId,
        public int $timeSpentSeconds,
        public CarbonImmutable $started,
    ) {}
}

<?php

namespace App\Services\Jira;

final readonly class JiraActiveSprint
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $startDate,
        public ?string $endDate,
    ) {}
}

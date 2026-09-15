<?php

namespace App\Services\Jira;

final readonly class JiraIssue
{
    public function __construct(
        public string $key,
        public string $summary,
        public string $status,
        public string $issueType,
        public bool $subtask,
        public ?string $assigneeAccountId,
        public ?string $parentKey,
        public ?int $estimateMinutes,
    ) {}

    public function isSubtask(): bool
    {
        return $this->subtask && $this->parentKey !== null;
    }
}

<?php

namespace App\Application\Sprint;

final readonly class ActiveSprintView
{
    /**
     * @param  list<array{key: string, summary: string, status: string, issueType: string, subtasks: list<array{key: string, summary: string, status: string, issueType: string, loggable: true}>}>  $issues
     */
    public function __construct(
        public ?SprintView $sprint,
        public array $issues,
    ) {}

    /** @return array{sprint: array{id: int, name: string, startDate: ?string, endDate: ?string}|null, issues: array<int, array{key: string, summary: string, status: string, issueType: string, subtasks: list<array{key: string, summary: string, status: string, issueType: string, loggable: true}>}>} */
    public function toArray(): array
    {
        return [
            'sprint' => $this->sprint?->toArray(),
            'issues' => $this->issues,
        ];
    }
}

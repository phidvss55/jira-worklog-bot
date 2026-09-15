<?php

namespace App\Application\Worklog;

final readonly class TodayWorklogSummary
{
    public function __construct(
        public string $date,
        public int $totalMinutes,
        public int $targetMinutes,
    ) {}

    /** @return array{date: string, totalMinutes: int, targetMinutes: int} */
    public function toArray(): array
    {
        return [
            'date' => $this->date,
            'totalMinutes' => $this->totalMinutes,
            'targetMinutes' => $this->targetMinutes,
        ];
    }
}

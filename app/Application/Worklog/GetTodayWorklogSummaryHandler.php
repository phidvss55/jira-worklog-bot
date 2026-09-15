<?php

namespace App\Application\Worklog;

use App\Services\Jira\JiraClient;
use Carbon\CarbonImmutable;

final readonly class GetTodayWorklogSummaryHandler
{
    public function __construct(
        private JiraClient $jira,
        private string $timezone,
        private int $targetMinutes,
    ) {}

    public function handle(): TodayWorklogSummary
    {
        $start = CarbonImmutable::now($this->timezone)->startOfDay();
        $end = $start->addDay();
        $accountId = $this->jira->currentUserAccountId();
        $totalSeconds = 0;

        foreach ($this->jira->worklogsForUserBetween($accountId, $start, $end) as $worklog) {
            $totalSeconds += $worklog->timeSpentSeconds;
        }

        return new TodayWorklogSummary(
            date: $start->toDateString(),
            totalMinutes: intdiv($totalSeconds, 60),
            targetMinutes: $this->targetMinutes,
        );
    }
}

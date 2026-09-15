<?php

namespace App\Application\Sprint;

use App\Services\Jira\JiraClient;
use App\Services\Jira\JiraIssue;

final readonly class GetActiveSprintHandler
{
    public function __construct(private JiraClient $jira) {}

    public function handle(): ActiveSprintView
    {
        $accountId = $this->jira->currentUserAccountId();
        $sprint = $this->jira->activeSprint();

        if ($sprint === null) {
            return new ActiveSprintView(sprint: null, issues: []);
        }

        $issues = $this->jira->sprintIssues($sprint->id);
        $parentsByKey = [];

        foreach ($issues as $issue) {
            if (! $issue->isSubtask()) {
                $parentsByKey[$issue->key] = $issue;
            }
        }

        $groupedIssues = [];

        foreach ($issues as $issue) {
            if (! $issue->isSubtask() || $issue->assigneeAccountId !== $accountId || $issue->parentKey === null) {
                continue;
            }

            $parent = $parentsByKey[$issue->parentKey] ?? null;

            if ($parent === null) {
                continue;
            }

            if (! isset($groupedIssues[$parent->key])) {
                $groupedIssues[$parent->key] = $this->parentView($parent);
            }

            $groupedIssues[$parent->key]['subtasks'][] = $this->subtaskView($issue);
        }

        return new ActiveSprintView(
            sprint: new SprintView(
                id: $sprint->id,
                name: $sprint->name,
                startDate: $sprint->startDate,
                endDate: $sprint->endDate,
            ),
            issues: array_values($groupedIssues),
        );
    }

    /** @return array{key: string, summary: string, status: string, issueType: string, subtasks: list<array{key: string, summary: string, status: string, issueType: string, loggable: true, estimateMinutes: ?int}>} */
    private function parentView(JiraIssue $issue): array
    {
        return [
            'key' => $issue->key,
            'summary' => $issue->summary,
            'status' => $issue->status,
            'issueType' => $issue->issueType,
            'subtasks' => [],
        ];
    }

    /** @return array{key: string, summary: string, status: string, issueType: string, loggable: true, estimateMinutes: ?int} */
    private function subtaskView(JiraIssue $issue): array
    {
        return [
            'key' => $issue->key,
            'summary' => $issue->summary,
            'status' => $issue->status,
            'issueType' => $issue->issueType,
            'loggable' => true,
            'estimateMinutes' => $issue->estimateMinutes,
        ];
    }
}

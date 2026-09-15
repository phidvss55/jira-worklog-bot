<?php

namespace App\Services\Jira;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

final readonly class JiraCloudClient implements JiraClient
{
    public function __construct(
        private Factory $http,
        private string $baseUrl,
        private string $email,
        private string $apiToken,
        private ?int $boardId,
        private int $timeoutSeconds = 10,
    ) {}

    public function logWork(string $ticket, int $durationSeconds, CarbonImmutable $started): void
    {
        try {
            $response = $this->request()->post(
                '/rest/api/3/issue/'.rawurlencode($ticket).'/worklog',
                [
                    'timeSpentSeconds' => $durationSeconds,
                    'started' => $started->format('Y-m-d\TH:i:s.vO'),
                ]
            );
        } catch (ConnectionException $exception) {
            throw new JiraClientException('Unable to connect to Jira.');
        }

        $this->ensureSuccessful($response);
    }

    public function isSubtask(string $ticket): bool
    {
        $response = $this->get('/rest/api/3/issue/'.rawurlencode($ticket), [
            'fields' => 'issuetype,parent',
        ]);

        return $response->json('fields.issuetype.subtask') === true
            && is_string($response->json('fields.parent.key'));
    }

    public function checkConnection(): void
    {
        try {
            $response = $this->request()->get('/rest/api/3/myself');
        } catch (ConnectionException $exception) {
            throw new JiraClientException('Unable to connect to Jira.');
        }

        $this->ensureSuccessful($response);
    }

    public function currentUserAccountId(): string
    {
        $response = $this->get('/rest/api/3/myself');
        $accountId = $response->json('accountId');

        if (! is_string($accountId) || $accountId === '') {
            throw new JiraClientException('Jira returned an invalid user response.');
        }

        return $accountId;
    }

    public function worklogsForUserBetween(string $accountId, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $issues = $this->issuesWithWorklogsInDateRange($start, $end);
        $worklogs = [];

        foreach ($issues as $issue) {
            $embeddedWorklogs = data_get($issue, 'fields.worklog.worklogs', []);
            $embeddedTotal = data_get($issue, 'fields.worklog.total', 0);

            if (! is_array($embeddedWorklogs)) {
                throw new JiraClientException('Jira returned an invalid worklog response.');
            }

            $issueWorklogs = is_int($embeddedTotal) && $embeddedTotal > count($embeddedWorklogs)
                ? $this->issueWorklogs((string) ($issue['key'] ?? ''))
                : $embeddedWorklogs;

            foreach ($issueWorklogs as $worklog) {
                if (! is_array($worklog)) {
                    continue;
                }

                $normalized = $this->worklogFromResponse($worklog);

                if ($normalized->authorAccountId === $accountId
                    && $normalized->started->greaterThanOrEqualTo($start)
                    && $normalized->started->lessThan($end)) {
                    $worklogs[] = $normalized;
                }
            }
        }

        return $worklogs;
    }

    public function activeSprint(): ?JiraActiveSprint
    {
        $boardId = $this->boardId();
        $response = $this->get('/rest/agile/1.0/board/'.$boardId.'/sprint', ['state' => 'active']);
        $sprint = $response->json('values.0');

        if (! is_array($sprint)) {
            return null;
        }

        $id = $sprint['id'] ?? null;
        $name = $sprint['name'] ?? null;

        if (! is_int($id) || ! is_string($name) || $name === '') {
            throw new JiraClientException('Jira returned an invalid active sprint response.');
        }

        return new JiraActiveSprint(
            id: $id,
            name: $name,
            startDate: $this->dateValue($sprint['startDate'] ?? null),
            endDate: $this->dateValue($sprint['endDate'] ?? null),
        );
    }

    public function sprintIssues(int $sprintId): array
    {
        $issues = [];
        $startAt = 0;

        do {
            $response = $this->get('/rest/agile/1.0/sprint/'.$sprintId.'/issue', [
                'startAt' => $startAt,
                'maxResults' => 100,
                'fields' => 'summary,status,issuetype,assignee,parent',
            ]);
            $page = $response->json('issues', []);

            if (! is_array($page)) {
                throw new JiraClientException('Jira returned an invalid sprint issue response.');
            }

            foreach ($page as $issue) {
                if (is_array($issue)) {
                    $issues[] = $this->issueFromResponse($issue);
                }
            }

            $startAt += count($page);
            $total = $response->json('total', 0);
        } while (is_int($total) && $startAt < $total && $page !== []);

        return $issues;
    }

    private function get(string $url, array $query = []): Response
    {
        try {
            $response = $this->request()->get($url, $query);
        } catch (ConnectionException $exception) {
            throw new JiraClientException('Unable to connect to Jira.');
        }

        $this->ensureSuccessful($response);

        return $response;
    }

    /** @return list<array<string, mixed>> */
    private function issuesWithWorklogsInDateRange(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $issues = [];
        $nextPageToken = null;

        do {
            $payload = [
                'jql' => sprintf(
                    'worklogAuthor = currentUser() AND worklogDate >= "%s" AND worklogDate < "%s"',
                    $start->format('Y/m/d'),
                    $end->format('Y/m/d'),
                ),
                'fields' => ['worklog'],
                'maxResults' => 100,
            ];

            if ($nextPageToken !== null) {
                $payload['nextPageToken'] = $nextPageToken;
            }

            $response = $this->post('/rest/api/3/search/jql', $payload);
            $page = $response->json('issues', []);

            if (! is_array($page)) {
                throw new JiraClientException('Jira returned an invalid worklog response.');
            }

            foreach ($page as $issue) {
                if (is_array($issue) && is_string($issue['key'] ?? null)) {
                    $issues[] = $issue;
                }
            }

            $pageToken = $response->json('nextPageToken');
            $nextPageToken = is_string($pageToken) && $pageToken !== '' ? $pageToken : null;
        } while ($nextPageToken !== null);

        return $issues;
    }

    /** @return list<array<string, mixed>> */
    private function issueWorklogs(string $issueKey): array
    {
        if ($issueKey === '') {
            throw new JiraClientException('Jira returned an invalid worklog response.');
        }

        $worklogs = [];
        $startAt = 0;

        do {
            $response = $this->get('/rest/api/3/issue/'.rawurlencode($issueKey).'/worklog', [
                'startAt' => $startAt,
                'maxResults' => 100,
            ]);
            $page = $response->json('worklogs', []);

            if (! is_array($page)) {
                throw new JiraClientException('Jira returned an invalid worklog response.');
            }

            foreach ($page as $worklog) {
                if (is_array($worklog)) {
                    $worklogs[] = $worklog;
                }
            }

            $startAt += count($page);
            $total = $response->json('total', 0);
        } while (is_int($total) && $startAt < $total && $page !== []);

        return $worklogs;
    }

    /** @param array<string, mixed> $payload */
    private function post(string $url, array $payload): Response
    {
        try {
            $response = $this->request()->post($url, $payload);
        } catch (ConnectionException) {
            throw new JiraClientException('Unable to connect to Jira.');
        }

        $this->ensureSuccessful($response);

        return $response;
    }

    /** @param array<string, mixed> $worklog */
    private function worklogFromResponse(array $worklog): JiraWorklog
    {
        $authorAccountId = data_get($worklog, 'author.accountId');
        $timeSpentSeconds = $worklog['timeSpentSeconds'] ?? null;
        $started = $worklog['started'] ?? null;

        if (! is_string($authorAccountId) || ! is_int($timeSpentSeconds) || ! is_string($started)) {
            throw new JiraClientException('Jira returned an invalid worklog response.');
        }

        try {
            return new JiraWorklog($authorAccountId, $timeSpentSeconds, CarbonImmutable::parse($started));
        } catch (\Exception) {
            throw new JiraClientException('Jira returned an invalid worklog response.');
        }
    }

    /** @param array<string, mixed> $issue */
    private function issueFromResponse(array $issue): JiraIssue
    {
        $key = $issue['key'] ?? null;
        $fields = $issue['fields'] ?? null;
        $summary = is_array($fields) ? $fields['summary'] ?? null : null;
        $status = is_array($fields) ? data_get($fields, 'status.name') : null;
        $issueType = is_array($fields) ? data_get($fields, 'issuetype.name') : null;

        if (! is_string($key) || ! is_string($summary) || ! is_string($status) || ! is_string($issueType)) {
            throw new JiraClientException('Jira returned an invalid sprint issue response.');
        }

        $assigneeAccountId = is_array($fields) ? data_get($fields, 'assignee.accountId') : null;
        $parentKey = is_array($fields) ? data_get($fields, 'parent.key') : null;
        $subtask = is_array($fields) && data_get($fields, 'issuetype.subtask') === true;

        return new JiraIssue(
            key: $key,
            summary: $summary,
            status: $status,
            issueType: $issueType,
            subtask: $subtask,
            assigneeAccountId: is_string($assigneeAccountId) ? $assigneeAccountId : null,
            parentKey: is_string($parentKey) ? $parentKey : null,
        );
    }

    private function boardId(): int
    {
        if ($this->boardId === null || $this->boardId <= 0) {
            throw new JiraClientException('Jira board configuration is incomplete.');
        }

        return $this->boardId;
    }

    private function dateValue(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->toDateString();
        } catch (\Exception) {
            throw new JiraClientException('Jira returned an invalid active sprint response.');
        }
    }

    private function request(): PendingRequest
    {
        if ($this->baseUrl === '' || $this->email === '' || $this->apiToken === '') {
            throw new JiraClientException('Jira configuration is incomplete.');
        }

        return $this->http
            ->baseUrl(rtrim($this->baseUrl, '/'))
            ->acceptJson()
            ->asJson()
            ->withBasicAuth($this->email, $this->apiToken)
            ->timeout($this->timeoutSeconds);
    }

    private function ensureSuccessful(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $message = match ($response->status()) {
            400 => 'Jira rejected the request as invalid.',
            401 => 'Jira authentication failed.',
            403 => 'Jira denied permission for the request.',
            404 => 'The Jira issue or resource was not found or is inaccessible.',
            429 => 'Jira rate limited the request. Try again later.',
            default => $response->serverError()
                ? 'Jira is temporarily unavailable.'
                : 'Jira rejected the request.',
        };

        throw new JiraClientException($message);
    }
}

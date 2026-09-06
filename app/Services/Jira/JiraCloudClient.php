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

    public function checkConnection(): void
    {
        try {
            $response = $this->request()->get('/rest/api/3/myself');
        } catch (ConnectionException $exception) {
            throw new JiraClientException('Unable to connect to Jira.');
        }

        $this->ensureSuccessful($response);
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

<?php

namespace App\Services\GoogleChat;

use App\Application\Worklog\LogWorkCommand;
use App\Application\Worklog\WorklogNotifier;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Psr\Log\LoggerInterface;

final readonly class GoogleChatWorklogNotifier implements WorklogNotifier
{
    public function __construct(
        private Factory $http,
        private LoggerInterface $logger,
        private ?string $webhookUrl,
        private string $timezone,
        private int $timeoutSeconds = 5,
    ) {}

    public function notify(LogWorkCommand $command): bool
    {
        $duration = preg_replace('/h(?=\d)/', 'h ', $command->duration) ?? $command->duration;
        $started = $command->started
            ->setTimezone($this->timezone)
            ->format('d/m/Y H:i');

        return $this->sendMessage(
            "✅ Jira Worklog Added\n\n🎫 {$command->ticket}\n⏱ {$duration}\n🕐 {$started}",
            $command->ticket,
        );
    }

    public function sendTestNotification(): bool
    {
        return $this->sendMessage('🧪 Jira Worklog Bot — Google Chat webhook test successful.');
    }

    private function sendMessage(string $message, ?string $ticket = null): bool
    {
        if ($this->webhookUrl === null || trim($this->webhookUrl) === '') {
            $this->logFailure('missing_configuration', $ticket);

            return false;
        }

        try {
            $response = $this->http
                ->acceptJson()
                ->asJson()
                ->timeout($this->timeoutSeconds)
                ->post($this->webhookUrl, ['text' => $message]);
        } catch (ConnectionException) {
            $this->logFailure('connection', $ticket);

            return false;
        }

        if (! $response->successful()) {
            $this->logFailure('http', $ticket, $response->status());

            return false;
        }

        return true;
    }

    private function logFailure(string $category, ?string $ticket = null, ?int $status = null): void
    {
        $context = [
            'provider' => 'google_chat',
            'category' => $category,
        ];

        if ($ticket !== null) {
            $context['ticket'] = $ticket;
        }

        if ($status !== null) {
            $context['status'] = $status;
        }

        $this->logger->warning('Google Chat notification was not sent.', $context);
    }
}

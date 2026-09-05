<?php

namespace App\Services\GoogleChat;

use Carbon\CarbonImmutable;

final class GoogleChatResponseBuilder
{
    public function worklogAdded(string $ticket, string $duration, CarbonImmutable $started): string
    {
        return implode("\n", [
            '✅ Worklog added',
            '',
            $ticket,
            'Time: '.$this->formatDuration($duration),
            'Started: '.$started->format('d/m/Y H:i'),
        ]);
    }

    public function invalidCommand(): string
    {
        return implode("\n", [
            '❌ Invalid command',
            '',
            'Usage:',
            '/log BKM4-1234 2h15m',
            '/log BKM4-1234 2h15m 14:30',
            '/log BKM4-1234 2h15m 04/09/2026 14:30',
        ]);
    }

    public function worklogFailed(string $ticket): string
    {
        return implode("\n", [
            '❌ Unable to log work',
            '',
            $ticket,
            'Jira rejected the worklog.',
        ]);
    }

    private function formatDuration(string $duration): string
    {
        return (string) preg_replace('/h(?=\d)/', 'h ', $duration);
    }
}

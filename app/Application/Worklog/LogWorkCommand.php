<?php

namespace App\Application\Worklog;

use Carbon\CarbonImmutable;

final readonly class LogWorkCommand
{
    public function __construct(
        public string $ticket,
        public string $duration,
        public int $durationSeconds,
        public CarbonImmutable $started,
    ) {}

    /**
     * @return array{ticket: string, duration: string, durationSeconds: int, started: string}
     */
    public function toArray(): array
    {
        return [
            'ticket' => $this->ticket,
            'duration' => $this->duration,
            'durationSeconds' => $this->durationSeconds,
            'started' => $this->started->toIso8601String(),
        ];
    }
}

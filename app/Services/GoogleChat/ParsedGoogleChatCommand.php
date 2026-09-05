<?php

namespace App\Services\GoogleChat;

final readonly class ParsedGoogleChatCommand
{
    public function __construct(
        public string $ticket,
        public string $duration,
        public ?string $date,
        public ?string $time,
    ) {}

    /**
     * @return array{ticket: string, duration: string, date: ?string, time: ?string}
     */
    public function toArray(): array
    {
        return [
            'ticket' => $this->ticket,
            'duration' => $this->duration,
            'date' => $this->date,
            'time' => $this->time,
        ];
    }
}

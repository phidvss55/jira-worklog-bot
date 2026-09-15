<?php

namespace App\Application\Sprint;

final readonly class SprintView
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $startDate,
        public ?string $endDate,
    ) {}

    /** @return array{id: int, name: string, startDate: ?string, endDate: ?string} */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ];
    }
}

<?php

namespace App\Support;

use InvalidArgumentException;

final class DurationParser
{
    public function parse(string $duration): int
    {
        if (preg_match('/^(?:(\d+)h)?(?:(\d+)m)?$/', $duration, $matches) !== 1) {
            throw new InvalidArgumentException('The duration format is invalid.');
        }

        $hours = isset($matches[1]) && $matches[1] !== '' ? (int) $matches[1] : 0;
        $minutes = isset($matches[2]) && $matches[2] !== '' ? (int) $matches[2] : 0;
        $seconds = ($hours * 3600) + ($minutes * 60);

        if ($seconds <= 0) {
            throw new InvalidArgumentException('The duration must be greater than zero.');
        }

        return $seconds;
    }
}

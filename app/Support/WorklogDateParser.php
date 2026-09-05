<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

final readonly class WorklogDateParser
{
    private DateTimeZone $timezone;

    public function __construct(string $timezone)
    {
        $this->timezone = new DateTimeZone($timezone);
    }

    public function parse(?string $date, ?string $time): CarbonImmutable
    {
        if ($date === null && $time === null) {
            return CarbonImmutable::now($this->timezone);
        }

        if ($date !== null && $time === null) {
            throw new InvalidArgumentException('A time is required when a date is provided.');
        }

        $date ??= CarbonImmutable::now($this->timezone)->format('d/m/Y');

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date) !== 1
            || preg_match('/^\d{2}:\d{2}$/', (string) $time) !== 1) {
            throw new InvalidArgumentException('The worklog date or time is invalid.');
        }

        $started = DateTimeImmutable::createFromFormat(
            '!d/m/Y H:i',
            $date.' '.$time,
            $this->timezone,
        );
        $errors = DateTimeImmutable::getLastErrors();

        if ($started === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw new InvalidArgumentException('The worklog date or time is invalid.');
        }

        return CarbonImmutable::instance($started);
    }
}

<?php

namespace Tests\Unit;

use App\Support\WorklogDateParser;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class WorklogDateParserTest extends TestCase
{
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_no_date_or_time_returns_now_in_the_configured_timezone(): void
    {
        CarbonImmutable::setTestNow('2026-09-05 09:12:34', 'UTC');

        $started = (new WorklogDateParser('Asia/Ho_Chi_Minh'))->parse(null, null);

        $this->assertSame('2026-09-05 16:12:34', $started->format('Y-m-d H:i:s'));
        $this->assertSame('Asia/Ho_Chi_Minh', $started->timezoneName);
    }

    public function test_time_only_uses_today_in_the_configured_timezone(): void
    {
        CarbonImmutable::setTestNow('2026-09-05T23:30:00+07:00');

        $started = (new WorklogDateParser('Asia/Ho_Chi_Minh'))->parse(null, '14:30');

        $this->assertSame('2026-09-05 14:30:00', $started->format('Y-m-d H:i:s'));
        $this->assertSame('+07:00', $started->format('P'));
    }

    public function test_explicit_date_and_time_are_parsed_in_the_configured_timezone(): void
    {
        $started = (new WorklogDateParser('Asia/Ho_Chi_Minh'))->parse('04/09/2026', '14:30');

        $this->assertSame('2026-09-04T14:30:00+07:00', $started->toIso8601String());
    }

    public function test_configured_timezone_is_not_the_server_timezone(): void
    {
        $originalTimezone = date_default_timezone_get();
        date_default_timezone_set('America/New_York');

        try {
            $started = (new WorklogDateParser('Asia/Ho_Chi_Minh'))->parse('04/09/2026', '14:30');
        } finally {
            date_default_timezone_set($originalTimezone);
        }

        $this->assertSame('+07:00', $started->format('P'));
    }

    #[DataProvider('invalidDateTimes')]
    public function test_invalid_date_or_time_is_rejected(?string $date, ?string $time): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new WorklogDateParser('Asia/Ho_Chi_Minh'))->parse($date, $time);
    }

    /**
     * @return array<string, array{?string, ?string}>
     */
    public static function invalidDateTimes(): array
    {
        return [
            'invalid date' => ['31/02/2026', '14:30'],
            'invalid date format' => ['2026-09-04', '14:30'],
            'invalid hour' => [null, '24:00'],
            'invalid time format' => [null, '2:30'],
            'date without time' => ['04/09/2026', null],
        ];
    }
}

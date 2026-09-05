<?php

namespace Tests\Unit;

use App\Support\DurationParser;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DurationParserTest extends TestCase
{
    #[DataProvider('validDurations')]
    public function test_it_parses_supported_durations(string $duration, int $expectedSeconds): void
    {
        $this->assertSame($expectedSeconds, (new DurationParser)->parse($duration));
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function validDurations(): array
    {
        return [
            'minutes' => ['15m', 900],
            'one hour' => ['1h', 3600],
            'hours and minutes' => ['1h30m', 5400],
            'two hours and minutes' => ['2h15m', 8100],
            'work day' => ['8h', 28800],
        ];
    }

    #[DataProvider('invalidDurations')]
    public function test_it_rejects_invalid_durations(string $duration): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new DurationParser)->parse($duration);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidDurations(): array
    {
        return [
            'text' => ['abc'],
            'words' => ['2hours'],
            'negative' => ['-1h'],
            'zero' => ['0h'],
            'empty' => [''],
            'internal whitespace' => ['2h 15m'],
        ];
    }
}

<?php

namespace Tests\Unit;

use App\Services\GoogleChat\GoogleChatCommandParser;
use App\Services\GoogleChat\InvalidGoogleChatCommandException;
use App\Support\DurationParser;
use App\Support\WorklogDateParser;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GoogleChatCommandParserTest extends TestCase
{
    private GoogleChatCommandParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new GoogleChatCommandParser;
    }

    public function test_it_parses_a_ticket_and_duration(): void
    {
        $parsed = $this->parser->parse('/log BKM4-1234 2h15m');

        $this->assertSame([
            'ticket' => 'BKM4-1234',
            'duration' => '2h15m',
            'date' => null,
            'time' => null,
        ], $parsed->toArray());
    }

    public function test_it_parses_a_time_only_command(): void
    {
        $parsed = $this->parser->parse('/log BKM4-1234 2h15m 14:30');

        $this->assertSame([
            'ticket' => 'BKM4-1234',
            'duration' => '2h15m',
            'date' => null,
            'time' => '14:30',
        ], $parsed->toArray());
    }

    public function test_it_parses_an_explicit_date_and_time_command(): void
    {
        $parsed = $this->parser->parse('/log BKM4-1234 2h15m 04/09/2026 14:30');

        $this->assertSame([
            'ticket' => 'BKM4-1234',
            'duration' => '2h15m',
            'date' => '04/09/2026',
            'time' => '14:30',
        ], $parsed->toArray());
    }

    #[DataProvider('whitespaceCommands')]
    public function test_it_normalizes_harmless_whitespace(string $command): void
    {
        $parsed = $this->parser->parse($command);

        $this->assertSame('BKM4-1234', $parsed->ticket);
        $this->assertSame('2h15m', $parsed->duration);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function whitespaceCommands(): array
    {
        return [
            'leading and trailing' => ['   /log BKM4-1234 2h15m   '],
            'repeated spaces' => ['/log   BKM4-1234    2h15m'],
            'mixed whitespace' => ["\t/log\nBKM4-1234\t2h15m\n"],
        ];
    }

    #[DataProvider('invalidStructures')]
    public function test_it_rejects_invalid_command_structures(string $command): void
    {
        $this->expectException(InvalidGoogleChatCommandException::class);

        $this->parser->parse($command);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidStructures(): array
    {
        return [
            'empty log command' => ['/log'],
            'missing duration' => ['/log BKM4-1234'],
            'unsupported command' => ['/test BKM4-1234 2h'],
            'random text' => ['random text'],
            'too many arguments' => ['/log BKM4-1234 2h 14:30 unexpected'],
            'more than date and time' => ['/log BKM4-1234 2h 04/09/2026 14:30 unexpected'],
        ];
    }

    public function test_command_name_is_case_insensitive(): void
    {
        $this->assertSame('BKM4-1234', $this->parser->parse('/LOG BKM4-1234 2h')->ticket);
    }

    public function test_ticket_case_is_left_for_the_existing_application_flow(): void
    {
        $this->assertSame('bkm4-1234', $this->parser->parse('/log bkm4-1234 2h')->ticket);
    }

    public function test_invalid_duration_is_left_for_the_duration_parser(): void
    {
        $parsed = $this->parser->parse('/log BKM4-1234 invalid-duration');

        $this->assertSame('invalid-duration', $parsed->duration);
        $this->expectException(InvalidArgumentException::class);

        (new DurationParser)->parse($parsed->duration);
    }

    #[DataProvider('invalidDateAndTimeCommands')]
    public function test_invalid_date_and_time_are_left_for_the_date_parser(
        string $command,
        ?string $expectedDate,
        ?string $expectedTime,
    ): void {
        $parsed = $this->parser->parse($command);

        $this->assertSame($expectedDate, $parsed->date);
        $this->assertSame($expectedTime, $parsed->time);
        $this->expectException(InvalidArgumentException::class);

        (new WorklogDateParser('Asia/Ho_Chi_Minh'))->parse($parsed->date, $parsed->time);
    }

    /**
     * @return array<string, array{string, ?string, ?string}>
     */
    public static function invalidDateAndTimeCommands(): array
    {
        return [
            'invalid time' => ['/log BKM4-1234 2h 25:00', null, '25:00'],
            'invalid date' => ['/log BKM4-1234 2h 31/02/2026 14:30', '31/02/2026', '14:30'],
        ];
    }
}

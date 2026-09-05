<?php

namespace Tests\Unit;

use App\Services\GoogleChat\GoogleChatResponseBuilder;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

final class GoogleChatResponseBuilderTest extends TestCase
{
    private GoogleChatResponseBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new GoogleChatResponseBuilder;
    }

    public function test_it_builds_a_concise_success_response(): void
    {
        $response = $this->builder->worklogAdded(
            'BKM4-1234',
            '2h15m',
            CarbonImmutable::parse('2026-09-05T14:30:00+07:00'),
        );

        $this->assertSame(implode("\n", [
            '✅ Worklog added',
            '',
            'BKM4-1234',
            'Time: 2h 15m',
            'Started: 05/09/2026 14:30',
        ]), $response);
    }

    public function test_it_builds_an_invalid_command_response_with_supported_usage(): void
    {
        $response = $this->builder->invalidCommand();

        $this->assertStringContainsString('❌ Invalid command', $response);
        $this->assertStringContainsString('/log BKM4-1234 2h15m', $response);
        $this->assertStringContainsString('/log BKM4-1234 2h15m 14:30', $response);
        $this->assertStringContainsString('/log BKM4-1234 2h15m 04/09/2026 14:30', $response);
    }

    public function test_it_does_not_add_trailing_whitespace_to_hour_only_durations(): void
    {
        $response = $this->builder->worklogAdded(
            'BKM4-1234',
            '1h',
            CarbonImmutable::parse('2026-09-05T14:30:00+07:00'),
        );

        $this->assertStringContainsString("Time: 1h\n", $response);
    }

    public function test_it_builds_a_safe_worklog_failure_response(): void
    {
        $this->assertSame(implode("\n", [
            '❌ Unable to log work',
            '',
            'BKM4-1234',
            'Jira rejected the worklog.',
        ]), $this->builder->worklogFailed('BKM4-1234'));
    }
}

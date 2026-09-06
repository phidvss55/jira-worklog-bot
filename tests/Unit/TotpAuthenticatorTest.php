<?php

namespace Tests\Unit;

use App\Services\Authentication\TotpAuthenticator;
use Carbon\CarbonImmutable;
use OTPHP\InternalClock;
use OTPHP\TOTP;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TotpAuthenticatorTest extends TestCase
{
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    #[DataProvider('acceptedPeriodOffsets')]
    public function test_it_accepts_previous_current_and_next_period(int $periodOffset): void
    {
        CarbonImmutable::setTestNow('2026-09-06T15:30:15+07:00');
        $totp = TOTP::generate(new InternalClock, 20);
        $authenticator = new TotpAuthenticator($totp->getSecret());
        $timestamp = CarbonImmutable::now()->getTimestamp() + ($periodOffset * 30);

        $this->assertTrue($authenticator->verify($totp->at($timestamp)));
    }

    /**
     * @return array<string, array{int}>
     */
    public static function acceptedPeriodOffsets(): array
    {
        return [
            'previous period' => [-1],
            'current period' => [0],
            'next period' => [1],
        ];
    }

    public function test_it_rejects_codes_outside_the_drift_window(): void
    {
        CarbonImmutable::setTestNow('2026-09-06T15:30:15+07:00');

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $totp = TOTP::generate(new InternalClock, 20);
            $authenticator = new TotpAuthenticator($totp->getSecret());
            $timestamp = CarbonImmutable::now()->getTimestamp() + 60;
            $code = $totp->at($timestamp);

            if (! $authenticator->verify($code)) {
                $this->assertFalse($authenticator->verify($code));

                return;
            }
        }

        $this->fail('Unable to generate an out-of-window test code without a collision.');
    }

    public function test_it_fails_closed_for_missing_invalid_or_malformed_input(): void
    {
        $this->assertFalse((new TotpAuthenticator(null))->verify('123456'));
        $this->assertFalse((new TotpAuthenticator('not-valid-base32!'))->verify('123456'));
        $this->assertFalse((new TotpAuthenticator(''))->verify('123456'));
        $this->assertFalse((new TotpAuthenticator(null))->verify('12345'));
    }
}

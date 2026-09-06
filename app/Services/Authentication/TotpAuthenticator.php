<?php

namespace App\Services\Authentication;

use Carbon\CarbonImmutable;
use OTPHP\Exception\OTPExceptionInterface;
use OTPHP\InternalClock;
use OTPHP\TOTP;

final readonly class TotpAuthenticator
{
    public function __construct(
        private ?string $secret,
        private int $period = 30,
        private int $digits = 6,
        private string $digest = 'sha1',
        private int $driftPeriods = 1,
    ) {}

    public function verify(string $code): bool
    {
        if (! preg_match('/^\d{'.$this->digits.'}$/', $code)
            || $this->secret === null
            || trim($this->secret) === '') {
            return false;
        }

        try {
            $totp = TOTP::createFromSecret($this->secret, new InternalClock)
                ->withPeriod($this->period)
                ->withDigits($this->digits)
                ->withDigest($this->digest);
            $timestamp = CarbonImmutable::now()->getTimestamp();

            for ($offset = -$this->driftPeriods; $offset <= $this->driftPeriods; $offset++) {
                $candidateTimestamp = $timestamp + ($offset * $this->period);

                if ($candidateTimestamp >= 0 && $totp->verify($code, $candidateTimestamp)) {
                    return true;
                }
            }
        } catch (OTPExceptionInterface) {
            return false;
        }

        return false;
    }
}

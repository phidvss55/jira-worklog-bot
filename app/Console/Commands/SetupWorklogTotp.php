<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use OTPHP\InternalClock;
use OTPHP\TOTP;

final class SetupWorklogTotp extends Command
{
    protected $signature = 'worklog:totp-setup';

    protected $description = 'Generate a TOTP secret and provisioning URI for worklog authentication';

    public function handle(): int
    {
        $totp = TOTP::generate(new InternalClock, 20)
            ->withPeriod(30)
            ->withDigits(6)
            ->withDigest('sha1')
            ->withLabel('Jira Worklog Bot');

        $this->components->info('TOTP setup generated locally. Nothing was saved or sent externally.');
        $this->line('Secret: '.$totp->getSecret());
        $this->line('Provisioning URI: '.$totp->getProvisioningUri());

        $this->newLine();
        $this->components->warn('Add the secret to Render as WORKLOG_TOTP_SECRET, then store it securely.');

        return self::SUCCESS;
    }
}

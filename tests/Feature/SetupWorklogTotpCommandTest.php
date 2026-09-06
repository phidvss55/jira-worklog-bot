<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

final class SetupWorklogTotpCommandTest extends TestCase
{
    public function test_command_generates_a_secret_and_google_authenticator_provisioning_uri(): void
    {
        $this->assertSame(0, Artisan::call('worklog:totp-setup'));

        $output = Artisan::output();

        $this->assertMatchesRegularExpression('/Secret: [A-Z2-7]+/', $output);
        $this->assertStringContainsString(
            'Provisioning URI: otpauth://totp/Jira%20Worklog%20Bot?secret=',
            $output,
        );
        $this->assertStringContainsString('WORKLOG_TOTP_SECRET', $output);
    }
}

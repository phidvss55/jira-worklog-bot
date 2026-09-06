<?php

namespace App\Console\Commands;

use App\Services\GoogleChat\GoogleChatWorklogNotifier;
use Illuminate\Console\Command;

final class TestGoogleChatWebhook extends Command
{
    protected $signature = 'google-chat:test';

    protected $description = 'Send a safe test message through the configured Google Chat webhook';

    public function handle(GoogleChatWorklogNotifier $notifier): int
    {
        if (! $notifier->sendTestNotification()) {
            $this->components->error('Google Chat notification failed. Check the configuration and application logs.');

            return self::FAILURE;
        }

        $this->components->info('Google Chat notification sent successfully.');

        return self::SUCCESS;
    }
}

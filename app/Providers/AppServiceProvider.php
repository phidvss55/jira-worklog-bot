<?php

namespace App\Providers;

use App\Application\Worklog\WorklogNotifier;
use App\Services\Authentication\TotpAuthenticator;
use App\Services\GoogleChat\GoogleChatWorklogNotifier;
use App\Services\Jira\JiraClient;
use App\Services\Jira\JiraCloudClient;
use App\Support\WorklogDateParser;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            JiraClient::class,
            fn (): JiraClient => new JiraCloudClient(
                http: $this->app->make(Factory::class),
                baseUrl: (string) config('services.jira.base_url'),
                email: (string) config('services.jira.email'),
                apiToken: (string) config('services.jira.api_token'),
                timeoutSeconds: (int) config('services.jira.timeout'),
            ),
        );

        $this->app->singleton(
            GoogleChatWorklogNotifier::class,
            fn (): GoogleChatWorklogNotifier => new GoogleChatWorklogNotifier(
                http: $this->app->make(Factory::class),
                logger: $this->app->make(LoggerInterface::class),
                webhookUrl: is_string(config('services.google_chat.webhook_url'))
                    ? config('services.google_chat.webhook_url')
                    : null,
                timezone: (string) config('app.timezone'),
                timeoutSeconds: (int) config('services.google_chat.timeout'),
            ),
        );

        $this->app->alias(GoogleChatWorklogNotifier::class, WorklogNotifier::class);

        $this->app->singleton(
            TotpAuthenticator::class,
            fn (): TotpAuthenticator => new TotpAuthenticator(
                secret: is_string(config('worklog.auth.totp_secret'))
                    ? config('worklog.auth.totp_secret')
                    : null,
                period: (int) config('worklog.auth.totp_period'),
                digits: (int) config('worklog.auth.totp_digits'),
                digest: (string) config('worklog.auth.totp_digest'),
                driftPeriods: (int) config('worklog.auth.totp_drift_periods'),
            ),
        );

        $this->app->bind(
            WorklogDateParser::class,
            fn (): WorklogDateParser => new WorklogDateParser((string) config('app.timezone')),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

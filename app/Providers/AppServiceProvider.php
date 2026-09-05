<?php

namespace App\Providers;

use App\Services\Jira\JiraClient;
use App\Services\Jira\JiraCloudClient;
use App\Support\WorklogDateParser;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\ServiceProvider;

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

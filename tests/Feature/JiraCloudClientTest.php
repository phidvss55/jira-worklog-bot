<?php

namespace Tests\Feature;

use App\Services\Jira\JiraClient;
use App\Services\Jira\JiraClientException;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class JiraCloudClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.jira.base_url' => 'https://jira.example.test/',
            'services.jira.email' => 'developer@example.test',
            'services.jira.api_token' => 'api-token',
            'services.jira.timeout' => 3,
        ]);

        Http::preventStrayRequests();
    }

    public function test_it_creates_a_worklog_with_the_expected_url_payload_and_authentication(): void
    {
        Http::fake([
            'jira.example.test/rest/api/3/issue/BKM4-1234/worklog' => Http::response([], 201),
        ]);

        $this->client()->logWork(
            'BKM4-1234',
            8100,
            CarbonImmutable::parse('2026-09-05T14:30:00+07:00'),
        );

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://jira.example.test/rest/api/3/issue/BKM4-1234/worklog'
                && $request->hasHeader('Authorization', 'Basic '.base64_encode('developer@example.test:api-token'))
                && $request['timeSpentSeconds'] === 8100
                && $request['started'] === '2026-09-05T14:30:00.000+0700';
        });
    }

    #[DataProvider('jiraFailures')]
    public function test_it_maps_jira_http_failures_to_safe_exceptions(int $status, string $expectedMessage): void
    {
        Http::fake([
            '*' => Http::response([
                'errorMessages' => ['Upstream details must not escape.'],
                'apiToken' => 'sensitive-value',
            ], $status),
        ]);

        try {
            $this->client()->logWork(
                'BKM4-1234',
                3600,
                CarbonImmutable::parse('2026-09-05T14:30:00+07:00'),
            );
            $this->fail('A JiraClientException was not thrown.');
        } catch (JiraClientException $exception) {
            $this->assertSame($expectedMessage, $exception->getMessage());
            $this->assertStringNotContainsString('Upstream details', $exception->getMessage());
            $this->assertStringNotContainsString('sensitive-value', $exception->getMessage());
        }
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function jiraFailures(): array
    {
        return [
            'validation failure' => [400, 'Jira rejected the request as invalid.'],
            'authentication failure' => [401, 'Jira authentication failed.'],
            'permission failure' => [403, 'Jira denied permission for the request.'],
            'issue not found' => [404, 'The Jira issue or resource was not found or is inaccessible.'],
            'rate limiting' => [429, 'Jira rate limited the request. Try again later.'],
            'server failure' => [503, 'Jira is temporarily unavailable.'],
        ];
    }

    public function test_it_maps_connection_and_timeout_failures_to_a_safe_exception(): void
    {
        Http::fake(Http::failedConnection('Operation timed out while using a sensitive URL.'));

        $this->expectException(JiraClientException::class);
        $this->expectExceptionMessage('Unable to connect to Jira.');

        $this->client()->logWork(
            'BKM4-1234',
            3600,
            CarbonImmutable::parse('2026-09-05T14:30:00+07:00'),
        );
    }

    public function test_it_fails_before_http_when_configuration_is_incomplete(): void
    {
        config(['services.jira.api_token' => null]);

        try {
            $this->client()->checkConnection();
            $this->fail('A JiraClientException was not thrown.');
        } catch (JiraClientException $exception) {
            $this->assertSame('Jira configuration is incomplete.', $exception->getMessage());
        }

        Http::assertNothingSent();
    }

    public function test_connectivity_check_uses_only_the_read_only_myself_endpoint(): void
    {
        Http::fake([
            'jira.example.test/rest/api/3/myself' => Http::response(['displayName' => 'Example User']),
        ]);

        $this->client()->checkConnection();

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://jira.example.test/rest/api/3/myself');
    }

    private function client(): JiraClient
    {
        return $this->app->make(JiraClient::class);
    }
}

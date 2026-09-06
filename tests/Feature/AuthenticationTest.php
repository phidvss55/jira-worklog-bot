<?php

namespace Tests\Feature;

use App\Application\Worklog\WorklogNotifier;
use App\Http\Middleware\EnsureWorklogAuthenticated;
use App\Services\Authentication\TotpAuthenticator;
use App\Services\Jira\JiraClient;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Mockery;
use OTPHP\InternalClock;
use OTPHP\TOTP;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class AuthenticationTest extends TestCase
{
    private string $totpSecret;

    private string $validCode;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-09-06T15:30:15+07:00');
        $totp = TOTP::generate(new InternalClock, 20);
        $this->totpSecret = $totp->getSecret();
        $this->validCode = $totp->at(CarbonImmutable::now()->getTimestamp());
        config(['worklog.auth.totp_secret' => $this->totpSecret]);
        $this->app->make(RateLimiter::class)->clear('worklog-login:127.0.0.1');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_guest_cannot_access_the_worklog_ui(): void
    {
        $this->get('/')
            ->assertRedirect('/login')
            ->assertDontSee('id="app"', false);
    }

    public function test_login_page_is_public(): void
    {
        $this->withoutVite();

        $this->get('/login')
            ->assertOk()
            ->assertSee('Jira Worklog')
            ->assertSee('name="code"', false)
            ->assertSee('inputmode="numeric"', false)
            ->assertSee('autocomplete="one-time-code"', false)
            ->assertSee('name="_token"', false)
            ->assertDontSee($this->totpSecret);
    }

    public function test_valid_totp_regenerates_and_authenticates_the_session(): void
    {
        $session = $this->app->make('session.store');
        $session->setId(Str::random(40));
        $originalSessionId = $session->getId();

        $this->post('/login', ['code' => $this->validCode])
            ->assertRedirect('/')
            ->assertSessionHas(EnsureWorklogAuthenticated::SESSION_KEY, true);

        $this->assertNotSame($originalSessionId, $session->getId());
        $this->assertSame(
            [EnsureWorklogAuthenticated::SESSION_KEY => true],
            $session->only([EnsureWorklogAuthenticated::SESSION_KEY]),
        );
        $sessionPayload = json_encode($session->all(), JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString($this->validCode, $sessionPayload);
        $this->assertStringNotContainsString($this->totpSecret, $sessionPayload);
    }

    public function test_invalid_totp_fails_with_a_generic_message(): void
    {
        $this->from('/login')
            ->post('/login', ['code' => $this->invalidCode()])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['code' => 'Invalid authentication code.'])
            ->assertSessionMissing(EnsureWorklogAuthenticated::SESSION_KEY);
    }

    #[DataProvider('malformedCodes')]
    public function test_malformed_code_is_rejected(string $code): void
    {
        $this->from('/login')
            ->post('/login', ['code' => $code])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['code' => 'Invalid authentication code.'])
            ->assertSessionMissing('_old_input.code')
            ->assertSessionMissing(EnsureWorklogAuthenticated::SESSION_KEY);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function malformedCodes(): array
    {
        return [
            'too short' => ['12345'],
            'too long' => ['1234567'],
            'not numeric' => ['12ab56'],
        ];
    }

    public function test_missing_or_invalid_totp_secret_fails_closed(): void
    {
        foreach ([null, 'not-valid-base32!'] as $secret) {
            config(['worklog.auth.totp_secret' => $secret]);
            $this->app->forgetInstance(TotpAuthenticator::class);

            $this->from('/login')
                ->post('/login', ['code' => '123456'])
                ->assertRedirect('/login')
                ->assertSessionHasErrors(['code' => 'Invalid authentication code.'])
                ->assertSessionMissing(EnsureWorklogAuthenticated::SESSION_KEY);
        }
    }

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        $invalidCode = $this->invalidCode();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['code' => $invalidCode])->assertRedirect();
        }

        $this->post('/login', ['code' => $this->validCode])
            ->assertStatus(429)
            ->assertSee('Too many login attempts. Try again in one minute.')
            ->assertDontSee($this->totpSecret)
            ->assertSessionMissing(EnsureWorklogAuthenticated::SESSION_KEY);
    }

    public function test_authenticated_user_can_access_the_worklog_ui(): void
    {
        $this->withoutVite();

        $this->withSession([EnsureWorklogAuthenticated::SESSION_KEY => true])
            ->get('/')
            ->assertOk()
            ->assertSee('id="app"', false);
    }

    public function test_guest_worklog_request_is_rejected_before_jira_or_google_chat(): void
    {
        Http::preventStrayRequests();

        $jiraClient = Mockery::mock(JiraClient::class);
        $jiraClient->shouldNotReceive('logWork');
        $this->app->instance(JiraClient::class, $jiraClient);

        $notifier = Mockery::mock(WorklogNotifier::class);
        $notifier->shouldNotReceive('notify');
        $this->app->instance(WorklogNotifier::class, $notifier);

        $this->postJson('/api/worklogs', $this->validWorklogPayload())
            ->assertUnauthorized()
            ->assertExactJson([
                'success' => false,
                'message' => 'Authentication required.',
            ]);

        Http::assertNothingSent();
    }

    public function test_authenticated_worklog_request_preserves_existing_behavior(): void
    {
        $jiraClient = Mockery::mock(JiraClient::class);
        $jiraClient->shouldReceive('logWork')->once();
        $this->app->instance(JiraClient::class, $jiraClient);

        $notifier = Mockery::mock(WorklogNotifier::class);
        $notifier->shouldReceive('notify')->once()->andReturn(true);
        $this->app->instance(WorklogNotifier::class, $notifier);

        $this->withSession([EnsureWorklogAuthenticated::SESSION_KEY => true])
            ->postJson('/api/worklogs', $this->validWorklogPayload())
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('notificationSent', true);
    }

    public function test_logout_invalidates_authentication_and_regenerates_the_csrf_token(): void
    {
        $session = $this->app->make('session.store');
        $this->withSession([EnsureWorklogAuthenticated::SESSION_KEY => true]);
        $originalToken = $session->token();

        $this->post('/logout')
            ->assertRedirect('/login')
            ->assertSessionMissing(EnsureWorklogAuthenticated::SESSION_KEY);

        $this->assertNotSame($originalToken, $session->token());

        $this->get('/')->assertRedirect('/login');
        $this->postJson('/api/worklogs', $this->validWorklogPayload())->assertUnauthorized();
    }

    public function test_worklog_api_remains_protected_by_csrf(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        $jiraClient = Mockery::mock(JiraClient::class);
        $jiraClient->shouldNotReceive('logWork');
        $this->app->instance(JiraClient::class, $jiraClient);

        $notifier = Mockery::mock(WorklogNotifier::class);
        $notifier->shouldNotReceive('notify');
        $this->app->instance(WorklogNotifier::class, $notifier);

        $this->withSession([EnsureWorklogAuthenticated::SESSION_KEY => true])
            ->postJson('/api/worklogs', $this->validWorklogPayload())
            ->assertStatus(419);
    }

    public function test_health_endpoint_remains_public(): void
    {
        $this->get('/health')->assertOk();
    }

    private function invalidCode(): string
    {
        $authenticator = $this->app->make(TotpAuthenticator::class);

        for ($value = 0; $value <= 999999; $value++) {
            $code = sprintf('%06d', $value);

            if (! $authenticator->verify($code)) {
                return $code;
            }
        }

        $this->fail('Unable to find an invalid test TOTP code.');
    }

    /**
     * @return array<string, string>
     */
    private function validWorklogPayload(): array
    {
        return [
            'ticket' => 'BKM4-1234',
            'duration' => '30m',
            'date' => '05/09/2026',
            'time' => '14:30',
        ];
    }
}

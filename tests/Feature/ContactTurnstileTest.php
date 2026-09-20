<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ContactTurnstileTest extends TestCase
{
    use RefreshDatabase;

    private const SITE_KEY = 'test-site-key-public';
    private const SECRET = 'test-secret-key-DO-NOT-LEAK';
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.turnstile.site_key' => self::SITE_KEY,
            'services.turnstile.secret_key' => self::SECRET,
        ]);
        RateLimiter::clear('contact-min|127.0.0.1');
        RateLimiter::clear('contact-hour|127.0.0.1');
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Fake Sender',
            'email' => 'fake.sender@example.test',
            'subject' => 'Hello',
            'message' => 'This is a test message.',
            'cf-turnstile-response' => 'XXXX.DUMMY.TOKEN',
        ], $overrides);
    }

    private function fakeCloudflare(bool $success = true): void
    {
        Http::fake([
            self::VERIFY_URL => Http::response(
                $success ? ['success' => true] : ['success' => false, 'error-codes' => ['invalid-input-response']],
                200
            ),
        ]);
    }

    public function test_contact_page_loads_with_site_key_and_widget_but_never_the_secret(): void
    {
        $html = $this->get('/contact')->assertOk()->getContent();

        $this->assertStringContainsString('data-sitekey="'.self::SITE_KEY.'"', $html);
        $this->assertStringContainsString('https://challenges.cloudflare.com/turnstile/v0/api.js', $html);
        $this->assertStringContainsString('name="_token"', $html);
        $this->assertStringNotContainsString(self::SECRET, $html);
    }

    public function test_valid_submission_with_successful_verification_creates_message(): void
    {
        $this->fakeCloudflare(true);

        $this->post('/contact', $this->payload())
            ->assertRedirect(route('contact').'#contact-form')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('contact_messages', ['email' => 'fake.sender@example.test', 'subject' => 'Hello']);

        Http::assertSent(function (HttpRequest $r) {
            return $r->url() === self::VERIFY_URL
                && $r['secret'] === self::SECRET
                && $r['response'] === 'XXXX.DUMMY.TOKEN';
        });
    }

    public function test_missing_token_fails_without_calling_cloudflare_or_saving(): void
    {
        Http::fake();

        $data = $this->payload();
        unset($data['cf-turnstile-response']);

        $this->from('/contact')->post('/contact', $data)
            ->assertRedirect('/contact')
            ->assertSessionHasErrors('cf-turnstile-response');

        Http::assertNothingSent();
        $this->assertSame(0, ContactMessage::count());
    }

    public function test_invalid_token_fails_and_creates_no_message(): void
    {
        $this->fakeCloudflare(false);

        $this->from('/contact')->post('/contact', $this->payload(['cf-turnstile-response' => 'fake-token']))
            ->assertRedirect('/contact')
            ->assertSessionHasErrors(['captcha' => 'CAPTCHA verification failed. Please try again.'])
            ->assertSessionHasInput('email', 'fake.sender@example.test');

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_cloudflare_network_failure_fails_closed(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $this->from('/contact')->post('/contact', $this->payload())
            ->assertRedirect('/contact')
            ->assertSessionHasErrors('captcha');

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_cloudflare_server_error_fails_closed(): void
    {
        Http::fake([self::VERIFY_URL => Http::response('oops', 500)]);

        $this->post('/contact', $this->payload())->assertSessionHasErrors('captcha');

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_missing_secret_config_fails_closed_and_never_bypasses(): void
    {
        config(['services.turnstile.secret_key' => null]);
        Http::fake();

        $this->post('/contact', $this->payload())->assertSessionHasErrors('captcha');

        Http::assertNothingSent();
        $this->assertSame(0, ContactMessage::count());
    }

    public function test_honeypot_rejects_submission_even_with_valid_captcha(): void
    {
        $this->fakeCloudflare(true);

        $this->from('/contact')->post('/contact', $this->payload(['website' => 'http://spam.example']))
            ->assertRedirect('/contact')
            ->assertSessionHasErrors('captcha');

        Http::assertNothingSent();
        $this->assertSame(0, ContactMessage::count());
    }

    public function test_field_validation_still_works(): void
    {
        $this->fakeCloudflare(true);

        $this->from('/contact')->post('/contact', $this->payload(['email' => 'not-an-email']))
            ->assertSessionHasErrors('email');
        $this->from('/contact')->post('/contact', $this->payload(['name' => '']))
            ->assertSessionHasErrors('name');

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_csrf_protection_is_enforced_outside_the_testing_environment(): void
    {
        $this->fakeCloudflare(true);
        $this->app['env'] = 'production';

        $this->post('/contact', $this->payload())->assertStatus(419);

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_contact_post_is_rate_limited_and_page_view_is_not(): void
    {
        $this->fakeCloudflare(false);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/contact', $this->payload())->assertStatus(302);
        }
        $this->post('/contact', $this->payload())->assertStatus(429);

        $this->get('/contact')->assertOk();
        $this->assertSame(0, ContactMessage::count());
    }

    public function test_only_contact_post_uses_the_contact_limiter(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertContains('throttle:contact', $routes->getByName('contact.store')->gatherMiddleware());
        $this->assertNotContains('throttle:contact', $routes->getByName('contact')->gatherMiddleware());
    }
}

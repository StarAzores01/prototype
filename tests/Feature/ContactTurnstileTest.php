<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\Notification;
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
    private const HUMAN_CHECK_FAILED = 'Please answer the human verification question correctly.';

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

    /**
     * Loads the contact form (as a real user would) so the session gets a
     * fresh human-verification challenge and render timestamp, then backdates
     * the render timestamp so the minimum-completion-time check is satisfied
     * without an actual sleep(). Returns the correct answer for that challenge.
     */
    private function primeHumanChallenge(int $ageSeconds = 5): int
    {
        $this->get('/contact')->assertOk();

        return $this->backdateCurrentChallenge($ageSeconds);
    }

    /**
     * Reads whatever human-verification challenge is CURRENTLY in the session
     * (e.g. one left behind by a prior failed POST) without issuing a new GET,
     * and backdates its render timestamp so it can be used immediately. This
     * is how the tests prove a challenge left over after a failure is real and
     * reusable, rather than just asserting it "looks" regenerated.
     */
    private function backdateCurrentChallenge(int $ageSeconds = 5): int
    {
        $answer = session('contact_human_answer');
        $this->assertIsInt($answer, 'Expected a valid human-verification challenge to already exist in the session.');

        session(['contact_form_rendered_at' => now()->subSeconds($ageSeconds)->timestamp]);

        return $answer;
    }

    private function validPayload(array $overrides = []): array
    {
        $answer = $this->primeHumanChallenge();

        return $this->payload(array_merge(['human_answer' => $answer], $overrides));
    }

    // ------------------------------------------------------------------
    // GET /contact
    // ------------------------------------------------------------------

    public function test_contact_page_loads_with_site_key_and_widget_but_never_the_secret(): void
    {
        $html = $this->get('/contact')->assertOk()->getContent();

        $this->assertStringContainsString('data-sitekey="'.self::SITE_KEY.'"', $html);
        $this->assertStringContainsString('https://challenges.cloudflare.com/turnstile/v0/api.js', $html);
        $this->assertStringContainsString('name="_token"', $html);
        $this->assertStringNotContainsString(self::SECRET, $html);
    }

    public function test_contact_page_shows_arithmetic_question_without_exposing_the_answer(): void
    {
        $html = $this->get('/contact')->assertOk()->getContent();

        $question = session('contact_human_question');
        $answer = session('contact_human_answer');

        $this->assertNotEmpty($question);
        $this->assertIsInt($answer);

        // The question and the input are visible to the user.
        $this->assertStringContainsString($question, $html);
        $this->assertStringContainsString('name="human_answer"', $html);
        $this->assertStringContainsString('Human Verification', $html);

        // No <input> tag anywhere on the page (hidden or otherwise) carries the
        // expected answer as its value. A blanket "the digit never appears in
        // the HTML" check is unsound: a small integer like 4 legitimately
        // appears elsewhere in the page's CSS/markup, so we assert the actual
        // security property instead — no input exposes it as a value.
        preg_match_all('/<input\b[^>]*>/i', $html, $inputTags);
        foreach ($inputTags[0] as $tag) {
            $this->assertDoesNotMatchRegularExpression(
                '/value=["\']\s*'.preg_quote((string) $answer, '/').'\s*["\']/',
                $tag,
                "An <input> tag exposes the expected human-verification answer: {$tag}"
            );
        }

        // The human_answer field itself must not be pre-filled with the answer.
        preg_match('/<input\b[^>]*name="human_answer"[^>]*>/i', $html, $matches);
        $this->assertNotEmpty($matches, 'Could not locate the human_answer input tag.');
        $this->assertDoesNotMatchRegularExpression(
            '/value=["\']\s*'.preg_quote((string) $answer, '/').'\s*["\']/',
            $matches[0]
        );

        // The session variable name itself must never leak into the page
        // (e.g. into an HTML comment or inline script).
        $this->assertStringNotContainsString('contact_human_answer', $html);

        // Turnstile secret must never be present.
        $this->assertStringNotContainsString(self::SECRET, $html);
    }

    // ------------------------------------------------------------------
    // Happy path
    // ------------------------------------------------------------------

    public function test_valid_submission_with_successful_verification_creates_message(): void
    {
        $this->fakeCloudflare(true);

        $this->post('/contact', $this->validPayload())
            ->assertRedirect(route('contact').'#contact-form')
            ->assertSessionHas('success');

        $this->assertSame(1, ContactMessage::where('email', 'fake.sender@example.test')->where('subject', 'Hello')->count());

        Http::assertSent(function (HttpRequest $r) {
            return $r->url() === self::VERIFY_URL
                && $r['secret'] === self::SECRET
                && $r['response'] === 'XXXX.DUMMY.TOKEN';
        });
    }

    // ------------------------------------------------------------------
    // Arithmetic human-verification challenge
    // ------------------------------------------------------------------

    public function test_wrong_arithmetic_answer_fails_without_calling_cloudflare(): void
    {
        $answer = $this->primeHumanChallenge();
        Http::fake();

        $this->from('/contact')->post('/contact', $this->payload(['human_answer' => $answer + 1]))
            ->assertRedirect('/contact')
            ->assertSessionHasErrors(['human_answer' => self::HUMAN_CHECK_FAILED])
            ->assertSessionHasInput('email', 'fake.sender@example.test');

        Http::assertNothingSent();
        $this->assertSame(0, ContactMessage::count());
    }

    public function test_missing_arithmetic_answer_fails_without_calling_cloudflare(): void
    {
        $this->primeHumanChallenge();
        Http::fake();

        $data = $this->payload();
        unset($data['human_answer']);

        $this->from('/contact')->post('/contact', $data)
            ->assertRedirect('/contact')
            ->assertSessionHasErrors(['human_answer' => self::HUMAN_CHECK_FAILED]);

        Http::assertNothingSent();
        $this->assertSame(0, ContactMessage::count());
    }

    public function test_non_numeric_human_answer_fails_without_calling_cloudflare(): void
    {
        $this->primeHumanChallenge();
        Http::fake();

        $this->from('/contact')->post('/contact', $this->payload(['human_answer' => 'not-a-number']))
            ->assertRedirect('/contact')
            ->assertSessionHasErrors(['human_answer' => self::HUMAN_CHECK_FAILED]);

        Http::assertNothingSent();
        $this->assertSame(0, ContactMessage::count());
    }

    public function test_missing_session_challenge_fails_closed(): void
    {
        // No prior GET /contact, so the session has no challenge at all.
        Http::fake();

        $this->from('/contact')->post('/contact', $this->payload(['human_answer' => 7]))
            ->assertRedirect('/contact')
            ->assertSessionHasErrors(['human_answer' => self::HUMAN_CHECK_FAILED]);

        Http::assertNothingSent();
        $this->assertSame(0, ContactMessage::count());
    }

    public function test_human_challenge_is_regenerated_after_a_failed_attempt(): void
    {
        // Registered once, up front, for the whole test — see the other
        // "fresh challenge" tests for why a second Http::fake() call later
        // would not correctly replace this one.
        $this->fakeCloudflare(true);

        // 1. GET /contact establishes a valid challenge/session state.
        $this->get('/contact')->assertOk();
        $firstAnswer = session('contact_human_answer');
        $this->assertIsInt($firstAnswer);
        $this->assertIsInt(session('contact_form_rendered_at'));

        // Backdated to an arbitrary point in the past. The exact value only
        // matters as a fixed reference point for step 3 below — it does not
        // encode anything about the (random) answer itself.
        $backdatedTimestamp = now()->subSeconds(5)->timestamp;
        session(['contact_form_rendered_at' => $backdatedTimestamp]);

        // 2. A failed POST consumes/replaces the challenge. firstAnswer + 1 is
        // guaranteed wrong for the challenge this request is evaluated
        // against, since correctness is checked against the session value
        // that is still firstAnswer at this point (nothing has regenerated
        // it yet). This is deterministic regardless of what the *next*
        // random challenge happens to be.
        $this->post('/contact', $this->payload(['human_answer' => $firstAnswer + 1]))
            ->assertSessionHasErrors('human_answer');

        Http::assertNothingSent();
        $this->assertSame(0, ContactMessage::count());

        // 3. After the failed POST, session contains a valid, freshly issued
        // challenge and render timestamp. Operands are 1-9, so the new sum
        // can legitimately equal the old one (e.g. 2+3 and 1+4 both give 5) —
        // asserting the numeric answer differs would be flaky, so we don't.
        // Instead we assert the timestamp was genuinely regenerated:
        // issueHumanChallenge() always stamps the real current time, which
        // must be later than the fixed backdated value set above regardless
        // of how fast the test runs.
        $secondAnswer = session('contact_human_answer');
        $secondRenderedAt = session('contact_form_rendered_at');

        $this->assertIsInt($secondAnswer);
        $this->assertIsInt($secondRenderedAt);
        $this->assertGreaterThan($backdatedTimestamp, $secondRenderedAt);

        // 4. The challenge produced after the failure can be backdated and
        // used successfully in a subsequent valid submission.
        session(['contact_form_rendered_at' => now()->subSeconds(5)->timestamp]);

        $this->post('/contact', $this->payload(['human_answer' => $secondAnswer]))
            ->assertSessionHas('success');

        // 5. ContactMessage count remains correct.
        $this->assertSame(1, ContactMessage::count());

        // 6. Cloudflare was not called for the initial human-verification
        // failure (already proven above via assertNothingSent), but was
        // called for the final valid submission.
        Http::assertSent(fn (HttpRequest $r) => $r->url() === self::VERIFY_URL);
    }

    // ------------------------------------------------------------------
    // Minimum form-completion time
    // ------------------------------------------------------------------

    public function test_too_fast_submission_fails_without_calling_cloudflare(): void
    {
        $this->get('/contact')->assertOk();
        $answer = session('contact_human_answer');
        // Deliberately leave contact_form_rendered_at at "now" (no backdating).
        Http::fake();

        $this->from('/contact')->post('/contact', $this->payload(['human_answer' => $answer]))
            ->assertRedirect('/contact')
            ->assertSessionHasErrors(['form_timing' => 'Please wait a moment before submitting the form.']);

        Http::assertNothingSent();
        $this->assertSame(0, ContactMessage::count());
    }

    // ------------------------------------------------------------------
    // Cloudflare Turnstile (existing coverage, adapted to prime the challenge)
    // ------------------------------------------------------------------

    public function test_missing_token_fails_without_calling_cloudflare_or_saving(): void
    {
        Http::fake();

        $data = $this->validPayload();
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

        $this->from('/contact')->post('/contact', $this->validPayload(['cf-turnstile-response' => 'fake-token']))
            ->assertRedirect('/contact')
            ->assertSessionHasErrors(['captcha' => 'CAPTCHA verification failed. Please try again.'])
            ->assertSessionHasInput('email', 'fake.sender@example.test');

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_turnstile_failure_leaves_a_fresh_challenge_available(): void
    {
        // A single fake registered up front, serving two responses in order
        // for the same endpoint. Calling Http::fake()/fakeCloudflare() a
        // second time later in a test does NOT replace the first stub — Laravel
        // appends new stubs and resolves the FIRST matching one for a given
        // request, so the original fake would keep intercepting the second
        // call. A sequence avoids that stacking pitfall entirely.
        Http::fake([
            self::VERIFY_URL => Http::sequence()
                ->push(['success' => false, 'error-codes' => ['invalid-input-response']], 200)
                ->push(['success' => true], 200),
        ]);

        $this->post('/contact', $this->validPayload())
            ->assertSessionHasErrors('captcha');
        $this->assertSame(0, ContactMessage::count());

        // The challenge was already consumed/regenerated while checking the
        // (correct) human answer earlier in that same request. Prove the
        // leftover challenge is real and usable for the next attempt.
        $answer = $this->backdateCurrentChallenge();

        $this->post('/contact', $this->payload(['human_answer' => $answer]))
            ->assertSessionHas('success');
        $this->assertSame(1, ContactMessage::count());
    }

    public function test_cloudflare_network_failure_fails_closed(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $this->from('/contact')->post('/contact', $this->validPayload())
            ->assertRedirect('/contact')
            ->assertSessionHasErrors('captcha');

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_cloudflare_server_error_fails_closed(): void
    {
        Http::fake([self::VERIFY_URL => Http::response('oops', 500)]);

        $this->post('/contact', $this->validPayload())->assertSessionHasErrors('captcha');

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_missing_secret_config_fails_closed_and_never_bypasses(): void
    {
        config(['services.turnstile.secret_key' => null]);
        $data = $this->validPayload();
        Http::fake();

        $this->post('/contact', $data)->assertSessionHasErrors('captcha');

        Http::assertNothingSent();
        $this->assertSame(0, ContactMessage::count());
    }

    // ------------------------------------------------------------------
    // Honeypot
    // ------------------------------------------------------------------

    public function test_honeypot_rejects_submission_even_with_valid_captcha(): void
    {
        $this->fakeCloudflare(true);

        $this->from('/contact')->post('/contact', $this->validPayload(['website' => 'http://spam.example']))
            ->assertRedirect('/contact')
            ->assertSessionHasErrors('captcha');

        Http::assertNothingSent();
        $this->assertSame(0, ContactMessage::count());
    }

    public function test_honeypot_failure_leaves_a_fresh_usable_challenge(): void
    {
        // Registered once, up front, for the whole test. Honeypot rejection
        // happens before any Turnstile call, so Http::assertNothingSent()
        // below still proves nothing was dispatched in that phase; the same
        // fake then correctly serves the second (legitimate) Turnstile call.
        // Calling Http::fake() again later would NOT replace this stub — see
        // test_turnstile_failure_leaves_a_fresh_challenge_available for why.
        $this->fakeCloudflare(true);

        $this->from('/contact')->post('/contact', $this->validPayload(['website' => 'http://spam.example']))
            ->assertSessionHasErrors('captcha');

        Http::assertNothingSent();
        $this->assertSame(0, ContactMessage::count());

        // A fresh challenge must exist and be usable for a subsequent honest attempt.
        $answer = $this->backdateCurrentChallenge();

        $this->post('/contact', $this->payload(['human_answer' => $answer]))
            ->assertSessionHas('success');
        $this->assertSame(1, ContactMessage::count());
    }

    // ------------------------------------------------------------------
    // Field preservation (never leak human_answer / website / cf-turnstile-response)
    // ------------------------------------------------------------------

    public function test_failed_submissions_never_preserve_sensitive_fields(): void
    {
        $answer = $this->primeHumanChallenge();

        $this->post('/contact', $this->payload([
            'human_answer' => $answer,
            'website' => 'http://spam.example',
        ]));

        $oldInput = session('_old_input', []);

        $this->assertArrayHasKey('name', $oldInput);
        $this->assertArrayHasKey('email', $oldInput);
        $this->assertArrayHasKey('subject', $oldInput);
        $this->assertArrayHasKey('message', $oldInput);
        $this->assertArrayNotHasKey('website', $oldInput);
        $this->assertArrayNotHasKey('human_answer', $oldInput);
        $this->assertArrayNotHasKey('cf-turnstile-response', $oldInput);
    }

    // ------------------------------------------------------------------
    // Field validation / CSRF (existing coverage, unaffected by ordering)
    // ------------------------------------------------------------------

    public function test_field_validation_still_works(): void
    {
        $this->fakeCloudflare(true);

        $this->from('/contact')->post('/contact', $this->payload(['email' => 'not-an-email']))
            ->assertSessionHasErrors('email');
        $this->from('/contact')->post('/contact', $this->payload(['name' => '']))
            ->assertSessionHasErrors('name');

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_normal_validation_failure_leaves_a_fresh_usable_challenge(): void
    {
        // Registered once, up front, for the whole test — see
        // test_turnstile_failure_leaves_a_fresh_challenge_available for why a
        // second Http::fake()/fakeCloudflare() call later would not work here.
        $this->fakeCloudflare(true);

        $this->from('/contact')->post('/contact', $this->payload(['email' => 'not-an-email']))
            ->assertSessionHasErrors('email');

        Http::assertNothingSent();
        $this->assertSame(0, ContactMessage::count());

        // A fresh challenge must exist and be usable for a subsequent valid attempt.
        $answer = $this->backdateCurrentChallenge();

        $this->post('/contact', $this->payload(['human_answer' => $answer]))
            ->assertSessionHas('success');
        $this->assertSame(1, ContactMessage::count());
    }

    public function test_csrf_protection_is_enforced_outside_the_testing_environment(): void
    {
        $this->fakeCloudflare(true);
        $this->app['env'] = 'production';

        $this->post('/contact', $this->payload())->assertStatus(419);

        $this->assertSame(0, ContactMessage::count());
    }

    // ------------------------------------------------------------------
    // Duplicate submission protection
    // ------------------------------------------------------------------

    public function test_duplicate_submission_within_ttl_is_rejected_without_extra_message_or_notification(): void
    {
        $this->fakeCloudflare(true);

        $this->post('/contact', $this->validPayload())->assertSessionHas('success');
        $this->assertSame(1, ContactMessage::count());
        $notificationsAfterFirst = Notification::count();

        // Same IP/email/subject/message again, well within the 5-minute TTL,
        // using a fresh (valid) human-verification challenge of its own.
        $this->post('/contact', $this->validPayload())
            ->assertSessionHasErrors(['duplicate' => "It looks like this message was already submitted recently. Please wait a few minutes before trying again."]);

        $this->assertSame(1, ContactMessage::count());
        $this->assertSame($notificationsAfterFirst, Notification::count());
    }

    public function test_duplicate_rejection_leaves_a_fresh_usable_challenge(): void
    {
        $this->fakeCloudflare(true);

        $this->post('/contact', $this->validPayload())->assertSessionHas('success');
        $this->assertSame(1, ContactMessage::count());

        $this->post('/contact', $this->validPayload())
            ->assertSessionHasErrors('duplicate');
        $this->assertSame(1, ContactMessage::count());

        // The duplicate rejection still leaves a fresh, usable challenge for a
        // legitimately different follow-up message.
        $answer = $this->backdateCurrentChallenge();

        $this->post('/contact', $this->payload([
            'human_answer' => $answer,
            'message' => 'A completely different follow-up message.',
        ]))->assertSessionHas('success');

        $this->assertSame(2, ContactMessage::count());
    }

    public function test_materially_different_message_is_not_blocked_as_duplicate(): void
    {
        $this->fakeCloudflare(true);

        $this->post('/contact', $this->validPayload())->assertSessionHas('success');
        $this->assertSame(1, ContactMessage::count());

        $this->post('/contact', $this->validPayload(['message' => 'A completely different follow-up message.']))
            ->assertSessionHas('success');

        $this->assertSame(2, ContactMessage::count());
    }

    // ------------------------------------------------------------------
    // Rate limiting (existing coverage, unaffected)
    // ------------------------------------------------------------------

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

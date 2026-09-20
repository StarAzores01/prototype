<?php

namespace Tests\Feature;

use App\Models\Beneficiary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Tmp-Fake-Pass-123!';

    private function staff(array $overrides = []): User
    {
        static $n = 0;
        $n++;

        return User::create(array_merge([
            'username' => "fake_staff_$n",
            'first_name' => 'Fake',
            'last_name' => 'Staff',
            'email' => "fake_staff_$n@example.test",
            'id_number' => "FAKE-$n",
            'position' => 'Instructor',
            'role' => 'trainer',
            'password_hash' => Hash::make(self::PASSWORD),
            'is_active' => true,
            'must_change_password' => false,
        ], $overrides));
    }

    private function beneficiary(array $overrides = []): Beneficiary
    {
        static $n = 0;
        $n++;

        return Beneficiary::create(array_merge([
            'username' => "fake_benef_$n",
            'first_name' => 'Fake',
            'last_name' => 'Benef',
            'email' => "fake_benef_$n@example.test",
            'password_hash' => Hash::make(self::PASSWORD),
            'is_active' => true,
        ], $overrides));
    }

    public function test_login_form_posts_login_and_password_fields(): void
    {
        $html = $this->get('/login')->assertOk()->getContent();

        $this->assertStringContainsString('name="login"', $html);
        $this->assertStringContainsString('name="password"', $html);
    }

    public function test_valid_staff_login_by_username_succeeds(): void
    {
        $user = $this->staff();

        $this->post('/login', ['login' => $user->username, 'password' => self::PASSWORD])
            ->assertRedirect(route('trainer.dashboard'));

        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_valid_staff_login_by_email_succeeds(): void
    {
        $user = $this->staff();

        $this->post('/login', ['login' => $user->email, 'password' => self::PASSWORD])
            ->assertRedirect(route('trainer.dashboard'));

        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_invalid_staff_password_fails(): void
    {
        $user = $this->staff();

        $this->from('/login')
            ->post('/login', ['login' => $user->username, 'password' => 'wrong-password'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('login');

        $this->assertGuest('web');
        $this->assertGuest('beneficiary');
    }

    public function test_inactive_staff_fails(): void
    {
        $user = $this->staff(['is_active' => false]);

        $this->from('/login')
            ->post('/login', ['login' => $user->username, 'password' => self::PASSWORD])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('login');

        $this->assertGuest('web');
    }

    public function test_valid_beneficiary_login_succeeds(): void
    {
        $b = $this->beneficiary();

        $this->post('/login', ['login' => $b->username, 'password' => self::PASSWORD])
            ->assertRedirect(route('beneficiary.home'));

        $this->assertAuthenticatedAs($b, 'beneficiary');
    }

    public function test_beneficiary_login_by_email_succeeds(): void
    {
        $b = $this->beneficiary();

        $this->post('/login', ['login' => $b->email, 'password' => self::PASSWORD])
            ->assertRedirect(route('beneficiary.home'));

        $this->assertAuthenticatedAs($b, 'beneficiary');
    }

    public function test_invalid_beneficiary_password_fails(): void
    {
        $b = $this->beneficiary();

        $this->from('/login')
            ->post('/login', ['login' => $b->username, 'password' => 'wrong-password'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('login');

        $this->assertGuest('beneficiary');
    }

    public function test_inactive_beneficiary_fails(): void
    {
        $b = $this->beneficiary(['is_active' => false]);

        $this->from('/login')
            ->post('/login', ['login' => $b->username, 'password' => self::PASSWORD])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('login');

        $this->assertGuest('beneficiary');
    }

    public function test_each_staff_role_redirects_to_its_dashboard(): void
    {
        $map = [
            'extension_coordinator' => 'ec.dashboard',
            'trainer' => 'trainer.dashboard',
            'evaluator' => 'evaluator.dashboard',
        ];

        foreach ($map as $role => $routeName) {
            $user = $this->staff(['role' => $role]);

            $this->post('/login', ['login' => $user->username, 'password' => self::PASSWORD])
                ->assertRedirect(route($routeName));

            $this->post('/logout');
            $this->flushSession();
        }
    }

    public function test_fake_credentials_cannot_authenticate(): void
    {
        $this->from('/login')
            ->post('/login', ['login' => 'no_such_user', 'password' => 'whatever123'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('login');

        $this->assertGuest('web');
        $this->assertGuest('beneficiary');
    }

    public function test_must_change_password_user_is_sent_to_force_change_after_login(): void
    {
        $user = $this->staff(['must_change_password' => true]);

        $this->post('/login', ['login' => $user->username, 'password' => self::PASSWORD]);
        $this->assertAuthenticatedAs($user, 'web');

        $this->get(route('trainer.dashboard'))->assertRedirect(route('password.force.form'));
    }

    public function test_user_without_must_change_password_is_not_forced_to_change(): void
    {
        $user = $this->staff(['must_change_password' => false]);

        $this->actingAs($user, 'web')->get(route('trainer.dashboard'))->assertOk();
    }

    public function test_web_guard_attempt_directly(): void
    {
        $user = $this->staff();
        $guard = auth()->guard('web');
        $cred = fn ($pw, $active = true) => ['username' => $user->username, 'password' => $pw, 'is_active' => $active];

        $this->assertFalse($guard->attempt($cred('wrong')));
        $this->assertFalse($guard->attempt($cred('')));
        $this->assertFalse($guard->attempt($cred(self::PASSWORD, false)));
        $this->assertGuest('web');
        $this->assertTrue($guard->attempt($cred(self::PASSWORD)));
        $this->assertAuthenticatedAs($user, 'web');

        $guard->logout();
        $user->update(['is_active' => false]);
        $this->assertFalse($guard->attempt($cred(self::PASSWORD)));
        $this->assertGuest('web');
    }

    public function test_beneficiary_guard_attempt_directly(): void
    {
        $b = $this->beneficiary();
        $guard = auth()->guard('beneficiary');
        $cred = fn ($pw) => ['username' => $b->username, 'password' => $pw, 'is_active' => true];

        $this->assertFalse($guard->attempt($cred('wrong')));
        $this->assertGuest('beneficiary');
        $this->assertTrue($guard->attempt($cred(self::PASSWORD)));

        $guard->logout();
        $b->update(['is_active' => false]);
        $this->assertFalse($guard->attempt($cred(self::PASSWORD)));
        $this->assertGuest('beneficiary');
    }

    public function test_credential_column_is_password_hash(): void
    {
        $user = $this->staff();
        $b = $this->beneficiary();

        foreach ([$user, $b] as $m) {
            $this->assertSame('password_hash', $m->getAuthPasswordName());
            $this->assertTrue(Hash::check(self::PASSWORD, $m->getAuthPassword()));
            $this->assertTrue(Hash::isHashed($m->getAuthPassword()));
        }
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('users', 'password'));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('beneficiaries', 'password'));
    }

    public function test_logout_invalidates_session_for_staff_and_beneficiary(): void
    {
        $user = $this->staff();
        $this->post('/login', ['login' => $user->username, 'password' => self::PASSWORD]);
        $this->assertAuthenticated('web');
        $oldId = session()->getId();

        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest('web');
        $this->assertNotSame($oldId, session()->getId());
        $this->get(route('trainer.dashboard'))->assertRedirect();

        $b = $this->beneficiary();
        $this->post('/login', ['login' => $b->username, 'password' => self::PASSWORD]);
        $this->assertAuthenticated('beneficiary');
        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest('beneficiary');
    }

    public function test_login_is_throttled_after_repeated_failures(): void
    {
        $user = $this->staff();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['login' => $user->username, 'password' => 'bad-password']);
        }

        $this->post('/login', ['login' => $user->username, 'password' => self::PASSWORD])
            ->assertStatus(429);

        $this->assertGuest('web');
    }

    /**
     * Hashes created with a different bcrypt cost (legacy accounts, or after
     * BCRYPT_ROUNDS changes) trigger Laravel's rehash-on-login, which writes to
     * the model's getAuthPasswordName() column. It must be password_hash.
     */
    public function test_staff_with_outdated_hash_cost_can_still_login_and_is_rehashed(): void
    {
        $user = $this->staff(['password_hash' => password_hash(self::PASSWORD, PASSWORD_BCRYPT, ['cost' => 10])]);
        $old = $user->password_hash;

        $this->post('/login', ['login' => $user->username, 'password' => self::PASSWORD])
            ->assertRedirect(route('trainer.dashboard'));

        $this->assertAuthenticatedAs($user, 'web');
        $fresh = $user->fresh()->password_hash;
        $this->assertNotSame($old, $fresh);
        $this->assertTrue(Hash::check(self::PASSWORD, $fresh));
    }

    public function test_beneficiary_with_outdated_hash_cost_can_still_login_and_is_rehashed(): void
    {
        $b = $this->beneficiary(['password_hash' => password_hash(self::PASSWORD, PASSWORD_BCRYPT, ['cost' => 10])]);
        $old = $b->password_hash;

        $this->post('/login', ['login' => $b->username, 'password' => self::PASSWORD])
            ->assertRedirect(route('beneficiary.home'));

        $this->assertAuthenticatedAs($b, 'beneficiary');
        $this->assertNotSame($old, $b->fresh()->password_hash);
    }
}

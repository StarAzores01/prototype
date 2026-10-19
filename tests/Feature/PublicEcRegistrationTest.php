<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class PublicEcRegistrationTest extends TestCase
{
    use \Tests\Concerns\UsesDisposablePostgres;

    protected function setUp(): void
    {
        parent::setUp();
        $this->beginDisposableDatabaseTransaction();
    }

    public function test_public_ec_signup_cannot_create_an_account(): void
    {
        $before = User::count();
        $token = bin2hex(random_bytes(8));
        $this->get(route('ec.signup'))->assertNotFound();
        $this->post(route('ec.signup'), [
            'first_name' => 'Public', 'last_name' => 'Signup', 'username' => $token,
            'email' => $token.'@example.test', 'id_number' => $token, 'position' => 'Instructor',
            'password' => 'Test-Only-Pass-123!', 'password2' => 'Test-Only-Pass-123!', 'agree' => '1',
        ])->assertNotFound();
        $this->assertSame($before, User::count());
        $this->assertFalse(User::where('username', $token)->exists());
    }
}

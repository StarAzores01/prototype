<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicEcRegistrationRouteTest extends TestCase
{
    public function test_named_signup_get_and_post_are_disabled_without_database_access(): void
    {
        DB::shouldReceive('connection')->never();
        $url = route('ec.signup');
        $this->assertStringEndsWith('/account/ec-signup', $url);
        $this->get($url)->assertNotFound();
        $this->post($url, ['username' => 'blocked-public-signup'])->assertNotFound();
    }

    public function test_legacy_signup_redirect_ends_at_disabled_named_route(): void
    {
        DB::shouldReceive('connection')->never();
        $this->get('/ecsignuppage.php')->assertRedirect('/account/ec-signup');
        $this->get(route('ec.signup'))->assertNotFound();
    }
}

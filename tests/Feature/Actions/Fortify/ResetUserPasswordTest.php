<?php

namespace Tests\Feature\Actions\Fortify;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class ResetUserPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_resetting_password_rotates_remember_token(): void
    {
        $user = User::factory()->create(['remember_token' => 'old-remember-token']);
        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'brand-new-password-1',
            'password_confirmation' => 'brand-new-password-1',
        ])->assertRedirect(route('login'));

        $user->refresh();
        $this->assertTrue(Hash::check('brand-new-password-1', $user->password));
        $this->assertNotSame('old-remember-token', $user->remember_token);
    }
}

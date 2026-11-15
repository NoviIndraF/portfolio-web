<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Auth\Pages\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AdminTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_without_two_factor_is_redirected_to_set_it_up(): void
    {
        $admin = User::factory()->withoutTwoFactor()->create();

        $this->actingAs($admin)->get('/admin')
            ->assertRedirect('/admin/multi-factor-authentication/set-up');
        $this->actingAs($admin)->get('/admin/projects')
            ->assertRedirect('/admin/multi-factor-authentication/set-up');
        $this->actingAs($admin)->get('/admin/multi-factor-authentication/set-up')->assertOk();
    }

    public function test_correct_password_alone_does_not_sign_in(): void
    {
        $admin = User::factory()->create();

        Livewire::test(Login::class)
            ->fillForm(['email' => $admin->email, 'password' => 'password'])
            ->call('authenticate')
            ->assertHasNoFormErrors()
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_wrong_authenticator_code_is_rejected(): void
    {
        $admin = User::factory()->create();
        $wrongCode = $this->currentCode($admin) === '000000' ? '111111' : '000000';

        Livewire::test(Login::class)
            ->fillForm(['email' => $admin->email, 'password' => 'password'])
            ->call('authenticate')
            ->set('data.multiFactor.app.code', $wrongCode)
            ->call('authenticate')
            ->assertHasErrors(['data.multiFactor.app.code'])
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_password_and_authenticator_code_sign_in(): void
    {
        $admin = User::factory()->create();

        Livewire::test(Login::class)
            ->fillForm(['email' => $admin->email, 'password' => 'password'])
            ->call('authenticate')
            ->set('data.multiFactor.app.code', $this->currentCode($admin))
            ->call('authenticate')
            ->assertHasNoErrors()
            ->assertRedirect('/admin');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_two_factor_secret_is_encrypted_in_the_database(): void
    {
        $admin = User::factory()->create();

        $stored = $admin->getRawOriginal('app_authentication_secret');

        $this->assertNotSame($admin->getAppAuthenticationSecret(), $stored);
        $this->assertArrayNotHasKey('app_authentication_secret', $admin->toArray());
    }

    /** The six-digit code the admin's authenticator app shows right now. */
    private function currentCode(User $admin): string
    {
        return app(Google2FA::class)->getCurrentOtp($admin->getAppAuthenticationSecret());
    }
}

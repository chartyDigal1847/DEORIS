<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class SsoControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_session_can_call_sso_check_without_origin_or_referer_headers(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('homepage', absolute: false));

        $this->getJson('/api/sso/check')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('authenticated', true)
            ->assertJsonPath('user.id', $user->id);
    }

    public function test_authenticated_portal_session_can_issue_and_exchange_single_use_sso_token(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user, 'web');

        $check = $this
            ->withHeader('Referer', 'https://deoris.test/homepage')
            ->getJson('/api/sso/check');

        $check
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('authenticated', true)
            ->assertJsonPath('user.id', $user->id);

        $issued = $this
            ->withHeader('Referer', 'https://deoris.test/homepage')
            ->getJson('/api/sso/token');

        $plainTextToken = $issued
            ->assertOk()
            ->assertJsonPath('success', true)
            ->json('token');

        $this->assertIsString($plainTextToken);

        $storedToken = PersonalAccessToken::query()
            ->where('tokenable_id', $user->id)
            ->where('name', 'sso-token')
            ->first();

        $this->assertNotNull($storedToken);
        $this->assertNull($storedToken->expires_at);

        $exchange = $this->postJson('/api/sso/exchange', [
            'token' => $plainTextToken,
        ]);

        $exchange
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.id', $user->id);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $storedToken->id,
        ]);

        $this->postJson('/api/sso/exchange', [
            'token' => $plainTextToken,
        ])
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => 'invalid_sso_token',
            ]);
    }

    public function test_new_sso_token_revokes_prior_sso_token_for_same_user(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user, 'web');

        $firstToken = $this
            ->withHeader('Referer', 'https://deoris.test/homepage')
            ->getJson('/api/sso/token')
            ->assertOk()
            ->json('token');

        $this
            ->withHeader('Referer', 'https://deoris.test/homepage')
            ->getJson('/api/sso/token')
            ->assertOk();

        $this->postJson('/api/sso/exchange', [
            'token' => $firstToken,
        ])
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => 'invalid_sso_token',
            ]);

        $this->assertSame(1, PersonalAccessToken::query()
            ->where('tokenable_id', $user->id)
            ->where('name', 'sso-token')
            ->count());
    }
}

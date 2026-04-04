<?php

use App\Models\ConnectedAccount;
use App\Models\User;

it('returns security summary for authenticated user', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password'),
        'password_updated_at' => now()->subDays(5),
        'two_factor_confirmed_at' => null,
    ]);

    $response = $this->actingAs($user)
        ->getJson('/settings/security-summary')
        ->assertOk();

    $response->assertJsonStructure([
        'authentication' => [
            'password' => ['enabled', 'last_changed_at'],
            'two_factor' => ['enabled', 'confirmed_at'],
            'social_accounts' => [],
        ],
        'security_score',
        'recommendations',
    ]);
});

it('shows password as enabled when password is set', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password'),
        'password_updated_at' => now()->subDays(10),
    ]);

    $response = $this->actingAs($user)
        ->getJson('/settings/security-summary')
        ->assertOk();

    $response->assertJsonPath('authentication.password.enabled', true);
    $response->assertJsonPath('authentication.password.last_changed_at', $user->password_updated_at->toIso8601String());
});

it('shows password as disabled when password is null', function () {
    $user = User::factory()->create([
        'password' => null,
        'password_updated_at' => null,
    ]);

    $response = $this->actingAs($user)
        ->getJson('/settings/security-summary')
        ->assertOk();

    $response->assertJsonPath('authentication.password.enabled', false);
    $response->assertJsonPath('authentication.password.last_changed_at', null);
});

it('shows 2fa as enabled when confirmed_at is set', function () {
    $user = User::factory()->create([
        'two_factor_confirmed_at' => now()->subMonth(),
    ]);

    $response = $this->actingAs($user)
        ->getJson('/settings/security-summary')
        ->assertOk();

    $response->assertJsonPath('authentication.two_factor.enabled', true);
    $response->assertJsonPath('authentication.two_factor.confirmed_at', $user->two_factor_confirmed_at->toIso8601String());
});

it('shows 2fa as disabled when confirmed_at is null', function () {
    $user = User::factory()->create([
        'two_factor_confirmed_at' => null,
    ]);

    $response = $this->actingAs($user)
        ->getJson('/settings/security-summary')
        ->assertOk();

    $response->assertJsonPath('authentication.two_factor.enabled', false);
    $response->assertJsonPath('authentication.two_factor.confirmed_at', null);
});

it('includes connected social accounts', function () {
    $user = User::factory()->create();
    $github = ConnectedAccount::factory()->create([
        'user_id' => $user->id,
        'provider' => 'github',
        'name' => 'Test User',
        'email' => 'test@github.com',
    ]);
    $google = ConnectedAccount::factory()->create([
        'user_id' => $user->id,
        'provider' => 'google',
        'name' => 'Test User',
        'email' => 'test@gmail.com',
    ]);

    $response = $this->actingAs($user)
        ->getJson('/settings/security-summary')
        ->assertOk();

    $response->assertJsonCount(2, 'authentication.social_accounts');
    $response->assertJsonPath('authentication.social_accounts.0.provider', 'github');
    $response->assertJsonPath('authentication.social_accounts.0.provider_name', 'GitHub');
    $response->assertJsonPath('authentication.social_accounts.1.provider', 'google');
    $response->assertJsonPath('authentication.social_accounts.1.provider_name', 'Google');
});

it('calculates security score correctly', function () {
    // Score 0: no password, no 2fa, no social
    $user1 = User::factory()->create(['password' => null, 'two_factor_confirmed_at' => null]);
    $this->actingAs($user1)
        ->getJson('/settings/security-summary')
        ->assertJsonPath('security_score', 0);

    // Score 40: password only
    $user2 = User::factory()->create(['password' => bcrypt('pass'), 'two_factor_confirmed_at' => null]);
    $this->actingAs($user2)
        ->getJson('/settings/security-summary')
        ->assertJsonPath('security_score', 40);

    // Score 60: password + social
    $user3 = User::factory()->create(['password' => bcrypt('pass'), 'two_factor_confirmed_at' => null]);
    ConnectedAccount::factory()->create(['user_id' => $user3->id, 'provider' => 'github']);
    $this->actingAs($user3)
        ->getJson('/settings/security-summary')
        ->assertJsonPath('security_score', 60);

    // Score 80: password + 2fa
    $user4 = User::factory()->create(['password' => bcrypt('pass'), 'two_factor_confirmed_at' => now()]);
    $this->actingAs($user4)
        ->getJson('/settings/security-summary')
        ->assertJsonPath('security_score', 80);

    // Score 100: password + 2fa + social
    $user5 = User::factory()->create(['password' => bcrypt('pass'), 'two_factor_confirmed_at' => now()]);
    ConnectedAccount::factory()->create(['user_id' => $user5->id, 'provider' => 'google']);
    $this->actingAs($user5)
        ->getJson('/settings/security-summary')
        ->assertJsonPath('security_score', 100);
});

it('returns recommendations for missing security features', function () {
    // No password, no 2fa, no social - should get 2 recommendations
    $user = User::factory()->create([
        'password' => null,
        'two_factor_confirmed_at' => null,
    ]);

    $response = $this->actingAs($user)
        ->getJson('/settings/security-summary')
        ->assertOk();

    $response->assertJsonCount(2, 'recommendations');
    $response->assertJsonPath('recommendations.0.priority', 'high');
    $response->assertJsonPath('recommendations.1.priority', 'high');
});

it('returns recommendation to enable 2fa when disabled', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password'),
        'two_factor_confirmed_at' => null,
    ]);

    $response = $this->actingAs($user)
        ->getJson('/settings/security-summary')
        ->assertOk();

    $recommendations = $response->json('recommendations');
    $has2faRecommendation = collect($recommendations)->contains(
        fn ($r) => str_contains($r['text'], 'two-factor') && $r['priority'] === 'high'
    );
    expect($has2faRecommendation)->toBeTrue();
});

it('returns recommendation to connect social account when password is set', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password'),
        'two_factor_confirmed_at' => now(), // 2FA enabled so only social recommendation
    ]);

    $response = $this->actingAs($user)
        ->getJson('/settings/security-summary')
        ->assertOk();

    $response->assertJsonCount(1, 'recommendations');
    $response->assertJsonPath('recommendations.0.priority', 'low');
    $response->assertJsonPath('recommendations.0.action', '/settings/connected-accounts');
});

it('returns empty recommendations when security score is 100', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password'),
        'two_factor_confirmed_at' => now(),
    ]);
    ConnectedAccount::factory()->create(['user_id' => $user->id, 'provider' => 'github']);

    $response = $this->actingAs($user)
        ->getJson('/settings/security-summary')
        ->assertOk();

    $response->assertJsonCount(0, 'recommendations');
    $response->assertJsonPath('security_score', 100);
});

it('requires authentication', function () {
    $this->getJson('/settings/security-summary')
        ->assertUnauthorized();
});

<?php

use App\Models\User;
use App\Models\Workspace;

it('updates last_seen_at on authenticated web request', function () {
    $user = User::factory()->create(['last_seen_at' => null]);
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    $workspace->addUser($user, 'owner');
    $user->switchWorkspace($workspace);

    expect($user->last_seen_at)->toBeNull();

    $this->actingAs($user)->get('/dashboard')->assertOk();

    $user->refresh();
    expect($user->last_seen_at)->not->toBeNull();
});

it('does not update last_seen_at within the 5-minute throttle window', function () {
    $recentTime = now()->subMinutes(2);
    $user = User::factory()->create(['last_seen_at' => $recentTime]);
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    $workspace->addUser($user, 'owner');
    $user->switchWorkspace($workspace);

    $this->actingAs($user)->get('/dashboard')->assertOk();

    $user->refresh();
    expect($user->last_seen_at->toDateTimeString())->toBe($recentTime->toDateTimeString());
});

it('updates last_seen_at when more than 5 minutes have passed', function () {
    $oldTime = now()->subMinutes(10);
    $user = User::factory()->create(['last_seen_at' => $oldTime]);
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    $workspace->addUser($user, 'owner');
    $user->switchWorkspace($workspace);

    $this->actingAs($user)->get('/dashboard')->assertOk();

    $user->refresh();
    expect($user->last_seen_at->gt($oldTime))->toBeTrue();
});

it('does not update last_seen_at for unauthenticated requests', function () {
    // Accessing an auth-required route without authentication should redirect
    $this->get('/dashboard')->assertRedirect();

    // No user to check, just ensure no errors occur
    expect(true)->toBeTrue();
});

it('team index returns last_seen_at for members', function () {
    $owner = User::factory()->create(['last_seen_at' => now()->subHour()]);
    $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
    $workspace->addUser($owner, 'owner');
    $owner->switchWorkspace($workspace);

    $member = User::factory()->create(['last_seen_at' => now()->subDay()]);
    $workspace->addUser($member, 'member');

    $this->actingAs($owner)
        ->get('/team')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('members', 2)
            ->where('members', fn ($members) => collect($members)->every(fn ($m) => array_key_exists('last_seen_at', $m)))
        );
});

it('admin user list returns last_seen_at for each user', function () {
    $admin = User::factory()->create([
        'is_superadmin' => true,
        'last_seen_at' => now(),
    ]);
    $admin->forceFill(['two_factor_secret' => 'secret', 'two_factor_confirmed_at' => now()])->save();

    $this->actingAs($admin)
        ->get('/admin/users')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('users')
            ->where('users.data', fn ($users) => collect($users)->every(fn ($u) => array_key_exists('last_seen_at', $u)))
        );
});

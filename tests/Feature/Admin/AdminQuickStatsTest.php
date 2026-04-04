<?php

use App\Models\User;
use App\Models\Workspace;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_superadmin' => true]);
    $this->admin->forceFill(['two_factor_secret' => 'secret', 'two_factor_confirmed_at' => now()])->save();
});

it('returns json with total_users, total_workspaces, and mrr', function () {
    User::factory()->count(3)->create();
    Workspace::factory()->count(2)->create();

    $this->actingAs($this->admin)
        ->getJson('/admin/quick-stats')
        ->assertOk()
        ->assertJsonStructure(['total_users', 'total_workspaces', 'mrr'])
        ->assertJsonPath('total_users', User::count())
        ->assertJsonPath('total_workspaces', Workspace::count());
});

it('returns mrr as a numeric value', function () {
    $response = $this->actingAs($this->admin)
        ->getJson('/admin/quick-stats')
        ->assertOk();

    expect($response->json('mrr'))->toBeNumeric();
});

it('is forbidden for non-superadmin users', function () {
    $user = User::factory()->create(['is_superadmin' => false]);

    $this->actingAs($user)
        ->getJson('/admin/quick-stats')
        ->assertForbidden();
});

it('returns 401 for unauthenticated json requests', function () {
    $this->getJson('/admin/quick-stats')
        ->assertUnauthorized();
});

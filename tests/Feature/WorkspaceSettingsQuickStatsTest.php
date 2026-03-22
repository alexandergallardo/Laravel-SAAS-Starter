<?php

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceApiKey;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->workspace = Workspace::factory()->create(['owner_id' => $this->owner->id]);
    $this->workspace->addUser($this->owner, 'owner');
    $this->owner->switchWorkspace($this->workspace);
});

it('requires authentication', function () {
    $this->get('/workspaces/settings')->assertRedirect('/login');
});

it('returns stats prop with members_count', function () {
    $member = User::factory()->create();
    $this->workspace->addUser($member, 'member');

    $response = $this->actingAs($this->owner)
        ->get('/workspaces/settings')
        ->assertOk();

    $stats = $response->original->getData()['page']['props']['stats'];
    expect($stats['members_count'])->toBe(2);
});

it('returns stats prop with api_keys_count', function () {
    WorkspaceApiKey::factory()->count(3)->create(['workspace_id' => $this->workspace->id]);

    $response = $this->actingAs($this->owner)
        ->get('/workspaces/settings')
        ->assertOk();

    $stats = $response->original->getData()['page']['props']['stats'];
    expect($stats['api_keys_count'])->toBe(3);
});

it('returns zero api_keys_count when no keys exist', function () {
    $response = $this->actingAs($this->owner)
        ->get('/workspaces/settings')
        ->assertOk();

    $stats = $response->original->getData()['page']['props']['stats'];
    expect($stats['api_keys_count'])->toBe(0);
});

it('returns workspace created_at in workspace prop', function () {
    $response = $this->actingAs($this->owner)
        ->get('/workspaces/settings')
        ->assertOk();

    $workspace = $response->original->getData()['page']['props']['workspace'];
    expect($workspace['created_at'])->not->toBeNull();
});

it('returns correct member count for workspace', function () {
    $member1 = User::factory()->create();
    $member2 = User::factory()->create();
    $this->workspace->addUser($member1, 'member');
    $this->workspace->addUser($member2, 'member');

    $response = $this->actingAs($this->owner)
        ->get('/workspaces/settings')
        ->assertOk();

    $stats = $response->original->getData()['page']['props']['stats'];
    expect($stats['members_count'])->toBe(3);
});

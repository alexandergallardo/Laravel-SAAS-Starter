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

it('api keys page exposes last_used_at for each key', function () {
    WorkspaceApiKey::factory()->create(['workspace_id' => $this->workspace->id]);

    $this->actingAs($this->owner)
        ->get('/workspaces/api-keys')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('keys.0.last_used_at')
        );
});

it('last_used_at is null when key has never been used', function () {
    WorkspaceApiKey::factory()->create([
        'workspace_id' => $this->workspace->id,
        'last_used_at' => null,
    ]);

    $this->actingAs($this->owner)
        ->get('/workspaces/api-keys')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('keys.0.last_used_at', null)
        );
});

it('last_used_at is populated after recordUsage is called', function () {
    $key = WorkspaceApiKey::factory()->create([
        'workspace_id' => $this->workspace->id,
        'last_used_at' => null,
    ]);

    expect($key->last_used_at)->toBeNull();

    $key->recordUsage();

    expect($key->fresh()->last_used_at)->not->toBeNull();
});

it('last_used_at is returned as iso8601 string when set', function () {
    $usedAt = now()->subHours(2);

    WorkspaceApiKey::factory()->create([
        'workspace_id' => $this->workspace->id,
        'last_used_at' => $usedAt,
    ]);

    $this->actingAs($this->owner)
        ->get('/workspaces/api-keys')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('keys.0.last_used_at', fn ($val) => $val !== null && str_contains($val, 'T'))
        );
});

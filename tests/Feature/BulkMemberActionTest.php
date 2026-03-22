<?php

use App\Models\User;
use App\Models\Workspace;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->workspace = Workspace::factory()->create(['owner_id' => $this->owner->id]);
    $this->workspace->addUser($this->owner, 'owner');
    $this->owner->switchWorkspace($this->workspace);
});

it('requires authentication', function () {
    $this->postJson('/team/bulk-action', [])->assertUnauthorized();
});

it('forbids non-admin members', function () {
    $member = User::factory()->create();
    $this->workspace->addUser($member, 'member');
    $member->switchWorkspace($this->workspace);

    $this->actingAs($member)
        ->postJson('/team/bulk-action', ['action' => 'remove', 'user_ids' => [$this->owner->id]])
        ->assertForbidden();
});

it('validates required fields', function () {
    $admin = User::factory()->create();
    $this->workspace->addUser($admin, 'admin');
    $admin->switchWorkspace($this->workspace);

    $this->actingAs($admin)
        ->postJson('/team/bulk-action', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['action', 'user_ids']);
});

it('validates action must be remove or change_role', function () {
    $admin = User::factory()->create();
    $this->workspace->addUser($admin, 'admin');
    $admin->switchWorkspace($this->workspace);
    $member = User::factory()->create();
    $this->workspace->addUser($member, 'member');

    $this->actingAs($admin)
        ->postJson('/team/bulk-action', ['action' => 'ban', 'user_ids' => [$member->id]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['action']);
});

it('validates role is required when action is change_role', function () {
    $admin = User::factory()->create();
    $this->workspace->addUser($admin, 'admin');
    $admin->switchWorkspace($this->workspace);
    $member = User::factory()->create();
    $this->workspace->addUser($member, 'member');

    $this->actingAs($admin)
        ->postJson('/team/bulk-action', ['action' => 'change_role', 'user_ids' => [$member->id]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['role']);
});

it('admin can bulk remove members', function () {
    $admin = User::factory()->create();
    $this->workspace->addUser($admin, 'admin');
    $admin->switchWorkspace($this->workspace);
    $member1 = User::factory()->create();
    $member2 = User::factory()->create();
    $this->workspace->addUser($member1, 'member');
    $this->workspace->addUser($member2, 'member');

    $this->actingAs($admin)
        ->post('/team/bulk-action', ['action' => 'remove', 'user_ids' => [$member1->id, $member2->id]])
        ->assertRedirect();

    expect($this->workspace->fresh()->hasUser($member1))->toBeFalse();
    expect($this->workspace->fresh()->hasUser($member2))->toBeFalse();
});

it('admin can bulk change role', function () {
    $admin = User::factory()->create();
    $this->workspace->addUser($admin, 'admin');
    $admin->switchWorkspace($this->workspace);
    $member1 = User::factory()->create();
    $member2 = User::factory()->create();
    $this->workspace->addUser($member1, 'member');
    $this->workspace->addUser($member2, 'member');

    $this->actingAs($admin)
        ->post('/team/bulk-action', ['action' => 'change_role', 'user_ids' => [$member1->id, $member2->id], 'role' => 'viewer'])
        ->assertRedirect();

    expect($this->workspace->getUserRole($member1))->toBe('viewer');
    expect($this->workspace->getUserRole($member2))->toBe('viewer');
});

it('skips workspace owners when bulk removing', function () {
    $admin = User::factory()->create();
    $this->workspace->addUser($admin, 'admin');
    $admin->switchWorkspace($this->workspace);

    $this->actingAs($admin)
        ->post('/team/bulk-action', ['action' => 'remove', 'user_ids' => [$this->owner->id]])
        ->assertRedirect();

    expect($this->workspace->fresh()->hasUser($this->owner))->toBeTrue();
});

it('cannot remove yourself via bulk action', function () {
    $admin = User::factory()->create();
    $this->workspace->addUser($admin, 'admin');
    $admin->switchWorkspace($this->workspace);

    $this->actingAs($admin)
        ->post('/team/bulk-action', ['action' => 'remove', 'user_ids' => [$admin->id]])
        ->assertRedirect();

    expect($this->workspace->fresh()->hasUser($admin))->toBeTrue();
});

it('skips users not in workspace', function () {
    $admin = User::factory()->create();
    $this->workspace->addUser($admin, 'admin');
    $admin->switchWorkspace($this->workspace);
    $outsider = User::factory()->create();

    $this->actingAs($admin)
        ->post('/team/bulk-action', ['action' => 'remove', 'user_ids' => [$outsider->id]])
        ->assertRedirect()
        ->assertSessionHas('success', '0 members removed from the workspace.');
});

it('owner can bulk remove members', function () {
    $member = User::factory()->create();
    $this->workspace->addUser($member, 'member');

    $this->actingAs($this->owner)
        ->post('/team/bulk-action', ['action' => 'remove', 'user_ids' => [$member->id]])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($this->workspace->fresh()->hasUser($member))->toBeFalse();
});

it('shows singular message when one member is processed', function () {
    $admin = User::factory()->create();
    $this->workspace->addUser($admin, 'admin');
    $admin->switchWorkspace($this->workspace);
    $member = User::factory()->create();
    $this->workspace->addUser($member, 'member');

    $this->actingAs($admin)
        ->post('/team/bulk-action', ['action' => 'remove', 'user_ids' => [$member->id]])
        ->assertRedirect()
        ->assertSessionHas('success', '1 member removed from the workspace.');
});

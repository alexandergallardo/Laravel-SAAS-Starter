<?php

use App\Models\User;
use App\Models\Workspace;

beforeEach(function (): void {
    $this->owner = User::factory()->create();
    $this->workspace = Workspace::factory()->create(['owner_id' => $this->owner->id, 'personal_workspace' => false]);
    $this->workspace->addUser($this->owner, 'owner');
    $this->owner->switchWorkspace($this->workspace);

    $this->admin = User::factory()->create();
    $this->workspace->addUser($this->admin, 'admin');
    $this->admin->switchWorkspace($this->workspace);

    $this->member = User::factory()->create();
    $this->workspace->addUser($this->member, 'member');
    $this->member->switchWorkspace($this->workspace);
});

it('owner can transfer ownership to an admin', function (): void {
    $response = $this->actingAs($this->owner)
        ->post("/team/transfer-ownership/{$this->admin->id}");

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->workspace->refresh();
    expect($this->workspace->owner_id)->toBe($this->admin->id);
});

it('updates roles after transfer', function (): void {
    $this->actingAs($this->owner)->post("/team/transfer-ownership/{$this->admin->id}");

    $this->workspace->refresh();
    expect($this->workspace->getUserRole($this->admin))->toBe('owner');
    expect($this->workspace->getUserRole($this->owner))->toBe('admin');
});

it('non-owner cannot transfer ownership', function (): void {
    $response = $this->actingAs($this->admin)
        ->post("/team/transfer-ownership/{$this->member->id}");

    $response->assertForbidden();
});

it('cannot transfer to a non-admin member', function (): void {
    $response = $this->actingAs($this->owner)
        ->post("/team/transfer-ownership/{$this->member->id}");

    $response->assertRedirect();
    $response->assertSessionHas('error');

    $this->workspace->refresh();
    expect($this->workspace->owner_id)->toBe($this->owner->id);
});

it('cannot transfer ownership of a personal workspace', function (): void {
    $personal = Workspace::factory()->create(['owner_id' => $this->owner->id, 'personal_workspace' => true]);
    $personal->addUser($this->owner, 'owner');
    $this->owner->switchWorkspace($personal);

    $response = $this->actingAs($this->owner)
        ->post("/team/transfer-ownership/{$this->admin->id}");

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

it('unauthenticated user cannot transfer ownership', function (): void {
    $response = $this->post("/team/transfer-ownership/{$this->admin->id}");

    $response->assertRedirect('/login');
});

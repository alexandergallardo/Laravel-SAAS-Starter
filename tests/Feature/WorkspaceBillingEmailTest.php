<?php

use App\Models\User;
use App\Models\Workspace;

beforeEach(function (): void {
    $this->owner = User::factory()->create();
    $this->workspace = Workspace::factory()->create(['owner_id' => $this->owner->id, 'personal_workspace' => false]);
    $this->workspace->addUser($this->owner, 'owner');
    $this->owner->switchWorkspace($this->workspace);
});

it('owner can set a billing email', function (): void {
    $response = $this->actingAs($this->owner)
        ->put('/workspaces/settings', [
            'name' => $this->workspace->name,
            'billing_email' => 'billing@acme.com',
        ]);

    $response->assertRedirect();

    $this->workspace->refresh();
    expect($this->workspace->billing_email)->toBe('billing@acme.com');
});

it('owner can clear the billing email', function (): void {
    $this->workspace->update(['billing_email' => 'old@acme.com']);

    $response = $this->actingAs($this->owner)
        ->put('/workspaces/settings', [
            'name' => $this->workspace->name,
            'billing_email' => '',
        ]);

    $response->assertRedirect();

    $this->workspace->refresh();
    expect($this->workspace->billing_email)->toBeNull();
});

it('rejects invalid email format', function (): void {
    $response = $this->actingAs($this->owner)
        ->put('/workspaces/settings', [
            'name' => $this->workspace->name,
            'billing_email' => 'not-an-email',
        ]);

    $response->assertSessionHasErrors('billing_email');
});

it('workspace settings page includes billing_email prop', function (): void {
    $this->workspace->update(['billing_email' => 'finance@acme.com']);

    $response = $this->actingAs($this->owner)->get('/workspaces/settings');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('workspace.billing_email', 'finance@acme.com')
    );
});

it('non-admin member cannot update billing email', function (): void {
    $member = User::factory()->create();
    $this->workspace->addUser($member, 'member');
    $member->switchWorkspace($this->workspace);

    $response = $this->actingAs($member)
        ->put('/workspaces/settings', [
            'name' => $this->workspace->name,
            'billing_email' => 'hacker@evil.com',
        ]);

    $response->assertForbidden();
});

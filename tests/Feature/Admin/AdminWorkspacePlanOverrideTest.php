<?php

use App\Models\User;
use App\Models\Workspace;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_superadmin' => true]);
    $this->owner = User::factory()->create();
    $this->workspace = Workspace::factory()->create(['owner_id' => $this->owner->id, 'name' => 'Acme Corp']);
});

it('requires authentication', function () {
    $this->postJson("/admin/workspaces/{$this->workspace->id}/override-plan", [])
        ->assertUnauthorized();
});

it('forbids non-superadmin users', function () {
    $user = User::factory()->create(['is_superadmin' => false]);

    $this->actingAs($user)
        ->post("/admin/workspaces/{$this->workspace->id}/override-plan", ['plan_override' => 'Pro'])
        ->assertForbidden();
});

it('superadmin can set a plan override', function () {
    $this->actingAs($this->admin)
        ->post("/admin/workspaces/{$this->workspace->id}/override-plan", ['plan_override' => 'Pro'])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($this->workspace->fresh()->plan_override)->toBe('Pro');
});

it('superadmin can clear a plan override', function () {
    $this->workspace->update(['plan_override' => 'Pro']);

    $this->actingAs($this->admin)
        ->post("/admin/workspaces/{$this->workspace->id}/override-plan", ['plan_override' => ''])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($this->workspace->fresh()->plan_override)->toBeNull();
});

it('plan_override takes precedence over subscription in plan_name', function () {
    $this->workspace->update(['plan_override' => 'Business']);

    expect($this->workspace->fresh()->plan_name)->toBe('Business');
});

it('no override returns free plan for unsubscribed workspace', function () {
    expect($this->workspace->fresh()->plan_name)->toBe('Free');
});

it('validates plan_override max length', function () {
    $this->actingAs($this->admin)
        ->postJson("/admin/workspaces/{$this->workspace->id}/override-plan", ['plan_override' => str_repeat('a', 51)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['plan_override']);
});

it('admin workspaces index includes plan_override in response', function () {
    $this->workspace->update(['plan_override' => 'Pro']);

    $response = $this->actingAs($this->admin)
        ->get('/admin/workspaces')
        ->assertOk();

    $data = $response->original->getData()['page']['props']['workspaces']['data'];
    $workspace = collect($data)->firstWhere('id', $this->workspace->id);
    expect($workspace['plan_override'])->toBe('Pro');
});

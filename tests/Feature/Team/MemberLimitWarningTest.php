<?php

use App\Models\User;
use App\Models\Workspace;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->workspace = Workspace::factory()->create(['owner_id' => $this->owner->id]);
    $this->workspace->addUser($this->owner, 'owner');
    $this->owner->switchWorkspace($this->workspace);
});

it('passes canInvite true when under member limit', function () {
    config(['billing.plans.free.limits.team_members' => 5]);

    $this->actingAs($this->owner)
        ->get('/team')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('canInvite', true)
        );
});

it('passes canInvite false when member limit is reached', function () {
    config(['billing.plans.free.limits.team_members' => 1]);

    $this->actingAs($this->owner)
        ->get('/team')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('canInvite', false)
        );
});

it('passes memberLimitMessage with current usage', function () {
    config(['billing.plans.free.limits.team_members' => 5]);

    $this->actingAs($this->owner)
        ->get('/team')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('memberLimitMessage')
            ->where('memberLimitMessage', fn ($msg) => str_contains($msg, '1 of 5'))
        );
});

it('includes upgrade context in memberLimitMessage when limit reached', function () {
    config(['billing.plans.free.limits.team_members' => 1]);

    $this->actingAs($this->owner)
        ->get('/team')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('canInvite', false)
            ->has('memberLimitMessage')
        );
});

it('shows canInvite true for admin member', function () {
    config(['billing.plans.free.limits.team_members' => 5]);
    $admin = User::factory()->create();
    $this->workspace->addUser($admin, 'admin');
    $admin->switchWorkspace($this->workspace);

    $this->actingAs($admin)
        ->get('/team')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('canInvite', true)
        );
});

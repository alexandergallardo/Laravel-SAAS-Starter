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

it('workspace settings page includes onboardingProgress', function () {
    $this->actingAs($this->owner)
        ->get('/workspaces/settings')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('onboardingProgress')
            ->has('onboardingProgress.score')
            ->has('onboardingProgress.steps')
        );
});

it('score reflects completed steps', function () {
    // owner_has_2fa is completed by default in factory (two_factor_secret is set)
    $this->actingAs($this->owner)
        ->get('/workspaces/settings')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('onboardingProgress.score', fn ($score) => $score >= 0 && $score < 100)
            ->has('onboardingProgress.steps', 5)
        );
});

it('has_logo step is completed when workspace has a logo', function () {
    $this->workspace->update(['logo' => 'logos/test.png']);

    $this->actingAs($this->owner)
        ->get('/workspaces/settings')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('onboardingProgress.steps', fn ($steps) => collect($steps)->firstWhere('key', 'has_logo')['completed'] === true)
        );
});

it('has_members step is completed when workspace has more than one member', function () {
    $member = User::factory()->create();
    $this->workspace->addUser($member, 'member');

    $this->actingAs($this->owner)
        ->get('/workspaces/settings')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('onboardingProgress.steps', fn ($steps) => collect($steps)->firstWhere('key', 'has_members')['completed'] === true)
        );
});

it('has_api_key step is completed when workspace has an api key', function () {
    WorkspaceApiKey::factory()->create(['workspace_id' => $this->workspace->id]);

    $this->actingAs($this->owner)
        ->get('/workspaces/settings')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('onboardingProgress.steps', fn ($steps) => collect($steps)->firstWhere('key', 'has_api_key')['completed'] === true)
        );
});

it('score reaches 100 when all steps completed', function () {
    $this->workspace->update(['logo' => 'logos/test.png']);

    $member = User::factory()->create();
    $this->workspace->addUser($member, 'member');

    WorkspaceApiKey::factory()->create(['workspace_id' => $this->workspace->id]);

    // Simulate 2FA and webhook via direct DB update (simplify for test)
    $this->owner->forceFill(['two_factor_secret' => 'secret'])->save();

    $this->workspace->webhookEndpoints()->create([
        'url' => 'https://example.com/hook',
        'secret' => 'test-secret',
        'events' => ['workspace.updated'],
        'is_active' => true,
    ]);

    $this->actingAs($this->owner)
        ->get('/workspaces/settings')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('onboardingProgress.score', 100)
        );
});

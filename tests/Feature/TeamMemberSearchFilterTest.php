<?php

use App\Models\User;
use App\Models\Workspace;

beforeEach(function (): void {
    $this->owner = User::factory()->create(['name' => 'Alice Owner', 'email' => 'alice@example.com']);
    $this->workspace = Workspace::factory()->create(['owner_id' => $this->owner->id, 'personal_workspace' => false]);
    $this->workspace->addUser($this->owner, 'owner');
    $this->owner->switchWorkspace($this->workspace);

    $this->admin = User::factory()->create(['name' => 'Bob Admin', 'email' => 'bob@example.com']);
    $this->workspace->addUser($this->admin, 'admin');

    $this->member = User::factory()->create(['name' => 'Carol Member', 'email' => 'carol@example.com']);
    $this->workspace->addUser($this->member, 'member');
});

it('renders team page with all members', function (): void {
    $response = $this->actingAs($this->owner)->get('/team');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Team/index')
        ->has('members', 3)
    );
});

it('includes member names and emails in props', function (): void {
    $response = $this->actingAs($this->owner)->get('/team');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('members', 3, fn ($member) => $member
            ->hasAll(['id', 'name', 'email', 'role'])
            ->etc()
        )
    );
});

it('includes role in member data for filter support', function (): void {
    $response = $this->actingAs($this->owner)->get('/team');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('members.0.role', 'owner')
        ->etc()
    );
});

it('team page is accessible by members', function (): void {
    $this->member->switchWorkspace($this->workspace);

    $response = $this->actingAs($this->member)->get('/team');

    $response->assertOk();
});

it('unauthenticated users cannot access team page', function (): void {
    $response = $this->get('/team');

    $response->assertRedirect('/login');
});

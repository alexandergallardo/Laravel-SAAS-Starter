<?php

use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_superadmin' => true]);
});

it('admin dashboard includes sparklines prop', function () {
    $this->actingAs($this->admin)
        ->get('/admin/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('sparklines')
            ->has('sparklines.new_users')
            ->has('sparklines.new_workspaces')
            ->has('sparklines.new_subscriptions')
        );
});

it('sparklines contain 7 data points', function () {
    $this->actingAs($this->admin)
        ->get('/admin/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('sparklines.new_users', 7)
            ->has('sparklines.new_workspaces', 7)
            ->has('sparklines.new_subscriptions', 7)
        );
});

it('sparkline counts users created today', function () {
    User::factory()->count(3)->create();

    $this->actingAs($this->admin)
        ->get('/admin/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('sparklines.new_users.6', fn ($count) => $count >= 3)
        );
});

it('admin dashboard sparklines contain non-negative integers', function () {
    $this->actingAs($this->admin)
        ->get('/admin/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('sparklines.new_users', fn ($data) => collect($data)->every(fn ($v) => is_int($v) && $v >= 0))
            ->where('sparklines.new_workspaces', fn ($data) => collect($data)->every(fn ($v) => is_int($v) && $v >= 0))
            ->where('sparklines.new_subscriptions', fn ($data) => collect($data)->every(fn ($v) => is_int($v) && $v >= 0))
        );
});

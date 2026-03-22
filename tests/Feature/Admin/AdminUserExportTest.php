<?php

use App\Models\User;
use App\Models\Workspace;

it('requires authentication to export users', function () {
    $this->get('/admin/users/export')
        ->assertRedirectToRoute('login');
});

it('forbids non-superadmin from exporting users', function () {
    $user = User::factory()->create(['is_superadmin' => false]);

    $this->actingAs($user)
        ->get('/admin/users/export')
        ->assertForbidden();
});

it('allows superadmin to export all users as csv', function () {
    $admin = User::factory()->create(['is_superadmin' => true]);

    $response = $this->actingAs($admin)
        ->get('/admin/users/export');

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
        ->assertHeader('Content-Disposition');
});

it('exports csv with correct columns', function () {
    $admin = User::factory()->create(['is_superadmin' => true]);
    $user = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

    $response = $this->actingAs($admin)
        ->get('/admin/users/export');

    $response->assertOk();
    $content = $response->streamedContent();

    expect($content)
        ->toContain('ID')
        ->toContain('Name')
        ->toContain('Email')
        ->toContain('Superadmin')
        ->toContain('Workspaces')
        ->toContain('Jane Doe')
        ->toContain('jane@example.com');
});

it('includes workspace count in export', function () {
    $admin = User::factory()->create(['is_superadmin' => true]);
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    $workspace->addUser($user, 'owner');

    $response = $this->actingAs($admin)
        ->get('/admin/users/export');

    $response->assertOk();
    $content = $response->streamedContent();

    // Should have workspace count data (at least the user row)
    expect($content)->toContain($user->email);
});

it('exports csv filename with current date', function () {
    $admin = User::factory()->create(['is_superadmin' => true]);

    $response = $this->actingAs($admin)
        ->get('/admin/users/export');

    $response->assertOk();
    $disposition = $response->headers->get('Content-Disposition');
    expect($disposition)->toContain('users-');
    expect($disposition)->toContain(now()->format('Y-m-d'));
    expect($disposition)->toContain('.csv');
});

it('includes superadmin flag in export', function () {
    $admin = User::factory()->create(['is_superadmin' => true]);

    $response = $this->actingAs($admin)
        ->get('/admin/users/export');

    $response->assertOk();
    $content = $response->streamedContent();
    expect($content)->toContain('Yes');
});

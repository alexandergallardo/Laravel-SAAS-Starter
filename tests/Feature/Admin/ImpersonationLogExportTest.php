<?php

use App\Models\ImpersonationLog;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_superadmin' => true]);
});

it('requires authentication', function () {
    $this->get('/admin/impersonation-logs/export')->assertRedirect('/login');
});

it('forbids non-superadmin users', function () {
    $user = User::factory()->create(['is_superadmin' => false]);

    $this->actingAs($user)
        ->get('/admin/impersonation-logs/export')
        ->assertForbidden();
});

it('superadmin can export impersonation logs as CSV', function () {
    $impersonator = User::factory()->create(['name' => 'Admin User', 'email' => 'admin@example.com']);
    $target = User::factory()->create(['name' => 'Target User', 'email' => 'target@example.com']);

    ImpersonationLog::create([
        'impersonator_id' => $impersonator->id,
        'impersonated_id' => $target->id,
        'started_at' => now()->subHour(),
        'ended_at' => now()->subMinutes(30),
    ]);

    $response = $this->actingAs($this->admin)
        ->get('/admin/impersonation-logs/export');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    $content = $response->streamedContent();
    expect($content)->toContain('Admin User');
    expect($content)->toContain('Target User');
    expect($content)->toContain('admin@example.com');
});

it('CSV includes correct headers', function () {
    $response = $this->actingAs($this->admin)
        ->get('/admin/impersonation-logs/export');

    $response->assertOk();
    $content = $response->streamedContent();
    expect($content)->toContain('Impersonator');
    expect($content)->toContain('Target User');
    expect($content)->toContain('Duration');
});

it('impersonation logs index page is accessible', function () {
    $response = $this->actingAs($this->admin)
        ->get('/admin/impersonation-logs')
        ->assertOk();

    $response->assertInertia(fn ($page) => $page->component('admin/impersonation-logs'));
});

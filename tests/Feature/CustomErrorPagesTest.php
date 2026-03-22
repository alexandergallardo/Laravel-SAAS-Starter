<?php

use App\Models\User;

it('renders 404 as Inertia error page for Inertia requests', function () {
    $response = $this->get('/this-page-does-not-exist-at-all-xyz', [
        'X-Inertia' => 'true',
    ]);

    $response->assertStatus(404);
    $json = $response->json();
    expect($json['component'])->toBe('error');
    expect($json['props']['status'])->toBe(404);
});

it('returns a 403 for non-superadmin users accessing admin routes', function () {
    $user = User::factory()->create(['is_superadmin' => false]);

    $this->actingAs($user)
        ->get('/admin/users')
        ->assertForbidden();
});

it('returns normal HTML for non-Inertia 404 requests', function () {
    $response = $this->get('/this-page-does-not-exist-at-all-xyz');

    $response->assertStatus(404);
    expect($response->headers->get('Content-Type'))->toContain('text/html');
});

it('error page component accepts status prop', function () {
    $response = $this->get('/this-page-does-not-exist-at-all-xyz', [
        'X-Inertia' => 'true',
    ]);

    $json = $response->json();
    expect($json['props'])->toHaveKey('status');
    expect($json['props']['status'])->toBeInt();
});

it('error component is registered as an Inertia page', function () {
    expect(file_exists(resource_path('js/pages/error.tsx')))->toBeTrue();
});

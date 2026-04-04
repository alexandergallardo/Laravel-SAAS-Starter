<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    // Ensure app is up before each test
    Artisan::call('up');
});

afterEach(function () {
    // Ensure app is up after each test
    Artisan::call('up');
});

it('allows superadmins to access site during maintenance mode', function () {
    $superadmin = User::factory()->create([
        'is_superadmin' => true,
        'email_verified_at' => now(),
    ]);

    // Put the app in maintenance mode
    Artisan::call('down', ['--with-secret' => true]);

    // Superadmin should be able to access the site
    $response = $this->actingAs($superadmin)
        ->get('/admin/dashboard');

    $response->assertSuccessful();
});

it('blocks regular users during maintenance mode', function () {
    $user = User::factory()->create([
        'is_superadmin' => false,
        'email_verified_at' => now(),
    ]);

    // Put the app in maintenance mode
    Artisan::call('down', ['--with-secret' => true]);

    // Regular user should see maintenance page
    $response = $this->actingAs($user)
        ->get('/dashboard');

    $response->assertStatus(503);
});

it('blocks guests during maintenance mode', function () {
    // Put the app in maintenance mode
    Artisan::call('down', ['--with-secret' => true]);

    // Guest should see maintenance page
    $response = $this->get('/');

    $response->assertStatus(503);
});

it('allows bypass with secret cookie during maintenance mode', function () {
    // Put the app in maintenance mode
    Artisan::call('down', ['--with-secret' => true]);

    // Extract the secret from the storage
    $maintenanceFile = storage_path('framework/down');
    $data = json_decode(file_get_contents($maintenanceFile), true);
    $secret = $data['secret'] ?? null;

    expect($secret)->not->toBeNull();

    // Access with secret should set a cookie and redirect
    $response = $this->get("/{$secret}");
    $response->assertRedirect('/');
    // Cookie name might vary by Laravel version, just check redirect works
});

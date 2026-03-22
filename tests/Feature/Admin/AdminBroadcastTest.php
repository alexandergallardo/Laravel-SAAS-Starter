<?php

use App\Models\BroadcastMessage;
use App\Models\User;
use Illuminate\Support\Facades\Bus;

beforeEach(function (): void {
    $this->admin = User::factory()->create(['is_superadmin' => true]);
    $this->user = User::factory()->create(['is_superadmin' => false]);
});

it('admin can view broadcasts page', function (): void {
    $response = $this->actingAs($this->admin)->get('/admin/broadcasts');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('admin/broadcasts/index'));
});

it('non-admin cannot access broadcasts page', function (): void {
    $response = $this->actingAs($this->user)->get('/admin/broadcasts');

    $response->assertForbidden();
});

it('admin can send a broadcast without action url', function (): void {
    Bus::fake();

    $response = $this->actingAs($this->admin)->post('/admin/broadcasts', [
        'subject' => 'System Update',
        'body' => 'We will be down for maintenance.',
        'send_via_in_app' => true,
        'target_segment' => 'all_users',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $broadcast = BroadcastMessage::first();
    expect($broadcast)->not->toBeNull();
    expect($broadcast->action_url)->toBeNull();
});

it('admin can send a broadcast with an action url', function (): void {
    Bus::fake();

    $response = $this->actingAs($this->admin)->post('/admin/broadcasts', [
        'subject' => 'New Feature',
        'body' => 'Check out our new feature!',
        'action_url' => 'https://example.com/features',
        'send_via_in_app' => true,
        'target_segment' => 'all_users',
    ]);

    $response->assertRedirect();

    expect(BroadcastMessage::first()->action_url)->toBe('https://example.com/features');
});

it('rejects invalid action url format', function (): void {
    $response = $this->actingAs($this->admin)->post('/admin/broadcasts', [
        'subject' => 'Test',
        'body' => 'Test body.',
        'action_url' => 'not-a-url',
        'send_via_in_app' => true,
        'target_segment' => 'all_users',
    ]);

    $response->assertSessionHasErrors('action_url');
});

it('requires at least one delivery channel', function (): void {
    $response = $this->actingAs($this->admin)->post('/admin/broadcasts', [
        'subject' => 'Test',
        'body' => 'Test body.',
        'send_via_email' => false,
        'send_via_in_app' => false,
        'target_segment' => 'all_users',
    ]);

    $response->assertSessionHasErrors('channels');
});

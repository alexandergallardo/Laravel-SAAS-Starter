<?php

use App\Models\ApiRequestLog;
use App\Models\User;
use App\Models\WorkspaceApiKey;

beforeEach(function (): void {
    $this->admin = User::factory()->create(['is_superadmin' => true]);
});

it('admin can access heatmap page', function (): void {
    $response = $this->actingAs($this->admin)->get('/admin/workspace-activity-heatmap');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/workspace-activity-heatmap')
        ->has('weeks')
        ->has('totalRequests')
        ->has('maxCount')
        ->has('dateRange')
    );
});

it('non-admin is forbidden', function (): void {
    $user = User::factory()->create(['is_superadmin' => false]);

    $response = $this->actingAs($user)->get('/admin/workspace-activity-heatmap');

    $response->assertForbidden();
});

it('returns 52 weeks of data', function (): void {
    $response = $this->actingAs($this->admin)->get('/admin/workspace-activity-heatmap');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('weeks', fn ($weeks) => count($weeks) >= 52)
    );
});

it('counts api requests correctly', function (): void {
    $apiKey = WorkspaceApiKey::factory()->create();

    ApiRequestLog::create([
        'workspace_id' => $apiKey->workspace_id,
        'api_key_id' => $apiKey->id,
        'method' => 'GET',
        'path' => 'api/test',
        'status_code' => 200,
        'response_time_ms' => 50,
        'was_throttled' => false,
        'ip_address' => '127.0.0.1',
        'requested_at' => now(),
    ]);

    $response = $this->actingAs($this->admin)->get('/admin/workspace-activity-heatmap');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('totalRequests', fn ($v) => $v >= 1)
    );
});

it('heatmap has correct date range structure', function (): void {
    $response = $this->actingAs($this->admin)->get('/admin/workspace-activity-heatmap');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('dateRange.start')
        ->has('dateRange.end')
    );
});

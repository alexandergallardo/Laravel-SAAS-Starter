<?php

use App\Models\ImpersonationLog;
use App\Models\User;
use App\Models\Workspace;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_superadmin' => true]);
    $this->target = User::factory()->create(['is_superadmin' => false]);

    Activity::query()->delete();
});

it('admin user show page is accessible by superadmin', function () {
    $this->actingAs($this->admin)
        ->get("/admin/users/{$this->target->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/users/show')
            ->has('user')
            ->has('impersonationLogs')
            ->has('activityLog')
        );
});

it('admin user show page is forbidden for non-superadmin', function () {
    $user = User::factory()->create(['is_superadmin' => false]);

    $this->actingAs($user)
        ->get("/admin/users/{$this->target->id}")
        ->assertForbidden();
});

it('impersonation logs an activity entry via spatie', function () {
    $workspace = Workspace::factory()->create(['owner_id' => $this->admin->id]);
    $workspace->addUser($this->admin, 'owner');
    $this->admin->switchWorkspace($workspace);

    $this->actingAs($this->admin)
        ->post("/admin/impersonate/{$this->target->id}")
        ->assertRedirect();

    $this->assertDatabaseHas('activity_log', [
        'event' => 'impersonated',
        'subject_type' => User::class,
        'subject_id' => $this->target->id,
    ]);
});

it('user show page includes impersonation history', function () {
    ImpersonationLog::create([
        'impersonator_id' => $this->admin->id,
        'impersonated_id' => $this->target->id,
        'ip_address' => '127.0.0.1',
        'started_at' => now()->subHour(),
        'ended_at' => now()->subMinutes(30),
    ]);

    $this->actingAs($this->admin)
        ->get("/admin/users/{$this->target->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('impersonationLogs', 1)
            ->where('impersonationLogs.0.impersonator_name', $this->admin->name)
        );
});

it('user show page displays activity log entries', function () {
    activity()
        ->causedBy($this->admin)
        ->performedOn($this->target)
        ->event('impersonated')
        ->log('Admin impersonated user');

    $this->actingAs($this->admin)
        ->get("/admin/users/{$this->target->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('activityLog', fn ($log) => count($log) >= 1)
        );
});

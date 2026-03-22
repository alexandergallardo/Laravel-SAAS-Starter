<?php

use App\Models\User;
use App\Models\Workspace;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->workspace = Workspace::factory()->create(['owner_id' => $this->owner->id]);
    $this->workspace->addUser($this->owner, 'owner');
    $this->owner->switchWorkspace($this->workspace);

    Activity::query()->delete();
});

it('requires authentication to export', function () {
    $this->get("/workspaces/{$this->workspace->id}/activity/export")
        ->assertRedirectToRoute('login');
});

it('allows workspace owner to export activity as csv', function () {
    activity()
        ->causedBy($this->owner)
        ->on($this->workspace)
        ->event('updated')
        ->log('Workspace updated');

    $response = $this->actingAs($this->owner)
        ->get("/workspaces/{$this->workspace->id}/activity/export");

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
        ->assertHeader('Content-Disposition');
});

it('exports activity with correct csv columns', function () {
    activity()
        ->causedBy($this->owner)
        ->on($this->workspace)
        ->event('updated')
        ->log('Workspace name changed');

    $response = $this->actingAs($this->owner)
        ->get("/workspaces/{$this->workspace->id}/activity/export");

    $response->assertOk();

    $content = $response->streamedContent();
    expect($content)->toContain('Date')
        ->toContain('Event')
        ->toContain('Causer')
        ->toContain('Subject Type')
        ->toContain('Description');
    expect($content)->toContain('updated');
    expect($content)->toContain($this->owner->name);
    expect($content)->toContain('Workspace name changed');
});

it('forbids non-member from exporting', function () {
    $other = User::factory()->create();

    $this->actingAs($other)
        ->get("/workspaces/{$this->workspace->id}/activity/export")
        ->assertForbidden();
});

it('forbids regular member from exporting', function () {
    $member = User::factory()->create();
    $this->workspace->addUser($member, 'member');
    $member->switchWorkspace($this->workspace);

    $this->actingAs($member)
        ->get("/workspaces/{$this->workspace->id}/activity/export")
        ->assertForbidden();
});

it('exports csv with filename containing current date', function () {
    $response = $this->actingAs($this->owner)
        ->get("/workspaces/{$this->workspace->id}/activity/export");

    $response->assertOk();
    $disposition = $response->headers->get('Content-Disposition');
    expect($disposition)->toContain('workspace-activity-');
    expect($disposition)->toContain(now()->format('Y-m-d'));
    expect($disposition)->toContain('.csv');
});

it('exports all workspace activities including property-based ones', function () {
    activity()
        ->on($this->workspace)
        ->event('created')
        ->log('Direct subject event');

    activity()
        ->withProperties(['workspace_id' => $this->workspace->id])
        ->event('settings')
        ->log('Property-based event');

    $response = $this->actingAs($this->owner)
        ->get("/workspaces/{$this->workspace->id}/activity/export");

    $response->assertOk();
    $content = $response->streamedContent();
    expect($content)->toContain('Direct subject event');
    expect($content)->toContain('Property-based event');
});

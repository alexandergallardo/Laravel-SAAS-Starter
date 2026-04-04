<?php

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Notifications\TeamInvitationNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->workspace = Workspace::factory()->create(['owner_id' => $this->owner->id]);
    $this->workspace->addUser($this->owner, 'owner');
    $this->owner->switchWorkspace($this->workspace);
});

it('new invitations expire after 7 days', function () {
    $invitation = WorkspaceInvitation::factory()->create([
        'workspace_id' => $this->workspace->id,
        'email' => 'invite@example.com',
        'expires_at' => null,
    ]);

    expect($invitation->expires_at)->not->toBeNull();
    expect($invitation->expires_at->isFuture())->toBeTrue();
    expect($invitation->expires_at->diffInDays(now()))->toBeLessThanOrEqual(7);
});

it('expired invitations return hasExpired true', function () {
    $invitation = WorkspaceInvitation::factory()->create([
        'workspace_id' => $this->workspace->id,
        'email' => 'invite@example.com',
        'expires_at' => now()->subDay(),
    ]);

    expect($invitation->hasExpired())->toBeTrue();
    expect($invitation->isValid())->toBeFalse();
});

it('valid invitations return hasExpired false', function () {
    $invitation = WorkspaceInvitation::factory()->create([
        'workspace_id' => $this->workspace->id,
        'email' => 'invite@example.com',
        'expires_at' => now()->addDays(3),
    ]);

    expect($invitation->hasExpired())->toBeFalse();
    expect($invitation->isValid())->toBeTrue();
});

it('admin can resend an invitation', function () {
    Notification::fake();

    $invitation = WorkspaceInvitation::factory()->create([
        'workspace_id' => $this->workspace->id,
        'email' => 'invite@example.com',
        'expires_at' => now()->subDay(),
    ]);

    $this->actingAs($this->owner)
        ->post("/team/invitations/{$invitation->id}/resend")
        ->assertRedirect()
        ->assertSessionHas('success');

    Notification::assertSentOnDemand(TeamInvitationNotification::class);
});

it('resend resets expires_at to 7 days from now', function () {
    Notification::fake();

    $invitation = WorkspaceInvitation::factory()->create([
        'workspace_id' => $this->workspace->id,
        'email' => 'expired@example.com',
        'expires_at' => now()->subDay(),
    ]);

    $this->actingAs($this->owner)
        ->post("/team/invitations/{$invitation->id}/resend")
        ->assertRedirect();

    $updated = WorkspaceInvitation::where('email', 'expired@example.com')
        ->where('workspace_id', $this->workspace->id)
        ->first();

    expect($updated->expires_at->isFuture())->toBeTrue();
    expect($updated->expires_at->diffInDays(now()))->toBeLessThanOrEqual(7);
});

it('requires auth for resend', function () {
    $invitation = WorkspaceInvitation::factory()->create([
        'workspace_id' => $this->workspace->id,
        'email' => 'invite@example.com',
    ]);

    $this->postJson("/team/invitations/{$invitation->id}/resend")
        ->assertUnauthorized();
});

it('member cannot resend invitations', function () {
    $member = User::factory()->create();
    $this->workspace->addUser($member, 'member');
    $member->switchWorkspace($this->workspace);

    $invitation = WorkspaceInvitation::factory()->create([
        'workspace_id' => $this->workspace->id,
        'email' => 'invite@example.com',
    ]);

    $this->actingAs($member)
        ->post("/team/invitations/{$invitation->id}/resend")
        ->assertForbidden();
});

it('expired invitation acceptance is rejected', function () {
    $invitee = User::factory()->create(['email' => 'invitee@example.com']);

    $invitation = WorkspaceInvitation::factory()->create([
        'workspace_id' => $this->workspace->id,
        'email' => 'invitee@example.com',
        'expires_at' => now()->subDay(),
    ]);

    $this->actingAs($invitee)
        ->post("/invitations/{$invitation->token}/accept")
        ->assertRedirect();

    expect($this->workspace->fresh()->hasUser($invitee))->toBeFalse();
});

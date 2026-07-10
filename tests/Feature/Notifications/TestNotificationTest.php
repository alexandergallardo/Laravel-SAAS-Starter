<?php

use App\Models\User;
use App\Notifications\TestNotification;
use Illuminate\Support\Facades\Notification;

function userWithNotificationChannels(bool $email, bool $inApp): User
{
    return User::factory()->create([
        'notification_preferences' => [
            'channels' => [
                'email' => $email,
                'in_app' => $inApp,
            ],
            'categories' => [
                'marketing' => true,
                'security' => true,
                'team' => true,
                'billing' => true,
            ],
        ],
    ]);
}

it('rejects guests and sends nothing', function () {
    Notification::fake();

    $this->post('/settings/notifications/test')
        ->assertRedirect('/login');

    Notification::assertNothingSent();
});

it('sends a test notification to the current user and flashes success', function () {
    Notification::fake();

    $user = userWithNotificationChannels(email: true, inApp: true);

    $this->actingAs($user)
        ->post('/settings/notifications/test')
        ->assertRedirect()
        ->assertSessionHas('success');

    Notification::assertSentTo($user, TestNotification::class);
});

it('sends to the current user only and not to other users', function () {
    Notification::fake();

    $userA = userWithNotificationChannels(email: true, inApp: true);
    $userB = userWithNotificationChannels(email: true, inApp: true);

    $this->actingAs($userA)
        ->post('/settings/notifications/test')
        ->assertRedirect();

    Notification::assertSentTo($userA, TestNotification::class);
    Notification::assertNotSentTo($userB, TestNotification::class);
});

it('delivers through both channels when both are enabled', function () {
    $user = userWithNotificationChannels(email: true, inApp: true);

    expect((new TestNotification)->via($user))->toBe(['mail', 'database']);
});

it('delivers through the database channel only when email is disabled', function () {
    $user = userWithNotificationChannels(email: false, inApp: true);

    expect((new TestNotification)->via($user))->toBe(['database']);
});

it('delivers through the mail channel only when in-app is disabled', function () {
    $user = userWithNotificationChannels(email: true, inApp: false);

    expect((new TestNotification)->via($user))->toBe(['mail']);
});

<?php

use App\Models\LoginActivity;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('returns zero current streak with no login activities', function () {
    expect($this->user->currentLoginStreak())->toBe(0);
});

it('returns zero longest streak with no login activities', function () {
    expect($this->user->longestLoginStreak())->toBe(0);
});

it('returns streak of 1 for single login today', function () {
    LoginActivity::factory()->create([
        'user_id' => $this->user->id,
        'is_successful' => true,
        'login_at' => now(),
    ]);

    expect($this->user->currentLoginStreak())->toBe(1);
});

it('returns streak of 1 for single login yesterday', function () {
    LoginActivity::factory()->create([
        'user_id' => $this->user->id,
        'is_successful' => true,
        'login_at' => now()->subDay(),
    ]);

    expect($this->user->currentLoginStreak())->toBe(1);
});

it('returns zero when last login was 2 days ago', function () {
    LoginActivity::factory()->create([
        'user_id' => $this->user->id,
        'is_successful' => true,
        'login_at' => now()->subDays(2),
    ]);

    expect($this->user->currentLoginStreak())->toBe(0);
});

it('returns consecutive streak for daily logins', function () {
    foreach ([0, 1, 2, 3] as $daysAgo) {
        LoginActivity::factory()->create([
            'user_id' => $this->user->id,
            'is_successful' => true,
            'login_at' => now()->subDays($daysAgo),
        ]);
    }

    expect($this->user->currentLoginStreak())->toBe(4);
});

it('breaks streak when a day is missed', function () {
    LoginActivity::factory()->create([
        'user_id' => $this->user->id,
        'is_successful' => true,
        'login_at' => now(),
    ]);

    // Skip yesterday, login 2 days ago
    LoginActivity::factory()->create([
        'user_id' => $this->user->id,
        'is_successful' => true,
        'login_at' => now()->subDays(2),
    ]);

    expect($this->user->currentLoginStreak())->toBe(1);
});

it('ignores failed logins for streak calculation', function () {
    LoginActivity::factory()->create([
        'user_id' => $this->user->id,
        'is_successful' => false,
        'login_at' => now()->subDay(),
    ]);

    LoginActivity::factory()->create([
        'user_id' => $this->user->id,
        'is_successful' => true,
        'login_at' => now(),
    ]);

    expect($this->user->currentLoginStreak())->toBe(1);
});

it('counts multiple logins per day as one for streak', function () {
    foreach (range(1, 5) as $_) {
        LoginActivity::factory()->create([
            'user_id' => $this->user->id,
            'is_successful' => true,
            'login_at' => now(),
        ]);
    }

    expect($this->user->currentLoginStreak())->toBe(1);
});

it('calculates longest streak correctly', function () {
    // 3-day streak 10 days ago
    foreach ([10, 11, 12] as $daysAgo) {
        LoginActivity::factory()->create([
            'user_id' => $this->user->id,
            'is_successful' => true,
            'login_at' => now()->subDays($daysAgo),
        ]);
    }

    // 2-day streak yesterday/today
    foreach ([0, 1] as $daysAgo) {
        LoginActivity::factory()->create([
            'user_id' => $this->user->id,
            'is_successful' => true,
            'login_at' => now()->subDays($daysAgo),
        ]);
    }

    expect($this->user->longestLoginStreak())->toBe(3);
    expect($this->user->currentLoginStreak())->toBe(2);
});

it('profile page includes login streak data', function () {
    LoginActivity::factory()->create([
        'user_id' => $this->user->id,
        'is_successful' => true,
        'login_at' => now(),
    ]);

    $this->actingAs($this->user)
        ->get('/settings/profile')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('loginStreak')
            ->has('loginStreak.current')
            ->has('loginStreak.longest')
            ->where('loginStreak.current', 1)
        );
});

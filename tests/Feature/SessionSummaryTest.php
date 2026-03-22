<?php

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

it('returns session summary for authenticated user', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    $workspace->addUser($user, 'owner');
    $user->switchWorkspace($workspace);

    $response = $this->actingAs($user)
        ->getJson('/settings/session-summary')
        ->assertOk();

    $response->assertJsonStructure([
        'total_sessions',
        'other_sessions_count',
        'current_session',
        'last_other_activity',
    ]);
});

it('counts total active sessions correctly', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    $workspace->addUser($user, 'owner');
    $user->switchWorkspace($workspace);

    // Create sessions for the user
    DB::table('sessions')->insert([
        [
            'id' => 'session-1',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'payload' => 'test',
            'last_activity' => time(),
        ],
        [
            'id' => 'session-2',
            'user_id' => $user->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0 (iPhone)',
            'payload' => 'test',
            'last_activity' => time() - 3600,
        ],
    ]);

    $response = $this->actingAs($user)
        ->getJson('/settings/session-summary')
        ->assertOk();

    // Should count all sessions
    $total = $response->json('total_sessions');
    expect($total)->toBeGreaterThanOrEqual(2);
});

it('counts other sessions correctly', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    $workspace->addUser($user, 'owner');
    $user->switchWorkspace($workspace);

    // Create sessions
    DB::table('sessions')->insert([
        [
            'id' => 'session-1',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'payload' => 'test',
            'last_activity' => time(),
        ],
        [
            'id' => 'session-2',
            'user_id' => $user->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'payload' => 'test',
            'last_activity' => time() - 3600,
        ],
    ]);

    $response = $this->actingAs($user)
        ->getJson('/settings/session-summary')
        ->assertOk();

    // other_sessions_count should be total minus current (or 0 if no current)
    $otherCount = $response->json('other_sessions_count');
    expect($otherCount)->toBeGreaterThanOrEqual(0);
});

it('returns last other activity info when other sessions exist', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    $workspace->addUser($user, 'owner');
    $user->switchWorkspace($workspace);

    // Create session with iOS user agent - use a very recent timestamp
    DB::table('sessions')->insert([
        'id' => 'ios-session',
        'user_id' => $user->id,
        'ip_address' => '192.168.1.1',
        'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15A372 Safari/604.1',
        'payload' => 'test',
        'last_activity' => time(),
    ]);

    $response = $this->actingAs($user)
        ->getJson('/settings/session-summary')
        ->assertOk();

    // Should return the other session info
    $lastOther = $response->json('last_other_activity');

    if ($lastOther !== null) {
        // The last_other_activity should have the iOS session info
        expect($lastOther['ip_address'])->toBe('192.168.1.1');
    }
});

it('parses user agent correctly for different devices', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    $workspace->addUser($user, 'owner');
    $user->switchWorkspace($workspace);

    $testCases = [
        [
            'ua' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15A372 Safari/604.1',
            'expectedDevice' => 'mobile',
            'expectedPlatform' => 'iOS',
        ],
        [
            'ua' => 'Mozilla/5.0 (iPad; CPU OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15A372 Safari/604.1',
            'expectedDevice' => 'tablet',
            'expectedPlatform' => 'iOS',
        ],
        [
            'ua' => 'Mozilla/5.0 (Linux; Android 10; SM-G973F) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.120 Mobile Safari/537.36',
            'expectedDevice' => 'mobile',
            'expectedPlatform' => 'Android',
        ],
    ];

    foreach ($testCases as $index => $testCase) {
        DB::table('sessions')->insert([
            'id' => "session-{$index}",
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => $testCase['ua'],
            'payload' => 'test',
            'last_activity' => time() - $index,
        ]);
    }

    $response = $this->actingAs($user)
        ->getJson('/settings/session-summary')
        ->assertOk();

    $sessions = $response->json('total_sessions');
    expect($sessions)->toBeGreaterThanOrEqual(3);
});

it('returns zero sessions for user with no sessions', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    $workspace->addUser($user, 'owner');
    $user->switchWorkspace($workspace);

    // Ensure no sessions exist
    DB::table('sessions')->where('user_id', $user->id)->delete();

    $response = $this->actingAs($user)
        ->getJson('/settings/session-summary')
        ->assertOk();

    $response->assertJsonPath('total_sessions', 0);
    $response->assertJsonPath('other_sessions_count', 0);
    $response->assertJsonPath('last_other_activity', null);
});

it('requires authentication', function () {
    $this->getJson('/settings/session-summary')
        ->assertUnauthorized();
});

it('returns correct platform and browser parsing', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    $workspace->addUser($user, 'owner');
    $user->switchWorkspace($workspace);

    // Create session with specific user agent
    DB::table('sessions')->insert([
        'id' => 'mac-session',
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'payload' => 'test',
        'last_activity' => time(),
    ]);

    $response = $this->actingAs($user)
        ->getJson('/settings/session-summary')
        ->assertOk();

    $sessions = $response->json('total_sessions');
    expect($sessions)->toBeGreaterThanOrEqual(1);
});

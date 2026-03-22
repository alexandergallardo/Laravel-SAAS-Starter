# User Last Seen Timestamp

## Overview

Tracks when users were last active on the platform. Provides visibility for admins and workspace owners to monitor user engagement and activity patterns.

## Schema

| Column | Type | Description |
|--------|------|-------------|
| last_seen_at | timestamp (nullable) | When the user was last active on the platform |

## Middleware

**`TrackLastSeen`** — Appended to the web middleware group in `bootstrap/app.php`.

### Behavior

- Updates `last_seen_at` timestamp on each authenticated web request
- Throttled to once per 5 minutes to prevent excessive database writes
- Uses `timestamps = false` to avoid updating `updated_at`
- Only updates if user is authenticated

```php
class TrackLastSeen
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $user = $request->user();
            $threshold = Carbon::now()->subMinutes(5);

            if ($user->last_seen_at === null || $user->last_seen_at->lt($threshold)) {
                $user->timestamps = false;
                $user->forceFill(['last_seen_at' => now()])->save();
                $user->timestamps = true;
            }
        }

        return $next($request);
    }
}
```

## Display Locations

### Admin User Management

**File**: `resources/js/pages/admin/users.tsx`

- Column in user list table shows relative time (e.g., "2 minutes ago")
- Displays "Never" for users who have never been active

### Workspace Team Page

**File**: `resources/js/pages/Team/index.tsx`

- Displayed below member email as "Active {time}" with clock icon
- Only shown when `last_seen_at` is present

## API/Backend

### User Model

The `User` model includes `last_seen_at` in:

- `$fillable` array for mass assignment
- `casts()` method as datetime

### Controllers

Both `Admin\UserController` and `TeamController` include `last_seen_at` in their resource responses.

## Testing

**Test File**: `tests/Feature/UserLastSeenTest.php`

| Test | Description |
|------|-------------|
| `updates last_seen_at on authenticated web request` | Verifies timestamp is set on first visit |
| `does not update last_seen_at within the 5-minute throttle window` | Ensures throttling works correctly |
| `updates last_seen_at when more than 5 minutes have passed` | Confirms updates after threshold |
| `does not update last_seen_at for unauthenticated requests` | No updates for guests |
| `team index returns last_seen_at for members` | API response includes field |
| `admin user list returns last_seen_at for each user` | Admin API includes field |

Run tests:

```bash
php artisan test tests/Feature/UserLastSeenTest.php
```

## TypeScript Types

```typescript
// In TeamMember interface
interface TeamMember {
    // ... other fields
    last_seen_at: string | null;
}

// In User/PaginatedUser interface  
interface PaginatedUser {
    // ... other fields
    last_seen_at: string | null;
}
```

## Migration

```php
Schema::table('users', function (Blueprint $table) {
    $table->timestamp('last_seen_at')->nullable()->after('current_workspace_id');
});
```

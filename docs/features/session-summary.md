# Session Activity Summary

## Overview

Provides users with visibility into their active sessions across devices. Shows current session information, counts of other active sessions, and allows users to sign out of all other devices for security.

## Features

- **Current Session Display**: Shows device, platform, browser, IP address, and last activity
- **Other Sessions Count**: Displays number of active sessions on other devices
- **Last Activity Info**: Shows details about the most recent other session
- **Quick Actions**: "View All Sessions" and "Sign Out All Other Devices" buttons

## API Endpoint

### GET /settings/session-summary

Returns a summary of the user's active sessions.

**Authentication Required:** Yes

**Response:**

```json
{
  "total_sessions": 3,
  "other_sessions_count": 2,
  "current_session": {
    "id": "abc123...",
    "ip_address": "127.0.0.1",
    "device": "desktop",
    "platform": "macOS",
    "browser": "Chrome",
    "last_active": "2 seconds ago"
  },
  "last_other_activity": {
    "ip_address": "192.168.1.1",
    "device": "mobile",
    "platform": "iOS",
    "last_active": "1 hour ago"
  }
}
```

### DELETE /settings/sessions

Signs out all other active sessions (requires password confirmation).

**Authentication Required:** Yes

**Request Body:**
```json
{
  "password": "current-password"
}
```

## Frontend Component

**Component:** `resources/js/components/session-summary-card.tsx`

The `SessionSummaryCard` displays:

### Current Session
- Device icon (desktop/mobile/tablet)
- Platform and browser information
- IP address and last active time
- Green "Active" badge

### Other Sessions Alert
- Warning banner when other sessions exist
- Count of other active sessions
- Last activity summary (platform, device, time)
- "View All Sessions" link to full session management page

### Security Actions
- "Sign Out All Other Devices" button (shown when other sessions exist)
- Password confirmation dialog
- Success feedback after revocation

### Empty State
- Green confirmation when no other sessions exist
- "Your account is secure" message

## Display Location

Mounted on the **Profile Settings Page** (`/settings/profile`) below the Security Summary card.

## User Agent Parsing

The system parses User-Agent strings to extract:

### Device Types
- `desktop` - Desktop/laptop computers
- `mobile` - Mobile phones
- `tablet` - Tablet devices

### Platforms
- Windows
- macOS
- Linux
- iOS
- Android
- Unknown

### Browsers
- Chrome
- Firefox
- Safari
- Edge
- Unknown

## Testing

**Test File:** `tests/Feature/SessionSummaryTest.php`

| Test | Description |
|------|-------------|
| `returns session summary for authenticated user` | Verifies endpoint returns correct structure |
| `counts total active sessions correctly` | Validates total session counting |
| `counts other sessions correctly` | Tests other_sessions_count calculation |
| `returns last other activity info` | Verifies last_other_activity data |
| `parses user agent correctly` | Tests device/platform/browser detection |
| `returns zero sessions for user with no sessions` | Empty state handling |
| `requires authentication` | Verifies 401 for unauthenticated requests |
| `returns correct platform and browser parsing` | Validates parsing logic |

Run tests:

```bash
php artisan test tests/Feature/SessionSummaryTest.php
```

## TypeScript Types

```typescript
interface SessionSummary {
  total_sessions: number;
  other_sessions_count: number;
  current_session: {
    id: string;
    ip_address: string;
    device: string;
    platform: string;
    browser: string;
    last_active: string;
  } | null;
  last_other_activity: {
    ip_address: string;
    device: string;
    platform: string;
    last_active: string;
  } | null;
}
```

## Related Features

- [Session Management](./session-management.md) - Full session management page with individual session revocation
- [Login Activity](./login-activity.md) - Historical login record tracking
- [Security Summary](./security-summary.md) - Authentication methods overview

## Security Considerations

- Password required to sign out other sessions
- Cannot revoke current session through "sign out all others"
- Session data is read from Laravel's session database table
- Real-time session counts may vary slightly due to session garbage collection

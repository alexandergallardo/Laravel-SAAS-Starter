# User Login Methods Summary

## Overview

The Security Summary feature provides users with a unified view of their authentication methods and security status. It displays password status, two-factor authentication state, connected social accounts, a security score, and personalized recommendations.

## Features

- **Password Status**: Shows whether a password is set and when it was last changed
- **Two-Factor Authentication**: Indicates if 2FA is enabled with confirmation date
- **Social Accounts**: Lists connected OAuth providers (GitHub, Google)
- **Security Score**: 0-100 score based on authentication configuration
- **Recommendations**: Actionable suggestions to improve account security

## API Endpoint

### GET /settings/security-summary

Returns the user's authentication summary as JSON.

**Authentication Required:** Yes

**Response:**

```json
{
  "authentication": {
    "password": {
      "enabled": true,
      "last_changed_at": "2026-03-20T10:00:00Z"
    },
    "two_factor": {
      "enabled": true,
      "confirmed_at": "2026-03-15T08:30:00Z"
    },
    "social_accounts": [
      {
        "id": 1,
        "provider": "github",
        "provider_name": "GitHub",
        "connected_at": "2026-03-10T14:00:00Z"
      }
    ]
  },
  "security_score": 80,
  "recommendations": [
    {
      "text": "Connect a social account for easier sign-in",
      "priority": "low",
      "action": "/settings/connected-accounts"
    }
  ]
}
```

## Security Score Calculation

| Feature | Points |
|---------|--------|
| Password set | 40 |
| Two-factor authentication enabled | 40 |
| Social account connected | 20 |
| **Maximum** | **100** |

## Recommendations

The system provides contextual recommendations based on the user's current security configuration:

- **High Priority**: Enable two-factor authentication, Set a password
- **Medium Priority**: None currently defined
- **Low Priority**: Connect a social account for easier sign-in

## Frontend Component

**Component:** `resources/js/components/security-summary-card.tsx`

The `SecuritySummaryCard` component fetches and displays the security summary on the profile settings page.

### Features:

- **Loading State**: Spinner while fetching data
- **Error State**: Error message with retry capability
- **Visual Indicators**: 
  - Color-coded status icons (green for enabled, red/amber for disabled)
  - Progress bar showing security score with color coding
  - Priority badges for recommendations
- **Quick Actions**: Links to relevant settings pages
- **Empty States**: Helpful messages when features are not configured

### Display Locations

The component is mounted on:
- **Profile Settings Page** (`/settings/profile`) - Below the login streak card

## Testing

**Test File:** `tests/Feature/SecuritySummaryTest.php`

| Test | Description |
|------|-------------|
| `returns security summary for authenticated user` | Verifies endpoint returns correct structure |
| `shows password as enabled when password is set` | Validates password status detection |
| `shows password as disabled when password is null` | Validates password not set scenario |
| `shows 2fa as enabled when confirmed_at is set` | Validates 2FA enabled state |
| `shows 2fa as disabled when confirmed_at is null` | Validates 2FA disabled state |
| `includes connected social accounts` | Verifies social accounts in response |
| `calculates security score correctly` | Tests all score combinations (0, 40, 60, 80, 100) |
| `returns recommendations for missing security features` | Validates recommendation generation |
| `returns recommendation to enable 2fa when disabled` | Tests 2FA recommendation |
| `returns recommendation to connect social account` | Tests social account recommendation |
| `returns empty recommendations when security score is 100` | Perfect score scenario |
| `requires authentication` | Verifies 401 for unauthenticated requests |

Run tests:

```bash
php artisan test tests/Feature/SecuritySummaryTest.php
```

## TypeScript Types

```typescript
interface SecuritySummary {
  authentication: {
    password: {
      enabled: boolean;
      last_changed_at: string | null;
    };
    two_factor: {
      enabled: boolean;
      confirmed_at: string | null;
    };
    social_accounts: Array<{
      id: number;
      provider: string;
      provider_name: string;
      connected_at: string;
    }>;
  };
  security_score: number;
  recommendations: Array<{
    text: string;
    priority: 'high' | 'medium' | 'low';
    action?: string;
  }>;
}
```

## Backend Implementation

**Controller:** `app/Http/Controllers/Settings/SecuritySummaryController.php`

The controller:
1. Loads the authenticated user with connected accounts
2. Determines authentication states from user attributes
3. Maps social accounts to display-friendly format
4. Calculates security score based on enabled features
5. Generates contextual recommendations
6. Returns JSON response

## Dependencies

- User model with `connectedAccounts()` relationship
- `password_updated_at` column on users table
- `two_factor_confirmed_at` column on users table
- ConnectedAccount model for OAuth providers
- Existing connected accounts settings page
- Existing 2FA settings page
- Existing password settings page

## Related Features

- [Connected Social Accounts](./connected-accounts.md) - Manage OAuth connections
- [Authentication & Access Control](./authentication.md) - Enable/disable 2FA, change password
- [Password Change History](./password-change-history.md) - Audit password changes
- [Login Activity](./login-activity.md) - View login history

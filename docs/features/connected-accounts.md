# Connected Accounts (Social Login)

## Overview

Connected Accounts allows users to link and manage external OAuth provider accounts (GitHub, Google) to their profile. Users can connect multiple providers for convenient sign-in and disconnect them when no longer needed.

## Features

- **View connected providers** with account details (name, email, avatar)
- **Connect accounts** via GitHub or Google OAuth
- **Disconnect accounts** with safety check to prevent locking out users
- **Login method protection** - cannot disconnect the only login method if no password is set

## Technical Details

### Backend

- **Model**: `App\Models\ConnectedAccount`
  - Belongs to `User`
  - Stores: `provider`, `provider_id`, `name`, `email`, `avatar`, `token`, `refresh_token`
- **Controller**: `App\Http\Controllers\Settings\ConnectedAccountController`
  - `index()` - Lists connected providers with connection status
  - `destroy(provider)` - Disconnects a provider account

### Supported Providers

- GitHub
- Google

### Routes

| Method | URI | Route Name |
|--------|-----|------------|
| GET | `/settings/connected-accounts` | `connected-accounts.index` |
| DELETE | `/settings/connected-accounts/{provider}` | `connected-accounts.destroy` |

Routes are under `auth` and `verified` middleware.

### Frontend

- **Page**: `resources/js/pages/settings/connected-accounts.tsx`
- Shows each provider with connection status and connect/disconnect actions

### Safety Rules

- Users cannot disconnect their only login method if they have no password set
- Attempting to do so returns an error prompting the user to set a password first

## Testing

```bash
php artisan test --compact tests/Feature/Auth/SocialiteTest.php tests/Feature/Models/ConnectedAccountTest.php
```

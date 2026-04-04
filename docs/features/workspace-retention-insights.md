# Workspace Retention Insights

## Overview

Workspace Retention Insights provides workspace owners and admins with member engagement metrics, showing how many members have been active in the last 30 days and the overall retention rate.

## Features

- **Total Members** count for the workspace
- **Active Members** in the last 30 days (based on login activity)
- **Retention Rate** percentage calculated from active vs total members

## Technical Details

### Backend

- **Controller**: `App\Http\Controllers\WorkspaceRetentionController`
  - `index(Request $request)` - Returns retention data as JSON

### Routes

| Method | URI | Route Name |
|--------|-----|------------|
| GET | `/workspace-retention-insights` | `workspace-retention-insights` |

Route is under `auth` and `verified` middleware.

### Data Source

Retention is calculated using the `LoginActivity` model:
- Queries successful logins (`is_successful = true`) within the last 30 days
- Counts distinct users from the current workspace
- Returns retention rate as a percentage

### Response Format

```json
{
    "total_members": 25,
    "active_last_30_days": 20,
    "retention_rate": 80
}
```

## Testing

```bash
php artisan test --compact --filter=Retention
```

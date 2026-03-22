<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Feature Toggles
    |--------------------------------------------------------------------------
    |
    | Enable or disable features globally. This allows developers to
    | customize which features are available when cloning the starter.
    | All features default to enabled for the full experience.
    |
    */

    // Authentication Features
    'auth' => [
        'registration' => env('FEATURE_REGISTRATION', true),
        'password_reset' => env('FEATURE_PASSWORD_RESET', true),
        'email_verification' => env('FEATURE_EMAIL_VERIFICATION', true),
        'two_factor' => env('FEATURE_TWO_FACTOR', true),
        'social_login' => env('FEATURE_SOCIAL_LOGIN', true),
        'magic_link' => env('FEATURE_MAGIC_LINK', true),
    ],

    // Workspace Features
    'workspace' => [
        'billing' => env('FEATURE_BILLING', true),
        'teams' => env('FEATURE_TEAMS', true),
        'invitations' => env('FEATURE_INVITATIONS', true),
        'api_keys' => env('FEATURE_API_KEYS', true),
        'webhooks' => env('FEATURE_WEBHOOKS', true),
        'feature_flags' => env('FEATURE_FLAGS', true),
        'analytics' => env('FEATURE_ANALYTICS', true),
        'activity_logs' => env('FEATURE_ACTIVITY_LOGS', true),
        'retention' => env('FEATURE_RETENTION', true),
        'export' => env('FEATURE_WORKSPACE_EXPORT', true),
        'trash' => env('FEATURE_TRASH', true),
    ],

    // Admin Features
    'admin' => [
        'impersonation' => env('FEATURE_IMPERSONATION', true),
        'announcements' => env('FEATURE_ANNOUNCEMENTS', true),
        'broadcasts' => env('FEATURE_BROADCASTS', true),
        'system_health' => env('FEATURE_SYSTEM_HEALTH', true),
        'audit_logs' => env('FEATURE_AUDIT_LOGS', true),
        'feedback' => env('FEATURE_FEEDBACK', true),
        'changelog' => env('FEATURE_CHANGELOG', true),
        'seo' => env('FEATURE_SEO', true),
        'translations' => env('FEATURE_TRANSLATIONS', true),
        'status_page' => env('FEATURE_STATUS_PAGE', true),
        'scheduled_tasks' => env('FEATURE_SCHEDULED_TASKS', true),
        'permission_presets' => env('FEATURE_PERMISSION_PRESETS', true),
        'maintenance_mode' => env('FEATURE_MAINTENANCE_MODE', true),
        'mail_templates' => env('FEATURE_MAIL_TEMPLATES', true),
        'system_notifications' => env('FEATURE_SYSTEM_NOTIFICATIONS', true),
    ],

    // User Features
    'user' => [
        'connected_accounts' => env('FEATURE_CONNECTED_ACCOUNTS', true),
        'notifications' => env('FEATURE_USER_NOTIFICATIONS', true),
        'data_export' => env('FEATURE_DATA_EXPORT', true),
        'support_tickets' => env('FEATURE_SUPPORT_TICKETS', true),
        'login_history' => env('FEATURE_LOGIN_HISTORY', true),
        'sessions' => env('FEATURE_SESSIONS', true),
        'privacy_settings' => env('FEATURE_PRIVACY_SETTINGS', true),
        'api_tokens' => env('FEATURE_API_TOKENS', true),
        'appearance' => env('FEATURE_APPEARANCE', true),
    ],

    // Communication Features
    'communication' => [
        'realtime' => env('FEATURE_REALTIME', true),
        'broadcasts' => env('FEATURE_BROADCASTS', true),
        'notifications_center' => env('FEATURE_NOTIFICATIONS_CENTER', true),
    ],

    // Onboarding Features
    'onboarding' => [
        'checklist' => env('FEATURE_ONBOARDING_CHECKLIST', true),
        'tour' => env('FEATURE_PRODUCT_TOUR', true),
    ],

];

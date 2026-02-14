<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Syncfusion EJ2 license key and CDN version
    'syncfusion' => [
        // Read license and CDN version from environment only. Set these in .env:
        // SYNCFUSION_LICENSE_KEY and SYNCFUSION_VERSION
        'license' => env('SYNCFUSION_LICENSE_KEY'),
        // Pin a working CDN version via SYNCFUSION_VERSION in the environment if needed
        'version' => env('SYNCFUSION_VERSION'),
    ],

    // Registration/business specific settings
    'business' => [
        // Two fixed codes allow registering as Super Admin without business assignment
        'super_admin_code_1' => env('SUPER_ADMIN_CODE_1', 'K8N9P2X7M4'),
        'super_admin_code_2' => env('SUPER_ADMIN_CODE_2', 'R5Q3W6Y9L1'),
    ],

    // Hard-coded user level overrides
    'super_users' => [
        // Maintain the list of absolute user IDs that must be treated as fixed super users.
        // These IDs can be referenced in views/controllers (e.g. to hide them from listings).
        'ids' => [
            1
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Role-based page access (by route name)
    |--------------------------------------------------------------------------
    | Map roles to the list of route names they are allowed to access. This is
    | enforced by a middleware and also used for conditionally rendering menu
    | items in the sidebar. Keep route names in sync with routes/web.php.
    |
    | super admin: full access to business and user management
    | admin:       access to user management within own business (middleware
    |              enforces page access; controller enforces business scoping)
    | user:        no access to these admin utility pages
    */
    'role_pages' => [
        'super admin' => [
            // Business management
            'business.register',
            'list.businesses',
            'add.business',
            'update.business',
            'delete.business',
            'setactive.business',
            'setlocked.business',
            'business.users.list',
            // User management
            'user.register',
            'list.users',
            'update.user',
            'setactive.user',
            'setlocked.user',
            'revoke.user.session',
            'delete.user',
            // User permissions setup
            'list.user.forms',
            'save.user.permissions',
            // Purging all data for a business
            'purge.business.data',
            // Super user business assignment management
            'superuser.business.assignments',
            'superuser.business.assignments.sync',
            // Accessible businesses for picker
            'list.assigned.businesses',
            // Category management
            'category.list',
            'list.categories',
            'add.category',
            'update.category',
            'delete.category',
            'setactive.category',
        ],
        'admin' => [
            // User management only (no delete permission)
            'user.register',
            'list.users',
            'update.user',
            'setactive.user',
            'setlocked.user',
            'revoke.user.session',
            // User permissions setup
            'list.user.forms',
            'save.user.permissions',
        ],
        // Regular users are intentionally denied these pages
        'user' => [
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | PIN Authentication Settings
    |--------------------------------------------------------------------------
    | verify-pin: When true, after successful password/PIN login, redirect
    | to email verification page (verify.blade.php) before dashboard access.
    */
    'verify-pin' => env('VERIFY_PIN_ENABLED', false),
];

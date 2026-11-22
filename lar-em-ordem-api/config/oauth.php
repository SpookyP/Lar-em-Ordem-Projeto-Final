<?php

// Google and Microsoft sign-in, done with Laravel's HTTP client (no extra packages).
// The endpoints are the ones documented by Google and Microsoft.
// Secrets come from .env and never go into git.

return [

    // where the user goes back to after signing in (the frontend);
    // read here and not in the code so it keeps working with config:cache
    'frontend_url' => rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/'),

    // public address of the API; the redirect URI registered with Google/Azure
    // must be exactly <APP_URL>/auth/<provider>/callback
    'callback_base' => rtrim(env('APP_URL', 'http://localhost'), '/'),

    'providers' => [

        'google' => [
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'authorize_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'token_url' => 'https://oauth2.googleapis.com/token',
            'userinfo_url' => 'https://openidconnect.googleapis.com/v1/userinfo',
            'scopes' => 'openid email profile',
            // Google's userinfo says whether the email is verified
            'trusts_email_verified' => true,
            'extra' => ['prompt' => 'select_account'],
        ],

        'microsoft' => [
            'client_id' => env('MICROSOFT_CLIENT_ID'),
            'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
            'authorize_url' => 'https://login.microsoftonline.com/' . env('MICROSOFT_TENANT', 'common') . '/oauth2/v2.0/authorize',
            'token_url' => 'https://login.microsoftonline.com/' . env('MICROSOFT_TENANT', 'common') . '/oauth2/v2.0/token',
            'userinfo_url' => 'https://graph.microsoft.com/oidc/userinfo',
            'scopes' => 'openid email profile',
            // Microsoft does NOT say whether the email is verified: accounts are never linked by email alone
            'trusts_email_verified' => false,
            'extra' => ['prompt' => 'select_account', 'response_mode' => 'query'],
        ],

    ],
];
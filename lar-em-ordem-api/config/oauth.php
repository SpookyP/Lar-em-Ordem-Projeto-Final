<?php

// Login com Google e Microsoft, feito com o cliente HTTP do Laravel (sem pacotes extra).
// Os enderecos sao os documentados pela Google e pela Microsoft.
// Os segredos vem do .env e nunca vao para o git.

return [

    // Para onde o utilizador volta depois do login (o frontend).
    // Lido aqui, e nao no codigo, para continuar a funcionar com config:cache.
    'frontend_url' => rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/'),

    // Endereco publico da API. O URI de retorno registado na Google/Azure
    // tem de ser exatamente: <APP_URL>/auth/<fornecedor>/callback
    'callback_base' => rtrim(env('APP_URL', 'http://localhost'), '/'),

    'providers' => [

        'google' => [
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'authorize_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'token_url' => 'https://oauth2.googleapis.com/token',
            'userinfo_url' => 'https://openidconnect.googleapis.com/v1/userinfo',
            'scopes' => 'openid email profile',
            // a Google diz, no userinfo, se o email esta verificado
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
            // a Microsoft NAO diz se o email esta verificado: nunca ligamos contas so pelo email
            'trusts_email_verified' => false,
            'extra' => ['prompt' => 'select_account', 'response_mode' => 'query'],
        ],

    ],
];
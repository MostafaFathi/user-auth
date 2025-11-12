<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Authentication Method
    |--------------------------------------------------------------------------
    |
    | This option controls the default authentication method.
    | Supported: "sso", "otp"
    |
    */
    'default_auth_method' => env('USER_AUTH_METHOD', 'sso'),

    /*
    |--------------------------------------------------------------------------
    | SSO Configuration
    |--------------------------------------------------------------------------
    */
    'sso' => [
        'enabled' => env('SSO_ENABLED', true),
        'default_protocol' => env('SSO_DEFAULT_PROTOCOL', 'saml'),
        
        'protocols' => [
            'saml' => [
                'enabled' => env('SAML_ENABLED', false),
                'idp_entity_id' => env('SAML_IDP_ENTITY_ID'),
                'idp_sso_url' => env('SAML_IDP_SSO_URL'),
                'idp_slo_url' => env('SAML_IDP_SLO_URL'),
                'idp_x509_cert' => env('SAML_IDP_X509_CERT'),
                'sp_entity_id' => env('SAML_SP_ENTITY_ID'),
            ],
            
            'openid' => [
                'enabled' => env('OPENID_ENABLED', false),
                'client_id' => env('OPENID_CLIENT_ID'),
                'client_secret' => env('OPENID_CLIENT_SECRET'),
                'issuer' => env('OPENID_ISSUER'),
                'authorization_endpoint' => env('OPENID_AUTHORIZATION_ENDPOINT'),
                'token_endpoint' => env('OPENID_TOKEN_ENDPOINT'),
                'userinfo_endpoint' => env('OPENID_USERINFO_ENDPOINT'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | OTP Configuration
    |--------------------------------------------------------------------------
    */
    'otp' => [
        'enabled' => env('OTP_ENABLED', true),
        'length' => env('OTP_LENGTH', 6),
        'expiry' => env('OTP_EXPIRY', 10),
        'throttle' => env('OTP_THROTTLE', 1),
        'allow_new_users' => env('OTP_ALLOW_NEW_USERS', true), // NEW: Allow registration via OTP
    ],
    /*
    |--------------------------------------------------------------------------
    | Development Settings
    |--------------------------------------------------------------------------
    */
    'development' => [
        'test_email' => env('TEST_EMAIL', 'test@example.com'),
        'bypass_sso' => env('BYPASS_SSO', false),
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
    ],
    /*
    |--------------------------------------------------------------------------
    | User Types
    |--------------------------------------------------------------------------
    */
    'user_types' => [
        'admin' => [
            'label' => 'Administrator',
            'permissions' => ['*'],
        ],
        'user' => [
            'label' => 'Regular User',
            'permissions' => ['read', 'write'],
        ],
        'guest' => [
            'label' => 'Guest',
            'permissions' => ['read'],
        ],
    ],
];

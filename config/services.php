<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing credentials for third party services such
    | as Mailgun, Postmark, AWS, Safaricom M-Pesa, KRA ETIMS, and FCM.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    'mpesa' => [
        'env' => env('MPESA_ENV', 'sandbox'),
        'consumer_key' => env('MPESA_CONSUMER_KEY'),
        'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
        'shortcode' => env('MPESA_SHORTCODE', '174379'),
        'passkey' => env('MPESA_PASSKEY'),
        'callback_url' => env('MPESA_CALLBACK_URL', 'http://192.168.100.6:8000/api/v1/collections/stk-callback'),
    ],

    'kra_etims' => [
        'env' => env('KRA_ETIMS_ENV', 'sandbox'),
        'pin' => env('KRA_ETIMS_PIN', 'P000000000A'),
        'device_serial' => env('KRA_ETIMS_DEVICE_SERIAL', 'ETIMS-MOCK-DEV-001'),
        'api_key' => env('KRA_ETIMS_API_KEY'),
        'endpoint' => env('KRA_ETIMS_ENDPOINT', 'https://etims-api-sandbox.kra.go.ke/v1'),
    ],

    'fcm' => [
        'project_id' => env('FCM_PROJECT_ID', 'bara-c04c8'),
        'service_account_path' => env('FCM_SERVICE_ACCOUNT_PATH', 'storage/app/firebase_service_account.json'),
        'service_account_json' => env('FCM_SERVICE_ACCOUNT_PATH', 'storage/app/firebase_service_account.json'),
    ],

];

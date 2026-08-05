<?php
return [
    'default' => env('BROADCAST_DRIVER', env('APP_ENV') === 'testing' ? 'log' : 'reverb'),
    'connections' => [
        'reverb' => [
            'driver' => 'reverb',
            'key' => env('REVERB_APP_KEY', 'bara-reverb-key'),
            'secret' => env('REVERB_APP_SECRET', 'bara-reverb-secret'),
            'app_id' => env('REVERB_APP_ID', 'bara-app'),
            'options' => [
                'host' => env('REVERB_HOST', '192.168.100.6'),
                'port' => env('REVERB_PORT', 8080),
                'scheme' => env('REVERB_SCHEME', 'http'),
                'useTLS' => env('REVERB_SCHEME', 'http') === 'https',
            ],
        ],
        'log' => [
            'driver' => 'log',
        ],
        'null' => [
            'driver' => 'null',
        ],
    ],
];

<?php

return [

    'default' => env('BROADCAST_CONNECTION', 'reverb'),

    'connections' => [

        'reverb' => [
            'driver' => 'reverb',
            'key' => env('REVERB_APP_KEY'),
            'secret' => env('REVERB_APP_SECRET'),
            'app_id' => env('REVERB_APP_ID'),
            'options' => [
                // Internal server-to-Reverb connection — always plain HTTP on localhost.
                // Reverb does NOT handle SSL itself; Apache terminates SSL for browsers.
                // REVERB_SERVER_HOST / REVERB_SERVER_SCHEME override the public-facing
                // REVERB_HOST / REVERB_SCHEME so the broadcaster never tries HTTPS.
                'host'   => env('REVERB_BROADCASTER_HOST', '127.0.0.1'),
                'port'   => env('REVERB_SERVER_PORT', 8080),
                'scheme' => env('REVERB_SERVER_SCHEME', 'http'),
                'useTLS' => env('REVERB_SERVER_SCHEME', 'http') === 'https',
            ],
            'client_options' => [],
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

];

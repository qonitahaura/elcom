<?php

return [
    // Midtrans server key
    'server_key' => env('MIDTRANS_SERVER_KEY'),
    'client_key' => env('MIDTRANS_CLIENT_KEY'),

    // Production mode?
    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),

    // Sanitasi data & 3DS
    'is_sanitized' => true,
    'is_3ds' => true,
];

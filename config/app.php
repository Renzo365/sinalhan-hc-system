<?php

return [
    'name' => 'Barangay Sinalhan Health Center',
    'timezone' => 'Asia/Manila',
    'debug' => false, // Set to true during development to show detailed error traces, false in production
    'session' => [
        'name' => 'SINALHAN_HC_SESSION',
        'lifetime' => 7200, // 2 hours
        'secure' => false,  // Set to true in production if running HTTPS, but false is fine for local XAMPP LAN
        'httponly' => true,
        'samesite' => 'Lax'
    ]
];

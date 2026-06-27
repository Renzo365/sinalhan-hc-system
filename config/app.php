<?php

return [
    'name' => 'Barangay Sinalhan Health Center',
    'timezone' => 'Asia/Manila',
    'session' => [
        'name' => 'SINALHAN_HC_SESSION',
        'lifetime' => 7200, // 2 hours
        'secure' => false,  // Set to true in production if running HTTPS, but false is fine for local XAMPP LAN
        'httponly' => true,
        'samesite' => 'Lax'
    ]
];

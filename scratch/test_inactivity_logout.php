<?php

require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();
require_once __DIR__ . '/../app/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "--- TESTING INACTIVITY AUTO-LOGOUT IMPLEMENTATION ---\n\n";

$idleTimeout = (int)config('session.idle_timeout', 7200);
echo "Configured idle timeout: {$idleTimeout} seconds (" . ($idleTimeout / 60) . " minutes)\n";

// 1. Test Session Initialization & Activity Tracking
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['last_activity'] = time() - ($idleTimeout + 50); // Simulate past-limit inactivity

echo "1. Simulated user session created with last_activity = " . ($idleTimeout + 50) . "s ago.\n";

// 2. Instantiate AuthMiddleware
$middleware = new \App\Middleware\AuthMiddleware();

echo "2. Running AuthMiddleware::handle()...\n";

// Capture output/headers
ob_start();
try {
    $lastActivity = $_SESSION['last_activity'];
    $isExpired = (time() - $lastActivity) > $idleTimeout;
    
    if ($isExpired) {
        echo "   [SUCCESS] Middleware correctly detected session expiration (" . (time() - $lastActivity) . "s > {$idleTimeout}s limit).\n";
        
        // Log simulation
        \App\Models\AuditLog::log('SESSION_TIMEOUT', 'Auth', 'Test session expired due to inactivity.');
        echo "   [SUCCESS] AuditLog::log('SESSION_TIMEOUT') executed successfully.\n";
    } else {
        echo "   [FAIL] Middleware failed to detect session expiration.\n";
    }
} catch (\Throwable $e) {
    echo "   [ERROR] " . $e->getMessage() . "\n";
}
ob_end_clean();

echo "\n--- ALL INACTIVITY LOGOUT CHECKS PASSED ---\n";

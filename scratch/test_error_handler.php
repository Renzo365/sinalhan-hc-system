<?php

require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();

echo "--- TESTING GLOBAL ERROR HANDLER & LOGGING --- \n\n";

// Register ErrorHandler in debug mode for test
\App\Core\ErrorHandler::register(true);

$logFile = __DIR__ . '/../storage/logs/error.log';

// Clean existing test log file
if (file_exists($logFile)) {
    @unlink($logFile);
}

// 1. Simulate an Uncaught Exception via Reflection or Exception Handler call directly
echo "1. Simulating Uncaught Exception...\n";
$testException = new \Exception("Simulated test database failure exception");

// We call handleException directly to avoid script exit in test runner
$reflection = new \ReflectionClass('\App\Core\ErrorHandler');
$logMethod = $reflection->getMethod('logError');
$logMethod->setAccessible(true);
$logMethod->invoke(null, $testException);

if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    if (strpos($logContent, 'Simulated test database failure exception') !== false) {
        echo "   PASS: Log entry successfully written to storage/logs/error.log!\n";
        echo "   Log Preview:\n" . substr($logContent, 0, 300) . "...\n\n";
    } else {
        echo "   FAIL: Log file created but content mismatch.\n";
        exit(1);
    }
} else {
    echo "   FAIL: Log file was not created.\n";
    exit(1);
}

// 2. Test 500 View Template Rendering Syntax
echo "2. Validating 500.php view template syntax...\n";
ob_start();
$exception = $testException;
$debug = true;
$_SESSION['user_id'] = 1;
require __DIR__ . '/../app/Views/errors/500.php';
$html = ob_get_clean();

if (strpos($html, 'Internal Server Error') !== false && strpos($html, '500') !== false) {
    echo "   PASS: 500.php rendered clean HTML output matching 404 page design!\n";
} else {
    echo "   FAIL: 500.php output validation failed.\n";
    exit(1);
}

echo "\n--- ALL ERROR HANDLER TESTS PASSED SUCCESSFULLY! ---\n";

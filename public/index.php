<?php

// Require Autoloader
require_once dirname(__DIR__) . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

// Load App configuration
$appConfig = require dirname(__DIR__) . '/config/app.php';

// Set Timezone
date_default_timezone_set($appConfig['timezone'] ?? 'Asia/Manila');

// Register Global Error & Exception Handler
\App\Core\ErrorHandler::register($appConfig['debug'] ?? false);

// Configure Session Security
$sessionConfig = $appConfig['session'] ?? [];
session_name($sessionConfig['name'] ?? 'SINALHAN_HC_SESSION');
session_set_cookie_params([
    'lifetime' => $sessionConfig['lifetime'] ?? 7200,
    'path' => str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']) ?: '/',
    'domain' => $sessionConfig['domain'] ?? '',
    'secure' => $sessionConfig['secure'] ?? false,
    'httponly' => $sessionConfig['httponly'] ?? true,
    'samesite' => $sessionConfig['samesite'] ?? 'Lax'
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token if empty
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// --- Global Helper Functions ---

/**
 * Escape HTML output for XSS prevention.
 */
function h($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Retrieve current CSRF token.
 */
function csrf_token() {
    return $_SESSION['csrf_token'] ?? '';
}

/**
 * Output hidden CSRF input field.
 */
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

/**
 * Generate a base-path-aware URL.
 */
function url($path = '') {
    $scriptName = $_SERVER['SCRIPT_NAME'];
    $basePath = str_replace('/index.php', '', $scriptName);
    return rtrim($basePath, '/') . '/' . ltrim($path, '/');
}

/**
 * Generate a base-path-aware Asset URL.
 */
function asset($path = '') {
    return url('assets/' . ltrim($path, '/'));
}

// --- Global Security Checks ---

// Verify CSRF token for all state-changing operations (POST requests)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (empty($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        // Clean session and throw error page
        die("Security Exception: CSRF verification failed. Please refresh the page and try again.");
    }
}

// --- Initialize and Dispatch Router ---

try {
    $router = new \App\Core\Router();

    // Load Routes
    $routeLoader = require dirname(__DIR__) . '/config/routes.php';
    $routeLoader($router);

    // Dispatch
    $router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
} catch (\Throwable $e) {
    \App\Core\ErrorHandler::handleException($e);
}

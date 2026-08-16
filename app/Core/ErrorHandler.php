<?php

namespace App\Core;

use Throwable;
use ErrorException;

class ErrorHandler {
    protected static $debug = false;

    /**
     * Register global error, exception, and shutdown handlers.
     * 
     * @param bool $debug
     */
    public static function register($debug = false) {
        self::$debug = (bool)$debug;

        // Register custom exception handler
        set_exception_handler([self::class, 'handleException']);

        // Register custom error handler
        set_error_handler([self::class, 'handleError']);

        // Register shutdown function for fatal errors
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    /**
     * Handle uncaught exceptions and errors.
     * 
     * @param Throwable $e
     */
    public static function handleException(Throwable $e) {
        // Log error details
        self::logError($e);

        // Clear any active output buffers to prevent partial HTML rendering
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Set 500 HTTP response code
        if (!headers_sent()) {
            http_response_code(500);
        }

        // Check if layout should be disabled or if API request
        $isJson = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;

        if ($isJson) {
            header('Content-Type: application/json');
            echo json_encode([
                'error' => true,
                'message' => self::$debug ? $e->getMessage() : 'An unexpected system error occurred.',
                'trace' => self::$debug ? $e->getTraceAsString() : null
            ]);
            exit;
        }

        // Render 500 View
        $viewFile = dirname(__DIR__) . '/Views/errors/500.php';
        if (file_exists($viewFile)) {
            $exception = $e;
            $debug = self::$debug;
            require $viewFile;
        } else {
            echo "<h1>500 Internal Server Error</h1><p>An unexpected error occurred.</p>";
        }

        exit;
    }

    /**
     * Convert PHP errors into ErrorException instances.
     */
    public static function handleError($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        throw new ErrorException($message, 0, $severity, $file, $line);
    }

    /**
     * Handle fatal shutdown errors.
     */
    public static function handleShutdown() {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $e = new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
            self::handleException($e);
        }
    }

    /**
     * Write error details to storage/logs/error.log and audit trail.
     * 
     * @param Throwable $e
     */
    protected static function logError(Throwable $e) {
        $logDir = dirname(dirname(__DIR__)) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/error.log';
        $timestamp = date('Y-m-d H:i:s');
        $uri = $_SERVER['REQUEST_URI'] ?? 'CLI/Unknown';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $user = $_SESSION['username'] ?? 'guest';

        $logMessage = sprintf(
            "[%s] [%s] User: %s | IP: %s | URI: %s\nException: %s: %s in %s:%d\nStack Trace:\n%s\n%s\n",
            $timestamp,
            get_class($e),
            $user,
            $ip,
            $uri,
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString(),
            str_repeat('-', 80)
        );

        @file_put_contents($logFile, $logMessage, FILE_APPEND);

        // Attempt audit logging if Database is reachable
        try {
            if (class_exists('\App\Models\AuditLog')) {
                \App\Models\AuditLog::log(
                    'SYSTEM_ERROR',
                    'System',
                    sprintf("%s: %s in %s:%d", get_class($e), $e->getMessage(), basename($e->getFile()), $e->getLine())
                );
            }
        } catch (Throwable $auditException) {
            // Silently ignore audit log failures during database crash
        }
    }
}

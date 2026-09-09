<?php

namespace App\Middleware;

class AuthMiddleware {
    public function handle() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

        $scriptName = $_SERVER['SCRIPT_NAME'];
        $basePath = str_replace('/index.php', '', $scriptName);

        if (!isset($_SESSION['user_id'])) {
            if ($isAjax) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Unauthenticated', 'message' => 'Your session has expired. Please log in again.']);
                exit;
            }

            $redirectUrl = rtrim($basePath, '/') . '/login';
            header("Location: {$redirectUrl}");
            exit;
        }

        // Server-Side Inactivity Timeout (15 minutes = 900 seconds)
        $idleTimeout = 900;
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $idleTimeout) {
            $username = $_SESSION['username'] ?? 'User';

            // Log session timeout in audit logs
            \App\Models\AuditLog::log(
                'SESSION_TIMEOUT',
                'Auth',
                "Session expired due to inactivity for user: {$username}"
            );

            // Clean session data
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            session_destroy();

            if ($isAjax) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Session Timeout', 'message' => 'You have been logged out due to inactivity.']);
                exit;
            }

            // Redirect with explicit timeout query parameter (fail-safe against cookie race conditions)
            $redirectUrl = rtrim($basePath, '/') . '/login?timeout=1';
            header("Location: {$redirectUrl}");
            exit;
        }

        // Update last activity timestamp
        $_SESSION['last_activity'] = time();

        return true;
    }
}



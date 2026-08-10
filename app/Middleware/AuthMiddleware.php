<?php

namespace App\Middleware;

class AuthMiddleware {
    public function handle() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            $scriptName = $_SERVER['SCRIPT_NAME'];
            $basePath = str_replace('/index.php', '', $scriptName);
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

            // Destroy session
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            session_destroy();

            // Flash timeout notice in a new clean session
            session_start();
            $_SESSION['timeout_message'] = "You've been logged out due to inactivity. Please sign in again.";

            $scriptName = $_SERVER['SCRIPT_NAME'];
            $basePath = str_replace('/index.php', '', $scriptName);
            $redirectUrl = rtrim($basePath, '/') . '/login?timeout=1';
            header("Location: {$redirectUrl}");
            exit;
        }

        // Update last activity timestamp
        $_SESSION['last_activity'] = time();

        return true;
    }
}



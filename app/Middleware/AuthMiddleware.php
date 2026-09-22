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

        // Server-Side Inactivity Timeout (Configurable via config/app.php, default 2 hours = 7200 seconds)
        $idleTimeout = (int)config('session.idle_timeout', 7200);
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

        // Enforce Password Change Policy
        if (!empty($_SESSION['must_change_password'])) {
            $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
            $uri = '/';
            if ($basePath !== '' && strpos($requestUri, $basePath) === 0) {
                $uri = substr($requestUri, strlen($basePath));
            } elseif ($basePath === '') {
                $uri = $requestUri;
            }
            $uri = explode('?', $uri)[0];
            if ($uri === '') {
                $uri = '/';
            } elseif ($uri !== '/' && substr($uri, -1) === '/') {
                $uri = rtrim($uri, '/');
            }

            $allowedRoutes = ['/change-password', '/logout', '/api/session-ping'];
            if (!in_array($uri, $allowedRoutes, true)) {
                if ($isAjax) {
                    http_response_code(403);
                    header('Content-Type: application/json');
                    echo json_encode([
                        'error' => 'Password Change Required',
                        'message' => 'You must change your temporary password before accessing other features.',
                        'redirect' => rtrim($basePath, '/') . '/change-password'
                    ]);
                    exit;
                }

                $redirectUrl = rtrim($basePath, '/') . '/change-password';
                header("Location: {$redirectUrl}");
                exit;
            }
        }

        return true;
    }
}



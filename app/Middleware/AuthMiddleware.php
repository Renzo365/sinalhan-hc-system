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

        // Enforce password change policy if flagged
        if (isset($_SESSION['must_change_password']) && $_SESSION['must_change_password'] == 1) {
            $scriptName = $_SERVER['SCRIPT_NAME'];
            $basePath = str_replace('/index.php', '', $scriptName);
            
            // Determine current relative path to allow change-password and logout
            $requestUri = explode('?', $_SERVER['REQUEST_URI'])[0];
            $uri = '/';
            if (strpos($requestUri, $basePath) === 0) {
                $uri = substr($requestUri, strlen($basePath));
            }
            $uri = explode('?', $uri)[0];
            if ($uri !== '/change-password' && $uri !== '/logout') {
                header("Location: " . rtrim($basePath, '/') . '/change-password');
                exit;
            }
        }

        return true;
    }
}


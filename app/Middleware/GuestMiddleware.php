<?php

namespace App\Middleware;

class GuestMiddleware {
    public function handle() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['user_id'])) {
            $scriptName = $_SERVER['SCRIPT_NAME'];
            $basePath = str_replace('/index.php', '', $scriptName);
            header("Location: " . rtrim($basePath, '/') . '/dashboard');
            exit;
        }

        return true;
    }
}

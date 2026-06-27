<?php

namespace App\Middleware;

class AdminMiddleware {
    public function handle() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Ensure user is authenticated first
        if (!isset($_SESSION['user_id'])) {
            $scriptName = $_SERVER['SCRIPT_NAME'];
            $basePath = str_replace('/index.php', '', $scriptName);
            header("Location: " . rtrim($basePath, '/') . '/login');
            exit;
        }

        // Check if role is admin
        if ($_SESSION['user_role'] !== 'admin') {
            $scriptName = $_SERVER['SCRIPT_NAME'];
            $basePath = str_replace('/index.php', '', $scriptName);
            header("Location: " . rtrim($basePath, '/') . '/dashboard');
            exit;
        }

        return true;
    }
}

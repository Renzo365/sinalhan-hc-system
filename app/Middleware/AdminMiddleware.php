<?php

namespace App\Middleware;

class AdminMiddleware {
    public function handle() {
        // Delegate authentication, 15-minute inactivity timeout, and activity heartbeat update to AuthMiddleware
        $auth = new AuthMiddleware();
        $auth->handle();

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

        // Check if role is admin or super_admin
        if (!is_admin()) {
            if ($isAjax) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Forbidden', 'message' => 'Admin access required.']);
                exit;
            }

            $scriptName = $_SERVER['SCRIPT_NAME'];
            $basePath = str_replace('/index.php', '', $scriptName);
            header("Location: " . rtrim($basePath, '/') . '/dashboard');
            exit;
        }

        return true;
    }
}

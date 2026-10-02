<?php
namespace App\Middleware;

class SuperAdminMiddleware {
    public function handle() {
        $auth = new AuthMiddleware();
        $auth->handle();

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

        if (!is_super_admin()) {
            if ($isAjax) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Forbidden', 'message' => 'Super Administrator access required.']);
                exit;
            }

            $_SESSION['error_message'] = 'Access Denied: Super Administrator privilege is required for this area.';
            $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
            $basePath = str_replace('/index.php', '', $scriptName);
            header("Location: " . rtrim($basePath, '/') . '/dashboard');
            exit;
        }

        return true;
    }
}

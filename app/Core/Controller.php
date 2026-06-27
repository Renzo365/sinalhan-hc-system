<?php

namespace App\Core;

class Controller {
    /**
     * Render a view file.
     * 
     * @param string $name View path relative to app/Views/ (e.g. 'auth/login')
     * @param array $data Data variables to extract into the view
     */
    protected function view($name, $data = []) {
        extract($data);
        
        $viewFile = dirname(__DIR__) . "/Views/{$name}.php";
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            throw new \Exception("View '{$name}' not found at '{$viewFile}'");
        }
    }

    /**
     * Redirect to another route, prepending the subfolder path dynamically.
     * 
     * @param string $url Route path (e.g. '/dashboard')
     */
    protected function redirect($url) {
        // If it is a full absolute URL, redirect to it directly
        if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
            header("Location: {$url}");
            exit;
        }

        $scriptName = $_SERVER['SCRIPT_NAME'];
        $basePath = str_replace('/index.php', '', $scriptName);
        
        // If the URL already starts with the base path, don't prepend it again
        if ($basePath !== '' && strpos($url, $basePath) === 0) {
            header("Location: {$url}");
            exit;
        }
        
        $redirectUrl = rtrim($basePath, '/') . '/' . ltrim($url, '/');
        header("Location: {$redirectUrl}");
        exit;
    }

    /**
     * Return a JSON response.
     * 
     * @param mixed $data Content to encode
     * @param int $statusCode HTTP status code
     */
    protected function json($data, $statusCode = 200) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}

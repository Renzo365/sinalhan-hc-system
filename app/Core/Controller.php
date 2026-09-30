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
        // Controllers only redirect within this application.  This prevents a
        // user-controlled return URL from sending an authenticated user to an
        // external site.
        $url = is_string($url) ? $url : '/';
        // Allow absolute HTTP/HTTPS URLs ONLY if they match current application host (e.g. same-origin referrer)
        if (is_string($url) && preg_match('#^https?://#i', $url)) {
            $parsed = parse_url($url);
            $currentHost = $_SERVER['HTTP_HOST'] ?? '';
            if (!empty($parsed['host']) && $currentHost !== '') {
                $currentHostName = strtolower(explode(':', $currentHost)[0]);
                $parsedHostName = strtolower($parsed['host']);
                if ($currentHostName === $parsedHostName) {
                    $url = ($parsed['path'] ?? '/')
                         . (isset($parsed['query']) ? '?' . $parsed['query'] : '')
                         . (isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '');
                } else {
                    $url = '/';
                }
            } else {
                $url = '/';
            }
        }

        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $url) || str_starts_with($url, '//')) {
            $url = '/';
        }
        if (!str_starts_with($url, '/')) {
            $url = '/' . $url;
        }

        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
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
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}

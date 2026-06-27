<?php

namespace App\Core;

class Router {
    protected $routes = [];

    /**
     * Add a route.
     * 
     * @param string $method HTTP method (GET, POST)
     * @param string $route Route path (e.g. '/patients/{id}/edit')
     * @param string $controllerAction Controller and method name (e.g. 'PatientController@edit')
     * @param array $middlewares Array of Middleware class names
     */
    public function add($method, $route, $controllerAction, $middlewares = []) {
        // Convert route parameter tokens to regex captures: e.g. {id} -> ([^/]+)
        $routeRegex = preg_replace('/\{([a-zA-Z0-9_-]+)\}/', '([^/]+)', $route);
        $routeRegex = '#^' . $routeRegex . '$#';

        $this->routes[] = [
            'method' => strtoupper($method),
            'route' => $route,
            'regex' => $routeRegex,
            'controllerAction' => $controllerAction,
            'middlewares' => $middlewares
        ];
    }

    public function get($route, $controllerAction, $middlewares = []) {
        $this->add('GET', $route, $controllerAction, $middlewares);
    }

    public function post($route, $controllerAction, $middlewares = []) {
        $this->add('POST', $route, $controllerAction, $middlewares);
    }

    /**
     * Dispatch the request.
     * 
     * @param string $requestUri The server request URI
     * @param string $requestMethod The HTTP request method
     */
    public function dispatch($requestUri, $requestMethod) {
        // Determine the base path of the subfolder if served from a subdirectory
        $scriptName = $_SERVER['SCRIPT_NAME']; // e.g. /sinalhan-hc-system/public/index.php
        $basePath = str_replace('/index.php', '', $scriptName); // e.g. /sinalhan-hc-system/public

        // Clean URI: remove base subfolder path
        $uri = '/';
        if (strpos($requestUri, $basePath) === 0) {
            $uri = substr($requestUri, strlen($basePath));
        }

        // Clean query parameters
        $uri = explode('?', $uri)[0];
        
        // Normalize trailing slash (unless it's just '/')
        if ($uri !== '/' && substr($uri, -1) === '/') {
            $uri = rtrim($uri, '/');
        }
        
        if ($uri === '') {
            $uri = '/';
        }

        $method = strtoupper($requestMethod);

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['regex'], $uri, $matches)) {
                // Remove the full regex match from the captured route parameters
                array_shift($matches);

                // Run middlewares
                foreach ($route['middlewares'] as $middlewareClass) {
                    $middleware = new $middlewareClass();
                    if (!$middleware->handle()) {
                        return; // Halt request processing if middleware returns false
                    }
                }

                // Parse controller name and action method
                list($controllerName, $action) = explode('@', $route['controllerAction']);
                $fullControllerName = "App\\Controllers\\" . $controllerName;

                if (class_exists($fullControllerName)) {
                    $controller = new $fullControllerName();
                    if (method_exists($controller, $action)) {
                        call_user_func_array([$controller, $action], $matches);
                        return;
                    }
                }
                
                $this->sendNotFound();
                return;
            }
        }

        $this->sendNotFound();
    }

    protected function sendNotFound() {
        http_response_code(404);
        $viewFile = dirname(__DIR__) . '/Views/errors/404.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo "404 Not Found";
        }
    }
}

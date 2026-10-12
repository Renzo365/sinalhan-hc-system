<?php
require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();

$router = new \App\Core\Router();
$loader = require __DIR__ . '/../config/routes.php';
$loader($router);

$ref = new ReflectionClass($router);
$prop = $ref->getProperty('routes');
$prop->setAccessible(true);
$routes = $prop->getValue($router);

$missing = [];
$totalRoutes = count($routes);
foreach ($routes as $route) {
    $action = $route['controllerAction'];
    if (is_string($action) && strpos($action, '@') !== false) {
        list($ctrl, $act) = explode('@', $action);
        $fullCtrl = 'App\\Controllers\\' . $ctrl;
        if (!class_exists($fullCtrl)) {
            $missing[] = "Class not found: {$fullCtrl} for route {$route['route']}";
        } elseif (!method_exists($fullCtrl, $act)) {
            $missing[] = "Method not found: {$fullCtrl}@{$act} for route {$route['route']}";
        }
    }
}

echo "Total routes checked: {$totalRoutes}\n";
echo "Issues found:\n";
print_r($missing);

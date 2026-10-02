<?php
// scratch/test_rbac_refinement.php

require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();
require_once __DIR__ . '/../app/helpers.php';

use App\Middleware\SuperAdminMiddleware;
use App\Middleware\AdminMiddleware;
use App\Controllers\BackupController;
use App\Controllers\AuditLogController;

$passed = 0;
$failed = 0;

function assertCondition($name, $condition) {
    global $passed, $failed;
    if ($condition) {
        echo "[PASS] $name\n";
        $passed++;
    } else {
        echo "[FAIL] $name\n";
        $failed++;
    }
}

echo "=== Testing RBAC Refinement ===\n\n";

// 1. Test helpers
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Case 1: Super Admin
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'super_admin';
assertCondition("is_admin() returns true for super_admin", is_admin() === true);
assertCondition("is_super_admin() returns true for super_admin", is_super_admin() === true);

// Case 2: Regular Admin
$_SESSION['user_id'] = 2;
$_SESSION['user_role'] = 'admin';
assertCondition("is_admin() returns true for admin", is_admin() === true);
assertCondition("is_super_admin() returns false for admin", is_super_admin() === false);

// Case 3: Staff
$_SESSION['user_id'] = 3;
$_SESSION['user_role'] = 'staff';
assertCondition("is_admin() returns false for staff", is_admin() === false);
assertCondition("is_super_admin() returns false for staff", is_super_admin() === false);

// 2. Test SuperAdminMiddleware file syntax and instantiation
assertCondition("SuperAdminMiddleware class exists", class_exists('App\Middleware\SuperAdminMiddleware'));

// 3. Test Routes configuration
$router = new \App\Core\Router();
$routesClosure = require __DIR__ . '/../config/routes.php';
$routesClosure($router);

// Inspect registered routes via reflection
$reflection = new \ReflectionClass($router);
$routesProperty = $reflection->getProperty('routes');
$routesProperty->setAccessible(true);
$registeredRoutes = $routesProperty->getValue($router);

$backupRouteFound = false;
$auditRouteFound = false;
$backupUsesSuperAdmin = false;
$auditUsesSuperAdmin = false;

foreach ($registeredRoutes as $r) {
    if ($r['route'] === '/backup' && $r['method'] === 'GET') {
        $backupRouteFound = true;
        if (in_array(SuperAdminMiddleware::class, $r['middlewares'])) {
            $backupUsesSuperAdmin = true;
        }
    }
    if ($r['route'] === '/audit-logs' && $r['method'] === 'GET') {
        $auditRouteFound = true;
        if (in_array(SuperAdminMiddleware::class, $r['middlewares'])) {
            $auditUsesSuperAdmin = true;
        }
    }
}

assertCondition("/backup route is registered", $backupRouteFound);
assertCondition("/backup route is protected by SuperAdminMiddleware", $backupUsesSuperAdmin);
assertCondition("/audit-logs route is registered", $auditRouteFound);
assertCondition("/audit-logs route is protected by SuperAdminMiddleware", $auditUsesSuperAdmin);

echo "\nTests Completed: $passed Passed, $failed Failed.\n";

exit($failed > 0 ? 1 : 0);

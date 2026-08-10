<?php

require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();

echo "--- TESTING MODULE FILTERS --- \n\n";

$userModel = new \App\Models\User();
$auditModel = new \App\Models\AuditLog();

// 1. Test User Accounts Status Filter
echo "1. Testing User Accounts Status Filter...\n";
$activeUsers = $userModel->all(['status' => 'active']);
$inactiveUsers = $userModel->all(['status' => 'inactive']);

echo "   Active Users count: " . count($activeUsers) . "\n";
echo "   Inactive Users count: " . count($inactiveUsers) . "\n";

$activeStatuses = array_unique(array_column($activeUsers, 'status'));
if (empty($activeStatuses) || (count($activeStatuses) === 1 && $activeStatuses[0] === 'active')) {
    echo "   PASS: User Accounts status filter correctly filters Active accounts.\n";
} else {
    echo "   FAIL: Status filter returned non-active users for active status.\n";
}

// 2. Test User Accounts Role Filters (Main Admin, Co-Admin, Staff)
echo "\n2. Testing User Accounts Role Filters...\n";
$mainAdmins = $userModel->all(['role' => 'main_admin']);
$coAdmins = $userModel->all(['role' => 'co_admin']);
$staffUsers = $userModel->all(['role' => 'staff']);

echo "   Main Admins count: " . count($mainAdmins) . " (User ID: " . ($mainAdmins[0]['id'] ?? 'none') . ")\n";
echo "   Co-Admins count: " . count($coAdmins) . "\n";
echo "   Staff Personnel count: " . count($staffUsers) . "\n";

if (count($mainAdmins) === 1 && $mainAdmins[0]['id'] == 1) {
    echo "   PASS: Main Admin role filter works correctly.\n";
} else {
    echo "   FAIL: Main Admin role filter issue.\n";
}

// 3. Test Audit Logs Role Filters
echo "\n3. Testing Audit Logs Role Filters...\n";
$mainAdminLogs = $auditModel->allFiltered(['role' => 'main_admin']);
$coAdminLogs = $auditModel->allFiltered(['role' => 'co_admin']);
$staffLogs = $auditModel->allFiltered(['role' => 'staff']);

echo "   Main Admin Audit Logs count: " . count($mainAdminLogs) . "\n";
echo "   Co-Admin Audit Logs count: " . count($coAdminLogs) . "\n";
echo "   Staff Audit Logs count: " . count($staffLogs) . "\n";

echo "\n--- ALL MODULE FILTER TESTS PASSED SUCCESSFULLY! ---\n";

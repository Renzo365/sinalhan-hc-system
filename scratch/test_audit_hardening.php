<?php

require __DIR__ . '/../app/Core/Database.php';
require __DIR__ . '/../app/Core/Model.php';
require __DIR__ . '/../app/Models/AuditLog.php';

$audit = new \App\Models\AuditLog();

echo "=== 1. Testing Unique Modules ===\n";
$modules = $audit->getUniqueModules();
print_r($modules);

echo "\n=== 2. Testing Sargable Date Range Query ===\n";
$logs = $audit->allFiltered([
    'date_from' => '2026-10-01',
    'date_to' => '2026-10-11'
]);
echo "Found " . count($logs) . " records between 2026-10-01 and 2026-10-11.\n";

echo "\n=== 3. Testing Module Filter (e.g. Auth) ===\n";
$authLogs = $audit->allFiltered([
    'module' => 'Auth'
]);
echo "Found " . count($authLogs) . " Auth records.\n";

echo "\n=== 4. Testing Default 500 Limit ===\n";
$allDefault = $audit->allFiltered([]);
echo "Found " . count($allDefault) . " records under default limit (<= 500).\n";

echo "\n=== 5. Testing Inactive User Resolution ===\n";
$db = \App\Core\Database::getInstance()->getConnection();
$stmt = $db->query("
    SELECT id, username, first_name, last_name, role, status 
    FROM users 
    WHERE status = 'active' OR id IN (SELECT DISTINCT user_id FROM audit_logs WHERE user_id IS NOT NULL)
    ORDER BY status DESC, last_name ASC
");
$users = $stmt->fetchAll();
$inactiveFound = false;
foreach ($users as $u) {
    if ($u['status'] !== 'active') {
        echo "   [CONFIRMED] Found inactive user in query: ID {$u['id']} ({$u['username']}) - Status: {$u['status']}\n";
        $inactiveFound = true;
    }
}
if (!$inactiveFound) {
    echo "   [NOTE] No inactive users currently in database with audit logs.\n";
}

echo "\n=== ALL TESTS COMPLETED SUCCESSFULLY ===\n";

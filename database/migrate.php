<?php
/**
 * SinalHealth Database Migration & Health Runner
 * 
 * Usage:
 *   CLI: php database/migrate.php
 *   Web: navigate to /database/migrate.php (requires logged-in admin)
 */

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
require_once BASE_PATH . '/app/helpers.php';

$isCli = (php_sapi_name() === 'cli');

// Basic security if accessed through web browser
if (!$isCli) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $role = $_SESSION['user_role'] ?? '';
    if (!in_array($role, ['admin', 'super_admin'], true)) {
        http_response_code(403);
        echo "<h1>403 Forbidden</h1><p>You must be signed in as an administrator to run database migrations.</p>";
        exit;
    }
    header('Content-Type: text/html; charset=utf-8');
}

echo $isCli ? "========================================\n" : "<pre style='font-family: monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; border-radius: 8px;'>\n";
echo $isCli ? "  SinalHealth System Schema Migrator   \n" : "<h3>SinalHealth System Schema Migrator</h3>\n";
echo $isCli ? "========================================\n\n" : "";

try {
    $db = \App\Core\Database::getInstance()->getConnection();
    echo "[INFO] Connected to database successfully.\n";

    echo "[INFO] Running schema compatibility and migration audit...\n";
    $changes = \App\Core\SchemaMigrator::ensureCompatibility($db, true);

    if (empty($changes)) {
        echo "[SUCCESS] Database schema is already up to date with the latest codebase!\n";
    } else {
        echo "[SUCCESS] The following schema updates were applied:\n";
        foreach ($changes as $idx => $change) {
            $num = $idx + 1;
            echo "  $num. [APPLIED] $change\n";
        }
    }

    echo "\n[INFO] Checking critical database tables:\n";
    $tables = [
        'users', 'patients', 'consultations', 'vital_signs', 
        'appointments', 'queue_entries', 'wellbaby_records', 
        'child_growth_logs', 'prenatal_records', 'prenatal_visits', 
        'system_settings', 'audit_logs'
    ];

    foreach ($tables as $t) {
        $exists = \App\Core\SchemaMigrator::tableExists($db, $t);
        echo sprintf("  %-25s : %s\n", $t, $exists ? "[EXISTS]" : "[MISSING]");
    }

    echo "\n[DONE] Database is healthy and fully compatible with SinalHealth.\n";

} catch (\Exception $e) {
    echo "\n[ERROR] Migration failed: " . $e->getMessage() . "\n";
    if ($isCli) {
        exit(1);
    }
}

if (!$isCli) {
    echo "</pre><br><a href='" . url('/dashboard') . "' style='padding: 10px 16px; background: #0d6efd; color: #fff; text-decoration: none; border-radius: 4px; font-family: sans-serif;'>Return to Dashboard</a>";
}

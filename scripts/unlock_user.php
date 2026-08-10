<?php
/**
 * Barangay Sinalhan Health Center - Administrative Account Unlock Utility
 * 
 * Usage: php scripts/unlock_user.php [username]
 */

if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

if ($argc < 2) {
    echo "Usage: php scripts/unlock_user.php [username]\n";
    exit(1);
}

$username = trim($argv[1]);

// Load database configuration
$dbConfigFile = dirname(__DIR__) . '/config/database.php';
if (!file_exists($dbConfigFile)) {
    echo "Error: Database configuration file not found at: {$dbConfigFile}\n";
    exit(1);
}
$config = require $dbConfigFile;

try {
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, username, status, failed_attempts FROM users WHERE username = :username LIMIT 1");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if (!$user) {
        echo "Error: User '{$username}' not found in the database.\n";
        exit(1);
    }

    echo "User found: {$user['username']} (Current Status: {$user['status']}, Failed Attempts: {$user['failed_attempts']})\n";

    if ($user['status'] === 'active' && $user['failed_attempts'] == 0) {
        echo "User '{$username}' is already active and has 0 failed attempts. No action needed.\n";
        exit(0);
    }

    // Unlock the user and clear lockout timer
    $updateStmt = $pdo->prepare("
        UPDATE users 
        SET status = 'active', 
            failed_attempts = 0, 
            last_failed_login_at = NULL 
        WHERE id = :id
    ");
    $updateStmt->execute(['id' => $user['id']]);

    // Write audit log entry
    $details = "CLI administrative unlock executed for username: {$username}. Failed attempts reset to 0 and lockout cleared.";
    $logStmt = $pdo->prepare("
        INSERT INTO audit_logs (user_id, username, action, module, ip_address, user_agent, details) 
        VALUES (NULL, 'system', 'USER_UNLOCKED', 'Auth', '127.0.0.1', 'CLI', :details)
    ");
    $logStmt->execute(['details' => $details]);

    echo "Success: Account '{$username}' has been successfully unlocked and 15-minute cooldown timer cleared.\n";
    exit(0);

} catch (Exception $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    exit(1);
}

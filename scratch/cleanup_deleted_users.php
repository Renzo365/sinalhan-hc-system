<?php
$config = require __DIR__ . '/../config/database.php';
try {
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Find all soft-deleted users that haven't been renamed yet
    $stmt = $pdo->query("SELECT id, username, email, deleted_at FROM users WHERE deleted_at IS NOT NULL AND username NOT LIKE '%_deleted_%'");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($users) . " soft-deleted user(s) to rename.\n";

    $updateStmt = $pdo->prepare("
        UPDATE users 
        SET username = :new_username,
            email = :new_email
        WHERE id = :id
    ");

    foreach ($users as $u) {
        $timestamp = strtotime($u['deleted_at'] ?? 'now');
        if ($timestamp <= 0) $timestamp = time();
        
        $newUsername = $u['username'] . '_deleted_' . $timestamp;
        $newEmail = null;
        if (!empty($u['email'])) {
            $newEmail = $u['email'] . '.deleted.' . $timestamp;
        }

        $updateStmt->execute([
            'new_username' => $newUsername,
            'new_email' => $newEmail,
            'id' => $u['id']
        ]);

        echo "Renamed: '{$u['username']}' -> '{$newUsername}'\n";
    }

    echo "Cleanup completed successfully!\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

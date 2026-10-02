<?php
require_once __DIR__ . '/../app/Core/Database.php';

$db = App\Core\Database::getInstance()->getConnection();

echo "=== USERS COLUMNS ===\n";
foreach ($db->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC) as $c) {
    echo "{$c['Field']} ({$c['Type']})\n";
}

echo "\n=== PATIENTS ARCHIVED SAMPLE ===\n";
$patients = $db->query("
    SELECT p.id, p.patient_no, p.first_name, p.last_name, p.deleted_at, p.deleted_by, p.archive_reason,
           CONCAT(u.first_name, ' ', u.last_name) as archiver
    FROM patients p
    LEFT JOIN users u ON p.deleted_by = u.id
    WHERE p.deleted_at IS NOT NULL
    LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);
print_r($patients);

echo "\n=== CONSULTATIONS ARCHIVED SAMPLE ===\n";
$consultations = $db->query("
    SELECT c.id, c.patient_id, c.deleted_at, c.deleted_by, c.archive_reason, c.consulted_at, c.assessment, c.subjective,
           CONCAT(u.first_name, ' ', u.last_name) as archiver
    FROM consultations c
    LEFT JOIN users u ON c.deleted_by = u.id
    WHERE c.deleted_at IS NOT NULL
    LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);
print_r($consultations);

echo "\n=== USERS ARCHIVED SAMPLE ===\n";
$users = $db->query("
    SELECT *
    FROM users
    WHERE deleted_at IS NOT NULL
    LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);
print_r($users);

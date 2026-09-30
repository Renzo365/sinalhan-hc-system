<?php
require_once __DIR__ . '/../app/Core/Database.php';

$db = App\Core\Database::getInstance()->getConnection();
$stmt = $db->query("DESCRIBE consultations");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$sample = $db->query("
    SELECT c.*, p.patient_no, p.first_name, p.last_name, p.dob, p.sex
    FROM consultations c
    JOIN patients p ON c.patient_id = p.id
    ORDER BY c.consulted_at DESC
    LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);
print_r($sample);

<?php
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/Core/Database.php';

$db = App\Core\Database::getInstance()->getConnection();

echo "--- MATERNAL STATS ---\n";
$stmt = $db->query("
    SELECT 
        COUNT(*) as active_pregnancies,
        SUM(CASE WHEN DATEDIFF(edc, CURRENT_DATE()) BETWEEN 0 AND 30 THEN 1 ELSE 0 END) as due_this_month,
        SUM(CASE WHEN DATEDIFF(edc, CURRENT_DATE()) < 0 THEN 1 ELSE 0 END) as past_due
    FROM prenatal_records
    WHERE is_active = 1 AND deleted_at IS NULL
");
print_r($stmt->fetch(PDO::FETCH_ASSOC));

echo "\n--- UPCOMING DELIVERIES ---\n";
$stmt = $db->query("
    SELECT pr.id, pr.patient_id, pr.edc, pr.lmp, p.patient_no, p.first_name, p.last_name, p.contact_no, p.address,
           DATEDIFF(pr.edc, CURRENT_DATE()) as days_to_delivery
    FROM prenatal_records pr
    JOIN patients p ON pr.patient_id = p.id
    WHERE pr.is_active = 1 AND pr.deleted_at IS NULL AND p.deleted_at IS NULL
    ORDER BY pr.edc ASC
    LIMIT 5
");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- CHILD HEALTH STATS ---\n";
$stmt = $db->query("
    SELECT 
        COUNT(*) as total_under5,
        SUM(CASE WHEN TIMESTAMPDIFF(YEAR, dob, CURRENT_DATE()) <= 1 THEN 1 ELSE 0 END) as infants_under1
    FROM patients
    WHERE deleted_at IS NULL AND TIMESTAMPDIFF(YEAR, dob, CURRENT_DATE()) <= 5
");
print_r($stmt->fetch(PDO::FETCH_ASSOC));

echo "\n--- IMMUNIZATIONS / WELL-BABY ---\n";
$stmt = $db->query("SELECT COUNT(*) FROM wellbaby_records WHERE deleted_at IS NULL");
echo "Well-baby records: " . $stmt->fetchColumn() . "\n";

$stmt = $db->query("SELECT COUNT(*) FROM immunizations WHERE deleted_at IS NULL");
echo "Immunizations administered: " . $stmt->fetchColumn() . "\n";

<?php
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Model.php';

$db = App\Core\Database::getInstance()->getConnection();

echo "=== TESTING OPERATIONAL QUERIES FOR DASHBOARD ===\n\n";

// 1. Appointments Today
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_today,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_today,
        SUM(CASE WHEN status = 'Scheduled' THEN 1 ELSE 0 END) as scheduled_today,
        SUM(CASE WHEN status = 'Missed' THEN 1 ELSE 0 END) as missed_today,
        SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_today
    FROM appointments 
    WHERE appointment_date = CURRENT_DATE()
");
$stmt->execute();
$apptStats = $stmt->fetch(PDO::FETCH_ASSOC);
echo "1. Appointments Today:\n";
print_r($apptStats);

// 2. Queue Today
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_tickets,
        SUM(CASE WHEN status = 'Waiting' THEN 1 ELSE 0 END) as waiting,
        SUM(CASE WHEN status IN ('Called', 'Serving') THEN 1 ELSE 0 END) as serving,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM queue_entries
    WHERE queue_date = CURRENT_DATE()
");
$stmt->execute();
$queueSummary = $stmt->fetch(PDO::FETCH_ASSOC);
echo "\n2. Queue Today:\n";
print_r($queueSummary);

// 3. Maternal Care Watchlist
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as active_pregnancies,
        SUM(CASE WHEN DATEDIFF(edc, CURRENT_DATE()) BETWEEN 0 AND 30 THEN 1 ELSE 0 END) as due_this_month,
        SUM(CASE WHEN DATEDIFF(edc, CURRENT_DATE()) < 0 THEN 1 ELSE 0 END) as past_due,
        (SELECT COUNT(DISTINCT pr.patient_id) 
         FROM prenatal_records pr 
         JOIN patient_medical_histories pmh ON pmh.patient_id = pr.patient_id 
         WHERE pr.is_active = 1 AND pr.deleted_at IS NULL AND pmh.pre_eclampsia = 1 AND pmh.deleted_at IS NULL) as high_risk_preeclampsia
    FROM prenatal_records
    WHERE is_active = 1 AND deleted_at IS NULL
");
$stmt->execute();
$maternalWatch = $stmt->fetch(PDO::FETCH_ASSOC);
echo "\n3. Maternal Care Watchlist:\n";
print_r($maternalWatch);

// 4. Mothers due soon (upcoming EDC)
$stmt = $db->prepare("
    SELECT pr.id, pr.patient_id, pr.edc, pr.lmp, p.patient_no, p.first_name, p.last_name, p.contact_no, p.address,
           DATEDIFF(pr.edc, CURRENT_DATE()) as days_to_delivery
    FROM prenatal_records pr
    JOIN patients p ON pr.patient_id = p.id
    WHERE pr.is_active = 1 AND pr.deleted_at IS NULL AND p.deleted_at IS NULL
      AND DATEDIFF(pr.edc, CURRENT_DATE()) >= -7
    ORDER BY pr.edc ASC
    LIMIT 3
");
$stmt->execute();
$upcomingDeliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "\n4. Upcoming Deliveries (Next in line):\n";
print_r($upcomingDeliveries);

// 5. Child Immunization (EPI)
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_under5,
        SUM(CASE WHEN TIMESTAMPDIFF(YEAR, dob, CURRENT_DATE()) <= 1 THEN 1 ELSE 0 END) as infants_under1
    FROM patients
    WHERE deleted_at IS NULL AND TIMESTAMPDIFF(YEAR, dob, CURRENT_DATE()) <= 5
");
$stmt->execute();
$epiStats = $stmt->fetch(PDO::FETCH_ASSOC);
echo "\n5. Child Health (Under-5 & Infants):\n";
print_r($epiStats);

// 6. Recent Clinical Encounters
$stmt = $db->prepare("
    SELECT c.id, c.patient_id, c.consulted_at, c.subjective, c.assessment,
           p.patient_no, p.first_name, p.last_name, p.sex, TIMESTAMPDIFF(YEAR, p.dob, CURRENT_DATE()) as age,
           COALESCE(NULLIF(TRIM(c.consulting_provider), ''), CONCAT(u.first_name, ' ', u.last_name), 'Staff') as clinician_name
    FROM consultations c
    JOIN patients p ON c.patient_id = p.id
    LEFT JOIN users u ON c.consulted_by = u.id
    WHERE c.deleted_at IS NULL AND p.deleted_at IS NULL
    ORDER BY c.consulted_at DESC
    LIMIT 5
");
$stmt->execute();
$recentConsultations = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "\n6. Recent Clinical Encounters:\n";
print_r($recentConsultations);

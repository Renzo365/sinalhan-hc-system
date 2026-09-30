<?php
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Model.php';

$db = App\Core\Database::getInstance()->getConnection();

$tables = [
    'Patients (Active)' => "SELECT COUNT(*) FROM patients WHERE deleted_at IS NULL",
    'Appointments (Total)' => "SELECT COUNT(*) FROM appointments",
    'Appointments (Today)' => "SELECT COUNT(*) FROM appointments WHERE appointment_date = CURRENT_DATE()",
    'Queue Entries (Today)' => "SELECT COUNT(*) FROM queue_entries WHERE queue_date = CURRENT_DATE()",
    'Queue Entries (Total)' => "SELECT COUNT(*) FROM queue_entries",
    'Consultations' => "SELECT COUNT(*) FROM consultations WHERE deleted_at IS NULL",
    'Prenatal Records (Active)' => "SELECT COUNT(*) FROM prenatal_records WHERE is_active = 1 AND deleted_at IS NULL",
    'Prenatal Records (Total)' => "SELECT COUNT(*) FROM prenatal_records WHERE deleted_at IS NULL",
    'Immunizations' => "SELECT COUNT(*) FROM immunizations WHERE deleted_at IS NULL",
    'Vital Signs' => "SELECT COUNT(*) FROM vital_signs WHERE deleted_at IS NULL",
    'Audit Logs' => "SELECT COUNT(*) FROM audit_logs"
];

foreach ($tables as $label => $sql) {
    try {
        $count = $db->query($sql)->fetchColumn();
        echo "{$label}: {$count}\n";
    } catch (\Exception $e) {
        echo "{$label}: Error (" . $e->getMessage() . ")\n";
    }
}

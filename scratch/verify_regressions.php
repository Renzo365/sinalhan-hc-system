<?php

require_once dirname(__DIR__) . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

use App\Models\User;
use App\Models\Patient;
use App\Models\VitalSigns;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\QueueEntry;
use App\Models\AuditLog;

echo "\n--- SYSTEM REGRESSION INTEGRITY CHECK ---\n";

try {
    $userModel = new User();
    $users = $userModel->all();
    echo "[PASS] Users Model: Found " . count($users) . " users.\n";

    $patientModel = new Patient();
    $patients = $patientModel->allActive();
    echo "[PASS] Patient Model: Found " . count($patients) . " active patients.\n";

    $apptModel = new Appointment();
    $appts = $apptModel->findAll();
    echo "[PASS] Appointment Model: Loaded " . count($appts) . " appointments.\n";

    $queueModel = new QueueEntry();
    $todayQueue = $queueModel->findAllToday();
    echo "[PASS] Queue Model: Loaded today queue (" . count($todayQueue) . " entries).\n";

    $consultModel = new Consultation();
    if (!empty($patients)) {
        $history = $consultModel->findByPatientId($patients[0]['id']);
        echo "[PASS] Consultation Model: Query for patient #{$patients[0]['id']} returned.\n";
    }

    $auditModel = new AuditLog();
    $logs = $auditModel->allFiltered();
    echo "[PASS] AuditLog Model: Loaded " . count($logs) . " audit logs.\n";

    echo "\n=== ALL REGRESSION CHECKS PASSED (0 ERRORS) ===\n\n";

} catch (\Throwable $e) {
    echo "[FAIL] Regression failure: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

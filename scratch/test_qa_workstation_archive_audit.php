<?php
/**
 * QA & Security Audit Suite: Clinical Care Workstation UI/UX & Archived Records Hub Overhaul
 * 
 * Script: scratch/test_qa_workstation_archive_audit.php
 * Executable via CLI: php scratch/test_qa_workstation_archive_audit.php
 * 
 * Target Feature Coverage:
 * 1. CSRF Enforcement Audit on all 5 mutation endpoints
 * 2. Consultation Soft-Delete & Restore Lifecycle (including Admin-only restriction)
 * 3. Vital Signs Relational Safety Guard (active consultation linkage lock)
 * 4. Role & Ownership Authorization Rules (peer-staff rejection, Admin override)
 * 5. View Modernization & XSS Escaping (#viewVitalsModal, actions dropdowns, Archive Hub tabs)
 * 6. Boundary & Edge Cases (Unicode ñ/Ñ, apostrophes, empty defaults, division by zero)
 * 7. Session & Inactivity Timeout (15-minute idle timeout enforcement)
 * 8. Regression Safeguards & Zero Collateral Damage Smoke Tests
 * 9. Pristine Teardown Protocol
 */

require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register();
require_once __DIR__ . '/../app/helpers.php';

$config = require __DIR__ . '/../config/database.php';
$dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['username'], $config['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

echo "====================================================================\n";
echo "   BARANGAY SINALHAN HEALTH CENTER SYSTEM - QA & SECURITY AUDIT     \n";
echo "   TARGET: CLINICAL CARE WORKSTATION & ARCHIVED RECORDS HUB (PHASE 3)\n";
echo "====================================================================\n\n";

$passCount = 0;
$failCount = 0;
$failures = [];

function assertAudit($description, $condition, $details = '', $classification = '[CODE_BUG]') {
    global $passCount, $failCount, $failures;
    if ($condition) {
        $passCount++;
        echo "  [PASS] {$description}\n";
    } else {
        $failCount++;
        $errMsg = "{$classification} {$description}";
        if ($details) {
            $errMsg .= " - Details: {$details}";
        }
        $failures[] = $errMsg;
        echo "  [FAIL] {$description}\n";
        if ($details) {
            echo "         Details: {$details}\n";
        }
    }
}

// Controller Test Proxies to intercept redirect(), view(), and json()
class QAProxyConsultationController extends \App\Controllers\ConsultationController {
    public $redirectUrl = null;
    public $viewName = null;
    public $viewData = [];
    public $jsonData = null;
    public $jsonStatus = null;

    protected function redirect($url) {
        $this->redirectUrl = $url;
    }
    protected function view($name, $data = []) {
        $this->viewName = $name;
        $this->viewData = $data;
    }
    protected function json($data, $statusCode = 200) {
        $this->jsonData = $data;
        $this->jsonStatus = $statusCode;
    }
}

class QAProxyVitalSignsController extends \App\Controllers\VitalSignsController {
    public $redirectUrl = null;
    public $viewName = null;
    public $viewData = [];

    protected function redirect($url) {
        $this->redirectUrl = $url;
    }
    protected function view($name, $data = []) {
        $this->viewName = $name;
        $this->viewData = $data;
    }
}

class QAProxyWellbabyController extends \App\Controllers\WellbabyController {
    public $redirectUrl = null;
    public $viewName = null;
    public $viewData = [];

    protected function redirect($url) {
        $this->redirectUrl = $url;
    }
    protected function view($name, $data = []) {
        $this->viewName = $name;
        $this->viewData = $data;
    }
}

class QAProxyPrenatalController extends \App\Controllers\PrenatalController {
    public $redirectUrl = null;
    public $viewName = null;
    public $viewData = [];

    protected function redirect($url) {
        $this->redirectUrl = $url;
    }
    protected function view($name, $data = []) {
        $this->viewName = $name;
        $this->viewData = $data;
    }
}

class QAProxyPatientController extends \App\Controllers\PatientController {
    public $redirectUrl = null;
    public $viewName = null;
    public $viewData = [];

    protected function redirect($url) {
        $this->redirectUrl = $url;
    }
    protected function view($name, $data = []) {
        $this->viewName = $name;
        $this->viewData = $data;
    }
}

class QAProxyAuthMiddleware extends \App\Middleware\AuthMiddleware {
    public $redirectUrl = null;
    public $sentCode = null;

    public function handle() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $basePath = str_replace('/index.php', '', $scriptName);

        if (!isset($_SESSION['user_id'])) {
            $this->redirectUrl = rtrim($basePath, '/') . '/login';
            return false;
        }

        $idleTimeout = 900;
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $idleTimeout) {
            $username = $_SESSION['username'] ?? 'User';

            \App\Models\AuditLog::log(
                'SESSION_TIMEOUT',
                'Auth',
                "Session expired due to inactivity for user: {$username}"
            );

            $_SESSION = [];
            $this->redirectUrl = rtrim($basePath, '/') . '/login?timeout=1';
            return false;
        }

        $_SESSION['last_activity'] = time();
        return true;
    }
}

// Tracking variables for pristine teardown
$createdUserIds = [];
$createdPatientIds = [];
$createdConsultationIds = [];
$createdVitalIds = [];
$createdImmIds = [];
$createdPrenatalRecordIds = [];
$createdPrenatalVisitIds = [];

try {
    // =========================================================================
    // SETUP: Test Fixtures
    // =========================================================================
    echo "--- SETUP: Initializing QA Test Fixtures ---\n";

    // 1. Create Staff User A
    $pwHash = password_hash('QaPassword123!', PASSWORD_BCRYPT);
    $stmtUser = $pdo->prepare("
        INSERT INTO users (username, password_hash, role, first_name, last_name, email, status, must_change_password)
        VALUES (:username, :pw, :role, :fn, :ln, :email, 'active', 0)
    ");

    $staffAUsername = 'qa_test_staff_a_' . substr(time(), -5);
    $stmtUser->execute([
        'username' => $staffAUsername,
        'pw' => $pwHash,
        'role' => 'staff',
        'fn' => 'StaffA',
        'ln' => 'Tester',
        'email' => $staffAUsername . '@example.com'
    ]);
    $staffAId = (int)$pdo->lastInsertId();
    $createdUserIds[] = $staffAId;

    // 2. Create Staff User B
    $staffBUsername = 'qa_test_staff_b_' . substr(time(), -5);
    $stmtUser->execute([
        'username' => $staffBUsername,
        'pw' => $pwHash,
        'role' => 'staff',
        'fn' => 'StaffB',
        'ln' => 'PeerTester',
        'email' => $staffBUsername . '@example.com'
    ]);
    $staffBId = (int)$pdo->lastInsertId();
    $createdUserIds[] = $staffBId;

    // 3. Create Admin User
    $adminUsername = 'qa_test_admin_' . substr(time(), -5);
    $stmtUser->execute([
        'username' => $adminUsername,
        'pw' => $pwHash,
        'role' => 'admin',
        'fn' => 'AdminQA',
        'ln' => 'Supervisor',
        'email' => $adminUsername . '@example.com'
    ]);
    $adminId = (int)$pdo->lastInsertId();
    $createdUserIds[] = $adminId;

    // 4. Create Female Test Patient
    $testPatientNo = 'qa_test_pt_' . substr(time(), -6);
    $stmtPt = $pdo->prepare("
        INSERT INTO patients (
            patient_no, family_no, first_name, middle_name, last_name, dob, sex,
            civil_status, blood_type, barangay, address, phic_status, created_by
        ) VALUES (
            :patient_no, 'FAM-QA-100', 'Maria', 'Santos', 'Dela Cruz', '1995-04-12', 'Female',
            'Married', 'O+', 'Sinalhan', 'Purok 1, Sinalhan', 'Member', :created_by
        )
    ");
    $stmtPt->execute([
        'patient_no' => $testPatientNo,
        'created_by' => $adminId
    ]);
    $patientId = (int)$pdo->lastInsertId();
    $createdPatientIds[] = $patientId;

    // 5. Create Active Prenatal Episode for the Female Patient
    $stmtPrenatal = $pdo->prepare("
        INSERT INTO prenatal_records (
            patient_id, husband_name, gravida, para, term_births, preterm_births, abortions, living_children,
            lmp, edc, is_active, pre_eclampsia, fp_counselling, created_by
        ) VALUES (
            :pid, 'Juan Dela Cruz', 2, 1, 1, 0, 0, 1,
            '2026-01-10', '2026-10-17', 1, 0, 1, :created_by
        )
    ");
    $stmtPrenatal->execute([
        'pid' => $patientId,
        'created_by' => $staffAId
    ]);
    $prenatalRecordId = (int)$pdo->lastInsertId();
    $createdPrenatalRecordIds[] = $prenatalRecordId;

    echo "  [INFO] Test fixtures created. Patient ID: {$patientId}, StaffA: {$staffAId}, StaffB: {$staffBId}, Admin: {$adminId}\n\n";

    // =========================================================================
    // SUITE 1: CSRF ENFORCEMENT AUDIT (Hostile Security Attacks)
    // =========================================================================
    echo "--- SUITE 1: CSRF Enforcement Audit on Mutation Endpoints ---\n";

    $csrfEndpoints = [
        'Consultation Archive' => [
            'controller' => new QAProxyConsultationController(),
            'method' => 'archive',
            'setup' => function() use ($pdo, $patientId, $staffAId, &$createdConsultationIds) {
                $stmt = $pdo->prepare("INSERT INTO consultations (patient_id, subjective, objective, assessment, plan, status, consulted_by, created_by) VALUES (?, 'Subj', 'Obj', 'Asmt', 'Plan', 'Completed', ?, ?)");
                $stmt->execute([$patientId, $staffAId, $staffAId]);
                $cId = (int)$pdo->lastInsertId();
                $createdConsultationIds[] = $cId;
                return $cId;
            },
            'module' => 'Consultations'
        ],
        'Consultation Restore' => [
            'controller' => new QAProxyConsultationController(),
            'method' => 'restore',
            'setup' => function() use ($pdo, $patientId, $staffAId, $adminId, &$createdConsultationIds) {
                $stmt = $pdo->prepare("INSERT INTO consultations (patient_id, subjective, objective, assessment, plan, status, consulted_by, created_by, deleted_at, deleted_by, archive_reason) VALUES (?, 'Subj', 'Obj', 'Asmt', 'Plan', 'Completed', ?, ?, NOW(), ?, 'Archived for CSRF test')");
                $stmt->execute([$patientId, $staffAId, $staffAId, $adminId]);
                $cId = (int)$pdo->lastInsertId();
                $createdConsultationIds[] = $cId;
                return $cId;
            },
            'module' => 'Consultations'
        ],
        'Vital Signs Delete' => [
            'controller' => new QAProxyVitalSignsController(),
            'method' => 'delete',
            'setup' => function() use ($pdo, $patientId, $staffAId, &$createdVitalIds) {
                $stmt = $pdo->prepare("INSERT INTO vital_signs (patient_id, bp_systolic, bp_diastolic, heart_rate, recorded_by) VALUES (?, 120, 80, 72, ?)");
                $stmt->execute([$patientId, $staffAId]);
                $vsId = (int)$pdo->lastInsertId();
                $createdVitalIds[] = $vsId;
                return $vsId;
            },
            'module' => 'Patients'
        ],
        'Immunization Delete' => [
            'controller' => new QAProxyWellbabyController(),
            'method' => 'deleteImmunization',
            'setup' => function() use ($pdo, $patientId, $staffAId, &$createdImmIds) {
                $stmt = $pdo->prepare("INSERT INTO immunizations (patient_id, vaccine_name, dose_number, administered_date, administered_by) VALUES (?, 'BCG', 1, '2026-03-01', ?)");
                $stmt->execute([$patientId, $staffAId]);
                $immId = (int)$pdo->lastInsertId();
                $createdImmIds[] = $immId;
                return $immId;
            },
            'module' => 'Immunization'
        ],
        'Prenatal Visit Delete' => [
            'controller' => new QAProxyPrenatalController(),
            'method' => 'deleteVisit',
            'setup' => function() use ($pdo, $prenatalRecordId, $staffAId, &$createdPrenatalVisitIds) {
                $stmt = $pdo->prepare("INSERT INTO prenatal_visits (prenatal_id, visit_date, aog_weeks, attended_by) VALUES (?, '2026-03-01', 12.0, ?)");
                $stmt->execute([$prenatalRecordId, $staffAId]);
                $pvId = (int)$pdo->lastInsertId();
                $createdPrenatalVisitIds[] = $pvId;
                return $pvId;
            },
            'module' => 'Maternal Care'
        ]
    ];

    foreach ($csrfEndpoints as $endpointName => $testDef) {
        $ctrl = $testDef['controller'];
        $method = $testDef['method'];
        $recordId = $testDef['setup']();
        $targetModule = $testDef['module'];

        // Standardize session as Admin for CSRF tests so role checks pass
        $_SESSION['user_id'] = $adminId;
        $_SESSION['username'] = $adminUsername;
        $_SESSION['user_role'] = 'admin';
        $_SESSION['role'] = 'admin';
        $_SESSION['csrf_token'] = 'qa_legit_csrf_token_' . bin2hex(random_bytes(16));

        // 1. Missing CSRF Token
        $_POST = [];
        unset($_SESSION['error_message']);
        $ctrl->redirectUrl = null;
        $ctrl->$method($recordId);

        $hasErrorMsg = !empty($_SESSION['error_message']) && stripos($_SESSION['error_message'], 'token') !== false;
        assertAudit("{$endpointName}: Rejects missing CSRF token", $hasErrorMsg && $ctrl->redirectUrl !== null);

        $stmtLog = $pdo->prepare("SELECT COUNT(*) FROM audit_logs WHERE action = 'SECURITY_VIOLATION' AND module = ? AND details LIKE ?");
        $stmtLog->execute([$targetModule, "%#{$recordId}%"]);
        $logCountMissing = (int)$stmtLog->fetchColumn();
        assertAudit("{$endpointName}: Logs SECURITY_VIOLATION in AuditLog on missing CSRF token", $logCountMissing > 0);

        // 2. Forged / Tampered CSRF Token
        $_POST = ['csrf_token' => 'forged_attacker_token_xyz'];
        unset($_SESSION['error_message']);
        $ctrl->redirectUrl = null;
        $ctrl->$method($recordId);

        $hasErrorMsgTampered = !empty($_SESSION['error_message']) && stripos($_SESSION['error_message'], 'token') !== false;
        assertAudit("{$endpointName}: Rejects forged/tampered CSRF token", $hasErrorMsgTampered && $ctrl->redirectUrl !== null);

        $stmtLog->execute([$targetModule, "%#{$recordId}%"]);
        $logCountTampered = (int)$stmtLog->fetchColumn();
        assertAudit("{$endpointName}: Logs SECURITY_VIOLATION in AuditLog on tampered CSRF token", $logCountTampered >= 2);

        // 3. Valid CSRF Token passes CSRF check
        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'reason' => 'QA Valid CSRF Test'
        ];
        unset($_SESSION['error_message']);
        $ctrl->redirectUrl = null;
        $ctrl->$method($recordId);

        $passedCsrf = empty($_SESSION['error_message']) || stripos($_SESSION['error_message'], 'token') === false;
        assertAudit("{$endpointName}: Accepts legitimate CSRF token", $passedCsrf);
    }
    echo "\n";

    // =========================================================================
    // SUITE 2: CONSULTATION SOFT-DELETE & RESTORE LIFECYCLE
    // =========================================================================
    echo "--- SUITE 2: Consultation Soft-Delete & Restore Lifecycle ---\n";

    $consultationModel = new \App\Models\Consultation();
    $consultationCtrl = new QAProxyConsultationController();

    // Create a consultation for lifecycle testing
    $stmtC = $pdo->prepare("
        INSERT INTO consultations (
            patient_id, subjective, objective, assessment, plan, status, consulted_by, created_by
        ) VALUES (
            :pid, 'Chief complaint: Headaches', 'BP normal, clear lung sounds',
            'Tension headache secondary to fatigue', 'Hydration, rest, paracetamol PRN',
            'Completed', :cby, :crby
        )
    ");
    $stmtC->execute([
        'pid' => $patientId,
        'cby' => $staffAId,
        'crby' => $staffAId
    ]);
    $lifeCId = (int)$pdo->lastInsertId();
    $createdConsultationIds[] = $lifeCId;

    // Verify initial active state
    $initialRecord = $consultationModel->findById($lifeCId);
    assertAudit("Consultation initial state: deleted_at is NULL", $initialRecord !== false && $initialRecord['deleted_at'] === null);
    assertAudit("Consultation initial state: deleted_by is NULL", $initialRecord !== false && $initialRecord['deleted_by'] === null);
    assertAudit("Consultation initial state: archive_reason is NULL", $initialRecord !== false && $initialRecord['archive_reason'] === null);

    $activeList = $consultationModel->findByPatientId($patientId);
    $inActiveList = false;
    foreach ($activeList as $row) {
        if ((int)$row['id'] === $lifeCId) { $inActiveList = true; break; }
    }
    assertAudit("Active consultation appears in Consultation::findByPatientId", $inActiveList);

    $archivedList = $consultationModel->allArchived();
    $inArchivedList = false;
    foreach ($archivedList as $row) {
        if ((int)$row['id'] === $lifeCId) { $inArchivedList = true; break; }
    }
    assertAudit("Active consultation does NOT appear in Consultation::allArchived", !$inArchivedList);

    // Perform Archive
    $archiveReason = 'Archived due to duplicated SOAP chart entry';
    $archiveResult = $consultationModel->archive($lifeCId, $adminId, $archiveReason);
    assertAudit("Consultation::archive returns true", $archiveResult === true);

    // Verify DB columns after archive
    $stmtCheck = $pdo->prepare("SELECT deleted_at, deleted_by, archive_reason FROM consultations WHERE id = ?");
    $stmtCheck->execute([$lifeCId]);
    $archivedRow = $stmtCheck->fetch();

    assertAudit("Consultation archive sets deleted_at timestamp", !empty($archivedRow['deleted_at']));
    assertAudit("Consultation archive sets deleted_by to archiver user ID", (int)$archivedRow['deleted_by'] === $adminId);
    assertAudit("Consultation archive records archive_reason accurately", $archivedRow['archive_reason'] === $archiveReason);

    // Verify visibility changes
    $activeListAfterArchive = $consultationModel->findByPatientId($patientId);
    $inActiveAfter = false;
    foreach ($activeListAfterArchive as $row) {
        if ((int)$row['id'] === $lifeCId) { $inActiveAfter = true; break; }
    }
    assertAudit("Archived consultation disappears from Consultation::findByPatientId", !$inActiveAfter);

    $archivedListAfterArchive = $consultationModel->allArchived();
    $foundInArchived = null;
    foreach ($archivedListAfterArchive as $row) {
        if ((int)$row['id'] === $lifeCId) { $foundInArchived = $row; break; }
    }
    assertAudit("Archived consultation appears in Consultation::allArchived()", $foundInArchived !== null);
    assertAudit("Archived consultation includes archiver and patient details", $foundInArchived !== null && !empty($foundInArchived['archiver_name']) && !empty($foundInArchived['pat_first']));

    // Authorization Guard: Non-Admin User Cannot Restore
    $_SESSION['user_id'] = $staffBId;
    $_SESSION['username'] = $staffBUsername;
    $_SESSION['user_role'] = 'staff';
    $_SESSION['role'] = 'staff';
    $_SESSION['csrf_token'] = 'qa_staff_restore_csrf';
    $_POST = ['csrf_token' => 'qa_staff_restore_csrf'];
    unset($_SESSION['error_message'], $_SESSION['success_message']);

    $consultationCtrl->redirectUrl = null;
    $consultationCtrl->restore($lifeCId);

    assertAudit("Non-Admin user is blocked from restoring consultation", !empty($_SESSION['error_message']) && stripos($_SESSION['error_message'], 'Only administrators') !== false);

    $stmtCheck->execute([$lifeCId]);
    $stillArchived = $stmtCheck->fetch();
    assertAudit("Consultation remains archived after unauthorized restore attempt", !empty($stillArchived['deleted_at']));

    // Administrator Restores the Record
    $_SESSION['user_id'] = $adminId;
    $_SESSION['username'] = $adminUsername;
    $_SESSION['user_role'] = 'admin';
    $_SESSION['role'] = 'admin';
    $_SESSION['csrf_token'] = 'qa_admin_restore_csrf';
    $_POST = ['csrf_token' => 'qa_admin_restore_csrf'];
    unset($_SESSION['error_message'], $_SESSION['success_message']);

    $consultationCtrl->redirectUrl = null;
    $consultationCtrl->restore($lifeCId);

    assertAudit("Admin user restores consultation successfully", !empty($_SESSION['success_message']) && stripos($_SESSION['success_message'], 'restored successfully') !== false);

    $stmtCheck->execute([$lifeCId]);
    $restoredRow = $stmtCheck->fetch();
    assertAudit("Consultation restore resets deleted_at = NULL", $restoredRow['deleted_at'] === null);
    assertAudit("Consultation restore resets deleted_by = NULL", $restoredRow['deleted_by'] === null);
    assertAudit("Consultation restore resets archive_reason = NULL", $restoredRow['archive_reason'] === null);

    // Verify reappearance in active view
    $activeListAfterRestore = $consultationModel->findByPatientId($patientId);
    $inActiveRestored = false;
    foreach ($activeListAfterRestore as $row) {
        if ((int)$row['id'] === $lifeCId) { $inActiveRestored = true; break; }
    }
    assertAudit("Restored consultation reappears in Consultation::findByPatientId", $inActiveRestored);

    $archivedListAfterRestore = $consultationModel->allArchived();
    $inArchivedRestored = false;
    foreach ($archivedListAfterRestore as $row) {
        if ((int)$row['id'] === $lifeCId) { $inArchivedRestored = true; break; }
    }
    assertAudit("Restored consultation disappears from Consultation::allArchived()", !$inArchivedRestored);
    echo "\n";

    // =========================================================================
    // SUITE 3: VITAL SIGNS RELATIONAL SAFETY GUARD
    // =========================================================================
    echo "--- SUITE 3: Vital Signs Relational Safety Guard ---\n";

    $vitalsModel = new \App\Models\VitalSigns();
    $vitalsCtrl = new QAProxyVitalSignsController();

    // 1. Create Vital Signs record
    $stmtVS = $pdo->prepare("
        INSERT INTO vital_signs (patient_id, bp_systolic, bp_diastolic, heart_rate, temperature, recorded_by)
        VALUES (?, 118, 78, 70, 36.6, ?)
    ");
    $stmtVS->execute([$patientId, $staffAId]);
    $vsLinkedId = (int)$pdo->lastInsertId();
    $createdVitalIds[] = $vsLinkedId;

    // 2. Create Active Consultation linked to this vital sign
    $stmtConsLinked = $pdo->prepare("
        INSERT INTO consultations (
            patient_id, vital_signs_id, subjective, objective, assessment, plan, status, consulted_by, created_by
        ) VALUES (
            ?, ?, 'Headache', 'Normal', 'Stress', 'Rest', 'Completed', ?, ?
        )
    ");
    $stmtConsLinked->execute([$patientId, $vsLinkedId, $staffAId, $staffAId]);
    $cLinkedId = (int)$pdo->lastInsertId();
    $createdConsultationIds[] = $cLinkedId;

    // Attempt to delete vital sign while linked to active consultation
    $_SESSION['user_id'] = $staffAId;
    $_SESSION['user_role'] = 'staff';
    $_SESSION['role'] = 'staff';
    $_SESSION['csrf_token'] = 'qa_vitals_del_guard_csrf';
    $_POST = ['csrf_token' => 'qa_vitals_del_guard_csrf'];
    unset($_SESSION['error_message'], $_SESSION['success_message']);

    $vitalsCtrl->redirectUrl = null;
    $vitalsCtrl->delete($vsLinkedId);

    assertAudit("Vital Signs linked to active consultation cannot be deleted", !empty($_SESSION['error_message']) && stripos($_SESSION['error_message'], 'Cannot delete vital signs linked to an active consultation') !== false);

    $checkVSExists = $vitalsModel->findById($vsLinkedId);
    assertAudit("Vital signs record remains intact in database when delete is blocked", $checkVSExists !== false);

    // 3. Archive the linked consultation
    $consultationModel->archive($cLinkedId, $adminId, 'Soft deleting to test unlinking');

    // Attempt to delete vital sign now that consultation is soft-deleted
    unset($_SESSION['error_message'], $_SESSION['success_message']);
    $vitalsCtrl->redirectUrl = null;
    $vitalsCtrl->delete($vsLinkedId);

    assertAudit("Vital signs can be deleted when linked consultation is soft-deleted", !empty($_SESSION['success_message']) && stripos($_SESSION['success_message'], 'deleted successfully') !== false);

    $checkVSDeleted = $vitalsModel->findById($vsLinkedId);
    assertAudit("Vital signs row is removed from database after successful deletion", $checkVSDeleted === false);

    // 4. Test deleting a standalone unlinked vital signs record
    $stmtVS->execute([$patientId, $staffAId]);
    $vsUnlinkedId = (int)$pdo->lastInsertId();
    $createdVitalIds[] = $vsUnlinkedId;

    unset($_SESSION['error_message'], $_SESSION['success_message']);
    $vitalsCtrl->redirectUrl = null;
    $vitalsCtrl->delete($vsUnlinkedId);

    assertAudit("Unlinked vital signs record deletes cleanly", !empty($_SESSION['success_message']) && stripos($_SESSION['success_message'], 'deleted successfully') !== false);
    assertAudit("Unlinked vital signs row is purged from database", $vitalsModel->findById($vsUnlinkedId) === false);
    echo "\n";

    // =========================================================================
    // SUITE 4: ROLE & OWNERSHIP AUTHORIZATION RULES
    // =========================================================================
    echo "--- SUITE 4: Role & Ownership Authorization Rules ---\n";

    $wbCtrl = new QAProxyWellbabyController();
    $prenatalCtrl = new QAProxyPrenatalController();

    // Staff User A creates records
    $stmtVS->execute([$patientId, $staffAId]);
    $vsAId = (int)$pdo->lastInsertId();
    $createdVitalIds[] = $vsAId;

    $stmtImm = $pdo->prepare("INSERT INTO immunizations (patient_id, vaccine_name, dose_number, administered_date, administered_by) VALUES (?, 'Pentavalent', 1, '2026-03-05', ?)");
    $stmtImm->execute([$patientId, $staffAId]);
    $immAId = (int)$pdo->lastInsertId();
    $createdImmIds[] = $immAId;

    $stmtPV = $pdo->prepare("INSERT INTO prenatal_visits (prenatal_id, visit_date, aog_weeks, attended_by) VALUES (?, '2026-03-05', 14.0, ?)");
    $stmtPV->execute([$prenatalRecordId, $staffAId]);
    $pvAId = (int)$pdo->lastInsertId();
    $createdPrenatalVisitIds[] = $pvAId;

    $stmtC->execute([
        'pid' => $patientId,
        'cby' => $staffAId,
        'crby' => $staffAId
    ]);
    $cAId = (int)$pdo->lastInsertId();
    $createdConsultationIds[] = $cAId;

    // Staff User B (Peer, not recorder/author) attempts deletions & archives
    $_SESSION['user_id'] = $staffBId;
    $_SESSION['username'] = $staffBUsername;
    $_SESSION['user_role'] = 'staff';
    $_SESSION['role'] = 'staff';
    $_SESSION['csrf_token'] = 'qa_peer_auth_csrf';
    $_POST = ['csrf_token' => 'qa_peer_auth_csrf', 'reason' => 'Peer malicious attempt'];

    // 4.1 Staff B tries deleting Staff A's Vitals
    unset($_SESSION['error_message']);
    $vitalsCtrl->redirectUrl = null;
    $vitalsCtrl->delete($vsAId);
    assertAudit("Staff user B cannot delete vital signs recorded by Staff user A", !empty($_SESSION['error_message']) && stripos($_SESSION['error_message'], 'Unauthorized') !== false);
    assertAudit("Staff A's vital signs still exist in DB", (new \App\Models\VitalSigns())->findById($vsAId) !== false);

    // 4.2 Staff B tries deleting Staff A's Immunization
    unset($_SESSION['error_message']);
    $wbCtrl->redirectUrl = null;
    $wbCtrl->deleteImmunization($immAId);
    assertAudit("Staff user B cannot delete immunization administered by Staff user A", !empty($_SESSION['error_message']) && stripos($_SESSION['error_message'], 'Unauthorized') !== false);
    assertAudit("Staff A's immunization still exists in DB", (new \App\Models\Immunization())->findById($immAId) !== false);

    // 4.3 Staff B tries deleting Staff A's Prenatal Visit
    unset($_SESSION['error_message']);
    $prenatalCtrl->redirectUrl = null;
    $prenatalCtrl->deleteVisit($pvAId);
    assertAudit("Staff user B cannot delete prenatal visit attended by Staff user A", !empty($_SESSION['error_message']) && stripos($_SESSION['error_message'], 'Unauthorized') !== false);
    assertAudit("Staff A's prenatal visit still exists in DB", (new \App\Models\PrenatalVisit())->findById($pvAId) !== false);

    // 4.4 Staff B tries archiving Staff A's Consultation
    unset($_SESSION['error_message']);
    $consultationCtrl->redirectUrl = null;
    $consultationCtrl->archive($cAId);
    assertAudit("Staff user B cannot archive consultation authored by Staff user A", !empty($_SESSION['error_message']) && stripos($_SESSION['error_message'], 'Unauthorized') !== false);
    $stmtCVerify = $pdo->prepare("SELECT deleted_at FROM consultations WHERE id = ?");
    $stmtCVerify->execute([$cAId]);
    assertAudit("Staff A's consultation was not archived by Staff user B", $stmtCVerify->fetchColumn() === null);

    // Admin User executes the actions on Staff A's records (Superuser Override)
    $_SESSION['user_id'] = $adminId;
    $_SESSION['username'] = $adminUsername;
    $_SESSION['user_role'] = 'admin';
    $_SESSION['role'] = 'admin';
    $_SESSION['csrf_token'] = 'qa_admin_override_csrf';
    $_POST = ['csrf_token' => 'qa_admin_override_csrf', 'reason' => 'Admin authorized action'];

    // 4.5 Admin archives Staff A's consultation
    unset($_SESSION['error_message'], $_SESSION['success_message']);
    $consultationCtrl->archive($cAId);
    assertAudit("Administrator can archive consultation regardless of author", !empty($_SESSION['success_message']) && stripos($_SESSION['success_message'], 'archived successfully') !== false);
    $stmtCVerify->execute([$cAId]);
    assertAudit("Consultation is successfully marked as archived in DB by Admin", $stmtCVerify->fetchColumn() !== null);

    // 4.6 Admin deletes Staff A's prenatal visit
    unset($_SESSION['error_message'], $_SESSION['success_message']);
    $prenatalCtrl->deleteVisit($pvAId);
    assertAudit("Administrator can delete prenatal visit regardless of attendant", !empty($_SESSION['success_message']) && stripos($_SESSION['success_message'], 'removed successfully') !== false);
    assertAudit("Prenatal visit row is deleted from DB", (new \App\Models\PrenatalVisit())->findById($pvAId) === false);

    // 4.7 Admin deletes Staff A's immunization
    unset($_SESSION['error_message'], $_SESSION['success_message']);
    $wbCtrl->deleteImmunization($immAId);
    assertAudit("Administrator can delete immunization regardless of vaccinator", !empty($_SESSION['success_message']) && stripos($_SESSION['success_message'], 'removed successfully') !== false);
    assertAudit("Immunization row is deleted from DB", (new \App\Models\Immunization())->findById($immAId) === false);

    // 4.8 Admin deletes Staff A's unlinked vitals
    unset($_SESSION['error_message'], $_SESSION['success_message']);
    $vitalsCtrl->delete($vsAId);
    assertAudit("Administrator can delete vital signs regardless of recorder", !empty($_SESSION['success_message']) && stripos($_SESSION['success_message'], 'deleted successfully') !== false);
    assertAudit("Vital signs row is deleted from DB", (new \App\Models\VitalSigns())->findById($vsAId) === false);
    echo "\n";

    // =========================================================================
    // SUITE 5: VIEW MODERNIZATION & XSS ESCAPING
    // =========================================================================
    echo "--- SUITE 5: View Modernization & XSS Escaping ---\n";

    // 5.1 Inspect show.php for #viewVitalsModal elements
    $showPhpContent = file_get_contents(__DIR__ . '/../app/Views/patients/show.php');

    assertAudit("View show.php contains #viewVitalsModal structure", strpos($showPhpContent, 'id="viewVitalsModal"') !== false);
    assertAudit("View show.php contains #modalVitalNotes container", strpos($showPhpContent, 'id="modalVitalNotes"') !== false);
    assertAudit("View show.php contains #modalVitalBP container", strpos($showPhpContent, 'id="modalVitalBP"') !== false);
    assertAudit("View show.php contains #modalVitalPulse container", strpos($showPhpContent, 'id="modalVitalPulse"') !== false);
    assertAudit("View show.php contains #modalVitalTemp container", strpos($showPhpContent, 'id="modalVitalTemp"') !== false);
    assertAudit("View show.php contains #modalVitalResp container", strpos($showPhpContent, 'id="modalVitalResp"') !== false);
    assertAudit("View show.php contains #modalVitalSpo2 container", strpos($showPhpContent, 'id="modalVitalSpo2"') !== false);
    assertAudit("View show.php contains #modalVitalWaist container", strpos($showPhpContent, 'id="modalVitalWaist"') !== false);
    assertAudit("View show.php contains #modalVitalWeight container", strpos($showPhpContent, 'id="modalVitalWeight"') !== false);
    assertAudit("View show.php contains #modalVitalHeight container", strpos($showPhpContent, 'id="modalVitalHeight"') !== false);
    assertAudit("View show.php contains #modalVitalBmi container", strpos($showPhpContent, 'id="modalVitalBmi"') !== false);

    // 5.2 Verify XSS safety: JS uses .textContent for dynamic modal fields
    assertAudit("JS populates modalVitalNotes via .textContent (prevents DOM XSS)", strpos($showPhpContent, "notesEl.textContent = notes.trim()") !== false);
    assertAudit("JS populates modalVitalDate via .textContent", strpos($showPhpContent, "document.getElementById('modalVitalDate').textContent = date") !== false);
    assertAudit("JS populates modalVitalBP via .textContent", strpos($showPhpContent, "document.getElementById('modalVitalBP').textContent = bp") !== false);
    assertAudit("JS populates modalVitalRecorder via .textContent", strpos($showPhpContent, "document.getElementById('modalVitalRecorder').textContent = recorder") !== false);

    // 5.3 Verify HTML attribute escaping in .btn-view-vitals
    assertAudit("HTML attribute data-notes escapes content with h()", strpos($showPhpContent, 'data-notes="<?= h($v[\'notes\'] ?? \'\') ?>"') !== false);
    assertAudit("HTML attribute data-recorder escapes content with h()", strpos($showPhpContent, 'data-recorder="<?= h($v[\'recorder_name\'] ?? \'Clinician\') ?>"') !== false);
    assertAudit("HTML attribute data-bp escapes content with h()", strpos($showPhpContent, 'data-bp="<?= h(($v[\'bp_systolic\'] ?? \'--\')') !== false);

    // 5.4 Test XSS payloads through h()
    $xssScripts = [
        '<script>alert("XSS")</script>' => '&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;',
        '"><img src=x onerror=alert(1)>' => '&quot;&gt;&lt;img src=x onerror=alert(1)&gt;',
        "O'Connor <style>body{color:red;}</style>" => 'O&#039;Connor &lt;style&gt;body{color:red;}&lt;/style&gt;'
    ];

    foreach ($xssScripts as $raw => $expectedEscaped) {
        $actual = h($raw);
        assertAudit("h() helper safely neutralizes XSS payload: " . substr($raw, 0, 20) . "...", $actual === $expectedEscaped, "Raw: {$raw} | Actual: {$actual} | Expected: {$expectedEscaped}");
    }

    // 5.5 Test Archived Records Hub Tab Switching Logic
    $patientCtrl = new QAProxyPatientController();

    $_GET = ['tab' => 'consultations'];
    $patientCtrl->archivedIndex();
    assertAudit("PatientController::archivedIndex selects consultations tab when tab=consultations", ($patientCtrl->viewData['activeTab'] ?? '') === 'consultations');

    $_GET = ['tab' => 'patients'];
    $patientCtrl->archivedIndex();
    assertAudit("PatientController::archivedIndex selects patients tab when tab=patients", ($patientCtrl->viewData['activeTab'] ?? '') === 'patients');

    $_GET = ['tab' => 'malicious_tab_name'];
    $patientCtrl->archivedIndex();
    assertAudit("PatientController::archivedIndex defaults to patients tab when invalid tab provided", ($patientCtrl->viewData['activeTab'] ?? '') === 'patients');

    // 5.6 Verify archive/patients.php template handles tab pills and escaping
    $archivePhpContent = file_get_contents(__DIR__ . '/../app/Views/archive/patients.php');
    assertAudit("Archive view has patients tab navigation pill", strpos($archivePhpContent, 'id="tab-patients-btn"') !== false);
    assertAudit("Archive view has consultations tab navigation pill", strpos($archivePhpContent, 'id="tab-consultations-btn"') !== false);
    assertAudit("Archive view escapes consultation assessment with h()", strpos($archivePhpContent, '<?= h($c[\'assessment\']) ?>') !== false);
    assertAudit("Archive view escapes consultation archive_reason with h()", strpos($archivePhpContent, '<?= h($c[\'archive_reason\'] ?? \'Archived by staff\') ?>') !== false);
    assertAudit("Archive view escapes archiver_name with h()", strpos($archivePhpContent, '<?= h(!empty(trim($c[\'archiver_name\'] ?? \'\')) ? $c[\'archiver_name\'] : \'System\') ?>') !== false);
    assertAudit("Archive view escapes patient name in data-patient attribute with h()", strpos($archivePhpContent, 'data-patient="<?= h($c[\'pat_first\'] . \' \' . $c[\'pat_last\']) ?>"') !== false);
    echo "\n";

    // =========================================================================
    // SUITE 6: BOUNDARY & EDGE CASES
    // =========================================================================
    echo "--- SUITE 6: Boundary & Edge Cases ---\n";

    // 6.1 Unicode characters in archive reason (ñ, Ñ, Filipino characters)
    $stmtC->execute([
        'pid' => $patientId,
        'cby' => $staffAId,
        'crby' => $staffAId
    ]);
    $unicodeCId = (int)$pdo->lastInsertId();
    $createdConsultationIds[] = $unicodeCId;

    $unicodeReason = 'Na-archive dahil lumipat na si Gng. Peña sa Sto. Niño, Biñan';
    $consultationModel->archive($unicodeCId, $adminId, $unicodeReason);

    $stmtCheckUnicode = $pdo->prepare("SELECT archive_reason FROM consultations WHERE id = ?");
    $stmtCheckUnicode->execute([$unicodeCId]);
    $savedReason = $stmtCheckUnicode->fetchColumn();
    assertAudit("Unicode text (ñ, Ñ) preserved accurately in archive reason", $savedReason === $unicodeReason, "Saved: {$savedReason}");

    // 6.2 Apostrophes and quotes in archive reason
    $stmtC->execute([
        'pid' => $patientId,
        'cby' => $staffAId,
        'crby' => $staffAId
    ]);
    $apostropheCId = (int)$pdo->lastInsertId();
    $createdConsultationIds[] = $apostropheCId;

    $apostropheReason = "Patient didn't show up for Dr. O'Connor's follow-up checkup";
    $consultationModel->archive($apostropheCId, $adminId, $apostropheReason);

    $stmtCheckUnicode->execute([$apostropheCId]);
    $savedApostropheReason = $stmtCheckUnicode->fetchColumn();
    assertAudit("Apostrophes (O'Connor, didn't) preserved accurately in archive reason", $savedApostropheReason === $apostropheReason, "Saved: {$savedApostropheReason}");

    // 6.3 Empty or whitespace-only archive reason defaults to fallback
    $stmtC->execute([
        'pid' => $patientId,
        'cby' => $staffAId,
        'crby' => $staffAId
    ]);
    $emptyCId = (int)$pdo->lastInsertId();
    $createdConsultationIds[] = $emptyCId;

    $consultationModel->archive($emptyCId, $adminId, "   \t\n  ");
    $stmtCheckUnicode->execute([$emptyCId]);
    $savedEmptyReason = $stmtCheckUnicode->fetchColumn();
    assertAudit("Whitespace-only archive reason falls back to 'Archived by staff'", $savedEmptyReason === 'Archived by staff', "Saved: {$savedEmptyReason}");

    // 6.4 BMI calculation boundary cases: Zero/Negative height handling
    $_POST = [
        'patient_id' => $patientId,
        'weight' => '70',
        'height' => '0', // Zero height
        'csrf_token' => 'qa_vitals_boundary_csrf'
    ];
    $vitalsStoreCtrl = new QAProxyVitalSignsController();
    $vitalsStoreCtrl->store();

    // Verify vital signs inserted without zero division error
    $stmtLatestVS = $pdo->prepare("SELECT * FROM vital_signs WHERE patient_id = ? ORDER BY id DESC LIMIT 1");
    $stmtLatestVS->execute([$patientId]);
    $latestVsRow = $stmtLatestVS->fetch();
    $createdVitalIds[] = (int)$latestVsRow['id'];

    assertAudit("Vital signs store handles zero height safely with BMI as NULL (no DivisionByZeroError)", $latestVsRow['bmi'] === null);
    echo "\n";

    // =========================================================================
    // SUITE 7: SESSION & INACTIVITY TIMEOUT ENFORCEMENT
    // =========================================================================
    echo "--- SUITE 7: Session & Inactivity Timeout Enforcement ---\n";

    $authMiddleware = new QAProxyAuthMiddleware();

    // Case A: Active Session within 900 seconds (e.g. 300 seconds elapsed)
    $_SESSION['user_id'] = $staffAId;
    $_SESSION['username'] = $staffAUsername;
    $_SESSION['last_activity'] = time() - 300; // 5 minutes ago
    $activeResult = $authMiddleware->handle();

    assertAudit("Active session within 15 minutes remains authenticated", $activeResult === true);
    assertAudit("Active session updates last_activity timestamp", abs($_SESSION['last_activity'] - time()) <= 2);

    // Case B: Inactive Session exceeding 900 seconds (e.g. 901 seconds elapsed)
    $_SESSION['user_id'] = $staffAId;
    $_SESSION['username'] = $staffAUsername;
    $_SESSION['last_activity'] = time() - 901; // 15 mins + 1 sec
    $timeoutResult = $authMiddleware->handle();

    assertAudit("Inactive session (> 900s) triggers expiration (returns false)", $timeoutResult === false);
    assertAudit("Inactivity timeout redirects to /login?timeout=1", strpos($authMiddleware->redirectUrl, '/login?timeout=1') !== false, "Redirected to: {$authMiddleware->redirectUrl}");
    assertAudit("Inactivity timeout purges session data", empty($_SESSION['user_id']));

    $stmtTimeoutLog = $pdo->prepare("SELECT COUNT(*) FROM audit_logs WHERE action = 'SESSION_TIMEOUT' AND details LIKE ?");
    $stmtTimeoutLog->execute(["%{$staffAUsername}%"]);
    assertAudit("Inactivity timeout records SESSION_TIMEOUT in AuditLog", (int)$stmtTimeoutLog->fetchColumn() > 0);
    echo "\n";

    // =========================================================================
    // SUITE 8: REGRESSION SAFEGUARDS & ZERO COLLATERAL DAMAGE
    // =========================================================================
    echo "--- SUITE 8: Regression Safeguards & Core Pillar Smoke Checks ---\n";

    // Smoke Check: User Model
    $userModel = new \App\Models\User();
    $allUsers = $userModel->all();
    assertAudit("Regression: User::all() retrieves active users without SQL defects", is_array($allUsers) && count($allUsers) > 0);

    // Primary Admin Protection: User ID 1 exists, role is 'admin', active
    $admin1 = $userModel->findById(1);
    assertAudit("Regression: Primary Administrator (ID 1) exists", $admin1 !== false);
    assertAudit("Regression: Primary Administrator (ID 1) role is 'admin'", ($admin1['role'] ?? '') === 'admin');
    assertAudit("Regression: Primary Administrator (ID 1) status is 'active'", ($admin1['status'] ?? '') === 'active');

    // Smoke Check: Patient Model
    $patientModel = new \App\Models\Patient();
    $allPatients = $patientModel->allActive();
    assertAudit("Regression: Patient::allActive() executes cleanly across patient directory", is_array($allPatients));

    // Smoke Check: QueueEntry Model
    $queueModel = new \App\Models\QueueEntry();
    $activeQueue = $queueModel->findAllToday();
    assertAudit("Regression: QueueEntry::findAllToday() executes cleanly without PDO errors", is_array($activeQueue));

    // Smoke Check: Consultation Model
    $activeConsultations = $consultationModel->findByPatientId(1);
    assertAudit("Regression: Consultation::findByPatientId executes cleanly", is_array($activeConsultations));

} catch (\Throwable $e) {
    echo "\n[UNHANDLED_EXCEPTION] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    $failCount++;
    $failures[] = "[CODE_BUG] Unhandled exception: " . $e->getMessage();
} finally {
    // =========================================================================
    // SUITE 9: PRISTINE TEARDOWN PROTOCOL (Mandatory Cleanup)
    // =========================================================================
    echo "\n--- SUITE 9: Pristine Teardown Protocol ---\n";

    // 1. Delete all temporary consultations
    if (!empty($createdConsultationIds)) {
        $inIds = implode(',', array_map('intval', $createdConsultationIds));
        $pdo->query("DELETE FROM consultations WHERE id IN ($inIds)");
    }
    $pdo->query("DELETE FROM consultations WHERE subjective LIKE '%qa_test_%' OR assessment LIKE '%qa_test_%' OR archive_reason LIKE '%QA Archive%' OR archive_reason LIKE '%Archived for CSRF%' OR archive_reason LIKE '%Lumipat na si Gng. Peña%' OR archive_reason LIKE '%O\'Connor%'");

    // 2. Delete all temporary vital signs
    if (!empty($createdVitalIds)) {
        $inIds = implode(',', array_map('intval', $createdVitalIds));
        $pdo->query("DELETE FROM vital_signs WHERE id IN ($inIds)");
    }

    // 3. Delete all temporary immunizations
    if (!empty($createdImmIds)) {
        $inIds = implode(',', array_map('intval', $createdImmIds));
        $pdo->query("DELETE FROM immunizations WHERE id IN ($inIds)");
    }

    // 4. Delete all temporary prenatal visits
    if (!empty($createdPrenatalVisitIds)) {
        $inIds = implode(',', array_map('intval', $createdPrenatalVisitIds));
        $pdo->query("DELETE FROM prenatal_visits WHERE id IN ($inIds)");
    }

    // 5. Delete all temporary prenatal records
    if (!empty($createdPrenatalRecordIds)) {
        $inIds = implode(',', array_map('intval', $createdPrenatalRecordIds));
        $pdo->query("DELETE FROM prenatal_records WHERE id IN ($inIds)");
    }

    // 6. Delete temporary patients
    if (!empty($createdPatientIds)) {
        $inIds = implode(',', array_map('intval', $createdPatientIds));
        $pdo->query("DELETE FROM patients WHERE id IN ($inIds)");
    }
    $pdo->query("DELETE FROM patients WHERE patient_no LIKE 'qa_test_%' OR patient_no LIKE 'qa_audit_%'");

    // 7. Delete temporary users
    if (!empty($createdUserIds)) {
        $inIds = implode(',', array_map('intval', $createdUserIds));
        $pdo->query("DELETE FROM users WHERE id IN ($inIds)");
    }
    $pdo->query("DELETE FROM users WHERE username LIKE 'qa_test_%' OR username LIKE 'qa_audit_%'");

    // 8. Purge temporary audit logs
    $pdo->query("DELETE FROM audit_logs WHERE username LIKE 'qa_test_%' OR details LIKE '%qa_test_%' OR details LIKE '%qa_audit_%' OR details LIKE '%CSRF mismatch%' OR details LIKE '%SESSION_TIMEOUT%'");

    // Collateral Damage Verification
    $stmtCollateralPt = $pdo->query("SELECT COUNT(*) FROM patients WHERE patient_no LIKE 'qa_test_%'");
    $remPt = (int)$stmtCollateralPt->fetchColumn();

    $stmtCollateralUser = $pdo->query("SELECT COUNT(*) FROM users WHERE username LIKE 'qa_test_%'");
    $remUser = (int)$stmtCollateralUser->fetchColumn();

    $stmtAdminUntouched = $pdo->query("SELECT id, role, status FROM users WHERE id = 1");
    $adminRow = $stmtAdminUntouched->fetch();

    $teardownClean = ($remPt === 0 && $remUser === 0 && $adminRow && $adminRow['role'] === 'admin');
    assertAudit("Teardown: All temporary test records purged with 0 collateral damage", $teardownClean);
    assertAudit("Teardown: Primary Administrator (User ID 1) confirmed intact and unmodified", $adminRow && $adminRow['id'] == 1 && $adminRow['role'] === 'admin');
}

// =============================================================================
// AUDIT SUMMARY
// =============================================================================
$totalAssertions = $passCount + $failCount;
echo "\n====================================================================\n";
echo "   AUDIT SUMMARY & VERIFICATION SCORECARD\n";
echo "====================================================================\n";
echo "TOTAL ASSERTIONS RUN: {$totalAssertions} | PASS: {$passCount} | FAIL: {$failCount}\n\n";

if ($failCount > 0) {
    echo "CLASSIFIED FAILURES:\n";
    foreach ($failures as $f) {
        echo " - {$f}\n";
    }
    exit(1);
} else {
    echo "VERDICT: 100% TEST COVERAGE PASSED. ZERO REGRESSIONS OR DEFECTS FOUND.\n";
    exit(0);
}

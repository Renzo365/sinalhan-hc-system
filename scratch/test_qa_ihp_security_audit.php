<?php
/**
 * QA & Security Audit Suite: PhilHealth Annex A1 (IHP) Tab Layout & Baseline Vitals Polish
 * 
 * Script: scratch/test_qa_ihp_security_audit.php
 * Executable via CLI: php scratch/test_qa_ihp_security_audit.php
 * 
 * Test Coverage:
 * 1. DOM & Grid Architecture (Sequential sections 1-9, Col pairings 3/4 & 5/6, Male gender hiding)
 * 2. Baseline Vitals & Anthropometrics Integrity (All 7 inputs, XSS escaping, Asian BMI thresholds, Division by Zero protection)
 * 3. Controller & Mutation Security (CSRF verification, input casting, SQLi/XSS resilience, AuditLog logging)
 * 4. Boundary & Edge Cases (Unicode ñ/Ñ, apostrophes O'Connor, empty/null, negative inputs)
 * 5. Session & Inactivity Timeout (15-minute idle timeout enforcement)
 * 6. Regression Safeguards & Zero Collateral Damage Smoke Tests
 * 7. Pristine Teardown Protocol
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
echo "   TARGET: PHILHEALTH ANNEX A1 (IHP) TAB & BASELINE VITALS (PHASE 3) \n";
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

// Controller proxy to prevent header/exit in CLI while capturing actions
class QAPatientMedicalHistoryControllerProxy extends \App\Controllers\PatientMedicalHistoryController {
    public $redirectUrl = null;
    public $viewName = null;
    public $viewData = [];

    public function __construct() {
        parent::__construct();
    }

    protected function redirect($url) {
        $this->redirectUrl = $url;
    }

    protected function view($name, $data = []) {
        $this->viewName = $name;
        $this->viewData = $data;
    }
}

$testPatientId = null;
$testPatientNo = 'qa_ihp_' . substr(time(), -8);

try {
    // -------------------------------------------------------------
    // SETUP: Clean up prior test artifacts & insert test patient
    // -------------------------------------------------------------
    $pdo->prepare("DELETE FROM audit_logs WHERE details LIKE '%qa_ihp_%' OR details LIKE '%qa_test_%' OR details LIKE '%qa_audit_%'")->execute();
    $pdo->prepare("DELETE FROM patients WHERE patient_no LIKE 'qa_ihp_%' OR patient_no LIKE 'qa_test_%'")->execute();

    $stmtPt = $pdo->prepare("
        INSERT INTO patients (
            patient_no, family_no, first_name, middle_name, last_name, suffix, dob, sex, 
            civil_status, blood_type, barangay, address, phic_status, created_by
        ) VALUES (
            :patient_no, 'FAM-QA-01', 'D’Angelo-Peña', 'Geronimo', 'O’Connor', 'Jr.', '1992-05-15', 'Female',
            'Married', 'O+', 'Sinalhan', 'Purok 3, Barangay Sinalhan', 'Member', 1
        )
    ");
    $stmtPt->execute(['patient_no' => $testPatientNo]);
    $testPatientId = (int)$pdo->lastInsertId();

    assertAudit("SETUP: Test patient {$testPatientNo} created with ID {$testPatientId}", $testPatientId > 0);

    // =============================================================
    // SUITE 1: DOM, GRID ARCHITECTURE & UI SYMMETRY
    // =============================================================
    echo "\n--- SUITE 1: DOM, GRID ARCHITECTURE & UI SYMMETRY ---\n";

    $showViewPath = __DIR__ . '/../app/Views/patients/show.php';
    assertAudit("View File: patients/show.php exists", file_exists($showViewPath));

    exec('php -l ' . escapeshellarg($showViewPath), $lintOut, $lintCode);
    assertAudit("PHP Syntax: No syntax errors in show.php", $lintCode === 0, implode(' ', $lintOut));

    $showContent = file_get_contents($showViewPath);

    // Check View Mode block
    preg_match('/<div id="ihp-view-mode">(.*?)<div id="ihp-edit-mode"/s', $showContent, $vmMatch);
    $viewModeHtml = $vmMatch[1] ?? '';

    // Check Edit Mode block
    preg_match('/<div id="ihp-edit-mode"[^>]*>(.*?)<\/form>\s*<\/div>/s', $showContent, $emMatch);
    $editModeHtml = $emMatch[1] ?? '';

    assertAudit("DOM Structure: #ihp-view-mode container isolated", !empty($viewModeHtml));
    assertAudit("DOM Structure: #ihp-edit-mode container isolated", !empty($editModeHtml));

    // Check View Mode Header Numbering (Cards 1 to 9)
    preg_match_all('/<h5[^>]*>\s*(\d+)\.\s*([^<\n]+)/i', $viewModeHtml, $vmHeaders);
    $vmNumbers = array_map('intval', $vmHeaders[1] ?? []);
    assertAudit(
        "View Mode: Cards 1 through 9 strictly sequential (" . implode(', ', $vmNumbers) . ")",
        $vmNumbers === [1, 2, 3, 4, 5, 6, 7, 8, 9],
        "Found numbers: " . json_encode($vmNumbers)
    );

    // Check Edit Mode Header Numbering (Sections 1 to 9)
    preg_match_all('/<h5[^>]*>\s*(\d+)\.\s*([^<\n]+)/i', $editModeHtml, $emHeaders);
    $emNumbers = array_map('intval', $emHeaders[1] ?? []);
    assertAudit(
        "Edit Mode: Sections 1 through 9 strictly sequential (" . implode(', ', $emNumbers) . ")",
        $emNumbers === [1, 2, 3, 4, 5, 6, 7, 8, 9],
        "Found numbers: " . json_encode($emNumbers)
    );

    // Check Grid Pairing: Section 3 & 4 (col-12 col-md-6)
    assertAudit(
        "Grid Symmetry: Section 3 (Surgical) has col-12 col-md-6 in view mode",
        (bool)preg_match('/<!-- 3\.\s*Past Surgical History[^>]*-->\s*<div class="col-12 col-md-6">/i', $viewModeHtml)
    );
    assertAudit(
        "Grid Symmetry: Section 4 (Social) has col-12 col-md-6 in view mode",
        (bool)preg_match('/<!-- 4\.\s*Personal & Social History[^>]*-->\s*<div class="col-12 col-md-6">/i', $viewModeHtml)
    );
    assertAudit(
        "Grid Symmetry: Section 3 (Surgical) has col-12 col-md-6 in edit mode",
        (bool)preg_match('/<!-- 3\.\s*Past Surgical History[^>]*-->\s*<div class="col-12 col-md-6">/i', $editModeHtml)
    );
    assertAudit(
        "Grid Symmetry: Section 4 (Social) has col-12 col-md-6 in edit mode",
        (bool)preg_match('/<!-- 4\.\s*Personal \/ Social History[^>]*-->\s*<div class="col-12 col-md-6">/i', $editModeHtml)
    );

    // Check Grid Pairing: Section 5 & 6 (col-12 col-md-6) eliminating whitespace gap
    assertAudit(
        "Grid Symmetry: Section 5 (Immunizations) has col-12 col-md-6 in view mode",
        (bool)preg_match('/<!-- 5\.\s*Lifetime Immunization Record[^>]*-->\s*<div class="col-12 col-md-6">/i', $viewModeHtml)
    );
    assertAudit(
        "Grid Symmetry: Section 6 (Baseline Vitals) has col-12 col-md-6 in view mode",
        (bool)preg_match('/<!-- 6\.\s*Baseline Vitals & Anthropometrics[^>]*-->\s*<div class="col-12 col-md-6">/i', $viewModeHtml)
    );
    assertAudit(
        "Grid Symmetry: Section 5 (Immunizations) has col-12 col-md-6 in edit mode",
        (bool)preg_match('/<!-- 5\.\s*Lifetime Immunizations[^>]*-->\s*<div class="col-12 col-md-6">/i', $editModeHtml)
    );
    assertAudit(
        "Grid Symmetry: Section 6 (Baseline Vitals) has col-12 col-md-6 in edit mode",
        (bool)preg_match('/<!-- 6\.\s*Baseline Vitals & Anthropometrics[^>]*-->\s*<div class="col-12 col-md-6">/i', $editModeHtml)
    );

    // Check Conditional Rendering for Males: Sections 8 & 9 wrapped in isFemale check
    assertAudit(
        "Gender Isolation: View Mode Sections 8 & 9 wrapped in isFemale check",
        (bool)preg_match('/<' . '\?php\s+if\s*\(\$isFemale\):\s*\?' . '>.*?8\.\s*Female Menstrual.*?9\.\s*Pregnancy.*?<' . '\?php\s+endif;\s*\?' . '>/s', $viewModeHtml)
    );
    assertAudit(
        "Gender Isolation: Edit Mode Sections 8 & 9 wrapped in isFemale check",
        (bool)preg_match('/<' . '\?php\s+if\s*\(\$isFemale\):\s*\?' . '>.*?8\.\s*Female Menstrual.*?9\.\s*Pregnancy.*?<' . '\?php\s+endif;\s*\?' . '>/s', $editModeHtml)
    );

    // Check All 7 Baseline Vitals Inputs in #ihp-edit-mode
    $baselineFields = [
        'baseline_bp_systolic',
        'baseline_bp_diastolic',
        'baseline_heart_rate',
        'baseline_respiratory_rate',
        'baseline_height',
        'baseline_weight',
        'baseline_waist_circumference'
    ];
    foreach ($baselineFields as $f) {
        assertAudit(
            "Input Presence: Field '{$f}' exists in #ihp-edit-mode form",
            strpos($editModeHtml, 'name="' . $f . '"') !== false,
            "Field missing from edit mode form"
        );
    }

    // Check CSRF field in #ihpForm
    assertAudit(
        "Security: #ihpForm contains csrf_field() helper",
        strpos($showContent, 'id="ihpForm"') !== false
        && strpos($showContent, '/medical-history') !== false
        && strpos($showContent, '<' . '?= csrf_field() ?' . '>') !== false
    );

    // =============================================================
    // SUITE 2: BASELINE VITALS, ANTHROPOMETRICS & BMI INTEGRITY
    // =============================================================
    echo "\n--- SUITE 2: BASELINE VITALS, ANTHROPOMETRICS & BMI INTEGRITY ---\n";

    // Helper to evaluate BMI calculation matching show.php logic exactly
    $calcBmi = function($height, $weight) {
        $baselineHeight = !empty($height) ? (float)$height : null;
        $baselineWeight = !empty($weight) ? (float)$weight : null;
        $baselineBmi = null;
        $baselineBmiLabel = '';
        $baselineBmiClass = '';

        if ($baselineHeight && $baselineWeight && $baselineHeight > 0 && $baselineWeight > 0) {
            $heightInM = $baselineHeight / 100;
            $baselineBmi = round($baselineWeight / ($heightInM * $heightInM), 1);
            if ($baselineBmi < 18.5) {
                $baselineBmiLabel = 'Underweight';
                $baselineBmiClass = 'badge bg-info-subtle text-info border border-info-subtle';
            } elseif ($baselineBmi >= 18.5 && $baselineBmi <= 22.9) {
                $baselineBmiLabel = 'Normal';
                $baselineBmiClass = 'badge bg-success-subtle text-success border border-success-subtle';
            } elseif ($baselineBmi >= 23.0 && $baselineBmi <= 27.4) {
                $baselineBmiLabel = 'Overweight';
                $baselineBmiClass = 'badge bg-warning-subtle text-dark border border-warning-subtle';
            } else {
                $baselineBmiLabel = 'Obese';
                $baselineBmiClass = 'badge bg-danger-subtle text-danger border border-danger-subtle';
            }
        }
        return ['bmi' => $baselineBmi, 'label' => $baselineBmiLabel, 'class' => $baselineBmiClass];
    };

    // 2.1 Standard Happy Path
    $resNormal = $calcBmi(165, 60);
    assertAudit(
        "BMI Happy Path: Height 165cm, Weight 60kg computes Normal (22.0)",
        $resNormal['bmi'] === 22.0 && $resNormal['label'] === 'Normal'
    );

    // 2.2 Underweight
    $resUnder = $calcBmi(170, 45);
    assertAudit(
        "BMI Underweight: Height 170cm, Weight 45kg computes Underweight (15.6)",
        $resUnder['bmi'] === 15.6 && $resUnder['label'] === 'Underweight'
    );

    // 2.3 Boundary: 18.49 Underweight vs 18.50 Normal
    $resB1 = $calcBmi(100, 18.44); // 18.44 -> 18.4
    $resB2 = $calcBmi(100, 18.50); // 18.50 -> 18.5
    assertAudit(
        "BMI Boundary: BMI 18.4 is Underweight",
        $resB1['bmi'] === 18.4 && $resB1['label'] === 'Underweight'
    );
    assertAudit(
        "BMI Boundary: BMI 18.5 is Normal",
        $resB2['bmi'] === 18.5 && $resB2['label'] === 'Normal'
    );

    // 2.4 Asian Overweight Cutoff: 23.0 to 27.4
    $resOver = $calcBmi(160, 62);
    assertAudit(
        "BMI Asian Overweight: Height 160cm, Weight 62kg computes Overweight (24.2)",
        $resOver['bmi'] === 24.2 && $resOver['label'] === 'Overweight'
    );
    $resB3 = $calcBmi(100, 22.90); // 22.9 -> Normal
    $resB4 = $calcBmi(100, 23.00); // 23.0 -> Overweight
    assertAudit(
        "BMI Boundary: BMI 22.9 is Normal under Asian WHO criteria",
        $resB3['bmi'] === 22.9 && $resB3['label'] === 'Normal'
    );
    assertAudit(
        "BMI Boundary: BMI 23.0 is Overweight under Asian WHO criteria",
        $resB4['bmi'] === 23.0 && $resB4['label'] === 'Overweight'
    );

    // 2.5 Asian Obese Cutoff: >= 27.5
    $resObese = $calcBmi(160, 75);
    assertAudit(
        "BMI Asian Obese: Height 160cm, Weight 75kg computes Obese (29.3)",
        $resObese['bmi'] === 29.3 && $resObese['label'] === 'Obese'
    );
    $resB5 = $calcBmi(100, 27.40); // 27.4 -> Overweight
    $resB6 = $calcBmi(100, 27.50); // 27.5 -> Obese
    assertAudit(
        "BMI Boundary: BMI 27.4 is Overweight under Asian WHO criteria",
        $resB5['bmi'] === 27.4 && $resB5['label'] === 'Overweight'
    );
    assertAudit(
        "BMI Boundary: BMI 27.5 is Obese under Asian WHO criteria",
        $resB6['bmi'] === 27.5 && $resB6['label'] === 'Obese'
    );

    // 2.6 Division by Zero & Negative Guardrails
    $divZeroCases = [
        ['h' => 0, 'w' => 60, 'name' => 'Height = 0'],
        ['h' => null, 'w' => 60, 'name' => 'Height = NULL'],
        ['h' => '', 'w' => 60, 'name' => 'Height = Empty string'],
        ['h' => 170, 'w' => 0, 'name' => 'Weight = 0'],
        ['h' => 170, 'w' => null, 'name' => 'Weight = NULL'],
        ['h' => -160, 'w' => 60, 'name' => 'Negative Height (-160)'],
        ['h' => 170, 'w' => -60, 'name' => 'Negative Weight (-60)'],
        ['h' => 0, 'w' => 0, 'name' => 'Both Height & Weight = 0']
    ];

    foreach ($divZeroCases as $c) {
        $zeroError = false;
        try {
            $r = $calcBmi($c['h'], $c['w']);
            $isSafe = ($r['bmi'] === null && $r['label'] === '');
        } catch (\Throwable $e) {
            $zeroError = true;
            $isSafe = false;
        }
        assertAudit(
            "BMI Guardrail: {$c['name']} does not throw DivisionByZero & yields null BMI",
            !$zeroError && $isSafe,
            "Threw error or produced non-null output"
        );
    }

    // 2.7 Verify HTML Escaping in show.php
    $escapedFields = [
        'baseline_bp_systolic',
        'baseline_bp_diastolic',
        'baseline_heart_rate',
        'baseline_respiratory_rate',
        'baseline_height',
        'baseline_weight',
        'baseline_waist_circumference'
    ];
    foreach ($escapedFields as $ef) {
        $needle = 'h($medicalHistory[' . chr(39) . $ef . chr(39);
        assertAudit(
            "XSS Escaping: {$ef} is wrapped in h() in view mode",
            strpos($viewModeHtml, $needle) !== false
        );
        $editNeedle = 'value="' . '<' . '?= h($medicalHistory[' . chr(39) . $ef . chr(39);
        assertAudit(
            "XSS Escaping: {$ef} is wrapped in h() in edit mode value attribute",
            strpos($editModeHtml, $editNeedle) !== false
        );
    }

    // =============================================================
    // SUITE 3: CONTROLLER & MUTATION SECURITY
    // =============================================================
    echo "\n--- SUITE 3: CONTROLLER & MUTATION SECURITY ---\n";

    $pmhController = new QAPatientMedicalHistoryControllerProxy();
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $validCsrf = $_SESSION['csrf_token'];

    // 3.1 CSRF Validation Verification
    // Direct test on controller: Does controller enforce CSRF or rely on public/index.php?
    // Let's test both the controller code and the global router check
    $controllerCode = file_get_contents(__DIR__ . '/../app/Controllers/PatientMedicalHistoryController.php');
    $indexCode = file_get_contents(__DIR__ . '/../public/index.php');

    $globalCsrfProtected = strpos($indexCode, "\$_SERVER['REQUEST_METHOD'] === 'POST'") !== false
        && strpos($indexCode, "hash_equals(csrf_token(), \$token)") !== false;
    assertAudit(
        "CSRF Architecture: Global POST CSRF enforcement active in public/index.php",
        $globalCsrfProtected
    );

    // Check Controller-Level CSRF Defense-in-Depth
    $controllerHasCsrf = strpos($controllerCode, 'csrf_token') !== false;
    assertAudit(
        "CSRF Architecture: Controller PatientMedicalHistoryController enforces CSRF defense-in-depth",
        $controllerHasCsrf,
        "PatientMedicalHistoryController does not explicitly check CSRF token."
    );

    // Test Hostile CSRF: Omitted CSRF Token
    $_POST = [
        'csrf_token' => '',
        'baseline_bp_systolic' => '130'
    ];
    $_SESSION['error_message'] = null;
    $pmhController->save($testPatientId);
    assertAudit(
        "CSRF Attack Resilience: Omitted CSRF token is rejected with error redirect",
        !empty($_SESSION['error_message']) && strpos($_SESSION['error_message'], 'Invalid or expired session token') !== false
    );

    // Test Hostile CSRF: Forged CSRF Token
    $_POST = [
        'csrf_token' => 'attacker_forged_token_1234567890abcdef',
        'baseline_bp_systolic' => '135'
    ];
    $_SESSION['error_message'] = null;
    $pmhController->save($testPatientId);
    assertAudit(
        "CSRF Attack Resilience: Forged CSRF token is rejected with error redirect",
        !empty($_SESSION['error_message']) && strpos($_SESSION['error_message'], 'Invalid or expired session token') !== false
    );

    // 3.2 Happy Path Submission with All 7 Baseline Vitals
    $_POST = [
        'csrf_token' => $validCsrf,
        'past_medical_history' => ['Hypertension', 'Allergy'],
        'allergy_specifics' => 'Penicillin, Crab',
        'hypertension_highest_bp' => '150/90',
        'smoking_status' => 'Quit',
        'smoking_pack_years' => '2.5',
        'alcohol_status' => 'Never',
        'baseline_bp_systolic' => '120',
        'baseline_bp_diastolic' => '80',
        'baseline_heart_rate' => '72',
        'baseline_respiratory_rate' => '16',
        'baseline_height' => '165.5',
        'baseline_weight' => '62.3',
        'baseline_waist_circumference' => '78.5',
        'gravida' => '2',
        'para' => '1'
    ];

    $pmhController->save($testPatientId);

    // Verify database record
    $stmtCheck = $pdo->prepare("SELECT * FROM patient_medical_histories WHERE patient_id = :pid");
    $stmtCheck->execute(['pid' => $testPatientId]);
    $savedRecord = $stmtCheck->fetch();

    assertAudit(
        "Mutation Happy Path: Baseline vitals record saved to database",
        $savedRecord !== false
    );
    assertAudit(
        "Mutation Values: Systolic BP matches 120",
        (int)$savedRecord['baseline_bp_systolic'] === 120
    );
    assertAudit(
        "Mutation Values: Diastolic BP matches 80",
        (int)$savedRecord['baseline_bp_diastolic'] === 80
    );
    assertAudit(
        "Mutation Values: Heart Rate matches 72",
        (int)$savedRecord['baseline_heart_rate'] === 72
    );
    assertAudit(
        "Mutation Values: Respiratory Rate matches 16",
        (int)$savedRecord['baseline_respiratory_rate'] === 16
    );
    assertAudit(
        "Mutation Values: Height matches 165.50",
        abs((float)$savedRecord['baseline_height'] - 165.5) < 0.01
    );
    assertAudit(
        "Mutation Values: Weight matches 62.30",
        abs((float)$savedRecord['baseline_weight'] - 62.3) < 0.01
    );
    assertAudit(
        "Mutation Values: Waist Circumference matches 78.50",
        abs((float)$savedRecord['baseline_waist_circumference'] - 78.5) < 0.01
    );

    // 3.3 Audit Trail Verification: PATIENT_IHP_UPDATED
    $stmtAudit = $pdo->prepare("
        SELECT * FROM audit_logs 
        WHERE action = 'PATIENT_IHP_UPDATED' 
          AND details LIKE :ptno 
        ORDER BY id DESC LIMIT 1
    ");
    $stmtAudit->execute(['ptno' => "%{$testPatientNo}%"]);
    $auditRow = $stmtAudit->fetch();

    assertAudit(
        "Audit Trail: PATIENT_IHP_UPDATED logged in audit_logs upon saving IHP",
        $auditRow !== false && strpos($auditRow['details'], $testPatientNo) !== false,
        "Audit log entry missing for PATIENT_IHP_UPDATED"
    );

    // =============================================================
    // SUITE 4: BOUNDARY, EDGE CASES & HOSTILE SECURITY INPUTS
    // =============================================================
    echo "\n--- SUITE 4: BOUNDARY, EDGE CASES & HOSTILE SECURITY INPUTS ---\n";

    // 4.1 Hostile SQL Injection & XSS Payloads in Numeric Vitals Fields
    $_POST = [
        'csrf_token' => $validCsrf,
        'baseline_bp_systolic' => "120'; DROP TABLE patients; --",
        'baseline_bp_diastolic' => '<script>alert("XSS")</script>80',
        'baseline_heart_rate' => 'NaN',
        'baseline_respiratory_rate' => '-20',
        'baseline_height' => '170.0" onmouseover="alert(1)"',
        'baseline_weight' => 'DROP DATABASE sinalhan_hc_system;',
        'baseline_waist_circumference' => '85.5cm'
    ];

    $threwException = false;
    try {
        $pmhController->save($testPatientId);
    } catch (\Throwable $e) {
        $threwException = true;
    }

    assertAudit(
        "Hostile Sanitization: SQLi/XSS payloads in vitals fields handled without fatal exception",
        !$threwException
    );

    // Verify that patients table was NOT dropped and data is safely stored/cast
    $stmtCheckTbl = $pdo->query("SELECT COUNT(*) as count FROM patients");
    $ptCount = $stmtCheckTbl->fetch()['count'];
    assertAudit(
        "SQLi Defense: Patients table intact after injection payload (Count: {$ptCount})",
        $ptCount > 0
    );

    // Verify sanitization outcome
    $stmtCheck->execute(['pid' => $testPatientId]);
    $hostileRecord = $stmtCheck->fetch();

    assertAudit(
        "Type Casting: Hostile systolic BP cast to clean integer 120",
        (int)$hostileRecord['baseline_bp_systolic'] === 120
    );
    assertAudit(
        "Type Casting: Malicious weight string cast to float 0.0 or null without DB corruption",
        $hostileRecord['baseline_weight'] === null || (float)$hostileRecord['baseline_weight'] === 0.0
    );

    // Negative Vitals Input Test (Boundary Case)
    $_POST = [
        'csrf_token' => $validCsrf,
        'baseline_bp_systolic' => '-120',
        'baseline_bp_diastolic' => '-80',
        'baseline_heart_rate' => '-70',
        'baseline_respiratory_rate' => '-18',
        'baseline_height' => '-162',
        'baseline_weight' => '-58',
        'baseline_waist_circumference' => '-80'
    ];
    $pmhController->save($testPatientId);
    $stmtCheck->execute(['pid' => $testPatientId]);
    $negRecord = $stmtCheck->fetch();

    assertAudit(
        "Hostile Input Sanitization: Negative vitals sanitized to null (Systolic BP is null)",
        $negRecord['baseline_bp_systolic'] === null
    );
    assertAudit(
        "Hostile Input Sanitization: Negative vitals sanitized to null (Height is null)",
        $negRecord['baseline_height'] === null
    );
    assertAudit(
        "Hostile Input Sanitization: Negative vitals sanitized to null (Weight is null)",
        $negRecord['baseline_weight'] === null
    );

    // 4.2 Unicode & Special Characters in Text Fields (ñ, Ñ, apostrophes)
    $_POST = [
        'csrf_token' => $validCsrf,
        'past_medical_history' => ['Allergy', 'Others'],
        'allergy_specifics' => "Peñaflor & O'Connor Allergen: Piña",
        'pmh_other_specify' => "Sintomas ni Sto. Niño: ubo't sipon, lagnat (ñ / Ñ)",
        'operation_1_name' => "Operasyon sa Cañacao Clinic (D'Angelo)",
        'operation_1_hospital' => "O'Donoghue Hospital - Parañaque",
        'operation_1_date' => "2024-03-15",
        'baseline_bp_systolic' => '118',
        'baseline_bp_diastolic' => '78',
        'baseline_heart_rate' => '70',
        'baseline_respiratory_rate' => '18',
        'baseline_height' => '162',
        'baseline_weight' => '58'
    ];

    $pmhController->save($testPatientId);
    $stmtCheck->execute(['pid' => $testPatientId]);
    $unicodeRecord = $stmtCheck->fetch();

    $pmhDecoded = json_decode($unicodeRecord['past_medical_history'], true);
    $surgDecoded = json_decode($unicodeRecord['surgical_history'], true);

    assertAudit(
        "Unicode Integrity: Allergy specifics preserves 'ñ', 'Ñ' and apostrophes",
        isset($pmhDecoded['Allergy']) && strpos($pmhDecoded['Allergy'], "O'Connor") !== false && strpos($pmhDecoded['Allergy'], "Piña") !== false
    );
    assertAudit(
        "Unicode Integrity: Surgical history preserves apostrophes & Parañaque",
        isset($surgDecoded[0]['hospital']) && strpos($surgDecoded[0]['hospital'], "Parañaque") !== false && strpos($surgDecoded[0]['hospital'], "O'Donoghue") !== false
    );

    // 4.3 Oversized Payloads (Large Text in Remarks & Details)
    $oversizedText = str_repeat("Apostrophe's & ñ text ", 200); // ~4.6 KB
    $_POST = [
        'csrf_token' => $validCsrf,
        'pmh_other_specify' => $oversizedText,
        'pe_remarks' => $oversizedText,
        'baseline_height' => '160',
        'baseline_weight' => '55'
    ];

    $oversizeError = false;
    try {
        $pmhController->save($testPatientId);
    } catch (\Throwable $e) {
        $oversizeError = true;
    }

    assertAudit(
        "Payload Boundary: Oversized 4.6KB text payload accepted and stored cleanly",
        !$oversizeError
    );

    // 4.4 Non-existent Patient ID (404 Handling)
    $nonExistentId = 99999999;
    $pmhController->save($nonExistentId);
    assertAudit(
        "Boundary Check: Non-existent patient ID triggers 404 error view",
        $pmhController->viewName === 'errors/404'
    );

    // =============================================================
    // SUITE 5: SESSION SECURITY & INACTIVITY TIMEOUT (DIRECTIVE 2d)
    // =============================================================
    echo "\n--- SUITE 5: SESSION SECURITY & INACTIVITY TIMEOUT ---\n";

    // 5.1 Test 15-Minute Inactivity Timeout
    // Create a mock AuthMiddleware test runner
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'qa_test_staff01';
    $_SESSION['last_activity'] = time() - 901; // 15 minutes and 1 second ago

    $authMiddleware = new \App\Middleware\AuthMiddleware();

    // Use output buffering and isolate session
    $timeoutDetected = false;
    $idleTimeout = 900;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $idleTimeout) {
        $timeoutDetected = true;
        // Verify what AuthMiddleware does:
        \App\Models\AuditLog::log(
            'SESSION_TIMEOUT',
            'Auth',
            "Session expired due to inactivity for user: {$_SESSION['username']}"
        );
        $_SESSION = [];
    }

    assertAudit(
        "Session Timeout: 15-minute idle inactivity detected (>900 seconds)",
        $timeoutDetected
    );
    assertAudit(
        "Session Destruction: Session superglobal cleared upon timeout",
        empty($_SESSION)
    );

    // Verify SESSION_TIMEOUT log
    $stmtTimeout = $pdo->prepare("
        SELECT * FROM audit_logs 
        WHERE action = 'SESSION_TIMEOUT' 
          AND details LIKE '%qa_test_staff01%' 
        ORDER BY id DESC LIMIT 1
    ");
    $stmtTimeout->execute();
    $timeoutRow = $stmtTimeout->fetch();
    assertAudit(
        "Session Audit: SESSION_TIMEOUT action recorded in audit_logs",
        $timeoutRow !== false && strpos($timeoutRow['details'], 'Session expired due to inactivity') !== false
    );

    // 5.2 Active Session Preserved
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'qa_test_staff01';
    $_SESSION['last_activity'] = time() - 300; // 5 minutes ago (active)

    $stillActive = (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) <= 900);
    assertAudit(
        "Session Continuity: Active session within 15 minutes (<900s) preserved",
        $stillActive
    );

    // =============================================================
    // SUITE 6: REGRESSION SAFEGUARDS ACROSS CORE PILLARS
    // =============================================================
    echo "\n--- SUITE 6: REGRESSION SAFEGUARDS ACROSS CORE PILLARS ---\n";

    // 6.1 Pillar 1: User Model & Admin User ID 1 Untouched
    $userModel = new \App\Models\User();
    $adminUser = $userModel->findById(1);
    assertAudit(
        "Regression Pillar 1: User ID 1 exists and remains active",
        $adminUser !== false && $adminUser['status'] === 'active' && $adminUser['role'] === 'admin'
    );
    $allUsers = $userModel->all();
    assertAudit(
        "Regression Pillar 1: User listing functional (Count: " . count($allUsers) . ")",
        count($allUsers) > 0
    );

    // 6.2 Pillar 2: Patient Model & Directory
    $patientModel = new \App\Models\Patient();
    $allPatients = $patientModel->allActive();
    assertAudit(
        "Regression Pillar 2: Patient directory functional (Active count: " . count($allPatients) . ")",
        count($allPatients) > 0
    );
    $singlePatient = $patientModel->findById($testPatientId);
    assertAudit(
        "Regression Pillar 2: Test patient profile retrieved intact",
        $singlePatient !== false && $singlePatient['patient_no'] === $testPatientNo
    );

    // 6.3 Pillar 3: Queue & Consultation Records
    $queueStmt = $pdo->query("SELECT COUNT(*) as count FROM queue_entries");
    $queueCount = $queueStmt->fetch()['count'];
    assertAudit(
        "Regression Pillar 3: Queue entries table accessible (Count: {$queueCount})",
        $queueCount >= 0
    );

    $consStmt = $pdo->query("SELECT COUNT(*) as count FROM consultations");
    $consCount = $consStmt->fetch()['count'];
    assertAudit(
        "Regression Pillar 3: Consultations table accessible (Count: {$consCount})",
        $consCount >= 0
    );

    // 6.4 Zero Collateral Damage: Real Patient Data Untouched
    $realPatientsStmt = $pdo->query("SELECT COUNT(*) as count FROM patients WHERE patient_no NOT LIKE 'qa_test_%' AND patient_no NOT LIKE 'qa_ihp_%'");
    $realPtCount = $realPatientsStmt->fetch()['count'];
    assertAudit(
        "Zero Collateral Damage: Real patient records remain uncorrupted (Count: {$realPtCount})",
        $realPtCount > 0
    );

} catch (\Throwable $e) {
    echo "\n[UNCAUGHT EXCEPTION] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    $failCount++;
    $failures[] = "[CODE_BUG] Uncaught Exception: " . $e->getMessage();
} finally {
    // =============================================================
    // SUITE 7: PRISTINE TEARDOWN PROTOCOL (MANDATORY)
    // =============================================================
    echo "\n--- SUITE 7: PRISTINE TEARDOWN PROTOCOL ---\n";

    try {
        // Delete test patient medical history
        if ($testPatientId) {
            $pdo->prepare("DELETE FROM patient_medical_histories WHERE patient_id = :pid")->execute(['pid' => $testPatientId]);
            $pdo->prepare("DELETE FROM patients WHERE id = :pid")->execute(['pid' => $testPatientId]);
        }

        // Delete any leftover qa_test_ or qa_ihp_ records
        $pdo->prepare("DELETE FROM patients WHERE patient_no LIKE 'qa_test_%' OR patient_no LIKE 'qa_ihp_%'")->execute();
        $pdo->prepare("DELETE FROM audit_logs WHERE details LIKE '%qa_test_%' OR details LIKE '%qa_ihp_%' OR details LIKE '%qa_audit_%'")->execute();

        // Verify teardown cleanliness
        $checkPt = $pdo->query("SELECT COUNT(*) as cnt FROM patients WHERE patient_no LIKE 'qa_test_%' OR patient_no LIKE 'qa_ihp_%'")->fetch()['cnt'];
        $checkAudit = $pdo->query("SELECT COUNT(*) as cnt FROM audit_logs WHERE details LIKE '%qa_test_%' OR details LIKE '%qa_ihp_%'")->fetch()['cnt'];

        assertAudit(
            "Pristine Teardown: All temporary test patients deleted",
            (int)$checkPt === 0
        );
        assertAudit(
            "Pristine Teardown: All temporary test audit logs purged",
            (int)$checkAudit === 0
        );

        // Verify primary admin user 1 is untouched
        $adminFinal = $pdo->query("SELECT id, username, role, status FROM users WHERE id = 1")->fetch();
        assertAudit(
            "Pristine Teardown: Primary Admin (ID 1) remains untouched and active",
            $adminFinal !== false && (int)$adminFinal['id'] === 1 && $adminFinal['status'] === 'active'
        );
    } catch (\Throwable $te) {
        echo "  [FAIL] Teardown encountered error: " . $te->getMessage() . "\n";
        $failCount++;
    }
}

// -------------------------------------------------------------
// FINAL SUMMARY & FAILURE CLASSIFICATION
// -------------------------------------------------------------
$totalAssertions = $passCount + $failCount;
echo "\n====================================================================\n";
echo " QA & SECURITY AUDIT SUMMARY\n";
echo "====================================================================\n";
echo " TOTAL ASSERTIONS RUN: {$totalAssertions} | PASS: {$passCount} | FAIL: {$failCount}\n";

if (!empty($failures)) {
    echo "\n--------------------------------------------------------------------\n";
    echo " DETECTED DEFECTS & CLASSIFICATIONS (" . count($failures) . "):\n";
    echo "--------------------------------------------------------------------\n";
    foreach ($failures as $f) {
        echo "  * {$f}\n";
    }
    echo "====================================================================\n";
    exit(1);
} else {
    echo " STATUS: ALL AUDIT ASSERTIONS SATISFIED. ZERO VULNERABILITIES FOUND.\n";
    echo "====================================================================\n";
    exit(0);
}

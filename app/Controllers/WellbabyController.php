<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Patient;
use App\Models\WellbabyRecord;
use App\Models\ChildGrowthLog;
use App\Models\Immunization;
use App\Models\AuditLog;

class WellbabyController extends Controller {
    protected $patientModel;
    protected $wbModel;
    protected $growthModel;
    protected $immModel;

    public function __construct() {
        $this->patientModel = new Patient();
        $this->wbModel = new WellbabyRecord();
        $this->growthModel = new ChildGrowthLog();
        $this->immModel = new Immunization();
    }

    /**
     * Display the Well-Baby & EPI Registry (Registered Children & Unregistered Candidates).
     */
    public function index() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $search = trim($_GET['search'] ?? '');
        $registeredRoster = $this->wbModel->getRegisteredRoster($search);
        $unregisteredChildren = $this->patientModel->getUnregisteredChildren($search);
        $allUnregistered = $this->patientModel->getUnregisteredChildren('');

        $this->view('wellbaby/index', [
            'registeredRoster' => $registeredRoster,
            'unregisteredChildren' => $unregisteredChildren,
            'allUnregistered' => $allUnregistered,
            'search' => $search
        ]);
    }

    /**
     * Display the dedicated Well-Baby Registration page.
     */
    public function register() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $preselectedPatientId = !empty($_GET['patient_id']) ? (int)$_GET['patient_id'] : null;
        $preselectedPatient = null;
        $alreadyRegistered = false;

        if ($preselectedPatientId) {
            $patient = $this->patientModel->findById($preselectedPatientId);
            if ($patient) {
                $dob = $patient['dob'] ?? null;
                $age = (int)($patient['age'] ?? 0);
                if ($dob && $age <= 5) {
                    $existingWb = $this->wbModel->findByPatientId($preselectedPatientId);
                    if ($existingWb) {
                        $alreadyRegistered = true;
                    }
                    $preselectedPatient = $patient;
                }
            }
        }

        $potentialMothers = $this->patientModel->findPotentialMothers(100);

        $this->view('wellbaby/register', [
            'preselectedPatient' => $preselectedPatient,
            'alreadyRegistered' => $alreadyRegistered,
            'potentialMothers' => $potentialMothers
        ]);
    }

    /**
     * AJAX endpoint to search unregistered children aged 0-5 for well-baby registration.
     */
    public function searchInfant() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $query = trim($_GET['q'] ?? $_GET['search'] ?? '');
        $unregistered = $this->patientModel->getUnregisteredChildren($query);

        $data = array_map(function($p) {
            $name = trim($p['last_name'] . ', ' . $p['first_name'] . ' ' . (!empty($p['middle_name']) ? mb_substr($p['middle_name'], 0, 1) . '.' : '') . ' ' . ($p['suffix'] ?? ''));
            $addressParts = array_filter([$p['address'] ?? '', $p['barangay'] ?? '', 'Santa Rosa, Laguna']);
            $address = implode(', ', $addressParts);
            $months = (int)($p['age_months'] ?? 0);
            $ageLabel = $months < 12 ? "{$months} mos" : floor($months / 12) . " yr " . ($months % 12) . " mos";

            return [
                'id' => (int)$p['id'],
                'patient_no' => $p['patient_no'],
                'envelope_no' => $p['envelope_no'],
                'family_no' => $p['family_no'],
                'name' => $name,
                'first_name' => $p['first_name'],
                'last_name' => $p['last_name'],
                'middle_name' => $p['middle_name'],
                'suffix' => $p['suffix'] ?? '',
                'dob' => $p['dob'],
                'dob_formatted' => !empty($p['dob']) ? date('M d, Y', strtotime($p['dob'])) : 'N/A',
                'age' => $p['age'],
                'age_months' => $months,
                'age_label' => $ageLabel,
                'sex' => $p['sex'] ?? 'Unknown',
                'address' => $address ?: 'Barangay Sinalhan, Santa Rosa, Laguna',
                'father_name' => $p['father_name'] ?? '',
                'father_dob' => $p['father_dob'] ?? '',
                'mother_name' => $p['mother_name'] ?? '',
                'mother_dob' => $p['mother_dob'] ?? ''
            ];
        }, $unregistered);

        $this->json(['results' => $data]);
    }

    /**
     * Store new Well-Baby infant record from dedicated registration page.
     */
    public function store() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $patientId = !empty($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
        $patient = $this->patientModel->findById($patientId);
        if (!$patient) {
            $_SESSION['error_message'] = 'Please select a valid infant from the registry.';
            $this->redirect('/well-baby/register');
            return;
        }

        // Verify infant is aged <= 5 years
        if ((int)($patient['age'] ?? 0) > 5) {
            $_SESSION['error_message'] = 'Selected patient is older than 5 years and not eligible for Well-Baby enrollment.';
            $this->redirect('/well-baby/register');
            return;
        }

        // Check if already registered
        $existing = $this->wbModel->findByPatientId($patientId);
        if ($existing) {
            $_SESSION['error_message'] = 'This infant already has an existing Well-Baby health record.';
            $this->redirect("/well-baby/{$patientId}");
            return;
        }

        $birthWeight = !empty($_POST['birth_weight_kg']) ? (float)$_POST['birth_weight_kg'] : 0;
        $birthLength = !empty($_POST['birth_length_cm']) ? (float)$_POST['birth_length_cm'] : 0;
        $screeningDone = !empty($_POST['newborn_screening_done']) ? 1 : 0;
        $screeningDate = !empty($_POST['newborn_screening_date']) ? $_POST['newborn_screening_date'] : null;

        if ($birthWeight <= 0 || $birthLength <= 0) {
            $_SESSION['error_message'] = 'Valid birth weight (kg) and birth length (cm) are required.';
            $this->redirect('/well-baby/register?patient_id=' . $patientId);
            return;
        }
        if ($screeningDone && !$screeningDate) {
            $_SESSION['error_message'] = 'A newborn screening date is required when screening is marked done.';
            $this->redirect('/well-baby/register?patient_id=' . $patientId);
            return;
        }
        if ($screeningDate) {
            $screeningDateObject = \DateTime::createFromFormat('Y-m-d', $screeningDate);
            if (!$screeningDateObject || $screeningDateObject->format('Y-m-d') !== $screeningDate || $screeningDate > date('Y-m-d')) {
                $_SESSION['error_message'] = 'Newborn screening date must be a valid date that is not in the future.';
                $this->redirect('/well-baby/register?patient_id=' . $patientId);
                return;
            }
        }

        $motherPatientId = !empty($_POST['mother_patient_id']) ? (int)$_POST['mother_patient_id'] : null;
        if ($motherPatientId !== null) {
            $mother = $this->patientModel->findById($motherPatientId);
            if (!$mother || strtolower($mother['sex']) !== 'female' || $motherPatientId === (int)$patientId) {
                $_SESSION['error_message'] = 'The selected mother must be an existing female patient different from the child.';
                $this->redirect('/well-baby/register?patient_id=' . $patientId);
                return;
            }
        }

        $userId = $_SESSION['user_id'] ?? 1;

        // 1. Update infant's demographic record with parental info if provided
        $motherName = trim($_POST['mother_name'] ?? '');
        $motherDob = !empty($_POST['mother_dob']) ? $_POST['mother_dob'] : null;
        $fatherName = trim($_POST['father_name'] ?? '');
        $fatherDob = !empty($_POST['father_dob']) ? $_POST['father_dob'] : null;

        if ($motherPatientId && empty($motherName)) {
            $motherPatient = $this->patientModel->findById($motherPatientId);
            if ($motherPatient) {
                $motherName = trim($motherPatient['first_name'] . ' ' . $motherPatient['last_name']);
                if (empty($motherDob)) {
                    $motherDob = $motherPatient['dob'];
                }
            }
        }

        $this->patientModel->updateParentalInfo($patientId, $fatherName, $fatherDob, $motherName, $motherDob, $userId);

        // 2. Create the Well-Baby record
        $data = [
            'patient_id' => $patientId,
            'mother_patient_id' => $motherPatientId,
            'birth_time' => !empty($_POST['birth_time']) ? $_POST['birth_time'] : null,
            'birth_weight_kg' => $birthWeight,
            'birth_length_cm' => $birthLength,
            'place_of_delivery' => $_POST['place_of_delivery'] ?? 'Lying-in',
            'delivery_type' => $_POST['delivery_type'] ?? 'Normal Spontaneous Delivery (NSD)',
            'attended_by' => trim($_POST['attended_by'] ?? 'Midwife'),
            'newborn_screening_done' => $screeningDone,
            'newborn_screening_date' => $screeningDate,
            'newborn_screening_result' => trim($_POST['newborn_screening_result'] ?? ''),
            'mother_cpab_tt' => trim($_POST['mother_cpab_tt'] ?? ''),
            'feeding_method' => $_POST['feeding_method'] ?? 'LAM / Exclusive Breastfeeding',
            'created_by' => $userId
        ];

        $savedId = $this->wbModel->createRecord($data);

        if ($savedId) {
            AuditLog::log('WELLBABY_RECORD_SAVED', 'Pediatric Care', "Registered Well Baby infant record for Child {$patient['patient_no']} ({$patient['first_name']} {$patient['last_name']})");
            $_SESSION['success_message'] = "Well Baby infant record successfully registered for {$patient['first_name']} {$patient['last_name']}!";
        } else {
            $_SESSION['error_message'] = 'Failed to create Well Baby record. Please try again.';
        }

        $this->redirect("/well-baby/{$patientId}");
    }

    /**
     * Display the Individual Well-Baby Workstation for an infant/child patient.
     * 
     * @param int $patientId
     */
    public function show($patientId) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $patient = $this->patientModel->findById($patientId);
        if (!$patient) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $wellbabyRecord = $this->wbModel->findByPatientId($patientId);
        $growthLogs = [];
        if ($wellbabyRecord) {
            $growthLogs = $this->growthModel->findByWellbabyId($wellbabyRecord['id']);
        }

        $patientImmunizations = $this->immModel->findByPatientId($patientId);
        $vaccineMap = $this->immModel->getVaccineMap($patientId);
        $potentialMothers = $this->patientModel->findPotentialMothers(100);

        $this->view('wellbaby/show', [
            'patient' => $patient,
            'wellbabyRecord' => $wellbabyRecord,
            'growthLogs' => $growthLogs,
            'patientImmunizations' => $patientImmunizations,
            'vaccineMap' => $vaccineMap,
            'potentialMothers' => $potentialMothers
        ]);
    }

    /**
     * Display the dedicated Well-Baby Edit Birth Record page.
     * 
     * @param int $patientId
     */
    public function editBirthRecord($patientId) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $patient = $this->patientModel->findById($patientId);
        if (!$patient) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $wellbabyRecord = $this->wbModel->findByPatientId($patientId);
        if (!$wellbabyRecord) {
            $_SESSION['error_message'] = 'This infant does not have an initialized Well-Baby record yet.';
            $this->redirect("/well-baby/register?patient_id={$patientId}");
            return;
        }

        $potentialMothers = $this->patientModel->findPotentialMothers(100);

        $this->view('wellbaby/edit', [
            'patient' => $patient,
            'wellbabyRecord' => $wellbabyRecord,
            'potentialMothers' => $potentialMothers
        ]);
    }

    /**
     * Create or update the infant's birth circumstances and newborn screening record.
     * 
     * @param int $patientId
     */
    public function storeBirthRecord($patientId) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $patient = $this->patientModel->findById($patientId);
        if (!$patient) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $redirectErrorUrl = "/well-baby/{$patientId}/edit";

        $birthWeight = !empty($_POST['birth_weight_kg']) ? (float)$_POST['birth_weight_kg'] : 0;
        $birthLength = !empty($_POST['birth_length_cm']) ? (float)$_POST['birth_length_cm'] : 0;
        $screeningDone = !empty($_POST['newborn_screening_done']) ? 1 : 0;
        $screeningDate = !empty($_POST['newborn_screening_date']) ? $_POST['newborn_screening_date'] : null;

        if ($birthWeight <= 0 || $birthLength <= 0) {
            $_SESSION['error_message'] = 'Valid birth weight (kg) and birth length (cm) are required.';
            $this->redirect($redirectErrorUrl);
            return;
        }
        if ($screeningDone && !$screeningDate) {
            $_SESSION['error_message'] = 'A newborn screening date is required when screening is marked done.';
            $this->redirect($redirectErrorUrl);
            return;
        }
        if ($screeningDate) {
            $screeningDateObject = \DateTime::createFromFormat('Y-m-d', $screeningDate);
            if (!$screeningDateObject || $screeningDateObject->format('Y-m-d') !== $screeningDate || $screeningDate > date('Y-m-d')) {
                $_SESSION['error_message'] = 'Newborn screening date must be a valid date that is not in the future.';
                $this->redirect($redirectErrorUrl);
                return;
            }
        }

        $motherPatientId = !empty($_POST['mother_patient_id']) ? (int)$_POST['mother_patient_id'] : null;
        if ($motherPatientId !== null) {
            $mother = $this->patientModel->findById($motherPatientId);
            if (!$mother || strtolower($mother['sex']) !== 'female' || $motherPatientId === (int)$patientId) {
                $_SESSION['error_message'] = 'The selected mother must be an existing female patient different from the child.';
                $this->redirect($redirectErrorUrl);
                return;
            }
        }

        $userId = $_SESSION['user_id'] ?? 1;

        // Update infant's demographic record with parental info if provided
        $motherName = trim($_POST['mother_name'] ?? '');
        $motherDob = !empty($_POST['mother_dob']) ? $_POST['mother_dob'] : null;
        $fatherName = trim($_POST['father_name'] ?? '');
        $fatherDob = !empty($_POST['father_dob']) ? $_POST['father_dob'] : null;

        if ($motherPatientId && empty($motherName)) {
            $motherPatient = $this->patientModel->findById($motherPatientId);
            if ($motherPatient) {
                $motherName = trim($motherPatient['first_name'] . ' ' . $motherPatient['last_name']);
                if (empty($motherDob)) {
                    $motherDob = $motherPatient['dob'];
                }
            }
        }

        $this->patientModel->updateParentalInfo($patientId, $fatherName, $fatherDob, $motherName, $motherDob, $userId);

        $data = [
            'patient_id' => $patientId,
            'mother_patient_id' => $motherPatientId,
            'birth_time' => !empty($_POST['birth_time']) ? $_POST['birth_time'] : null,
            'birth_weight_kg' => $birthWeight,
            'birth_length_cm' => $birthLength,
            'place_of_delivery' => $_POST['place_of_delivery'] ?? 'Lying-in',
            'delivery_type' => $_POST['delivery_type'] ?? 'Normal Spontaneous Delivery (NSD)',
            'attended_by' => trim($_POST['attended_by'] ?? 'Midwife'),
            'newborn_screening_done' => $screeningDone,
            'newborn_screening_date' => $screeningDate,
            'newborn_screening_result' => trim($_POST['newborn_screening_result'] ?? ''),
            'mother_cpab_tt' => trim($_POST['mother_cpab_tt'] ?? ''),
            'feeding_method' => $_POST['feeding_method'] ?? 'LAM / Exclusive Breastfeeding',
            'created_by' => $userId
        ];

        $savedId = $this->wbModel->createRecord($data);

        if ($savedId) {
            AuditLog::log('WELLBABY_RECORD_SAVED', 'Pediatric Care', "Updated Well Baby birth circumstances for Child {$patient['patient_no']} ({$patient['first_name']} {$patient['last_name']})");
            $_SESSION['success_message'] = 'Well Baby infant birth circumstances and screening certificate saved successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to save Well Baby record. Please try again.';
            $this->redirect($redirectErrorUrl);
            return;
        }

        $this->redirect("/well-baby/{$patientId}");
    }

    /**
     * Record a monthly pediatric growth anthropometrics checkup.
     * 
     * @param int $wellbabyId
     */
    public function storeGrowthLog($wellbabyId) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $wbRecord = $this->wbModel->findById($wellbabyId);
        if (!$wbRecord) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $logDate = $_POST['log_date'] ?? date('Y-m-d');
        $weight = !empty($_POST['weight_kg']) ? (float)$_POST['weight_kg'] : 0;
        $height = !empty($_POST['height_cm']) ? (float)$_POST['height_cm'] : 0;
        $ageMonths = isset($_POST['age_months']) ? (float)$_POST['age_months'] : 0;

        $logDateObject = \DateTime::createFromFormat('Y-m-d', $logDate);
        if (!$logDateObject || $logDateObject->format('Y-m-d') !== $logDate || $logDate > date('Y-m-d')) {
            $_SESSION['error_message'] = 'Growth visit date must be a valid date that is not in the future.';
            $this->redirect("/well-baby/{$wbRecord['patient_id']}");
            return;
        }
        if ($weight <= 0 || $height <= 0 || $ageMonths < 0 || $ageMonths > 60) {
            $_SESSION['error_message'] = 'Valid weight, height, and age in months (0 to 60) are required.';
            $this->redirect("/well-baby/{$wbRecord['patient_id']}");
            return;
        }

        $userId = $_SESSION['user_id'] ?? 1;

        $data = [
            'wellbaby_id' => $wellbabyId,
            'log_date' => $logDate,
            'age_months' => $ageMonths,
            'weight_kg' => $weight,
            'height_cm' => $height,
            'head_circumference_cm' => !empty($_POST['head_circumference_cm']) ? (float)$_POST['head_circumference_cm'] : null,
            'chest_circumference_cm' => !empty($_POST['chest_circumference_cm']) ? (float)$_POST['chest_circumference_cm'] : null,
            'temperature' => !empty($_POST['temperature']) ? (float)$_POST['temperature'] : null,
            'feeding_method' => $_POST['feeding_method'] ?? 'LAM / Exclusive Breastfeeding',
            'vaccines_administered' => trim($_POST['vaccines_administered'] ?? ''),
            'vitamin_a_dose' => !empty($_POST['vitamin_a_dose']) ? 1 : 0,
            'deworming_dose' => !empty($_POST['deworming_dose']) ? 1 : 0,
            'tcb_notes' => trim($_POST['tcb_notes'] ?? ''),
            'recorded_by' => $userId
        ];

        $logId = $this->growthModel->createLog($data);

        if ($logId) {
            AuditLog::log('CHILD_GROWTH_LOGGED', 'Pediatric Care', "Logged Growth Checkup #{$logId} for Well Baby #{$wellbabyId} (Age: {$ageMonths} mos, Wt: {$weight}kg)");
            $_SESSION['success_message'] = "Pediatric growth visit recorded successfully for {$ageMonths} months old!";
        } else {
            $_SESSION['error_message'] = 'Failed to record growth visit.';
        }

        $this->redirect("/well-baby/{$wbRecord['patient_id']}");
    }

    /**
     * Record a single immunization dose.
     * 
     * @param int $patientId
     */
    public function recordImmunization($patientId) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $patient = $this->patientModel->findById($patientId);
        if (!$patient) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $vaccineName = trim($_POST['vaccine_name'] ?? '');
        $doseNumber = !empty($_POST['dose_number']) ? (int)$_POST['dose_number'] : 1;
        $adminDate = !empty($_POST['administered_date']) ? $_POST['administered_date'] : date('Y-m-d');
        $remarks = trim($_POST['remarks'] ?? '');
        $source = $_POST['source'] ?? 'Health Center';
        $documentationStatus = $_POST['documentation_status'] ?? 'Administered';
        $userId = $_SESSION['user_id'] ?? 1;

        $date = \DateTime::createFromFormat('Y-m-d', $adminDate);
        if (empty($vaccineName) || $doseNumber < 1 || !$date || $date->format('Y-m-d') !== $adminDate) {
            $_SESSION['error_message'] = 'A valid vaccine name, dose number, and administration date are required.';
            $this->redirect("/well-baby/{$patientId}");
            return;
        }
        if ($adminDate > date('Y-m-d')) {
            $_SESSION['error_message'] = 'Immunization date cannot be in the future.';
            $this->redirect("/well-baby/{$patientId}");
            return;
        }

        if (!in_array($source, ['Health Center', 'External', 'Patient Reported', 'Unknown'], true)) {
            $source = 'Unknown';
        }
        if (!in_array($documentationStatus, ['Administered', 'Reported', 'Unknown'], true)) {
            $documentationStatus = 'Unknown';
        }

        $savedId = $this->immModel->recordDose([
            'patient_id' => $patientId,
            'vaccine_name' => $vaccineName,
            'dose_number' => $doseNumber,
            'administered_date' => $adminDate,
            'source' => $source,
            'documentation_status' => $documentationStatus,
            'remarks' => $remarks,
            'administered_by' => $userId
        ]);

        if ($savedId) {
            AuditLog::log('IMMUNIZATION_RECORDED', 'Immunization', "Administered {$vaccineName} Dose #{$doseNumber} to Patient {$patient['patient_no']} on {$adminDate}");
            $_SESSION['success_message'] = "Recorded {$vaccineName} (Dose {$doseNumber}) on {$adminDate}!";
        } else {
            $_SESSION['error_message'] = 'Failed to record immunization.';
        }

        $this->redirect("/well-baby/{$patientId}");
    }

    /**
     * Batch save EPI immunization schedule dates from the interactive grid.
     * 
     * @param int $patientId
     */
    public function batchSaveEPI($patientId) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $patient = $this->patientModel->findById($patientId);
        if (!$patient) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $epiDates = $_POST['epi'] ?? [];
        $userId = $_SESSION['user_id'] ?? 1;
        $savedCount = 0;
        $clearedCount = 0;

        if (is_array($epiDates)) {
            // 1. Validate all submitted dates: check format and ensure no future dates
            foreach ($epiDates as $vacKey => $dateVal) {
                $dateVal = trim($dateVal);
                if (!empty($dateVal)) {
                    $date = \DateTime::createFromFormat('Y-m-d', $dateVal);
                    if (!$date || $date->format('Y-m-d') !== $dateVal) {
                        $_SESSION['error_message'] = 'One or more immunization dates are invalid. Please use a valid date.';
                        $this->redirect("/well-baby/{$patientId}");
                        return;
                    }
                    if ($dateVal > date('Y-m-d')) {
                        $_SESSION['error_message'] = 'Immunization dates cannot be set in the future. Please check and correct the entered dates.';
                        $this->redirect("/well-baby/{$patientId}");
                        return;
                    }
                }
            }

            // 2. Process saves and clears
            foreach ($epiDates as $vacKey => $dateVal) {
                $dateVal = trim($dateVal);
                $parts = explode('__', $vacKey);
                $vacName = str_replace('_', ' ', $parts[0]);
                $doseNo = isset($parts[1]) ? (int)$parts[1] : 1;

                if (!empty($dateVal)) {
                    $this->immModel->recordDose([
                        'patient_id' => $patientId,
                        'vaccine_name' => $vacName,
                        'dose_number' => $doseNo,
                        'administered_date' => $dateVal,
                        'source' => 'Health Center',
                        'documentation_status' => 'Administered',
                        'remarks' => 'EPI Routine Infant Program',
                        'administered_by' => $userId
                    ]);
                    $savedCount++;
                } else {
                    // Empty date: user cleared an existing date field
                    $deleted = $this->immModel->deleteByPatientVaccineDose($patientId, $vacName, $doseNo, $userId);
                    if ($deleted) {
                        $clearedCount++;
                    }
                }
            }
        }

        $totalChanges = $savedCount + $clearedCount;
        if ($totalChanges > 0) {
            $msgParts = [];
            if ($savedCount > 0) {
                $msgParts[] = "saved {$savedCount} dose date" . ($savedCount > 1 ? 's' : '');
            }
            if ($clearedCount > 0) {
                $msgParts[] = "cleared {$clearedCount} dose date" . ($clearedCount > 1 ? 's' : '');
            }
            $actionDesc = implode(' and ', $msgParts);
            AuditLog::log('EPI_SCHEDULE_SAVED', 'Immunization', "Successfully {$actionDesc} for Child {$patient['patient_no']}");
            $_SESSION['success_message'] = "Successfully {$actionDesc} in the EPI immunization schedule!";
        } else {
            $_SESSION['info_message'] = 'No changes were detected in the EPI vaccination schedule.';
        }

        $this->redirect("/well-baby/{$patientId}");
    }

    /**
     * Delete a growth log entry.
     * 
     * @param int $id
     */
    public function deleteGrowthLog($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !hash_equals(csrf_token(), $token)) {
            $_SESSION['error_message'] = 'Security validation failed (invalid token). Please try again.';
            $this->redirect('/patients');
            return;
        }

        $log = $this->growthModel->findById($id);
        if (!$log) {
            $_SESSION['error_message'] = 'Growth log entry not found.';
            $this->redirect('/patients');
            return;
        }

        $wellbaby = $this->wbModel->findById($log['wellbaby_id']);
        $patientId = $wellbaby ? (int)$wellbaby['patient_id'] : (int)($_POST['patient_id'] ?? 0);
        $currentUserId = (int)($_SESSION['user_id'] ?? 0);
        $userRole = $_SESSION['user_role'] ?? 'staff';
        if (!in_array($userRole, ['admin', 'super_admin'], true) && $currentUserId !== (int)$log['recorded_by']) {
            $_SESSION['error_message'] = 'Unauthorized: you may only remove growth records you recorded.';
            $this->redirect("/well-baby/{$patientId}");
            return;
        }

        $this->growthModel->deleteLog($id);
        AuditLog::log('CHILD_GROWTH_LOG_DELETED', 'Pediatric Care', "Deleted growth log #{$id} for patient ID #{$patientId}");
        $_SESSION['success_message'] = 'Growth log entry removed.';

        $this->redirect($patientId ? "/well-baby/{$patientId}" : "/patients");
    }

    /**
     * Delete an immunization dose record.
     * 
     * @param int $id
     */
    public function deleteImmunization($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Validate CSRF token
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !hash_equals(csrf_token(), $token)) {
            AuditLog::log('SECURITY_VIOLATION', 'Immunization', "CSRF mismatch while attempting to delete immunization #{$id}");
            $_SESSION['error_message'] = 'Security validation failed (invalid token). Please try again.';
            $this->redirect('/patients');
            return;
        }

        $imm = $this->immModel->findById($id);
        if (!$imm) {
            $_SESSION['error_message'] = 'Immunization record not found.';
            $this->redirect('/patients');
            return;
        }

        $patientId = (int)$imm['patient_id'];
        $currentUserId = (int)($_SESSION['user_id'] ?? 0);
        $userRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'staff';
        $canDelete = ($userRole === 'admin' || $currentUserId === (int)$imm['administered_by']);

        $redirectUrl = (!empty($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'well-baby') !== false) 
            ? "/well-baby/{$patientId}" 
            : "/patients/{$patientId}#tab-immunizations";

        if (!$canDelete) {
            $_SESSION['error_message'] = 'Unauthorized: You do not have permission to delete this immunization record.';
            $this->redirect($redirectUrl);
            return;
        }

        $this->immModel->deleteDose($id);
        AuditLog::log('IMMUNIZATION_DELETED', 'Immunization', "Deleted immunization ID #{$id} ({$imm['vaccine_name']} Dose #{$imm['dose_number']}) for patient ID #{$patientId}");

        $_SESSION['success_message'] = 'Immunization record removed successfully.';
        $this->redirect($redirectUrl);
    }
}

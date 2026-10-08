<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Patient;
use App\Models\VitalSigns;
use App\Models\AuditLog;
use PDO;

class PatientController extends Controller {
    protected $patientModel;
    protected $vitalsModel;

    public function __construct() {
        $this->patientModel = new Patient();
        $this->vitalsModel = new VitalSigns();
    }

    /**
     * Display a paginated, filterable list of active patients.
     */
    public function index() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Get filters
        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'sex' => trim($_GET['sex'] ?? ''),
            'age_group' => trim($_GET['age_group'] ?? ''),
            'phic_status' => trim($_GET['phic_status'] ?? '')
        ];

        // Fetch demographic census metrics
        $censusMetrics = $this->patientModel->getCensusMetrics();

        // Fetch patients
        $patients = $this->patientModel->allActive($filters);

        $this->view('patients/index', [
            'patients' => $patients,
            'filters' => $filters,
            'censusMetrics' => $censusMetrics
        ]);
    }

    /**
     * Show the registration form.
     */
    public function create() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Retrieve flashed input/errors
        $errors = $_SESSION['form_errors'] ?? [];
        $input = $_SESSION['form_input'] ?? [];
        $duplicateWarnings = $_SESSION['duplicate_found_records'] ?? [];
        
        unset($_SESSION['form_errors']);
        unset($_SESSION['form_input']);
        unset($_SESSION['duplicate_found_records']);

        $this->view('patients/create', [
            'errors' => $errors,
            'input' => $input,
            'duplicateWarnings' => $duplicateWarnings
        ]);
    }

    /**
     * Handle the patient registration submission.
     */
    public function store() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $errors = $this->validatePatientData($_POST);

        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_input'] = $_POST;
            $this->redirect('/patients/create');
            return;
        }

        // Server-Side Duplicate Patient Safety Net
        $acknowledged = !empty($_POST['duplicate_acknowledged']) && $_POST['duplicate_acknowledged'] === '1';
        if (!$acknowledged) {
            $firstName = $_POST['first_name'] ?? '';
            $lastName = $_POST['last_name'] ?? '';
            $dob = !empty($_POST['dob']) ? $_POST['dob'] : null;

            $duplicates = $this->patientModel->findDuplicates($firstName, $lastName, $dob);
            if (!empty($duplicates)) {
                $_SESSION['duplicate_found_records'] = $duplicates;
                $_SESSION['form_input'] = $_POST;
                $this->redirect('/patients/create');
                return;
            }
        }

        // Save
        $data = $_POST;
        $data['created_by'] = $_SESSION['user_id'];

        $newId = $this->patientModel->create($data);

        if ($newId) {
            $patient = $this->patientModel->findById($newId);
            $patientNo = $patient['patient_no'] ?? 'N/A';
            
            AuditLog::log('PATIENT_REGISTERED', 'Patients', "Registered new patient: " . $_POST['first_name'] . ' ' . $_POST['last_name'] . " ({$patientNo})");
            
            $_SESSION['success_message'] = 'Patient registered successfully!';
            $this->redirect("/patients/{$newId}");
        } else {
            $_SESSION['form_errors'] = ['Database insertion failed. Please try again.'];
            $_SESSION['form_input'] = $_POST;
            $this->redirect('/patients/create');
        }
    }

    /**
     * AJAX action to inspect duplicate patient names.
     */
    public function checkDuplicate() {
        $firstName = trim($_GET['first_name'] ?? '');
        $lastName = trim($_GET['last_name'] ?? '');
        $dob = trim($_GET['dob'] ?? '');
        $excludeId = !empty($_GET['exclude_id']) ? (int)$_GET['exclude_id'] : null;

        if (empty($firstName) && empty($lastName)) {
            $this->json([]);
            return;
        }

        if (empty($lastName)) {
            $this->json([]);
            return;
        }

        $duplicates = $this->patientModel->findDuplicates($firstName, $lastName, $dob, $excludeId);
        $this->json($duplicates);
    }

    /**
     * Display a patient's profile details & clinical history.
     */
    public function show($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $patient = $this->patientModel->findById($id);

        if (!$patient) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        // Get vital signs history & latest record
        $vitalsHistory = $this->vitalsModel->findByPatientId($id);
        $latestVitals = $this->vitalsModel->latestByPatientId($id);

        // Get consultation history & latest encounter
        $consultationsHistory = (new \App\Models\Consultation())->findByPatientId($id);
        $latestConsultation = !empty($consultationsHistory) ? $consultationsHistory[0] : null;
        $latestConsultationPrescriptions = $latestConsultation ? (new \App\Models\Prescription())->findByConsultationId($latestConsultation['id']) : [];

        // Get appointment history
        $appointmentsHistory = (new \App\Models\Appointment())->findByPatientId($id);

        // Get queue history
        $queueHistory = (new \App\Models\QueueEntry())->findByPatientId($id);

        // Get IHP Medical History & Clinical Decision Support Alerts
        $medicalHistory = (new \App\Models\PatientMedicalHistory())->findByPatientId($id);
        $cdsAlerts = \App\Services\ClinicalDecisionService::getAlertsForPatient($id);

        // Get Household Family Members sharing the same family_no
        $familyMembers = !empty($patient['family_no']) 
            ? $this->patientModel->familyMembers($patient['family_no'], $id) 
            : [];

        // Get Active Maternal Prenatal Episode (if female)
        $activePrenatal = false;
        $allPrenatalEpisodes = [];

        if (strtolower($patient['sex']) === 'female') {
            $prenatalModel = new \App\Models\PrenatalRecord();
            $activePrenatal = $prenatalModel->findActiveByPatientId($id);
            $allPrenatalEpisodes = $prenatalModel->findAllByPatientId($id);
        }

        // Get Well Baby Record & Growth Logs (if child 0-5 or registered)
        $wellbabyRecord = (new \App\Models\WellbabyRecord())->findByPatientId($id);
        $growthLogs = [];
        if ($wellbabyRecord) {
            $growthLogs = (new \App\Models\ChildGrowthLog())->findByWellbabyId($wellbabyRecord['id']);
        }

        // Get Immunization records for this patient
        $immModel = new \App\Models\Immunization();
        $patientImmunizations = $immModel->findByPatientId($id);

        // Fetch PhilHealth PCB Obligated Services & Service Encounter Logs (Page 3)
        $pcbModel = new \App\Models\PcbLedger();
        $pcbYear = !empty($_GET['pcb_year']) ? (int)$_GET['pcb_year'] : (int)date('Y');
        $pcbObligated = $pcbModel->getObligatedServices($id, $pcbYear);
        $pcbServiceLogs = $pcbModel->getServiceLogs($id);

        $this->view('patients/show', [
            'patient' => $patient,
            'vitalsHistory' => $vitalsHistory,
            'latestVitals' => $latestVitals,
            'consultationsHistory' => $consultationsHistory,
            'latestConsultation' => $latestConsultation,
            'latestConsultationPrescriptions' => $latestConsultationPrescriptions,
            'appointmentsHistory' => $appointmentsHistory,
            'queueHistory' => $queueHistory,
            'medicalHistory' => $medicalHistory,
            'cdsAlerts' => $cdsAlerts,
            'familyMembers' => $familyMembers,
            'activePrenatal' => $activePrenatal,
            'allPrenatalEpisodes' => $allPrenatalEpisodes,
            'wellbabyRecord' => $wellbabyRecord,
            'growthLogs' => $growthLogs,
            'patientImmunizations' => $patientImmunizations,
            'pcbObligated' => $pcbObligated,
            'pcbServiceLogs' => $pcbServiceLogs,
            'pcbYear' => $pcbYear
        ]);
    }

    /**
     * Show edit demographic form.
     */
    public function edit($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $patient = $this->patientModel->findById($id);

        if (!$patient) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        // Retrieve flashed inputs/errors
        $errors = $_SESSION['form_errors'] ?? [];
        $formInput = $_SESSION['form_input'] ?? [];
        unset($_SESSION['form_errors']);
        unset($_SESSION['form_input']);

        // Merge flashed user input over patient record so corrections are preserved upon validation error
        if (!empty($formInput)) {
            $patient = array_merge($patient, $formInput);
        }

        $this->view('patients/edit', [
            'patient' => $patient,
            'errors' => $errors
        ]);
    }

    /**
     * Process updates to a patient's profile.
     */
    public function update($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $patient = $this->patientModel->findById($id);
        if (!$patient) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $errors = $this->validatePatientData($_POST, $id);

        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_input'] = $_POST;
            $this->redirect("/patients/{$id}/edit");
            return;
        }

        $data = $_POST;
        $data['updated_by'] = $_SESSION['user_id'];

        if ($this->patientModel->update($id, $data)) {
            AuditLog::log('PATIENT_UPDATED', 'Patients', "Updated patient profile: " . $_POST['first_name'] . ' ' . $_POST['last_name'] . " ({$patient['patient_no']})");
            
            $_SESSION['success_message'] = 'Patient demographics updated successfully!';
            $this->redirect("/patients/{$id}");
        } else {
            $_SESSION['form_errors'] = ['Database update failed. Please try again.'];
            $_SESSION['form_input'] = $_POST;
            $this->redirect("/patients/{$id}/edit");
        }
    }

    /**
     * Comprehensive server-side validation for patient demographics.
     * 
     * @param array $input Raw POST input array
     * @param int|null $excludePatientId Patient ID to exclude for PhilHealth uniqueness
     * @return array Array of validation error messages
     */
    protected function validatePatientData(array $input, $excludePatientId = null) {
        $validator = new \App\Validators\PatientValidator($this->patientModel);
        return $validator->validate($input, $excludePatientId);
    }

    /**
     * Archive (soft-delete) a patient record.
     */
    public function archive($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $patient = $this->patientModel->findById($id);
        if (!$patient) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        // Authorization check: Only administrators can archive patient records
        if (!is_admin()) {
            $_SESSION['error_message'] = 'Unauthorized access: Only administrators can archive patient records.';
            $this->redirect("/patients/{$id}");
            return;
        }

        // Validate archive reason
        $reason = $_POST['archive_reason'] ?? '';
        if (empty(trim($reason))) {
            $_SESSION['error_message'] = 'Archive reason is required.';
            $this->redirect("/patients/{$id}");
            return;
        }

        $userId = $_SESSION['user_id'];
        if ($this->patientModel->archive($id, $userId, $reason)) {
            AuditLog::log('PATIENT_ARCHIVED', 'Patients', "Archived patient: {$patient['first_name']} {$patient['last_name']} ({$patient['patient_no']}). Reason: {$reason}");
            $_SESSION['success_message'] = 'Patient record archived successfully.';
            $this->redirect('/patients');
        } else {
            $_SESSION['error_message'] = 'Failed to archive patient. Please try again.';
            $this->redirect("/patients/{$id}");
        }
    }

    /**
     * Display a list of archived patients, consultations, and staff accounts.
     */
    public function archivedIndex() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $activeTab = trim($_GET['tab'] ?? 'patients');
        if (!in_array($activeTab, ['patients', 'consultations', 'users'], true)) {
            $activeTab = 'patients';
        }

        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'date_from' => trim($_GET['date_from'] ?? ''),
            'date_to' => trim($_GET['date_to'] ?? ''),
            'role' => trim($_GET['role'] ?? '')
        ];

        // Isolate search and date filters to the active tab so filtering one tab never mutates or zeroes out others
        $filtersPatients = ($activeTab === 'patients') ? $filters : [];
        $filtersConsultations = ($activeTab === 'consultations') ? $filters : [];
        $filtersUsers = ($activeTab === 'users') ? $filters : [];

        $archivedPatients = $this->patientModel->allArchived($filtersPatients);

        $consultationModel = new \App\Models\Consultation();
        $archivedConsultations = $consultationModel->allArchived($filtersConsultations);
        if (!empty($archivedConsultations)) {
            $prescriptionModel = new \App\Models\Prescription();
            foreach ($archivedConsultations as &$cRecord) {
                $cRecord['prescriptions'] = $prescriptionModel->findByConsultationId($cRecord['id']);
            }
            unset($cRecord);
        }

        $userModel = new \App\Models\User();
        $archivedUsers = $userModel->allArchived($filtersUsers);

        // Baseline total archived counts for tab badges (independent of active search queries)
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            $tabCounts = [
                'patients' => (int)$db->query("SELECT COUNT(*) FROM patients WHERE deleted_at IS NOT NULL")->fetchColumn(),
                'consultations' => (int)$db->query("SELECT COUNT(*) FROM consultations WHERE deleted_at IS NOT NULL")->fetchColumn(),
                'users' => (int)$db->query("SELECT COUNT(*) FROM users WHERE deleted_at IS NOT NULL")->fetchColumn()
            ];
        } catch (\Exception $e) {
            $tabCounts = [
                'patients' => count($archivedPatients),
                'consultations' => count($archivedConsultations),
                'users' => count($archivedUsers)
            ];
        }

        $this->view('archive/patients', [
            'patients' => $archivedPatients,
            'consultations' => $archivedConsultations,
            'users' => $archivedUsers,
            'tabCounts' => $tabCounts,
            'filters' => $filters,
            'activeTab' => $activeTab
        ]);
    }

    /**
     * Restore an archived patient record.
     */
    public function restore($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Retrieve patient details (even if soft-deleted)
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM patients WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
            $patient = $stmt->fetch();
        } catch (\Exception $e) {
            $patient = false;
        }

        if (!$patient) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        if ($this->patientModel->restore($id)) {
            AuditLog::log('PATIENT_RESTORED', 'Patients', "Restored patient: {$patient['first_name']} {$patient['last_name']} ({$patient['patient_no']})");
            $_SESSION['success_message'] = "Patient record for {$patient['first_name']} {$patient['last_name']} ({$patient['patient_no']}) restored successfully.";
            $this->redirect('/archive?tab=patients');
        } else {
            $_SESSION['error_message'] = 'Failed to restore patient. Please try again.';
            $this->redirect('/archive?tab=patients');
        }
    }

    /**
     * AJAX endpoint to search female patients for maternal registration.
     */
    public function searchFemale() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $query = trim($_GET['q'] ?? $_GET['search'] ?? '');
        $db = \App\Core\Database::getInstance()->getConnection();

        $sql = "SELECT p.id, p.patient_no, p.envelope_no, p.first_name, p.last_name, p.middle_name, p.suffix,
                       p.dob, p.civil_status, p.contact_no, p.address, p.barangay,
                       TIMESTAMPDIFF(YEAR, p.dob, CURRENT_DATE()) AS age,
                       (SELECT id FROM prenatal_records pr 
                        WHERE pr.patient_id = p.id AND pr.is_active = 1 AND pr.deleted_at IS NULL LIMIT 1) AS active_episode_id
                FROM patients p
                WHERE p.deleted_at IS NULL 
                  AND LOWER(p.sex) = 'female'";
        
        $params = [];
        if (!empty($query)) {
            $sql .= " AND (p.first_name LIKE :q1 
                        OR p.last_name LIKE :q2 
                        OR p.patient_no LIKE :q3 
                        OR p.envelope_no LIKE :q4
                        OR CONCAT(p.first_name, ' ', p.last_name) LIKE :q5
                        OR CONCAT(p.last_name, ', ', p.first_name) LIKE :q6)";
            $param = '%' . $query . '%';
            $params['q1'] = $param;
            $params['q2'] = $param;
            $params['q3'] = $param;
            $params['q4'] = $param;
            $params['q5'] = $param;
            $params['q6'] = $param;
        }

        $sql .= " ORDER BY p.last_name ASC, p.first_name ASC LIMIT 25";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll() ?: [];

        $data = array_map(function($p) {
            $name = trim($p['last_name'] . ', ' . $p['first_name'] . ' ' . (!empty($p['middle_name']) ? mb_substr($p['middle_name'], 0, 1) . '.' : '') . ' ' . ($p['suffix'] ?? ''));
            $address = !empty(trim($p['address'] ?? '')) ? trim($p['address']) : 'Barangay Sinalhan, Santa Rosa, Laguna';
            
            $age = $p['age'] !== null ? (int)$p['age'] : 0;
            $isWra = ($age >= 10 && $age <= 49);
            $wraNotice = !$isWra ? "Patient age ({$age}) is outside standard Women of Reproductive Age (10–49 yrs)." : null;
            
            return [
                'id' => (int)$p['id'],
                'patient_no' => $p['patient_no'],
                'envelope_no' => $p['envelope_no'],
                'name' => $name,
                'first_name' => $p['first_name'],
                'last_name' => $p['last_name'],
                'middle_name' => $p['middle_name'],
                'dob' => $p['dob'],
                'dob_formatted' => !empty($p['dob']) ? date('M d, Y', strtotime($p['dob'])) : 'N/A',
                'age' => $age,
                'is_wra' => $isWra,
                'wra_notice' => $wraNotice,
                'address' => $address ?: 'Barangay Sinalhan, Santa Rosa, Laguna',
                'contact_no' => $p['contact_no'] ?: 'N/A',
                'civil_status' => $p['civil_status'] ?: 'Single',
                'has_active_episode' => !empty($p['active_episode_id']),
                'active_episode_id' => $p['active_episode_id']
            ];
        }, $results);

        $this->json(['results' => $data]);
    }

    /**
     * AJAX endpoint to search all active patients (male and female, all ages) for appointments and general lookups.
     */
    public function searchAll() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $query = trim($_GET['q'] ?? $_GET['search'] ?? '');
        $db = \App\Core\Database::getInstance()->getConnection();

        $sql = "SELECT p.id, p.patient_no, p.envelope_no, p.first_name, p.last_name, p.middle_name, p.suffix,
                       p.sex, p.dob, p.civil_status, p.contact_no, p.address, p.barangay,
                       TIMESTAMPDIFF(YEAR, p.dob, CURRENT_DATE()) AS age
                FROM patients p
                WHERE p.deleted_at IS NULL";
        
        $params = [];
        if (!empty($query)) {
            $sql .= " AND (p.first_name LIKE :q1 
                        OR p.last_name LIKE :q2 
                        OR p.patient_no LIKE :q3 
                        OR p.envelope_no LIKE :q4
                        OR CONCAT(p.first_name, ' ', p.last_name) LIKE :q5
                        OR CONCAT(p.last_name, ', ', p.first_name) LIKE :q6)";
            $param = '%' . $query . '%';
            $params['q1'] = $param;
            $params['q2'] = $param;
            $params['q3'] = $param;
            $params['q4'] = $param;
            $params['q5'] = $param;
            $params['q6'] = $param;
        }

        $sql .= " ORDER BY p.last_name ASC, p.first_name ASC LIMIT 25";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll() ?: [];

        $data = array_map(function($p) {
            $name = trim($p['last_name'] . ', ' . $p['first_name'] . ' ' . (!empty($p['middle_name']) ? mb_substr($p['middle_name'], 0, 1) . '.' : '') . ' ' . ($p['suffix'] ?? ''));
            $address = !empty(trim($p['address'] ?? '')) ? trim($p['address']) : 'Barangay Sinalhan, Santa Rosa, Laguna';
            
            return [
                'id' => (int)$p['id'],
                'patient_no' => $p['patient_no'],
                'envelope_no' => $p['envelope_no'],
                'name' => $name,
                'first_name' => $p['first_name'],
                'last_name' => $p['last_name'],
                'middle_name' => $p['middle_name'],
                'suffix' => $p['suffix'],
                'sex' => !empty($p['sex']) ? ucfirst(strtolower($p['sex'])) : 'N/A',
                'dob' => $p['dob'],
                'dob_formatted' => !empty($p['dob']) ? date('M d, Y', strtotime($p['dob'])) : 'N/A',
                'age' => $p['age'] !== null ? (int)$p['age'] : 0,
                'address' => $address ?: 'Barangay Sinalhan, Santa Rosa, Laguna',
                'contact_no' => $p['contact_no'] ?: 'N/A',
                'civil_status' => $p['civil_status'] ?: 'Single'
            ];
        }, $results);

        $this->json(['results' => $data]);
    }

    /**
     * AJAX endpoint to get patient identity info and IHP GTPAL data for maternal registration.
     */
    public function getMaternalData($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $patient = $this->patientModel->findById($id);
        if (!$patient || strtolower($patient['sex'] ?? '') !== 'female') {
            $this->json(['error' => 'Female patient not found.'], 404);
            return;
        }

        $prenatalModel = new \App\Models\PrenatalRecord();
        $activeEpisode = $prenatalModel->findActiveByPatientId($id);

        $pmhModel = new \App\Models\PatientMedicalHistory();
        $ihp = $pmhModel->findByPatientId($id);

        $name = trim($patient['last_name'] . ', ' . $patient['first_name'] . ' ' . (!empty($patient['middle_name']) ? mb_substr($patient['middle_name'], 0, 1) . '.' : '') . ' ' . ($patient['suffix'] ?? ''));
        $address = !empty(trim($patient['address'] ?? '')) ? trim($patient['address']) : 'Barangay Sinalhan, Santa Rosa, Laguna';

        $ihpGravida = isset($ihp['gravida']) ? (int)$ihp['gravida'] : null;
        $suggestedGravida = ($ihpGravida !== null && $ihpGravida > 0) ? ($ihpGravida + 1) : 1;

        $response = [
            'patient' => [
                'id' => (int)$patient['id'],
                'patient_no' => $patient['patient_no'],
                'envelope_no' => $patient['envelope_no'],
                'name' => $name,
                'first_name' => $patient['first_name'],
                'last_name' => $patient['last_name'],
                'middle_name' => $patient['middle_name'],
                'dob' => $patient['dob'],
                'dob_formatted' => !empty($patient['dob']) ? date('M d, Y', strtotime($patient['dob'])) : 'N/A',
                'age' => $patient['age'],
                'address' => $address ?: 'Barangay Sinalhan, Santa Rosa, Laguna',
                'contact_no' => $patient['contact_no'] ?: 'N/A',
                'civil_status' => $patient['civil_status'] ?: 'Single',
                'spouse_name' => $patient['spouse_name'] ?? ''
            ],
            'has_active_episode' => !empty($activeEpisode),
            'active_episode' => $activeEpisode ? [
                'id' => $activeEpisode['id'],
                'created_at' => date('M d, Y', strtotime($activeEpisode['created_at'])),
                'lmp' => $activeEpisode['lmp'],
                'edc' => $activeEpisode['edc']
            ] : null,
            'ihp' => [
                'has_ihp' => !empty($ihp),
                'gravida' => $suggestedGravida,
                'para' => isset($ihp['para']) ? (int)$ihp['para'] : 0,
                'term_births' => isset($ihp['term_births']) ? (int)$ihp['term_births'] : 0,
                'preterm_births' => isset($ihp['preterm_births']) ? (int)$ihp['preterm_births'] : 0,
                'abortions' => isset($ihp['abortions']) ? (int)$ihp['abortions'] : 0,
                'living_children' => isset($ihp['living_children']) ? (int)$ihp['living_children'] : 0,
                'lmp' => $ihp['lmp'] ?? ''
            ]
        ];

        $this->json($response);
    }

    /**
     * Export active patient directory records to CSV with official DOH/CHO preamble.
     */
    public function export() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Apply filters
        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'sex' => trim($_GET['sex'] ?? ''),
            'age_group' => trim($_GET['age_group'] ?? ''),
            'phic_status' => trim($_GET['phic_status'] ?? '')
        ];

        $patients = $this->patientModel->allActive($filters);

        $filename = 'patient_directory_' . date('Y-m-d_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // Write UTF-8 BOM for Microsoft Excel compatibility
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        $generatedBy = $_SESSION['user_fullname'] ?? $_SESSION['username'] ?? 'Staff Personnel';
        $filterDesc = [];
        if (!empty($filters['search'])) $filterDesc[] = 'Search: "' . $filters['search'] . '"';
        if (!empty($filters['age_group'])) $filterDesc[] = 'Age: ' . ucfirst($filters['age_group']);
        if (!empty($filters['sex'])) $filterDesc[] = 'Sex: ' . $filters['sex'];
        if (!empty($filters['phic_status'])) $filterDesc[] = 'PhilHealth: ' . ucfirst($filters['phic_status']);
        $filterSummary = !empty($filterDesc) ? implode(', ', $filterDesc) : 'All Active Records';

        // Write official health center metadata preamble block
        fputcsv($output, ['BARANGAY SINALHAN HEALTH CENTER - CITY HEALTH OFFICE OF SANTA ROSA']);
        fputcsv($output, ['System Export Report', 'Master Patient Directory & Census']);
        fputcsv($output, ['Active Filter Scope', $filterSummary]);
        fputcsv($output, ['Total Records Exported', count($patients)]);
        fputcsv($output, ['Exported By', $generatedBy, 'Export Timestamp', date('Y-m-d H:i:s')]);
        fputcsv($output, []); // Blank separator row

        // CSV Column Headers (no barangay column, uses Full Address)
        fputcsv($output, [
            'Patient No.',
            'Last Name',
            'First Name',
            'Middle Name',
            'Suffix',
            'Date of Birth',
            'Age',
            'Sex',
            'Civil Status',
            'Full Address',
            'Contact No.',
            'PhilHealth Status',
            'PhilHealth PIN',
            'Physical Folder / Envelope No.',
            'Family Household Folder No.',
            'Registration Date'
        ]);

        foreach ($patients as $p) {
            $regDate = !empty($p['created_at']) ? date('Y-m-d H:i', strtotime($p['created_at'])) : '-';
            $dobFormatted = !empty($p['dob']) ? date('Y-m-d', strtotime($p['dob'])) : '-';

            fputcsv($output, [
                $p['patient_no'] ?? '',
                $p['last_name'] ?? '',
                $p['first_name'] ?? '',
                $p['middle_name'] ?? '',
                $p['suffix'] ?? '',
                $dobFormatted,
                isset($p['age']) ? $p['age'] . ' yrs' : '',
                $p['sex'] ?? '',
                $p['civil_status'] ?? '',
                $p['address'] ?? '',
                $p['contact_no'] ?? '',
                $p['phic_status'] ?? 'None',
                $p['philhealth_no'] ?? '',
                $p['envelope_no'] ?? '',
                $p['family_no'] ?? '',
                $regDate
            ]);
        }

        fclose($output);
        exit;
    }
}

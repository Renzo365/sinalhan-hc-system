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
            'barangay' => trim($_GET['barangay'] ?? ''),
            'sex' => trim($_GET['sex'] ?? ''),
            'age_group' => trim($_GET['age_group'] ?? '')
        ];

        // Fetch patients
        $patients = $this->patientModel->allActive($filters);

        // Fetch list of unique barangays for filter dropdown
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT DISTINCT barangay FROM patients WHERE deleted_at IS NULL ORDER BY barangay ASC");
            $barangays = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: ['Sinalhan'];
        } catch (\Exception $e) {
            $barangays = ['Sinalhan'];
        }

        $this->view('patients/index', [
            'patients' => $patients,
            'filters' => $filters,
            'barangays' => $barangays
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
        
        unset($_SESSION['form_errors']);
        unset($_SESSION['form_input']);

        $this->view('patients/create', [
            'errors' => $errors,
            'input' => $input
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
        $firstName = $_GET['first_name'] ?? '';
        $lastName = $_GET['last_name'] ?? '';

        if (empty($firstName) || empty($lastName)) {
            $this->json([]);
        }

        $duplicates = $this->patientModel->findDuplicates($firstName, $lastName);
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

        // Get consultation history
        $consultationsHistory = (new \App\Models\Consultation())->findByPatientId($id);

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

        // Get Active Maternal Prenatal Episode, Visits & Past Obstetric History (if female)
        $activePrenatal = false;
        $prenatalVisits = [];
        $pastDeliveries = [];
        $allPrenatalEpisodes = [];

        if (strtolower($patient['sex']) === 'female') {
            $prenatalModel = new \App\Models\PrenatalRecord();
            $activePrenatal = $prenatalModel->findActiveByPatientId($id);
            if ($activePrenatal) {
                $prenatalVisits = (new \App\Models\PrenatalVisit())->findByPrenatalId($activePrenatal['id']);
            }
            $pastDeliveries = (new \App\Models\PastObstetricHistory())->findByPatientId($id);
            $allPrenatalEpisodes = $prenatalModel->findAllByPatientId($id);
        }

        // Get Well Baby Record & Growth Logs (if child 0-5 or registered)
        $wellbabyRecord = (new \App\Models\WellbabyRecord())->findByPatientId($id);
        $growthLogs = [];
        if ($wellbabyRecord) {
            $growthLogs = (new \App\Models\ChildGrowthLog())->findByWellbabyId($wellbabyRecord['id']);
        }

        // Get Immunization records & map for this patient
        $immModel = new \App\Models\Immunization();
        $patientImmunizations = $immModel->findByPatientId($id);
        $vaccineMap = $immModel->getVaccineMap($id);

        // Fetch potential registered mothers for linking
        $potentialMothers = $this->patientModel->findPotentialMothers(100);


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
            'appointmentsHistory' => $appointmentsHistory,
            'queueHistory' => $queueHistory,
            'medicalHistory' => $medicalHistory,
            'cdsAlerts' => $cdsAlerts,
            'familyMembers' => $familyMembers,
            'activePrenatal' => $activePrenatal,
            'prenatalVisits' => $prenatalVisits,
            'pastDeliveries' => $pastDeliveries,
            'allPrenatalEpisodes' => $allPrenatalEpisodes,
            'wellbabyRecord' => $wellbabyRecord,
            'growthLogs' => $growthLogs,
            'patientImmunizations' => $patientImmunizations,
            'vaccineMap' => $vaccineMap,

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
        unset($_SESSION['form_errors']);

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
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
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
     * Display a list of archived patients.
     */
    public function archivedIndex() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'date_from' => trim($_GET['date_from'] ?? ''),
            'date_to' => trim($_GET['date_to'] ?? '')
        ];

        $archivedPatients = $this->patientModel->allArchived($filters);

        $consultationModel = new \App\Models\Consultation();
        $archivedConsultations = $consultationModel->allArchived($filters);

        $activeTab = trim($_GET['tab'] ?? 'patients');
        if (!in_array($activeTab, ['patients', 'consultations'], true)) {
            $activeTab = 'patients';
        }

        $this->view('archive/patients', [
            'patients' => $archivedPatients,
            'consultations' => $archivedConsultations,
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
            $_SESSION['success_message'] = 'Patient record restored successfully.';
            $this->redirect('/patients');
        } else {
            $_SESSION['error_message'] = 'Failed to restore patient. Please try again.';
            $this->redirect('/archive/patients');
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
                        OR p.envelope_no LIKE :q4)";
            $param = '%' . $query . '%';
            $params['q1'] = $param;
            $params['q2'] = $param;
            $params['q3'] = $param;
            $params['q4'] = $param;
        }

        $sql .= " ORDER BY p.last_name ASC, p.first_name ASC LIMIT 25";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll() ?: [];

        $data = array_map(function($p) {
            $name = trim($p['last_name'] . ', ' . $p['first_name'] . ' ' . (!empty($p['middle_name']) ? mb_substr($p['middle_name'], 0, 1) . '.' : '') . ' ' . ($p['suffix'] ?? ''));
            $addressParts = array_filter([$p['address'] ?? '', $p['barangay'] ?? '', 'Santa Rosa, Laguna']);
            $address = implode(', ', $addressParts);
            
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
                'age' => $p['age'],
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
        $addressParts = array_filter([$patient['address'] ?? '', $patient['barangay'] ?? '', 'Santa Rosa, Laguna']);
        $address = implode(', ', $addressParts);

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
}

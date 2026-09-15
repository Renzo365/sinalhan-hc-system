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
            'age_group' => trim($_GET['age_group'] ?? ''),
            'program_type' => trim($_GET['program_type'] ?? '')
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

        // Compute Program Badge
        $programBadge = $this->patientModel->getProgramBadge($id, $patient['dob'], $patient['sex']);

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
            'potentialMothers' => $potentialMothers,
            'programBadge' => $programBadge,
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
}

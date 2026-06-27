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

        $errors = [];
        
        // Basic demographic fields validation
        $requiredFields = [
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'dob' => 'Date of Birth',
            'sex' => 'Sex',
            'civil_status' => 'Civil Status',
            'address' => 'Complete Address'
        ];

        foreach ($requiredFields as $field => $label) {
            if (empty($_POST[$field]) || trim($_POST[$field]) === '') {
                $errors[] = "{$label} is required.";
            }
        }

        // Date of birth bounds check (no future dates)
        if (!empty($_POST['dob'])) {
            $dob = $_POST['dob'];
            if (strtotime($dob) > time()) {
                $errors[] = 'Date of Birth cannot be a future date.';
            }
        }

        // Unique PhilHealth check
        $philhealthNo = !empty($_POST['philhealth_no']) ? trim($_POST['philhealth_no']) : '';
        if ($philhealthNo !== '' && !$this->patientModel->isPhilHealthUnique($philhealthNo)) {
            $errors[] = 'PhilHealth number is already registered to another active patient.';
        }

        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_input'] = $_POST;
            $this->redirect('/patients/create');
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

        // Get vital signs history
        $vitalsHistory = $this->vitalsModel->findByPatientId($id);

        // Get consultation history
        $consultationsHistory = (new \App\Models\Consultation())->findByPatientId($id);

        // Get appointment history
        $appointmentsHistory = (new \App\Models\Appointment())->findByPatientId($id);

        // Get queue history
        $queueHistory = (new \App\Models\QueueEntry())->findByPatientId($id);

        $this->view('patients/show', [
            'patient' => $patient,
            'vitalsHistory' => $vitalsHistory,
            'consultationsHistory' => $consultationsHistory,
            'appointmentsHistory' => $appointmentsHistory,
            'queueHistory' => $queueHistory
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

        $errors = [];

        $requiredFields = [
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'dob' => 'Date of Birth',
            'sex' => 'Sex',
            'civil_status' => 'Civil Status',
            'address' => 'Complete Address'
        ];

        foreach ($requiredFields as $field => $label) {
            if (empty($_POST[$field]) || trim($_POST[$field]) === '') {
                $errors[] = "{$label} is required.";
            }
        }

        if (!empty($_POST['dob'])) {
            if (strtotime($_POST['dob']) > time()) {
                $errors[] = 'Date of Birth cannot be a future date.';
            }
        }

        $philhealthNo = !empty($_POST['philhealth_no']) ? trim($_POST['philhealth_no']) : '';
        if ($philhealthNo !== '' && !$this->patientModel->isPhilHealthUnique($philhealthNo, $id)) {
            $errors[] = 'PhilHealth number is already registered to another active patient.';
        }

        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $this->redirect("/patients/{$id}/edit");
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

        $this->view('archive/patients', [
            'patients' => $archivedPatients,
            'filters' => $filters
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

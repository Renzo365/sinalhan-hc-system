<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\VitalSigns;
use App\Models\AuditLog;
use PDO;

class ConsultationController extends Controller {
    protected $consultationModel;
    protected $patientModel;
    protected $vitalsModel;

    public function __construct() {
        $this->consultationModel = new Consultation();
        $this->patientModel = new Patient();
        $this->vitalsModel = new VitalSigns();
    }

    /**
     * Show the consultation logging form.
     * 
     * @param int $patientId
     */
    public function create($patientId) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $patient = $this->patientModel->findById($patientId);
        if (!$patient) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        // Get vital signs history for linking
        $vitalsList = $this->vitalsModel->findByPatientId($patientId);
        $latestVitals = $this->vitalsModel->getLatestByPatientId($patientId);

        // Get IHP Medical History & Active Prenatal Episode for Clinical Decision Support
        $medicalHistory = (new \App\Models\PatientMedicalHistory())->findByPatientId($patientId);
        $activePrenatal = (new \App\Models\PrenatalRecord())->findActiveByPatientId($patientId);

        // Get active clinicians/staff list
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT id, first_name, last_name, job_title FROM users WHERE status = 'active' ORDER BY last_name ASC, first_name ASC");
            $clinicians = $stmt->fetchAll() ?: [];
        } catch (\Exception $e) {
            $clinicians = [];
        }

        // Retrieve flashed errors/inputs
        $errors = $_SESSION['form_errors'] ?? [];
        $input = $_SESSION['form_input'] ?? [];

        unset($_SESSION['form_errors']);
        unset($_SESSION['form_input']);

        $this->view('consultations/create', [
            'patient' => $patient,
            'vitalsList' => $vitalsList,
            'latestVitals' => $latestVitals,
            'medicalHistory' => $medicalHistory,
            'activePrenatal' => $activePrenatal,
            'clinicians' => $clinicians,
            'errors' => $errors,
            'input' => $input
        ]);
    }

    /**
     * Store new consultation checkup records.
     */
    public function store() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $patientId = (int)($_POST['patient_id'] ?? 0);
        $patient = $this->patientModel->findById($patientId);
        
        if (!$patient) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $errors = [];

        // Validate SOAP required fields
        $requiredFields = [
            'subjective' => 'Subjective Notes (Chief Complaint)',
            'objective' => 'Objective Notes (Physical Findings)',
            'assessment' => 'Assessment (Diagnosis)',
            'plan' => 'Plan (Treatment/Prescriptions)',
            'consulted_by' => 'Consulting Provider',
            'consulted_at' => 'Date & Time of Consultation'
        ];

        foreach ($requiredFields as $field => $label) {
            if (empty($_POST[$field]) || trim($_POST[$field]) === '') {
                $errors[] = "{$label} is required.";
            }
        }

        // Validate consulted_at is not in the future
        if (!empty($_POST['consulted_at'])) {
            if (strtotime($_POST['consulted_at']) > time()) {
                $errors[] = 'Consultation date/time cannot be in the future.';
            }
        }

        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_input'] = $_POST;
            $this->redirect("/patients/{$patientId}/consultations/create");
            return;
        }

        // Save
        $data = $_POST;
        $data['status'] = !empty($_POST['status']) ? $_POST['status'] : 'Completed';
        $data['created_by'] = $_SESSION['user_id'];

        $newId = $this->consultationModel->create($data);

        if ($newId) {
            AuditLog::log('CONSULTATION_CREATED', 'Clinical', "Recorded new consultation SOAP note for patient: {$patient['first_name']} {$patient['last_name']} ({$patient['patient_no']})");
            
            $_SESSION['success_message'] = 'Consultation record saved successfully!';
            $this->redirect("/patients/{$patientId}#tab-consultations");
        } else {
            $_SESSION['form_errors'] = ['Database insertion failed. Please try again.'];
            $_SESSION['form_input'] = $_POST;
            $this->redirect("/patients/{$patientId}/consultations/create");
        }
    }

    /**
     * Show consultation edit form.
     * 
     * @param int $id
     */
    public function edit($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $consultation = $this->consultationModel->findById($id);
        if (!$consultation) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $patientId = $consultation['patient_id'];
        $patient = $this->patientModel->findById($patientId);
        if (!$patient) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        // Permission check: only author or Admin can edit
        $currentUserId = (int)($_SESSION['user_id'] ?? 0);
        $userRole = $_SESSION['role'] ?? 'staff';
        $canEdit = ($userRole === 'admin' || $currentUserId === (int)$consultation['created_by'] || $currentUserId === (int)$consultation['consulted_by']);

        if (!$canEdit) {
            $_SESSION['error_message'] = 'Unauthorized: You do not have permission to edit this consultation.';
            $this->redirect("/patients/{$patientId}#tab-consultations");
            return;
        }

        if ($consultation['status'] === 'Cancelled') {
            $_SESSION['error_message'] = 'Cancelled consultations cannot be edited.';
            $this->redirect("/patients/{$patientId}#tab-consultations");
            return;
        }

        // Get vital signs history for linking
        $vitalsList = $this->vitalsModel->findByPatientId($patientId);
        $latestVitals = $this->vitalsModel->getLatestByPatientId($patientId);

        // Get IHP Medical History & Active Prenatal Episode
        $medicalHistory = (new \App\Models\PatientMedicalHistory())->findByPatientId($patientId);
        $activePrenatal = (new \App\Models\PrenatalRecord())->findActiveByPatientId($patientId);

        // Get clinicians
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT id, first_name, last_name, job_title FROM users WHERE status = 'active' ORDER BY last_name ASC, first_name ASC");
            $clinicians = $stmt->fetchAll() ?: [];
        } catch (\Exception $e) {
            $clinicians = [];
        }

        $errors = $_SESSION['form_errors'] ?? [];
        $input = $_SESSION['form_input'] ?? $consultation;

        unset($_SESSION['form_errors']);
        unset($_SESSION['form_input']);

        $this->view('consultations/edit', [
            'consultation' => $consultation,
            'patient' => $patient,
            'vitalsList' => $vitalsList,
            'latestVitals' => $latestVitals,
            'medicalHistory' => $medicalHistory,
            'activePrenatal' => $activePrenatal,
            'clinicians' => $clinicians,
            'errors' => $errors,
            'input' => $input
        ]);
    }

    /**
     * Update an existing consultation.
     * 
     * @param int $id
     */
    public function update($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $consultation = $this->consultationModel->findById($id);
        if (!$consultation) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $patientId = (int)$consultation['patient_id'];
        $patient = $this->patientModel->findById($patientId);

        // Permission check
        $currentUserId = (int)($_SESSION['user_id'] ?? 0);
        $userRole = $_SESSION['role'] ?? 'staff';
        $canEdit = ($userRole === 'admin' || $currentUserId === (int)$consultation['created_by'] || $currentUserId === (int)$consultation['consulted_by']);

        if (!$canEdit) {
            $_SESSION['error_message'] = 'Unauthorized: You do not have permission to modify this consultation.';
            $this->redirect("/patients/{$patientId}#tab-consultations");
            return;
        }

        if ($consultation['status'] === 'Cancelled') {
            $_SESSION['error_message'] = 'Cancelled consultations cannot be updated.';
            $this->redirect("/patients/{$patientId}#tab-consultations");
            return;
        }

        $errors = [];
        $requiredFields = [
            'subjective' => 'Subjective Notes (Chief Complaint)',
            'objective' => 'Objective Notes (Physical Findings)',
            'assessment' => 'Assessment (Diagnosis)',
            'plan' => 'Plan (Treatment/Prescriptions)',
            'consulted_by' => 'Consulting Provider',
            'consulted_at' => 'Date & Time of Consultation'
        ];

        foreach ($requiredFields as $field => $label) {
            if (empty($_POST[$field]) || trim($_POST[$field]) === '') {
                $errors[] = "{$label} is required.";
            }
        }

        if (!empty($_POST['consulted_at'])) {
            if (strtotime($_POST['consulted_at']) > time()) {
                $errors[] = 'Consultation date/time cannot be in the future.';
            }
        }

        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_input'] = $_POST;
            $this->redirect("/consultations/{$id}/edit");
            return;
        }

        $data = $_POST;
        $data['status'] = !empty($_POST['status']) ? $_POST['status'] : ($consultation['status'] ?: 'Completed');
        $data['updated_by'] = $currentUserId;

        $updated = $this->consultationModel->update($id, $data);

        if ($updated) {
            $patientLabel = $patient ? "{$patient['first_name']} {$patient['last_name']} ({$patient['patient_no']})" : "Patient #{$patientId}";
            AuditLog::log('CONSULTATION_UPDATED', 'Clinical', "Updated consultation SOAP note (#{$id}) for patient: {$patientLabel}");

            $_SESSION['success_message'] = 'Consultation record updated successfully!';
            $this->redirect("/patients/{$patientId}#tab-consultations");
        } else {
            $_SESSION['form_errors'] = ['Database update failed. Please try again.'];
            $_SESSION['form_input'] = $_POST;
            $this->redirect("/consultations/{$id}/edit");
        }
    }

    /**
     * Cancel / void an existing consultation.
     * 
     * @param int $id
     */
    public function cancel($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $consultation = $this->consultationModel->findById($id);
        if (!$consultation) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $patientId = (int)$consultation['patient_id'];
        $currentUserId = (int)($_SESSION['user_id'] ?? 0);
        $userRole = $_SESSION['role'] ?? 'staff';
        $canCancel = ($userRole === 'admin' || $currentUserId === (int)$consultation['created_by'] || $currentUserId === (int)$consultation['consulted_by']);

        if (!$canCancel) {
            $_SESSION['error_message'] = 'Unauthorized: You do not have permission to cancel this consultation.';
            $this->redirect("/patients/{$patientId}#tab-consultations");
            return;
        }

        $reason = $_POST['reason'] ?? 'Voided by clinician';
        $this->consultationModel->cancel($id, $currentUserId, $reason);

        AuditLog::log('CONSULTATION_CANCELLED', 'Clinical', "Voided consultation SOAP record #{$id} for patient #{$patientId}. Reason: {$reason}");

        $_SESSION['success_message'] = 'Consultation has been cancelled.';
        $this->redirect("/patients/{$patientId}#tab-consultations");
    }

    /**
     * AJAX endpoint to fetch detailed consultation data in JSON format.
     * 
     * @param int $id
     */
    public function show($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $consultation = $this->consultationModel->findById($id);

        if (!$consultation) {
            $this->json(['error' => 'Consultation record not found.'], 404);
            return;
        }

        $currentUserId = (int)($_SESSION['user_id'] ?? 0);
        $userRole = $_SESSION['role'] ?? 'staff';

        // Check if current user can edit
        $consultation['can_edit'] = ($userRole === 'admin' || $currentUserId === (int)$consultation['created_by'] || $currentUserId === (int)$consultation['consulted_by']) && ($consultation['status'] !== 'Cancelled');
        
        // Formatting date helpers
        $consultation['formatted_date'] = date('F d, Y h:i A', strtotime($consultation['consulted_at']));
        if (!empty($consultation['updated_at']) && $consultation['updated_at'] !== $consultation['created_at']) {
            $consultation['formatted_updated'] = date('F d, Y h:i A', strtotime($consultation['updated_at']));
        } else {
            $consultation['formatted_updated'] = null;
        }

        $this->json($consultation);
    }
}

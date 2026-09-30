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
    public function create($patientId = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $patientId = (int)($patientId ?: ($_GET['patient_id'] ?? 0));
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
        $cdsAlerts = \App\Services\ClinicalDecisionService::getAlertsForPatient($patientId);

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
            'cdsAlerts' => $cdsAlerts,
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

        $errors = $this->validateConsultationInput($_POST);

        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_input'] = $_POST;
            $this->redirect("/patients/{$patientId}/consultations/create");
            return;
        }

        $vitalSignsId = !empty($_POST['vital_signs_id']) ? (int)$_POST['vital_signs_id'] : null;

        // Save Consultation
        $data = $_POST;
        $data['vital_signs_id'] = $vitalSignsId;
        $data['consulting_provider'] = trim($_POST['consulting_provider'] ?? '');
        $data['status'] = !empty($_POST['status']) ? $_POST['status'] : 'Completed';
        $data['created_by'] = $_SESSION['user_id'];

        $newId = $this->consultationModel->create($data);

        if ($newId) {
            if (!empty($_POST['prescriptions']) && is_array($_POST['prescriptions'])) {
                (new \App\Models\Prescription())->syncForConsultation(
                    $newId,
                    $patientId,
                    $_SESSION['user_id'],
                    $_POST['prescriptions'],
                    $data['consulted_at'] ?? null
                );
            }

            AuditLog::log('CONSULTATION_CREATED', 'Clinical', "Recorded new consultation ledger entry for patient: {$patient['first_name']} {$patient['last_name']} ({$patient['patient_no']})");
            
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

        $userRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'staff';
        if (!in_array($userRole, ['admin', 'super_admin', 'staff'], true)) {
            $_SESSION['error_message'] = 'Unauthorized: You do not have permission to edit consultations.';
            $this->redirect("/patients/{$patientId}#tab-consultations");
            return;
        }

        // Get vital signs history for linking
        $vitalsList = $this->vitalsModel->findByPatientId($patientId);
        $latestVitals = $this->vitalsModel->getLatestByPatientId($patientId);

        // Get IHP Medical History & Active Prenatal Episode
        $medicalHistory = (new \App\Models\PatientMedicalHistory())->findByPatientId($patientId);
        $activePrenatal = (new \App\Models\PrenatalRecord())->findActiveByPatientId($patientId);
        $cdsAlerts = \App\Services\ClinicalDecisionService::getAlertsForPatient($patientId);

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

        $dbPrescriptions = (new \App\Models\Prescription())->findByConsultationId($id);
        $prescriptions = !empty($input['prescriptions']) ? $input['prescriptions'] : $dbPrescriptions;

        $this->view('consultations/edit', [
            'consultation' => $consultation,
            'patient' => $patient,
            'vitalsList' => $vitalsList,
            'latestVitals' => $latestVitals,
            'medicalHistory' => $medicalHistory,
            'activePrenatal' => $activePrenatal,
            'cdsAlerts' => $cdsAlerts,
            'clinicians' => $clinicians,
            'prescriptions' => $prescriptions,
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

        $currentUserId = (int)($_SESSION['user_id'] ?? 0);

        $userRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'staff';
        if (!in_array($userRole, ['admin', 'super_admin', 'staff'], true)) {
            $_SESSION['error_message'] = 'Unauthorized: You do not have permission to update consultations.';
            $this->redirect("/patients/{$patientId}#tab-consultations");
            return;
        }

        $errors = $this->validateConsultationInput($_POST);

        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_input'] = $_POST;
            $this->redirect("/consultations/{$id}/edit");
            return;
        }

        $data = $_POST;
        $data['consulting_provider'] = trim($_POST['consulting_provider'] ?? '');
        $data['status'] = 'Completed';
        $data['updated_by'] = $currentUserId;

        $updated = $this->consultationModel->update($id, $data);

        if ($updated) {
            $prescriptionItems = !empty($_POST['prescriptions']) && is_array($_POST['prescriptions']) ? $_POST['prescriptions'] : [];
            (new \App\Models\Prescription())->syncForConsultation(
                $id,
                $patientId,
                $currentUserId,
                $prescriptionItems,
                $data['consulted_at'] ?? $consultation['consulted_at'] ?? null
            );

            $patientLabel = $patient ? "{$patient['first_name']} {$patient['last_name']} ({$patient['patient_no']})" : "Patient #{$patientId}";
            AuditLog::log('CONSULTATION_UPDATED', 'Clinical', "Updated consultation ledger entry (#{$id}) for patient: {$patientLabel}");

            $_SESSION['success_message'] = 'Consultation record updated successfully!';
            $this->redirect("/patients/{$patientId}#tab-consultations");
        } else {
            $_SESSION['form_errors'] = ['Database update failed. Please try again.'];
            $_SESSION['form_input'] = $_POST;
            $this->redirect("/consultations/{$id}/edit");
        }
    }

    /**
     * Keep consultation create and update validation consistent.
     *
     * @param array $input
     * @return array
     */
    private function validateConsultationInput($input) {
        $validator = new \App\Validators\ConsultationValidator();
        return $validator->validate($input);
    }

    /**
     * Cancel / void an existing consultation (delegates to standard archive workflow).
     * 
     * @param int $id
     */
    public function cancel($id) {
        $this->archive($id);
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
            $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
            if (strpos($accept, 'text/html') !== false && strpos($accept, 'application/json') === false) {
                http_response_code(404);
                $this->view('errors/404');
                return;
            }
            $this->json(['error' => 'Consultation record not found.'], 404);
            return;
        }

        // If accessed directly from browser URL navigation (HTML), redirect to patient consultations tab
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        if (!$isAjax && strpos($accept, 'text/html') !== false && strpos($accept, 'application/json') === false) {
            $this->redirect("/patients/{$consultation['patient_id']}#tab-consultations");
            return;
        }

        $userRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'staff';

        // Authorized staff and admins can edit active consultations.
        $consultation['can_edit'] = in_array($userRole, ['admin', 'super_admin', 'staff'], true);
        
        // Formatting date helpers
        $consultation['formatted_date'] = date('F d, Y h:i A', strtotime($consultation['consulted_at']));
        $consultation['formatted_created'] = date('F d, Y h:i A', strtotime($consultation['created_at']));
        if (!empty($consultation['updated_at']) && $consultation['updated_at'] !== $consultation['created_at']) {
            $consultation['formatted_updated'] = date('F d, Y h:i A', strtotime($consultation['updated_at']));
        } else {
            $consultation['formatted_updated'] = null;
        }

        $consultation['prescriptions'] = (new \App\Models\Prescription())->findByConsultationId($id);

        $this->json($consultation);
    }

    /**
     * Archive (soft-delete) an existing consultation record.
     * 
     * @param int $id
     */
    public function archive($id) {
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
        $canArchive = is_admin();

        if (!$canArchive) {
            $_SESSION['error_message'] = 'Unauthorized: Only administrators can archive consultation records.';
            $this->redirect("/patients/{$patientId}#tab-consultations");
            return;
        }

        $reason = trim($_POST['reason'] ?? '') ?: 'Archived by administrator';
        $this->consultationModel->archive($id, $currentUserId, $reason);

        AuditLog::log('CONSULTATION_ARCHIVED', 'Consultations', "Archived consultation ID #{$id} for patient: {$consultation['pat_first']} {$consultation['pat_last']}");

        $_SESSION['success_message'] = 'Consultation record archived successfully.';
        $this->redirect("/patients/{$patientId}#tab-consultations");
    }

    /**
     * Restore an archived consultation record.
     * 
     * @param int $id
     */
    public function restore($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Enforce Admin/Super Admin role
        $userRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'staff';
        if (!in_array($userRole, ['admin', 'super_admin'], true)) {
            $_SESSION['error_message'] = 'Unauthorized: Only administrators can restore archived consultations.';
            $this->redirect('/archive/patients?tab=consultations');
            return;
        }

        if ($this->consultationModel->restore($id)) {
            AuditLog::log('CONSULTATION_RESTORED', 'Consultations', "Restored consultation ID #{$id}");
            $_SESSION['success_message'] = 'Consultation record restored successfully.';
        } else {
            $_SESSION['error_message'] = 'Failed to restore consultation. Please try again.';
        }

        $this->redirect('/archive/patients?tab=consultations');
    }
}

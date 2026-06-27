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
            'status' => 'Consultation Status',
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
        }

        // Save
        $data = $_POST;
        $data['created_by'] = $_SESSION['user_id'];

        $newId = $this->consultationModel->create($data);

        if ($newId) {
            AuditLog::log('CONSULTATION_CREATED', 'Clinical', "Recorded new consultation SOAP note for patient: " . $patient['first_name'] . ' ' . $patient['last_name'] . " ({$patient['patient_no']})");
            
            $_SESSION['success_message'] = 'Consultation record saved successfully!';
            // Redirect back to profile page and highlight consultations tab via url hash
            $this->redirect("/patients/{$patientId}#consultations-tab");
        } else {
            $_SESSION['form_errors'] = ['Database insertion failed. Please try again.'];
            $_SESSION['form_input'] = $_POST;
            $this->redirect("/patients/{$patientId}/consultations/create");
        }
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
        }

        // Formatting date helper prior to json delivery
        $consultation['formatted_date'] = date('F d, Y h:i A', strtotime($consultation['consulted_at']));
        
        $this->json($consultation);
    }
}

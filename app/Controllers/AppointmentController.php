<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\AuditLog;
use PDO;

class AppointmentController extends Controller {
    protected $appointmentModel;
    protected $patientModel;

    public function __construct() {
        $this->appointmentModel = new Appointment();
        $this->patientModel = new Patient();
    }

    /**
     * Display a filtered list of appointments.
     */
    public function index() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $filters = [
            'date_from' => trim($_GET['date_from'] ?? ''),
            'date_to' => trim($_GET['date_to'] ?? ''),
            'status' => trim($_GET['status'] ?? ''),
            'search' => trim($_GET['search'] ?? '')
        ];

        $appointments = $this->appointmentModel->findAll($filters);

        $this->view('appointments/index', [
            'appointments' => $appointments,
            'filters' => $filters
        ]);
    }

    /**
     * Show the appointment scheduling form.
     */
    public function create() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $patientId = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : null;
        $patient = null;
        $patients = [];

        if ($patientId) {
            $patient = $this->patientModel->findById($patientId);
        }

        // If no specific patient is pre-selected, fetch all active patients for search selection
        if (!$patient) {
            $patients = $this->patientModel->allActive();
        }

        $errors = $_SESSION['form_errors'] ?? [];
        $input = $_SESSION['form_input'] ?? [];

        unset($_SESSION['form_errors']);
        unset($_SESSION['form_input']);

        $this->view('appointments/create', [
            'patient' => $patient,
            'patients' => $patients,
            'errors' => $errors,
            'input' => $input
        ]);
    }

    /**
     * Save a new appointment.
     */
    public function store() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $patientId = (int)($_POST['patient_id'] ?? 0);
        $patient = $this->patientModel->findById($patientId);

        if (!$patient) {
            $_SESSION['form_errors'] = ['Selected patient record does not exist.'];
            $_SESSION['form_input'] = $_POST;
            $this->redirect('/appointments/create');
            return;
        }

        $errors = [];
        $requiredFields = [
            'appointment_date' => 'Appointment Date',
            'appointment_time' => 'Appointment Time',
            'purpose' => 'Purpose of Visit'
        ];

        foreach ($requiredFields as $field => $label) {
            if (empty($_POST[$field]) || trim($_POST[$field]) === '') {
                $errors[] = "{$label} is required.";
            }
        }

        // Validate date is not in the past
        if (!empty($_POST['appointment_date'])) {
            $today = date('Y-m-d');
            if ($_POST['appointment_date'] < $today) {
                $errors[] = 'Appointment date cannot be in the past.';
            }
        }

        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_input'] = $_POST;
            $this->redirect('/appointments/create' . ($patientId ? "?patient_id={$patientId}" : ''));
            return;
        }

        $programType = trim($_POST['program_type'] ?? 'General OPD');
        $validPrograms = ['General OPD', 'Prenatal Care', 'Well Baby Immunization', 'Senior Care', 'Family Planning', 'Dental Care', 'NCD / Hypertension'];
        if (!in_array($programType, $validPrograms)) {
            $programType = 'General OPD';
        }

        $data = [
            'patient_id' => $patientId,
            'appointment_date' => $_POST['appointment_date'],
            'appointment_time' => $_POST['appointment_time'],
            'purpose' => $_POST['purpose'],
            'program_type' => $programType,
            'status' => $_POST['status'] ?? 'Scheduled',
            'notes' => $_POST['notes'] ?? '',
            'created_by' => $_SESSION['user_id']
        ];

        $newId = $this->appointmentModel->create($data);

        if ($newId) {
            AuditLog::log('APPOINTMENT_CREATED', 'Appointments', "Scheduled [{$programType}] appointment for patient: " . $patient['first_name'] . ' ' . $patient['last_name'] . " on " . date('M d, Y', strtotime($data['appointment_date'])) . " at " . date('h:i A', strtotime($data['appointment_time'])));
            
            $_SESSION['success_message'] = 'Appointment scheduled successfully!';
            $this->redirect("/patients/{$patientId}#appointments-tab");
        } else {
            $_SESSION['form_errors'] = ['Database transaction failed. Please try again.'];
            $_SESSION['form_input'] = $_POST;
            $this->redirect('/appointments/create' . ($patientId ? "?patient_id={$patientId}" : ''));
        }
    }

    /**
     * Show appointment edit/reschedule form.
     */
    public function edit($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $appointment = $this->appointmentModel->findById($id);

        if (!$appointment) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $errors = $_SESSION['form_errors'] ?? [];
        unset($_SESSION['form_errors']);

        $this->view('appointments/edit', [
            'appointment' => $appointment,
            'errors' => $errors
        ]);
    }

    /**
     * Process updates to an appointment.
     */
    public function update($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $appointment = $this->appointmentModel->findById($id);

        if (!$appointment) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $errors = [];
        $requiredFields = [
            'appointment_date' => 'Appointment Date',
            'appointment_time' => 'Appointment Time',
            'purpose' => 'Purpose of Visit',
            'status' => 'Status'
        ];

        foreach ($requiredFields as $field => $label) {
            if (empty($_POST[$field]) || trim($_POST[$field]) === '') {
                $errors[] = "{$label} is required.";
            }
        }

        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $this->redirect("/appointments/{$id}/edit");
            return;
        }

        $programType = trim($_POST['program_type'] ?? 'General OPD');
        $validPrograms = ['General OPD', 'Prenatal Care', 'Well Baby Immunization', 'Senior Care', 'Family Planning', 'Dental Care', 'NCD / Hypertension'];
        if (!in_array($programType, $validPrograms)) {
            $programType = 'General OPD';
        }

        $data = [
            'appointment_date' => $_POST['appointment_date'],
            'appointment_time' => $_POST['appointment_time'],
            'purpose' => $_POST['purpose'],
            'program_type' => $programType,
            'status' => $_POST['status'],
            'notes' => $_POST['notes'] ?? '',
            'updated_by' => $_SESSION['user_id']
        ];

        if ($this->appointmentModel->update($id, $data)) {
            AuditLog::log('APPOINTMENT_UPDATED', 'Appointments', "Updated [{$programType}] appointment ID: {$id} for patient ID: " . $appointment['patient_id'] . " to " . date('M d, Y', strtotime($data['appointment_date'])) . " (" . $data['status'] . ")");
            
            $_SESSION['success_message'] = 'Appointment details updated successfully!';
            $this->redirect("/patients/{$appointment['patient_id']}#appointments-tab");
        } else {
            $_SESSION['form_errors'] = ['Database transaction failed. Please try again.'];
            $this->redirect("/appointments/{$id}/edit");
        }
    }

    /**
     * Quick status transition update.
     */
    public function updateStatus($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $appointment = $this->appointmentModel->findById($id);

        if (!$appointment) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Appointment not found.'], 404);
            } else {
                http_response_code(404);
                $this->view('errors/404');
            }
            return;
        }

        $status = $_POST['status'] ?? '';
        $validStatuses = ['Scheduled', 'Completed', 'Cancelled', 'Missed'];

        if (!in_array($status, $validStatuses)) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Invalid status option.'], 400);
            } else {
                $_SESSION['error_message'] = 'Invalid status option.';
                $this->redirect("/appointments");
            }
            return;
        }

        $userId = $_SESSION['user_id'];

        if ($this->appointmentModel->updateStatus($id, $status, $userId)) {
            AuditLog::log('APPOINTMENT_STATUS_UPDATED', 'Appointments', "Marked appointment ID: {$id} for patient: " . $appointment['patient_first'] . " " . $appointment['patient_last'] . " as {$status}");
            
            if ($this->isAjax()) {
                $this->json(['success' => true, 'message' => "Appointment marked as {$status}."]);
            } else {
                $_SESSION['success_message'] = "Appointment marked as {$status} successfully.";
                // Redirect back to profile page tab if possible, otherwise to lists
                $referrer = $_SERVER['HTTP_REFERER'] ?? '';
                if (strpos($referrer, 'patients') !== false) {
                    $this->redirect("/patients/{$appointment['patient_id']}#appointments-tab");
                } else {
                    $this->redirect("/appointments");
                }
            }
        } else {
            if ($this->isAjax()) {
                $this->json(['error' => 'Failed to update database.'], 500);
            } else {
                $_SESSION['error_message'] = 'Failed to update appointment status.';
                $this->redirect("/appointments");
            }
        }
    }

    /**
     * AJAX endpoint to check schedule conflicts.
     */
    public function checkConflict() {
        $date = $_GET['date'] ?? '';
        $time = $_GET['time'] ?? '';
        $excludeId = isset($_GET['exclude_id']) ? (int)$_GET['exclude_id'] : null;

        if (empty($date) || empty($time)) {
            $this->json(['conflict' => false]);
            return;
        }

        $hasConflict = $this->appointmentModel->hasConflict($date, $time, $excludeId);
        $this->json(['conflict' => $hasConflict]);
    }

    /**
     * Check if request is AJAX.
     */
    private function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
}

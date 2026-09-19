<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\VitalSigns;
use App\Models\Patient;
use App\Models\AuditLog;

class VitalSignsController extends Controller {
    protected $vitalsModel;
    protected $patientModel;

    public function __construct() {
        $this->vitalsModel = new VitalSigns();
        $this->patientModel = new Patient();
    }

    /**
     * Store new vital signs record.
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

        // Validate that at least one metric is filled
        $metrics = [
            'bp_systolic', 'bp_diastolic', 'heart_rate', 
            'respiratory_rate', 'temperature', 'weight', 
            'height', 'oxygen_saturation', 'notes'
        ];

        $hasMetric = false;
        foreach ($metrics as $metric) {
            if (isset($_POST[$metric]) && trim($_POST[$metric]) !== '') {
                $hasMetric = true;
                break;
            }
        }

        $redirectTo = !empty($_POST['redirect_to']) ? $_POST['redirect_to'] : "/patients/{$patientId}#tab-vitals";

        if (!$hasMetric) {
            $_SESSION['error_message'] = 'At least one vital sign value must be filled.';
            $this->redirect($redirectTo);
            return;
        }

        // Server-Side BMI Calculation
        $weight = !empty($_POST['weight']) ? (float)$_POST['weight'] : null;
        $height = !empty($_POST['height']) ? (float)$_POST['height'] : null;
        $bmi = null;

        if ($weight && $height && $height > 0) {
            $heightInMeters = $height / 100;
            $bmi = round($weight / ($heightInMeters * $heightInMeters), 2);
        }

        // Save vital signs
        $data = $_POST;
        $data['bmi'] = $bmi;
        $data['recorded_by'] = $_SESSION['user_id'];

        $newId = $this->vitalsModel->create($data);

        if ($newId) {
            AuditLog::log('VITAL_SIGNS_RECORDED', 'Patients', "Recorded vital signs for patient: " . $patient['first_name'] . ' ' . $patient['last_name'] . " ({$patient['patient_no']})");
            $_SESSION['success_message'] = 'Vital signs recorded successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to save vital signs. Please try again.';
        }

        $this->redirect($redirectTo);
    }

    /**
     * Delete a vital signs record.
     * 
     * @param int $id
     */
    public function delete($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Validate CSRF token
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !hash_equals(csrf_token(), $token)) {
            AuditLog::log('SECURITY_VIOLATION', 'Patients', "CSRF mismatch while attempting to delete vital signs #{$id}");
            $_SESSION['error_message'] = 'Security validation failed (invalid token). Please try again.';
            $this->redirect('/patients');
            return;
        }

        $vital = $this->vitalsModel->findById($id);
        if (!$vital) {
            $_SESSION['error_message'] = 'Vital signs record not found.';
            $this->redirect('/patients');
            return;
        }

        $patientId = (int)$vital['patient_id'];
        $currentUserId = (int)($_SESSION['user_id'] ?? 0);
        $userRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'staff';
        $canDelete = ($userRole === 'admin' || $currentUserId === (int)$vital['recorded_by']);

        if (!$canDelete) {
            $_SESSION['error_message'] = 'Unauthorized: You do not have permission to delete this vital signs record.';
            $this->redirect("/patients/{$patientId}#tab-vitals");
            return;
        }

        // Safety Check: Ensure vital sign is NOT linked to an active consultation
        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id FROM consultations WHERE vital_signs_id = :id AND deleted_at IS NULL LIMIT 1");
        $stmt->execute(['id' => $id]);
        if ($stmt->fetch()) {
            $_SESSION['error_message'] = 'Cannot delete vital signs linked to an active consultation record.';
            $this->redirect("/patients/{$patientId}#tab-vitals");
            return;
        }

        $this->vitalsModel->delete($id);
        AuditLog::log('VITAL_SIGNS_DELETED', 'Patients', "Deleted vital signs ID #{$id}");

        $_SESSION['success_message'] = 'Vital signs record deleted successfully.';
        $this->redirect("/patients/{$patientId}#tab-vitals");
    }
}

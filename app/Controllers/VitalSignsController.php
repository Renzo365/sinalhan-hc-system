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

        if (!$hasMetric) {
            $_SESSION['error_message'] = 'At least one vital sign value must be filled.';
            $this->redirect("/patients/{$patientId}");
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

        $this->redirect("/patients/{$patientId}");
    }
}

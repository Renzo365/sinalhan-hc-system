<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Patient;
use App\Models\PcbLedger;
use App\Models\AuditLog;

class PcbLedgerController extends Controller {
    protected $patientModel;
    protected $pcbModel;

    public function __construct() {
        $this->patientModel = new Patient();
        $this->pcbModel = new PcbLedger();
    }

    /**
     * Save or update annual PhilHealth PCB Obligated Services.
     * 
     * @param int $patientId
     */
    public function saveObligated($patientId) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $patient = $this->patientModel->findById($patientId);
        if (!$patient) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        // Verify CSRF Token
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !hash_equals(csrf_token(), $token)) {
            $_SESSION['form_errors'] = ['Security validation failed (CSRF token mismatch). Please refresh and try again.'];
            $this->redirect("/patients/{$patientId}#tab-pcb");
            return;
        }

        $year = !empty($_POST['service_year']) ? (int)$_POST['service_year'] : (int)date('Y');
        
        // Helper to validate date or return null
        $cleanDate = function($val) {
            $val = trim((string)$val);
            if (empty($val)) return null;
            $d = \DateTime::createFromFormat('Y-m-d', $val);
            return ($d && $d->format('Y-m-d') === $val) ? $val : null;
        };

        $data = [
            'service_year' => $year,
            'is_hypertensive' => !empty($_POST['is_hypertensive']) ? 1 : 0,
            'bp_q1' => $cleanDate($_POST['bp_q1'] ?? ''),
            'bp_q2' => $cleanDate($_POST['bp_q2'] ?? ''),
            'bp_q3' => $cleanDate($_POST['bp_q3'] ?? ''),
            'bp_q4' => $cleanDate($_POST['bp_q4'] ?? ''),
            'cbe_q1' => $cleanDate($_POST['cbe_q1'] ?? ''),
            'cbe_q2' => $cleanDate($_POST['cbe_q2'] ?? ''),
            'cbe_q3' => $cleanDate($_POST['cbe_q3'] ?? ''),
            'cbe_q4' => $cleanDate($_POST['cbe_q4'] ?? ''),
            'via_q1' => $cleanDate($_POST['via_q1'] ?? ''),
            'via_q2' => $cleanDate($_POST['via_q2'] ?? ''),
            'via_q3' => $cleanDate($_POST['via_q3'] ?? ''),
            'via_q4' => $cleanDate($_POST['via_q4'] ?? ''),
            'remarks' => trim($_POST['remarks'] ?? ''),
            'updated_by' => $_SESSION['user_id'] ?? 1
        ];

        $saved = $this->pcbModel->saveObligatedServices($patientId, $data);

        if ($saved) {
            AuditLog::log(
                'PCB_OBLIGATED_UPDATED',
                'Patients',
                "Updated PhilHealth PCB Obligated Services ({$year}) for {$patient['patient_no']} ({$patient['first_name']} {$patient['last_name']})"
            );
            $_SESSION['success_message'] = "PhilHealth PCB Obligated Services for {$year} updated successfully.";
        } else {
            $_SESSION['form_errors'] = ['Failed to update PhilHealth PCB Obligated Services. Please try again.'];
        }

        $this->redirect("/patients/{$patientId}#tab-pcb");
    }

    /**
     * Store a diagnostic examination, PCB1, or other service encounter.
     * 
     * @param int $patientId
     */
    public function storeLog($patientId) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $patient = $this->patientModel->findById($patientId);
        if (!$patient) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $serviceCategory = trim($_POST['service_category'] ?? 'Diagnostic');
        if (!in_array($serviceCategory, ['Diagnostic', 'PCB1', 'Other'], true)) {
            $serviceCategory = 'Diagnostic';
        }

        $serviceDate = trim($_POST['service_date'] ?? '');
        $serviceType = trim($_POST['service_type'] ?? '');
        $diagnosis = trim($_POST['diagnosis'] ?? '');
        $statusGiven = !empty($_POST['status_given']) ? 1 : 0;
        $statusReferred = !empty($_POST['status_referred']) ? 1 : 0;
        $referredTo = trim($_POST['referred_to'] ?? '');
        $remarks = trim($_POST['remarks'] ?? '');

        $errors = [];
        if (empty($serviceDate)) {
            $errors[] = 'Service date is required.';
        }
        if (empty($serviceType)) {
            $errors[] = 'Service or diagnostic test name is required.';
        }

        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $this->redirect("/patients/{$patientId}#tab-pcb");
            return;
        }

        $data = [
            'patient_id' => $patientId,
            'service_category' => $serviceCategory,
            'service_date' => $serviceDate,
            'diagnosis' => $diagnosis,
            'service_type' => $serviceType,
            'status_given' => $statusGiven,
            'status_referred' => $statusReferred,
            'referred_to' => $referredTo,
            'remarks' => $remarks,
            'recorded_by' => $_SESSION['user_id'] ?? 1
        ];

        $newId = $this->pcbModel->createServiceLog($data);

        if ($newId) {
            AuditLog::log(
                'PCB_SERVICE_RECORDED',
                'Patients',
                "Recorded PhilHealth PCB service ({$serviceCategory}: {$serviceType}) for {$patient['patient_no']} ({$patient['first_name']} {$patient['last_name']})"
            );
            $_SESSION['success_message'] = "PhilHealth PCB service encounter recorded successfully.";
        } else {
            $_SESSION['form_errors'] = ['Failed to record PhilHealth PCB service entry. Please try again.'];
        }

        $this->redirect("/patients/{$patientId}#tab-pcb");
    }

    /**
     * Delete a diagnostic or PCB service encounter log.
     * 
     * @param int $id
     */
    public function deleteLog($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $log = $this->pcbModel->findLogById($id);
        if (!$log) {
            $_SESSION['form_errors'] = ['Service log entry not found.'];
            $this->redirect('/patients');
            return;
        }

        $patientId = (int)$log['patient_id'];
        $deleted = $this->pcbModel->deleteServiceLog($id, $patientId);

        if ($deleted) {
            AuditLog::log(
                'PCB_SERVICE_DELETED',
                'Patients',
                "Deleted PhilHealth PCB service entry #{$id} ({$log['service_category']}: {$log['service_type']}) for Patient ID #{$patientId}"
            );
            $_SESSION['success_message'] = "PhilHealth PCB service encounter removed.";
        } else {
            $_SESSION['form_errors'] = ['Failed to remove service log entry.'];
        }

        $this->redirect("/patients/{$patientId}#tab-pcb");
    }
}
<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\VitalSigns;
use App\Models\Patient;
use App\Models\AuditLog;
use App\Validators\VitalSignsValidator;

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

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
               || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
               || (!empty($_POST['format']) && $_POST['format'] === 'json');

        $patientId = (int)($_POST['patient_id'] ?? 0);
        
        $patient = $this->patientModel->findById($patientId);
        if (!$patient) {
            if ($isAjax) {
                $this->json(['success' => false, 'message' => 'Patient record not found.'], 404);
                return;
            }
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $redirectTo = !empty($_POST['redirect_to']) ? $_POST['redirect_to'] : "/patients/{$patientId}#tab-vitals";

        $validator = new VitalSignsValidator();
        $errors = $validator->validate($_POST);

        if (!empty($errors)) {
            $errorMsg = implode(' ', $errors);
            if ($isAjax) {
                $this->json(['success' => false, 'message' => $errorMsg, 'errors' => $errors], 422);
                return;
            }
            $_SESSION['error_message'] = $errorMsg;
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
            if ($isAjax) {
                $vital = $this->vitalsModel->findById($newId);
                $this->json([
                    'success' => true,
                    'message' => 'Vital signs recorded successfully!',
                    'vital' => $vital
                ]);
                return;
            }
            $_SESSION['success_message'] = 'Vital signs recorded successfully!';
        } else {
            if ($isAjax) {
                $this->json(['success' => false, 'message' => 'Failed to save vital signs. Please try again.'], 500);
                return;
            }
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



        $vital = $this->vitalsModel->findById($id);
        if (!$vital) {
            $_SESSION['error_message'] = 'Vital signs record not found.';
            $this->redirect('/patients');
            return;
        }

        $patientId = (int)$vital['patient_id'];
        $currentUserId = (int)($_SESSION['user_id'] ?? 0);
        $canDelete = is_admin();

        if (!$canDelete) {
            $_SESSION['error_message'] = 'Unauthorized: Only administrators can delete vital signs records.';
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

        $this->vitalsModel->delete($id, $currentUserId, 'Deleted by clinician');
        AuditLog::log('VITAL_SIGNS_DELETED', 'Patients', "Deleted vital signs ID #{$id} for patient ID #{$patientId}");

        $_SESSION['success_message'] = 'Vital signs record deleted successfully.';
        $this->redirect("/patients/{$patientId}#tab-vitals");
    }

    /**
     * Update an existing vital signs record.
     * 
     * @param int $id
     */
    public function update($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
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
        if (!in_array($userRole, ['admin', 'super_admin', 'staff'], true)) {
            $_SESSION['error_message'] = 'Unauthorized: You do not have permission to update vital signs records.';
            $this->redirect("/patients/{$patientId}#tab-vitals");
            return;
        }

        $validator = new VitalSignsValidator();
        $errors = $validator->validate($_POST);

        if (!empty($errors)) {
            $_SESSION['error_message'] = implode(' ', $errors);
            $this->redirect("/patients/{$patientId}#tab-vitals");
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

        $data = $_POST;
        $data['bmi'] = $bmi;

        $updated = $this->vitalsModel->update($id, $data);
        if ($updated) {
            AuditLog::log('VITAL_SIGNS_UPDATED', 'Patients', "Updated vital signs ID #{$id} for patient ID #{$patientId}");
            $_SESSION['success_message'] = 'Vital signs record updated successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to update vital signs. Please try again.';
        }

        $this->redirect("/patients/{$patientId}#tab-vitals");
    }
}

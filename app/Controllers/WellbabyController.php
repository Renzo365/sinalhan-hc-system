<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Patient;
use App\Models\WellbabyRecord;
use App\Models\ChildGrowthLog;
use App\Models\Immunization;
use App\Models\AuditLog;

class WellbabyController extends Controller {
    protected $patientModel;
    protected $wbModel;
    protected $growthModel;
    protected $immModel;

    public function __construct() {
        $this->patientModel = new Patient();
        $this->wbModel = new WellbabyRecord();
        $this->growthModel = new ChildGrowthLog();
        $this->immModel = new Immunization();
    }

    /**
     * Create or update the infant's birth circumstances and newborn screening record.
     * 
     * @param int $patientId
     */
    public function storeBirthRecord($patientId) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $patient = $this->patientModel->findById($patientId);
        if (!$patient) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $birthWeight = !empty($_POST['birth_weight_kg']) ? (float)$_POST['birth_weight_kg'] : 0;
        $birthLength = !empty($_POST['birth_length_cm']) ? (float)$_POST['birth_length_cm'] : 0;

        if ($birthWeight <= 0 || $birthLength <= 0) {
            $_SESSION['error_message'] = 'Valid birth weight (kg) and birth length (cm) are required.';
            $this->redirect("/patients/{$patientId}#tab-wellbaby");
            return;
        }

        $userId = $_SESSION['user_id'] ?? 1;

        $data = [
            'patient_id' => $patientId,
            'mother_patient_id' => !empty($_POST['mother_patient_id']) ? (int)$_POST['mother_patient_id'] : null,
            'birth_time' => !empty($_POST['birth_time']) ? $_POST['birth_time'] : null,
            'birth_weight_kg' => $birthWeight,
            'birth_length_cm' => $birthLength,
            'place_of_delivery' => $_POST['place_of_delivery'] ?? 'Lying-in',
            'delivery_type' => $_POST['delivery_type'] ?? 'Normal Spontaneous Delivery (NSD)',
            'attended_by' => trim($_POST['attended_by'] ?? 'Midwife'),
            'newborn_screening_done' => !empty($_POST['newborn_screening_done']) ? 1 : 0,
            'newborn_screening_date' => !empty($_POST['newborn_screening_date']) ? $_POST['newborn_screening_date'] : null,
            'newborn_screening_result' => trim($_POST['newborn_screening_result'] ?? ''),
            'mother_cpab_tt' => trim($_POST['mother_cpab_tt'] ?? ''),
            'feeding_method' => $_POST['feeding_method'] ?? 'LAM / Exclusive Breastfeeding',
            'created_by' => $userId
        ];

        $savedId = $this->wbModel->createRecord($data);

        if ($savedId) {
            AuditLog::log('WELLBABY_RECORD_SAVED', 'Pediatric Care', "Saved Well Baby birth circumstances for Child {$patient['patient_no']} ({$patient['first_name']} {$patient['last_name']})");
            $_SESSION['success_message'] = 'Well Baby infant birth circumstances and screening certificate saved successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to save Well Baby record. Please try again.';
        }

        $this->redirect("/patients/{$patientId}#tab-wellbaby");
    }

    /**
     * Record a monthly pediatric growth anthropometrics checkup.
     * 
     * @param int $wellbabyId
     */
    public function storeGrowthLog($wellbabyId) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $wbRecord = $this->wbModel->findById($wellbabyId);
        if (!$wbRecord) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $logDate = $_POST['log_date'] ?? date('Y-m-d');
        $weight = !empty($_POST['weight_kg']) ? (float)$_POST['weight_kg'] : 0;
        $height = !empty($_POST['height_cm']) ? (float)$_POST['height_cm'] : 0;
        $ageMonths = isset($_POST['age_months']) ? (float)$_POST['age_months'] : 0;

        if ($weight <= 0 || $height <= 0) {
            $_SESSION['error_message'] = 'Valid weight (kg) and height (cm) are required.';
            $this->redirect("/patients/{$wbRecord['patient_id']}#tab-wellbaby");
            return;
        }

        $userId = $_SESSION['user_id'] ?? 1;

        $data = [
            'wellbaby_id' => $wellbabyId,
            'log_date' => $logDate,
            'age_months' => $ageMonths,
            'weight_kg' => $weight,
            'height_cm' => $height,
            'head_circumference_cm' => !empty($_POST['head_circumference_cm']) ? (float)$_POST['head_circumference_cm'] : null,
            'chest_circumference_cm' => !empty($_POST['chest_circumference_cm']) ? (float)$_POST['chest_circumference_cm'] : null,
            'temperature' => !empty($_POST['temperature']) ? (float)$_POST['temperature'] : null,
            'feeding_method' => $_POST['feeding_method'] ?? 'LAM / Exclusive Breastfeeding',
            'vaccines_administered' => trim($_POST['vaccines_administered'] ?? ''),
            'vitamin_a_dose' => !empty($_POST['vitamin_a_dose']) ? 1 : 0,
            'deworming_dose' => !empty($_POST['deworming_dose']) ? 1 : 0,
            'tcb_notes' => trim($_POST['tcb_notes'] ?? ''),
            'recorded_by' => $userId
        ];

        $logId = $this->growthModel->createLog($data);

        if ($logId) {
            AuditLog::log('CHILD_GROWTH_LOGGED', 'Pediatric Care', "Logged Growth Checkup #{$logId} for Well Baby #{$wellbabyId} (Age: {$ageMonths} mos, Wt: {$weight}kg)");
            $_SESSION['success_message'] = "Pediatric growth visit recorded successfully for {$ageMonths} months old!";
        } else {
            $_SESSION['error_message'] = 'Failed to record growth visit.';
        }

        $this->redirect("/patients/{$wbRecord['patient_id']}#tab-wellbaby");
    }

    /**
     * Record a single immunization dose.
     * 
     * @param int $patientId
     */
    public function recordImmunization($patientId) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $patient = $this->patientModel->findById($patientId);
        if (!$patient) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $vaccineName = trim($_POST['vaccine_name'] ?? '');
        $doseNumber = !empty($_POST['dose_number']) ? (int)$_POST['dose_number'] : 1;
        $adminDate = !empty($_POST['administered_date']) ? $_POST['administered_date'] : date('Y-m-d');
        $remarks = trim($_POST['remarks'] ?? '');
        $userId = $_SESSION['user_id'] ?? 1;

        if (empty($vaccineName)) {
            $_SESSION['error_message'] = 'Vaccine name is required.';
            $this->redirect("/patients/{$patientId}#tab-wellbaby");
            return;
        }

        $savedId = $this->immModel->recordDose([
            'patient_id' => $patientId,
            'vaccine_name' => $vaccineName,
            'dose_number' => $doseNumber,
            'administered_date' => $adminDate,
            'remarks' => $remarks,
            'administered_by' => $userId
        ]);

        if ($savedId) {
            AuditLog::log('IMMUNIZATION_RECORDED', 'Immunization', "Administered {$vaccineName} Dose #{$doseNumber} to Patient {$patient['patient_no']} on {$adminDate}");
            $_SESSION['success_message'] = "Recorded {$vaccineName} (Dose {$doseNumber}) on {$adminDate}!";
        } else {
            $_SESSION['error_message'] = 'Failed to record immunization.';
        }

        $this->redirect("/patients/{$patientId}#tab-wellbaby");
    }

    /**
     * Batch save EPI immunization schedule dates from the interactive grid.
     * 
     * @param int $patientId
     */
    public function batchSaveEPI($patientId) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $patient = $this->patientModel->findById($patientId);
        if (!$patient) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $epiDates = $_POST['epi'] ?? [];
        $userId = $_SESSION['user_id'] ?? 1;
        $savedCount = 0;

        if (is_array($epiDates)) {
            foreach ($epiDates as $vacKey => $dateVal) {
                $dateVal = trim($dateVal);
                if (!empty($dateVal)) {
                    // vacKey format: "VACCINE_NAME__DOSE" e.g. "BCG__1", "PENTAVALENT__2"
                    $parts = explode('__', $vacKey);
                    $vacName = str_replace('_', ' ', $parts[0]);
                    $doseNo = isset($parts[1]) ? (int)$parts[1] : 1;

                    $this->immModel->recordDose([
                        'patient_id' => $patientId,
                        'vaccine_name' => $vacName,
                        'dose_number' => $doseNo,
                        'administered_date' => $dateVal,
                        'remarks' => 'EPI Routine Infant Program',
                        'administered_by' => $userId
                    ]);
                    $savedCount++;
                }
            }
        }

        if ($savedCount > 0) {
            AuditLog::log('EPI_SCHEDULE_SAVED', 'Immunization', "Updated {$savedCount} EPI vaccine doses for Child {$patient['patient_no']}");
            $_SESSION['success_message'] = "Saved {$savedCount} EPI immunization dose dates successfully!";
        } else {
            $_SESSION['info_message'] = 'No new EPI vaccination dates entered.';
        }

        $this->redirect("/patients/{$patientId}#tab-wellbaby");
    }

    /**
     * Delete a growth log entry.
     * 
     * @param int $id
     */
    public function deleteGrowthLog($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $patientId = $_POST['patient_id'] ?? null;
        $this->growthModel->deleteLog($id);
        $_SESSION['success_message'] = 'Growth log entry removed.';

        $this->redirect($patientId ? "/patients/{$patientId}#tab-wellbaby" : "/patients");
    }
}

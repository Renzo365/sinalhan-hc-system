<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Patient;
use App\Models\PatientMedicalHistory;
use App\Models\AuditLog;

class PatientMedicalHistoryController extends Controller {
    protected $patientModel;
    protected $pmhModel;

    public function __construct() {
        $this->patientModel = new Patient();
        $this->pmhModel = new PatientMedicalHistory();
    }

    /**
     * Save or update Annex A1 IHP Medical History for a patient.
     * 
     * @param int $patientId
     */
    public function save($patientId) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $patient = $this->patientModel->findById($patientId);
        if (!$patient) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        // Process Checklists and Structured Text - build associative map [Condition => Detail]
        $rawPastMedical = $_POST['past_medical_history'] ?? [];
        if (!is_array($rawPastMedical)) {
            $rawPastMedical = !empty($_POST['past_medical_history']) ? [$_POST['past_medical_history']] : [];
        }

        $pastMedical = [];
        foreach ($rawPastMedical as $k => $item) {
            $item = trim((string)$item);
            if ($item === '' || $item === '[]') continue;
            if (is_string($k) && !is_numeric($k)) {
                $pastMedical[$k] = $item;
            } else {
                $pastMedical[$item] = '';
            }
        }

        // If specific detail fields are filled, update the condition in the map (no duplicate numeric items)
        if (!empty($_POST['allergy_specifics'])) {
            $pastMedical['Allergy'] = trim($_POST['allergy_specifics']);
        }
        if (!empty($_POST['cancer_organ'])) {
            $pastMedical['Cancer'] = trim($_POST['cancer_organ']);
        }
        if (!empty($_POST['hypertension_highest_bp'])) {
            $pastMedical['Hypertension'] = 'Highest BP: ' . trim($_POST['hypertension_highest_bp']);
        }
        if (!empty($_POST['ptb_details'])) {
            $pastMedical['Pulmonary Tuberculosis (PTB)'] = trim($_POST['ptb_details']);
            unset($pastMedical['PTB'], $pastMedical['Tuberculosis']);
        }

        // Process Surgical History
        $surgical = [];
        if (!empty($_POST['operation_1_name'])) {
            $surgical[] = [
                'operation' => trim($_POST['operation_1_name']),
                'date' => $_POST['operation_1_date'] ?? '',
                'hospital' => trim($_POST['operation_1_hospital'] ?? '')
            ];
        }
        if (!empty($_POST['operation_2_name'])) {
            $surgical[] = [
                'operation' => trim($_POST['operation_2_name']),
                'date' => $_POST['operation_2_date'] ?? '',
                'hospital' => trim($_POST['operation_2_hospital'] ?? '')
            ];
        }

        // Process Family Heredity - build associative map [Condition => Detail]
        $rawFamily = $_POST['family_history'] ?? [];
        if (!is_array($rawFamily)) {
            $rawFamily = !empty($_POST['family_history']) ? [$_POST['family_history']] : [];
        }
        $family = [];
        foreach ($rawFamily as $k => $v) {
            $cond = (is_string($k) && !is_numeric($k)) ? trim($k) : trim((string)$v);
            if ($cond === '' || $cond === '[]' || $cond === 'Yes') continue;
            $detail = (is_string($k) && !is_numeric($k) && !empty($v) && $v !== 'Yes' && $v !== $k) ? trim($v) : '';
            $family[$cond] = $detail;
        }
        if (!empty($_POST['family_other'])) {
            $family['Other'] = trim($_POST['family_other']);
        }

        $data = [
            'past_medical_history' => $pastMedical,
            'surgical_history' => $surgical,
            'family_history' => $family,
            'smoking_status' => $_POST['smoking_status'] ?? 'Never',
            'smoking_pack_years' => !empty($_POST['smoking_pack_years']) ? (float)$_POST['smoking_pack_years'] : null,
            'alcohol_status' => $_POST['alcohol_status'] ?? 'Never',
            'alcohol_bottles_per_day' => !empty($_POST['alcohol_bottles_per_day']) ? (float)$_POST['alcohol_bottles_per_day'] : null,
            'illicit_drugs' => !empty($_POST['illicit_drugs']) ? 1 : 0,
            'menarche_age' => !empty($_POST['menarche_age']) ? (int)$_POST['menarche_age'] : null,
            'sexual_onset_age' => !empty($_POST['sexual_onset_age']) ? (int)$_POST['sexual_onset_age'] : null,
            'lmp' => !empty($_POST['lmp']) ? $_POST['lmp'] : null,
            'period_duration_days' => !empty($_POST['period_duration_days']) ? (int)$_POST['period_duration_days'] : null,
            'cycle_interval_days' => !empty($_POST['cycle_interval_days']) ? (int)$_POST['cycle_interval_days'] : null,
            'pads_per_day' => !empty($_POST['pads_per_day']) ? (int)$_POST['pads_per_day'] : null,
            'is_menopausal' => !empty($_POST['is_menopausal']) ? 1 : 0,
            'menopause_age' => !empty($_POST['menopause_age']) ? (int)$_POST['menopause_age'] : null,
            'birth_control_method' => !empty($_POST['birth_control_method']) ? trim($_POST['birth_control_method']) : null
        ];

        $userId = $_SESSION['user_id'] ?? 1;
        $saved = $this->pmhModel->saveHistory($patientId, $data, $userId);

        if ($saved) {
            AuditLog::log('PATIENT_IHP_UPDATED', 'Patients', "Updated PhilHealth IHP Medical History for {$patient['patient_no']} ({$patient['first_name']} {$patient['last_name']})");
            $_SESSION['success_message'] = 'Individual Health Profile (IHP) Medical History saved successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to save Medical History. Please check input values.';
        }

        $this->redirect("/patients/{$patientId}#ihp-history");
    }
}

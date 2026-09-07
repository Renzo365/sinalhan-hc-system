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

        // Apply specific detail fields for PMH
        if (!empty($_POST['allergy_specifics'])) {
            $pastMedical['Allergy'] = trim($_POST['allergy_specifics']);
        }
        if (!empty($_POST['cancer_organ'])) {
            $pastMedical['Cancer'] = trim($_POST['cancer_organ']);
        }
        if (!empty($_POST['hepatitis_type'])) {
            $pastMedical['Hepatitis'] = trim($_POST['hepatitis_type']);
        }
        if (!empty($_POST['hypertension_highest_bp'])) {
            $pastMedical['Hypertension'] = 'Highest BP: ' . trim($_POST['hypertension_highest_bp']);
        }
        if (!empty($_POST['tuberculosis_organ'])) {
            $pastMedical['Tuberculosis'] = trim($_POST['tuberculosis_organ']);
        }
        if (!empty($_POST['ptb_details'])) {
            $pastMedical['Pulmonary Tuberculosis (PTB)'] = trim($_POST['ptb_details']);
            unset($pastMedical['PTB']);
        }
        if (!empty($_POST['pmh_other_specify'])) {
            $pastMedical['Others'] = trim($_POST['pmh_other_specify']);
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
        if (!empty($_POST['fam_allergy_specifics'])) {
            $family['Allergy'] = trim($_POST['fam_allergy_specifics']);
        }
        if (!empty($_POST['fam_cancer_organ'])) {
            $family['Cancer'] = trim($_POST['fam_cancer_organ']);
        }
        if (!empty($_POST['fam_hepatitis_type'])) {
            $family['Hepatitis'] = trim($_POST['fam_hepatitis_type']);
        }
        if (!empty($_POST['fam_hypertension_highest_bp'])) {
            $family['Hypertension'] = 'Highest BP: ' . trim($_POST['fam_hypertension_highest_bp']);
        }
        if (!empty($_POST['fam_tuberculosis_organ'])) {
            $family['Tuberculosis'] = trim($_POST['fam_tuberculosis_organ']);
        }
        if (!empty($_POST['fam_ptb_details'])) {
            $family['PTB Category'] = trim($_POST['fam_ptb_details']);
        }
        if (!empty($_POST['family_other'])) {
            $family['Others'] = trim($_POST['family_other']);
        }

        // Process Physical Examination Checklist
        $physicalExam = [];
        $systems = ['skin', 'heent', 'chest_lungs', 'heart', 'abdomen', 'extremities'];
        foreach ($systems as $sys) {
            if (!empty($_POST['pe_' . $sys]) && is_array($_POST['pe_' . $sys])) {
                $physicalExam[$sys] = array_values(array_filter(array_map('trim', $_POST['pe_' . $sys])));
            } elseif (!empty($_POST['pe_' . $sys])) {
                $physicalExam[$sys] = [trim((string)$_POST['pe_' . $sys])];
            } else {
                $physicalExam[$sys] = [];
            }
        }
        if (!empty($_POST['pe_remarks'])) {
            $physicalExam['remarks'] = trim($_POST['pe_remarks']);
        }

        // Process Lifetime / Annex A1 Immunizations
        $immunizations = [];
        $immCategories = ['children', 'young_women', 'pregnant', 'elderly'];
        foreach ($immCategories as $cat) {
            if (!empty($_POST['imm_' . $cat]) && is_array($_POST['imm_' . $cat])) {
                $immunizations[$cat] = array_values(array_filter(array_map('trim', $_POST['imm_' . $cat])));
            } else {
                $immunizations[$cat] = [];
            }
        }
        if (!empty($_POST['imm_others_specify'])) {
            $immunizations['others'] = trim($_POST['imm_others_specify']);
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
            'birth_control_method' => !empty($_POST['birth_control_method']) ? trim($_POST['birth_control_method']) : null,
            'baseline_bp_systolic' => !empty($_POST['baseline_bp_systolic']) ? (int)$_POST['baseline_bp_systolic'] : null,
            'baseline_bp_diastolic' => !empty($_POST['baseline_bp_diastolic']) ? (int)$_POST['baseline_bp_diastolic'] : null,
            'baseline_heart_rate' => !empty($_POST['baseline_heart_rate']) ? (int)$_POST['baseline_heart_rate'] : null,
            'baseline_respiratory_rate' => !empty($_POST['baseline_respiratory_rate']) ? (int)$_POST['baseline_respiratory_rate'] : null,
            'baseline_height' => !empty($_POST['baseline_height']) ? (float)$_POST['baseline_height'] : null,
            'baseline_weight' => !empty($_POST['baseline_weight']) ? (float)$_POST['baseline_weight'] : null,
            'baseline_waist_circumference' => !empty($_POST['baseline_waist_circumference']) ? (float)$_POST['baseline_waist_circumference'] : null,
            'gravida' => isset($_POST['gravida']) && $_POST['gravida'] !== '' ? (int)$_POST['gravida'] : null,
            'para' => isset($_POST['para']) && $_POST['para'] !== '' ? (int)$_POST['para'] : null,
            'delivery_type' => !empty($_POST['delivery_type']) ? trim($_POST['delivery_type']) : null,
            'term_births' => isset($_POST['term_births']) && $_POST['term_births'] !== '' ? (int)$_POST['term_births'] : null,
            'preterm_births' => isset($_POST['preterm_births']) && $_POST['preterm_births'] !== '' ? (int)$_POST['preterm_births'] : null,
            'abortions' => isset($_POST['abortions']) && $_POST['abortions'] !== '' ? (int)$_POST['abortions'] : null,
            'living_children' => isset($_POST['living_children']) && $_POST['living_children'] !== '' ? (int)$_POST['living_children'] : null,
            'pre_eclampsia' => !empty($_POST['pre_eclampsia']) ? 1 : 0,
            'fp_counselling' => isset($_POST['fp_counselling']) ? (int)$_POST['fp_counselling'] : 1,
            'physical_examination' => $physicalExam,
            'external_immunizations' => $immunizations
        ];

        $userId = $_SESSION['user_id'] ?? 1;
        $saved = $this->pmhModel->saveHistory($patientId, $data, $userId);

        if ($saved) {
            AuditLog::log('PATIENT_IHP_UPDATED', 'Patients', "Updated PhilHealth IHP Medical History for {$patient['patient_no']} ({$patient['first_name']} {$patient['last_name']})");
            $_SESSION['success_message'] = 'Individual Health Profile (IHP) Medical History saved successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to save Medical History. Please check input values.';
        }

        $this->redirect("/patients/{$patientId}#tab-ihp");
    }
}

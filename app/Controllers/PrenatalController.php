<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Patient;
use App\Models\PrenatalRecord;
use App\Models\PrenatalVisit;
use App\Models\PastObstetricHistory;
use App\Models\AuditLog;

class PrenatalController extends Controller {
    protected $patientModel;
    protected $prenatalModel;
    protected $visitModel;
    protected $pohModel;

    public function __construct() {
        $this->patientModel = new Patient();
        $this->prenatalModel = new PrenatalRecord();
        $this->visitModel = new PrenatalVisit();
        $this->pohModel = new PastObstetricHistory();
    }

    /**
     * Start a new active maternal pregnancy episode for a patient.
     * 
     * @param int $patientId
     */
    public function storeEpisode($patientId) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $patient = $this->patientModel->findById($patientId);
        if (!$patient) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        if (strtolower($patient['sex']) !== 'female') {
            $_SESSION['error_message'] = 'Prenatal care episodes can only be recorded for female patients.';
            $this->redirect("/patients/{$patientId}");
            return;
        }

        $lmp = $_POST['lmp'] ?? '';
        if (empty($lmp) || !strtotime($lmp)) {
            $_SESSION['error_message'] = 'Valid Last Menstrual Period (LMP) is required.';
            $this->redirect("/patients/{$patientId}#tab-prenatal");
            return;
        }

        if (strtotime($lmp) > time()) {
            $_SESSION['error_message'] = 'LMP cannot be a future date.';
            $this->redirect("/patients/{$patientId}#tab-prenatal");
            return;
        }

        $userId = $_SESSION['user_id'] ?? 1;

        $data = [
            'patient_id' => $patientId,
            'husband_name' => trim($_POST['husband_name'] ?? ''),
            'gravida' => !empty($_POST['gravida']) ? (int)$_POST['gravida'] : 1,
            'para' => !empty($_POST['para']) ? (int)$_POST['para'] : 0,
            'term_births' => !empty($_POST['term_births']) ? (int)$_POST['term_births'] : 0,
            'preterm_births' => !empty($_POST['preterm_births']) ? (int)$_POST['preterm_births'] : 0,
            'abortions' => !empty($_POST['abortions']) ? (int)$_POST['abortions'] : 0,
            'living_children' => !empty($_POST['living_children']) ? (int)$_POST['living_children'] : 0,
            'lmp' => $lmp,
            'edc' => $this->prenatalModel->calculateEDC($lmp),
            'is_active' => 1,
            'pre_eclampsia' => !empty($_POST['pre_eclampsia']) ? 1 : 0,
            'fp_counselling' => isset($_POST['fp_counselling']) ? (int)$_POST['fp_counselling'] : 1,
            'notes' => trim($_POST['notes'] ?? ''),
            'created_by' => $userId
        ];

        $episodeId = $this->prenatalModel->createEpisode($data);

        if ($episodeId) {
            AuditLog::log('PRENATAL_EPISODE_CREATED', 'Maternal Care', "Started Pregnancy Episode #{$episodeId} for {$patient['patient_no']} (EDC: {$data['edc']})");
            $_SESSION['success_message'] = 'Maternal pregnancy episode started successfully! EDC calculated: ' . date('M d, Y', strtotime($data['edc']));
        } else {
            $_SESSION['error_message'] = 'Failed to create pregnancy episode. Please try again.';
        }

        $this->redirect("/patients/{$patientId}#tab-prenatal");
    }

    /**
     * Update an ongoing pregnancy episode.
     * 
     * @param int $id
     */
    public function updateEpisode($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $episode = $this->prenatalModel->findById($id);
        if (!$episode) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $lmp = $_POST['lmp'] ?? $episode['lmp'];
        $data = [
            'husband_name' => trim($_POST['husband_name'] ?? $episode['husband_name']),
            'gravida' => isset($_POST['gravida']) ? (int)$_POST['gravida'] : $episode['gravida'],
            'para' => isset($_POST['para']) ? (int)$_POST['para'] : $episode['para'],
            'term_births' => isset($_POST['term_births']) ? (int)$_POST['term_births'] : $episode['term_births'],
            'preterm_births' => isset($_POST['preterm_births']) ? (int)$_POST['preterm_births'] : $episode['preterm_births'],
            'abortions' => isset($_POST['abortions']) ? (int)$_POST['abortions'] : $episode['abortions'],
            'living_children' => isset($_POST['living_children']) ? (int)$_POST['living_children'] : $episode['living_children'],
            'lmp' => $lmp,
            'edc' => $this->prenatalModel->calculateEDC($lmp),
            'is_active' => $episode['is_active'],
            'pre_eclampsia' => !empty($_POST['pre_eclampsia']) ? 1 : 0,
            'fp_counselling' => isset($_POST['fp_counselling']) ? (int)$_POST['fp_counselling'] : $episode['fp_counselling'],
            'delivery_date' => $episode['delivery_date'],
            'delivery_outcome' => $episode['delivery_outcome'],
            'notes' => trim($_POST['notes'] ?? $episode['notes'])
        ];

        $updated = $this->prenatalModel->updateEpisode($id, $data);

        if ($updated) {
            AuditLog::log('PRENATAL_EPISODE_UPDATED', 'Maternal Care', "Updated Pregnancy Episode #{$id}");
            $_SESSION['success_message'] = 'Pregnancy episode details updated successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to update pregnancy episode.';
        }

        $this->redirect("/patients/{$episode['patient_id']}#tab-prenatal");
    }

    /**
     * Record a serial follow-up prenatal checkup visit.
     * 
     * @param int $prenatalId
     */
    public function storeVisit($prenatalId) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $episode = $this->prenatalModel->findById($prenatalId);
        if (!$episode) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $visitDate = $_POST['visit_date'] ?? date('Y-m-d');
        $userId = $_SESSION['user_id'] ?? 1;

        // Calculate dynamic AOG in weeks if not explicitly provided
        $aogWeeks = !empty($_POST['aog_weeks']) ? (float)$_POST['aog_weeks'] : ($episode['calculated_aog']['weeks'] ?? 0);

        $data = [
            'prenatal_id' => $prenatalId,
            'visit_date' => $visitDate,
            'chief_complaint' => trim($_POST['chief_complaint'] ?? ''),
            'aog_weeks' => $aogWeeks,
            'bp_systolic' => !empty($_POST['bp_systolic']) ? (int)$_POST['bp_systolic'] : null,
            'bp_diastolic' => !empty($_POST['bp_diastolic']) ? (int)$_POST['bp_diastolic'] : null,
            'weight_kg' => !empty($_POST['weight_kg']) ? (float)$_POST['weight_kg'] : null,
            'height_cm' => !empty($_POST['height_cm']) ? (float)$_POST['height_cm'] : null,
            'fetal_heart_tone' => !empty($_POST['fetal_heart_tone']) ? (int)$_POST['fetal_heart_tone'] : null,
            'fundal_height_cm' => !empty($_POST['fundal_height_cm']) ? (float)$_POST['fundal_height_cm'] : null,
            'fetal_presentation' => $_POST['fetal_presentation'] ?? 'Cephalic',
            'tcb' => trim($_POST['tcb'] ?? ''),
            'remarks' => trim($_POST['remarks'] ?? ''),
            'attended_by' => $userId
        ];

        $visitId = $this->visitModel->createVisit($data);

        if ($visitId) {
            AuditLog::log('PRENATAL_VISIT_LOGGED', 'Maternal Care', "Logged Prenatal Follow-up Visit #{$visitId} for Episode #{$prenatalId} (FHT: {$data['fetal_heart_tone']} bpm)");
            $_SESSION['success_message'] = 'Prenatal follow-up checkup visit recorded successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to record prenatal visit.';
        }

        $this->redirect("/patients/{$episode['patient_id']}#tab-prenatal");
    }

    /**
     * Add a past delivery record (Gravida 1, 2, 3...) to past obstetric history matrix.
     * 
     * @param int $patientId
     */
    public function storePastObstetric($patientId) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $patient = $this->patientModel->findById($patientId);
        if (!$patient) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $data = [
            'patient_id' => $patientId,
            'gravida_no' => !empty($_POST['gravida_no']) ? (int)$_POST['gravida_no'] : 1,
            'delivery_type' => $_POST['delivery_type'] ?? 'NSD',
            'infant_sex' => $_POST['infant_sex'] ?? 'Unknown',
            'place_of_delivery' => trim($_POST['place_of_delivery'] ?? ''),
            'year_delivered' => !empty($_POST['year_delivered']) ? (int)$_POST['year_delivered'] : null,
            'birth_date' => !empty($_POST['birth_date']) ? $_POST['birth_date'] : null,
            'attended_by' => trim($_POST['attended_by'] ?? ''),
            'status' => $_POST['status'] ?? 'Alive',
            'tt_status' => trim($_POST['tt_status'] ?? '')
        ];

        $recordId = $this->pohModel->createRecord($data);

        if ($recordId) {
            AuditLog::log('PAST_OBSTETRIC_RECORDED', 'Maternal Care', "Logged Past Delivery G{$data['gravida_no']} for Patient {$patient['patient_no']}");
            $_SESSION['success_message'] = "Past delivery record for Gravida {$data['gravida_no']} saved successfully!";
        } else {
            $_SESSION['error_message'] = 'Failed to save past delivery record.';
        }

        $this->redirect("/patients/{$patientId}#tab-prenatal");
    }

    /**
     * Delete a past delivery record row.
     * 
     * @param int $id
     */
    public function deletePastObstetric($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $patientId = $_POST['patient_id'] ?? null;
        $this->pohModel->deleteRecord($id);
        $_SESSION['success_message'] = 'Past obstetric record removed.';

        $this->redirect($patientId ? "/patients/{$patientId}#tab-prenatal" : "/patients");
    }

    /**
     * Conclude a pregnancy episode upon delivery or outcome.
     * 
     * @param int $id
     */
    public function concludeEpisode($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $episode = $this->prenatalModel->findById($id);
        if (!$episode) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $deliveryDate = $_POST['delivery_date'] ?? date('Y-m-d');
        $deliveryOutcome = $_POST['delivery_outcome'] ?? 'Live Birth';
        $notes = trim($_POST['notes'] ?? '');

        $concluded = $this->prenatalModel->concludeEpisode($id, [
            'delivery_date' => $deliveryDate,
            'delivery_outcome' => $deliveryOutcome,
            'notes' => $notes
        ]);

        if ($concluded) {
            AuditLog::log('PRENATAL_EPISODE_CONCLUDED', 'Maternal Care', "Concluded Pregnancy Episode #{$id} with outcome: {$deliveryOutcome}");
            $_SESSION['success_message'] = "Pregnancy episode concluded successfully ({$deliveryOutcome}).";
        } else {
            $_SESSION['error_message'] = 'Failed to conclude pregnancy episode.';
        }

        $this->redirect("/patients/{$episode['patient_id']}#tab-prenatal");
    }
}

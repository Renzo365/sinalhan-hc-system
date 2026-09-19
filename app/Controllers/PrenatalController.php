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
     * Display the dedicated Maternal Pregnancy Episode Registration Page.
     */
    public function createEpisode() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $preselectedId = !empty($_GET['patient_id']) ? (int)$_GET['patient_id'] : null;
        $preselectedPatient = null;
        $preselectedIhp = null;
        $hasActiveEpisode = false;

        if ($preselectedId) {
            $patient = $this->patientModel->findById($preselectedId);
            if ($patient && strtolower($patient['sex'] ?? '') === 'female') {
                $preselectedPatient = $patient;
                $hasActiveEpisode = $this->prenatalModel->hasActiveEpisode($preselectedId);
                $pmhModel = new \App\Models\PatientMedicalHistory();
                $preselectedIhp = $pmhModel->findByPatientId($preselectedId);
            }
        }

        $this->view('maternal/register', [
            'preselectedPatient' => $preselectedPatient,
            'preselectedIhp' => $preselectedIhp,
            'hasActiveEpisode' => $hasActiveEpisode
        ]);
    }

    /**
     * Display the dedicated Maternal Pregnancy Episode Edit Page.
     * 
     * @param int $id
     */
    public function editEpisode($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $episode = $this->prenatalModel->findById($id);
        if (!$episode) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $patient = $this->patientModel->findById($episode['patient_id']);
        if (!$patient) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $this->view('maternal/edit_episode', [
            'episode' => $episode,
            'patient' => $patient
        ]);
    }

    /**
     * Start a new active maternal pregnancy episode for a patient.
     * 
     * @param int|null $patientId
     */
    public function storeEpisode($patientId = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($patientId) && !empty($_POST['patient_id'])) {
            $patientId = (int)$_POST['patient_id'];
        }

        if (empty($patientId)) {
            $_SESSION['error_message'] = 'Please select a valid female patient to enroll in Maternal Care.';
            $this->redirect('/maternal/register');
            return;
        }

        $patient = $this->patientModel->findById($patientId);
        if (!$patient) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        if (strtolower($patient['sex']) !== 'female') {
            $_SESSION['error_message'] = 'Prenatal care episodes can only be recorded for female patients.';
            $this->redirect("/maternal/register?patient_id={$patientId}");
            return;
        }

        if ($this->prenatalModel->hasActiveEpisode($patientId)) {
            $_SESSION['error_message'] = 'This patient already has an active pregnancy episode. Conclude it before starting another episode.';
            $this->redirect("/maternal/{$patientId}");
            return;
        }

        $lmp = $_POST['lmp'] ?? '';
        if (empty($lmp) || !strtotime($lmp)) {
            $_SESSION['error_message'] = 'Valid Last Menstrual Period (LMP) is required.';
            $this->redirect("/maternal/register?patient_id={$patientId}");
            return;
        }

        if (strtotime($lmp) > time()) {
            $_SESSION['error_message'] = 'LMP cannot be a future date.';
            $this->redirect("/maternal/register?patient_id={$patientId}");
            return;
        }

        $userId = $_SESSION['user_id'] ?? 1;

        $data = [
            'patient_id' => $patientId,
            'husband_name' => trim($_POST['husband_name'] ?? ''),
            'gravida' => max(1, (int)($_POST['gravida'] ?? 1)),
            'para' => max(0, (int)($_POST['para'] ?? 0)),
            'term_births' => max(0, (int)($_POST['term_births'] ?? 0)),
            'preterm_births' => max(0, (int)($_POST['preterm_births'] ?? 0)),
            'abortions' => max(0, (int)($_POST['abortions'] ?? 0)),
            'living_children' => max(0, (int)($_POST['living_children'] ?? 0)),
            'lmp' => $lmp,
            'edc' => $this->prenatalModel->calculateEDC($lmp),
            'is_active' => 1,
            'notes' => trim($_POST['notes'] ?? ''),
            'created_by' => $userId
        ];

        $episodeId = $this->prenatalModel->createEpisode($data);

        if ($episodeId) {
            $this->syncObstetricHistoryToIhp($patientId, $data, $userId);
            AuditLog::log('PRENATAL_EPISODE_CREATED', 'Maternal Care', "Started Pregnancy Episode #{$episodeId} for {$patient['patient_no']} (EDC: {$data['edc']})");
            $_SESSION['success_message'] = 'Maternal pregnancy episode started successfully! EDC calculated: ' . date('M d, Y', strtotime($data['edc']));
        } else {
            $_SESSION['error_message'] = 'Failed to create pregnancy episode. Please try again.';
            $this->redirect("/maternal/register?patient_id={$patientId}");
            return;
        }

        $this->redirect("/maternal/{$patientId}");
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
        $lmpDate = \DateTime::createFromFormat('Y-m-d', $lmp);
        if (!$lmpDate || $lmpDate->format('Y-m-d') !== $lmp || $lmp > date('Y-m-d')) {
            $_SESSION['error_message'] = 'Last Menstrual Period must be a valid date that is not in the future.';
            $this->redirect("/maternal/episode/{$id}/edit");
            return;
        }
        $counts = ['gravida', 'para', 'term_births', 'preterm_births', 'abortions', 'living_children'];
        foreach ($counts as $count) {
            if (isset($_POST[$count]) && (int)$_POST[$count] < 0) {
                $_SESSION['error_message'] = 'Pregnancy and delivery counts cannot be negative.';
                $this->redirect("/maternal/episode/{$id}/edit");
                return;
            }
        }
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
            'delivery_date' => $episode['delivery_date'],
            'delivery_outcome' => $episode['delivery_outcome'],
            'notes' => trim($_POST['notes'] ?? $episode['notes'])
        ];

        $updated = $this->prenatalModel->updateEpisode($id, $data);

        if ($updated) {
            $currentUserId = (int)($_SESSION['user_id'] ?? 1);
            $this->syncObstetricHistoryToIhp($episode['patient_id'], $data, $currentUserId);
            AuditLog::log('PRENATAL_EPISODE_UPDATED', 'Maternal Care', "Updated Pregnancy Episode #{$id}");
            $_SESSION['success_message'] = 'Pregnancy episode details updated successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to update pregnancy episode.';
            $this->redirect("/maternal/episode/{$id}/edit");
            return;
        }

        $this->redirect("/maternal/{$episode['patient_id']}");
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
        $visitDateObject = \DateTime::createFromFormat('Y-m-d', $visitDate);
        if (!$visitDateObject || $visitDateObject->format('Y-m-d') !== $visitDate || $visitDate > date('Y-m-d')) {
            $_SESSION['error_message'] = 'Prenatal visit date must be a valid date that is not in the future.';
            $this->redirect("/maternal/{$episode['patient_id']}");
            return;
        }

        // Calculate dynamic AOG in weeks if not explicitly provided
        $aogWeeks = !empty($_POST['aog_weeks']) ? (float)$_POST['aog_weeks'] : ($episode['calculated_aog']['weeks'] ?? 0);
        if ($aogWeeks < 0 || $aogWeeks > 45) {
            $_SESSION['error_message'] = 'Age of gestation must be between 0 and 45 weeks.';
            $this->redirect("/maternal/{$episode['patient_id']}");
            return;
        }

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

        $this->redirect("/maternal/{$episode['patient_id']}");
    }

    /**
     * Update an existing follow-up prenatal visit (e.g. Midwife adding clinical remarks, FHT, fundal height).
     * 
     * @param int $id
     */
    public function updateVisit($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $visit = $this->visitModel->findById($id);
        if (!$visit) {
            $_SESSION['error_message'] = 'Prenatal visit record not found.';
            $this->redirect('/maternal');
            return;
        }

        $episode = $this->prenatalModel->findById($visit['prenatal_id']);
        if (!$episode) {
            $_SESSION['error_message'] = 'Associated maternal episode not found.';
            $this->redirect('/maternal');
            return;
        }

        $data = [];
        if (isset($_POST['visit_date'])) {
            $data['visit_date'] = $_POST['visit_date'];
        }
        if (isset($_POST['aog_weeks'])) {
            $data['aog_weeks'] = (float)$_POST['aog_weeks'];
        }
        if (isset($_POST['bp_systolic'])) {
            $data['bp_systolic'] = !empty($_POST['bp_systolic']) ? (int)$_POST['bp_systolic'] : null;
        }
        if (isset($_POST['bp_diastolic'])) {
            $data['bp_diastolic'] = !empty($_POST['bp_diastolic']) ? (int)$_POST['bp_diastolic'] : null;
        }
        if (isset($_POST['weight_kg'])) {
            $data['weight_kg'] = !empty($_POST['weight_kg']) ? (float)$_POST['weight_kg'] : null;
        }
        if (isset($_POST['height_cm'])) {
            $data['height_cm'] = !empty($_POST['height_cm']) ? (float)$_POST['height_cm'] : null;
        }
        if (isset($_POST['fetal_heart_tone'])) {
            $data['fetal_heart_tone'] = !empty($_POST['fetal_heart_tone']) ? (int)$_POST['fetal_heart_tone'] : null;
        }
        if (isset($_POST['fundal_height_cm'])) {
            $data['fundal_height_cm'] = !empty($_POST['fundal_height_cm']) ? (float)$_POST['fundal_height_cm'] : null;
        }
        if (isset($_POST['fetal_presentation'])) {
            $data['fetal_presentation'] = $_POST['fetal_presentation'];
        }
        if (isset($_POST['tcb'])) {
            $data['tcb'] = trim($_POST['tcb']);
        }
        if (isset($_POST['chief_complaint'])) {
            $data['chief_complaint'] = trim($_POST['chief_complaint']);
        }
        if (isset($_POST['remarks'])) {
            $data['remarks'] = trim($_POST['remarks']);
        }

        $updated = $this->visitModel->updateVisit($id, $data);
        if ($updated) {
            AuditLog::log('PRENATAL_VISIT_UPDATED', 'Maternal Care', "Updated clinical assessment for Prenatal Visit #{$id} in Episode #{$visit['prenatal_id']}");
            $_SESSION['success_message'] = 'Prenatal visit clinical details updated successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to update prenatal visit.';
        }

        $this->redirect("/maternal/{$episode['patient_id']}");
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

        $this->redirect("/maternal/{$patientId}");
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

        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !hash_equals(csrf_token(), $token)) {
            $_SESSION['error_message'] = 'Security validation failed (invalid token). Please try again.';
            $this->redirect('/patients');
            return;
        }

        $record = $this->pohModel->findById($id);
        if (!$record) {
            $_SESSION['error_message'] = 'Past obstetric record not found.';
            $this->redirect('/patients');
            return;
        }

        $patientId = (int)$record['patient_id'];
        if (($_SESSION['user_role'] ?? 'staff') !== 'admin') {
            $_SESSION['error_message'] = 'Only an administrator may remove past obstetric history.';
            $this->redirect("/maternal/{$patientId}");
            return;
        }

        $this->pohModel->deleteRecord($id);
        AuditLog::log('PAST_OBSTETRIC_DELETED', 'Maternal Care', "Deleted past obstetric record #{$id} for patient ID #{$patientId}");
        $_SESSION['success_message'] = 'Past obstetric record removed.';
        $this->redirect("/maternal/{$patientId}");
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
        $deliveryDateObject = \DateTime::createFromFormat('Y-m-d', $deliveryDate);
        $allowedOutcomes = ['Live Birth', 'Stillbirth', 'Miscarriage', 'Ectopic', 'Other'];
        if (!$deliveryDateObject || $deliveryDateObject->format('Y-m-d') !== $deliveryDate || $deliveryDate > date('Y-m-d')) {
            $_SESSION['error_message'] = 'Pregnancy outcome date must be a valid date that is not in the future.';
            $this->redirect("/maternal/{$episode['patient_id']}");
            return;
        }
        if (!in_array($deliveryOutcome, $allowedOutcomes, true)) {
            $_SESSION['error_message'] = 'Invalid pregnancy outcome selected.';
            $this->redirect("/maternal/{$episode['patient_id']}");
            return;
        }

        $userId = (int)($_SESSION['user_id'] ?? 1);
        $patientId = (int)$episode['patient_id'];

        $concluded = $this->prenatalModel->concludeEpisode($id, [
            'delivery_date' => $deliveryDate,
            'delivery_outcome' => $deliveryOutcome,
            'notes' => $notes
        ]);

        if ($concluded) {
            $isTerm = (($_POST['delivery_classification'] ?? 'Term') === 'Term');
            $deliveryType = trim($_POST['delivery_type'] ?? 'NSD');
            $livingChildrenCount = max(0, (int)($_POST['living_children'] ?? 1));

            // 1. Automatically update Master Obstetric History (GTPAL) in IHP
            $pmhModel = new \App\Models\PatientMedicalHistory();
            $pmhModel->updateObstetricOutcome($patientId, [
                'delivery_outcome' => $deliveryOutcome,
                'is_term' => $isTerm,
                'delivery_type' => $deliveryType,
                'living_children' => $livingChildrenCount
            ], $userId);

            // 2. Automatically log into Past Deliveries Matrix if outcome is a delivery
            if (in_array($deliveryOutcome, ['Live Birth', 'Stillbirth'], true)) {
                $pohData = [
                    'patient_id' => $patientId,
                    'gravida_no' => (int)($episode['gravida'] ?? 1),
                    'delivery_type' => in_array($deliveryType, ['NSD', 'CS', 'Abortion', 'Other'], true) ? $deliveryType : 'NSD',
                    'infant_sex' => in_array($_POST['infant_sex'] ?? '', ['Male', 'Female', 'Unknown'], true) ? $_POST['infant_sex'] : 'Unknown',
                    'place_of_delivery' => trim($_POST['place_of_delivery'] ?? 'Health Center / Lying-in'),
                    'year_delivered' => (int)date('Y', strtotime($deliveryDate)),
                    'attended_by' => trim($_POST['attended_by'] ?? $_SESSION['user_name'] ?? 'Midwife'),
                    'status' => ($deliveryOutcome === 'Live Birth') ? 'Alive' : 'Not Alive',
                    'birth_date' => $deliveryDate,
                    'tt_status' => trim($_POST['tt_status'] ?? '')
                ];
                $this->pohModel->createRecord($pohData);
            }

            AuditLog::log('PRENATAL_EPISODE_CONCLUDED', 'Maternal Care', "Concluded Pregnancy Episode #{$id} with outcome: {$deliveryOutcome}. IHP & Past Deliveries updated.");
            $_SESSION['success_message'] = "Pregnancy episode concluded successfully ({$deliveryOutcome}). Patient's IHP Obstetric Score and Deliveries Matrix have been updated!";
        } else {
            $_SESSION['error_message'] = 'Failed to conclude pregnancy episode.';
        }

        $this->redirect("/maternal/{$episode['patient_id']}");
    }

    /**
     * Cancel an active pregnancy episode (e.g. started in error).
     * Only allowed if no visits are recorded.
     * 
     * @param int $id
     */
    public function cancelEpisode($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !hash_equals(csrf_token(), $token)) {
            $_SESSION['error_message'] = 'Security validation failed (invalid token). Please try again.';
            $this->redirect('/patients');
            return;
        }

        $episode = $this->prenatalModel->findById($id);
        if (!$episode) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $patientId = (int)$episode['patient_id'];

        // Verify that no checkup visits have been logged
        $visitCount = $this->visitModel->countByPrenatalId($id);
        if ($visitCount > 0) {
            $_SESSION['error_message'] = "Cannot cancel this pregnancy episode because it already has {$visitCount} logged prenatal checkup visit(s). Remove visits first or conclude the episode.";
            $this->redirect("/maternal/{$patientId}");
            return;
        }

        $userId = (int)($_SESSION['user_id'] ?? 1);
        $reason = trim($_POST['cancel_reason'] ?? 'Started in error / Cancelled by staff');

        $deleted = $this->prenatalModel->deleteEpisode($id, $userId, $reason);

        if ($deleted) {
            // Decrement Gravida in IHP to revert erroneous enrollment
            (new \App\Models\PatientMedicalHistory())->decrementGravida($patientId, $userId);

            AuditLog::log('PRENATAL_EPISODE_CANCELLED', 'Maternal Care', "Cancelled Pregnancy Episode #{$id} for Patient #{$patientId} (Reason: {$reason})");
            $_SESSION['success_message'] = 'Pregnancy episode cancelled and removed successfully. Obstetric Gravida count in IHP reverted.';
        } else {
            $_SESSION['error_message'] = 'Failed to cancel pregnancy episode.';
        }

        $this->redirect("/maternal/{$patientId}");
    }

    /**
     * Delete a prenatal visit record.
     * 
     * @param int $id
     */
    public function deleteVisit($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Validate CSRF token
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !hash_equals(csrf_token(), $token)) {
            AuditLog::log('SECURITY_VIOLATION', 'Maternal Care', "CSRF mismatch while attempting to delete prenatal visit #{$id}");
            $_SESSION['error_message'] = 'Security validation failed (invalid token). Please try again.';
            $this->redirect('/patients');
            return;
        }

        $visit = $this->visitModel->findById($id);
        if (!$visit) {
            $_SESSION['error_message'] = 'Prenatal visit record not found.';
            $this->redirect('/patients');
            return;
        }

        $episode = $this->prenatalModel->findById($visit['prenatal_id']);
        $patientId = $episode ? (int)$episode['patient_id'] : (int)($_POST['patient_id'] ?? 0);

        $currentUserId = (int)($_SESSION['user_id'] ?? 0);
        $userRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'staff';
        $canDelete = ($userRole === 'admin' || $currentUserId === (int)$visit['attended_by']);

        if (!$canDelete) {
            $_SESSION['error_message'] = 'Unauthorized: You do not have permission to delete this prenatal visit.';
            $this->redirect("/maternal/{$patientId}");
            return;
        }

        $this->visitModel->deleteVisit($id);
        AuditLog::log('PRENATAL_VISIT_DELETED', 'Maternal Care', "Deleted prenatal visit ID #{$id} dated {$visit['visit_date']} for patient ID #{$patientId}");

        $_SESSION['success_message'] = 'Prenatal checkup visit record removed successfully.';
        $this->redirect("/maternal/{$patientId}");
    }

    /**
     * Synchronize obstetric history (G, P, T, P, A, L) to baseline IHP medical history.
     */
    protected function syncObstetricHistoryToIhp($patientId, array $data, $userId = 1) {
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT id FROM patient_medical_histories WHERE patient_id = :patient_id LIMIT 1");
            $stmt->execute(['patient_id' => $patientId]);
            $exists = $stmt->fetchColumn();

            if ($exists) {
                $up = $db->prepare("UPDATE patient_medical_histories SET 
                    gravida = :gravida,
                    para = :para,
                    term_births = :term_births,
                    preterm_births = :preterm_births,
                    abortions = :abortions,
                    living_children = :living_children,
                    lmp = :lmp,
                    updated_by = :updated_by,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE patient_id = :patient_id");
                $up->execute([
                    'gravida' => $data['gravida'] ?? null,
                    'para' => $data['para'] ?? null,
                    'term_births' => $data['term_births'] ?? null,
                    'preterm_births' => $data['preterm_births'] ?? null,
                    'abortions' => $data['abortions'] ?? null,
                    'living_children' => $data['living_children'] ?? null,
                    'lmp' => !empty($data['lmp']) ? $data['lmp'] : null,
                    'updated_by' => $userId,
                    'patient_id' => $patientId
                ]);
            } else {
                $ins = $db->prepare("INSERT INTO patient_medical_histories (
                    patient_id, gravida, para, term_births, preterm_births, abortions, living_children, lmp, updated_by
                ) VALUES (
                    :patient_id, :gravida, :para, :term_births, :preterm_births, :abortions, :living_children, :lmp, :updated_by
                )");
                $ins->execute([
                    'patient_id' => $patientId,
                    'gravida' => $data['gravida'] ?? null,
                    'para' => $data['para'] ?? null,
                    'term_births' => $data['term_births'] ?? null,
                    'preterm_births' => $data['preterm_births'] ?? null,
                    'abortions' => $data['abortions'] ?? null,
                    'living_children' => $data['living_children'] ?? null,
                    'lmp' => !empty($data['lmp']) ? $data['lmp'] : null,
                    'updated_by' => $userId
                ]);
            }
        } catch (\Exception $e) {
            error_log("Failed to sync obstetric history to IHP: " . $e->getMessage());
        }
    }
}

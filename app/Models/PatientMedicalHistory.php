<?php

namespace App\Models;

use App\Core\Model;
use App\Services\MedicalDictionaryService;
use PDO;

class PatientMedicalHistory extends Model {
    /**
     * Get the medical history record for a patient.
     * Hydrates past conditions, family history, surgeries, and external immunizations relationally.
     * 
     * @param int $patientId
     * @return array|false
     */
    public function findByPatientId($patientId) {
        $sql = "SELECT pmh.*, 
                       CONCAT(u.first_name, ' ', u.last_name) AS updater_name
                FROM patient_medical_histories pmh
                LEFT JOIN users u ON pmh.updated_by = u.id
                WHERE pmh.patient_id = :patient_id AND pmh.deleted_at IS NULL
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['patient_id' => $patientId]);
        $row = $stmt->fetch();

        if (!$row) {
            return false;
        }

        // Decode physical_examination findings
        if (!empty($row['physical_examination'])) {
            $decoded = json_decode($row['physical_examination'], true);
            $row['physical_examination'] = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
        } else {
            $row['physical_examination'] = [];
        }
        $row['physical_examination'] = self::normalizePhysicalExamination($row['physical_examination']);

        // Fetch relational conditions (Past Medical & Family Hereditary)
        $cStmt = $this->db->prepare("
            SELECT condition_type, condition_name, remarks 
            FROM patient_conditions 
            WHERE patient_id = :patient_id AND deleted_at IS NULL
            ORDER BY id ASC
        ");
        $cStmt->execute(['patient_id' => $patientId]);
        $conditionRows = $cStmt->fetchAll(PDO::FETCH_ASSOC);

        $pmh = [];
        $fam = [];
        foreach ($conditionRows as $c) {
            $name = MedicalDictionaryService::normalizeCondition($c['condition_name']);
            if ($name === '') continue;
            $remarks = trim((string)($c['remarks'] ?? ''));
            if ($c['condition_type'] === 'Family') {
                $fam[$name] = $remarks;
            } else {
                $pmh[$name] = $remarks;
            }
        }
        $row['past_medical_history'] = $pmh;
        $row['family_history'] = $fam;

        // Fetch relational surgeries
        $sStmt = $this->db->prepare("
            SELECT procedure_name AS operation, surgery_date AS date, hospital 
            FROM patient_surgeries 
            WHERE patient_id = :patient_id AND deleted_at IS NULL
            ORDER BY id ASC
        ");
        $sStmt->execute(['patient_id' => $patientId]);
        $row['surgical_history'] = $sStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Fetch relational external immunizations
        $iStmt = $this->db->prepare("
            SELECT category, vaccine_name, remarks 
            FROM patient_external_immunizations 
            WHERE patient_id = :patient_id AND deleted_at IS NULL
            ORDER BY id ASC
        ");
        $iStmt->execute(['patient_id' => $patientId]);
        $immRows = $iStmt->fetchAll(PDO::FETCH_ASSOC);

        $imm = [
            'children' => [],
            'young_women' => [],
            'pregnant' => [],
            'elderly' => [],
            'others' => ''
        ];
        foreach ($immRows as $ir) {
            $cat = $ir['category'];
            if ($cat === 'others') {
                $imm['others'] = $ir['vaccine_name'] . (!empty($ir['remarks']) ? ' - ' . $ir['remarks'] : '');
            } elseif (isset($imm[$cat])) {
                $imm[$cat][] = $ir['vaccine_name'];
            }
        }
        $row['external_immunizations'] = $imm;

        return $row;
    }

    /**
     * Normalize and deduplicate past medical history array.
     * Prevents duplicate illnesses caused by mixed numeric and associative keys.
     *
     * @param mixed $data
     * @return array
     */
    public static function normalizePastMedicalHistory($data) {
        if (empty($data)) {
            return [];
        }
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            $data = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [$data];
        }
        if (!is_array($data)) {
            return [];
        }

        $normalized = [];
        foreach ($data as $k => $v) {
            $rawCondition = (is_string($k) && !is_numeric($k)) ? trim($k) : trim((string)$v);
            $detail = (is_string($k) && !is_numeric($k) && is_string($v)) ? trim($v) : '';

            $condition = MedicalDictionaryService::normalizeCondition($rawCondition);
            if ($condition === '') {
                continue;
            }

            // Deduplicate: If condition already exists, preserve non-empty detail
            if (isset($normalized[$condition])) {
                if (empty($normalized[$condition]) && !empty($detail)) {
                    $normalized[$condition] = $detail;
                }
            } else {
                $normalized[$condition] = $detail;
            }
        }

        return $normalized;
    }

    /**
     * Normalize and deduplicate family history array.
     *
     * @param mixed $data
     * @return array
     */
    public static function normalizeFamilyHistory($data) {
        if (empty($data)) {
            return [];
        }
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            $data = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [$data];
        }
        if (!is_array($data)) {
            return [];
        }

        $normalized = [];
        foreach ($data as $k => $v) {
            $rawCondition = (is_string($k) && !is_numeric($k)) ? trim($k) : trim((string)$v);
            $detail = (is_string($k) && !is_numeric($k) && is_string($v) && $v !== 'Yes' && $v !== $k) ? trim($v) : '';

            $condition = MedicalDictionaryService::normalizeCondition($rawCondition);
            if ($condition === '') {
                continue;
            }

            if (isset($normalized[$condition])) {
                if (empty($normalized[$condition]) && !empty($detail)) {
                    $normalized[$condition] = $detail;
                }
            } else {
                $normalized[$condition] = $detail;
            }
        }

        return $normalized;
    }

    /**
     * Normalize physical examination array.
     * Supports both flat checklists and legacy nested structures (findings + other).
     *
     * @param mixed $data
     * @return array
     */
    public static function normalizePhysicalExamination($data) {
        if (empty($data)) {
            return [];
        }
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            $data = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
        }
        if (!is_array($data)) {
            return [];
        }

        $systems = ['skin', 'heent', 'chest_lungs', 'heart', 'abdomen', 'extremities'];
        $normalized = [];

        foreach ($systems as $sys) {
            $findings = [];
            if (!empty($data[$sys])) {
                if (is_array($data[$sys])) {
                    if (isset($data[$sys]['findings']) && is_array($data[$sys]['findings'])) {
                        foreach ($data[$sys]['findings'] as $f) {
                            if (is_string($f) && trim($f) !== '') {
                                $findings[] = trim($f);
                            }
                        }
                        if (!empty($data[$sys]['other']) && is_string($data[$sys]['other'])) {
                            $findings[] = trim($data[$sys]['other']);
                        }
                    } else {
                        foreach ($data[$sys] as $f) {
                            if (is_string($f) && trim($f) !== '') {
                                $findings[] = trim($f);
                            } elseif (is_array($f)) {
                                foreach ($f as $subF) {
                                    if (is_string($subF) && trim($subF) !== '') {
                                        $findings[] = trim($subF);
                                    }
                                }
                            }
                        }
                    }
                } elseif (is_string($data[$sys]) && trim($data[$sys]) !== '') {
                    $findings[] = trim($data[$sys]);
                }
            }
            $normalized[$sys] = array_values(array_unique($findings));
        }

        if (!empty($data['remarks']) && is_string($data['remarks'])) {
            $normalized['remarks'] = trim($data['remarks']);
        }

        return $normalized;
    }

    /**
     * Normalize external immunizations array.
     *
     * @param mixed $data
     * @return array
     */
    public static function normalizeImmunizations($data) {
        if (empty($data)) {
            return [];
        }
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            $data = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
        }
        if (!is_array($data)) {
            return [];
        }

        $categories = ['children', 'young_women', 'pregnant', 'elderly'];
        $normalized = [];

        foreach ($categories as $cat) {
            $items = [];
            if (!empty($data[$cat])) {
                if (is_array($data[$cat])) {
                    foreach ($data[$cat] as $item) {
                        if (is_string($item) && trim($item) !== '') {
                            $items[] = trim($item);
                        }
                    }
                } elseif (is_string($data[$cat]) && trim($data[$cat]) !== '') {
                    $items[] = trim($data[$cat]);
                }
            }
            $normalized[$cat] = array_values(array_unique($items));
        }

        if (!empty($data['others']) && is_string($data['others'])) {
            $normalized['others'] = trim($data['others']);
        } elseif (!empty($data['others_specify']) && is_string($data['others_specify'])) {
            $normalized['others'] = trim($data['others_specify']);
        }

        return $normalized;
    }

    /**
     * Create or update the medical history record for a patient.
     * Persists baseline & demographics in patient_medical_histories,
     * and conditions/surgeries/immunizations relationally.
     * 
     * @param int $patientId
     * @param array $data
     * @param int $userId
     * @return bool
     */
    public function saveHistory($patientId, $data, $userId) {
        $this->db->beginTransaction();
        try {
            // 1. Clean physical exam
            $cleanPe = self::normalizePhysicalExamination($data['physical_examination'] ?? []);
            $physicalExam = !empty($cleanPe) ? json_encode($cleanPe) : null;

            // 2. Upsert baseline demographics & vitals in patient_medical_histories
            $sql = "INSERT INTO patient_medical_histories (
                        patient_id,
                        smoking_status, smoking_pack_years, alcohol_status, alcohol_bottles_per_day,
                        illicit_drugs, menarche_age, sexual_onset_age, lmp, period_duration_days,
                        cycle_interval_days, pads_per_day, is_menopausal, menopause_age,
                        birth_control_method, baseline_bp_systolic, baseline_bp_diastolic,
                        baseline_heart_rate, baseline_respiratory_rate, baseline_height,
                        baseline_weight, baseline_waist_circumference, gravida, para, delivery_type,
                        term_births, preterm_births, abortions, living_children, pre_eclampsia,
                        fp_counselling, physical_examination, updated_by
                    ) VALUES (
                        :patient_id,
                        :smoking_status, :smoking_pack_years, :alcohol_status, :alcohol_bottles_per_day,
                        :illicit_drugs, :menarche_age, :sexual_onset_age, :lmp, :period_duration_days,
                        :cycle_interval_days, :pads_per_day, :is_menopausal, :menopause_age,
                        :birth_control_method, :baseline_bp_systolic, :baseline_bp_diastolic,
                        :baseline_heart_rate, :baseline_respiratory_rate, :baseline_height,
                        :baseline_weight, :baseline_waist_circumference, :gravida, :para, :delivery_type,
                        :term_births, :preterm_births, :abortions, :living_children, :pre_eclampsia,
                        :fp_counselling, :physical_examination, :updated_by
                    )
                    ON DUPLICATE KEY UPDATE
                        smoking_status = VALUES(smoking_status),
                        smoking_pack_years = VALUES(smoking_pack_years),
                        alcohol_status = VALUES(alcohol_status),
                        alcohol_bottles_per_day = VALUES(alcohol_bottles_per_day),
                        illicit_drugs = VALUES(illicit_drugs),
                        menarche_age = VALUES(menarche_age),
                        sexual_onset_age = VALUES(sexual_onset_age),
                        lmp = VALUES(lmp),
                        period_duration_days = VALUES(period_duration_days),
                        cycle_interval_days = VALUES(cycle_interval_days),
                        pads_per_day = VALUES(pads_per_day),
                        is_menopausal = VALUES(is_menopausal),
                        menopause_age = VALUES(menopause_age),
                        birth_control_method = VALUES(birth_control_method),
                        baseline_bp_systolic = VALUES(baseline_bp_systolic),
                        baseline_bp_diastolic = VALUES(baseline_bp_diastolic),
                        baseline_heart_rate = VALUES(baseline_heart_rate),
                        baseline_respiratory_rate = VALUES(baseline_respiratory_rate),
                        baseline_height = VALUES(baseline_height),
                        baseline_weight = VALUES(baseline_weight),
                        baseline_waist_circumference = VALUES(baseline_waist_circumference),
                        gravida = VALUES(gravida),
                        para = VALUES(para),
                        delivery_type = VALUES(delivery_type),
                        term_births = VALUES(term_births),
                        preterm_births = VALUES(preterm_births),
                        abortions = VALUES(abortions),
                        living_children = VALUES(living_children),
                        pre_eclampsia = VALUES(pre_eclampsia),
                        fp_counselling = VALUES(fp_counselling),
                        physical_examination = VALUES(physical_examination),
                        updated_by = VALUES(updated_by),
                        deleted_at = NULL,
                        deleted_by = NULL,
                        archive_reason = NULL,
                        updated_at = CURRENT_TIMESTAMP";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'patient_id' => $patientId,
                'smoking_status' => $data['smoking_status'] ?? 'Never',
                'smoking_pack_years' => !empty($data['smoking_pack_years']) ? (float)$data['smoking_pack_years'] : null,
                'alcohol_status' => $data['alcohol_status'] ?? 'Never',
                'alcohol_bottles_per_day' => !empty($data['alcohol_bottles_per_day']) ? (float)$data['alcohol_bottles_per_day'] : null,
                'illicit_drugs' => !empty($data['illicit_drugs']) ? 1 : 0,
                'menarche_age' => !empty($data['menarche_age']) ? (int)$data['menarche_age'] : null,
                'sexual_onset_age' => !empty($data['sexual_onset_age']) ? (int)$data['sexual_onset_age'] : null,
                'lmp' => !empty($data['lmp']) ? $data['lmp'] : null,
                'period_duration_days' => !empty($data['period_duration_days']) ? (int)$data['period_duration_days'] : null,
                'cycle_interval_days' => !empty($data['cycle_interval_days']) ? (int)$data['cycle_interval_days'] : null,
                'pads_per_day' => !empty($data['pads_per_day']) ? (int)$data['pads_per_day'] : null,
                'is_menopausal' => !empty($data['is_menopausal']) ? 1 : 0,
                'menopause_age' => !empty($data['menopause_age']) ? (int)$data['menopause_age'] : null,
                'birth_control_method' => !empty($data['birth_control_method']) ? trim($data['birth_control_method']) : null,
                'baseline_bp_systolic' => !empty($data['baseline_bp_systolic']) ? (int)$data['baseline_bp_systolic'] : null,
                'baseline_bp_diastolic' => !empty($data['baseline_bp_diastolic']) ? (int)$data['baseline_bp_diastolic'] : null,
                'baseline_heart_rate' => !empty($data['baseline_heart_rate']) ? (int)$data['baseline_heart_rate'] : null,
                'baseline_respiratory_rate' => !empty($data['baseline_respiratory_rate']) ? (int)$data['baseline_respiratory_rate'] : null,
                'baseline_height' => !empty($data['baseline_height']) ? (float)$data['baseline_height'] : null,
                'baseline_weight' => !empty($data['baseline_weight']) ? (float)$data['baseline_weight'] : null,
                'baseline_waist_circumference' => !empty($data['baseline_waist_circumference']) ? (float)$data['baseline_waist_circumference'] : null,
                'gravida' => isset($data['gravida']) && $data['gravida'] !== '' ? (int)$data['gravida'] : null,
                'para' => isset($data['para']) && $data['para'] !== '' ? (int)$data['para'] : null,
                'delivery_type' => !empty($data['delivery_type']) ? trim($data['delivery_type']) : null,
                'term_births' => isset($data['term_births']) && $data['term_births'] !== '' ? (int)$data['term_births'] : null,
                'preterm_births' => isset($data['preterm_births']) && $data['preterm_births'] !== '' ? (int)$data['preterm_births'] : null,
                'abortions' => isset($data['abortions']) && $data['abortions'] !== '' ? (int)$data['abortions'] : null,
                'living_children' => isset($data['living_children']) && $data['living_children'] !== '' ? (int)$data['living_children'] : null,
                'pre_eclampsia' => !empty($data['pre_eclampsia']) ? 1 : 0,
                'fp_counselling' => isset($data['fp_counselling']) ? (int)$data['fp_counselling'] : 1,
                'physical_examination' => $physicalExam,
                'updated_by' => $userId
            ]);

            // 3. Relational save for patient_conditions (Past and Family)
            $delCond = $this->db->prepare("
                UPDATE patient_conditions 
                SET deleted_at = CURRENT_TIMESTAMP, 
                    deleted_by = :user_id, 
                    archive_reason = 'Superseded by medical history update' 
                WHERE patient_id = :patient_id AND deleted_at IS NULL
            ");
            $delCond->execute(['patient_id' => $patientId, 'user_id' => $userId]);

            $insCond = $this->db->prepare("
                INSERT INTO patient_conditions (patient_id, condition_type, condition_name, remarks)
                VALUES (:patient_id, :condition_type, :condition_name, :remarks)
            ");

            $cleanPmh = self::normalizePastMedicalHistory($data['past_medical_history'] ?? []);
            foreach ($cleanPmh as $cond => $remarks) {
                $insCond->execute([
                    'patient_id' => $patientId,
                    'condition_type' => 'Past',
                    'condition_name' => $cond,
                    'remarks' => !empty($remarks) ? $remarks : null
                ]);
            }

            $cleanFam = self::normalizeFamilyHistory($data['family_history'] ?? []);
            foreach ($cleanFam as $cond => $remarks) {
                $insCond->execute([
                    'patient_id' => $patientId,
                    'condition_type' => 'Family',
                    'condition_name' => $cond,
                    'remarks' => !empty($remarks) ? $remarks : null
                ]);
            }

            // 4. Relational save for patient_surgeries
            $delSurg = $this->db->prepare("
                UPDATE patient_surgeries 
                SET deleted_at = CURRENT_TIMESTAMP, 
                    deleted_by = :user_id, 
                    archive_reason = 'Superseded by medical history update' 
                WHERE patient_id = :patient_id AND deleted_at IS NULL
            ");
            $delSurg->execute(['patient_id' => $patientId, 'user_id' => $userId]);

            $insSurg = $this->db->prepare("
                INSERT INTO patient_surgeries (patient_id, procedure_name, surgery_date, hospital)
                VALUES (:patient_id, :procedure_name, :surgery_date, :hospital)
            ");

            $surgeries = is_array($data['surgical_history'] ?? null) ? $data['surgical_history'] : [];
            foreach ($surgeries as $s) {
                if (is_array($s) && !empty($s['operation'])) {
                    $insSurg->execute([
                        'patient_id' => $patientId,
                        'procedure_name' => trim($s['operation']),
                        'surgery_date' => !empty($s['date']) ? trim($s['date']) : null,
                        'hospital' => !empty($s['hospital']) ? trim($s['hospital']) : null
                    ]);
                }
            }

            // 5. Relational save for patient_external_immunizations
            $delImm = $this->db->prepare("
                UPDATE patient_external_immunizations 
                SET deleted_at = CURRENT_TIMESTAMP, 
                    deleted_by = :user_id, 
                    archive_reason = 'Superseded by medical history update' 
                WHERE patient_id = :patient_id AND deleted_at IS NULL
            ");
            $delImm->execute(['patient_id' => $patientId, 'user_id' => $userId]);

            $insImm = $this->db->prepare("
                INSERT INTO patient_external_immunizations (patient_id, category, vaccine_name, remarks)
                VALUES (:patient_id, :category, :vaccine_name, :remarks)
            ");

            $cleanImm = self::normalizeImmunizations($data['external_immunizations'] ?? []);
            foreach (['children', 'young_women', 'pregnant', 'elderly'] as $cat) {
                if (!empty($cleanImm[$cat]) && is_array($cleanImm[$cat])) {
                    foreach ($cleanImm[$cat] as $vaccine) {
                        if (is_string($vaccine) && trim($vaccine) !== '') {
                            $insImm->execute([
                                'patient_id' => $patientId,
                                'category' => $cat,
                                'vaccine_name' => trim($vaccine),
                                'remarks' => null
                            ]);
                        }
                    }
                }
            }
            if (!empty($cleanImm['others'])) {
                $insImm->execute([
                    'patient_id' => $patientId,
                    'category' => 'others',
                    'vaccine_name' => trim($cleanImm['others']),
                    'remarks' => null
                ]);
            }

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log("Failed to save patient medical history: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Soft delete patient medical history and all associated sub-records.
     * 
     * @param int $patientId
     * @param int|null $userId
     * @param string|null $reason
     * @return bool
     */
    public function deleteHistory($patientId, $userId = null, $reason = null) {
        try {
            $this->db->beginTransaction();
            $params = [
                'patient_id' => (int)$patientId,
                'user_id' => $userId ? (int)$userId : null,
                'reason' => $reason ? trim($reason) : null
            ];

            $stmtPmh = $this->db->prepare("
                UPDATE patient_medical_histories 
                SET deleted_at = CURRENT_TIMESTAMP, deleted_by = :user_id, archive_reason = :reason 
                WHERE patient_id = :patient_id AND deleted_at IS NULL
            ");
            $stmtPmh->execute($params);

            $stmtCond = $this->db->prepare("
                UPDATE patient_conditions 
                SET deleted_at = CURRENT_TIMESTAMP, deleted_by = :user_id, archive_reason = :reason 
                WHERE patient_id = :patient_id AND deleted_at IS NULL
            ");
            $stmtCond->execute($params);

            $stmtSurg = $this->db->prepare("
                UPDATE patient_surgeries 
                SET deleted_at = CURRENT_TIMESTAMP, deleted_by = :user_id, archive_reason = :reason 
                WHERE patient_id = :patient_id AND deleted_at IS NULL
            ");
            $stmtSurg->execute($params);

            $stmtImm = $this->db->prepare("
                UPDATE patient_external_immunizations 
                SET deleted_at = CURRENT_TIMESTAMP, deleted_by = :user_id, archive_reason = :reason 
                WHERE patient_id = :patient_id AND deleted_at IS NULL
            ");
            $stmtImm->execute($params);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log("Failed to soft-delete patient medical history: " . $e->getMessage());
            return false;
        }
    }
}
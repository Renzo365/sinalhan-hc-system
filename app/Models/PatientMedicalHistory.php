<?php

namespace App\Models;

use App\Core\Model;

class PatientMedicalHistory extends Model {
    /**
     * Get the medical history record for a patient.
     * 
     * @param int $patientId
     * @return array|false
     */
    public function findByPatientId($patientId) {
        $sql = "SELECT pmh.*, 
                       CONCAT(u.first_name, ' ', u.last_name) AS updater_name
                FROM patient_medical_histories pmh
                LEFT JOIN users u ON pmh.updated_by = u.id
                WHERE pmh.patient_id = :patient_id
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['patient_id' => $patientId]);
        $row = $stmt->fetch();

        if ($row) {
            // Decode JSON fields if stored as JSON strings
            foreach (['past_medical_history', 'surgical_history', 'family_history', 'physical_examination', 'external_immunizations'] as $f) {
                if (!empty($row[$f])) {
                    $decoded = json_decode($row[$f], true);
                    $row[$f] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : $row[$f];
                } else {
                    $row[$f] = [];
                }
                if ($row[$f] === '[]' || $row[$f] === '{}' || $row[$f] === null) {
                    $row[$f] = [];
                }
            }

            $row['past_medical_history'] = self::normalizePastMedicalHistory($row['past_medical_history']);
            $row['family_history'] = self::normalizeFamilyHistory($row['family_history']);
            $row['physical_examination'] = self::normalizePhysicalExamination($row['physical_examination']);
            $row['external_immunizations'] = self::normalizeImmunizations($row['external_immunizations']);
        }

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
            $condition = (is_string($k) && !is_numeric($k)) ? trim($k) : trim((string)$v);
            $detail = (is_string($k) && !is_numeric($k) && is_string($v)) ? trim($v) : '';

            if ($condition === '' || $condition === '[]' || $condition === '{}') {
                continue;
            }

            // Standardize aliases to canonical names
            if (in_array($condition, ['PTB', 'Tuberculosis', 'Pulmonary Tuberculosis'], true)) {
                $condition = 'Pulmonary Tuberculosis (PTB)';
            } elseif ($condition === 'Allergies') {
                $condition = 'Allergy';
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
            $condition = (is_string($k) && !is_numeric($k)) ? trim($k) : trim((string)$v);
            $detail = (is_string($k) && !is_numeric($k) && is_string($v) && $v !== 'Yes' && $v !== $k) ? trim($v) : '';

            if ($condition === '' || $condition === '[]' || $condition === '{}' || $condition === 'Yes') {
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
                    // Check if nested: ['findings' => [...], 'other' => '...']
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
     * Create or update the medical history record for a patient (Upsert).
     * 
     * @param int $patientId
     * @param array $data
     * @param int $userId
     * @return bool
     */
    public function saveHistory($patientId, $data, $userId) {
        // Normalize and encode array checklists to JSON
        $cleanPmh = self::normalizePastMedicalHistory($data['past_medical_history'] ?? []);
        $cleanFam = self::normalizeFamilyHistory($data['family_history'] ?? []);
        $cleanPe = self::normalizePhysicalExamination($data['physical_examination'] ?? []);
        $cleanImm = self::normalizeImmunizations($data['external_immunizations'] ?? []);

        $pastMedical = !empty($cleanPmh) ? json_encode($cleanPmh) : null;
        $surgical = is_array($data['surgical_history'] ?? null) ? json_encode($data['surgical_history']) : ($data['surgical_history'] ?? null);
        $family = !empty($cleanFam) ? json_encode($cleanFam) : null;
        $physicalExam = !empty($cleanPe) ? json_encode($cleanPe) : null;
        $immunizations = !empty($cleanImm) ? json_encode($cleanImm) : null;

        $sql = "INSERT INTO patient_medical_histories (
                    patient_id, past_medical_history, surgical_history, family_history,
                    smoking_status, smoking_pack_years, alcohol_status, alcohol_bottles_per_day,
                    illicit_drugs, menarche_age, sexual_onset_age, lmp, period_duration_days,
                    cycle_interval_days, pads_per_day, is_menopausal, menopause_age,
                    birth_control_method, baseline_bp_systolic, baseline_bp_diastolic,
                    baseline_heart_rate, baseline_respiratory_rate, baseline_height,
                    baseline_weight, baseline_waist_circumference, gravida, para, delivery_type,
                    term_births, preterm_births, abortions, living_children, pre_eclampsia,
                    fp_counselling, physical_examination, external_immunizations, updated_by
                ) VALUES (
                    :patient_id, :past_medical_history, :surgical_history, :family_history,
                    :smoking_status, :smoking_pack_years, :alcohol_status, :alcohol_bottles_per_day,
                    :illicit_drugs, :menarche_age, :sexual_onset_age, :lmp, :period_duration_days,
                    :cycle_interval_days, :pads_per_day, :is_menopausal, :menopause_age,
                    :birth_control_method, :baseline_bp_systolic, :baseline_bp_diastolic,
                    :baseline_heart_rate, :baseline_respiratory_rate, :baseline_height,
                    :baseline_weight, :baseline_waist_circumference, :gravida, :para, :delivery_type,
                    :term_births, :preterm_births, :abortions, :living_children, :pre_eclampsia,
                    :fp_counselling, :physical_examination, :external_immunizations, :updated_by
                )
                ON DUPLICATE KEY UPDATE
                    past_medical_history = VALUES(past_medical_history),
                    surgical_history = VALUES(surgical_history),
                    family_history = VALUES(family_history),
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
                    external_immunizations = VALUES(external_immunizations),
                    updated_by = VALUES(updated_by),
                    updated_at = CURRENT_TIMESTAMP";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'patient_id' => $patientId,
            'past_medical_history' => $pastMedical,
            'surgical_history' => $surgical,
            'family_history' => $family,
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
            'external_immunizations' => $immunizations,
            'updated_by' => $userId
        ]);
    }
}

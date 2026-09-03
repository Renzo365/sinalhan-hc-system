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
            $row['past_medical_history'] = !empty($row['past_medical_history']) ? json_decode($row['past_medical_history'], true) ?: $row['past_medical_history'] : [];
            $row['surgical_history'] = !empty($row['surgical_history']) ? json_decode($row['surgical_history'], true) ?: $row['surgical_history'] : [];
            $row['family_history'] = !empty($row['family_history']) ? json_decode($row['family_history'], true) ?: $row['family_history'] : [];
        }

        return $row;
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
        // Encode array checklists to JSON if array passed
        $pastMedical = is_array($data['past_medical_history'] ?? null) ? json_encode($data['past_medical_history']) : ($data['past_medical_history'] ?? null);
        $surgical = is_array($data['surgical_history'] ?? null) ? json_encode($data['surgical_history']) : ($data['surgical_history'] ?? null);
        $family = is_array($data['family_history'] ?? null) ? json_encode($data['family_history']) : ($data['family_history'] ?? null);

        $sql = "INSERT INTO patient_medical_histories (
                    patient_id, past_medical_history, surgical_history, family_history,
                    smoking_status, smoking_pack_years, alcohol_status, alcohol_bottles_per_day,
                    illicit_drugs, menarche_age, sexual_onset_age, lmp, period_duration_days,
                    cycle_interval_days, pads_per_day, is_menopausal, menopause_age,
                    birth_control_method, updated_by
                ) VALUES (
                    :patient_id, :past_medical_history, :surgical_history, :family_history,
                    :smoking_status, :smoking_pack_years, :alcohol_status, :alcohol_bottles_per_day,
                    :illicit_drugs, :menarche_age, :sexual_onset_age, :lmp, :period_duration_days,
                    :cycle_interval_days, :pads_per_day, :is_menopausal, :menopause_age,
                    :birth_control_method, :updated_by
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
            'updated_by' => $userId
        ]);
    }
}

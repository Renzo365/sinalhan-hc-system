<?php

namespace App\Models;

use App\Core\Model;

class WellbabyRecord extends Model {
    /**
     * Get the Well Baby record for a child patient.
     * 
     * @param int $patientId
     * @return array|false
     */
    public function findByPatientId($patientId) {
        $sql = "SELECT wb.*, 
                       p.first_name, p.last_name, p.dob, p.sex, p.family_no, p.address,
                       m.first_name AS mother_first_name, m.last_name AS mother_last_name, m.patient_no AS mother_patient_no,
                       TIMESTAMPDIFF(YEAR, m.dob, CURRENT_DATE()) AS mother_age,
                       CONCAT(u.first_name, ' ', u.last_name) AS creator_name
                FROM wellbaby_records wb
                INNER JOIN patients p ON wb.patient_id = p.id
                LEFT JOIN patients m ON wb.mother_patient_id = m.id
                LEFT JOIN users u ON wb.created_by = u.id
                WHERE wb.patient_id = :patient_id
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['patient_id' => $patientId]);
        return $stmt->fetch();
    }

    /**
     * Find a Well Baby record by its primary key ID.
     * 
     * @param int $id
     * @return array|false
     */
    public function findById($id) {
        $sql = "SELECT wb.*, 
                       p.first_name, p.last_name, p.dob, p.sex, p.family_no, p.address,
                       CONCAT(u.first_name, ' ', u.last_name) AS creator_name
                FROM wellbaby_records wb
                INNER JOIN patients p ON wb.patient_id = p.id
                LEFT JOIN users u ON wb.created_by = u.id
                WHERE wb.id = :id
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Create or initialize a Well Baby record for a child.
     * 
     * @param array $data
     * @return int|false Last insert ID
     */
    public function createRecord($data) {
        $sql = "INSERT INTO wellbaby_records (
                    patient_id, mother_patient_id, birth_time, birth_weight_kg,
                    birth_length_cm, place_of_delivery, delivery_type,
                    attended_by, newborn_screening_done, newborn_screening_date,
                    newborn_screening_result, mother_cpab_tt, feeding_method,
                    created_by
                ) VALUES (
                    :patient_id, :mother_patient_id, :birth_time, :birth_weight_kg,
                    :birth_length_cm, :place_of_delivery, :delivery_type,
                    :attended_by, :newborn_screening_done, :newborn_screening_date,
                    :newborn_screening_result, :mother_cpab_tt, :feeding_method,
                    :created_by
                )
                ON DUPLICATE KEY UPDATE
                    mother_patient_id = VALUES(mother_patient_id),
                    birth_time = VALUES(birth_time),
                    birth_weight_kg = VALUES(birth_weight_kg),
                    birth_length_cm = VALUES(birth_length_cm),
                    place_of_delivery = VALUES(place_of_delivery),
                    delivery_type = VALUES(delivery_type),
                    attended_by = VALUES(attended_by),
                    newborn_screening_done = VALUES(newborn_screening_done),
                    newborn_screening_date = VALUES(newborn_screening_date),
                    newborn_screening_result = VALUES(newborn_screening_result),
                    mother_cpab_tt = VALUES(mother_cpab_tt),
                    feeding_method = VALUES(feeding_method),
                    updated_at = CURRENT_TIMESTAMP";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            'patient_id' => $data['patient_id'],
            'mother_patient_id' => !empty($data['mother_patient_id']) ? (int)$data['mother_patient_id'] : null,
            'birth_time' => !empty($data['birth_time']) ? $data['birth_time'] : null,
            'birth_weight_kg' => (float)$data['birth_weight_kg'],
            'birth_length_cm' => (float)$data['birth_length_cm'],
            'place_of_delivery' => $data['place_of_delivery'] ?? 'Lying-in',
            'delivery_type' => $data['delivery_type'] ?? 'Normal Spontaneous Delivery (NSD)',
            'attended_by' => $data['attended_by'] ?? 'Midwife',
            'newborn_screening_done' => !empty($data['newborn_screening_done']) ? 1 : 0,
            'newborn_screening_date' => !empty($data['newborn_screening_date']) ? $data['newborn_screening_date'] : null,
            'newborn_screening_result' => !empty($data['newborn_screening_result']) ? trim($data['newborn_screening_result']) : null,
            'mother_cpab_tt' => !empty($data['mother_cpab_tt']) ? trim($data['mother_cpab_tt']) : null,
            'feeding_method' => $data['feeding_method'] ?? 'LAM / Exclusive Breastfeeding',
            'created_by' => $data['created_by']
        ]);

        return $result ? (int)($this->db->lastInsertId() ?: $data['patient_id']) : false;
    }

    /**
     * Update an existing Well Baby record.
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateRecord($id, $data) {
        $sql = "UPDATE wellbaby_records SET
                    mother_patient_id = :mother_patient_id,
                    birth_time = :birth_time,
                    birth_weight_kg = :birth_weight_kg,
                    birth_length_cm = :birth_length_cm,
                    place_of_delivery = :place_of_delivery,
                    delivery_type = :delivery_type,
                    attended_by = :attended_by,
                    newborn_screening_done = :newborn_screening_done,
                    newborn_screening_date = :newborn_screening_date,
                    newborn_screening_result = :newborn_screening_result,
                    mother_cpab_tt = :mother_cpab_tt,
                    feeding_method = :feeding_method
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'mother_patient_id' => !empty($data['mother_patient_id']) ? (int)$data['mother_patient_id'] : null,
            'birth_time' => !empty($data['birth_time']) ? $data['birth_time'] : null,
            'birth_weight_kg' => (float)$data['birth_weight_kg'],
            'birth_length_cm' => (float)$data['birth_length_cm'],
            'place_of_delivery' => $data['place_of_delivery'] ?? 'Lying-in',
            'delivery_type' => $data['delivery_type'] ?? 'Normal Spontaneous Delivery (NSD)',
            'attended_by' => $data['attended_by'] ?? 'Midwife',
            'newborn_screening_done' => !empty($data['newborn_screening_done']) ? 1 : 0,
            'newborn_screening_date' => !empty($data['newborn_screening_date']) ? $data['newborn_screening_date'] : null,
            'newborn_screening_result' => !empty($data['newborn_screening_result']) ? trim($data['newborn_screening_result']) : null,
            'mother_cpab_tt' => !empty($data['mother_cpab_tt']) ? trim($data['mother_cpab_tt']) : null,
            'feeding_method' => $data['feeding_method'] ?? 'LAM / Exclusive Breastfeeding'
        ]);
    }
}

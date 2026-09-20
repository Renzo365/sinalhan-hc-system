<?php

namespace App\Models;

use App\Core\Model;

class PastObstetricHistory extends Model {
    /**
     * Get all past obstetric delivery histories for a patient.
     * 
     * @param int $patientId
     * @return array
     */
    public function findByPatientId($patientId) {
        $sql = "SELECT poh.* 
                FROM past_obstetric_histories poh
                WHERE poh.patient_id = :patient_id AND poh.deleted_at IS NULL
                ORDER BY poh.gravida_no ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['patient_id' => $patientId]);
        return $stmt->fetchAll();
    }

    /**
     * Find one past obstetric history entry.
     *
     * @param int $id
     * @return array|false
     */
    public function findById($id) {
        $stmt = $this->db->prepare(
            "SELECT * FROM past_obstetric_histories WHERE id = :id AND deleted_at IS NULL LIMIT 1"
        );
        $stmt->execute(['id' => (int)$id]);
        return $stmt->fetch();
    }

    /**
     * Insert a past obstetric delivery history entry.
     * 
     * @param array $data
     * @return int|false
     */
    public function createRecord($data) {
        $sql = "INSERT INTO past_obstetric_histories (
                    patient_id, gravida_no, delivery_type, infant_sex,
                    place_of_delivery, year_delivered, attended_by,
                    status, birth_date, tt_status
                ) VALUES (
                    :patient_id, :gravida_no, :delivery_type, :infant_sex,
                    :place_of_delivery, :year_delivered, :attended_by,
                    :status, :birth_date, :tt_status
                )";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            'patient_id' => $data['patient_id'],
            'gravida_no' => (int)$data['gravida_no'],
            'delivery_type' => $data['delivery_type'] ?? 'NSD',
            'infant_sex' => $data['infant_sex'] ?? 'Unknown',
            'place_of_delivery' => !empty($data['place_of_delivery']) ? trim($data['place_of_delivery']) : null,
            'year_delivered' => !empty($data['year_delivered']) ? (int)$data['year_delivered'] : null,
            'attended_by' => !empty($data['attended_by']) ? trim($data['attended_by']) : null,
            'status' => $data['status'] ?? 'Alive',
            'birth_date' => !empty($data['birth_date']) ? $data['birth_date'] : null,
            'tt_status' => !empty($data['tt_status']) ? trim($data['tt_status']) : null
        ]);

        return $result ? (int)$this->db->lastInsertId() : false;
    }

    /**
     * Soft delete a past obstetric history entry.
     * 
     * @param int $id
     * @param int|null $userId
     * @param string|null $reason
     * @return bool
     */
    /**
     * Soft delete a past obstetric history entry.
     * 
     * @param int $id
     * @param int|null $userId
     * @param string|null $reason
     * @return bool
     */
    public function deleteRecord($id, $userId = null, $reason = null) {
        $stmt = $this->db->prepare("
            UPDATE past_obstetric_histories 
            SET deleted_at = CURRENT_TIMESTAMP, 
                deleted_by = :user_id, 
                archive_reason = :reason 
            WHERE id = :id AND deleted_at IS NULL
        ");
        return $stmt->execute([
            'id' => (int)$id,
            'user_id' => $userId ? (int)$userId : null,
            'reason' => $reason ? trim($reason) : null
        ]);
    }

    /**
     * Update an existing past obstetric history entry.
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateRecord($id, $data) {
        $sql = "UPDATE past_obstetric_histories SET
                    gravida_no = :gravida_no,
                    delivery_type = :delivery_type,
                    infant_sex = :infant_sex,
                    place_of_delivery = :place_of_delivery,
                    year_delivered = :year_delivered,
                    attended_by = :attended_by,
                    status = :status,
                    birth_date = :birth_date,
                    tt_status = :tt_status
                WHERE id = :id AND deleted_at IS NULL";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => (int)$id,
            'gravida_no' => !empty($data['gravida_no']) ? (int)$data['gravida_no'] : 1,
            'delivery_type' => $data['delivery_type'] ?? 'NSD',
            'infant_sex' => $data['infant_sex'] ?? 'Unknown',
            'place_of_delivery' => !empty($data['place_of_delivery']) ? trim($data['place_of_delivery']) : null,
            'year_delivered' => !empty($data['year_delivered']) ? (int)$data['year_delivered'] : null,
            'attended_by' => !empty($data['attended_by']) ? trim($data['attended_by']) : null,
            'status' => $data['status'] ?? 'Alive',
            'birth_date' => !empty($data['birth_date']) ? $data['birth_date'] : null,
            'tt_status' => !empty($data['tt_status']) ? trim($data['tt_status']) : null
        ]);
    }
}

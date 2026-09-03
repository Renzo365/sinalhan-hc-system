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
                WHERE poh.patient_id = :patient_id
                ORDER BY poh.gravida_no ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['patient_id' => $patientId]);
        return $stmt->fetchAll();
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
     * Delete a past obstetric history entry.
     * 
     * @param int $id
     * @return bool
     */
    public function deleteRecord($id) {
        $stmt = $this->db->prepare("DELETE FROM past_obstetric_histories WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}

<?php

namespace App\Models;

use App\Core\Model;

class VitalSigns extends Model {
    /**
     * Get all vital signs recorded for a patient, sorted from newest to oldest.
     * 
     * @param int $patientId
     * @return array History of vital signs
     */
    public function findByPatientId($patientId) {
        $sql = "SELECT vs.*, 
                       CONCAT(u.first_name, ' ', u.last_name) AS recorder_name
                FROM vital_signs vs
                LEFT JOIN users u ON vs.recorded_by = u.id
                WHERE vs.patient_id = :patient_id
                ORDER BY vs.recorded_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['patient_id' => $patientId]);
        return $stmt->fetchAll();
    }

    /**
     * Get the latest vital signs record for a patient.
     * 
     * @param int $patientId
     * @return array|false Latest record, or false if none
     */
    public function latestByPatientId($patientId) {
        $sql = "SELECT vs.*, 
                       CONCAT(u.first_name, ' ', u.last_name) AS recorder_name
                FROM vital_signs vs
                LEFT JOIN users u ON vs.recorded_by = u.id
                WHERE vs.patient_id = :patient_id
                ORDER BY vs.recorded_at DESC
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['patient_id' => $patientId]);
        return $stmt->fetch();
    }

    /**
     * Alias for latestByPatientId
     * 
     * @param int $patientId
     * @return array|false
     */
    public function getLatestByPatientId($patientId) {
        return $this->latestByPatientId($patientId);
    }

    /**
     * Insert a new set of vital signs.
     * 
     * @param array $data Vital signs fields
     * @return int|false New vital signs ID, or false on failure
     */
    public function create($data) {
        $sql = "INSERT INTO vital_signs (
                    patient_id, bp_systolic, bp_diastolic, heart_rate, 
                    respiratory_rate, temperature, weight, height, 
                    bmi, waist_circumference, oxygen_saturation, notes, recorded_by
                ) VALUES (
                    :patient_id, :bp_systolic, :bp_diastolic, :heart_rate, 
                    :respiratory_rate, :temperature, :weight, :height, 
                    :bmi, :waist_circumference, :oxygen_saturation, :notes, :recorded_by
                )";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            'patient_id' => $data['patient_id'],
            'bp_systolic' => !empty($data['bp_systolic']) ? (int)$data['bp_systolic'] : null,
            'bp_diastolic' => !empty($data['bp_diastolic']) ? (int)$data['bp_diastolic'] : null,
            'heart_rate' => !empty($data['heart_rate']) ? (int)$data['heart_rate'] : null,
            'respiratory_rate' => !empty($data['respiratory_rate']) ? (int)$data['respiratory_rate'] : null,
            'temperature' => !empty($data['temperature']) ? (float)$data['temperature'] : null,
            'weight' => !empty($data['weight']) ? (float)$data['weight'] : null,
            'height' => !empty($data['height']) ? (float)$data['height'] : null,
            'bmi' => !empty($data['bmi']) ? (float)$data['bmi'] : null,
            'waist_circumference' => !empty($data['waist_circumference']) ? (float)$data['waist_circumference'] : null,
            'oxygen_saturation' => !empty($data['oxygen_saturation']) ? (int)$data['oxygen_saturation'] : null,
            'notes' => !empty($data['notes']) ? trim($data['notes']) : null,
            'recorded_by' => $data['recorded_by']
        ]);

        return $result ? (int)$this->db->lastInsertId() : false;
    }
}

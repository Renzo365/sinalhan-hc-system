<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Consultation extends Model {
    /**
     * Get the consultation history list for a patient.
     * 
     * @param int $patientId
     * @return array History of consultations
     */
    public function findByPatientId($patientId) {
        $sql = "SELECT c.*, 
                       CONCAT(u.first_name, ' ', u.last_name) AS clinician_name
                FROM consultations c
                LEFT JOIN users u ON c.consulted_by = u.id
                WHERE c.patient_id = :patient_id AND c.deleted_at IS NULL
                ORDER BY c.consulted_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['patient_id' => $patientId]);
        return $stmt->fetchAll();
    }

    /**
     * Fetch a single consultation record by ID, joining details for patients, clinicians, and vitals.
     * 
     * @param int $id
     * @return array|false Consultation details, or false if not found
     */
    public function findById($id) {
        $sql = "SELECT c.*, 
                       CONCAT(u.first_name, ' ', u.last_name) AS clinician_name,
                       p.patient_no, p.first_name AS pat_first, p.last_name AS pat_last, p.dob AS pat_dob, p.sex AS pat_sex,
                       vs.bp_systolic, vs.bp_diastolic, vs.heart_rate, vs.respiratory_rate, vs.temperature, vs.weight, vs.height, vs.bmi, vs.oxygen_saturation, vs.notes AS vital_notes
                FROM consultations c
                LEFT JOIN users u ON c.consulted_by = u.id
                LEFT JOIN patients p ON c.patient_id = p.id
                LEFT JOIN vital_signs vs ON c.vital_signs_id = vs.id
                WHERE c.id = :id AND c.deleted_at IS NULL 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Save a new consultation record.
     * 
     * @param array $data Consultation details
     * @return int|false New consultation ID, or false on failure
     */
    public function create($data) {
        $sql = "INSERT INTO consultations (
                    patient_id, vital_signs_id, subjective, objective, 
                    assessment, plan, status, consulted_by, 
                    consulted_at, created_by
                ) VALUES (
                    :patient_id, :vital_signs_id, :subjective, :objective, 
                    :assessment, :plan, :status, :consulted_by, 
                    :consulted_at, :created_by
                )";
        
        $stmt = $this->db->prepare($sql);
        
        // Use custom consulted_at timestamp or fall back to current time
        $consultedAt = !empty($data['consulted_at']) ? $data['consulted_at'] : date('Y-m-d H:i:s');

        $result = $stmt->execute([
            'patient_id' => $data['patient_id'],
            'vital_signs_id' => !empty($data['vital_signs_id']) ? (int)$data['vital_signs_id'] : null,
            'subjective' => trim($data['subjective']),
            'objective' => trim($data['objective']),
            'assessment' => trim($data['assessment']),
            'plan' => trim($data['plan']),
            'status' => !empty($data['status']) ? $data['status'] : 'Completed',
            'consulted_by' => $data['consulted_by'],
            'consulted_at' => $consultedAt,
            'created_by' => $data['created_by']
        ]);

        return $result ? $this->db->lastInsertId() : false;
    }
}

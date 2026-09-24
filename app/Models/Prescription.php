<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Prescription extends Model {
    /**
     * Get all active prescriptions for a specific consultation.
     * 
     * @param int $consultationId
     * @return array
     */
    public function findByConsultationId($consultationId) {
        $sql = "SELECT p.*, 
                       CONCAT(u.first_name, ' ', u.last_name) AS prescriber_name
                FROM prescriptions p
                LEFT JOIN users u ON p.prescribed_by = u.id
                WHERE p.consultation_id = :consultation_id AND p.deleted_at IS NULL
                ORDER BY p.id ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['consultation_id' => (int)$consultationId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Get all active prescriptions for a specific patient.
     * 
     * @param int $patientId
     * @return array
     */
    public function findByPatientId($patientId) {
        $sql = "SELECT p.*, 
                       c.consulted_at,
                       CONCAT(u.first_name, ' ', u.last_name) AS prescriber_name
                FROM prescriptions p
                JOIN consultations c ON p.consultation_id = c.id
                LEFT JOIN users u ON p.prescribed_by = u.id
                WHERE p.patient_id = :patient_id 
                  AND p.deleted_at IS NULL 
                  AND c.deleted_at IS NULL
                ORDER BY p.prescribed_at DESC, p.id DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['patient_id' => (int)$patientId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Insert a single prescription record.
     * 
     * @param array $data
     * @return int|false
     */
    public function create($data) {
        $sql = "INSERT INTO prescriptions (
                    consultation_id, patient_id, medicine_name, dosage,
                    frequency, duration, instructions, prescribed_by, prescribed_at
                ) VALUES (
                    :consultation_id, :patient_id, :medicine_name, :dosage,
                    :frequency, :duration, :instructions, :prescribed_by, :prescribed_at
                )";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            'consultation_id' => (int)$data['consultation_id'],
            'patient_id' => (int)$data['patient_id'],
            'medicine_name' => trim($data['medicine_name'] ?? ''),
            'dosage' => trim($data['dosage'] ?? ''),
            'frequency' => trim($data['frequency'] ?? ''),
            'duration' => trim($data['duration'] ?? ''),
            'instructions' => !empty($data['instructions']) ? trim($data['instructions']) : null,
            'prescribed_by' => (int)$data['prescribed_by'],
            'prescribed_at' => !empty($data['prescribed_at']) ? $data['prescribed_at'] : date('Y-m-d H:i:s')
        ]);

        return $result ? (int)$this->db->lastInsertId() : false;
    }

    /**
     * Synchronize prescription items for a consultation.
     * Deletes removed ones or replaces all existing items with newly submitted items.
     * 
     * @param int $consultationId
     * @param int $patientId
     * @param int $userId
     * @param array $items Array of prescription arrays
     * @param string|null $consultedAt
     * @return bool
     */
    public function syncForConsultation($consultationId, $patientId, $userId, array $items, $consultedAt = null) {
        try {
            $this->db->beginTransaction();

            // Permanently remove previous items for this consultation to avoid stale rows upon re-edit
            // (Since consultations are soft-deletable and prescriptions cascade-delete with them)
            $deleteStmt = $this->db->prepare("DELETE FROM prescriptions WHERE consultation_id = :consultation_id");
            $deleteStmt->execute(['consultation_id' => (int)$consultationId]);

            $insertStmt = $this->db->prepare("INSERT INTO prescriptions (
                consultation_id, patient_id, medicine_name, dosage,
                frequency, duration, instructions, prescribed_by, prescribed_at
            ) VALUES (
                :consultation_id, :patient_id, :medicine_name, :dosage,
                :frequency, :duration, :instructions, :prescribed_by, :prescribed_at
            )");

            $prescribedAt = !empty($consultedAt) ? $consultedAt : date('Y-m-d H:i:s');

            foreach ($items as $item) {
                $medName = trim($item['medicine_name'] ?? '');
                if ($medName === '') {
                    continue; // Skip blank rows
                }

                $insertStmt->execute([
                    'consultation_id' => (int)$consultationId,
                    'patient_id' => (int)$patientId,
                    'medicine_name' => $medName,
                    'dosage' => trim($item['dosage'] ?? ''),
                    'frequency' => trim($item['frequency'] ?? ''),
                    'duration' => trim($item['duration'] ?? ''),
                    'instructions' => !empty($item['instructions']) ? trim($item['instructions']) : null,
                    'prescribed_by' => (int)$userId,
                    'prescribed_at' => $prescribedAt
                ]);
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Failed to sync prescriptions: " . $e->getMessage());
            return false;
        }
    }
}

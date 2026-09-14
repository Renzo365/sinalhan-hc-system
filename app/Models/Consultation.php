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
                       CONCAT(updater.first_name, ' ', updater.last_name) AS updater_name,
                       p.patient_no, p.first_name AS pat_first, p.last_name AS pat_last, p.dob AS pat_dob, p.sex AS pat_sex,
                       vs.bp_systolic, vs.bp_diastolic, vs.heart_rate, vs.respiratory_rate, vs.temperature, vs.weight, vs.height, vs.bmi, vs.oxygen_saturation, vs.waist_circumference, vs.notes AS vital_notes, vs.recorded_at AS vital_recorded_at
                FROM consultations c
                LEFT JOIN users u ON c.consulted_by = u.id
                LEFT JOIN users updater ON c.updated_by = updater.id
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

    /**
     * Update an existing consultation record.
     * 
     * @param int $id Consultation ID
     * @param array $data Updated consultation details
     * @return bool True on success, false on failure
     */
    public function update($id, $data) {
        $sql = "UPDATE consultations SET
                    vital_signs_id = :vital_signs_id,
                    subjective = :subjective,
                    objective = :objective,
                    assessment = :assessment,
                    plan = :plan,
                    status = :status,
                    consulted_by = :consulted_by,
                    consulted_at = :consulted_at,
                    updated_by = :updated_by,
                    updated_at = NOW()
                WHERE id = :id AND deleted_at IS NULL";

        $stmt = $this->db->prepare($sql);

        $consultedAt = !empty($data['consulted_at']) ? $data['consulted_at'] : date('Y-m-d H:i:s');

        return $stmt->execute([
            'id' => (int)$id,
            'vital_signs_id' => !empty($data['vital_signs_id']) ? (int)$data['vital_signs_id'] : null,
            'subjective' => trim($data['subjective']),
            'objective' => trim($data['objective']),
            'assessment' => trim($data['assessment']),
            'plan' => trim($data['plan']),
            'status' => !empty($data['status']) ? $data['status'] : 'Completed',
            'consulted_by' => (int)$data['consulted_by'],
            'consulted_at' => $consultedAt,
            'updated_by' => (int)$data['updated_by']
        ]);
    }

    /**
     * Cancel / void an existing consultation.
     * 
     * @param int $id Consultation ID
     * @param int $userId ID of user performing cancellation
     * @param string $reason Cancellation justification
     * @return bool True on success, false on failure
     */
    public function cancel($id, $userId, $reason = '') {
        $sql = "UPDATE consultations SET
                    status = 'Cancelled',
                    updated_by = :updated_by,
                    archive_reason = :archive_reason,
                    updated_at = NOW()
                WHERE id = :id AND deleted_at IS NULL";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => (int)$id,
            'updated_by' => (int)$userId,
            'archive_reason' => trim($reason) ?: 'Cancelled by user'
        ]);
    }

    /**
     * Archive (soft-delete) an existing consultation record.
     * 
     * @param int $id Consultation ID
     * @param int $userId ID of user archiving the record
     * @param string $reason Reason for archiving
     * @return bool True on success, false on failure
     */
    public function archive($id, $userId, $reason = '') {
        $sql = "UPDATE consultations SET
                    deleted_at = CURRENT_TIMESTAMP,
                    deleted_by = :userId,
                    archive_reason = :reason
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => (int)$id,
            'userId' => (int)$userId,
            'reason' => trim($reason) ?: 'Archived by staff'
        ]);
    }

    /**
     * Restore an archived consultation record.
     * 
     * @param int $id Consultation ID
     * @return bool True on success, false on failure
     */
    public function restore($id) {
        $sql = "UPDATE consultations SET
                    deleted_at = NULL,
                    deleted_by = NULL,
                    archive_reason = NULL
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => (int)$id
        ]);
    }

    /**
     * Retrieve all archived consultations with patient and user details.
     * 
     * @param array $filters Search and date range filters
     * @return array List of archived consultations
     */
    public function allArchived($filters = []) {
        $sql = "SELECT c.*, p.patient_no, p.first_name AS pat_first, p.last_name AS pat_last,
                       CONCAT(u.first_name, ' ', u.last_name) AS clinician_name,
                       CONCAT(archiver.first_name, ' ', archiver.last_name) AS archiver_name
                FROM consultations c
                JOIN patients p ON c.patient_id = p.id
                LEFT JOIN users u ON c.consulted_by = u.id
                LEFT JOIN users archiver ON c.deleted_by = archiver.id
                WHERE c.deleted_at IS NOT NULL";
        $params = [];
        if (!empty($filters['search'])) {
            $sql .= " AND (p.first_name LIKE :s1 OR p.last_name LIKE :s2 OR p.patient_no LIKE :s3 OR c.assessment LIKE :s4)";
            $term = '%' . $filters['search'] . '%';
            $params['s1'] = $term; $params['s2'] = $term; $params['s3'] = $term; $params['s4'] = $term;
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(c.deleted_at) >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(c.deleted_at) <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }
        $sql .= " ORDER BY c.deleted_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Find consultation record regardless of soft-delete status.
     * 
     * @param int $id
     * @return array|false
     */
    public function findWithArchivedById($id) {
        $sql = "SELECT c.*, 
                       CONCAT(u.first_name, ' ', u.last_name) AS clinician_name,
                       p.patient_no, p.first_name AS pat_first, p.last_name AS pat_last
                FROM consultations c
                LEFT JOIN users u ON c.consulted_by = u.id
                LEFT JOIN patients p ON c.patient_id = p.id
                WHERE c.id = :id 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => (int)$id]);
        return $stmt->fetch();
    }
}

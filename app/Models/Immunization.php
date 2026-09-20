<?php

namespace App\Models;

use App\Core\Model;

class Immunization extends Model {
    /**
     * Get all immunizations administered to a patient.
     * 
     * @param int $patientId
     * @return array
     */
    public function findByPatientId($patientId) {
        $sql = "SELECT imm.*, 
                       CONCAT(u.first_name, ' ', u.last_name) AS vaccinator_name
                FROM immunizations imm
                LEFT JOIN users u ON imm.administered_by = u.id
                WHERE imm.patient_id = :patient_id AND imm.deleted_at IS NULL
                ORDER BY imm.administered_date ASC, imm.id ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['patient_id' => $patientId]);
        return $stmt->fetchAll();
    }

    /**
     * Get a map of vaccine name + dose number => record for fast lookup.
     * 
     * @param int $patientId
     * @return array Keyed by 'VACCINE_NAME:DOSE_NUMBER'
     */
    public function getVaccineMap($patientId) {
        $records = $this->findByPatientId($patientId);
        $map = [];
        foreach ($records as $r) {
            $key = strtoupper(trim($r['vaccine_name'])) . ':' . (int)$r['dose_number'];
            $map[$key] = $r;
        }
        return $map;
    }

    /**
     * Record or update an immunization dose for a patient.
     * 
     * @param array $data
     * @return int|false
     */
    public function recordDose($data) {
        $source = $data['source'] ?? 'Health Center';
        $documentationStatus = $data['documentation_status'] ?? 'Administered';
        if (!in_array($source, ['Health Center', 'External', 'Patient Reported', 'Unknown'], true)) {
            $source = 'Unknown';
        }
        if (!in_array($documentationStatus, ['Administered', 'Reported', 'Unknown'], true)) {
            $documentationStatus = 'Unknown';
        }

        // Check if dose already recorded
        $sqlCheck = "SELECT id FROM immunizations 
                     WHERE patient_id = :patient_id 
                       AND UPPER(TRIM(vaccine_name)) = UPPER(TRIM(:vaccine_name))
                       AND dose_number = :dose_number
                       AND deleted_at IS NULL
                     LIMIT 1";
        $stmtCheck = $this->db->prepare($sqlCheck);
        $stmtCheck->execute([
            'patient_id' => $data['patient_id'],
            'vaccine_name' => $data['vaccine_name'],
            'dose_number' => (int)($data['dose_number'] ?? 1)
        ]);
        $existing = $stmtCheck->fetch();

        if ($existing) {
            // Update existing dose
            $sqlUpdate = "UPDATE immunizations SET
                            administered_date = :administered_date,
                            source = :source,
                            documentation_status = :documentation_status,
                            remarks = :remarks,
                            administered_by = :administered_by
                          WHERE id = :id";
            $stmtUpdate = $this->db->prepare($sqlUpdate);
            $result = $stmtUpdate->execute([
                'id' => $existing['id'],
                'administered_date' => $data['administered_date'],
                'source' => $source,
                'documentation_status' => $documentationStatus,
                'remarks' => !empty($data['remarks']) ? trim($data['remarks']) : null,
                'administered_by' => $data['administered_by']
            ]);
            return $result ? (int)$existing['id'] : false;
        } else {
            // Insert new dose
            $sqlInsert = "INSERT INTO immunizations (
                            patient_id, vaccine_name, dose_number, 
                            administered_date, source, documentation_status, remarks, administered_by
                          ) VALUES (
                            :patient_id, :vaccine_name, :dose_number,
                            :administered_date, :source, :documentation_status, :remarks, :administered_by
                          )";
            $stmtInsert = $this->db->prepare($sqlInsert);
            $result = $stmtInsert->execute([
                'patient_id' => $data['patient_id'],
                'vaccine_name' => trim($data['vaccine_name']),
                'dose_number' => (int)($data['dose_number'] ?? 1),
                'administered_date' => $data['administered_date'],
                'source' => $source,
                'documentation_status' => $documentationStatus,
                'remarks' => !empty($data['remarks']) ? trim($data['remarks']) : null,
                'administered_by' => $data['administered_by']
            ]);
            return $result ? (int)$this->db->lastInsertId() : false;
        }
    }

    /**
     * Find an immunization record by ID.
     * 
     * @param int $id
     * @return array|false
     */
    public function findById($id) {
        $sql = "SELECT imm.*, 
                       CONCAT(u.first_name, ' ', u.last_name) AS vaccinator_name
                FROM immunizations imm
                LEFT JOIN users u ON imm.administered_by = u.id
                WHERE imm.id = :id AND imm.deleted_at IS NULL
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => (int)$id]);
        return $stmt->fetch();
    }

    /**
     * Soft delete an immunization record.
     * 
     * @param int $id
     * @param int|null $userId
     * @param string|null $reason
     * @return bool
     */
    public function deleteDose($id, $userId = null, $reason = null) {
        $stmt = $this->db->prepare("
            UPDATE immunizations 
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
     * Soft delete an immunization record by patient ID, vaccine name, and dose number.
     * Used when an accidentally entered dose date is cleared in the EPI schedule.
     * 
     * @param int $patientId
     * @param string $vaccineName
     * @param int $doseNumber
     * @param int|null $userId
     * @return bool True if a record was actually deleted, false otherwise
     */
    public function deleteByPatientVaccineDose($patientId, $vaccineName, $doseNumber, $userId = null) {
        $stmt = $this->db->prepare("
            UPDATE immunizations 
            SET deleted_at = CURRENT_TIMESTAMP, 
                deleted_by = :user_id, 
                archive_reason = 'Cleared from EPI schedule' 
            WHERE patient_id = :patient_id 
              AND UPPER(TRIM(vaccine_name)) = UPPER(TRIM(:vaccine_name))
              AND dose_number = :dose_number
              AND deleted_at IS NULL
        ");
        $stmt->execute([
            'patient_id' => (int)$patientId,
            'vaccine_name' => trim($vaccineName),
            'dose_number' => (int)$doseNumber,
            'user_id' => $userId ? (int)$userId : null
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Update an existing immunization record.
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateDose($id, $data) {
        $source = $data['source'] ?? 'Health Center';
        $documentationStatus = $data['documentation_status'] ?? 'Administered';
        if (!in_array($source, ['Health Center', 'External', 'Patient Reported', 'Unknown'], true)) {
            $source = 'Unknown';
        }
        if (!in_array($documentationStatus, ['Administered', 'Reported', 'Unknown'], true)) {
            $documentationStatus = 'Unknown';
        }

        $sql = "UPDATE immunizations SET
                    vaccine_name = :vaccine_name,
                    dose_number = :dose_number,
                    administered_date = :administered_date,
                    source = :source,
                    documentation_status = :documentation_status,
                    remarks = :remarks
                WHERE id = :id AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => (int)$id,
            'vaccine_name' => trim($data['vaccine_name'] ?? ''),
            'dose_number' => (int)($data['dose_number'] ?? 1),
            'administered_date' => $data['administered_date'],
            'source' => $source,
            'documentation_status' => $documentationStatus,
            'remarks' => !empty($data['remarks']) ? trim($data['remarks']) : null
        ]);
    }
}

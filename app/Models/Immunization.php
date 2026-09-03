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
                WHERE imm.patient_id = :patient_id
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
        // Check if dose already recorded
        $sqlCheck = "SELECT id FROM immunizations 
                     WHERE patient_id = :patient_id 
                       AND UPPER(TRIM(vaccine_name)) = UPPER(TRIM(:vaccine_name))
                       AND dose_number = :dose_number
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
                            remarks = :remarks,
                            administered_by = :administered_by
                          WHERE id = :id";
            $stmtUpdate = $this->db->prepare($sqlUpdate);
            $result = $stmtUpdate->execute([
                'id' => $existing['id'],
                'administered_date' => $data['administered_date'],
                'remarks' => !empty($data['remarks']) ? trim($data['remarks']) : null,
                'administered_by' => $data['administered_by']
            ]);
            return $result ? (int)$existing['id'] : false;
        } else {
            // Insert new dose
            $sqlInsert = "INSERT INTO immunizations (
                            patient_id, vaccine_name, dose_number, 
                            administered_date, remarks, administered_by
                          ) VALUES (
                            :patient_id, :vaccine_name, :dose_number,
                            :administered_date, :remarks, :administered_by
                          )";
            $stmtInsert = $this->db->prepare($sqlInsert);
            $result = $stmtInsert->execute([
                'patient_id' => $data['patient_id'],
                'vaccine_name' => trim($data['vaccine_name']),
                'dose_number' => (int)($data['dose_number'] ?? 1),
                'administered_date' => $data['administered_date'],
                'remarks' => !empty($data['remarks']) ? trim($data['remarks']) : null,
                'administered_by' => $data['administered_by']
            ]);
            return $result ? (int)$this->db->lastInsertId() : false;
        }
    }

    /**
     * Delete an immunization record.
     * 
     * @param int $id
     * @return bool
     */
    public function deleteDose($id) {
        $stmt = $this->db->prepare("DELETE FROM immunizations WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}

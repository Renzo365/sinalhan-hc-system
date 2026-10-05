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
     * Canonicalize raw vaccine name to official DOH EPI clinical registry format.
     * 
     * @param string $name
     * @param int|null $doseNumber
     * @return string
     */
    public static function canonicalizeVaccineName($name, $doseNumber = null) {
        $clean = strtoupper(trim(str_replace(['_', '-'], [' ', ' '], $name)));
        $clean = preg_replace('/\s+/', ' ', $clean);

        if (str_starts_with($clean, 'BCG')) {
            return 'BCG';
        }
        if (str_contains($clean, 'PENTAVALENT') || str_contains($clean, 'PENTA') || str_contains($clean, 'DTP HEPB HIB')) {
            return 'Pentavalent (DTP-HepB-Hib)';
        }
        if (str_contains($clean, 'HEPATITIS B') || str_contains($clean, 'HEPA B') || str_contains($clean, 'HEP B') || $clean === 'HEPB' || str_starts_with($clean, 'HEPB ')) {
            return 'Hepatitis B (Birth Dose)';
        }
        if (str_contains($clean, 'ORAL POLIO') || $clean === 'OPV') {
            return 'Oral Polio Vaccine (OPV)';
        }
        if (str_contains($clean, 'INACTIVATED POLIO') || $clean === 'IPV') {
            return 'Inactivated Polio Vaccine (IPV)';
        }
        if (str_contains($clean, 'ROTAVIRUS') || str_contains($clean, 'ROTA')) {
            return 'Rotavirus Vaccine (ROTA)';
        }
        if (str_contains($clean, 'PNEUMOCOCCAL') || str_contains($clean, 'PCV')) {
            return 'Pneumococcal Conjugate Vaccine (PCV)';
        }
        if (str_contains($clean, 'MEASLES RUBELLA') || str_contains($clean, 'MCV1') || str_contains($clean, 'MCV 1') || str_contains($clean, 'ANTI MEASLES')) {
            return 'Measles-Rubella (MCV1)';
        }
        if (str_contains($clean, 'MMR') || str_contains($clean, 'MCV2') || str_contains($clean, 'MCV 2') || str_contains($clean, 'MEASLES MUMPS RUBELLA')) {
            return 'Measles-Mumps-Rubella (MCV2)';
        }
        if ($clean === 'MCV') {
            return ($doseNumber === 2) ? 'Measles-Mumps-Rubella (MCV2)' : 'Measles-Rubella (MCV1)';
        }
        if (str_contains($clean, 'VITAMIN A') || $clean === 'VIT A') {
            return 'Vitamin A';
        }
        if (str_contains($clean, 'DEWORMING') || str_contains($clean, 'DEWORM')) {
            return 'Deworming';
        }

        return trim($name);
    }

    /**
     * Retrieve all search alias variations for a given vaccine.
     * 
     * @param string $vaccineName
     * @return array
     */
    public static function getVaccineAliases($vaccineName) {
        $canonical = self::canonicalizeVaccineName($vaccineName);
        $groups = [
            'BCG' => ['BCG', 'BACILLUS CALMETTE-GUERIN'],
            'Hepatitis B (Birth Dose)' => ['HEPATITIS B (BIRTH DOSE)', 'HEPATITIS B', 'HEPATITIS_B', 'HEPA B', 'HEPA_B', 'HEPB', 'HEP B'],
            'Pentavalent (DTP-HepB-Hib)' => ['PENTAVALENT (DTP-HEPB-HIB)', 'PENTAVALENT', 'PENTA', 'DTP-HEPB-HIB', 'DTP HEPB HIB'],
            'Oral Polio Vaccine (OPV)' => ['ORAL POLIO VACCINE (OPV)', 'ORAL POLIO VACCINE', 'ORAL POLIO', 'OPV'],
            'Inactivated Polio Vaccine (IPV)' => ['INACTIVATED POLIO VACCINE (IPV)', 'INACTIVATED POLIO', 'IPV'],
            'Rotavirus Vaccine (ROTA)' => ['ROTAVIRUS VACCINE (ROTA)', 'ROTAVIRUS VACCINE', 'ROTAVIRUS', 'ROTA', 'ROTA I', 'ROTA II', 'ROTA 1', 'ROTA 2'],
            'Pneumococcal Conjugate Vaccine (PCV)' => ['PNEUMOCOCCAL CONJUGATE VACCINE (PCV)', 'PNEUMOCOCCAL CONJUGATE VACCINE', 'PCV', 'PNEUMOCOCCAL'],
            'Measles-Rubella (MCV1)' => ['MEASLES-RUBELLA (MCV1)', 'MEASLES-RUBELLA', 'MEASLES RUBELLA (MCV1)', 'MCV1', 'MCV 1', 'MEASLES (MCV 1)', 'ANTI-MEASLES', 'ANTI MEASLES', 'MEASLES', 'MCV'],
            'Measles-Mumps-Rubella (MCV2)' => ['MEASLES-MUMPS-RUBELLA (MCV2)', 'MEASLES-MUMPS-RUBELLA', 'MEASLES MUMPS RUBELLA (MCV2)', 'MCV2', 'MCV 2', 'MMR BOOSTER (MCV 2)', 'MMR BOOSTER', 'MMR', 'MCV'],
            'Vitamin A' => ['VITAMIN A', 'VIT A', 'VITAMIN_A', 'VITAMIN A CAPSULE'],
            'Deworming' => ['DEWORMING', 'DEWORM', 'DEWORMING TABLET']
        ];
        return $groups[$canonical] ?? [strtoupper(trim($vaccineName))];
    }

    /**
     * Get a map of vaccine name + dose number => record for fast lookup.
     * Indexing occurs across canonical names, short names, and EPI schedule keys.
     * 
     * @param int $patientId
     * @return array Keyed by multiple alias variations and schedule keys
     */
    public function getVaccineMap($patientId) {
        $records = $this->findByPatientId($patientId);
        $map = [];

        foreach ($records as $r) {
            $dose = (int)$r['dose_number'];
            $rawName = trim($r['vaccine_name']);
            $canonical = self::canonicalizeVaccineName($rawName, $dose);
            $aliases = self::getVaccineAliases($canonical);

            // 1. Raw DB key
            $map[strtoupper($rawName) . ':' . $dose] = $r;
            // 2. Canonical key
            $map[strtoupper($canonical) . ':' . $dose] = $r;

            // 3. All alias variations
            foreach ($aliases as $alias) {
                $map[$alias . ':' . $dose] = $r;
            }

            // 4. EPI Form Schedule Keys
            if ($canonical === 'BCG' && $dose === 1) {
                $map['BCG__1'] = $r;
            } elseif ($canonical === 'Hepatitis B (Birth Dose)' && $dose === 1) {
                $map['HEPATITIS_B__1'] = $r;
                $map['HEPATITIS B__1'] = $r;
            } elseif ($canonical === 'Pentavalent (DTP-HepB-Hib)') {
                $map['PENTAVALENT__' . $dose] = $r;
                $map['Pentavalent__' . $dose] = $r;
            } elseif ($canonical === 'Oral Polio Vaccine (OPV)') {
                $map['OPV__' . $dose] = $r;
            } elseif ($canonical === 'Rotavirus Vaccine (ROTA)') {
                $map['ROTAVIRUS__' . $dose] = $r;
                $map['Rotavirus__' . $dose] = $r;
                $map['ROTA__' . $dose] = $r;
                $map['Rota__' . $dose] = $r;
            } elseif ($canonical === 'Pneumococcal Conjugate Vaccine (PCV)') {
                $map['PCV__' . $dose] = $r;
            } elseif ($canonical === 'Inactivated Polio Vaccine (IPV)' && $dose === 1) {
                $map['IPV__1'] = $r;
            } elseif ($canonical === 'Measles-Rubella (MCV1)' && ($dose === 1 || $dose === 0)) {
                $map['MCV__1'] = $r;
                $map['MCV1__1'] = $r;
            } elseif ($canonical === 'Measles-Mumps-Rubella (MCV2)' && ($dose === 2 || $dose === 1)) {
                $map['MCV__2'] = $r;
                $map['MCV2__2'] = $r;
            } elseif ($canonical === 'Vitamin A') {
                $map['VITAMIN_A__' . $dose] = $r;
                $map['Vitamin_A__' . $dose] = $r;
            } elseif ($canonical === 'Deworming') {
                $map['DEWORMING__' . $dose] = $r;
                $map['Deworming__' . $dose] = $r;
            }
        }
        return $map;
    }

    /**
     * Record or update an immunization dose for a patient.
     * Prevents duplicate inserts by matching across known vaccine aliases.
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

        $doseNumber = (int)($data['dose_number'] ?? 1);
        $canonicalName = self::canonicalizeVaccineName($data['vaccine_name'], $doseNumber);
        $aliases = self::getVaccineAliases($canonicalName);
        $placeholders = implode(',', array_fill(0, count($aliases), '?'));

        // Check if dose already recorded using alias group
        $sqlCheck = "SELECT id FROM immunizations 
                     WHERE patient_id = ? 
                       AND dose_number = ?
                       AND UPPER(TRIM(vaccine_name)) IN ($placeholders)
                       AND deleted_at IS NULL
                     LIMIT 1";
        $stmtCheck = $this->db->prepare($sqlCheck);
        $checkParams = array_merge([(int)$data['patient_id'], $doseNumber], $aliases);
        $stmtCheck->execute($checkParams);
        $existing = $stmtCheck->fetch();

        if ($existing) {
            // Update existing dose
            $sqlUpdate = "UPDATE immunizations SET
                            vaccine_name = :vaccine_name,
                            administered_date = :administered_date,
                            source = :source,
                            documentation_status = :documentation_status,
                            remarks = :remarks,
                            administered_by = :administered_by
                          WHERE id = :id";
            $stmtUpdate = $this->db->prepare($sqlUpdate);
            $result = $stmtUpdate->execute([
                'id' => $existing['id'],
                'vaccine_name' => $canonicalName,
                'administered_date' => $data['administered_date'],
                'source' => $source,
                'documentation_status' => $documentationStatus,
                'remarks' => !empty($data['remarks']) ? trim($data['remarks']) : null,
                'administered_by' => $data['administered_by']
            ]);
            return $result ? (int)$existing['id'] : false;
        } else {
            // Insert new dose using canonical name
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
                'vaccine_name' => $canonicalName,
                'dose_number' => $doseNumber,
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
        $canonicalName = self::canonicalizeVaccineName($vaccineName, (int)$doseNumber);
        $aliases = self::getVaccineAliases($canonicalName);
        $placeholders = implode(',', array_fill(0, count($aliases), '?'));

        $sql = "UPDATE immunizations 
                SET deleted_at = CURRENT_TIMESTAMP, 
                    deleted_by = ?, 
                    archive_reason = 'Cleared from EPI schedule' 
                WHERE patient_id = ? 
                  AND dose_number = ?
                  AND UPPER(TRIM(vaccine_name)) IN ($placeholders)
                  AND deleted_at IS NULL";
        
        $params = array_merge([
            $userId ? (int)$userId : null,
            (int)$patientId,
            (int)$doseNumber
        ], $aliases);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
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

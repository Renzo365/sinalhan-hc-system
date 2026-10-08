<?php

namespace App\Models;

use App\Core\Model;

class WellbabyRecord extends Model {
    private function normalizeFeedingMethod($feedingMethod) {
        $aliases = [
            'Bottle Feeding (Formula)' => 'Bottle Feed',
            'Bottle Feeding' => 'Bottle Feed',
            'Bottle Feed' => 'Bottle Feed',
            'Formula Feeding' => 'Bottle Feed',
            'Mixed Feeding' => 'Mixed',
            'Mixed' => 'Mixed',
            'LAM (Exclusive Breastfeeding)' => 'LAM / Exclusive Breastfeeding',
            'LAM / Exclusive Breastfeeding' => 'LAM / Exclusive Breastfeeding',
            'LAM' => 'LAM / Exclusive Breastfeeding',
        ];
        $feedingMethod = $aliases[$feedingMethod] ?? $feedingMethod;
        return in_array($feedingMethod, ['LAM / Exclusive Breastfeeding', 'Bottle Feed', 'Mixed'], true)
            ? $feedingMethod
            : 'LAM / Exclusive Breastfeeding';
    }

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
                WHERE wb.patient_id = :patient_id AND wb.deleted_at IS NULL
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
                WHERE wb.id = :id AND wb.deleted_at IS NULL
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
                    birth_length_cm, place_of_delivery, place_of_delivery_other,
                    delivery_type, attended_by, attended_by_other,
                    newborn_screening_done, newborn_screening_date,
                    newborn_screening_result, mother_cpab_tt, feeding_method,
                    created_by
                ) VALUES (
                    :patient_id, :mother_patient_id, :birth_time, :birth_weight_kg,
                    :birth_length_cm, :place_of_delivery, :place_of_delivery_other,
                    :delivery_type, :attended_by, :attended_by_other,
                    :newborn_screening_done, :newborn_screening_date,
                    :newborn_screening_result, :mother_cpab_tt, :feeding_method,
                    :created_by
                )
                ON DUPLICATE KEY UPDATE
                    mother_patient_id = VALUES(mother_patient_id),
                    birth_time = VALUES(birth_time),
                    birth_weight_kg = VALUES(birth_weight_kg),
                    birth_length_cm = VALUES(birth_length_cm),
                    place_of_delivery = VALUES(place_of_delivery),
                    place_of_delivery_other = VALUES(place_of_delivery_other),
                    delivery_type = VALUES(delivery_type),
                    attended_by = VALUES(attended_by),
                    attended_by_other = VALUES(attended_by_other),
                    newborn_screening_done = VALUES(newborn_screening_done),
                    newborn_screening_date = VALUES(newborn_screening_date),
                    newborn_screening_result = VALUES(newborn_screening_result),
                    mother_cpab_tt = VALUES(mother_cpab_tt),
                    feeding_method = VALUES(feeding_method),
                    deleted_at = NULL,
                    deleted_by = NULL,
                    archive_reason = NULL,
                    updated_at = CURRENT_TIMESTAMP";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            'patient_id' => $data['patient_id'],
            'mother_patient_id' => !empty($data['mother_patient_id']) ? (int)$data['mother_patient_id'] : null,
            'birth_time' => !empty($data['birth_time']) ? $data['birth_time'] : null,
            'birth_weight_kg' => (float)$data['birth_weight_kg'],
            'birth_length_cm' => (float)$data['birth_length_cm'],
            'place_of_delivery' => $data['place_of_delivery'] ?? 'Lying-in',
            'place_of_delivery_other' => !empty($data['place_of_delivery_other']) ? trim($data['place_of_delivery_other']) : null,
            'delivery_type' => $data['delivery_type'] ?? 'Normal Spontaneous Delivery (NSD)',
            'attended_by' => $data['attended_by'] ?? 'Midwife',
            'attended_by_other' => !empty($data['attended_by_other']) ? trim($data['attended_by_other']) : null,
            'newborn_screening_done' => !empty($data['newborn_screening_done']) ? 1 : 0,
            'newborn_screening_date' => !empty($data['newborn_screening_date']) ? $data['newborn_screening_date'] : null,
            'newborn_screening_result' => !empty($data['newborn_screening_result']) ? trim($data['newborn_screening_result']) : null,
            'mother_cpab_tt' => !empty($data['mother_cpab_tt']) ? trim($data['mother_cpab_tt']) : null,
            'feeding_method' => $this->normalizeFeedingMethod($data['feeding_method'] ?? ''),
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
                    place_of_delivery_other = :place_of_delivery_other,
                    delivery_type = :delivery_type,
                    attended_by = :attended_by,
                    attended_by_other = :attended_by_other,
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
            'place_of_delivery_other' => !empty($data['place_of_delivery_other']) ? trim($data['place_of_delivery_other']) : null,
            'delivery_type' => $data['delivery_type'] ?? 'Normal Spontaneous Delivery (NSD)',
            'attended_by' => $data['attended_by'] ?? 'Midwife',
            'attended_by_other' => !empty($data['attended_by_other']) ? trim($data['attended_by_other']) : null,
            'newborn_screening_done' => !empty($data['newborn_screening_done']) ? 1 : 0,
            'newborn_screening_date' => !empty($data['newborn_screening_date']) ? $data['newborn_screening_date'] : null,
            'newborn_screening_result' => !empty($data['newborn_screening_result']) ? trim($data['newborn_screening_result']) : null,
            'mother_cpab_tt' => !empty($data['mother_cpab_tt']) ? trim($data['mother_cpab_tt']) : null,
            'feeding_method' => $this->normalizeFeedingMethod($data['feeding_method'] ?? '')
        ]);
    }

    /**
     * Soft delete a Well Baby record.
     * 
     * @param int $id
     * @param int|null $userId
     * @param string|null $reason
     * @return bool
     */
    public function deleteRecord($id, $userId = null, $reason = null) {
        $stmt = $this->db->prepare("
            UPDATE wellbaby_records 
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
     * Get all registered well-baby records with child demographics and mother info for registry roster.
     * 
     * @param string $search
     * @return array
     */
    public function getRegisteredRoster($search = '') {
        $searchTerm = is_array($search) ? ($search['search'] ?? '') : (string)$search;
        $searchTerm = trim($searchTerm);

        $sql = "SELECT wb.*, 
                       p.id AS patient_id, p.patient_no, p.envelope_no, p.family_no, p.first_name, p.last_name, 
                       p.middle_name, p.suffix, p.dob, p.sex, p.address, p.mother_name, p.father_name,
                       TIMESTAMPDIFF(MONTH, p.dob, CURRENT_DATE()) AS age_months,
                       TIMESTAMPDIFF(YEAR, p.dob, CURRENT_DATE()) AS age_years,
                       m.id AS mother_id, m.first_name AS mother_first_name, m.last_name AS mother_last_name, m.patient_no AS mother_patient_no,
                       (
                           SELECT COUNT(DISTINCT 
                               CASE 
                                   WHEN UPPER(imm.vaccine_name) LIKE '%BCG%' AND imm.dose_number = 1 THEN 'BCG:1'
                                   WHEN (UPPER(imm.vaccine_name) LIKE '%HEPATITIS B%' OR UPPER(imm.vaccine_name) LIKE '%HEPA B%') AND imm.dose_number = 1 THEN 'HEPB:1'
                                   WHEN (UPPER(imm.vaccine_name) LIKE '%PENTA%' OR UPPER(imm.vaccine_name) LIKE '%DTP%') AND imm.dose_number IN (1, 2, 3) THEN CONCAT('PENTA:', imm.dose_number)
                                   WHEN (UPPER(imm.vaccine_name) LIKE '%ORAL POLIO%' OR UPPER(imm.vaccine_name) LIKE '%OPV%') AND imm.dose_number IN (1, 2, 3) THEN CONCAT('OPV:', imm.dose_number)
                                   WHEN UPPER(imm.vaccine_name) LIKE '%ROTA%' AND imm.dose_number IN (1, 2) THEN CONCAT('ROTA:', imm.dose_number)
                                   WHEN (UPPER(imm.vaccine_name) LIKE '%INACTIVATED POLIO%' OR UPPER(imm.vaccine_name) LIKE '%IPV%') AND imm.dose_number = 1 THEN 'IPV:1'
                                   WHEN (UPPER(imm.vaccine_name) LIKE '%MEASLES-RUBELLA%' OR UPPER(imm.vaccine_name) LIKE '%MCV1%' OR UPPER(imm.vaccine_name) LIKE '%ANTI-MEASLES%') AND imm.dose_number IN (1, 0) THEN 'MCV:1'
                                   WHEN (UPPER(imm.vaccine_name) LIKE '%MMR%' OR UPPER(imm.vaccine_name) LIKE '%MCV2%') AND imm.dose_number IN (1, 2) THEN 'MCV:2'
                                   WHEN UPPER(imm.vaccine_name) LIKE '%MCV%' AND imm.dose_number = 1 THEN 'MCV:1'
                                   WHEN UPPER(imm.vaccine_name) LIKE '%MCV%' AND imm.dose_number = 2 THEN 'MCV:2'
                                   ELSE NULL
                               END
                           )
                           FROM immunizations imm 
                           WHERE imm.patient_id = p.id AND imm.deleted_at IS NULL
                       ) AS imm_count,
                       (SELECT COUNT(*) FROM child_growth_logs cgl WHERE cgl.wellbaby_id = wb.id AND cgl.deleted_at IS NULL) AS growth_log_count,
                       (SELECT MAX(log_date) FROM child_growth_logs cgl WHERE cgl.wellbaby_id = wb.id AND cgl.deleted_at IS NULL) AS last_growth_date
                FROM wellbaby_records wb
                INNER JOIN patients p ON wb.patient_id = p.id
                LEFT JOIN patients m ON wb.mother_patient_id = m.id
                WHERE wb.deleted_at IS NULL 
                  AND p.deleted_at IS NULL";

        $params = [];
        if (!empty($searchTerm)) {
            $sql .= " AND (p.first_name LIKE :s1 OR p.last_name LIKE :s2 OR p.patient_no LIKE :s3 OR p.envelope_no LIKE :s4 OR m.first_name LIKE :s5 OR m.last_name LIKE :s6 OR p.mother_name LIKE :s7 OR p.family_no LIKE :s8)";
            $term = '%' . $searchTerm . '%';
            $params['s1'] = $term;
            $params['s2'] = $term;
            $params['s3'] = $term;
            $params['s4'] = $term;
            $params['s5'] = $term;
            $params['s6'] = $term;
            $params['s7'] = $term;
            $params['s8'] = $term;
        }

        $sql .= " ORDER BY p.dob DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Compute operational summary KPIs for the Well-Baby & EPI Registry.
     * 
     * @return array
     */
    public function getRegistryMetrics() {
        $sqlRegistered = "SELECT COUNT(*) FROM wellbaby_records wb INNER JOIN patients p ON wb.patient_id = p.id WHERE wb.deleted_at IS NULL AND p.deleted_at IS NULL";
        $totalRegistered = (int)$this->db->query($sqlRegistered)->fetchColumn();

        $sqlUnderOne = "SELECT COUNT(*) FROM wellbaby_records wb INNER JOIN patients p ON wb.patient_id = p.id WHERE wb.deleted_at IS NULL AND p.deleted_at IS NULL AND TIMESTAMPDIFF(MONTH, p.dob, CURRENT_DATE()) < 12";
        $underOneCohort = (int)$this->db->query($sqlUnderOne)->fetchColumn();

        $sqlFIC = "SELECT COUNT(DISTINCT wb.patient_id) 
                   FROM wellbaby_records wb
                   INNER JOIN patients p ON wb.patient_id = p.id
                   INNER JOIN (
                       SELECT patient_id, COUNT(DISTINCT 
                           CASE 
                               WHEN UPPER(vaccine_name) LIKE '%BCG%' AND dose_number = 1 THEN 'BCG:1'
                               WHEN (UPPER(vaccine_name) LIKE '%HEPATITIS B%' OR UPPER(vaccine_name) LIKE '%HEPA B%') AND dose_number = 1 THEN 'HEPB:1'
                               WHEN (UPPER(vaccine_name) LIKE '%PENTA%' OR UPPER(vaccine_name) LIKE '%DTP%') AND dose_number IN (1, 2, 3) THEN CONCAT('PENTA:', dose_number)
                               WHEN (UPPER(vaccine_name) LIKE '%ORAL POLIO%' OR UPPER(vaccine_name) LIKE '%OPV%') AND dose_number IN (1, 2, 3) THEN CONCAT('OPV:', dose_number)
                               WHEN UPPER(vaccine_name) LIKE '%ROTA%' AND dose_number IN (1, 2) THEN CONCAT('ROTA:', dose_number)
                               WHEN (UPPER(vaccine_name) LIKE '%INACTIVATED POLIO%' OR UPPER(vaccine_name) LIKE '%IPV%') AND dose_number = 1 THEN 'IPV:1'
                               WHEN (UPPER(vaccine_name) LIKE '%MEASLES-RUBELLA%' OR UPPER(vaccine_name) LIKE '%MCV1%' OR UPPER(vaccine_name) LIKE '%ANTI-MEASLES%') AND dose_number IN (1, 0) THEN 'MCV:1'
                               WHEN (UPPER(vaccine_name) LIKE '%MMR%' OR UPPER(vaccine_name) LIKE '%MCV2%') AND dose_number IN (1, 2) THEN 'MCV:2'
                               WHEN UPPER(vaccine_name) LIKE '%MCV%' AND dose_number = 1 THEN 'MCV:1'
                               WHEN UPPER(vaccine_name) LIKE '%MCV%' AND dose_number = 2 THEN 'MCV:2'
                               ELSE NULL
                           END
                       ) as routine_doses
                       FROM immunizations
                       WHERE deleted_at IS NULL
                       GROUP BY patient_id
                       HAVING routine_doses >= 9
                   ) imm_summary ON wb.patient_id = imm_summary.patient_id
                   WHERE wb.deleted_at IS NULL AND p.deleted_at IS NULL";
        $ficCount = (int)$this->db->query($sqlFIC)->fetchColumn();

        return [
            'total_registered' => $totalRegistered,
            'under_one_cohort' => $underOneCohort,
            'fic_count' => $ficCount
        ];
    }
}
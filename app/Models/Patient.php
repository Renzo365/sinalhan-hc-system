<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Patient extends Model {
    /**
     * Get all active (non-archived) patients based on filters.
     * 
     * @param array $filters Filter terms (search, barangay, sex, age_group, program_type)
     * @return array List of matching patient records with computed badges
     */
    public function allActive($filters = []) {
        $sql = "SELECT p.*, 
                       TIMESTAMPDIFF(YEAR, p.dob, CURRENT_DATE()) AS age,
                       (SELECT COUNT(*) FROM prenatal_records pr WHERE pr.patient_id = p.id AND pr.is_active = 1) AS active_prenatal_count,
                       (SELECT COUNT(*) FROM wellbaby_records wb WHERE wb.patient_id = p.id) AS has_wellbaby_record
                FROM patients p
                WHERE p.deleted_at IS NULL";
        $params = [];

        // Global Search (Name, Patient No, Family No, Contact No, PhilHealth PIN, Barangay)
        if (!empty($filters['search'])) {
            $sql .= " AND (p.first_name LIKE :search_first 
                        OR p.last_name LIKE :search_last 
                        OR p.patient_no LIKE :search_no 
                        OR p.family_no LIKE :search_family
                        OR p.contact_no LIKE :search_contact 
                        OR p.philhealth_no LIKE :search_phic
                        OR p.barangay LIKE :search_barangay)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params['search_first'] = $searchTerm;
            $params['search_last'] = $searchTerm;
            $params['search_no'] = $searchTerm;
            $params['search_family'] = $searchTerm;
            $params['search_contact'] = $searchTerm;
            $params['search_phic'] = $searchTerm;
            $params['search_barangay'] = $searchTerm;
        }

        // Filter by Barangay
        if (!empty($filters['barangay'])) {
            $sql .= " AND p.barangay = :barangay";
            $params['barangay'] = $filters['barangay'];
        }

        // Filter by Sex
        if (!empty($filters['sex'])) {
            $sql .= " AND p.sex = :sex";
            $params['sex'] = $filters['sex'];
        }

        // Filter by Age Group
        if (!empty($filters['age_group'])) {
            switch ($filters['age_group']) {
                case 'infant':
                    $sql .= " AND TIMESTAMPDIFF(YEAR, p.dob, CURRENT_DATE()) <= 1";
                    break;
                case 'toddler':
                    $sql .= " AND TIMESTAMPDIFF(YEAR, p.dob, CURRENT_DATE()) BETWEEN 2 AND 5";
                    break;
                case 'child':
                    $sql .= " AND TIMESTAMPDIFF(YEAR, p.dob, CURRENT_DATE()) BETWEEN 6 AND 12";
                    break;
                case 'teen':
                    $sql .= " AND TIMESTAMPDIFF(YEAR, p.dob, CURRENT_DATE()) BETWEEN 13 AND 19";
                    break;
                case 'adult':
                    $sql .= " AND TIMESTAMPDIFF(YEAR, p.dob, CURRENT_DATE()) BETWEEN 20 AND 59";
                    break;
                case 'senior':
                    $sql .= " AND TIMESTAMPDIFF(YEAR, p.dob, CURRENT_DATE()) >= 60";
                    break;
            }
        }

        // Filter by Program Type
        if (!empty($filters['program_type'])) {
            switch ($filters['program_type']) {
                case 'wellbaby':
                    $sql .= " AND (TIMESTAMPDIFF(YEAR, p.dob, CURRENT_DATE()) <= 5 OR EXISTS (SELECT 1 FROM wellbaby_records wb WHERE wb.patient_id = p.id))";
                    break;
                case 'prenatal':
                    $sql .= " AND p.sex = 'Female' AND EXISTS (SELECT 1 FROM prenatal_records pr WHERE pr.patient_id = p.id AND pr.is_active = 1)";
                    break;
                case 'senior':
                    $sql .= " AND TIMESTAMPDIFF(YEAR, p.dob, CURRENT_DATE()) >= 60";
                    break;
                case 'opd':
                    $sql .= " AND (TIMESTAMPDIFF(YEAR, p.dob, CURRENT_DATE()) BETWEEN 6 AND 59)
                              AND NOT (p.sex = 'Female' AND EXISTS (SELECT 1 FROM prenatal_records pr WHERE pr.patient_id = p.id AND pr.is_active = 1))";
                    break;
            }
        }

        $sql .= " ORDER BY p.last_name ASC, p.first_name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $patients = $stmt->fetchAll();

        // Attach computed program badge to each record
        foreach ($patients as &$patient) {
            $age = (int)($patient['age'] ?? 0);
            $sex = $patient['sex'] ?? 'Unknown';
            $hasActivePrenatal = !empty($patient['active_prenatal_count']) && (int)$patient['active_prenatal_count'] > 0;
            $hasWellBaby = !empty($patient['has_wellbaby_record']) && (int)$patient['has_wellbaby_record'] > 0;

            if ($age <= 5 || $hasWellBaby) {
                $patient['program_badge'] = [
                    'tag' => 'wellbaby',
                    'class' => 'bg-success text-white',
                    'icon' => 'bi-emoji-smile-fill',
                    'label' => 'Well Baby'
                ];
            } elseif ($hasActivePrenatal && strtolower($sex) === 'female') {
                $patient['program_badge'] = [
                    'tag' => 'prenatal',
                    'class' => 'bg-pink text-white',
                    'icon' => 'bi-heart-pulse-fill',
                    'label' => 'Prenatal'
                ];
            } elseif ($age >= 60) {
                $patient['program_badge'] = [
                    'tag' => 'senior',
                    'class' => 'bg-purple text-white',
                    'icon' => 'bi-award-fill',
                    'label' => 'Senior'
                ];
            } else {
                $patient['program_badge'] = [
                    'tag' => 'opd',
                    'class' => 'bg-primary text-white',
                    'icon' => 'bi-clipboard2-pulse',
                    'label' => 'General OPD'
                ];
            }
        }

        return $patients;
    }

    /**
     * Find a patient by their internal ID, joining creator and updater name details.
     * 
     * @param int $id
     * @return array|false Patient details, or false if not found
     */
    public function findById($id) {
        $sql = "SELECT p.*, 
                       TIMESTAMPDIFF(YEAR, p.dob, CURRENT_DATE()) AS age,
                       CONCAT(u1.first_name, ' ', u1.last_name) AS creator_name,
                       CONCAT(u2.first_name, ' ', u2.last_name) AS updater_name
                FROM patients p
                LEFT JOIN users u1 ON p.created_by = u1.id
                LEFT JOIN users u2 ON p.updated_by = u2.id
                WHERE p.id = :id AND p.deleted_at IS NULL 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Find existing active patients with matching names to prevent duplicate creation.
     * 
     * @param string $firstName
     * @param string $lastName
     * @return array Matches
     */
    public function findDuplicates($firstName, $lastName) {
        $sql = "SELECT id, patient_no, first_name, last_name, dob, sex, barangay 
                FROM patients 
                WHERE first_name = :first_name AND last_name = :last_name AND deleted_at IS NULL";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'first_name' => trim($firstName),
            'last_name' => trim($lastName)
        ]);
        return $stmt->fetchAll();
    }

    /**
     * Insert a new patient into the database.
     * 
     * @param array $data Patient data fields
     * @return int|false The new patient ID, or false on failure
     */
    public function create($data) {
        // Auto-generate patient number inside the create method
        $data['patient_no'] = $this->generatePatientNo();

        $sql = "INSERT INTO patients (
                    patient_no, family_no, first_name, middle_name, last_name, suffix, dob, sex, 
                    civil_status, civil_status_other, blood_type, religion, occupation, education_attainment,
                    contact_no, barangay, address, phic_status, phic_type, philhealth_no,
                    father_name, father_dob, mother_name, mother_dob, spouse_name, spouse_dob,
                    emergency_name, emergency_relationship, emergency_no, created_by
                ) VALUES (
                    :patient_no, :family_no, :first_name, :middle_name, :last_name, :suffix, :dob, :sex, 
                    :civil_status, :civil_status_other, :blood_type, :religion, :occupation, :education_attainment,
                    :contact_no, :barangay, :address, :phic_status, :phic_type, :philhealth_no,
                    :father_name, :father_dob, :mother_name, :mother_dob, :spouse_name, :spouse_dob,
                    :emergency_name, :emergency_relationship, :emergency_no, :created_by
                )";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            'patient_no' => $data['patient_no'],
            'family_no' => !empty($data['family_no']) ? trim($data['family_no']) : null,
            'first_name' => trim($data['first_name']),
            'middle_name' => !empty($data['middle_name']) ? trim($data['middle_name']) : null,
            'last_name' => trim($data['last_name']),
            'suffix' => !empty($data['suffix']) ? trim($data['suffix']) : null,
            'dob' => $data['dob'],
            'sex' => $data['sex'],
            'civil_status' => $data['civil_status'],
            'civil_status_other' => ($data['civil_status'] === 'Others' && !empty($data['civil_status_other'])) ? trim($data['civil_status_other']) : null,
            'blood_type' => !empty($data['blood_type']) ? $data['blood_type'] : 'Unknown',
            'religion' => !empty($data['religion']) ? trim($data['religion']) : null,
            'occupation' => !empty($data['occupation']) ? trim($data['occupation']) : null,
            'education_attainment' => !empty($data['education_attainment']) ? $data['education_attainment'] : null,
            'contact_no' => !empty($data['contact_no']) ? trim($data['contact_no']) : null,
            'barangay' => !empty($data['barangay']) ? trim($data['barangay']) : 'Sinalhan',
            'address' => trim($data['address']),
            'phic_status' => !empty($data['phic_status']) ? $data['phic_status'] : 'Non-Member',
            'phic_type' => !empty($data['phic_type']) ? trim($data['phic_type']) : null,
            'philhealth_no' => !empty($data['philhealth_no']) ? trim($data['philhealth_no']) : null,
            'father_name' => !empty($data['father_name']) ? trim($data['father_name']) : null,
            'father_dob' => !empty($data['father_dob']) ? $data['father_dob'] : null,
            'mother_name' => !empty($data['mother_name']) ? trim($data['mother_name']) : null,
            'mother_dob' => !empty($data['mother_dob']) ? $data['mother_dob'] : null,
            'spouse_name' => !empty($data['spouse_name']) ? trim($data['spouse_name']) : null,
            'spouse_dob' => !empty($data['spouse_dob']) ? $data['spouse_dob'] : null,
            'emergency_name' => !empty($data['emergency_name']) ? trim($data['emergency_name']) : null,
            'emergency_relationship' => !empty($data['emergency_relationship']) ? trim($data['emergency_relationship']) : null,
            'emergency_no' => !empty($data['emergency_no']) ? trim($data['emergency_no']) : null,
            'created_by' => $data['created_by']
        ]);

        return $result ? (int)$this->db->lastInsertId() : false;
    }

    /**
     * Update an existing patient's demographic details.
     * 
     * @param int $id Patient ID
     * @param array $data Fields to update
     * @return bool
     */
    public function update($id, $data) {
        $sql = "UPDATE patients SET 
                    family_no = :family_no,
                    first_name = :first_name,
                    middle_name = :middle_name,
                    last_name = :last_name,
                    suffix = :suffix,
                    dob = :dob,
                    sex = :sex,
                    civil_status = :civil_status,
                    civil_status_other = :civil_status_other,
                    blood_type = :blood_type,
                    religion = :religion,
                    occupation = :occupation,
                    education_attainment = :education_attainment,
                    contact_no = :contact_no,
                    barangay = :barangay,
                    address = :address,
                    phic_status = :phic_status,
                    phic_type = :phic_type,
                    philhealth_no = :philhealth_no,
                    father_name = :father_name,
                    father_dob = :father_dob,
                    mother_name = :mother_name,
                    mother_dob = :mother_dob,
                    spouse_name = :spouse_name,
                    spouse_dob = :spouse_dob,
                    emergency_name = :emergency_name,
                    emergency_relationship = :emergency_relationship,
                    emergency_no = :emergency_no,
                    updated_by = :updated_by,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND deleted_at IS NULL";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'family_no' => !empty($data['family_no']) ? trim($data['family_no']) : null,
            'first_name' => trim($data['first_name']),
            'middle_name' => !empty($data['middle_name']) ? trim($data['middle_name']) : null,
            'last_name' => trim($data['last_name']),
            'suffix' => !empty($data['suffix']) ? trim($data['suffix']) : null,
            'dob' => $data['dob'],
            'sex' => $data['sex'],
            'civil_status' => $data['civil_status'],
            'civil_status_other' => ($data['civil_status'] === 'Others' && !empty($data['civil_status_other'])) ? trim($data['civil_status_other']) : null,
            'blood_type' => !empty($data['blood_type']) ? $data['blood_type'] : 'Unknown',
            'religion' => !empty($data['religion']) ? trim($data['religion']) : null,
            'occupation' => !empty($data['occupation']) ? trim($data['occupation']) : null,
            'education_attainment' => !empty($data['education_attainment']) ? $data['education_attainment'] : null,
            'contact_no' => !empty($data['contact_no']) ? trim($data['contact_no']) : null,
            'barangay' => !empty($data['barangay']) ? trim($data['barangay']) : 'Sinalhan',
            'address' => trim($data['address']),
            'phic_status' => !empty($data['phic_status']) ? $data['phic_status'] : 'Non-Member',
            'phic_type' => !empty($data['phic_type']) ? trim($data['phic_type']) : null,
            'philhealth_no' => !empty($data['philhealth_no']) ? trim($data['philhealth_no']) : null,
            'father_name' => !empty($data['father_name']) ? trim($data['father_name']) : null,
            'father_dob' => !empty($data['father_dob']) ? $data['father_dob'] : null,
            'mother_name' => !empty($data['mother_name']) ? trim($data['mother_name']) : null,
            'mother_dob' => !empty($data['mother_dob']) ? $data['mother_dob'] : null,
            'spouse_name' => !empty($data['spouse_name']) ? trim($data['spouse_name']) : null,
            'spouse_dob' => !empty($data['spouse_dob']) ? $data['spouse_dob'] : null,
            'emergency_name' => !empty($data['emergency_name']) ? trim($data['emergency_name']) : null,
            'emergency_relationship' => !empty($data['emergency_relationship']) ? trim($data['emergency_relationship']) : null,
            'emergency_no' => !empty($data['emergency_no']) ? trim($data['emergency_no']) : null,
            'updated_by' => $data['updated_by']
        ]);
    }

    /**
     * Auto-generate a sequential patient number formatted as P-YYYY-XXXXX.
     * e.g. P-2026-00001
     * 
     * @return string Generated patient number
     */
    protected function generatePatientNo() {
        $year = date('Y');
        
        // Find the last patient_no created in this year
        $stmt = $this->db->prepare("SELECT patient_no FROM patients WHERE patient_no LIKE :prefix ORDER BY id DESC LIMIT 1");
        $stmt->execute(['prefix' => "P-{$year}-%"]);
        $lastPatientNo = $stmt->fetchColumn();

        if ($lastPatientNo) {
            $parts = explode('-', $lastPatientNo);
            $seq = (int)$parts[2] + 1;
        } else {
            $seq = 1;
        }

        return "P-{$year}-" . str_pad($seq, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Check if a PhilHealth number is unique, ignoring a specific patient (for edits).
     * 
     * @param string $philhealthNo
     * @param int|null $excludeId Patient ID to exclude
     * @return bool
     */
    public function isPhilHealthUnique($philhealthNo, $excludeId = null) {
        if (empty($philhealthNo)) return true;
        
        $sql = "SELECT COUNT(*) FROM patients WHERE philhealth_no = :philhealth_no AND deleted_at IS NULL";
        $params = ['philhealth_no' => $philhealthNo];
        
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() == 0;
    }

    /**
     * Get all archived (soft-deleted) patients.
     * 
     * @param array $filters Filter terms (search, date_from, date_to)
     * @return array Archived patient records
     */
    public function allArchived($filters = []) {
        $sql = "SELECT p.*, TIMESTAMPDIFF(YEAR, p.dob, CURRENT_DATE()) AS age,
                       CONCAT(u.first_name, ' ', u.last_name) AS archiver_name
                FROM patients p
                LEFT JOIN users u ON p.deleted_by = u.id
                WHERE p.deleted_at IS NOT NULL";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (p.first_name LIKE :search_first 
                        OR p.last_name LIKE :search_last 
                        OR p.patient_no LIKE :search_no)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params['search_first'] = $searchTerm;
            $params['search_last'] = $searchTerm;
            $params['search_no'] = $searchTerm;
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(p.deleted_at) >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(p.deleted_at) <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }

        $sql .= " ORDER BY p.deleted_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Archive (soft-delete) a patient.
     * 
     * @param int $id
     * @param int $userId
     * @param string $reason
     * @return bool
     */
    public function archive($id, $userId, $reason) {
        $sql = "UPDATE patients SET 
                    deleted_at = CURRENT_TIMESTAMP, 
                    deleted_by = :deleted_by, 
                    archive_reason = :archive_reason 
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'deleted_by' => $userId,
            'archive_reason' => trim($reason)
        ]);
    }

    /**
     * Restore an archived patient.
     * 
     * @param int $id
     * @return bool
     */
    public function restore($id) {
        $sql = "UPDATE patients SET 
                    deleted_at = NULL, 
                    deleted_by = NULL, 
                    archive_reason = NULL 
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Get the IHP medical history for this patient.
     * 
     * @param int $patientId
     * @return array|false
     */
    public function medicalHistory($patientId) {
        $model = new PatientMedicalHistory();
        return $model->findByPatientId($patientId);
    }

    /**
     * Get the active prenatal record for this patient if any.
     * 
     * @param int $patientId
     * @return array|false
     */
    public function activePrenatalRecord($patientId) {
        $model = new PrenatalRecord();
        return $model->findActiveByPatientId($patientId);
    }

    /**
     * Get all prenatal records for this patient.
     * 
     * @param int $patientId
     * @return array
     */
    public function allPrenatalRecords($patientId) {
        $model = new PrenatalRecord();
        return $model->findAllByPatientId($patientId);
    }

    /**
     * Get past obstetric histories for this patient.
     * 
     * @param int $patientId
     * @return array
     */
    public function pastObstetricHistories($patientId) {
        $model = new PastObstetricHistory();
        return $model->findByPatientId($patientId);
    }

    /**
     * Get the Well Baby record for this child patient.
     * 
     * @param int $patientId
     * @return array|false
     */
    public function wellbabyRecord($patientId) {
        $model = new WellbabyRecord();
        return $model->findByPatientId($patientId);
    }

    /**
     * Find other household family members sharing the same family number.
     * 
     * @param string $familyNo
     * @param int|null $excludePatientId
     * @return array
     */
    public function familyMembers($familyNo, $excludePatientId = null) {
        if (empty($familyNo)) return [];

        $sql = "SELECT id, patient_no, family_no, first_name, middle_name, last_name, suffix, dob, sex, civil_status,
                       TIMESTAMPDIFF(YEAR, dob, CURRENT_DATE()) AS age
                FROM patients 
                WHERE family_no = :family_no AND deleted_at IS NULL";
        
        $params = ['family_no' => trim($familyNo)];

        if ($excludePatientId !== null) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludePatientId;
        }

        $sql .= " ORDER BY dob ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Determine visual program badge classification for a patient.
     * 
     * @param int $patientId
     * @param string $dob
     * @param string $sex
     * @return array ['tag' => string, 'class' => string, 'label' => string]
     */
    public function getProgramBadge($patientId, $dob, $sex) {
        // Calculate age in years
        $age = 0;
        if (!empty($dob)) {
            $birthDate = new \DateTime($dob);
            $today = new \DateTime();
            $age = $today->diff($birthDate)->y;
        }

        // Check if infant / well baby (0-5 years)
        if ($age <= 5) {
            return [
                'tag' => 'wellbaby',
                'class' => 'bg-success text-white',
                'label' => 'Well Baby'
            ];
        }

        // Check active pregnancy for reproductive females
        if (strtolower($sex) === 'female') {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM prenatal_records WHERE patient_id = :id AND is_active = 1");
            $stmt->execute(['id' => $patientId]);
            if ($stmt->fetchColumn() > 0) {
                return [
                    'tag' => 'prenatal',
                    'class' => 'bg-pink text-white',
                    'label' => 'Prenatal'
                ];
            }
        }

        // Check senior citizen (60+)
        if ($age >= 60) {
            return [
                'tag' => 'senior',
                'class' => 'bg-purple text-white',
                'label' => 'Senior'
            ];
        }

        // Default General OPD
        return [
            'tag' => 'opd',
            'class' => 'bg-primary text-white',
            'label' => 'General OPD'
        ];
    }

    /**
     * Get active female patients who can be linked as mothers.
     * 
     * @param int $limit
     * @return array
     */
    public function findPotentialMothers($limit = 100) {
        $sql = "SELECT id, patient_no, first_name, last_name, dob, family_no 
                FROM patients 
                WHERE LOWER(sex) = 'female' AND deleted_at IS NULL 
                ORDER BY last_name ASC, first_name ASC 
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

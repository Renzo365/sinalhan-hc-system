<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Patient extends Model {
    /**
     * Get all active (non-archived) patients based on filters.
     * 
     * @param array $filters Filter terms (search, barangay, sex, age_group)
     * @return array List of matching patient records
     */
    public function allActive($filters = []) {
        $sql = "SELECT *, TIMESTAMPDIFF(YEAR, dob, CURRENT_DATE()) AS age 
                FROM patients 
                WHERE deleted_at IS NULL";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (first_name LIKE :search_first 
                        OR last_name LIKE :search_last 
                        OR patient_no LIKE :search_no 
                        OR contact_no LIKE :search_contact 
                        OR barangay LIKE :search_barangay)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params['search_first'] = $searchTerm;
            $params['search_last'] = $searchTerm;
            $params['search_no'] = $searchTerm;
            $params['search_contact'] = $searchTerm;
            $params['search_barangay'] = $searchTerm;
        }

        // Filter by Barangay
        if (!empty($filters['barangay'])) {
            $sql .= " AND barangay = :barangay";
            $params['barangay'] = $filters['barangay'];
        }

        // Filter by Sex
        if (!empty($filters['sex'])) {
            $sql .= " AND sex = :sex";
            $params['sex'] = $filters['sex'];
        }

        // Filter by Age Group
        if (!empty($filters['age_group'])) {
            switch ($filters['age_group']) {
                case 'child':
                    $sql .= " AND TIMESTAMPDIFF(YEAR, dob, CURRENT_DATE()) BETWEEN 0 AND 12";
                    break;
                case 'teen':
                    $sql .= " AND TIMESTAMPDIFF(YEAR, dob, CURRENT_DATE()) BETWEEN 13 AND 19";
                    break;
                case 'adult':
                    $sql .= " AND TIMESTAMPDIFF(YEAR, dob, CURRENT_DATE()) BETWEEN 20 AND 59";
                    break;
                case 'senior':
                    $sql .= " AND TIMESTAMPDIFF(YEAR, dob, CURRENT_DATE()) >= 60";
                    break;
            }
        }

        $sql .= " ORDER BY last_name ASC, first_name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
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
                    patient_no, first_name, middle_name, last_name, dob, sex, 
                    civil_status, contact_no, barangay, address, 
                    emergency_name, emergency_no, philhealth_no, created_by
                ) VALUES (
                    :patient_no, :first_name, :middle_name, :last_name, :dob, :sex, 
                    :civil_status, :contact_no, :barangay, :address, 
                    :emergency_name, :emergency_no, :philhealth_no, :created_by
                )";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            'patient_no' => $data['patient_no'],
            'first_name' => trim($data['first_name']),
            'middle_name' => !empty($data['middle_name']) ? trim($data['middle_name']) : null,
            'last_name' => trim($data['last_name']),
            'dob' => $data['dob'],
            'sex' => $data['sex'],
            'civil_status' => $data['civil_status'],
            'contact_no' => !empty($data['contact_no']) ? trim($data['contact_no']) : null,
            'barangay' => !empty($data['barangay']) ? trim($data['barangay']) : 'Sinalhan',
            'address' => trim($data['address']),
            'emergency_name' => !empty($data['emergency_name']) ? trim($data['emergency_name']) : null,
            'emergency_no' => !empty($data['emergency_no']) ? trim($data['emergency_no']) : null,
            'philhealth_no' => !empty($data['philhealth_no']) ? trim($data['philhealth_no']) : null,
            'created_by' => $data['created_by']
        ]);

        return $result ? $this->db->lastInsertId() : false;
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
                    first_name = :first_name,
                    middle_name = :middle_name,
                    last_name = :last_name,
                    dob = :dob,
                    sex = :sex,
                    civil_status = :civil_status,
                    contact_no = :contact_no,
                    barangay = :barangay,
                    address = :address,
                    emergency_name = :emergency_name,
                    emergency_no = :emergency_no,
                    philhealth_no = :philhealth_no,
                    updated_by = :updated_by,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND deleted_at IS NULL";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'first_name' => trim($data['first_name']),
            'middle_name' => !empty($data['middle_name']) ? trim($data['middle_name']) : null,
            'last_name' => trim($data['last_name']),
            'dob' => $data['dob'],
            'sex' => $data['sex'],
            'civil_status' => $data['civil_status'],
            'contact_no' => !empty($data['contact_no']) ? trim($data['contact_no']) : null,
            'barangay' => !empty($data['barangay']) ? trim($data['barangay']) : 'Sinalhan',
            'address' => trim($data['address']),
            'emergency_name' => !empty($data['emergency_name']) ? trim($data['emergency_name']) : null,
            'emergency_no' => !empty($data['emergency_no']) ? trim($data['emergency_no']) : null,
            'philhealth_no' => !empty($data['philhealth_no']) ? trim($data['philhealth_no']) : null,
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
}

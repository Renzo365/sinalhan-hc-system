<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Appointment extends Model {
    /**
     * Retrieve all active appointments with filters.
     * 
     * @param array $filters Filter parameters
     * @return array List of appointments
     */
    public function findAll($filters = []) {
        $sql = "SELECT a.*, 
                       p.patient_no, p.first_name AS patient_first, p.last_name AS patient_last, p.middle_name AS patient_middle
                FROM appointments a
                JOIN patients p ON a.patient_id = p.id
                WHERE p.deleted_at IS NULL";
        
        $params = [];
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND a.appointment_date >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND a.appointment_date <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND a.status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (p.first_name LIKE :search_first 
                           OR p.last_name LIKE :search_last 
                           OR p.patient_no LIKE :search_no)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params['search_first'] = $searchTerm;
            $params['search_last'] = $searchTerm;
            $params['search_no'] = $searchTerm;
        }
        
        $sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get appointments for a specific patient.
     * 
     * @param int $patientId
     * @return array History of appointments
     */
    public function findByPatientId($patientId) {
        $sql = "SELECT * FROM appointments 
                WHERE patient_id = :patient_id 
                ORDER BY appointment_date DESC, appointment_time DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['patient_id' => $patientId]);
        return $stmt->fetchAll();
    }

    /**
     * Find a single appointment by ID.
     * 
     * @param int $id
     * @return array|false Appointment details, or false if not found
     */
    public function findById($id) {
        $sql = "SELECT a.*, 
                       p.patient_no, p.first_name AS patient_first, p.last_name AS patient_last, p.middle_name AS patient_middle
                FROM appointments a
                JOIN patients p ON a.patient_id = p.id
                WHERE a.id = :id AND p.deleted_at IS NULL
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Store a new appointment.
     * 
     * @param array $data Appointment details
     * @return int|false New ID, or false on failure
     */
    public function create($data) {
        $sql = "INSERT INTO appointments (
                    patient_id, appointment_date, appointment_time, 
                    purpose, status, notes, created_by
                ) VALUES (
                    :patient_id, :appointment_date, :appointment_time, 
                    :purpose, :status, :notes, :created_by
                )";
        
        $stmt = $this->db->prepare($sql);
        
        $result = $stmt->execute([
            'patient_id' => (int)$data['patient_id'],
            'appointment_date' => $data['appointment_date'],
            'appointment_time' => $data['appointment_time'],
            'purpose' => trim($data['purpose']),
            'status' => !empty($data['status']) ? $data['status'] : 'Scheduled',
            'notes' => !empty($data['notes']) ? trim($data['notes']) : null,
            'created_by' => (int)$data['created_by']
        ]);

        return $result ? $this->db->lastInsertId() : false;
    }

    /**
     * Update an appointment record.
     * 
     * @param int $id
     * @param array $data Updated fields
     * @return bool True on success, false on failure
     */
    public function update($id, $data) {
        $sql = "UPDATE appointments SET 
                    appointment_date = :appointment_date,
                    appointment_time = :appointment_time,
                    purpose = :purpose,
                    status = :status,
                    notes = :notes,
                    updated_by = :updated_by
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            'id' => (int)$id,
            'appointment_date' => $data['appointment_date'],
            'appointment_time' => $data['appointment_time'],
            'purpose' => trim($data['purpose']),
            'status' => $data['status'],
            'notes' => !empty($data['notes']) ? trim($data['notes']) : null,
            'updated_by' => (int)$data['updated_by']
        ]);
    }

    /**
     * Update only the status of an appointment.
     * 
     * @param int $id
     * @param string $status New status
     * @param int $userId Performing user
     * @return bool
     */
    public function updateStatus($id, $status, $userId) {
        $sql = "UPDATE appointments SET 
                    status = :status,
                    updated_by = :updated_by
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            'id' => (int)$id,
            'status' => $status,
            'updated_by' => (int)$userId
        ]);
    }

    /**
     * Verify if there is a scheduling conflict.
     * 
     * @param string $date Date string
     * @param string $time Time string
     * @param int|null $excludeId Exclude appointment ID (for edits)
     * @return bool True if conflict exists, false otherwise
     */
    public function hasConflict($date, $time, $excludeId = null) {
        // Format time to HH:MM:00 for strict match
        $formattedTime = date('H:i:00', strtotime($time));

        $sql = "SELECT COUNT(*) FROM appointments 
                WHERE appointment_date = :date 
                  AND appointment_time = :time
                  AND status = 'Scheduled'";
        
        $params = ['date' => $date, 'time' => $formattedTime];
        
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = (int)$excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Get today's scheduled appointments.
     * 
     * @return array
     */
    public function getTodayAppointments() {
        $sql = "SELECT a.*, 
                       p.patient_no, p.first_name AS patient_first, p.last_name AS patient_last, p.middle_name AS patient_middle
                FROM appointments a
                JOIN patients p ON a.patient_id = p.id
                WHERE a.appointment_date = CURRENT_DATE() 
                  AND p.deleted_at IS NULL
                ORDER BY a.appointment_time ASC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll() ?: [];
    }
}

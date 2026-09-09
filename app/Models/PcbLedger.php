<?php

namespace App\Models;

use App\Core\Model;

class PcbLedger extends Model {
    /**
     * Get the annual obligated services record for a patient.
     * 
     * @param int $patientId
     * @param int|null $year Defaults to current calendar year
     * @return array|null
     */
    public function getObligatedServices($patientId, $year = null) {
        $year = $year ? (int)$year : (int)date('Y');
        
        $sql = "SELECT pos.*,
                       CONCAT(u.first_name, ' ', u.last_name) AS updater_name
                FROM pcb_obligated_services pos
                LEFT JOIN users u ON pos.updated_by = u.id
                WHERE pos.patient_id = :patient_id AND pos.service_year = :service_year
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'patient_id' => $patientId,
            'service_year' => $year
        ]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Save or update the annual obligated services for a patient.
     * 
     * @param int $patientId
     * @param array $data
     * @return bool
     */
    public function saveObligatedServices($patientId, $data) {
        $year = !empty($data['service_year']) ? (int)$data['service_year'] : (int)date('Y');
        $existing = $this->getObligatedServices($patientId, $year);

        $params = [
            'patient_id' => $patientId,
            'service_year' => $year,
            'is_hypertensive' => !empty($data['is_hypertensive']) ? 1 : 0,
            'bp_q1' => !empty($data['bp_q1']) ? $data['bp_q1'] : null,
            'bp_q2' => !empty($data['bp_q2']) ? $data['bp_q2'] : null,
            'bp_q3' => !empty($data['bp_q3']) ? $data['bp_q3'] : null,
            'bp_q4' => !empty($data['bp_q4']) ? $data['bp_q4'] : null,
            'cbe_q1' => !empty($data['cbe_q1']) ? $data['cbe_q1'] : null,
            'cbe_q2' => !empty($data['cbe_q2']) ? $data['cbe_q2'] : null,
            'cbe_q3' => !empty($data['cbe_q3']) ? $data['cbe_q3'] : null,
            'cbe_q4' => !empty($data['cbe_q4']) ? $data['cbe_q4'] : null,
            'via_q1' => !empty($data['via_q1']) ? $data['via_q1'] : null,
            'via_q2' => !empty($data['via_q2']) ? $data['via_q2'] : null,
            'via_q3' => !empty($data['via_q3']) ? $data['via_q3'] : null,
            'via_q4' => !empty($data['via_q4']) ? $data['via_q4'] : null,
            'remarks' => !empty($data['remarks']) ? trim($data['remarks']) : null,
            'updated_by' => !empty($data['updated_by']) ? (int)$data['updated_by'] : null
        ];

        if ($existing) {
            $sql = "UPDATE pcb_obligated_services SET
                        is_hypertensive = :is_hypertensive,
                        bp_q1 = :bp_q1, bp_q2 = :bp_q2, bp_q3 = :bp_q3, bp_q4 = :bp_q4,
                        cbe_q1 = :cbe_q1, cbe_q2 = :cbe_q2, cbe_q3 = :cbe_q3, cbe_q4 = :cbe_q4,
                        via_q1 = :via_q1, via_q2 = :via_q2, via_q3 = :via_q3, via_q4 = :via_q4,
                        remarks = :remarks,
                        updated_by = :updated_by,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id";
            $params['id'] = $existing['id'];
            unset($params['patient_id'], $params['service_year']);
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } else {
            $sql = "INSERT INTO pcb_obligated_services 
                    (patient_id, service_year, is_hypertensive,
                     bp_q1, bp_q2, bp_q3, bp_q4,
                     cbe_q1, cbe_q2, cbe_q3, cbe_q4,
                     via_q1, via_q2, via_q3, via_q4,
                     remarks, updated_by)
                    VALUES 
                    (:patient_id, :service_year, :is_hypertensive,
                     :bp_q1, :bp_q2, :bp_q3, :bp_q4,
                     :cbe_q1, :cbe_q2, :cbe_q3, :cbe_q4,
                     :via_q1, :via_q2, :via_q3, :via_q4,
                     :remarks, :updated_by)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        }
    }

    /**
     * Get diagnostic and PCB service encounter logs for a patient.
     * 
     * @param int $patientId
     * @param string|null $category 'Diagnostic', 'PCB1', or 'Other'
     * @return array
     */
    public function getServiceLogs($patientId, $category = null) {
        $sql = "SELECT psl.*,
                       CONCAT(u.first_name, ' ', u.last_name) AS recorder_name
                FROM pcb_service_logs psl
                LEFT JOIN users u ON psl.recorded_by = u.id
                WHERE psl.patient_id = :patient_id";
        
        $params = ['patient_id' => $patientId];
        
        if (!empty($category)) {
            $sql .= " AND psl.service_category = :service_category";
            $params['service_category'] = $category;
        }
        
        $sql .= " ORDER BY psl.service_date DESC, psl.id DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Create a new diagnostic or PCB service encounter log.
     * 
     * @param array $data
     * @return int|false Last inserted ID or false on failure
     */
    public function createServiceLog($data) {
        $sql = "INSERT INTO pcb_service_logs 
                (patient_id, service_category, service_date, diagnosis, service_type,
                 status_given, status_referred, referred_to, remarks, recorded_by)
                VALUES 
                (:patient_id, :service_category, :service_date, :diagnosis, :service_type,
                 :status_given, :status_referred, :referred_to, :remarks, :recorded_by)";
        
        $params = [
            'patient_id' => (int)$data['patient_id'],
            'service_category' => in_array($data['service_category'] ?? '', ['Diagnostic', 'PCB1', 'Other'], true) ? $data['service_category'] : 'Diagnostic',
            'service_date' => !empty($data['service_date']) ? $data['service_date'] : date('Y-m-d'),
            'diagnosis' => !empty($data['diagnosis']) ? trim($data['diagnosis']) : null,
            'service_type' => trim($data['service_type'] ?? 'General PCB Service'),
            'status_given' => !empty($data['status_given']) ? 1 : 0,
            'status_referred' => !empty($data['status_referred']) ? 1 : 0,
            'referred_to' => !empty($data['referred_to']) ? trim($data['referred_to']) : null,
            'remarks' => !empty($data['remarks']) ? trim($data['remarks']) : null,
            'recorded_by' => (int)($data['recorded_by'] ?? 1)
        ];

        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($params)) {
            return (int)$this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Find single service log by ID.
     * 
     * @param int $id
     * @return array|null
     */
    public function findLogById($id) {
        $sql = "SELECT * FROM pcb_service_logs WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Delete a service log entry.
     * 
     * @param int $id
     * @param int|null $patientId
     * @return bool
     */
    public function deleteServiceLog($id, $patientId = null) {
        $sql = "DELETE FROM pcb_service_logs WHERE id = :id";
        $params = ['id' => $id];
        if ($patientId !== null) {
            $sql .= " AND patient_id = :patient_id";
            $params['patient_id'] = $patientId;
        }
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
}
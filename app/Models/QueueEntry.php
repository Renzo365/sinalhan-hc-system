<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class QueueEntry extends Model {
    /**
     * Retrieve all queue entries for today, joining patient details.
     * 
     * @return array
     */
    public function findAllToday() {
        $sql = "SELECT q.*, 
                       p.patient_no, p.first_name AS patient_first, p.last_name AS patient_last, p.middle_name AS patient_middle
                FROM queue_entries q
                JOIN patients p ON q.patient_id = p.id
                WHERE q.queue_date = CURRENT_DATE() 
                  AND p.deleted_at IS NULL
                ORDER BY q.queue_no ASC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Retrieve queue log history for a specific patient.
     * 
     * @param int $patientId
     * @return array
     */
    public function findByPatientId($patientId) {
        $sql = "SELECT * FROM queue_entries 
                WHERE patient_id = :patient_id 
                ORDER BY queue_date DESC, queue_no DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['patient_id' => $patientId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Find a single queue entry by ID.
     * 
     * @param int $id
     * @return array|false
     */
    public function findById($id) {
        $sql = "SELECT q.*, 
                       p.patient_no, p.first_name AS patient_first, p.last_name AS patient_last, p.middle_name AS patient_middle
                FROM queue_entries q
                JOIN patients p ON q.patient_id = p.id
                WHERE q.id = :id AND p.deleted_at IS NULL
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Check if the patient is already queued active today.
     * 
     * @param int $patientId
     * @return bool
     */
    public function isPatientQueuedToday($patientId) {
        $sql = "SELECT COUNT(*) FROM queue_entries 
                WHERE patient_id = :patient_id 
                  AND queue_date = CURRENT_DATE() 
                  AND status IN ('Waiting', 'Called', 'Serving')";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['patient_id' => $patientId]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Auto-generate the next queue number for today.
     * 
     * @param string $date Today's date
     * @return int
     */
    public function getNextQueueNo($date) {
        $sql = "SELECT COALESCE(MAX(queue_no), 0) + 1 FROM queue_entries WHERE queue_date = :date";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['date' => $date]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Create a new queue entry.
     * 
     * @param array $data Queue data
     * @return int|false
     */
    public function create($data) {
        $date = date('Y-m-d');
        $queueNo = $this->getNextQueueNo($date);
        
        $sql = "INSERT INTO queue_entries (
                    patient_id, queue_date, queue_no, 
                    status, time_in, created_by
                ) VALUES (
                    :patient_id, :queue_date, :queue_no, 
                    'Waiting', CURRENT_TIME(), :created_by
                )";
        
        $stmt = $this->db->prepare($sql);
        
        $result = $stmt->execute([
            'patient_id' => (int)$data['patient_id'],
            'queue_date' => $date,
            'queue_no' => $queueNo,
            'created_by' => (int)$data['created_by']
        ]);

        return $result ? $this->db->lastInsertId() : false;
    }

    /**
     * Transition the status of a queue entry.
     * 
     * @param int $id
     * @param string $status New status
     * @param int $userId Performing user
     * @return bool
     */
    public function updateStatus($id, $status, $userId) {
        $fields = "status = :status, updated_by = :updated_by";
        $params = ['id' => $id, 'status' => $status, 'updated_by' => $userId];
        
        if ($status === 'Called') {
            $fields .= ", time_called = CURRENT_TIME()";
        } elseif ($status === 'Completed') {
            $fields .= ", time_completed = CURRENT_TIME()";
        }
        
        $sql = "UPDATE queue_entries SET {$fields} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Get statistics summary of today's queue.
     * 
     * @return array
     */
    public function getTodayStats() {
        $stats = [
            'waiting' => 0,
            'called' => 0,
            'completed' => 0,
            'serving_no' => 0
        ];

        $sqlWaiting = "SELECT COUNT(*) FROM queue_entries WHERE queue_date = CURRENT_DATE() AND status = 'Waiting'";
        $sqlCalled = "SELECT COUNT(*) FROM queue_entries WHERE queue_date = CURRENT_DATE() AND status IN ('Called', 'Serving')";
        $sqlCompleted = "SELECT COUNT(*) FROM queue_entries WHERE queue_date = CURRENT_DATE() AND status = 'Completed'";
        
        // Find the current serving queue number (Serving, or Called if none is serving)
        $sqlServing = "SELECT queue_no FROM queue_entries 
                       WHERE queue_date = CURRENT_DATE() AND status = 'Serving' 
                       LIMIT 1";
        
        $sqlLastCalled = "SELECT queue_no FROM queue_entries 
                          WHERE queue_date = CURRENT_DATE() AND status = 'Called' 
                          ORDER BY time_called DESC LIMIT 1";

        $stats['waiting'] = (int)$this->db->query($sqlWaiting)->fetchColumn();
        $stats['called'] = (int)$this->db->query($sqlCalled)->fetchColumn();
        $stats['completed'] = (int)$this->db->query($sqlCompleted)->fetchColumn();
        
        $servingNo = $this->db->query($sqlServing)->fetchColumn();
        if (!$servingNo) {
            $servingNo = $this->db->query($sqlLastCalled)->fetchColumn();
        }
        $stats['serving_no'] = $servingNo ? (int)$servingNo : 0;

        return $stats;
    }

    /**
     * Get data formatted specifically for the public queue display board.
     * 
     * @return array
     */
    public function getPublicDisplayData() {
        $date = date('Y-m-d');
        
        // Serving: status = 'Serving' or last 'Called'
        $stmtServing = $this->db->query("SELECT queue_no FROM queue_entries WHERE queue_date = '{$date}' AND status = 'Serving' LIMIT 1");
        $serving = $stmtServing->fetchColumn();
        
        if (!$serving) {
            $stmtLastCalled = $this->db->query("SELECT queue_no FROM queue_entries WHERE queue_date = '{$date}' AND status = 'Called' ORDER BY time_called DESC LIMIT 1");
            $serving = $stmtLastCalled->fetchColumn();
        }

        // Waiting list (Waiting status)
        $stmtWaiting = $this->db->query("SELECT queue_no FROM queue_entries WHERE queue_date = '{$date}' AND status = 'Waiting' ORDER BY queue_no ASC");
        $waiting = $stmtWaiting->fetchAll(PDO::FETCH_COLUMN) ?: [];

        // Recently Called (Any status where they have been called, excluding Cancelled)
        $stmtCalled = $this->db->query("SELECT queue_no FROM queue_entries WHERE queue_date = '{$date}' AND time_called IS NOT NULL AND status != 'Cancelled' ORDER BY time_called DESC LIMIT 5");
        $called = $stmtCalled->fetchAll(PDO::FETCH_COLUMN) ?: [];

        return [
            'serving' => $serving ? sprintf('%03d', $serving) : '000',
            'waiting' => array_map(function($no) { return sprintf('%03d', $no); }, $waiting),
            'called' => array_map(function($no) { return sprintf('%03d', $no); }, $called)
        ];
    }
}

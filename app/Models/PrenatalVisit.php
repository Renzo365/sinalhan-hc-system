<?php

namespace App\Models;

use App\Core\Model;

class PrenatalVisit extends Model {
    /**
     * Get all follow-up visits for a prenatal record, ordered chronologically.
     * 
     * @param int $prenatalId
     * @return array
     */
    public function findByPrenatalId($prenatalId) {
        $sql = "SELECT pv.*, 
                       CONCAT(u.first_name, ' ', u.last_name) AS attendant_name,
                       u.role AS attendant_role
                FROM prenatal_visits pv
                LEFT JOIN users u ON pv.attended_by = u.id
                WHERE pv.prenatal_id = :prenatal_id AND pv.deleted_at IS NULL
                ORDER BY pv.visit_date ASC, pv.created_at ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['prenatal_id' => $prenatalId]);
        return $stmt->fetchAll();
    }

    /**
     * Find a single prenatal visit by ID.
     * 
     * @param int $id
     * @return array|false
     */
    public function findById($id) {
        $sql = "SELECT pv.*, 
                       CONCAT(u.first_name, ' ', u.last_name) AS attendant_name
                FROM prenatal_visits pv
                LEFT JOIN users u ON pv.attended_by = u.id
                WHERE pv.id = :id AND pv.deleted_at IS NULL
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Insert a new prenatal visit record.
     * 
     * @param array $data
     * @return int|false Last insert ID
     */
    public function createVisit($data) {
        $sql = "INSERT INTO prenatal_visits (
                    prenatal_id, visit_date, chief_complaint, aog_weeks,
                    bp_systolic, bp_diastolic, weight_kg, height_cm,
                    fetal_heart_tone, fundal_height_cm, fetal_presentation,
                    tcb, remarks, attended_by
                ) VALUES (
                    :prenatal_id, :visit_date, :chief_complaint, :aog_weeks,
                    :bp_systolic, :bp_diastolic, :weight_kg, :height_cm,
                    :fetal_heart_tone, :fundal_height_cm, :fetal_presentation,
                    :tcb, :remarks, :attended_by
                )";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            'prenatal_id' => $data['prenatal_id'],
            'visit_date' => $data['visit_date'],
            'chief_complaint' => !empty($data['chief_complaint']) ? trim($data['chief_complaint']) : null,
            'aog_weeks' => (float)$data['aog_weeks'],
            'bp_systolic' => !empty($data['bp_systolic']) ? (int)$data['bp_systolic'] : null,
            'bp_diastolic' => !empty($data['bp_diastolic']) ? (int)$data['bp_diastolic'] : null,
            'weight_kg' => !empty($data['weight_kg']) ? (float)$data['weight_kg'] : null,
            'height_cm' => !empty($data['height_cm']) ? (float)$data['height_cm'] : null,
            'fetal_heart_tone' => !empty($data['fetal_heart_tone']) ? (int)$data['fetal_heart_tone'] : null,
            'fundal_height_cm' => !empty($data['fundal_height_cm']) ? (float)$data['fundal_height_cm'] : null,
            'fetal_presentation' => $data['fetal_presentation'] ?? 'Cephalic',
            'tcb' => !empty($data['tcb']) ? trim($data['tcb']) : null,
            'remarks' => !empty($data['remarks']) ? trim($data['remarks']) : null,
            'attended_by' => $data['attended_by']
        ]);

        return $result ? (int)$this->db->lastInsertId() : false;
    }

    /**
     * Soft delete a prenatal visit entry.
     * 
     * @param int $id
     * @param int|null $userId
     * @param string|null $reason
     * @return bool
     */
    public function deleteVisit($id, $userId = null, $reason = null) {
        $stmt = $this->db->prepare("
            UPDATE prenatal_visits 
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
     * Count the number of active visits logged for a specific prenatal record.
     *
     * @param int $prenatalId
     * @return int
     */
    public function countByPrenatalId($prenatalId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM prenatal_visits 
            WHERE prenatal_id = :prenatal_id AND deleted_at IS NULL
        ");
        $stmt->execute(['prenatal_id' => (int)$prenatalId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Update an existing prenatal visit record (e.g. adding clinical remarks, FHT, or updating vitals).
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateVisit($id, $data) {
        $fields = [];
        $params = ['id' => (int)$id];

        $allowed = [
            'visit_date', 'chief_complaint', 'aog_weeks',
            'bp_systolic', 'bp_diastolic', 'weight_kg', 'height_cm',
            'fetal_heart_tone', 'fundal_height_cm', 'fetal_presentation',
            'tcb', 'remarks'
        ];

        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "{$col} = :{$col}";
                $params[$col] = $data[$col];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE prenatal_visits SET " . implode(', ', $fields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = :id AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
}

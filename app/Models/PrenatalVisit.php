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
                WHERE pv.prenatal_id = :prenatal_id
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
                WHERE pv.id = :id
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
     * Delete a prenatal visit entry.
     * 
     * @param int $id
     * @return bool
     */
    public function deleteVisit($id) {
        $stmt = $this->db->prepare("DELETE FROM prenatal_visits WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}

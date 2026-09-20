<?php

namespace App\Models;

use App\Core\Model;

class ChildGrowthLog extends Model {
    private function normalizeFeedingMethod($feedingMethod) {
        $aliases = [
            'Bottle Feeding (Formula)' => 'Bottle Feed',
            'Formula Feeding' => 'Bottle Feed',
            'Mixed Feeding' => 'Mixed',
            'Complementary Feeding' => 'Mixed',
        ];
        $feedingMethod = $aliases[$feedingMethod] ?? $feedingMethod;
        return in_array($feedingMethod, ['LAM / Exclusive Breastfeeding', 'Bottle Feed', 'Mixed'], true)
            ? $feedingMethod
            : 'LAM / Exclusive Breastfeeding';
    }

    /**
     * Get all growth monitoring logs for a Well Baby record, ordered chronologically.
     * 
     * @param int $wellbabyId
     * @return array
     */
    public function findByWellbabyId($wellbabyId) {
        $sql = "SELECT cgl.*, 
                       CONCAT(u.first_name, ' ', u.last_name) AS recorder_name
                FROM child_growth_logs cgl
                LEFT JOIN users u ON cgl.recorded_by = u.id
                WHERE cgl.wellbaby_id = :wellbaby_id AND cgl.deleted_at IS NULL
                ORDER BY cgl.log_date ASC, cgl.created_at ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['wellbaby_id' => $wellbabyId]);
        return $stmt->fetchAll();
    }

    /**
     * Find a single growth log by its ID.
     * 
     * @param int $id
     * @return array|false
     */
    public function findById($id) {
        $sql = "SELECT cgl.*, 
                       CONCAT(u.first_name, ' ', u.last_name) AS recorder_name
                FROM child_growth_logs cgl
                LEFT JOIN users u ON cgl.recorded_by = u.id
                WHERE cgl.id = :id AND cgl.deleted_at IS NULL
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Insert a new growth monitoring entry.
     * 
     * @param array $data
     * @return int|false Last insert ID
     */
    public function createLog($data) {
        $sql = "INSERT INTO child_growth_logs (
                    wellbaby_id, log_date, age_months, weight_kg, height_cm,
                    head_circumference_cm, chest_circumference_cm, temperature,
                    feeding_method, vaccines_administered, vitamin_a_dose,
                    deworming_dose, tcb_notes, recorded_by
                ) VALUES (
                    :wellbaby_id, :log_date, :age_months, :weight_kg, :height_cm,
                    :head_circumference_cm, :chest_circumference_cm, :temperature,
                    :feeding_method, :vaccines_administered, :vitamin_a_dose,
                    :deworming_dose, :tcb_notes, :recorded_by
                )";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            'wellbaby_id' => $data['wellbaby_id'],
            'log_date' => $data['log_date'],
            'age_months' => (float)$data['age_months'],
            'weight_kg' => (float)$data['weight_kg'],
            'height_cm' => (float)$data['height_cm'],
            'head_circumference_cm' => !empty($data['head_circumference_cm']) ? (float)$data['head_circumference_cm'] : null,
            'chest_circumference_cm' => !empty($data['chest_circumference_cm']) ? (float)$data['chest_circumference_cm'] : null,
            'temperature' => !empty($data['temperature']) ? (float)$data['temperature'] : null,
            'feeding_method' => $this->normalizeFeedingMethod($data['feeding_method'] ?? ''),
            'vaccines_administered' => !empty($data['vaccines_administered']) ? trim($data['vaccines_administered']) : null,
            'vitamin_a_dose' => !empty($data['vitamin_a_dose']) ? 1 : 0,
            'deworming_dose' => !empty($data['deworming_dose']) ? 1 : 0,
            'tcb_notes' => !empty($data['tcb_notes']) ? trim($data['tcb_notes']) : null,
            'recorded_by' => $data['recorded_by']
        ]);

        return $result ? (int)$this->db->lastInsertId() : false;
    }

    /**
     * Soft delete a growth log entry.
     * 
     * @param int $id
     * @param int|null $userId
     * @param string|null $reason
     * @return bool
     */
    /**
     * Soft delete a growth log entry.
     * 
     * @param int $id
     * @param int|null $userId
     * @param string|null $reason
     * @return bool
     */
    public function deleteLog($id, $userId = null, $reason = null) {
        $stmt = $this->db->prepare("
            UPDATE child_growth_logs 
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
     * Update an existing growth log entry.
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateLog($id, $data) {
        $sql = "UPDATE child_growth_logs SET
                    log_date = :log_date,
                    age_months = :age_months,
                    weight_kg = :weight_kg,
                    height_cm = :height_cm,
                    head_circumference_cm = :head_circumference_cm,
                    chest_circumference_cm = :chest_circumference_cm,
                    temperature = :temperature,
                    feeding_method = :feeding_method,
                    vitamin_a_dose = :vitamin_a_dose,
                    deworming_dose = :deworming_dose,
                    tcb_notes = :tcb_notes
                WHERE id = :id AND deleted_at IS NULL";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => (int)$id,
            'log_date' => $data['log_date'],
            'age_months' => (float)$data['age_months'],
            'weight_kg' => (float)$data['weight_kg'],
            'height_cm' => (float)$data['height_cm'],
            'head_circumference_cm' => !empty($data['head_circumference_cm']) ? (float)$data['head_circumference_cm'] : null,
            'chest_circumference_cm' => !empty($data['chest_circumference_cm']) ? (float)$data['chest_circumference_cm'] : null,
            'temperature' => !empty($data['temperature']) ? (float)$data['temperature'] : null,
            'feeding_method' => $this->normalizeFeedingMethod($data['feeding_method'] ?? ''),
            'vitamin_a_dose' => !empty($data['vitamin_a_dose']) ? 1 : 0,
            'deworming_dose' => !empty($data['deworming_dose']) ? 1 : 0,
            'tcb_notes' => !empty($data['tcb_notes']) ? trim($data['tcb_notes']) : null
        ]);
    }
}

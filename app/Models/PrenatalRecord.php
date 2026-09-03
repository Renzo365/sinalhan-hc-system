<?php

namespace App\Models;

use App\Core\Model;
use DateTime;

class PrenatalRecord extends Model {
    /**
     * Find the currently active pregnancy record for a patient.
     * 
     * @param int $patientId
     * @return array|false
     */
    public function findActiveByPatientId($patientId) {
        $sql = "SELECT pr.*, 
                       CONCAT(u.first_name, ' ', u.last_name) AS creator_name
                FROM prenatal_records pr
                LEFT JOIN users u ON pr.created_by = u.id
                WHERE pr.patient_id = :patient_id AND pr.is_active = 1
                ORDER BY pr.created_at DESC
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['patient_id' => $patientId]);
        $record = $stmt->fetch();

        if ($record && !empty($record['lmp'])) {
            $record['calculated_aog'] = $this->calculateCurrentAOG($record['lmp']);
        }

        return $record;
    }

    /**
     * Find all pregnancy episodes (active and past) for a patient.
     * 
     * @param int $patientId
     * @return array
     */
    public function findAllByPatientId($patientId) {
        $sql = "SELECT pr.*, 
                       CONCAT(u.first_name, ' ', u.last_name) AS creator_name,
                       (SELECT COUNT(*) FROM prenatal_visits pv WHERE pv.prenatal_id = pr.id) AS visit_count
                FROM prenatal_records pr
                LEFT JOIN users u ON pr.created_by = u.id
                WHERE pr.patient_id = :patient_id
                ORDER BY pr.is_active DESC, pr.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['patient_id' => $patientId]);
        return $stmt->fetchAll();
    }

    /**
     * Find a prenatal record episode by its primary key ID.
     * 
     * @param int $id
     * @return array|false
     */
    public function findById($id) {
        $sql = "SELECT pr.*, 
                       p.first_name, p.last_name, p.patient_no, p.dob, p.contact_no, p.barangay,
                       TIMESTAMPDIFF(YEAR, p.dob, CURRENT_DATE()) AS patient_age,
                       CONCAT(u.first_name, ' ', u.last_name) AS creator_name
                FROM prenatal_records pr
                INNER JOIN patients p ON pr.patient_id = p.id
                LEFT JOIN users u ON pr.created_by = u.id
                WHERE pr.id = :id
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $record = $stmt->fetch();

        if ($record && !empty($record['lmp'])) {
            $record['calculated_aog'] = $this->calculateCurrentAOG($record['lmp']);
        }

        return $record;
    }

    /**
     * Create a new pregnancy episode record.
     * 
     * @param array $data
     * @return int|false Last insert ID
     */
    public function createEpisode($data) {
        // Compute EDC if not provided using Naegele's rule
        $edc = !empty($data['edc']) ? $data['edc'] : $this->calculateEDC($data['lmp']);

        $sql = "INSERT INTO prenatal_records (
                    patient_id, husband_name, gravida, para, term_births,
                    preterm_births, abortions, living_children, lmp, edc,
                    is_active, pre_eclampsia, fp_counselling, delivery_date,
                    delivery_outcome, notes, created_by
                ) VALUES (
                    :patient_id, :husband_name, :gravida, :para, :term_births,
                    :preterm_births, :abortions, :living_children, :lmp, :edc,
                    :is_active, :pre_eclampsia, :fp_counselling, :delivery_date,
                    :delivery_outcome, :notes, :created_by
                )";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            'patient_id' => $data['patient_id'],
            'husband_name' => !empty($data['husband_name']) ? trim($data['husband_name']) : null,
            'gravida' => isset($data['gravida']) ? (int)$data['gravida'] : 1,
            'para' => isset($data['para']) ? (int)$data['para'] : 0,
            'term_births' => isset($data['term_births']) ? (int)$data['term_births'] : 0,
            'preterm_births' => isset($data['preterm_births']) ? (int)$data['preterm_births'] : 0,
            'abortions' => isset($data['abortions']) ? (int)$data['abortions'] : 0,
            'living_children' => isset($data['living_children']) ? (int)$data['living_children'] : 0,
            'lmp' => $data['lmp'],
            'edc' => $edc,
            'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1,
            'pre_eclampsia' => !empty($data['pre_eclampsia']) ? 1 : 0,
            'fp_counselling' => isset($data['fp_counselling']) ? (int)$data['fp_counselling'] : 1,
            'delivery_date' => !empty($data['delivery_date']) ? $data['delivery_date'] : null,
            'delivery_outcome' => !empty($data['delivery_outcome']) ? $data['delivery_outcome'] : null,
            'notes' => !empty($data['notes']) ? trim($data['notes']) : null,
            'created_by' => $data['created_by']
        ]);

        return $result ? (int)$this->db->lastInsertId() : false;
    }

    /**
     * Update an existing pregnancy episode record.
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateEpisode($id, $data) {
        $edc = !empty($data['edc']) ? $data['edc'] : $this->calculateEDC($data['lmp']);

        $sql = "UPDATE prenatal_records SET
                    husband_name = :husband_name,
                    gravida = :gravida,
                    para = :para,
                    term_births = :term_births,
                    preterm_births = :preterm_births,
                    abortions = :abortions,
                    living_children = :living_children,
                    lmp = :lmp,
                    edc = :edc,
                    is_active = :is_active,
                    pre_eclampsia = :pre_eclampsia,
                    fp_counselling = :fp_counselling,
                    delivery_date = :delivery_date,
                    delivery_outcome = :delivery_outcome,
                    notes = :notes
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'husband_name' => !empty($data['husband_name']) ? trim($data['husband_name']) : null,
            'gravida' => isset($data['gravida']) ? (int)$data['gravida'] : 1,
            'para' => isset($data['para']) ? (int)$data['para'] : 0,
            'term_births' => isset($data['term_births']) ? (int)$data['term_births'] : 0,
            'preterm_births' => isset($data['preterm_births']) ? (int)$data['preterm_births'] : 0,
            'abortions' => isset($data['abortions']) ? (int)$data['abortions'] : 0,
            'living_children' => isset($data['living_children']) ? (int)$data['living_children'] : 0,
            'lmp' => $data['lmp'],
            'edc' => $edc,
            'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1,
            'pre_eclampsia' => !empty($data['pre_eclampsia']) ? 1 : 0,
            'fp_counselling' => isset($data['fp_counselling']) ? (int)$data['fp_counselling'] : 1,
            'delivery_date' => !empty($data['delivery_date']) ? $data['delivery_date'] : null,
            'delivery_outcome' => !empty($data['delivery_outcome']) ? $data['delivery_outcome'] : null,
            'notes' => !empty($data['notes']) ? trim($data['notes']) : null
        ]);
    }

    /**
     * Conclude a pregnancy episode upon delivery or other outcome.
     * 
     * @param int $id
     * @param string|array $deliveryDate
     * @param string $outcome
     * @param string|null $notes
     * @return bool
     */
    public function concludeEpisode($id, $deliveryDate, $outcome = 'Live Birth', $notes = null) {
        if (is_array($deliveryDate)) {
            $data = $deliveryDate;
            $delivDate = $data['delivery_date'] ?? date('Y-m-d');
            $delivOutcome = $data['delivery_outcome'] ?? 'Live Birth';
            $delivNotes = $data['notes'] ?? null;
        } else {
            $delivDate = $deliveryDate;
            $delivOutcome = $outcome;
            $delivNotes = $notes;
        }

        $sql = "UPDATE prenatal_records SET
                    is_active = 0,
                    delivery_date = :delivery_date,
                    delivery_outcome = :delivery_outcome,
                    notes = CONCAT(COALESCE(notes, ''), '\n', :notes)
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'delivery_date' => $delivDate,
            'delivery_outcome' => $delivOutcome,
            'notes' => !empty($delivNotes) ? "Delivery Notes: " . trim($delivNotes) : ""
        ]);
    }

    /**
     * Calculate Estimated Date of Confinement (EDC / Due Date) using Naegele's Rule:
     * EDC = LMP + 1 Year - 3 Months + 7 Days (or LMP + 280 days).
     * 
     * @param string $lmpDate YYYY-MM-DD
     * @return string YYYY-MM-DD
     */
    public function calculateEDC($lmpDate) {
        if (empty($lmpDate)) return '';
        try {
            $lmp = new DateTime($lmpDate);
            $edc = clone $lmp;
            $edc->modify('+1 year -3 months +7 days');
            return $edc->format('Y-m-d');
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Calculate current Age of Gestation (AOG) in weeks and days from LMP to current date.
     * 
     * @param string $lmpDate YYYY-MM-DD
     * @param string|null $targetDate Defaults to Today
     * @return array ['weeks' => int, 'days' => int, 'formatted' => string, 'decimal' => float]
     */
    public function calculateCurrentAOG($lmpDate, $targetDate = null) {
        if (empty($lmpDate)) {
            return ['weeks' => 0, 'days' => 0, 'formatted' => '0 weeks', 'decimal' => 0.0];
        }

        try {
            $lmp = new DateTime($lmpDate);
            $target = $targetDate ? new DateTime($targetDate) : new DateTime();

            $interval = $lmp->diff($target);
            $totalDays = (int)$interval->format('%r%a');

            if ($totalDays < 0) {
                return ['weeks' => 0, 'days' => 0, 'formatted' => '0 weeks', 'decimal' => 0.0];
            }

            $weeks = (int)floor($totalDays / 7);
            $days = $totalDays % 7;
            $decimal = round($weeks + ($days / 7), 1);

            $formatted = $weeks . " weeks";
            if ($days > 0) {
                $formatted .= ", " . $days . " days";
            }

            return [
                'weeks' => $weeks,
                'days' => $days,
                'formatted' => $formatted,
                'decimal' => $decimal,
                'total_days' => $totalDays
            ];
        } catch (\Exception $e) {
            return ['weeks' => 0, 'days' => 0, 'formatted' => '0 weeks', 'decimal' => 0.0];
        }
    }
}

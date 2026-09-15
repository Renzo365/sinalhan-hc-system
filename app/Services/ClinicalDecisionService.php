<?php

namespace App\Services;

use App\Core\Database;
use PDO;

class ClinicalDecisionService {
    /**
     * Get all active clinical alerts and safety flags for a patient.
     *
     * @param int $patientId
     * @return array
     */
    public static function getAlertsForPatient(int $patientId): array {
        if ($patientId <= 0) {
            return self::emptyAlerts();
        }

        $db = Database::getInstance()->getConnection();

        // 1. Fetch Past Medical Conditions
        $stmt = $db->prepare("
            SELECT condition_name, remarks 
            FROM patient_conditions 
            WHERE patient_id = :patient_id AND condition_type = 'Past'
        ");
        $stmt->execute(['patient_id' => $patientId]);
        $conditions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $condMap = [];
        foreach ($conditions as $c) {
            $name = MedicalDictionaryService::normalizeCondition($c['condition_name']);
            if ($name !== '') {
                $condMap[$name] = trim((string)($c['remarks'] ?? ''));
            }
        }

        // 2. Evaluate Specific CDS Safety Flags
        $allergyDetail = null;
        $hasAllergy = false;
        if (isset($condMap['Allergy'])) {
            $hasAllergy = true;
            $allergyDetail = !empty($condMap['Allergy']) ? $condMap['Allergy'] : 'Allergy Recorded';
        }

        $hasHypertension = isset($condMap['Hypertension']);
        $hasDiabetes = isset($condMap['Diabetes Mellitus']);
        $hasAsthma = isset($condMap['Asthma']);
        $hasTb = isset($condMap['Pulmonary Tuberculosis (PTB)']);

        // 3. Build UI Badges / Safety Flags list
        $flags = [];
        if ($hasAllergy) {
            $flags[] = [
                'type' => 'danger',
                'class' => 'bg-danger-subtle text-danger border border-danger-subtle',
                'icon' => 'bi-exclamation-octagon-fill',
                'label' => 'ALLERGIC: ' . $allergyDetail,
                'code' => 'ALLERGY'
            ];
        }
        if ($hasHypertension) {
            $flags[] = [
                'type' => 'secondary',
                'class' => 'bg-secondary-subtle text-dark border',
                'icon' => 'bi-heart-pulse text-danger',
                'label' => 'Hypertension' . (!empty($condMap['Hypertension']) ? ' (' . $condMap['Hypertension'] . ')' : ''),
                'code' => 'HYPERTENSION'
            ];
        }
        if ($hasDiabetes) {
            $flags[] = [
                'type' => 'secondary',
                'class' => 'bg-secondary-subtle text-dark border',
                'icon' => 'bi-droplet-half text-warning',
                'label' => 'Diabetes Mellitus' . (!empty($condMap['Diabetes Mellitus']) ? ' (' . $condMap['Diabetes Mellitus'] . ')' : ''),
                'code' => 'DIABETES'
            ];
        }
        if ($hasAsthma) {
            $flags[] = [
                'type' => 'secondary',
                'class' => 'bg-secondary-subtle text-dark border',
                'icon' => 'bi-wind text-info',
                'label' => 'Asthma' . (!empty($condMap['Asthma']) ? ' (' . $condMap['Asthma'] . ')' : ''),
                'code' => 'ASTHMA'
            ];
        }
        if ($hasTb) {
            $flags[] = [
                'type' => 'secondary',
                'class' => 'bg-secondary-subtle text-dark border',
                'icon' => 'bi-lungs text-danger',
                'label' => 'Tuberculosis' . (!empty($condMap['Pulmonary Tuberculosis (PTB)']) ? ' (' . $condMap['Pulmonary Tuberculosis (PTB)'] . ')' : ''),
                'code' => 'TB'
            ];
        }

        return [
            'has_alerts' => !empty($flags),
            'flags' => $flags,
            'allergy_alert' => $allergyDetail,
            'has_hypertension' => $hasHypertension,
            'has_diabetes' => $hasDiabetes,
            'has_asthma' => $hasAsthma,
            'has_tb' => $hasTb,
            'conditions_map' => $condMap
        ];
    }

    /**
     * Return empty alerts structure.
     *
     * @return array
     */
    protected static function emptyAlerts(): array {
        return [
            'has_alerts' => false,
            'flags' => [],
            'allergy_alert' => null,
            'has_hypertension' => false,
            'has_diabetes' => false,
            'has_asthma' => false,
            'has_tb' => false,
            'conditions_map' => []
        ];
    }
}
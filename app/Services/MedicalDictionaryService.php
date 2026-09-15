<?php

namespace App\Services;

class MedicalDictionaryService {
    /**
     * Standard alias mappings for canonical medical condition names.
     */
    protected static array $aliases = [
        'ptb' => 'Pulmonary Tuberculosis (PTB)',
        'tuberculosis' => 'Pulmonary Tuberculosis (PTB)',
        'pulmonary tuberculosis' => 'Pulmonary Tuberculosis (PTB)',
        'pulmonary tuberculosis (ptb)' => 'Pulmonary Tuberculosis (PTB)',
        'allergies' => 'Allergy',
        'allergy' => 'Allergy',
        'asthma' => 'Asthma',
        'bronchial asthma' => 'Asthma',
        'hypertension' => 'Hypertension',
        'htn' => 'Hypertension',
        'high blood' => 'Hypertension',
        'diabetes' => 'Diabetes Mellitus',
        'diabetes mellitus' => 'Diabetes Mellitus',
        'dm' => 'Diabetes Mellitus',
        'heart disease' => 'Heart Disease',
        'epilepsy' => 'Epilepsy / Seizure Disorder',
        'seizure disorder' => 'Epilepsy / Seizure Disorder',
        'thyroid' => 'Thyroid Disease',
        'thyroid disease' => 'Thyroid Disease',
        'kidney disease' => 'Kidney / Renal Disease',
        'renal disease' => 'Kidney / Renal Disease',
        'stroke' => 'Stroke / CVA',
        'cva' => 'Stroke / CVA',
        'cancer' => 'Cancer',
        'hepatitis' => 'Hepatitis',
        'hyperlipidemia' => 'Hyperlipidemia',
        'peptic ulcer' => 'Peptic Ulcer Disease',
        'pud' => 'Peptic Ulcer Disease',
    ];

    /**
     * Normalize and canonicalize a medical condition name.
     *
     * @param string $name
     * @return string
     */
    public static function normalizeCondition(string $name): string {
        $trimmed = trim($name);
        if ($trimmed === '' || $trimmed === '[]' || $trimmed === '{}' || $trimmed === 'Yes') {
            return '';
        }

        $lower = strtolower($trimmed);
        if (isset(self::$aliases[$lower])) {
            return self::$aliases[$lower];
        }

        return $trimmed;
    }

    /**
     * Get the list of conditions that warrant clinical decision support (CDS) alerts.
     *
     * @return array
     */
    public static function getCdsAlertConditions(): array {
        return [
            'Allergy',
            'Hypertension',
            'Diabetes Mellitus',
            'Asthma',
            'Pulmonary Tuberculosis (PTB)',
        ];
    }
}
<?php

namespace App\Validators;

use DateTime;

class ConsultationValidator extends BaseValidator {
    /**
     * Validate consultation create and update inputs.
     *
     * @param array $input
     * @return array
     */
    public function validate(array $input) {
        $this->clearErrors();

        $requiredFields = [
            'subjective' => 'Subjective Notes (Chief Complaint)',
            'objective' => 'Objective Notes (Physical Findings)',
            'assessment' => 'Assessment (Diagnosis)',
            'plan' => 'Plan (Treatment/Prescriptions)',
            'consulted_by' => 'Consulting Provider',
            'consulted_at' => 'Date & Time of Consultation'
        ];

        foreach ($requiredFields as $field => $label) {
            if (empty($input[$field]) || trim((string)$input[$field]) === '') {
                $this->addError("{$label} is required.");
            }
        }

        $status = $input['status'] ?? 'Completed';
        if (!in_array($status, ['Open', 'Completed', 'Cancelled'], true)) {
            $this->addError('Invalid consultation status.');
        }

        if (!empty($input['consulted_at'])) {
            $date = DateTime::createFromFormat('Y-m-d\TH:i', $input['consulted_at'])
                ?: DateTime::createFromFormat('Y-m-d H:i:s', $input['consulted_at']);
            if (!$date || $date->format('Y-m-d') > date('Y-m-d') ||
                ($date->format('Y-m-d') === date('Y-m-d') && $date->getTimestamp() > time())) {
                $this->addError('Consultation date/time must be valid and cannot be in the future.');
            }
        }

        return $this->errors;
    }
}

<?php

namespace App\Validators;

use DateTime;

class WellbabyValidator extends BaseValidator {
    /**
     * Validate infant birth circumstances and newborn screening input.
     *
     * @param array $input
     * @param int|null $patientId Child patient ID
     * @param \App\Models\Patient|null $patientModel
     * @return array List of error strings
     */
    public function validateBirthRecord(array $input, $patientId = null, $patientModel = null) {
        $this->clearErrors();

        $birthWeight = !empty($input['birth_weight_kg']) ? (float)$input['birth_weight_kg'] : 0;
        $birthLength = !empty($input['birth_length_cm']) ? (float)$input['birth_length_cm'] : 0;
        $screeningDone = !empty($input['newborn_screening_done']) ? 1 : 0;
        $screeningDate = !empty($input['newborn_screening_date']) ? $input['newborn_screening_date'] : null;

        if ($birthWeight <= 0 || $birthLength <= 0) {
            $this->addError('Valid birth weight (kg) and birth length (cm) are required.');
        }

        if ($screeningDone && !$screeningDate) {
            $this->addError('A newborn screening date is required when screening is marked done.');
        }

        if ($screeningDate) {
            $screeningDateObject = DateTime::createFromFormat('Y-m-d', $screeningDate);
            if (!$screeningDateObject || $screeningDateObject->format('Y-m-d') !== $screeningDate || $screeningDate > date('Y-m-d')) {
                $this->addError('Newborn screening date must be a valid date that is not in the future.');
            }
        }

        $motherPatientId = !empty($input['mother_patient_id']) ? (int)$input['mother_patient_id'] : null;
        if ($motherPatientId !== null && $patientModel !== null) {
            $mother = $patientModel->findById($motherPatientId);
            if (!$mother || strtolower($mother['sex'] ?? '') !== 'female' || ($patientId !== null && $motherPatientId === (int)$patientId)) {
                $this->addError('The selected mother must be an existing female patient different from the child.');
            }
        }

        return $this->errors;
    }

    /**
     * Validate pediatric growth log checkup inputs.
     *
     * @param array $input
     * @return array List of error strings
     */
    public function validateGrowthLog(array $input) {
        $this->clearErrors();

        $logDate = $input['log_date'] ?? date('Y-m-d');
        $weight = !empty($input['weight_kg']) ? (float)$input['weight_kg'] : 0;
        $height = !empty($input['height_cm']) ? (float)$input['height_cm'] : 0;
        $ageMonths = isset($input['age_months']) ? (float)$input['age_months'] : 0;

        $logDateObject = DateTime::createFromFormat('Y-m-d', $logDate);
        if (!$logDateObject || $logDateObject->format('Y-m-d') !== $logDate || $logDate > date('Y-m-d')) {
            $this->addError('Growth visit date must be a valid date that is not in the future.');
        }

        if ($weight <= 0 || $height <= 0 || $ageMonths < 0 || $ageMonths > 60) {
            $this->addError('Valid weight, height, and age in months (0 to 60) are required.');
        }

        return $this->errors;
    }
}

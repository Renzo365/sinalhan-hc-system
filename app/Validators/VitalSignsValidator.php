<?php

namespace App\Validators;

class VitalSignsValidator extends BaseValidator {
    /**
     * Validate vital signs input.
     *
     * @param array $input Raw input array
     * @return array Array of validation error messages
     */
    public function validate(array $input): array {
        $this->clearErrors();

        $metrics = [
            'bp_systolic', 'bp_diastolic', 'heart_rate', 
            'respiratory_rate', 'temperature', 'weight', 
            'height', 'oxygen_saturation', 'waist_circumference', 'notes'
        ];

        $hasMetric = false;
        foreach ($metrics as $metric) {
            if (isset($input[$metric]) && trim((string)$input[$metric]) !== '') {
                $hasMetric = true;
                break;
            }
        }

        if (!$hasMetric) {
            $this->addError('At least one vital sign value must be filled.');
            return $this->errors;
        }

        // Blood Pressure (Systolic)
        $sys = null;
        if (isset($input['bp_systolic']) && trim((string)$input['bp_systolic']) !== '') {
            $sys = (int)$input['bp_systolic'];
            if ($sys < 40 || $sys > 300) {
                $this->addError('Systolic BP must be between 40 and 300 mmHg.');
            }
        }

        // Blood Pressure (Diastolic)
        $dia = null;
        if (isset($input['bp_diastolic']) && trim((string)$input['bp_diastolic']) !== '') {
            $dia = (int)$input['bp_diastolic'];
            if ($dia < 30 || $dia > 200) {
                $this->addError('Diastolic BP must be between 30 and 200 mmHg.');
            }
        }

        // Physiological check: Systolic must be greater than Diastolic
        if ($sys !== null && $dia !== null) {
            if ($sys <= $dia) {
                $this->addError('Systolic BP must be greater than Diastolic BP.');
            }
        }

        // Heart Rate / Pulse
        if (isset($input['heart_rate']) && trim((string)$input['heart_rate']) !== '') {
            $hr = (int)$input['heart_rate'];
            if ($hr < 20 || $hr > 250) {
                $this->addError('Heart rate must be between 20 and 250 bpm.');
            }
        }

        // Respiratory Rate
        if (isset($input['respiratory_rate']) && trim((string)$input['respiratory_rate']) !== '') {
            $rr = (int)$input['respiratory_rate'];
            if ($rr < 5 || $rr > 80) {
                $this->addError('Respiratory rate must be between 5 and 80 cpm.');
            }
        }

        // Temperature
        if (isset($input['temperature']) && trim((string)$input['temperature']) !== '') {
            $temp = (float)$input['temperature'];
            if ($temp < 30.0 || $temp > 45.0) {
                $this->addError('Temperature must be between 30.0 °C and 45.0 °C.');
            }
        }

        // Oxygen Saturation (SpO2)
        if (isset($input['oxygen_saturation']) && trim((string)$input['oxygen_saturation']) !== '') {
            $spo2 = (int)$input['oxygen_saturation'];
            if ($spo2 < 50 || $spo2 > 100) {
                $this->addError('Oxygen saturation (SpO2) must be between 50% and 100%.');
            }
        }

        // Weight
        if (isset($input['weight']) && trim((string)$input['weight']) !== '') {
            $wt = (float)$input['weight'];
            if ($wt < 0.5 || $wt > 500) {
                $this->addError('Weight must be between 0.5 kg and 500 kg.');
            }
        }

        // Height
        if (isset($input['height']) && trim((string)$input['height']) !== '') {
            $ht = (float)$input['height'];
            if ($ht < 20 || $ht > 250) {
                $this->addError('Height must be between 20 cm and 250 cm.');
            }
        }

        // Waist Circumference
        if (isset($input['waist_circumference']) && trim((string)$input['waist_circumference']) !== '') {
            $waist = (float)$input['waist_circumference'];
            if ($waist < 20 || $waist > 300) {
                $this->addError('Waist circumference must be between 20 cm and 300 cm.');
            }
        }

        return $this->errors;
    }
}

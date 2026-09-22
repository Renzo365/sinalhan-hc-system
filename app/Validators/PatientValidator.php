<?php

namespace App\Validators;

use App\Models\Patient;
use DateTime;

class PatientValidator extends BaseValidator {
    /**
     * @var Patient|null
     */
    protected $patientModel;

    public function __construct(Patient $patientModel = null) {
        $this->patientModel = $patientModel ?: new Patient();
    }

    /**
     * Comprehensive server-side validation for patient demographics.
     * 
     * @param array $input Raw POST input array
     * @param int|null $excludePatientId Patient ID to exclude for PhilHealth uniqueness
     * @return array Array of validation error messages
     */
    public function validate(array $input, $excludePatientId = null) {
        $this->clearErrors();

        // 1. Required fields presence check
        $requiredFields = [
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'dob' => 'Date of Birth',
            'sex' => 'Biological Sex',
            'civil_status' => 'Civil Status',
            'address' => 'Full Address'
        ];

        foreach ($requiredFields as $field => $label) {
            if (empty($input[$field]) || trim($input[$field]) === '') {
                $this->addError("{$label} is required.");
            }
        }

        // 2. Name fields regex & length validation (Letters, spaces, hyphens, apostrophes, dots, ñ/Ñ)
        $namePattern = '/^[a-zA-ZñÑ\s\-\'\.]{2,50}$/u';

        if (!empty($input['first_name'])) {
            $firstName = trim($input['first_name']);
            if (!preg_match($namePattern, $firstName)) {
                $this->addError('First Name must contain only letters, spaces, hyphens, or apostrophes (2 to 50 characters, no numbers allowed).');
            }
        }

        if (!empty($input['middle_name']) && trim($input['middle_name']) !== '') {
            $middleName = trim($input['middle_name']);
            if (!preg_match($namePattern, $middleName)) {
                $this->addError('Middle Name must contain only letters, spaces, hyphens, or apostrophes (2 to 50 characters, no numbers allowed).');
            }
        }

        if (!empty($input['last_name'])) {
            $lastName = trim($input['last_name']);
            if (!preg_match($namePattern, $lastName)) {
                $this->addError('Last Name must contain only letters, spaces, hyphens, or apostrophes (2 to 50 characters, no numbers allowed).');
            }
        }

        if (!empty($input['emergency_name']) && trim($input['emergency_name']) !== '') {
            $emergencyName = trim($input['emergency_name']);
            if (!preg_match('/^[a-zA-ZñÑ\s\-\'\.\,]{2,100}$/u', $emergencyName)) {
                $this->addError('Emergency Contact Person Name must contain only letters, spaces, hyphens, commas, or apostrophes (2 to 100 characters).');
            }
        }

        // 3. Philippine Mobile Phone Numbers (11 digits, starting with 09)
        $phonePattern = '/^09\d{9}$/';
        
        if (!empty($input['contact_no']) && trim($input['contact_no']) !== '') {
            $contactNo = trim($input['contact_no']);
            if (!preg_match($phonePattern, $contactNo)) {
                $this->addError('Primary Contact No. must be a valid 11-digit Philippine mobile number starting with 09 (e.g. 09998698088).');
            }
        }

        if (!empty($input['emergency_no']) && trim($input['emergency_no']) !== '') {
            $emergencyNo = trim($input['emergency_no']);
            if (!preg_match($phonePattern, $emergencyNo)) {
                $this->addError('Emergency Contact Number must be a valid 11-digit Philippine mobile number starting with 09 (e.g. 09998698088).');
            }
        }

        // 4. Date of Birth Bounds & Format Validation
        if (!empty($input['dob'])) {
            $dob = trim($input['dob']);
            $d = DateTime::createFromFormat('Y-m-d', $dob);
            if (!$d || $d->format('Y-m-d') !== $dob) {
                $this->addError('Date of Birth must be a valid date in YYYY-MM-DD format.');
            } else {
                $dobTimestamp = strtotime($dob);
                $now = time();
                $minDate = strtotime('1900-01-01');

                if ($dobTimestamp > $now) {
                    $this->addError('Date of Birth cannot be a future date.');
                } elseif ($dobTimestamp < $minDate) {
                    $this->addError('Date of Birth cannot be prior to 1900.');
                }
            }
        }

        // 5. PhilHealth ID Number Validation (XX-XXXXXXXXX-X format)
        if (!empty($input['philhealth_no']) && trim($input['philhealth_no']) !== '') {
            $philhealthNo = trim($input['philhealth_no']);
            if (!preg_match('/^\d{2}-\d{9}-\d{1}$/', $philhealthNo)) {
                $this->addError('PhilHealth ID No. must follow the standard 12-digit format: XX-XXXXXXXXX-X (e.g. 12-345678901-2).');
            } elseif ($this->patientModel && !$this->patientModel->isPhilHealthUnique($philhealthNo, $excludePatientId)) {
                $this->addError('PhilHealth ID number is already registered to another patient record.');
            }
        }

        // 6. Biological Sex Whitelist
        if (!empty($input['sex'])) {
            if (!in_array($input['sex'], ['Male', 'Female'], true)) {
                $this->addError('Invalid Biological Sex selected.');
            }
        }

        // 7. Civil Status Whitelist & Conditional 'Others' Validation
        if (!empty($input['civil_status'])) {
            $allowedStatuses = ['Single', 'Married', 'Widow/Widower', 'Annulled', 'Separated', 'Others'];
            if (!in_array($input['civil_status'], $allowedStatuses, true)) {
                $this->addError('Invalid Civil Status selected.');
            } elseif ($input['civil_status'] === 'Others' && empty(trim($input['civil_status_other'] ?? ''))) {
                $this->addError('Please specify the Civil Status when "Others" is selected.');
            }
        }

        // 8. Blood Type Whitelist
        if (!empty($input['blood_type'])) {
            $allowedBloodTypes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'Unknown'];
            if (!in_array($input['blood_type'], $allowedBloodTypes, true)) {
                $this->addError('Invalid Blood Type selected.');
            }
        }

        // 9. Barangay & Address Length
        if (!empty($input['barangay'])) {
            $barangay = trim($input['barangay']);
            if (mb_strlen($barangay) < 2 || mb_strlen($barangay) > 100) {
                $this->addError('Barangay name must be between 2 and 100 characters.');
            }
        }

        if (!empty($input['address'])) {
            $address = trim($input['address']);
            if (mb_strlen($address) < 5 || mb_strlen($address) > 500) {
                $this->addError('Complete Address must be between 5 and 500 characters.');
            }
        }

        // 10. Suffix / Extension Validation
        if (!empty($input['suffix']) && trim($input['suffix']) !== '') {
            $suffix = trim($input['suffix']);
            if (mb_strlen($suffix) > 20 || !preg_match('/^[a-zA-Z0-9\.\s\-]+$/', $suffix)) {
                $this->addError('Extension (Sr., Jr., etc.) must be 20 characters or less.');
            }
        }

        // 11. Family Number Format
        if (!empty($input['family_no']) && trim($input['family_no']) !== '') {
            $familyNo = trim($input['family_no']);
            if (mb_strlen($familyNo) > 50 || !preg_match('/^[a-zA-Z0-9\-\_\s\.]+$/', $familyNo)) {
                $this->addError('Family Number must be alphanumeric and under 50 characters.');
            }
        }

        // 12. Educational Attainment Whitelist
        if (!empty($input['education_attainment']) && trim($input['education_attainment']) !== '') {
            $allowedEdu = ['No Schooling', 'Elementary', 'High School', 'Vocational', 'College degree, post graduate'];
            if (!in_array($input['education_attainment'], $allowedEdu, true)) {
                $this->addError('Invalid Educational Attainment selected.');
            }
        }

        // 13. PhilHealth Membership Status
        if (!empty($input['phic_status'])) {
            $allowedPhicStatus = ['Member', 'Dependent', 'Non-Member'];
            if (!in_array($input['phic_status'], $allowedPhicStatus, true)) {
                $this->addError('Invalid PhilHealth Status selected.');
            }
        }

        // 14. Immediate Family Names Validation
        $familyNames = [
            'father_name' => "Father's Name",
            'mother_name' => "Mother's Maiden Name",
            'spouse_name' => "Spouse's Name"
        ];

        foreach ($familyNames as $field => $fieldLabel) {
            if (!empty($input[$field]) && trim($input[$field]) !== '') {
                $val = trim($input[$field]);
                if (mb_strlen($val) > 150 || !preg_match('/^[a-zA-ZñÑ\s\-\'\.\,]{2,150}$/u', $val)) {
                    $this->addError("{$fieldLabel} must contain only letters, spaces, hyphens, commas, or apostrophes (2 to 150 characters).");
                }
            }
        }

        // 15. Immediate Family Dates of Birth
        $familyDobs = [
            'father_dob' => "Father's Date of Birth",
            'mother_dob' => "Mother's Date of Birth",
            'spouse_dob' => "Spouse's Date of Birth"
        ];

        foreach ($familyDobs as $field => $fieldLabel) {
            if (!empty($input[$field]) && trim($input[$field]) !== '') {
                $dobVal = trim($input[$field]);
                $d = DateTime::createFromFormat('Y-m-d', $dobVal);
                if (!$d || $d->format('Y-m-d') !== $dobVal) {
                    $this->addError("{$fieldLabel} must be a valid date in YYYY-MM-DD format.");
                } elseif (strtotime($dobVal) > time()) {
                    $this->addError("{$fieldLabel} cannot be a future date.");
                }
            }
        }

        // 16. Physical Envelope Number (Optional, max 50 chars)
        if (!empty($input['envelope_no']) && mb_strlen(trim($input['envelope_no'])) > 50) {
            $this->addError('Physical Envelope No. cannot exceed 50 characters.');
        }

        return $this->errors;
    }
}

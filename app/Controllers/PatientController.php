<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Patient;
use App\Models\VitalSigns;
use App\Models\AuditLog;
use PDO;

class PatientController extends Controller {
    protected $patientModel;
    protected $vitalsModel;

    public function __construct() {
        $this->patientModel = new Patient();
        $this->vitalsModel = new VitalSigns();
    }

    /**
     * Display a paginated, filterable list of active patients.
     */
    public function index() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Get filters
        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'barangay' => trim($_GET['barangay'] ?? ''),
            'sex' => trim($_GET['sex'] ?? ''),
            'age_group' => trim($_GET['age_group'] ?? ''),
            'program_type' => trim($_GET['program_type'] ?? '')
        ];

        // Fetch patients
        $patients = $this->patientModel->allActive($filters);

        // Fetch list of unique barangays for filter dropdown
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT DISTINCT barangay FROM patients WHERE deleted_at IS NULL ORDER BY barangay ASC");
            $barangays = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: ['Sinalhan'];
        } catch (\Exception $e) {
            $barangays = ['Sinalhan'];
        }

        $this->view('patients/index', [
            'patients' => $patients,
            'filters' => $filters,
            'barangays' => $barangays
        ]);
    }

    /**
     * Show the registration form.
     */
    public function create() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Retrieve flashed input/errors
        $errors = $_SESSION['form_errors'] ?? [];
        $input = $_SESSION['form_input'] ?? [];
        
        unset($_SESSION['form_errors']);
        unset($_SESSION['form_input']);

        $this->view('patients/create', [
            'errors' => $errors,
            'input' => $input
        ]);
    }

    /**
     * Handle the patient registration submission.
     */
    public function store() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $errors = $this->validatePatientData($_POST);

        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_input'] = $_POST;
            $this->redirect('/patients/create');
        }

        // Save
        $data = $_POST;
        $data['created_by'] = $_SESSION['user_id'];

        $newId = $this->patientModel->create($data);

        if ($newId) {
            $patient = $this->patientModel->findById($newId);
            $patientNo = $patient['patient_no'] ?? 'N/A';
            
            AuditLog::log('PATIENT_REGISTERED', 'Patients', "Registered new patient: " . $_POST['first_name'] . ' ' . $_POST['last_name'] . " ({$patientNo})");
            
            $_SESSION['success_message'] = 'Patient registered successfully!';
            $this->redirect("/patients/{$newId}");
        } else {
            $_SESSION['form_errors'] = ['Database insertion failed. Please try again.'];
            $_SESSION['form_input'] = $_POST;
            $this->redirect('/patients/create');
        }
    }

    /**
     * AJAX action to inspect duplicate patient names.
     */
    public function checkDuplicate() {
        $firstName = $_GET['first_name'] ?? '';
        $lastName = $_GET['last_name'] ?? '';

        if (empty($firstName) || empty($lastName)) {
            $this->json([]);
        }

        $duplicates = $this->patientModel->findDuplicates($firstName, $lastName);
        $this->json($duplicates);
    }

    /**
     * Display a patient's profile details & clinical history.
     */
    public function show($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $patient = $this->patientModel->findById($id);

        if (!$patient) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        // Get vital signs history & latest record
        $vitalsHistory = $this->vitalsModel->findByPatientId($id);
        $latestVitals = $this->vitalsModel->latestByPatientId($id);

        // Get consultation history
        $consultationsHistory = (new \App\Models\Consultation())->findByPatientId($id);

        // Get appointment history
        $appointmentsHistory = (new \App\Models\Appointment())->findByPatientId($id);

        // Get queue history
        $queueHistory = (new \App\Models\QueueEntry())->findByPatientId($id);

        // Get IHP Medical History
        $medicalHistory = (new \App\Models\PatientMedicalHistory())->findByPatientId($id);

        // Get Household Family Members sharing the same family_no
        $familyMembers = !empty($patient['family_no']) 
            ? $this->patientModel->familyMembers($patient['family_no'], $id) 
            : [];

        // Get Active Maternal Prenatal Episode, Visits & Past Obstetric History (if female)
        $activePrenatal = false;
        $prenatalVisits = [];
        $pastDeliveries = [];
        $allPrenatalEpisodes = [];

        if (strtolower($patient['sex']) === 'female') {
            $prenatalModel = new \App\Models\PrenatalRecord();
            $activePrenatal = $prenatalModel->findActiveByPatientId($id);
            if ($activePrenatal) {
                $prenatalVisits = (new \App\Models\PrenatalVisit())->findByPrenatalId($activePrenatal['id']);
            }
            $pastDeliveries = (new \App\Models\PastObstetricHistory())->findByPatientId($id);
            $allPrenatalEpisodes = $prenatalModel->findAllByPatientId($id);
        }

        // Get Well Baby Record & Growth Logs (if child 0-5 or registered)
        $wellbabyRecord = (new \App\Models\WellbabyRecord())->findByPatientId($id);
        $growthLogs = [];
        if ($wellbabyRecord) {
            $growthLogs = (new \App\Models\ChildGrowthLog())->findByWellbabyId($wellbabyRecord['id']);
        }

        // Get Immunization records & map for this patient
        $immModel = new \App\Models\Immunization();
        $patientImmunizations = $immModel->findByPatientId($id);
        $vaccineMap = $immModel->getVaccineMap($id);

        // Fetch potential registered mothers for linking
        $potentialMothers = $this->patientModel->findPotentialMothers(100);

        // Compute Program Badge
        $programBadge = $this->patientModel->getProgramBadge($id, $patient['dob'], $patient['sex']);

        $this->view('patients/show', [
            'patient' => $patient,
            'vitalsHistory' => $vitalsHistory,
            'latestVitals' => $latestVitals,
            'consultationsHistory' => $consultationsHistory,
            'appointmentsHistory' => $appointmentsHistory,
            'queueHistory' => $queueHistory,
            'medicalHistory' => $medicalHistory,
            'familyMembers' => $familyMembers,
            'activePrenatal' => $activePrenatal,
            'prenatalVisits' => $prenatalVisits,
            'pastDeliveries' => $pastDeliveries,
            'allPrenatalEpisodes' => $allPrenatalEpisodes,
            'wellbabyRecord' => $wellbabyRecord,
            'growthLogs' => $growthLogs,
            'patientImmunizations' => $patientImmunizations,
            'vaccineMap' => $vaccineMap,
            'potentialMothers' => $potentialMothers,
            'programBadge' => $programBadge
        ]);
    }

    /**
     * Show edit demographic form.
     */
    public function edit($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $patient = $this->patientModel->findById($id);

        if (!$patient) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        // Retrieve flashed inputs/errors
        $errors = $_SESSION['form_errors'] ?? [];
        unset($_SESSION['form_errors']);

        $this->view('patients/edit', [
            'patient' => $patient,
            'errors' => $errors
        ]);
    }

    /**
     * Process updates to a patient's profile.
     */
    public function update($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $patient = $this->patientModel->findById($id);
        if (!$patient) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $errors = $this->validatePatientData($_POST, $id);

        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $this->redirect("/patients/{$id}/edit");
        }

        $data = $_POST;
        $data['updated_by'] = $_SESSION['user_id'];

        if ($this->patientModel->update($id, $data)) {
            AuditLog::log('PATIENT_UPDATED', 'Patients', "Updated patient profile: " . $_POST['first_name'] . ' ' . $_POST['last_name'] . " ({$patient['patient_no']})");
            
            $_SESSION['success_message'] = 'Patient demographics updated successfully!';
            $this->redirect("/patients/{$id}");
        } else {
            $_SESSION['form_errors'] = ['Database update failed. Please try again.'];
            $this->redirect("/patients/{$id}/edit");
        }
    }

    /**
     * Comprehensive server-side validation for patient demographics.
     * 
     * @param array $input Raw POST input array
     * @param int|null $excludePatientId Patient ID to exclude for PhilHealth uniqueness
     * @return array Array of validation error messages
     */
    protected function validatePatientData(array $input, $excludePatientId = null) {
        $errors = [];

        // 1. Required fields presence check
        $requiredFields = [
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'dob' => 'Date of Birth',
            'sex' => 'Biological Sex',
            'civil_status' => 'Civil Status',
            'address' => 'Complete Address',
            'barangay' => 'Barangay'
        ];

        foreach ($requiredFields as $field => $label) {
            if (empty($input[$field]) || trim($input[$field]) === '') {
                $errors[] = "{$label} is required.";
            }
        }

        // 2. Name fields regex & length validation (Letters, spaces, hyphens, apostrophes, dots, ñ/Ñ)
        $namePattern = '/^[a-zA-ZñÑ\s\-\'\.]{2,50}$/u';

        if (!empty($input['first_name'])) {
            $firstName = trim($input['first_name']);
            if (!preg_match($namePattern, $firstName)) {
                $errors[] = 'First Name must contain only letters, spaces, hyphens, or apostrophes (2 to 50 characters, no numbers allowed).';
            }
        }

        if (!empty($input['middle_name']) && trim($input['middle_name']) !== '') {
            $middleName = trim($input['middle_name']);
            if (!preg_match($namePattern, $middleName)) {
                $errors[] = 'Middle Name must contain only letters, spaces, hyphens, or apostrophes (2 to 50 characters, no numbers allowed).';
            }
        }

        if (!empty($input['last_name'])) {
            $lastName = trim($input['last_name']);
            if (!preg_match($namePattern, $lastName)) {
                $errors[] = 'Last Name must contain only letters, spaces, hyphens, or apostrophes (2 to 50 characters, no numbers allowed).';
            }
        }

        if (!empty($input['emergency_name']) && trim($input['emergency_name']) !== '') {
            $emergencyName = trim($input['emergency_name']);
            if (!preg_match('/^[a-zA-ZñÑ\s\-\'\.]{2,100}$/u', $emergencyName)) {
                $errors[] = 'Emergency Contact Person Name must contain only letters, spaces, or hyphens (2 to 100 characters).';
            }
        }

        // 3. Philippine Mobile Phone Numbers (11 digits, starting with 09)
        $phonePattern = '/^09\d{9}$/';
        
        if (!empty($input['contact_no']) && trim($input['contact_no']) !== '') {
            $contactNo = trim($input['contact_no']);
            if (!preg_match($phonePattern, $contactNo)) {
                $errors[] = 'Primary Contact No. must be a valid 11-digit Philippine mobile number starting with 09 (e.g. 09998698088).';
            }
        }

        if (!empty($input['emergency_no']) && trim($input['emergency_no']) !== '') {
            $emergencyNo = trim($input['emergency_no']);
            if (!preg_match($phonePattern, $emergencyNo)) {
                $errors[] = 'Emergency Contact Number must be a valid 11-digit Philippine mobile number starting with 09 (e.g. 09998698088).';
            }
        }

        // 4. Date of Birth Bounds & Format Validation
        if (!empty($input['dob'])) {
            $dob = trim($input['dob']);
            $d = \DateTime::createFromFormat('Y-m-d', $dob);
            if (!$d || $d->format('Y-m-d') !== $dob) {
                $errors[] = 'Date of Birth must be a valid date in YYYY-MM-DD format.';
            } else {
                $dobTimestamp = strtotime($dob);
                $now = time();
                $minDate = strtotime('1900-01-01');

                if ($dobTimestamp > $now) {
                    $errors[] = 'Date of Birth cannot be a future date.';
                } elseif ($dobTimestamp < $minDate) {
                    $errors[] = 'Date of Birth cannot be prior to 1900.';
                }
            }
        }

        // 5. PhilHealth ID Number Validation (XX-XXXXXXXXX-X format)
        if (!empty($input['philhealth_no']) && trim($input['philhealth_no']) !== '') {
            $philhealthNo = trim($input['philhealth_no']);
            if (!preg_match('/^\d{2}-\d{9}-\d{1}$/', $philhealthNo)) {
                $errors[] = 'PhilHealth ID No. must follow the standard 12-digit format: XX-XXXXXXXXX-X (e.g. 12-345678901-2).';
            } elseif (!$this->patientModel->isPhilHealthUnique($philhealthNo, $excludePatientId)) {
                $errors[] = 'PhilHealth ID number is already registered to another active patient.';
            }
        }

        // 6. Biological Sex Whitelist
        if (!empty($input['sex'])) {
            if (!in_array($input['sex'], ['Male', 'Female'], true)) {
                $errors[] = 'Invalid Biological Sex selected.';
            }
        }

        // 7. Civil Status Whitelist
        if (!empty($input['civil_status'])) {
            $allowedStatuses = ['Single', 'Married', 'Widowed', 'Divorced', 'Separated'];
            if (!in_array($input['civil_status'], $allowedStatuses, true)) {
                $errors[] = 'Invalid Civil Status selected.';
            }
        }

        // 8. Blood Type Whitelist
        if (!empty($input['blood_type'])) {
            $allowedBloodTypes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'Unknown'];
            if (!in_array($input['blood_type'], $allowedBloodTypes, true)) {
                $errors[] = 'Invalid Blood Type selected.';
            }
        }

        // 9. Barangay & Address Length
        if (!empty($input['barangay'])) {
            $barangay = trim($input['barangay']);
            if (mb_strlen($barangay) < 2 || mb_strlen($barangay) > 100) {
                $errors[] = 'Barangay name must be between 2 and 100 characters.';
            }
        }

        if (!empty($input['address'])) {
            $address = trim($input['address']);
            if (mb_strlen($address) < 5 || mb_strlen($address) > 500) {
                $errors[] = 'Complete Address must be between 5 and 500 characters.';
            }
        }

        // 10. Suffix Validation
        if (!empty($input['suffix']) && trim($input['suffix']) !== '') {
            $suffix = trim($input['suffix']);
            if (mb_strlen($suffix) > 20 || !preg_match('/^[a-zA-Z0-9\.\s\-]+$/', $suffix)) {
                $errors[] = 'Name Suffix must be 20 characters or less (e.g. Jr., Sr., III).';
            }
        }

        // 11. Family Number Format
        if (!empty($input['family_no']) && trim($input['family_no']) !== '') {
            $familyNo = trim($input['family_no']);
            if (mb_strlen($familyNo) > 50 || !preg_match('/^[a-zA-Z0-9\-\_\s\.]+$/', $familyNo)) {
                $errors[] = 'Family Number must be alphanumeric and under 50 characters.';
            }
        }

        // 12. Educational Attainment Whitelist
        if (!empty($input['education_attainment']) && trim($input['education_attainment']) !== '') {
            $allowedEdu = ['No Schooling', 'Elementary', 'High School', 'Vocational', 'College / Post-Graduate'];
            if (!in_array($input['education_attainment'], $allowedEdu, true)) {
                $errors[] = 'Invalid Educational Attainment selected.';
            }
        }

        // 13. PhilHealth Membership Status
        if (!empty($input['phic_status'])) {
            $allowedPhicStatus = ['Member', 'Dependent', 'Non-Member'];
            if (!in_array($input['phic_status'], $allowedPhicStatus, true)) {
                $errors[] = 'Invalid PhilHealth Status selected.';
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
                if (mb_strlen($val) > 150 || !preg_match('/^[a-zA-ZñÑ\s\-\'\.]{2,150}$/u', $val)) {
                    $errors[] = "{$fieldLabel} must contain only letters, spaces, hyphens, or apostrophes (2 to 150 characters).";
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
                $d = \DateTime::createFromFormat('Y-m-d', $dobVal);
                if (!$d || $d->format('Y-m-d') !== $dobVal) {
                    $errors[] = "{$fieldLabel} must be a valid date in YYYY-MM-DD format.";
                } elseif (strtotime($dobVal) > time()) {
                    $errors[] = "{$fieldLabel} cannot be a future date.";
                }
            }
        }

        return $errors;
    }

    /**
     * Archive (soft-delete) a patient record.
     */
    public function archive($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $patient = $this->patientModel->findById($id);
        if (!$patient) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        // Validate archive reason
        $reason = $_POST['archive_reason'] ?? '';
        if (empty(trim($reason))) {
            $_SESSION['error_message'] = 'Archive reason is required.';
            $this->redirect("/patients/{$id}");
            return;
        }

        $userId = $_SESSION['user_id'];
        if ($this->patientModel->archive($id, $userId, $reason)) {
            AuditLog::log('PATIENT_ARCHIVED', 'Patients', "Archived patient: {$patient['first_name']} {$patient['last_name']} ({$patient['patient_no']}). Reason: {$reason}");
            $_SESSION['success_message'] = 'Patient record archived successfully.';
            $this->redirect('/patients');
        } else {
            $_SESSION['error_message'] = 'Failed to archive patient. Please try again.';
            $this->redirect("/patients/{$id}");
        }
    }

    /**
     * Display a list of archived patients.
     */
    public function archivedIndex() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'date_from' => trim($_GET['date_from'] ?? ''),
            'date_to' => trim($_GET['date_to'] ?? '')
        ];

        $archivedPatients = $this->patientModel->allArchived($filters);

        $this->view('archive/patients', [
            'patients' => $archivedPatients,
            'filters' => $filters
        ]);
    }

    /**
     * Restore an archived patient record.
     */
    public function restore($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Retrieve patient details (even if soft-deleted)
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM patients WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
            $patient = $stmt->fetch();
        } catch (\Exception $e) {
            $patient = false;
        }

        if (!$patient) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        if ($this->patientModel->restore($id)) {
            AuditLog::log('PATIENT_RESTORED', 'Patients', "Restored patient: {$patient['first_name']} {$patient['last_name']} ({$patient['patient_no']})");
            $_SESSION['success_message'] = 'Patient record restored successfully.';
            $this->redirect('/patients');
        } else {
            $_SESSION['error_message'] = 'Failed to restore patient. Please try again.';
            $this->redirect('/archive/patients');
        }
    }
}

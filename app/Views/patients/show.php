<?php
/**
 * Clinical Care Workstation - Patient Profile Orchestrator
 *
 * @var array $patient Patient demographic record
 * @var array $vitalsHistory Vital signs history
 * @var array|false $latestVitals Latest vital signs
 * @var array $consultationsHistory Consultation records
 * @var array|null $latestConsultation Latest clinical consultation encounter
 * @var array $latestConsultationPrescriptions Prescriptions attached to latest consultation
 * @var array $appointmentsHistory Appointment history
 * @var array $queueHistory Daily queue history logs
 * @var array|false $medicalHistory Annex A1 IHP Medical History
 * @var array $familyMembers Household members sharing same family_no
 * @var array|false $activePrenatal Active pregnancy episode
 * @var array $prenatalVisits Follow-up prenatal visits
 * @var array $pastDeliveries Past obstetric delivery histories
 * @var array $allPrenatalEpisodes All historical pregnancy episodes
 * @var array|false $wellbabyRecord Well Baby child profile
 * @var array $growthLogs Pediatric growth anthropometrics logs
 * @var array $patientImmunizations All immunizations administered
 * @var array $vaccineMap Vaccine name+dose lookup map
 * @var array $potentialMothers Potential mother profiles in directory
 * @var array $programBadge Program classification badge
 * @var int $pcbYear PhilHealth PCB active filter year
 * @var array $pcbObligated PhilHealth PCB obligated preventive services status
 * @var array $pcbServiceLogs PhilHealth PCB diagnostic encounters
 * @var array $cdsAlerts Clinical decision support alert flags
 */

$title = 'Patient Profile';
$breadcrumbs = [
    'Patients' => '/patients',
    'Profile' => null
];
require dirname(__DIR__) . '/layout/header.php';

$patientSex = !empty($patient['sex']) ? trim($patient['sex']) : '';
$isFemale = (strtolower($patientSex) === 'female');
$hasNumericAge = (isset($patient['age']) && $patient['age'] !== '' && is_numeric($patient['age']));
$isChild = ($hasNumericAge && (int)$patient['age'] <= 5) || !empty($wellbabyRecord);

// Robust patient full name formatting
$lastName = trim($patient['last_name'] ?? '');
$firstName = trim($patient['first_name'] ?? '');
$middleName = trim($patient['middle_name'] ?? '');
$suffix = trim($patient['suffix'] ?? '');

if (!empty($lastName) && !empty($firstName)) {
    $fullNameFormatted = $lastName . ', ' . $firstName;
    if (!empty($middleName)) {
        $fullNameFormatted .= ' ' . mb_substr($middleName, 0, 1) . '.';
    }
    if (!empty($suffix)) {
        $fullNameFormatted .= ' ' . $suffix;
    }
} elseif (!empty($lastName)) {
    $fullNameFormatted = $lastName . (!empty($suffix) ? ' ' . $suffix : '');
} elseif (!empty($firstName)) {
    $fullNameFormatted = $firstName . (!empty($suffix) ? ' ' . $suffix : '');
} else {
    $fullNameFormatted = 'Unnamed Patient';
}

// Avatar Initials or fallback icon
$avatarInitials = '';
if (!empty($firstName) || !empty($lastName)) {
    $avatarInitials = strtoupper(
        (!empty($firstName) ? mb_substr($firstName, 0, 1) : '') .
        (!empty($lastName) ? mb_substr($lastName, 0, 1) : '')
    );
}

$formatDateSafe = function($dateStr, $format = 'M d, Y') {
    if (empty($dateStr) || $dateStr === '0000-00-00' || $dateStr === '0000-00-00 00:00:00') {
        return null;
    }
    $ts = strtotime($dateStr);
    return ($ts !== false && $ts > 0) ? date($format, $ts) : null;
};

// Valid Date of Birth check
$dobFormatted = $formatDateSafe($patient['dob'] ?? null) ?? 'Unspecified';
$isValidDob = ($formatDateSafe($patient['dob'] ?? null) !== null);

$ageVal = ($hasNumericAge) ? h($patient['age']) : null;
$hasBloodType = (!empty($patient['blood_type']) && strtolower(trim($patient['blood_type'])) !== 'unknown');
$curRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'staff';
?>

<!-- ==========================================================================
   PATIENT MASTER HEADER & ACTION BAR
   ========================================================================== -->
<?php require __DIR__ . '/partials/patient_header.php'; ?>

<!-- ==========================================================================
   CLINICAL WORKSTATION (FULL 100% WIDTH MODULAR TABS)
   ========================================================================== -->
<div class="card card-premium shadow-sm border-0 mb-5">
    
    <!-- Sleek Single-Line Tab Bar -->
    <div class="card-header bg-white p-0 border-0 position-relative workstation-tabs-wrapper">
        <button type="button" class="workstation-tab-scroll-btn scroll-prev" id="tabScrollPrev" title="Scroll left" aria-label="Scroll tabs left">
            <i class="bi bi-chevron-left"></i>
        </button>
        <ul class="nav nav-tabs m-0 border-0 flex-nowrap" id="workstationTabs" role="tablist">
            <!-- Tab 1: Overview -->
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-semibold text-nowrap" id="tab-overview-btn" data-bs-toggle="tab" data-bs-target="#tab-overview" type="button" role="tab">
                    Overview
                </button>
            </li>
                    
            <!-- Tab 2: IHP Medical History -->
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold text-nowrap" id="tab-ihp-btn" data-bs-toggle="tab" data-bs-target="#tab-ihp" type="button" role="tab">
                    IHP History
                </button>
            </li>

            <!-- Tab 3: PHIC / PCB Ledger (Page 3) -->
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold text-nowrap" id="tab-pcb-btn" data-bs-toggle="tab" data-bs-target="#tab-pcb" type="button" role="tab">
                    PHIC / PCB Ledger
                    <?php if (!empty($pcbServiceLogs)): ?>
                        <span class="badge bg-light text-secondary border ms-1"><?= count($pcbServiceLogs) ?></span>
                    <?php endif; ?>
                </button>
            </li>

            <!-- Tab 4: Clinical Consultation Ledger -->
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold text-nowrap" id="tab-consultations-btn" data-bs-toggle="tab" data-bs-target="#tab-consultations" type="button" role="tab">
                    Consultations
                    <span class="badge bg-light text-secondary border ms-1"><?= count($consultationsHistory) ?></span>
                </button>
            </li>

            <!-- Tab 5: Vital Signs History -->
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold text-nowrap" id="tab-vitals-btn" data-bs-toggle="tab" data-bs-target="#tab-vitals" type="button" role="tab">
                    Vitals Log
                    <span class="badge bg-light text-secondary border ms-1"><?= count($vitalsHistory) ?></span>
                </button>
            </li>

            <!-- Tab 6: Universal Immunizations -->
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold text-nowrap" id="tab-immunizations-btn" data-bs-toggle="tab" data-bs-target="#tab-immunizations" type="button" role="tab">
                    Immunizations
                    <span class="badge bg-light text-secondary border ms-1"><?= count($patientImmunizations) ?></span>
                </button>
            </li>

            <!-- Tab 7: Appointments & Queue -->
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold text-nowrap" id="tab-appointments-btn" data-bs-toggle="tab" data-bs-target="#tab-appointments" type="button" role="tab">
                    Appointments
                </button>
            </li>
        </ul>
        <button type="button" class="workstation-tab-scroll-btn scroll-next" id="tabScrollNext" title="Scroll right" aria-label="Scroll tabs right">
            <i class="bi bi-chevron-right"></i>
        </button>
    </div>

    <!-- Tab Panels Body -->
    <div class="card-body p-4">
        <div class="tab-content" id="workstationTabsContent">
            <!-- Tab 1: Overview & Clinical Snapshot -->
            <?php require __DIR__ . '/partials/tab_overview.php'; ?>

            <!-- Tab 2: Annex A1 Individual Health Profile (IHP) -->
            <?php require __DIR__ . '/partials/tab_ihp.php'; ?>

            <!-- Tab 3: PHIC / PCB Patient Ledger -->
            <?php require __DIR__ . '/partials/tab_pcb.php'; ?>

            <!-- Tab 4: Clinical Consultation Ledger -->
            <?php require __DIR__ . '/partials/tab_consultations.php'; ?>

            <!-- Tab 5: Vital Signs History Log -->
            <?php require __DIR__ . '/partials/tab_vitals.php'; ?>

            <!-- Tab 6: Universal Immunizations Record -->
            <?php require __DIR__ . '/partials/tab_immunizations.php'; ?>

            <!-- Tab 7: Scheduled Appointments & Queue Tickets -->
            <?php require __DIR__ . '/partials/tab_appointments.php'; ?>
        </div>
    </div>
</div>

<!-- ==========================================================================
   WORKSTATION ACTION MODALS & FORMS
   ========================================================================== -->
<?php require __DIR__ . '/partials/modals.php'; ?>

<!-- ==========================================================================
   CLIENT-SIDE CONTROLLER SCRIPTS
   ========================================================================== -->
<?php require __DIR__ . '/partials/scripts.php'; ?>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
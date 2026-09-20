<?php
/**
 * @var array $patient Patient demographic record
 * @var array $vitalsHistory Vital signs history
 * @var array|false $latestVitals Latest vital signs
 * @var array $consultationsHistory Consultation records
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
?>

<!-- ==========================================================================
   PAGE HEADER (Title, Subtitle & Action)
   ========================================================================== -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div class="min-w-0">
        <h2 class="h3 mb-1 fw-bold text-primary-dark">Clinical Care Workstation</h2>
        <p class="text-secondary small mb-0">Manage check-ups, PhilHealth IHP profile, maternal/child care, vitals, and appointments.</p>
    </div>
    <a href="<?= url('/patients') ?>" class="btn btn-outline-secondary text-nowrap flex-shrink-0">
        <i class="bi bi-arrow-left me-1"></i> Back to Directory
    </a>
</div>

<!-- ==========================================================================
   PATIENT MASTER HEADER (Compact Universal Identity & Actions Banner)
   ========================================================================== -->
<div class="card card-premium shadow-sm border-0 mb-3">
    <div class="card-body p-3">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
            
            <!-- Left: Avatar, Name, Badges & Baseline Demographics -->
            <div class="d-flex align-items-center gap-3 min-w-0 flex-grow-1">
                <div class="avatar-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center rounded-circle shadow-xs flex-shrink-0" style="width: 48px; height: 48px; font-size: 1.15rem;">
                    <?php if (!empty($avatarInitials)): ?>
                        <?= h($avatarInitials) ?>
                    <?php else: ?>
                        <i class="bi bi-person-fill fs-4"></i>
                    <?php endif; ?>
                </div>

                <div class="min-w-0">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <h3 class="h5 fw-bold text-primary-dark mb-0 lh-1">
                            <?php if ($fullNameFormatted === 'Unnamed Patient'): ?>
                                <span class="text-muted fst-italic">Unnamed Patient</span>
                            <?php else: ?>
                                <?= h($fullNameFormatted) ?>
                            <?php endif; ?>
                        </h3>
                        <span class="badge bg-light text-dark border font-monospace fs-7">
                            <?= h($patient['patient_no']) ?>
                        </span>
                        <?php if (!empty($patient['envelope_no'])): ?>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace fs-7" title="Physical Logbook Envelope No.">
                                <i class="bi bi-folder2-open me-1"></i>Env #<?= h($patient['envelope_no']) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php
                    $demographicsItems = [];
                    if ($ageVal !== null && $patientSex !== '') {
                        $demographicsItems[] = '<span><strong>' . $ageVal . '</strong> yrs &bull; ' . h($patientSex) . '</span>';
                    } elseif ($ageVal !== null) {
                        $demographicsItems[] = '<span><strong>' . $ageVal . '</strong> yrs</span>';
                    } elseif ($patientSex !== '') {
                        $demographicsItems[] = '<span>' . h($patientSex) . '</span>';
                    }
                    $demographicsItems[] = '<span>DOB: <strong class="' . ($isValidDob ? 'text-dark' : 'text-muted') . '">' . h($dobFormatted) . '</strong></span>';
                    if ($hasBloodType) {
                        $demographicsItems[] = '<span>Blood: <strong class="text-danger">' . h($patient['blood_type']) . '</strong></span>';
                    } else {
                        $demographicsItems[] = '<span>Blood: <span class="text-muted">Unknown</span></span>';
                    }
                    if (!empty($patient['family_no'])) {
                        $demographicsItems[] = '<a href="' . url('/patients?search=' . urlencode($patient['family_no'])) . '" class="text-decoration-none text-secondary" title="View household in directory"><i class="bi bi-house-door-fill text-primary me-1"></i>Fam # ' . h($patient['family_no']) . '</a>';
                    }
                    if (!empty($patient['philhealth_no'])) {
                        $demographicsItems[] = '<span>PHIC: <span class="font-monospace text-dark">' . h($patient['philhealth_no']) . '</span></span>';
                    }
                    ?>
                    <div class="d-flex flex-wrap align-items-center gap-2 small text-secondary">
                        <?= implode('<span class="text-muted">&bull;</span>', $demographicsItems) ?>
                    </div>

                    <?php if (!empty($cdsAlerts['has_alerts'])): ?>
                        <div class="d-flex flex-wrap align-items-center gap-2 mt-2 pt-2 border-top small">
                            <span class="text-muted small fw-semibold me-1"><i class="bi bi-shield-exclamation text-danger me-1"></i>Clinical Alerts:</span>
                            <?php foreach ($cdsAlerts['flags'] as $flag): ?>
                                <span class="badge <?= $flag['class'] ?> px-2 py-1">
                                    <i class="bi <?= $flag['icon'] ?> me-1"></i><?= h($flag['label']) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right: Patient-Level Action Controls -->
            <div class="d-flex flex-wrap align-items-center gap-2 flex-shrink-0 ms-auto ms-lg-0">
                <a href="<?= url('/patients/' . $patient['id'] . '/edit') ?>" class="btn btn-outline-primary btn-sm d-flex align-items-center px-3 py-1.5 shadow-xs text-nowrap" title="Edit Patient Identity & Demographics">
                    <i class="bi bi-pencil-square me-1"></i> Edit Profile
                </a>
                <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm d-flex align-items-center px-3 py-1.5 shadow-xs text-nowrap" title="Print Patient Profile">
                    <i class="bi bi-printer me-1"></i> Print
                </button>
                <?php if (is_admin()): ?>
                    <button type="button" class="btn btn-outline-danger btn-sm d-flex align-items-center px-3 py-1.5 shadow-xs text-nowrap" data-bs-toggle="modal" data-bs-target="#archivePatientModal" title="Archive Patient Record">
                        <i class="bi bi-archive me-1"></i> Archive
                    </button>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>


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
                    <i class="bi bi-grid-1x2-fill me-1"></i> Overview
                </button>
            </li>
                    
                    <!-- Tab 2: IHP Medical History -->
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold text-nowrap" id="tab-ihp-btn" data-bs-toggle="tab" data-bs-target="#tab-ihp" type="button" role="tab">
                            <i class="bi bi-file-earmark-medical-fill me-1"></i> IHP History
                        </button>
                    </li>

                    <!-- Tab: PHIC / PCB Ledger (Page 3) -->
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold text-nowrap" id="tab-pcb-btn" data-bs-toggle="tab" data-bs-target="#tab-pcb" type="button" role="tab">
                            <i class="bi bi-card-checklist me-1"></i> PHIC / PCB Ledger
                            <?php if (!empty($pcbServiceLogs)): ?>
                                <span class="badge bg-light text-secondary border ms-1"><?= count($pcbServiceLogs) ?></span>
                            <?php endif; ?>
                        </button>
                    </li>

                    <!-- Tab 3: Consultations (SOAP) -->
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold text-nowrap" id="tab-consultations-btn" data-bs-toggle="tab" data-bs-target="#tab-consultations" type="button" role="tab">
                            <i class="bi bi-clipboard2-pulse-fill me-1"></i> Consultations
                            <span class="badge bg-light text-secondary border ms-1"><?= count($consultationsHistory) ?></span>
                        </button>
                    </li>

                    <!-- Tab 4: Vital Signs History -->
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold text-nowrap" id="tab-vitals-btn" data-bs-toggle="tab" data-bs-target="#tab-vitals" type="button" role="tab">
                            <i class="bi bi-activity me-1"></i> Vitals Log
                            <span class="badge bg-light text-secondary border ms-1"><?= count($vitalsHistory) ?></span>
                        </button>
                    </li>

                    <!-- Tab 5: Universal Immunizations -->
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold text-nowrap" id="tab-immunizations-btn" data-bs-toggle="tab" data-bs-target="#tab-immunizations" type="button" role="tab">
                            <i class="bi bi-shield-plus me-1"></i> Immunizations
                            <span class="badge bg-light text-secondary border ms-1"><?= count($patientImmunizations) ?></span>
                        </button>
                    </li>



                    <!-- Tab 8: Appointments & Queue -->
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold text-nowrap" id="tab-appointments-btn" data-bs-toggle="tab" data-bs-target="#tab-appointments" type="button" role="tab">
                            <i class="bi bi-calendar3 me-1"></i> Appointments
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
                    
                    <!-- ==============================================================
                       TAB 1: OVERVIEW & CLINICAL SNAPSHOT
                       ============================================================== -->
                    <div class="tab-pane fade show active" id="tab-overview" role="tabpanel">
                        <div class="row g-3">
                            
                            <!-- 1. Demographic & Civil Profile Card -->
                            <div class="col-12 col-lg-6">
                                <div class="card border rounded-3 h-100 shadow-xs">
                                    <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                                        <h5 class="h6 mb-0 fw-bold text-dark">
                                            <i class="bi bi-person-lines-fill text-primary me-2"></i>Demographic & Civil Profile
                                        </h5>
                                        <a href="<?= url('/patients/' . $patient['id'] . '/edit') ?>" class="btn btn-xs btn-outline-primary py-1 px-2" title="Edit Demographics">
                                            <i class="bi bi-pencil me-1"></i> Edit
                                        </a>
                                    </div>
                                    <div class="card-body p-3 small">
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">Civil Status</span>
                                                <span class="fw-semibold text-dark">
                                                    <?= ($patient['civil_status'] === 'Others' && !empty($patient['civil_status_other'])) ? 'Others (' . h($patient['civil_status_other']) . ')' : h($patient['civil_status']) ?>
                                                </span>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">Religion</span>
                                                <span class="text-dark"><?= h($patient['religion'] ?? 'Unspecified') ?></span>
                                            </div>
                                            <div class="col-12 border-top pt-2 mt-1">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">Residential Address</span>
                                                <?php 
                                                $addressParts = array_filter([
                                                    trim($patient['address'] ?? ''),
                                                    !empty($patient['barangay']) ? 'Brgy. ' . trim($patient['barangay']) : ''
                                                ]);
                                                $fullAddress = !empty($addressParts) ? implode(', ', $addressParts) : 'None registered';
                                                ?>
                                                <span class="fw-medium text-dark"><i class="bi bi-geo-alt text-secondary me-1"></i><?= h($fullAddress) ?></span>
                                            </div>
                                            <div class="col-12 border-top pt-2 mt-1">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">Contact Number</span>
                                                <?php if (!empty($patient['contact_no'])): ?>
                                                    <span class="font-monospace fw-semibold text-dark"><i class="bi bi-telephone text-secondary me-1"></i><a href="tel:<?= h($patient['contact_no']) ?>" class="text-decoration-none text-dark"><?= h($patient['contact_no']) ?></a></span>
                                                <?php else: ?>
                                                    <span class="text-muted fst-italic"><i class="bi bi-telephone text-muted me-1"></i>None registered</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-6 border-top pt-2 mt-1">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">PhilHealth Status</span>
                                                <span class="badge bg-light text-dark border"><?= h($patient['phic_status'] ?? 'Non-Member') ?></span>
                                                <?php if (!empty($patient['phic_type'])): ?>
                                                    <span class="text-muted d-block" style="font-size: 0.7rem;"><?= h($patient['phic_type']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-6 border-top pt-2 mt-1">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">PhilHealth Identification No. (PIN)</span>
                                                <span class="font-monospace text-dark"><?= h($patient['philhealth_no'] ?? 'Unregistered') ?></span>
                                            </div>
                                            <div class="col-6 border-top pt-2 mt-1">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">Educational Attainment</span>
                                                <span class="text-dark"><?= h($patient['education_attainment'] ?? 'Unspecified') ?></span>
                                            </div>
                                            <div class="col-6 border-top pt-2 mt-1">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">Occupation</span>
                                                <span class="text-dark"><?= h($patient['occupation'] ?? 'Unspecified') ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Emergency & Family Contacts Card -->
                            <div class="col-12 col-lg-6">
                                <div class="card border rounded-3 h-100 shadow-xs">
                                    <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                                        <h5 class="h6 mb-0 fw-bold text-dark">
                                            <i class="bi bi-person-exclamation text-danger me-2"></i>Immediate Family & Emergency Contacts
                                        </h5>
                                        <?php if (!empty($patient['family_no'])): ?>
                                            <span class="badge bg-light text-dark border">Fam # <?= h($patient['family_no']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body p-3 small">
                                        <div class="row g-2">
                                            <div class="col-12">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">Father's Full Name</span>
                                                <span class="text-dark">
                                                    <?= !empty($patient['father_name']) ? h($patient['father_name']) . ($formatDateSafe($patient['father_dob'] ?? null) ? ' <span class="text-muted small">(DOB: ' . $formatDateSafe($patient['father_dob']) . ')</span>' : '') : '<span class="text-muted">None on record</span>' ?>
                                                </span>
                                            </div>
                                            <div class="col-12 border-top pt-2 mt-1">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">Mother's Maiden Name</span>
                                                <span class="text-dark">
                                                    <?= !empty($patient['mother_name']) ? h($patient['mother_name']) . ($formatDateSafe($patient['mother_dob'] ?? null) ? ' <span class="text-muted small">(DOB: ' . $formatDateSafe($patient['mother_dob']) . ')</span>' : '') : '<span class="text-muted">None on record</span>' ?>
                                                </span>
                                            </div>
                                            <div class="col-12 border-top pt-2 mt-1">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">Spouse's Full Name</span>
                                                <span class="text-dark">
                                                    <?= !empty($patient['spouse_name']) ? h($patient['spouse_name']) . ($formatDateSafe($patient['spouse_dob'] ?? null) ? ' <span class="text-muted small">(DOB: ' . $formatDateSafe($patient['spouse_dob']) . ')</span>' : '') : '<span class="text-muted">None on record</span>' ?>
                                                </span>
                                            </div>
                                            <div class="col-6 border-top pt-2 mt-1">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">Emergency Contact</span>
                                                <span class="fw-semibold text-dark"><?= h($patient['emergency_name'] ?? 'None registered') ?></span>
                                            </div>
                                            <div class="col-6 border-top pt-2 mt-1">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">Relationship</span>
                                                <span class="text-dark"><?= h($patient['emergency_relationship'] ?? 'N/A') ?></span>
                                            </div>
                                            <div class="col-12 border-top pt-2 mt-1">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">Emergency Phone</span>
                                                <?php if (!empty($patient['emergency_no'])): ?>
                                                    <span class="font-monospace fw-semibold text-dark"><i class="bi bi-telephone-fill text-danger me-1"></i><a href="tel:<?= h($patient['emergency_no']) ?>" class="text-decoration-none text-dark"><?= h($patient['emergency_no']) ?></a></span>
                                                <?php else: ?>
                                                    <span class="text-muted fst-italic"><i class="bi bi-telephone text-muted me-1"></i>None provided</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-12 border-top pt-2 mt-1">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">Household Code</span>
                                                <?php if (!empty($patient['family_no'])): ?>
                                                    <a href="<?= url('/patients?search=' . urlencode($patient['family_no'])) ?>" class="text-decoration-none fw-medium text-primary">
                                                        <i class="bi bi-house-door me-1"></i>Family Group # <?= h($patient['family_no']) ?> (Click to view members)
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">No household number assigned</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Latest Vital Signs Snapshot Card (Full Width) -->
                            <div class="col-12">
                                <div class="card border rounded-3 shadow-xs">
                                    <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                                        <h5 class="h6 mb-0 fw-bold text-dark">
                                            <i class="bi bi-heart-pulse text-danger me-2"></i>Latest Vital Signs Snapshot
                                        </h5>
                                        <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" data-bs-toggle="modal" data-bs-target="#addVitalsModal">
                                            <i class="bi bi-plus-lg me-1"></i> Record Vitals
                                        </button>
                                    </div>
                                    <div class="card-body p-3">
                                        <?php if ($latestVitals): ?>
                                            <div class="row g-2 text-center mb-2">
                                                <!-- BP -->
                                                <div class="col-6 col-sm-4 col-md-2">
                                                    <div class="p-2 bg-light rounded border">
                                                        <span class="text-muted d-block small" style="font-size: 0.75rem;">Blood Pressure</span>
                                                        <span class="fw-bold fs-6 text-dark"><?= h($latestVitals['bp_systolic'] ?? '--') ?>/<?= h($latestVitals['bp_diastolic'] ?? '--') ?></span>
                                                        <span class="text-muted d-block small" style="font-size: 0.65rem;">mmHg</span>
                                                    </div>
                                                </div>

                                                <!-- Pulse -->
                                                <div class="col-6 col-sm-4 col-md-2">
                                                    <div class="p-2 bg-light rounded border">
                                                        <span class="text-muted d-block small" style="font-size: 0.75rem;">Heart Rate</span>
                                                        <span class="fw-bold fs-6 text-dark"><?= h($latestVitals['heart_rate'] ?? '--') ?></span>
                                                        <span class="text-muted d-block small" style="font-size: 0.65rem;">bpm</span>
                                                    </div>
                                                </div>

                                                <!-- Temp -->
                                                <div class="col-6 col-sm-4 col-md-2">
                                                    <div class="p-2 bg-light rounded border">
                                                        <span class="text-muted d-block small" style="font-size: 0.75rem;">Temperature</span>
                                                        <span class="fw-bold fs-6 text-dark"><?= h($latestVitals['temperature'] ?? '--') ?></span>
                                                        <span class="text-muted d-block small" style="font-size: 0.65rem;">°C</span>
                                                    </div>
                                                </div>

                                                <!-- Weight / Height -->
                                                <div class="col-6 col-sm-4 col-md-2">
                                                    <div class="p-2 bg-light rounded border">
                                                        <span class="text-muted d-block small" style="font-size: 0.75rem;">Weight / Height</span>
                                                        <span class="fw-bold fs-6 text-dark"><?= h($latestVitals['weight'] ?? '--') ?> kg</span>
                                                        <span class="text-muted d-block small" style="font-size: 0.65rem;"><?= h($latestVitals['height'] ?? '--') ?> cm</span>
                                                    </div>
                                                </div>

                                                <!-- BMI -->
                                                <div class="col-6 col-sm-4 col-md-2">
                                                    <div class="p-2 bg-light rounded border">
                                                        <span class="text-muted d-block small" style="font-size: 0.75rem;">BMI</span>
                                                        <span class="fw-bold fs-6 text-primary"><?= h($latestVitals['bmi'] ?? '--') ?></span>
                                                        <span class="text-muted d-block small" style="font-size: 0.65rem;">
                                                            <?php 
                                                             $bmi = (float)($latestVitals['bmi'] ?? 0);
                                                            if ($bmi > 0 && $bmi < 18.5) echo '<span class="text-warning">Underweight</span>';
                                                            elseif ($bmi >= 18.5 && $bmi <= 24.9) echo '<span class="text-success">Normal</span>';
                                                            elseif ($bmi >= 25.0 && $bmi <= 29.9) echo '<span class="text-warning">Overweight</span>';
                                                            elseif ($bmi >= 30.0) echo '<span class="text-danger">Obese</span>';
                                                            else echo '--';
                                                            ?>
                                                        </span>
                                                    </div>
                                                </div>

                                                <!-- Waist -->
                                                <div class="col-6 col-sm-4 col-md-2">
                                                    <div class="p-2 bg-light rounded border">
                                                        <span class="text-muted d-block small" style="font-size: 0.75rem;">Waistline</span>
                                                        <span class="fw-bold fs-6 text-dark"><?= h($latestVitals['waist_circumference'] ?? '--') ?></span>
                                                        <span class="text-muted d-block small" style="font-size: 0.65rem;">cm</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="text-muted small d-flex justify-content-between pt-1" style="font-size: 0.75rem;">
                                                <span>Recorded: <strong><?= date('M d, Y h:i A', strtotime($latestVitals['recorded_at'])) ?></strong></span>
                                                <span>Recorded By: <?= h($latestVitals['recorder_name'] ?? 'Clinician') ?></span>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center py-4 text-muted">
                                                <div class="mb-2">
                                                    <i class="bi bi-heart-pulse fs-2 text-secondary opacity-50"></i>
                                                </div>
                                                <p class="mb-2 fw-medium text-secondary">No vital signs recorded yet for this patient.</p>
                                                <button type="button" class="btn btn-sm btn-outline-primary shadow-xs px-3" data-bs-toggle="modal" data-bs-target="#addVitalsModal">
                                                    <i class="bi bi-plus-circle me-1"></i> Record First Vital Signs
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- 4. Household Members Card -->
                            <div class="col-12 col-md-6">
                                <div class="card border rounded-3 h-100 shadow-xs">
                                    <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                                        <h5 class="h6 mb-0 fw-bold text-dark">
                                            <i class="bi bi-people-fill text-primary me-2"></i>Household Members
                                        </h5>
                                        <?php if (!empty($patient['family_no'])): ?>
                                            <span class="badge bg-light text-dark border small">Family # <?= h($patient['family_no']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body p-3 small">
                                        <?php if (!empty($familyMembers)): ?>
                                            <div class="list-group list-group-flush">
                                                <?php foreach ($familyMembers as $member): ?>
                                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                                        <div>
                                                            <i class="bi bi-person me-1 text-primary"></i>
                                                            <strong><?= h($member['last_name']) ?>, <?= h($member['first_name']) ?> <?= h($member['suffix'] ?? '') ?></strong>
                                                            <span class="text-muted ms-1">(<?= h($member['age']) ?> yrs / <?= h($member['sex']) ?>)</span>
                                                        </div>
                                                        <a href="<?= url('/patients/' . $member['id']) ?>" class="btn btn-xs btn-outline-primary py-1 px-2">
                                                            View Profile <i class="bi bi-arrow-right"></i>
                                                        </a>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center py-3 text-muted">
                                                <i class="bi bi-house-door fs-3 text-secondary opacity-50 d-block mb-1"></i>
                                                <span class="text-secondary small">No other relatives registered under this household code.</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- 5. IHP Summary Card -->
                            <div class="col-12 col-md-6">
                                <div class="card border rounded-3 h-100 shadow-xs">
                                    <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                                        <h5 class="h6 mb-0 fw-bold text-dark">
                                            <i class="bi bi-clipboard2-check text-primary me-2"></i>IHP Health Summary
                                        </h5>
                                        <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" onclick="editIhpFromOverview();">
                                            <i class="bi bi-pencil me-1"></i> Edit IHP
                                        </button>
                                    </div>
                                    <div class="card-body p-3 small">
                                        <?php if ($medicalHistory): ?>
                                            <div class="mb-2">
                                                <strong>Past Illnesses:</strong> 
                                                <?php 
                                                $overviewPmhList = [];
                                                if (!empty($medicalHistory['past_medical_history']) && is_array($medicalHistory['past_medical_history'])) {
                                                    foreach ($medicalHistory['past_medical_history'] as $cond => $det) {
                                                        if (empty($cond)) continue;
                                                        $overviewPmhList[] = !empty($det) ? "{$cond} ({$det})" : $cond;
                                                    }
                                                }
                                                ?>
                                                <?php if (!empty($overviewPmhList)): ?>
                                                    <span class="text-dark"><?= h(implode(', ', $overviewPmhList)) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">None declared.</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="mb-2">
                                                <strong>Social Habits:</strong> 
                                                <span class="text-dark">Smoking: <?= h($medicalHistory['smoking_status'] ?? 'Never') ?> &bull; Alcohol: <?= h($medicalHistory['alcohol_status'] ?? 'Never') ?></span>
                                            </div>
                                            <div>
                                                <strong>Family Heredity:</strong> 
                                                <?php 
                                                $overviewFamList = [];
                                                if (!empty($medicalHistory['family_history']) && is_array($medicalHistory['family_history'])) {
                                                    foreach ($medicalHistory['family_history'] as $cond => $det) {
                                                        if (empty($cond)) continue;
                                                        $lin = $medicalHistory['family_history_lineage'][$cond] ?? null;
                                                        $linBadge = (!empty($lin) && $lin !== 'Unknown') ? " ({$lin})" : "";
                                                        $overviewFamList[] = !empty($det) ? "{$cond}{$linBadge}: {$det}" : "{$cond}{$linBadge}";
                                                    }
                                                }
                                                ?>
                                                <?php if (!empty($overviewFamList)): ?>
                                                    <span class="text-dark"><?= h(implode(', ', $overviewFamList)) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">None declared.</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-muted mb-0 py-2 text-center">
                                                No IHP medical history recorded. <a href="javascript:void(0)" onclick="editIhpFromOverview();">Complete IHP Form</a>.
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Maternal Care Workstation Quick Card (Only if registered in Maternal program) -->
                            <?php 
                            $hasMaternalRegistration = !empty($activePrenatal) || !empty($allPrenatalEpisodes);
                            $hasWellbabyRegistration = !empty($wellbabyRecord);
                            ?>
                            <?php if ($hasMaternalRegistration): ?>
                                <div class="col-12 <?= $hasWellbabyRegistration ? 'col-md-6' : '' ?>">
                                    <div class="card border rounded-3 h-100 shadow-xs">
                                        <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                                            <h5 class="h6 mb-0 fw-bold text-dark">
                                                <i class="bi bi-heart-pulse-fill text-pink me-2"></i>Maternal Care
                                            </h5>
                                            <a href="<?= url('/maternal/' . $patient['id']) ?>" class="btn btn-xs btn-outline-primary py-1 px-2">
                                                Open Workstation <i class="bi bi-arrow-right ms-1"></i>
                                            </a>
                                        </div>
                                        <div class="card-body p-3 small">
                                            <?php if ($activePrenatal): ?>
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <span class="badge bg-pink text-white"><i class="bi bi-heart-fill me-1"></i>Active Pregnancy Episode</span>
                                                    <span class="fw-bold text-pink"><?= h($activePrenatal['calculated_aog']['weeks'] ?? '--') ?> weeks AOG</span>
                                                </div>
                                                <div class="row g-2 text-muted">
                                                    <div class="col-6"><strong>LMP:</strong> <?= !empty($activePrenatal['lmp']) ? date('M d, Y', strtotime($activePrenatal['lmp'])) : 'N/A' ?></div>
                                                    <div class="col-6"><strong>EDC:</strong> <?= !empty($activePrenatal['edc']) ? date('M d, Y', strtotime($activePrenatal['edc'])) : 'N/A' ?></div>
                                                    <div class="col-6"><strong>Gravida/Para:</strong> G<?= h($activePrenatal['gravida'] ?? 1) ?> P<?= h($activePrenatal['para'] ?? 0) ?></div>
                                                    <div class="col-6"><strong>Trimester:</strong> <?= h($activePrenatal['calculated_aog']['trimester'] ?? '1st') ?></div>
                                                </div>
                                                <div class="mt-3">
                                                    <a href="<?= url('/maternal/' . $patient['id']) ?>" class="btn btn-sm btn-pink text-white w-100 shadow-xs">
                                                        <i class="bi bi-heart-pulse-fill me-1"></i> Open Maternal Workstation
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <span class="badge bg-secondary text-white"><i class="bi bi-clock-history me-1"></i>Concluded / Past Episodes</span>
                                                    <span class="text-muted"><?= count($allPrenatalEpisodes) ?> episode(s)</span>
                                                </div>
                                                <p class="text-muted mb-2">Patient has past maternal health and delivery records on file.</p>
                                                <a href="<?= url('/maternal/' . $patient['id']) ?>" class="btn btn-sm btn-outline-primary w-100 shadow-xs">
                                                    <i class="bi bi-journal-medical me-1"></i> Open Maternal Workstation
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Well-Baby & EPI Workstation Quick Card (Only if registered in Well-Baby program) -->
                            <?php if ($hasWellbabyRegistration): ?>
                                <div class="col-12 <?= $hasMaternalRegistration ? 'col-md-6' : '' ?>">
                                    <div class="card border rounded-3 h-100 shadow-xs">
                                        <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                                            <h5 class="h6 mb-0 fw-bold text-dark">
                                                <i class="bi bi-emoji-smile-fill text-success me-2"></i>Well-Baby &amp; EPI
                                            </h5>
                                            <a href="<?= url('/well-baby/' . $patient['id']) ?>" class="btn btn-xs btn-outline-success py-1 px-2">
                                                Open Workstation <i class="bi bi-arrow-right ms-1"></i>
                                            </a>
                                        </div>
                                        <div class="card-body p-3 small">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <span class="badge bg-success text-white"><i class="bi bi-check-circle me-1"></i>Registered Infant</span>
                                                <span class="text-muted"><?= count($growthLogs ?? []) ?> growth visits logged</span>
                                            </div>
                                            <div class="row g-2 text-muted">
                                                <div class="col-6"><strong>Birth Weight:</strong> <?= h($wellbabyRecord['birth_weight_kg'] ?? '--') ?> kg</div>
                                                <div class="col-6"><strong>Birth Length:</strong> <?= h($wellbabyRecord['birth_length_cm'] ?? '--') ?> cm</div>
                                                <div class="col-6"><strong>Delivery:</strong> <?= h($wellbabyRecord['place_of_delivery'] ?? '--') ?></div>
                                                <div class="col-6"><strong>NBS:</strong> <?= !empty($wellbabyRecord['newborn_screening_done']) ? 'Done' : 'Pending' ?></div>
                                            </div>
                                            <div class="mt-3">
                                                <a href="<?= url('/well-baby/' . $patient['id']) ?>" class="btn btn-sm btn-success text-white w-100 shadow-xs">
                                                    <i class="bi bi-emoji-smile-fill me-1"></i> Open Well-Baby Workstation
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- 6. Metadata Footer -->
                            <div class="col-12 text-center text-muted pt-2" style="font-size: 0.75rem;">
                                <span>Patient chart registered on <?= date('M d, Y \a\t h:i A', strtotime($patient['created_at'])) ?> <?= !empty($patient['creator_name']) ? 'by ' . h($patient['creator_name']) : '' ?></span>
                            </div>

                        </div>
                    </div>

                    <!-- ==============================================================
                       TAB 2: ANNEX A1 INDIVIDUAL HEALTH PROFILE (IHP) FORM
                       ============================================================== -->
                    <div class="tab-pane fade" id="tab-ihp" role="tabpanel">
                        <?php 
                        $pmhSaved = $medicalHistory['past_medical_history'] ?? [];
                        $familySaved = $medicalHistory['family_history'] ?? [];
                        $famLineageSaved = $medicalHistory['family_history_lineage'] ?? [];
                        $surgicalSaved = $medicalHistory['surgical_history'] ?? [];
                        $peSaved = $medicalHistory['physical_examination'] ?? [];
                        $immSaved = $medicalHistory['external_immunizations'] ?? [];

                        // Ensure JSON string arrays are decoded if they came as raw strings
                        if (is_string($pmhSaved)) {
                            $decoded = json_decode($pmhSaved, true);
                            $pmhSaved = is_array($decoded) ? $decoded : ($pmhSaved === '[]' || $pmhSaved === '{}' ? [] : [$pmhSaved]);
                        }
                        if (is_string($familySaved)) {
                            $decoded = json_decode($familySaved, true);
                            $familySaved = is_array($decoded) ? $decoded : ($familySaved === '[]' || $familySaved === '{}' ? [] : [$familySaved]);
                        }
                        if (is_string($surgicalSaved)) {
                            $decoded = json_decode($surgicalSaved, true);
                            $surgicalSaved = is_array($decoded) ? $decoded : ($surgicalSaved === '[]' || $surgicalSaved === '{}' ? [] : [$surgicalSaved]);
                        }
                        if (is_string($peSaved)) {
                            $decoded = json_decode($peSaved, true);
                            $peSaved = is_array($decoded) ? $decoded : [];
                        }
                        $peSaved = \App\Models\PatientMedicalHistory::normalizePhysicalExamination($peSaved);

                        if (is_string($immSaved)) {
                            $decoded = json_decode($immSaved, true);
                            $immSaved = is_array($decoded) ? $decoded : [];
                        }
                        $immSaved = \App\Models\PatientMedicalHistory::normalizeImmunizations($immSaved);

                        // Build display list for Past Medical History (Deduplicated)
                        $pmhDisplay = [];
                        $seenConditions = [];
                        if (!empty($pmhSaved) && is_array($pmhSaved)) {
                            foreach ($pmhSaved as $k => $v) {
                                $cond = (is_string($k) && !is_numeric($k)) ? trim($k) : trim((string)$v);
                                $det = (is_string($k) && !is_numeric($k) && is_string($v)) ? trim($v) : '';

                                if (in_array($cond, ['PTB', 'Tuberculosis', 'Pulmonary Tuberculosis'], true)) {
                                    $cond = 'Pulmonary Tuberculosis (PTB)';
                                } elseif ($cond === 'Allergies') {
                                    $cond = 'Allergy';
                                }

                                if ($cond === '' || $cond === '[]' || $cond === '{}') {
                                    continue;
                                }

                                if (isset($seenConditions[$cond])) {
                                    if (!empty($det)) {
                                        foreach ($pmhDisplay as &$item) {
                                            if ($item['condition'] === $cond && empty($item['detail'])) {
                                                $item['detail'] = $det;
                                            }
                                        }
                                        unset($item);
                                    }
                                    continue;
                                }

                                $seenConditions[$cond] = true;
                                $pmhDisplay[] = ['condition' => $cond, 'detail' => $det];
                            }
                        }

                        // Build display list for Family History (Deduplicated)
                        $familyDisplay = [];
                        $seenFamily = [];
                        if (!empty($familySaved) && is_array($familySaved)) {
                            foreach ($familySaved as $k => $v) {
                                $cond = (is_string($k) && !is_numeric($k)) ? trim($k) : trim((string)$v);
                                $det = (is_string($k) && !is_numeric($k) && is_string($v) && $v !== 'Yes' && $v !== $k) ? trim($v) : '';

                                if (in_array($cond, ['PTB', 'Tuberculosis', 'Pulmonary Tuberculosis'], true)) {
                                    $cond = 'Pulmonary Tuberculosis (PTB)';
                                } elseif ($cond === 'Allergies') {
                                    $cond = 'Allergy';
                                }

                                if ($cond === '' || $cond === '[]' || $cond === '{}' || $cond === 'Yes') {
                                    continue;
                                }

                                if (isset($seenFamily[$cond])) {
                                    if (!empty($det)) {
                                        foreach ($familyDisplay as &$item) {
                                            if ($item['condition'] === $cond && empty($item['detail'])) {
                                                $item['detail'] = $det;
                                            }
                                        }
                                        unset($item);
                                    }
                                    continue;
                                }

                                $seenFamily[$cond] = true;
                                $familyDisplay[] = ['condition' => $cond, 'detail' => $det];
                            }
                        }

                        // Build display list for Surgical History
                        $surgicalDisplay = [];
                        if (!empty($surgicalSaved) && is_array($surgicalSaved)) {
                            foreach ($surgicalSaved as $surg) {
                                if (is_array($surg) && (!empty($surg['operation']) || !empty($surg['date']))) {
                                    $surgicalDisplay[] = $surg;
                                } elseif (is_string($surg) && trim($surg) !== '' && trim($surg) !== '[]') {
                                    $surgicalDisplay[] = ['operation' => trim($surg), 'date' => '', 'hospital' => ''];
                                }
                            }
                        }

                        // Check physical examination findings
                        $peSystems = [
                            'skin' => 'Skin',
                            'heent' => 'HEENT',
                            'chest_lungs' => 'Chest / Lungs',
                            'heart' => 'Heart',
                            'abdomen' => 'Abdomen',
                            'extremities' => 'Extremities'
                        ];
                        $hasPeFindings = false;
                        foreach ($peSystems as $sKey => $sLabel) {
                            if (!empty($peSaved[$sKey]) && is_array($peSaved[$sKey])) {
                                $hasPeFindings = true;
                                break;
                            }
                        }
                        if (!empty($peSaved['remarks'])) {
                            $hasPeFindings = true;
                        }

                        // Check lifetime immunizations
                        $hasImmData = !empty($immSaved['children']) || !empty($immSaved['young_women']) || !empty($immSaved['pregnant']) || !empty($immSaved['elderly']) || !empty($immSaved['others']);

                        $hasPmhData = !empty($pmhDisplay);
                        $hasSurgicalData = !empty($surgicalDisplay);
                        $hasFamilyData = !empty($familyDisplay);
                        $hasLifestyleData = !empty($medicalHistory) && (
                            ($medicalHistory['smoking_status'] ?? 'Never') !== 'Never' ||
                            ($medicalHistory['alcohol_status'] ?? 'Never') !== 'Never' ||
                            !empty($medicalHistory['illicit_drugs']) ||
                            !empty($medicalHistory['smoking_pack_years']) ||
                            !empty($medicalHistory['alcohol_bottles_per_day'])
                        );
                        $hasReproductiveData = $isFemale && !empty($medicalHistory) && (
                            !empty($medicalHistory['menarche_age']) ||
                            !empty($medicalHistory['sexual_onset_age']) ||
                            !empty($medicalHistory['lmp']) ||
                            !empty($medicalHistory['period_duration_days']) ||
                            !empty($medicalHistory['cycle_interval_days']) ||
                            !empty($medicalHistory['pads_per_day']) ||
                            !empty($medicalHistory['is_menopausal']) ||
                            !empty($medicalHistory['birth_control_method'])
                        );
                        $hasObstetricData = $isFemale && !empty($medicalHistory) && (
                            (!empty($medicalHistory['gravida']) && (int)$medicalHistory['gravida'] > 0) ||
                            (!empty($medicalHistory['para']) && (int)$medicalHistory['para'] > 0) ||
                            !empty($medicalHistory['delivery_type']) ||
                            !empty($medicalHistory['pre_eclampsia'])
                        );
                        $hasBaselineVitals = !empty($medicalHistory) && (
                            !empty($medicalHistory['baseline_bp_systolic']) ||
                            !empty($medicalHistory['baseline_heart_rate']) ||
                            !empty($medicalHistory['baseline_height']) ||
                            !empty($medicalHistory['baseline_weight']) ||
                            !empty($medicalHistory['baseline_respiratory_rate']) ||
                            !empty($medicalHistory['baseline_waist_circumference'])
                        );

                        // Baseline Vitals BMI computation
                        $baselineHeight = !empty($medicalHistory['baseline_height']) ? (float)$medicalHistory['baseline_height'] : null;
                        $baselineWeight = !empty($medicalHistory['baseline_weight']) ? (float)$medicalHistory['baseline_weight'] : null;
                        $baselineBmi = null;
                        $baselineBmiClass = '';
                        $baselineBmiLabel = '';

                        if ($baselineHeight && $baselineWeight && $baselineHeight > 0 && $baselineWeight > 0) {
                            $heightInM = $baselineHeight / 100;
                            $baselineBmi = round($baselineWeight / ($heightInM * $heightInM), 1);
                            if ($baselineBmi < 18.5) {
                                $baselineBmiLabel = 'Underweight';
                                $baselineBmiClass = 'badge bg-info-subtle text-info border border-info-subtle';
                            } elseif ($baselineBmi >= 18.5 && $baselineBmi <= 22.9) {
                                $baselineBmiLabel = 'Normal';
                                $baselineBmiClass = 'badge bg-success-subtle text-success border border-success-subtle';
                            } elseif ($baselineBmi >= 23.0 && $baselineBmi <= 27.4) {
                                $baselineBmiLabel = 'Overweight';
                                $baselineBmiClass = 'badge bg-warning-subtle text-dark border border-warning-subtle';
                            } else {
                                $baselineBmiLabel = 'Obese';
                                $baselineBmiClass = 'badge bg-danger-subtle text-danger border border-danger-subtle';
                            }
                        }

                        $hasAnyIhpRecord = !empty($medicalHistory) && (
                            $hasPmhData ||
                            $hasSurgicalData ||
                            $hasFamilyData ||
                            $hasLifestyleData ||
                            $hasReproductiveData ||
                            $hasObstetricData ||
                            $hasPeFindings ||
                            $hasImmData ||
                            $hasBaselineVitals ||
                            !empty($medicalHistory['updated_at'])
                        );
                        ?>

                        <!-- -----------------------------------------------------------
                           MODE A: READ-ONLY VIEW (DEFAULT)
                           ----------------------------------------------------------- -->
                        <div id="ihp-view-mode">
                            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-3 pb-2 border-bottom">
                                <div>
                                    <h4 class="h6 mb-0 fw-bold text-dark">PhilHealth Annex A1: Individual Health Profile (IHP)</h4>
                                    <span class="text-muted small">
                                        <?php if (!empty($medicalHistory['updated_at'])): ?>
                                            Last updated on <?= date('M d, Y \a\t h:i A', strtotime($medicalHistory['updated_at'])) ?>
                                            <?= !empty($medicalHistory['updater_name']) ? 'by ' . h($medicalHistory['updater_name']) : '' ?>
                                        <?php else: ?>
                                            Baseline patient medical history and PhilHealth Annex A1 profile
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm px-3 fw-medium" onclick="enterIhpEditMode()">
                                    <i class="bi bi-pencil-square me-1"></i>Edit IHP Record
                                </button>
                            </div>

                            <?php if ($hasAnyIhpRecord): ?>
                                <div class="row g-3">
                                    <!-- 1. Past Medical Illnesses Card -->
                                    <div class="col-12">
                                        <div class="card border rounded-3 p-3 shadow-xs">
                                            <h5 class="h6 fw-bold text-primary-dark mb-2">
                                                1. Past Medical Illnesses
                                            </h5>
                                            <?php if ($hasPmhData): ?>
                                                <div class="d-flex flex-wrap gap-2 pt-1">
                                                    <?php foreach ($pmhDisplay as $item): ?>
                                                        <?php 
                                                        $cond = $item['condition'];
                                                        $det = $item['detail'];
                                                        ?>
                                                        <?php if (stripos($cond, 'Allergy') !== false || stripos($det, 'Allergy') !== false): ?>
                                                            <span class="badge bg-danger text-white px-2.5 py-1.5 fs-7">
                                                                <?= h($cond) ?><?= !empty($det) ? ': ' . h($det) : '' ?>
                                                            </span>
                                                        <?php elseif (stripos($cond, 'Hypertension') !== false): ?>
                                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1.5 fs-7">
                                                                <?= h($cond) ?><?= !empty($det) ? ' (' . h($det) . ')' : '' ?>
                                                            </span>
                                                        <?php elseif (stripos($cond, 'Cancer') !== false): ?>
                                                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2.5 py-1.5 fs-7">
                                                                <?= h($cond) ?><?= !empty($det) ? ' (' . h($det) . ')' : '' ?>
                                                            </span>
                                                        <?php elseif (stripos($cond, 'PTB') !== false || stripos($cond, 'Tuberculosis') !== false): ?>
                                                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2.5 py-1.5 fs-7">
                                                                <?= h($cond) ?><?= !empty($det) ? ' (' . h($det) . ')' : '' ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-light text-dark border px-2.5 py-1.5 fs-7">
                                                                <?= h($cond) ?><?= !empty($det) ? ': ' . h($det) : '' ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="pt-1">
                                                    <span class="badge bg-light text-secondary border px-3 py-1.5 fs-7">
                                                        <i class="bi bi-check-circle text-success me-1"></i>No chronic illnesses or allergies recorded
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- 2. Family History (Hereditary Diseases) Card (Full Width with Color-Coded Badges) -->
                                    <div class="col-12">
                                        <div class="card border rounded-3 p-3 shadow-xs">
                                            <h5 class="h6 fw-bold text-primary-dark mb-2">
                                                2. Family History (Hereditary Diseases)
                                            </h5>
                                            <?php if ($hasFamilyData): ?>
                                                <div class="d-flex flex-wrap gap-2 pt-1">
                                                    <?php foreach ($familyDisplay as $item): ?>
                                                        <?php 
                                                        $cond = $item['condition'];
                                                        $det = $item['detail'];
                                                        ?>
                                                        <?php if (stripos($cond, 'Allergy') !== false || stripos($det, 'Allergy') !== false): ?>
                                                            <span class="badge bg-danger text-white px-2.5 py-1.5 fs-7">
                                                                <?= h($cond) ?><?= !empty($det) ? ': ' . h($det) : '' ?>
                                                            </span>
                                                        <?php elseif (stripos($cond, 'Hypertension') !== false): ?>
                                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1.5 fs-7">
                                                                <?= h($cond) ?><?= !empty($det) ? ' (' . h($det) . ')' : '' ?>
                                                            </span>
                                                        <?php elseif (stripos($cond, 'Cancer') !== false): ?>
                                                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2.5 py-1.5 fs-7">
                                                                <?= h($cond) ?><?= !empty($det) ? ' (' . h($det) . ')' : '' ?>
                                                            </span>
                                                        <?php elseif (stripos($cond, 'PTB') !== false || stripos($cond, 'Tuberculosis') !== false): ?>
                                                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2.5 py-1.5 fs-7">
                                                                <?= h($cond) ?><?= !empty($det) ? ' (' . h($det) . ')' : '' ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-light text-dark border px-2.5 py-1.5 fs-7">
                                                                <?= h($cond) ?><?= !empty($det) ? ': ' . h($det) : '' ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="pt-1">
                                                    <span class="badge bg-light text-secondary border px-3 py-1.5 fs-7">
                                                        <i class="bi bi-check-circle text-success me-1"></i>No hereditary family diseases declared
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- 3. Past Surgical History & Hospitalization Card -->
                                    <div class="col-12 col-md-6">
                                        <div class="card border rounded-3 p-3 shadow-xs h-100">
                                            <h5 class="h6 fw-bold text-primary-dark mb-2">
                                                3. Past Surgical History & Hospitalization
                                            </h5>
                                            <?php if ($hasSurgicalData): ?>
                                                <ul class="list-group list-group-flush small">
                                                    <?php foreach ($surgicalDisplay as $surg): ?>
                                                        <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center bg-transparent border-bottom-subtle">
                                                            <div>
                                                                <span class="fw-semibold text-dark"><?= h($surg['operation'] ?? 'Surgical Procedure') ?></span>
                                                                <?php if (!empty($surg['hospital'])): ?>
                                                                    <span class="text-muted small ms-1">(<?= h($surg['hospital']) ?>)</span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <span class="badge bg-light text-secondary border"><?= !empty($surg['date']) ? h($surg['date']) : 'Year N/A' ?></span>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php else: ?>
                                                <div class="pt-1">
                                                    <span class="badge bg-light text-secondary border px-3 py-1.5 fs-7">
                                                        <i class="bi bi-check-circle text-success me-1"></i>No prior surgeries or hospitalizations declared
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- 4. Personal & Social History Card -->
                                    <div class="col-12 col-md-6">
                                        <div class="card border rounded-3 p-3 shadow-xs h-100">
                                            <h5 class="h6 fw-bold text-primary-dark mb-2">
                                                4. Personal & Social History
                                            </h5>
                                            <div class="row g-2 small pt-1">
                                                <div class="col-12 col-sm-6">
                                                    <div class="p-2 rounded bg-light border h-100">
                                                        <span class="text-muted d-block small mb-1">Smoking Status:</span>
                                                        <?php 
                                                        $smk = $medicalHistory['smoking_status'] ?? 'Never';
                                                        if ($smk === 'Yes'): ?>
                                                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle">Active Smoker</span>
                                                            <?php if (!empty($medicalHistory['smoking_pack_years'])): ?>
                                                                <span class="text-dark small ms-1">(<?= h($medicalHistory['smoking_pack_years']) ?> pk-yrs)</span>
                                                            <?php endif; ?>
                                                        <?php elseif ($smk === 'Quit'): ?>
                                                            <span class="badge bg-secondary-subtle text-secondary border">Quit Smoking</span>
                                                            <?php if (!empty($medicalHistory['smoking_pack_years'])): ?>
                                                                <span class="text-dark small ms-1">(<?= h($medicalHistory['smoking_pack_years']) ?> pk-yrs)</span>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="text-dark">Never Smoked</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-sm-6">
                                                    <div class="p-2 rounded bg-light border h-100">
                                                        <span class="text-muted d-block small mb-1">Alcohol Drinking:</span>
                                                        <?php 
                                                        $alc = $medicalHistory['alcohol_status'] ?? 'Never';
                                                        if ($alc === 'Yes'): ?>
                                                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle">Regular / Occasional</span>
                                                            <?php if (!empty($medicalHistory['alcohol_bottles_per_day'])): ?>
                                                                <span class="text-dark small ms-1">(<?= h($medicalHistory['alcohol_bottles_per_day']) ?> btls/day)</span>
                                                            <?php endif; ?>
                                                        <?php elseif ($alc === 'Quit'): ?>
                                                            <span class="badge bg-secondary-subtle text-secondary border">Quit Drinking</span>
                                                        <?php else: ?>
                                                            <span class="text-dark">Non-Drinker</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <div class="p-2 rounded bg-light border d-flex justify-content-between align-items-center">
                                                        <span class="text-muted small">Illicit Drug Use:</span>
                                                        <?php if (!empty($medicalHistory['illicit_drugs'])): ?>
                                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Reported History</span>
                                                        <?php else: ?>
                                                            <span class="text-muted">None Declared</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 5. Lifetime Immunization Record (Annex A1) Card -->
                                    <div class="col-12 col-md-6">
                                        <div class="card border rounded-3 p-3 shadow-xs h-100">
                                            <h5 class="h6 fw-bold text-primary-dark mb-2">
                                                5. Lifetime Immunization Record (Annex A1)
                                            </h5>
                                            <?php if ($hasImmData): ?>
                                                <div class="small pt-1">
                                                    <?php if (!empty($immSaved['children'])): ?>
                                                        <div class="mb-2">
                                                            <span class="text-muted d-block" style="font-size: 0.75rem;">Children Vaccines:</span>
                                                            <div class="d-flex flex-wrap gap-1 mt-1">
                                                                <?php foreach ($immSaved['children'] as $v): ?>
                                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1"><?= h($v) ?></span>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if (!empty($immSaved['young_women'])): ?>
                                                        <div class="mb-2">
                                                            <span class="text-muted d-block" style="font-size: 0.75rem;">Young Women Vaccines:</span>
                                                            <div class="d-flex flex-wrap gap-1 mt-1">
                                                                <?php foreach ($immSaved['young_women'] as $v): ?>
                                                                    <span class="badge bg-pink-subtle text-pink border border-pink-subtle px-2 py-1"><?= h($v) ?></span>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if (!empty($immSaved['pregnant'])): ?>
                                                        <div class="mb-2">
                                                            <span class="text-muted d-block" style="font-size: 0.75rem;">Pregnant Vaccines:</span>
                                                            <div class="d-flex flex-wrap gap-1 mt-1">
                                                                <?php foreach ($immSaved['pregnant'] as $v): ?>
                                                                    <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1"><?= h($v) ?></span>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if (!empty($immSaved['elderly'])): ?>
                                                        <div class="mb-2">
                                                            <span class="text-muted d-block" style="font-size: 0.75rem;">Elderly / Immunocompromised:</span>
                                                            <div class="d-flex flex-wrap gap-1 mt-1">
                                                                <?php foreach ($immSaved['elderly'] as $v): ?>
                                                                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1"><?= h($v) ?></span>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if (!empty($immSaved['others'])): ?>
                                                        <div>
                                                            <span class="text-muted d-block" style="font-size: 0.75rem;">Others:</span>
                                                            <span class="fw-medium text-dark"><?= h($immSaved['others']) ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="pt-1">
                                                    <span class="badge bg-light text-secondary border px-3 py-1.5 fs-7">
                                                        <i class="bi bi-shield-slash text-muted me-1"></i>No lifetime immunization history recorded
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- 6. Baseline Vitals & Anthropometrics (Annex A1) Card -->
                                    <div class="col-12 col-md-6">
                                        <div class="card border rounded-3 p-3 shadow-xs h-100">
                                            <h5 class="h6 fw-bold text-primary-dark mb-2">
                                                6. Baseline Vitals & Anthropometrics (Annex A1)
                                            </h5>
                                            <?php if ($hasBaselineVitals): ?>
                                                <div class="row g-2 small pt-1">
                                                    <div class="col-6">
                                                        <div class="p-2 rounded bg-light border h-100">
                                                            <span class="text-muted d-block small mb-1">Blood Pressure:</span>
                                                            <?php if (!empty($medicalHistory['baseline_bp_systolic']) || !empty($medicalHistory['baseline_bp_diastolic'])): ?>
                                                                <span class="fw-bold text-dark fs-7">
                                                                    <?= h($medicalHistory['baseline_bp_systolic'] ?? '—') ?>/<?= h($medicalHistory['baseline_bp_diastolic'] ?? '—') ?>
                                                                    <span class="text-muted fw-normal fs-8">mmHg</span>
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="text-muted">—</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="p-2 rounded bg-light border h-100">
                                                            <span class="text-muted d-block small mb-1">Heart Rate:</span>
                                                            <?php if (!empty($medicalHistory['baseline_heart_rate'])): ?>
                                                                <span class="fw-bold text-dark fs-7">
                                                                    <?= h($medicalHistory['baseline_heart_rate']) ?>
                                                                    <span class="text-muted fw-normal fs-8">bpm</span>
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="text-muted">—</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="p-2 rounded bg-light border h-100">
                                                            <span class="text-muted d-block small mb-1">Respiratory Rate:</span>
                                                            <?php if (!empty($medicalHistory['baseline_respiratory_rate'])): ?>
                                                                <span class="fw-bold text-dark fs-7">
                                                                    <?= h($medicalHistory['baseline_respiratory_rate']) ?>
                                                                    <span class="text-muted fw-normal fs-8">cpm</span>
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="text-muted">—</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="p-2 rounded bg-light border h-100">
                                                            <span class="text-muted d-block small mb-1">Waist Circumference:</span>
                                                            <?php if (!empty($medicalHistory['baseline_waist_circumference'])): ?>
                                                                <span class="fw-bold text-dark fs-7">
                                                                    <?= h($medicalHistory['baseline_waist_circumference']) ?>
                                                                    <span class="text-muted fw-normal fs-8">cm</span>
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="text-muted">—</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="p-2 rounded bg-light border h-100">
                                                            <span class="text-muted d-block small mb-1">Height & Weight:</span>
                                                            <div class="fw-semibold text-dark">
                                                                <span><?= !empty($medicalHistory['baseline_height']) ? h($medicalHistory['baseline_height']) . ' cm' : '—' ?></span>
                                                                <span class="text-muted mx-1">&bull;</span>
                                                                <span><?= !empty($medicalHistory['baseline_weight']) ? h($medicalHistory['baseline_weight']) . ' kg' : '—' ?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="p-2 rounded bg-light border h-100">
                                                            <span class="text-muted d-block small mb-1">Body Mass Index (BMI):</span>
                                                            <?php if ($baselineBmi !== null): ?>
                                                                <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                                                    <span class="fw-bold text-dark fs-7"><?= number_format($baselineBmi, 1) ?></span>
                                                                    <span class="<?= $baselineBmiClass ?>"><?= $baselineBmiLabel ?></span>
                                                                </div>
                                                            <?php else: ?>
                                                                <span class="text-muted">None recorded</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div class="pt-1">
                                                    <span class="badge bg-light text-secondary border px-3 py-1.5 fs-7">
                                                        <i class="bi bi-activity text-muted me-1"></i>No baseline vitals or anthropometrics recorded
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- 7. Pertinent Physical Examination Findings (Annex A1) -->
                                    <div class="col-12">
                                        <div class="card border rounded-3 p-3 shadow-xs">
                                            <h5 class="h6 fw-bold text-primary-dark mb-2">
                                                7. Pertinent Physical Examination Findings (Annex A1)
                                            </h5>
                                            <?php if ($hasPeFindings): ?>
                                                <div class="row g-2 small pt-1">
                                                    <?php foreach ($peSystems as $sKey => $sLabel): ?>
                                                        <div class="col-12 col-sm-6 col-md-4">
                                                            <div class="p-2 rounded bg-light border h-100">
                                                                <span class="fw-bold text-secondary d-block mb-1" style="font-size: 0.75rem;"><?= $sLabel ?>:</span>
                                                                <?php if (!empty($peSaved[$sKey]) && is_array($peSaved[$sKey])): ?>
                                                                    <div class="d-flex flex-wrap gap-1">
                                                                        <?php foreach ($peSaved[$sKey] as $finding): ?>
                                                                            <span class="badge bg-white text-dark border px-2 py-1"><?= h($finding) ?></span>
                                                                        <?php endforeach; ?>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <span class="text-muted fst-italic" style="font-size: 0.75rem;">Normal / Unremarkable</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                    <?php if (!empty($peSaved['remarks'])): ?>
                                                        <div class="col-12 mt-2">
                                                            <span class="text-muted d-block" style="font-size: 0.75rem;">Physical Exam Remarks / Notes:</span>
                                                            <span class="text-dark fw-medium"><?= h($peSaved['remarks']) ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="pt-1">
                                                    <span class="badge bg-light text-secondary border px-3 py-1.5 fs-7">
                                                        <i class="bi bi-clipboard2-check text-muted me-1"></i>No physical examination findings on record
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- 8. Female Menstrual & Reproductive History (if Female) -->
                                    <?php if ($isFemale): ?>
                                        <div class="col-12 col-md-6">
                                            <div class="card border rounded-3 p-3 shadow-xs h-100">
                                                <h5 class="h6 fw-bold text-pink mb-2">
                                                    8. Female Menstrual & Reproductive History
                                                </h5>
                                                <?php if ($hasReproductiveData): ?>
                                                    <div class="row g-2 small pt-1">
                                                        <div class="col-6">
                                                            <span class="text-muted d-block">Menarche Age:</span>
                                                            <span class="fw-semibold text-dark"><?= !empty($medicalHistory['menarche_age']) ? h($medicalHistory['menarche_age']) . ' yrs old' : '<span class="text-muted">Unspecified</span>' ?></span>
                                                        </div>
                                                        <div class="col-6">
                                                            <span class="text-muted d-block">Sexual Onset Age:</span>
                                                            <span class="fw-semibold text-dark"><?= !empty($medicalHistory['sexual_onset_age']) ? h($medicalHistory['sexual_onset_age']) . ' yrs old' : '<span class="text-muted">Unspecified</span>' ?></span>
                                                        </div>
                                                        <div class="col-6">
                                                            <span class="text-muted d-block">Last Menstrual Period (LMP):</span>
                                                            <span class="fw-bold text-primary"><?= !empty($medicalHistory['lmp']) ? date('M d, Y', strtotime($medicalHistory['lmp'])) : '<span class="text-muted fw-normal">None recorded</span>' ?></span>
                                                        </div>
                                                        <div class="col-6">
                                                            <span class="text-muted d-block">Menopausal:</span>
                                                            <?php if (!empty($medicalHistory['is_menopausal'])): ?>
                                                                <span class="badge bg-warning-subtle text-dark border">Yes <?= !empty($medicalHistory['menopause_age']) ? '(Age ' . h($medicalHistory['menopause_age']) . ')' : '' ?></span>
                                                            <?php else: ?>
                                                                <span class="badge bg-light text-muted border">No</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="col-4">
                                                            <span class="text-muted d-block">Duration:</span>
                                                            <span class="fw-medium text-dark"><?= !empty($medicalHistory['period_duration_days']) ? h($medicalHistory['period_duration_days']) . ' days' : '—' ?></span>
                                                        </div>
                                                        <div class="col-4">
                                                            <span class="text-muted d-block">Cycle:</span>
                                                            <span class="fw-medium text-dark"><?= !empty($medicalHistory['cycle_interval_days']) ? h($medicalHistory['cycle_interval_days']) . ' days' : '—' ?></span>
                                                        </div>
                                                        <div class="col-4">
                                                            <span class="text-muted d-block">Pads/Day:</span>
                                                            <span class="fw-medium text-dark"><?= !empty($medicalHistory['pads_per_day']) ? h($medicalHistory['pads_per_day']) : '—' ?></span>
                                                        </div>
                                                        <div class="col-12 border-top pt-2 mt-1">
                                                            <span class="text-muted d-block">Family Planning Method:</span>
                                                            <span class="fw-semibold text-dark"><?= !empty($medicalHistory['birth_control_method']) ? h($medicalHistory['birth_control_method']) : '<span class="text-muted fw-normal">None declared</span>' ?></span>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="pt-1">
                                                        <span class="badge bg-light text-secondary border px-3 py-1.5 fs-7">
                                                            <i class="bi bi-person text-pink me-1"></i>No menstrual or reproductive history recorded
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- 9. Pregnancy & Obstetric History (if Female) -->
                                        <div class="col-12 col-md-6">
                                            <div class="card border rounded-3 p-3 shadow-xs h-100">
                                                <h5 class="h6 fw-bold text-pink mb-2">
                                                    9. Pregnancy & Obstetric History
                                                </h5>
                                                <?php if ($hasObstetricData): ?>
                                                    <div class="row g-2 small pt-1">
                                                        <div class="col-6">
                                                            <span class="text-muted d-block">Gravida / Para (G/P):</span>
                                                            <span class="fw-bold text-dark">G<?= h($medicalHistory['gravida'] ?? '0') ?> P<?= h($medicalHistory['para'] ?? '0') ?> (T:<?= h($medicalHistory['term_births'] ?? '0') ?> P:<?= h($medicalHistory['preterm_births'] ?? '0') ?> A:<?= h($medicalHistory['abortions'] ?? '0') ?> L:<?= h($medicalHistory['living_children'] ?? '0') ?>)</span>
                                                        </div>
                                                        <div class="col-6">
                                                            <span class="text-muted d-block">Type of Delivery:</span>
                                                            <span class="fw-semibold text-dark"><?= !empty($medicalHistory['delivery_type']) ? h($medicalHistory['delivery_type']) : 'Unspecified' ?></span>
                                                        </div>
                                                        <div class="col-6 border-top pt-2 mt-1">
                                                            <span class="text-muted d-block">Pre-eclampsia / PIH History:</span>
                                                            <?php if (!empty($medicalHistory['pre_eclampsia'])): ?>
                                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Yes (Reported)</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-light text-muted border">No</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="col-6 border-top pt-2 mt-1">
                                                            <span class="text-muted d-block">Access to FP Counselling:</span>
                                                            <span class="badge bg-light text-dark border"><?= (!empty($medicalHistory['fp_counselling']) && (int)$medicalHistory['fp_counselling'] === 1) ? 'Yes' : 'No' ?></span>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="pt-1">
                                                        <span class="badge bg-light text-secondary border px-3 py-1.5 fs-7">
                                                            <i class="bi bi-person text-pink me-1"></i>Nulliparous / No obstetric history recorded
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <!-- Clean Empty State Card -->
                                <div class="card border border-dashed rounded-3 p-4 text-center bg-light shadow-xs my-2">
                                    <h5 class="h6 fw-bold text-dark mb-1">No Individual Health Profile (IHP) On File</h5>
                                    <p class="text-muted small mb-3 mx-auto" style="max-width: 520px;">
                                        PhilHealth Annex A1 baseline medical history has not yet been recorded for this patient. Click below to record past chronic illnesses, surgical operations, family heredity, social history, immunizations, physical exam, and reproductive health.
                                    </p>
                                    <div>
                                        <button type="button" class="btn btn-primary btn-sm px-4 fw-medium" onclick="enterIhpEditMode()">
                                            Record IHP Medical History
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- -----------------------------------------------------------
                           MODE B: EDIT FORM (INITIALLY HIDDEN)
                           ----------------------------------------------------------- -->
                        <div id="ihp-edit-mode" class="d-none">
                            <form action="<?= url('/patients/' . $patient['id'] . '/medical-history') ?>" method="POST" id="ihpForm">
                                <?= csrf_field() ?>

                                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                    <div>
                                        <h4 class="h6 mb-0 fw-bold text-dark">PhilHealth Annex A1: Individual Health Profile (IHP)</h4>
                                        <span class="text-muted small">Update past chronic illnesses, surgeries, family heredity, social habits, immunizations, physical exam, and reproductive health.</span>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="cancelIhpEditMode()">
                                            Cancel
                                        </button>
                                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-xs">
                                            Save IHP Record
                                        </button>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <!-- 1. Past Medical History Checklist (with Proximity Inputs) -->
                                    <div class="col-12">
                                        <div class="card border rounded-3 p-3 shadow-xs">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <h5 class="h6 fw-bold text-primary-dark mb-0">
                                                    1. Past Medical History (Illnesses)
                                                </h5>
                                                <span class="text-muted small">Check condition and provide details where applicable</span>
                                            </div>

                                            <!-- Group 1A: Conditions with Specific Details (2 Columns) -->
                                            <div class="row g-3 small mb-3">
                                                <!-- Allergy -->
                                                <div class="col-12 col-md-6">
                                                    <div class="p-2 border rounded bg-light h-100">
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox" name="past_medical_history[]" value="Allergy" id="pmh_allergy" <?= (isset($pmhSaved['Allergy']) || in_array('Allergy', $pmhSaved) || isset($pmhSaved['Allergies']) || in_array('Allergies', $pmhSaved)) ? 'checked' : '' ?>>
                                                            <label class="form-check-label fw-semibold text-dark" for="pmh_allergy">Allergy</label>
                                                        </div>
                                                        <input type="text" name="allergy_specifics" class="form-control form-control-sm bg-white" placeholder="Specify allergens (e.g. Penicillin, Seafood)" value="<?= h(is_array($pmhSaved) ? ($pmhSaved['Allergy'] ?? $pmhSaved['Allergies'] ?? '') : '') ?>">
                                                    </div>
                                                </div>

                                                <!-- Hypertension -->
                                                <div class="col-12 col-md-6">
                                                    <div class="p-2 border rounded bg-light h-100">
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox" name="past_medical_history[]" value="Hypertension" id="pmh_hypertension" <?= (isset($pmhSaved['Hypertension']) || in_array('Hypertension', $pmhSaved)) ? 'checked' : '' ?>>
                                                            <label class="form-check-label fw-semibold text-dark" for="pmh_hypertension">Hypertension</label>
                                                        </div>
                                                        <input type="text" name="hypertension_highest_bp" class="form-control form-control-sm bg-white" placeholder="Highest BP (e.g. 160/100)" value="<?= h(is_array($pmhSaved) ? str_replace('Highest BP: ', '', $pmhSaved['Hypertension'] ?? '') : '') ?>">
                                                    </div>
                                                </div>

                                                <!-- Cancer -->
                                                <div class="col-12 col-md-6">
                                                    <div class="p-2 border rounded bg-light h-100">
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox" name="past_medical_history[]" value="Cancer" id="pmh_cancer" <?= (isset($pmhSaved['Cancer']) || in_array('Cancer', $pmhSaved)) ? 'checked' : '' ?>>
                                                            <label class="form-check-label fw-semibold text-dark" for="pmh_cancer">Cancer</label>
                                                        </div>
                                                        <input type="text" name="cancer_organ" class="form-control form-control-sm bg-white" placeholder="Specify organ (e.g. Breast, Colon)" value="<?= h(is_array($pmhSaved) ? ($pmhSaved['Cancer'] ?? '') : '') ?>">
                                                    </div>
                                                </div>

                                                <!-- Hepatitis -->
                                                <div class="col-12 col-md-6">
                                                    <div class="p-2 border rounded bg-light h-100">
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox" name="past_medical_history[]" value="Hepatitis" id="pmh_hepatitis" <?= (isset($pmhSaved['Hepatitis']) || in_array('Hepatitis', $pmhSaved)) ? 'checked' : '' ?>>
                                                            <label class="form-check-label fw-semibold text-dark" for="pmh_hepatitis">Hepatitis</label>
                                                        </div>
                                                        <input type="text" name="hepatitis_type" class="form-control form-control-sm bg-white" placeholder="Specify type (e.g. Hepatitis B)" value="<?= h(is_array($pmhSaved) ? ($pmhSaved['Hepatitis'] ?? '') : '') ?>">
                                                    </div>
                                                </div>

                                                <!-- Tuberculosis & PTB Category -->
                                                <div class="col-12 col-md-6">
                                                    <div class="p-2 border rounded bg-light h-100">
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox" name="past_medical_history[]" value="Pulmonary Tuberculosis (PTB)" id="pmh_ptb" <?= (isset($pmhSaved['Pulmonary Tuberculosis (PTB)']) || isset($pmhSaved['PTB']) || isset($pmhSaved['Tuberculosis']) || in_array('Pulmonary Tuberculosis (PTB)', $pmhSaved) || in_array('PTB', $pmhSaved) || in_array('Tuberculosis', $pmhSaved)) ? 'checked' : '' ?>>
                                                            <label class="form-check-label fw-semibold text-dark" for="pmh_ptb">Tuberculosis / PTB</label>
                                                        </div>
                                                        <div class="row g-2">
                                                            <div class="col-6">
                                                                <input type="text" name="tuberculosis_organ" class="form-control form-control-sm bg-white" placeholder="Organ (e.g. Lungs, Spine)" value="<?= h(is_array($pmhSaved) ? ($pmhSaved['Tuberculosis'] ?? '') : '') ?>">
                                                            </div>
                                                            <div class="col-6">
                                                                <input type="text" name="ptb_details" class="form-control form-control-sm bg-white" placeholder="PTB Category (e.g. Cat 1)" value="<?= h(is_array($pmhSaved) ? ($pmhSaved['Pulmonary Tuberculosis (PTB)'] ?? $pmhSaved['PTB'] ?? '') : '') ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Others -->
                                                <div class="col-12 col-md-6">
                                                    <div class="p-2 border rounded bg-light h-100">
                                                        <div class="form-check mb-1">
                                                            <input class="form-check-input" type="checkbox" name="past_medical_history[]" value="Others" id="pmh_others" <?= (isset($pmhSaved['Others']) || in_array('Others', $pmhSaved)) ? 'checked' : '' ?>>
                                                            <label class="form-check-label fw-semibold text-dark" for="pmh_others">Others (Specify)</label>
                                                        </div>
                                                        <input type="text" name="pmh_other_specify" class="form-control form-control-sm bg-white" placeholder="Specify other illnesses..." value="<?= h(is_array($pmhSaved) ? ($pmhSaved['Others'] ?? '') : '') ?>">
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Group 1B: Common Illnesses Checklist (4 Columns) -->
                                            <div class="border-top pt-2">
                                                <span class="text-secondary fw-semibold small d-block mb-2">Other Chronic & Systemic Illnesses:</span>
                                                <div class="row g-2 small">
                                                    <?php
                                                    $pmhGeneral = [
                                                        'Asthma' => 'Asthma',
                                                        'Cerebrovascular Disease' => 'Cerebrovascular Disease (Stroke)',
                                                        'Coronary Artery Disease' => 'Coronary Artery Disease',
                                                        'Diabetes Mellitus' => 'Diabetes Mellitus',
                                                        'Emphysema' => 'Emphysema / COPD',
                                                        'Epilepsy / Seizure Disease' => 'Epilepsy / Seizure Disease',
                                                        'Hyperlipidemia' => 'Hyperlipidemia',
                                                        'Peptic Ulcer Disease' => 'Peptic Ulcer Disease',
                                                        'Pneumonia' => 'Pneumonia',
                                                        'Thyroid Disease' => 'Thyroid Disease',
                                                        'Urinary Tract Infection' => 'Urinary Tract Infection (UTI)'
                                                    ];
                                                    foreach ($pmhGeneral as $gKey => $gLabel):
                                                        $gChecked = is_array($pmhSaved) && (isset($pmhSaved[$gKey]) || in_array($gKey, $pmhSaved) || ($gKey === 'Cerebrovascular Disease' && in_array('Stroke', $pmhSaved)) || ($gKey === 'Emphysema' && in_array('Emphysema / COPD', $pmhSaved)) || ($gKey === 'Epilepsy / Seizure Disease' && in_array('Epilepsy / Seizure', $pmhSaved)));
                                                    ?>
                                                        <div class="col-12 col-sm-6 col-md-3">
                                                            <div class="p-2 border rounded bg-light h-100 d-flex align-items-center">
                                                                <div class="form-check mb-0">
                                                                    <input class="form-check-input" type="checkbox" name="past_medical_history[]" value="<?= $gKey ?>" id="pmh_g_<?= md5($gKey) ?>" <?= $gChecked ? 'checked' : '' ?>>
                                                                    <label class="form-check-label fw-semibold text-dark small" for="pmh_g_<?= md5($gKey) ?>"><?= $gLabel ?></label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 2. Family History (Hereditary Diseases) Checklist (Matching Section 1 Layout) -->
                                    <div class="col-12">
                                        <div class="card border rounded-3 p-3 shadow-xs">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <h5 class="h6 fw-bold text-primary-dark mb-0">
                                                    2. Family History (Hereditary Diseases)
                                                </h5>
                                                <span class="text-muted small">Check hereditary condition and provide details where applicable</span>
                                            </div>

                                            <!-- Group 2A: Hereditary Conditions with Specific Details (2 Columns) -->
                                             <div class="row g-3 small mb-3">
                                                 <!-- Allergy -->
                                                 <div class="col-12 col-md-6">
                                                     <div class="p-2 border rounded bg-light h-100">
                                                         <div class="form-check mb-1">
                                                             <input class="form-check-input" type="checkbox" name="family_history[]" value="Allergy" id="fam_allergy" <?= (isset($familySaved['Allergy']) || in_array('Allergy', $familySaved) || isset($familySaved['Allergies']) || in_array('Allergies', $familySaved)) ? 'checked' : '' ?>>
                                                             <label class="form-check-label fw-semibold text-dark" for="fam_allergy">Allergy</label>
                                                         </div>
                                                         <input type="text" name="fam_allergy_specifics" class="form-control form-control-sm bg-white mb-1" placeholder="Specify allergens (e.g. Asthma, Eczema, Food)" value="<?= h(is_array($familySaved) ? ($familySaved['Allergy'] ?? $familySaved['Allergies'] ?? '') : '') ?>">
                                                         <select name="family_history_lineage[Allergy]" class="form-select form-select-sm bg-white py-0 px-2" style="font-size: 0.75rem;">
                                                             <option value="Unknown" <?= ($famLineageSaved['Allergy'] ?? '') === 'Unknown' ? 'selected' : '' ?>>-- Lineage: Unknown / Unspecified --</option>
                                                             <option value="Mother" <?= ($famLineageSaved['Allergy'] ?? '') === 'Mother' ? 'selected' : '' ?>>Mother (Maternal)</option>
                                                             <option value="Father" <?= ($famLineageSaved['Allergy'] ?? '') === 'Father' ? 'selected' : '' ?>>Father (Paternal)</option>
                                                             <option value="Both" <?= ($famLineageSaved['Allergy'] ?? '') === 'Both' ? 'selected' : '' ?>>Both Parents</option>
                                                         </select>
                                                     </div>
                                                 </div>

                                                 <!-- Hypertension -->
                                                 <div class="col-12 col-md-6">
                                                     <div class="p-2 border rounded bg-light h-100">
                                                         <div class="form-check mb-1">
                                                             <input class="form-check-input" type="checkbox" name="family_history[]" value="Hypertension" id="fam_hypertension" <?= (isset($familySaved['Hypertension']) || in_array('Hypertension', $familySaved)) ? 'checked' : '' ?>>
                                                             <label class="form-check-label fw-semibold text-dark" for="fam_hypertension">Hypertension</label>
                                                         </div>
                                                         <input type="text" name="fam_hypertension_highest_bp" class="form-control form-control-sm bg-white mb-1" placeholder="Highest BP / Complication (e.g. 180/100, Stroke)" value="<?= h(is_array($familySaved) ? str_replace('Highest BP: ', '', $familySaved['Hypertension'] ?? '') : '') ?>">
                                                         <select name="family_history_lineage[Hypertension]" class="form-select form-select-sm bg-white py-0 px-2" style="font-size: 0.75rem;">
                                                             <option value="Unknown" <?= ($famLineageSaved['Hypertension'] ?? '') === 'Unknown' ? 'selected' : '' ?>>-- Lineage: Unknown / Unspecified --</option>
                                                             <option value="Mother" <?= ($famLineageSaved['Hypertension'] ?? '') === 'Mother' ? 'selected' : '' ?>>Mother (Maternal)</option>
                                                             <option value="Father" <?= ($famLineageSaved['Hypertension'] ?? '') === 'Father' ? 'selected' : '' ?>>Father (Paternal)</option>
                                                             <option value="Both" <?= ($famLineageSaved['Hypertension'] ?? '') === 'Both' ? 'selected' : '' ?>>Both Parents</option>
                                                         </select>
                                                     </div>
                                                 </div>

                                                 <!-- Cancer -->
                                                 <div class="col-12 col-md-6">
                                                     <div class="p-2 border rounded bg-light h-100">
                                                         <div class="form-check mb-1">
                                                             <input class="form-check-input" type="checkbox" name="family_history[]" value="Cancer" id="fam_cancer" <?= (isset($familySaved['Cancer']) || in_array('Cancer', $familySaved)) ? 'checked' : '' ?>>
                                                             <label class="form-check-label fw-semibold text-dark" for="fam_cancer">Cancer</label>
                                                         </div>
                                                         <input type="text" name="fam_cancer_organ" class="form-control form-control-sm bg-white mb-1" placeholder="Specify organ (e.g. Breast, Colon)" value="<?= h(is_array($familySaved) ? ($familySaved['Cancer'] ?? '') : '') ?>">
                                                         <select name="family_history_lineage[Cancer]" class="form-select form-select-sm bg-white py-0 px-2" style="font-size: 0.75rem;">
                                                             <option value="Unknown" <?= ($famLineageSaved['Cancer'] ?? '') === 'Unknown' ? 'selected' : '' ?>>-- Lineage: Unknown / Unspecified --</option>
                                                             <option value="Mother" <?= ($famLineageSaved['Cancer'] ?? '') === 'Mother' ? 'selected' : '' ?>>Mother (Maternal)</option>
                                                             <option value="Father" <?= ($famLineageSaved['Cancer'] ?? '') === 'Father' ? 'selected' : '' ?>>Father (Paternal)</option>
                                                             <option value="Both" <?= ($famLineageSaved['Cancer'] ?? '') === 'Both' ? 'selected' : '' ?>>Both Parents</option>
                                                         </select>
                                                     </div>
                                                 </div>

                                                 <!-- Hepatitis -->
                                                 <div class="col-12 col-md-6">
                                                     <div class="p-2 border rounded bg-light h-100">
                                                         <div class="form-check mb-1">
                                                             <input class="form-check-input" type="checkbox" name="family_history[]" value="Hepatitis" id="fam_hepatitis" <?= (isset($familySaved['Hepatitis']) || in_array('Hepatitis', $familySaved)) ? 'checked' : '' ?>>
                                                             <label class="form-check-label fw-semibold text-dark" for="fam_hepatitis">Hepatitis</label>
                                                         </div>
                                                         <input type="text" name="fam_hepatitis_type" class="form-control form-control-sm bg-white mb-1" placeholder="Specify type (e.g. Hepatitis B)" value="<?= h(is_array($familySaved) ? ($familySaved['Hepatitis'] ?? '') : '') ?>">
                                                         <select name="family_history_lineage[Hepatitis]" class="form-select form-select-sm bg-white py-0 px-2" style="font-size: 0.75rem;">
                                                             <option value="Unknown" <?= ($famLineageSaved['Hepatitis'] ?? '') === 'Unknown' ? 'selected' : '' ?>>-- Lineage: Unknown / Unspecified --</option>
                                                             <option value="Mother" <?= ($famLineageSaved['Hepatitis'] ?? '') === 'Mother' ? 'selected' : '' ?>>Mother (Maternal)</option>
                                                             <option value="Father" <?= ($famLineageSaved['Hepatitis'] ?? '') === 'Father' ? 'selected' : '' ?>>Father (Paternal)</option>
                                                             <option value="Both" <?= ($famLineageSaved['Hepatitis'] ?? '') === 'Both' ? 'selected' : '' ?>>Both Parents</option>
                                                         </select>
                                                     </div>
                                                 </div>

                                                 <!-- Tuberculosis & PTB Category -->
                                                 <div class="col-12 col-md-6">
                                                     <div class="p-2 border rounded bg-light h-100">
                                                         <div class="form-check mb-1">
                                                             <input class="form-check-input" type="checkbox" name="family_history[]" value="Tuberculosis" id="fam_ptb" <?= (isset($familySaved['Tuberculosis']) || isset($familySaved['PTB Category']) || in_array('Tuberculosis', $familySaved) || in_array('PTB Category', $familySaved)) ? 'checked' : '' ?>>
                                                             <label class="form-check-label fw-semibold text-dark" for="fam_ptb">Tuberculosis / PTB</label>
                                                         </div>
                                                         <div class="row g-2 mb-1">
                                                             <div class="col-6">
                                                                 <input type="text" name="fam_tuberculosis_organ" class="form-control form-control-sm bg-white" placeholder="Organ (e.g. Pulmonary)" value="<?= h(is_array($familySaved) ? ($familySaved['Tuberculosis'] ?? '') : '') ?>">
                                                             </div>
                                                             <div class="col-6">
                                                                 <input type="text" name="fam_ptb_details" class="form-control form-control-sm bg-white" placeholder="PTB Category (e.g. Active)" value="<?= h(is_array($familySaved) ? ($familySaved['PTB Category'] ?? '') : '') ?>">
                                                             </div>
                                                         </div>
                                                         <select name="family_history_lineage[Tuberculosis]" class="form-select form-select-sm bg-white py-0 px-2" style="font-size: 0.75rem;">
                                                             <option value="Unknown" <?= ($famLineageSaved['Tuberculosis'] ?? '') === 'Unknown' ? 'selected' : '' ?>>-- Lineage: Unknown / Unspecified --</option>
                                                             <option value="Mother" <?= ($famLineageSaved['Tuberculosis'] ?? '') === 'Mother' ? 'selected' : '' ?>>Mother (Maternal)</option>
                                                             <option value="Father" <?= ($famLineageSaved['Tuberculosis'] ?? '') === 'Father' ? 'selected' : '' ?>>Father (Paternal)</option>
                                                             <option value="Both" <?= ($famLineageSaved['Tuberculosis'] ?? '') === 'Both' ? 'selected' : '' ?>>Both Parents</option>
                                                         </select>
                                                     </div>
                                                 </div>

                                                 <!-- Others -->
                                                 <div class="col-12 col-md-6">
                                                     <div class="p-2 border rounded bg-light h-100">
                                                         <div class="form-check mb-1">
                                                             <input class="form-check-input" type="checkbox" name="family_history[]" value="Others" id="fam_others" <?= (isset($familySaved['Others']) || in_array('Others', $familySaved)) ? 'checked' : '' ?>>
                                                             <label class="form-check-label fw-semibold text-dark" for="fam_others">Others (Specify)</label>
                                                         </div>
                                                         <input type="text" name="family_other" class="form-control form-control-sm bg-white mb-1" placeholder="Specify other hereditary illnesses..." value="<?= h(is_array($familySaved) ? ($familySaved['Others'] ?? '') : '') ?>">
                                                         <select name="family_history_lineage[Others]" class="form-select form-select-sm bg-white py-0 px-2" style="font-size: 0.75rem;">
                                                             <option value="Unknown" <?= ($famLineageSaved['Others'] ?? '') === 'Unknown' ? 'selected' : '' ?>>-- Lineage: Unknown / Unspecified --</option>
                                                             <option value="Mother" <?= ($famLineageSaved['Others'] ?? '') === 'Mother' ? 'selected' : '' ?>>Mother (Maternal)</option>
                                                             <option value="Father" <?= ($famLineageSaved['Others'] ?? '') === 'Father' ? 'selected' : '' ?>>Father (Paternal)</option>
                                                             <option value="Both" <?= ($famLineageSaved['Others'] ?? '') === 'Both' ? 'selected' : '' ?>>Both Parents</option>
                                                         </select>
                                                     </div>
                                                 </div>
                                             </div>

                                            <!-- Group 2B: Hereditary Illnesses Checklist (4 Columns) -->
                                            <div class="border-top pt-2">
                                                <span class="text-secondary fw-semibold small d-block mb-2">Other Hereditary & Familial Conditions:</span>
                                                <div class="row g-2 small">
                                                    <?php
                                                    $familyGeneral = [
                                                        'Asthma' => 'Asthma',
                                                        'Cerebrovascular Disease' => 'Cerebrovascular Disease (Stroke)',
                                                        'Coronary Artery Disease' => 'Coronary Artery Disease',
                                                        'Diabetes Mellitus' => 'Diabetes Mellitus',
                                                        'Emphysema' => 'Emphysema / COPD',
                                                        'Epilepsy / Seizure Disease' => 'Epilepsy / Seizure Disease',
                                                        'Hyperlipidemia' => 'Hyperlipidemia',
                                                        'Kidney Disease' => 'Kidney Disease',
                                                        'Mental Disorder' => 'Mental Disorder',
                                                        'Peptic Ulcer Disease' => 'Peptic Ulcer Disease',
                                                        'Pneumonia' => 'Pneumonia',
                                                        'Thyroid Disease' => 'Thyroid Disease'
                                                    ];
                                                    foreach ($familyGeneral as $fKey => $fLabel):
                                                        $fChecked = is_array($familySaved) && (isset($familySaved[$fKey]) || in_array($fKey, $familySaved));
                                                    ?>
                                                        <div class="col-12 col-sm-6 col-md-3">
                                                            <div class="p-2 border rounded bg-light h-100">
                                                                <div class="form-check mb-1">
                                                                    <input class="form-check-input" type="checkbox" name="family_history[]" value="<?= $fKey ?>" id="fam_g_<?= md5($fKey) ?>" <?= $fChecked ? 'checked' : '' ?>>
                                                                    <label class="form-check-label fw-semibold text-dark small" for="fam_g_<?= md5($fKey) ?>"><?= $fLabel ?></label>
                                                                </div>
                                                                <select name="family_history_lineage[<?= $fKey ?>]" class="form-select form-select-sm bg-white py-0 px-1" style="font-size: 0.72rem;">
                                                                    <option value="Unknown" <?= ($famLineageSaved[$fKey] ?? '') === 'Unknown' ? 'selected' : '' ?>>-- Lineage --</option>
                                                                    <option value="Mother" <?= ($famLineageSaved[$fKey] ?? '') === 'Mother' ? 'selected' : '' ?>>Mother</option>
                                                                    <option value="Father" <?= ($famLineageSaved[$fKey] ?? '') === 'Father' ? 'selected' : '' ?>>Father</option>
                                                                    <option value="Both" <?= ($famLineageSaved[$fKey] ?? '') === 'Both' ? 'selected' : '' ?>>Both</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 3. Past Surgical History & Hospitalization -->
                                    <div class="col-12 col-md-6">
                                        <div class="card border rounded-3 p-3 h-100 shadow-xs">
                                            <h5 class="h6 fw-bold text-primary-dark mb-2">
                                                3. Past Surgical History & Hospitalization
                                            </h5>
                                            <div class="row g-2 small">
                                                <div class="col-12 col-sm-6">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Operation 1 Name</label>
                                                    <input type="text" name="operation_1_name" class="form-control form-control-sm" placeholder="e.g. Appendectomy" value="<?= h($surgicalSaved[0]['operation'] ?? '') ?>">
                                                </div>
                                                <div class="col-12 col-sm-6">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Operation 1 Date</label>
                                                    <input type="text" name="operation_1_date" class="form-control form-control-sm" placeholder="YYYY or YYYY-MM-DD" value="<?= h($surgicalSaved[0]['date'] ?? '') ?>">
                                                </div>

                                                <div class="col-12 col-sm-6">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Operation 2 Name</label>
                                                    <input type="text" name="operation_2_name" class="form-control form-control-sm" placeholder="e.g. CS Delivery" value="<?= h($surgicalSaved[1]['operation'] ?? '') ?>">
                                                </div>
                                                <div class="col-12 col-sm-6">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Operation 2 Date</label>
                                                    <input type="text" name="operation_2_date" class="form-control form-control-sm" placeholder="YYYY or YYYY-MM-DD" value="<?= h($surgicalSaved[1]['date'] ?? '') ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 4. Personal / Social History -->
                                    <div class="col-12 col-md-6">
                                        <div class="card border rounded-3 p-3 h-100 shadow-xs">
                                            <h5 class="h6 fw-bold text-primary-dark mb-2">
                                                4. Personal / Social History
                                            </h5>
                                            <div class="row g-2 small">
                                                <div class="col-12 col-sm-6">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Smoking Status</label>
                                                    <select name="smoking_status" class="form-select form-select-sm">
                                                        <option value="Never" <?= ($medicalHistory['smoking_status'] ?? 'Never') === 'Never' ? 'selected' : '' ?>>Never (No)</option>
                                                        <option value="Yes" <?= ($medicalHistory['smoking_status'] ?? '') === 'Yes' ? 'selected' : '' ?>>Yes (Active)</option>
                                                        <option value="Quit" <?= ($medicalHistory['smoking_status'] ?? '') === 'Quit' ? 'selected' : '' ?>>Quit</option>
                                                    </select>
                                                </div>
                                                <div class="col-12 col-sm-6">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">No. of Pack Years</label>
                                                    <input type="number" step="0.1" name="smoking_pack_years" class="form-control form-control-sm" placeholder="e.g. 5.0" value="<?= h($medicalHistory['smoking_pack_years'] ?? '') ?>">
                                                </div>

                                                <div class="col-12 col-sm-6">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Alcohol Drinking</label>
                                                    <select name="alcohol_status" class="form-select form-select-sm">
                                                        <option value="Never" <?= ($medicalHistory['alcohol_status'] ?? 'Never') === 'Never' ? 'selected' : '' ?>>Never (No)</option>
                                                        <option value="Yes" <?= ($medicalHistory['alcohol_status'] ?? '') === 'Yes' ? 'selected' : '' ?>>Yes (Regular/Occasional)</option>
                                                        <option value="Quit" <?= ($medicalHistory['alcohol_status'] ?? '') === 'Quit' ? 'selected' : '' ?>>Quit</option>
                                                    </select>
                                                </div>
                                                <div class="col-12 col-sm-6">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">No. of Bottles / Day</label>
                                                    <input type="number" step="0.1" name="alcohol_bottles_per_day" class="form-control form-control-sm" placeholder="e.g. 2.0" value="<?= h($medicalHistory['alcohol_bottles_per_day'] ?? '') ?>">
                                                </div>

                                                <div class="col-12">
                                                    <div class="form-check mt-1">
                                                        <input class="form-check-input" type="checkbox" name="illicit_drugs" value="1" id="illicit_drugs" <?= !empty($medicalHistory['illicit_drugs']) ? 'checked' : '' ?>>
                                                        <label class="form-check-label text-secondary fw-semibold small" for="illicit_drugs">History of Illicit Drug Use</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 5. Lifetime Immunizations (Annex A1) -->
                                    <div class="col-12 col-md-6">
                                        <div class="card border rounded-3 p-3 h-100 shadow-xs">
                                            <h5 class="h6 fw-bold text-primary-dark mb-2">
                                                5. Lifetime Immunizations (Annex A1)
                                            </h5>
                                            <div class="small">
                                                <!-- For children -->
                                                <label class="fw-semibold text-secondary small d-block mb-1">For Children:</label>
                                                <div class="row g-1 mb-2">
                                                    <?php 
                                                    $childVax = ['BCG', 'OPV1 / IPV1', 'OPV2 / IPV2', 'OPV3 / IPV3', 'DPT1', 'DPT2', 'DPT3', 'Measles', 'Hepatitis B1', 'Hepatitis B2', 'Hepatitis B3', 'Hepatitis A', 'Varicella (Chicken Pox)'];
                                                    $savedChild = $immSaved['children'] ?? [];
                                                    foreach ($childVax as $cv): 
                                                    ?>
                                                        <div class="col-6 col-sm-4">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="imm_children[]" value="<?= $cv ?>" id="imm_c_<?= md5($cv) ?>" <?= in_array($cv, $savedChild, true) ? 'checked' : '' ?>>
                                                                <label class="form-check-label text-dark" for="imm_c_<?= md5($cv) ?>" style="font-size: 0.75rem;"><?= $cv ?></label>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>

                                                <!-- For young women -->
                                                <label class="fw-semibold text-secondary small d-block mb-1 border-top pt-2">For Young Women:</label>
                                                <div class="row g-1 mb-2">
                                                    <?php 
                                                    $ywVax = ['HPV', 'MMR'];
                                                    $savedYw = $immSaved['young_women'] ?? [];
                                                    foreach ($ywVax as $yv): 
                                                    ?>
                                                        <div class="col-6">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="imm_young_women[]" value="<?= $yv ?>" id="imm_yw_<?= md5($yv) ?>" <?= in_array($yv, $savedYw, true) ? 'checked' : '' ?>>
                                                                <label class="form-check-label text-dark" for="imm_yw_<?= md5($yv) ?>" style="font-size: 0.75rem;"><?= $yv ?></label>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>

                                                <!-- For pregnant women -->
                                                <label class="fw-semibold text-secondary small d-block mb-1 border-top pt-2">For Pregnant Women:</label>
                                                <div class="row g-1 mb-2">
                                                    <?php 
                                                    $pregVax = ['Tetanus Toxoid'];
                                                    $savedPreg = $immSaved['pregnant'] ?? [];
                                                    foreach ($pregVax as $pv): 
                                                    ?>
                                                        <div class="col-6">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="imm_pregnant[]" value="<?= $pv ?>" id="imm_p_<?= md5($pv) ?>" <?= in_array($pv, $savedPreg, true) ? 'checked' : '' ?>>
                                                                <label class="form-check-label text-dark" for="imm_p_<?= md5($pv) ?>" style="font-size: 0.75rem;"><?= $pv ?></label>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>

                                                <!-- For elderly and immunocompromised -->
                                                <label class="fw-semibold text-secondary small d-block mb-1 border-top pt-2">For Elderly & Immunocompromised:</label>
                                                <div class="row g-1 mb-2">
                                                    <?php 
                                                    $eldVax = ['Pneumococcal Vaccine', 'Flu Vaccine'];
                                                    $savedEld = $immSaved['elderly'] ?? [];
                                                    foreach ($eldVax as $ev): 
                                                    ?>
                                                        <div class="col-6">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="imm_elderly[]" value="<?= $ev ?>" id="imm_e_<?= md5($ev) ?>" <?= in_array($ev, $savedEld, true) ? 'checked' : '' ?>>
                                                                <label class="form-check-label text-dark" for="imm_e_<?= md5($ev) ?>" style="font-size: 0.75rem;"><?= $ev ?></label>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>

                                                <!-- Others specify -->
                                                <div class="border-top pt-2">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Others (Specify)</label>
                                                    <input type="text" name="imm_others_specify" class="form-control form-control-sm" placeholder="Specify other vaccines received..." value="<?= h($immSaved['others'] ?? '') ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 6. Baseline Vitals & Anthropometrics (Annex A1) -->
                                    <div class="col-12 col-md-6">
                                        <div class="card border rounded-3 p-3 h-100 shadow-xs">
                                            <h5 class="h6 fw-bold text-primary-dark mb-2">
                                                6. Baseline Vitals & Anthropometrics (Annex A1)
                                            </h5>
                                            <div class="row g-2 small">
                                                <div class="col-12">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Baseline Blood Pressure</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" name="baseline_bp_systolic" class="form-control" placeholder="Systolic (e.g. 120)" min="50" max="300" value="<?= h($medicalHistory['baseline_bp_systolic'] ?? '') ?>">
                                                        <span class="input-group-text">/</span>
                                                        <input type="number" name="baseline_bp_diastolic" class="form-control" placeholder="Diastolic (e.g. 80)" min="30" max="200" value="<?= h($medicalHistory['baseline_bp_diastolic'] ?? '') ?>">
                                                        <span class="input-group-text">mmHg</span>
                                                    </div>
                                                </div>

                                                <div class="col-12 col-sm-6">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Heart Rate</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" name="baseline_heart_rate" class="form-control" placeholder="e.g. 72" min="30" max="250" value="<?= h($medicalHistory['baseline_heart_rate'] ?? '') ?>">
                                                        <span class="input-group-text">bpm</span>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-sm-6">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Respiratory Rate</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" name="baseline_respiratory_rate" class="form-control" placeholder="e.g. 18" min="8" max="60" value="<?= h($medicalHistory['baseline_respiratory_rate'] ?? '') ?>">
                                                        <span class="input-group-text">cpm</span>
                                                    </div>
                                                </div>

                                                <div class="col-12 col-sm-6">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Height</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" step="0.1" name="baseline_height" class="form-control" placeholder="e.g. 165" min="30" max="250" value="<?= h($medicalHistory['baseline_height'] ?? '') ?>">
                                                        <span class="input-group-text">cm</span>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-sm-6">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Weight</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" step="0.1" name="baseline_weight" class="form-control" placeholder="e.g. 60" min="1" max="300" value="<?= h($medicalHistory['baseline_weight'] ?? '') ?>">
                                                        <span class="input-group-text">kg</span>
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Waist Circumference</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" step="0.1" name="baseline_waist_circumference" class="form-control" placeholder="e.g. 78" min="20" max="200" value="<?= h($medicalHistory['baseline_waist_circumference'] ?? '') ?>">
                                                        <span class="input-group-text">cm</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 7. Pertinent Physical Examination Findings Checklist (Annex A1) -->
                                    <div class="col-12">
                                        <div class="card border rounded-3 p-3 shadow-xs">
                                            <h5 class="h6 fw-bold text-primary-dark mb-2">
                                                7. Pertinent Physical Examination Findings Checklist (Annex A1)
                                            </h5>
                                            <div class="row g-3 small">
                                                <!-- Skin -->
                                                <div class="col-12 col-md-4">
                                                    <div class="p-2 border rounded bg-light h-100">
                                                        <span class="fw-bold text-dark d-block mb-2"><i class="bi bi-person me-1 text-primary"></i>Skin</span>
                                                        <?php 
                                                        $skinItems = ['Pallor', 'Rashes', 'Jaundice', 'Good skin turgor'];
                                                        $savedSkin = $peSaved['skin'] ?? [];
                                                        foreach ($skinItems as $si): ?>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="pe_skin[]" value="<?= $si ?>" id="pe_skin_<?= md5($si) ?>" <?= in_array($si, $savedSkin, true) ? 'checked' : '' ?>>
                                                                <label class="form-check-label text-secondary" for="pe_skin_<?= md5($si) ?>"><?= $si ?></label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>

                                                <!-- HEENT -->
                                                <div class="col-12 col-md-4">
                                                    <div class="p-2 border rounded bg-light h-100">
                                                        <span class="fw-bold text-dark d-block mb-2"><i class="bi bi-eye me-1 text-primary"></i>HEENT</span>
                                                        <?php 
                                                        $heentItems = [
                                                            'Anicteric sclerae', 'Intact tympanic membrane', 'Tonsillopharyngeal congestion',
                                                            'Exudates', 'Pupils briskly reactive to light', 'Alar flaring',
                                                            'Hypertrophic tonsils', 'Aural discharge', 'Nasal discharge', 'Palpable mass'
                                                        ];
                                                        $savedHeent = $peSaved['heent'] ?? [];
                                                        foreach ($heentItems as $hi): ?>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="pe_heent[]" value="<?= $hi ?>" id="pe_heent_<?= md5($hi) ?>" <?= in_array($hi, $savedHeent, true) ? 'checked' : '' ?>>
                                                                <label class="form-check-label text-secondary" for="pe_heent_<?= md5($hi) ?>"><?= $hi ?></label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>

                                                <!-- Chest / Lungs -->
                                                <div class="col-12 col-md-4">
                                                    <div class="p-2 border rounded bg-light h-100">
                                                        <span class="fw-bold text-dark d-block mb-2"><i class="bi bi-lungs me-1 text-primary"></i>Chest / Lungs</span>
                                                        <?php 
                                                        $chestItems = ['Symmetrical chest expansion', 'Retractions', 'Wheezes', 'Clear breath sounds', 'Crackles / rales'];
                                                        $savedChest = $peSaved['chest_lungs'] ?? [];
                                                        foreach ($chestItems as $ci): ?>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="pe_chest_lungs[]" value="<?= $ci ?>" id="pe_cl_<?= md5($ci) ?>" <?= in_array($ci, $savedChest, true) ? 'checked' : '' ?>>
                                                                <label class="form-check-label text-secondary" for="pe_cl_<?= md5($ci) ?>"><?= $ci ?></label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>

                                                <!-- Heart -->
                                                <div class="col-12 col-md-4">
                                                    <div class="p-2 border rounded bg-light h-100">
                                                        <span class="fw-bold text-dark d-block mb-2"><i class="bi bi-heart-pulse me-1 text-primary"></i>Heart</span>
                                                        <?php 
                                                        $heartItems = ['Adynamic precordium', 'Normal rate regular rhythm', 'Heaves / thrills', 'Murmurs'];
                                                        $savedHeart = $peSaved['heart'] ?? [];
                                                        foreach ($heartItems as $hti): ?>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="pe_heart[]" value="<?= $hti ?>" id="pe_ht_<?= md5($hti) ?>" <?= in_array($hti, $savedHeart, true) ? 'checked' : '' ?>>
                                                                <label class="form-check-label text-secondary" for="pe_ht_<?= md5($hti) ?>"><?= $hti ?></label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>

                                                <!-- Abdomen -->
                                                <div class="col-12 col-md-4">
                                                    <div class="p-2 border rounded bg-light h-100">
                                                        <span class="fw-bold text-dark d-block mb-2"><i class="bi bi-shield-shaded me-1 text-primary"></i>Abdomen</span>
                                                        <?php 
                                                        $abdoItems = ['Flat', 'Flabby', 'Tenderness', 'Globular', 'Muscle guarding', 'Palpable mass'];
                                                        $savedAbdo = $peSaved['abdomen'] ?? [];
                                                        foreach ($abdoItems as $ai): ?>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="pe_abdomen[]" value="<?= $ai ?>" id="pe_ab_<?= md5($ai) ?>" <?= in_array($ai, $savedAbdo, true) ? 'checked' : '' ?>>
                                                                <label class="form-check-label text-secondary" for="pe_ab_<?= md5($ai) ?>"><?= $ai ?></label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>

                                                <!-- Extremities -->
                                                <div class="col-12 col-md-4">
                                                    <div class="p-2 border rounded bg-light h-100">
                                                        <span class="fw-bold text-dark d-block mb-2"><i class="bi bi-activity me-1 text-primary"></i>Extremities</span>
                                                        <?php 
                                                        $extItems = ['Gross deformity', 'Normal gait', 'Full and equal pulses'];
                                                        $savedExt = $peSaved['extremities'] ?? [];
                                                        foreach ($extItems as $ei): ?>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="pe_extremities[]" value="<?= $ei ?>" id="pe_ext_<?= md5($ei) ?>" <?= in_array($ei, $savedExt, true) ? 'checked' : '' ?>>
                                                                <label class="form-check-label text-secondary" for="pe_ext_<?= md5($ei) ?>"><?= $ei ?></label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>

                                                <!-- Remarks -->
                                                <div class="col-12">
                                                    <label class="form-label fw-semibold text-secondary small mb-1">Physical Examination Remarks / Other Findings</label>
                                                    <textarea name="pe_remarks" class="form-control form-control-sm" rows="2" placeholder="Record other physical examination findings or clinical notes..."><?= h($peSaved['remarks'] ?? '') ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 8. Female Menstrual & Reproductive History (if Female) -->
                                    <?php if ($isFemale): ?>
                                        <div class="col-12 col-md-6">
                                            <div class="card border rounded-3 p-3 h-100 shadow-xs">
                                                <h5 class="h6 fw-bold text-pink mb-2">
                                                    8. Female Menstrual & Reproductive History
                                                </h5>
                                                <div class="row g-2 small">
                                                    <div class="col-6 col-sm-4">
                                                        <label class="form-label fw-semibold text-secondary small mb-1">Menarche Age</label>
                                                        <input type="number" name="menarche_age" class="form-control form-control-sm" placeholder="e.g. 13" min="8" max="25" value="<?= h($medicalHistory['menarche_age'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-6 col-sm-4">
                                                        <label class="form-label fw-semibold text-secondary small mb-1">Sexual Onset</label>
                                                        <input type="number" name="sexual_onset_age" class="form-control form-control-sm" placeholder="e.g. 20" min="10" max="60" value="<?= h($medicalHistory['sexual_onset_age'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-12 col-sm-4">
                                                        <label class="form-label fw-semibold text-secondary small mb-1">LMP Date</label>
                                                        <input type="date" name="lmp" class="form-control form-control-sm " placeholder="YYYY-MM-DD" value="<?= h($medicalHistory['lmp'] ?? '') ?>">
                                                    </div>

                                                    <div class="col-6 col-sm-4">
                                                        <label class="form-label fw-semibold text-secondary small mb-1">Duration (days)</label>
                                                        <input type="number" name="period_duration_days" class="form-control form-control-sm" placeholder="e.g. 5" min="1" max="15" value="<?= h($medicalHistory['period_duration_days'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-6 col-sm-4">
                                                        <label class="form-label fw-semibold text-secondary small mb-1">Cycle (days)</label>
                                                        <input type="number" name="cycle_interval_days" class="form-control form-control-sm" placeholder="e.g. 28" min="15" max="60" value="<?= h($medicalHistory['cycle_interval_days'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-12 col-sm-4">
                                                        <label class="form-label fw-semibold text-secondary small mb-1">Pads / Day</label>
                                                        <input type="number" name="pads_per_day" class="form-control form-control-sm" placeholder="e.g. 3" min="1" max="20" value="<?= h($medicalHistory['pads_per_day'] ?? '') ?>">
                                                    </div>

                                                    <div class="col-12 col-sm-6">
                                                        <div class="form-check mt-1">
                                                            <input class="form-check-input" type="checkbox" name="is_menopausal" value="1" id="is_menopausal" <?= !empty($medicalHistory['is_menopausal']) ? 'checked' : '' ?>>
                                                            <label class="form-check-label text-secondary fw-semibold small" for="is_menopausal">Menopausal</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 col-sm-6">
                                                        <input type="number" name="menopause_age" class="form-control form-control-sm" placeholder="Menopause Age (e.g. 50)" min="30" max="70" value="<?= h($medicalHistory['menopause_age'] ?? '') ?>">
                                                    </div>

                                                    <div class="col-12">
                                                        <label class="form-label fw-semibold text-secondary small mb-1">Family Planning Method in Use</label>
                                                        <input type="text" name="birth_control_method" class="form-control form-control-sm" placeholder="e.g. Pills, BTL, IUD, Injectable, Condom, None" value="<?= h($medicalHistory['birth_control_method'] ?? '') ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- 9. Pregnancy History Card (if Female) -->
                                        <div class="col-12 col-md-6">
                                            <div class="card border rounded-3 p-3 h-100 shadow-xs">
                                                <h5 class="h6 fw-bold text-pink mb-2">
                                                    9. Pregnancy & Obstetric History (Annex A1)
                                                </h5>
                                                <div class="row g-2 small">
                                                    <div class="col-6 col-sm-3">
                                                        <label class="form-label fw-semibold text-secondary small mb-1">Gravida</label>
                                                        <input type="number" name="gravida" class="form-control form-control-sm" min="0" max="25" placeholder="G" value="<?= h($medicalHistory['gravida'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-6 col-sm-3">
                                                        <label class="form-label fw-semibold text-secondary small mb-1">Parity</label>
                                                        <input type="number" name="para" class="form-control form-control-sm" min="0" max="25" placeholder="P" value="<?= h($medicalHistory['para'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-12 col-sm-6">
                                                        <label class="form-label fw-semibold text-secondary small mb-1">Type of Delivery</label>
                                                        <input type="text" name="delivery_type" class="form-control form-control-sm" placeholder="e.g. NSD, CS, Forceps" value="<?= h($medicalHistory['delivery_type'] ?? '') ?>">
                                                    </div>

                                                    <div class="col-6 col-sm-3">
                                                        <label class="form-label fw-semibold text-secondary small mb-1">Full Term</label>
                                                        <input type="number" name="term_births" class="form-control form-control-sm" min="0" max="25" placeholder="F" value="<?= h($medicalHistory['term_births'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-6 col-sm-3">
                                                        <label class="form-label fw-semibold text-secondary small mb-1">Premature</label>
                                                        <input type="number" name="preterm_births" class="form-control form-control-sm" min="0" max="25" placeholder="P" value="<?= h($medicalHistory['preterm_births'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-6 col-sm-3">
                                                        <label class="form-label fw-semibold text-secondary small mb-1">Abortion</label>
                                                        <input type="number" name="abortions" class="form-control form-control-sm" min="0" max="25" placeholder="A" value="<?= h($medicalHistory['abortions'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-6 col-sm-3">
                                                        <label class="form-label fw-semibold text-secondary small mb-1">Living Children</label>
                                                        <input type="number" name="living_children" class="form-control form-control-sm" min="0" max="25" placeholder="L" value="<?= h($medicalHistory['living_children'] ?? '') ?>">
                                                    </div>

                                                    <div class="col-12 col-sm-6 border-top pt-2 mt-2">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="pre_eclampsia" value="1" id="pre_eclampsia" <?= !empty($medicalHistory['pre_eclampsia']) ? 'checked' : '' ?>>
                                                            <label class="form-check-label text-secondary fw-semibold small" for="pre_eclampsia">Pregnancy-Induced Hypertension (Pre-eclampsia)</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 col-sm-6 border-top pt-2 mt-2">
                                                        <label class="form-label fw-semibold text-secondary small mb-1 d-block">Access to Family Planning Counselling</label>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="fp_counselling" id="fp_counselling_yes" value="1" <?= (!isset($medicalHistory['fp_counselling']) || (int)$medicalHistory['fp_counselling'] === 1) ? 'checked' : '' ?>>
                                                            <label class="form-check-label small" for="fp_counselling_yes">Yes</label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="fp_counselling" id="fp_counselling_no" value="0" <?= (isset($medicalHistory['fp_counselling']) && (int)$medicalHistory['fp_counselling'] === 0) ? 'checked' : '' ?>>
                                                            <label class="form-check-label small" for="fp_counselling_no">No</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-4 pt-3 border-top text-end">
                                    <button type="button" class="btn btn-outline-secondary btn-sm px-3 me-2" onclick="cancelIhpEditMode()">
                                        Cancel
                                    </button>
                                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-xs">
                                        Save IHP Record
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- ==============================================================
                       TAB: PHIC / PCB PATIENT LEDGER (ANNEX A1 PAGE 3)
                       ============================================================== -->
                    <div class="tab-pane fade" id="tab-pcb" role="tabpanel">
                        
                        <!-- 1. Top PHIC Membership & Identity Card -->
                        <div class="card border rounded-3 p-3 shadow-xs mb-3 bg-white">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle bg-primary-subtle text-primary p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                        <i class="bi bi-shield-check fs-4"></i>
                                    </div>
                                    <div>
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <h4 class="h6 mb-0 fw-bold text-dark">PHILIPPINE HEALTH INSURANCE CORPORATION</h4>
                                            <span class="badge bg-primary text-white fw-medium">PCB PATIENT LEDGER</span>
                                        </div>
                                        <div class="text-muted small">
                                            Santa Rosa City Health Office I &bull; Page 3 Primary Care Benefit (PCB1) Service Record
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <div class="p-2 rounded bg-light border text-center">
                                        <span class="text-muted d-block" style="font-size: 0.7rem; text-transform: uppercase;">PhilHealth PIN</span>
                                        <span class="fw-bold text-primary font-monospace small"><?= !empty($patient['philhealth_no']) ? h($patient['philhealth_no']) : '<span class="text-muted fw-normal">No PIN Recorded</span>' ?></span>
                                    </div>
                                    <div class="p-2 rounded bg-light border text-center">
                                        <span class="text-muted d-block" style="font-size: 0.7rem; text-transform: uppercase;">Membership Status</span>
                                        <span class="badge <?= ($patient['phic_status'] ?? '') === 'Member' ? 'bg-success' : (($patient['phic_status'] ?? '') === 'Dependent' ? 'bg-info text-dark' : 'bg-secondary') ?>">
                                            <?= h($patient['phic_status'] ?? 'Non-Member') ?>
                                        </span>
                                    </div>
                                    <div class="p-2 rounded bg-light border text-center">
                                        <span class="text-muted d-block" style="font-size: 0.7rem; text-transform: uppercase;">PHIC Category</span>
                                        <span class="fw-semibold text-dark small"><?= !empty($patient['phic_type']) ? h($patient['phic_type']) : 'General / Standard' ?></span>
                                    </div>
                                    <a href="<?= url('/patients/' . $patient['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary py-2 px-3" title="Edit PhilHealth Demographics">
                                        <i class="bi bi-pencil me-1"></i> Edit PHIC Info
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Card 1: Obligated Services (Annual Tracking Grid) -->
                        <div class="card border rounded-3 shadow-xs mb-4">
                            <div class="card-header bg-white py-3 border-bottom d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
                                <div>
                                    <h5 class="h6 mb-0 fw-bold text-dark">
                                        <i class="bi bi-calendar-check text-primary me-2"></i>1. Obligated Primary Preventive Services (PCB1)
                                    </h5>
                                    <span class="text-muted small">Annual and quarterly monitoring matrix for mandated preventive care services.</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="input-group input-group-sm" style="width: auto;">
                                        <span class="input-group-text bg-light text-muted border-secondary-subtle">Year</span>
                                        <select name="pcb_year" id="pcb_year_select" class="form-select border-secondary-subtle fw-medium text-primary" style="min-width: 90px;" onchange="window.location.href='<?= url('/patients/' . $patient['id']) ?>?pcb_year=' + this.value + '#tab-pcb'">
                                            <?php 
                                            $currYr = (int)date('Y');
                                            // Show next year, current year, and up to 10 past years
                                            for ($yr = $currYr + 1; $yr >= $currYr - 10; $yr--): ?>
                                                <option value="<?= $yr ?>" <?= $pcbYear === $yr ? 'selected' : '' ?>><?= $yr ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#obligatedEditModal">
                                        <i class="bi bi-pencil-square me-1"></i> Update Dates
                                    </button>
                                </div>
                            </div>

                            <!-- Read-Only Table View matching Page 3 -->
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 text-center small">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-start ps-3" style="width: 32%;">Primary Preventive Services</th>
                                            <th style="width: 18%;">Frequency</th>
                                            <th style="width: 12.5%;">1<sup>st</sup> Qtr</th>
                                            <th style="width: 12.5%;">2<sup>nd</sup> Qtr</th>
                                            <th style="width: 12.5%;">3<sup>rd</sup> Qtr</th>
                                            <th style="width: 12.5%;" class="pe-3">4<sup>th</sup> Qtr</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Service 1: BP Measurements -->
                                        <tr>
                                            <td class="text-start ps-3 fw-bold text-dark">
                                                1. BP Measurements
                                                <?php if (!empty($pcbObligated['is_hypertensive'])): ?>
                                                    <span class="badge bg-danger-subtle text-danger border ms-1">Hypertensive</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success-subtle text-success border ms-1">Non-Hypertensive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= !empty($pcbObligated['is_hypertensive']) ? '<span class="text-danger fw-medium">Once a month</span>' : '<span class="text-muted">Once a year</span>' ?>
                                            </td>
                                            <td><?= !empty($pcbObligated['bp_q1']) ? '<span class="badge bg-light text-dark border font-monospace">' . date('M d, Y', strtotime($pcbObligated['bp_q1'])) . '</span>' : '<span class="text-muted">&mdash;</span>' ?></td>
                                            <td><?= !empty($pcbObligated['bp_q2']) ? '<span class="badge bg-light text-dark border font-monospace">' . date('M d, Y', strtotime($pcbObligated['bp_q2'])) . '</span>' : '<span class="text-muted">&mdash;</span>' ?></td>
                                            <td><?= !empty($pcbObligated['bp_q3']) ? '<span class="badge bg-light text-dark border font-monospace">' . date('M d, Y', strtotime($pcbObligated['bp_q3'])) . '</span>' : '<span class="text-muted">&mdash;</span>' ?></td>
                                            <td class="pe-3"><?= !empty($pcbObligated['bp_q4']) ? '<span class="badge bg-light text-dark border font-monospace">' . date('M d, Y', strtotime($pcbObligated['bp_q4'])) . '</span>' : '<span class="text-muted">&mdash;</span>' ?></td>
                                        </tr>

                                        <!-- Service 2: Periodic Clinical Breast Exam -->
                                        <tr>
                                            <td class="text-start ps-3 fw-bold text-dark">2. Periodic Clinical Breast Examination</td>
                                            <td class="text-muted">Once a year</td>
                                            <td><?= !empty($pcbObligated['cbe_q1']) ? '<span class="badge bg-light text-dark border font-monospace">' . date('M d, Y', strtotime($pcbObligated['cbe_q1'])) . '</span>' : '<span class="text-muted">&mdash;</span>' ?></td>
                                            <td><?= !empty($pcbObligated['cbe_q2']) ? '<span class="badge bg-light text-dark border font-monospace">' . date('M d, Y', strtotime($pcbObligated['cbe_q2'])) . '</span>' : '<span class="text-muted">&mdash;</span>' ?></td>
                                            <td><?= !empty($pcbObligated['cbe_q3']) ? '<span class="badge bg-light text-dark border font-monospace">' . date('M d, Y', strtotime($pcbObligated['cbe_q3'])) . '</span>' : '<span class="text-muted">&mdash;</span>' ?></td>
                                            <td class="pe-3"><?= !empty($pcbObligated['cbe_q4']) ? '<span class="badge bg-light text-dark border font-monospace">' . date('M d, Y', strtotime($pcbObligated['cbe_q4'])) . '</span>' : '<span class="text-muted">&mdash;</span>' ?></td>
                                        </tr>

                                        <!-- Service 3: Visual Inspection with Acetic Acid (Applicable to Females) -->
                                        <?php if ($isFemale): ?>
                                            <tr>
                                                <td class="text-start ps-3 fw-bold text-dark">3. Visual Inspection with Acetic Acid (VIA)</td>
                                                <td class="text-muted">Once a year</td>
                                                <td><?= !empty($pcbObligated['via_q1']) ? '<span class="badge bg-light text-dark border font-monospace">' . date('M d, Y', strtotime($pcbObligated['via_q1'])) . '</span>' : '<span class="text-muted">&mdash;</span>' ?></td>
                                                <td><?= !empty($pcbObligated['via_q2']) ? '<span class="badge bg-light text-dark border font-monospace">' . date('M d, Y', strtotime($pcbObligated['via_q2'])) . '</span>' : '<span class="text-muted">&mdash;</span>' ?></td>
                                                <td><?= !empty($pcbObligated['via_q3']) ? '<span class="badge bg-light text-dark border font-monospace">' . date('M d, Y', strtotime($pcbObligated['via_q3'])) . '</span>' : '<span class="text-muted">&mdash;</span>' ?></td>
                                                <td class="pe-3"><?= !empty($pcbObligated['via_q4']) ? '<span class="badge bg-light text-dark border font-monospace">' . date('M d, Y', strtotime($pcbObligated['via_q4'])) . '</span>' : '<span class="text-muted">&mdash;</span>' ?></td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if (!empty($pcbObligated['remarks'])): ?>
                                <div class="card-footer bg-light py-2 px-3 small border-top text-muted">
                                    <strong class="text-dark">Remarks:</strong> <?= h($pcbObligated['remarks']) ?>
                                    <?php if (!empty($pcbObligated['updater_name'])): ?>
                                        &bull; <span class="fst-italic">Last updated by <?= h($pcbObligated['updater_name']) ?> on <?= date('M d, Y h:i A', strtotime($pcbObligated['updated_at'])) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- 3. Card 2: Diagnostic Examination, Other PCB1, & Other Services Encounter Ledger -->
                        <div class="card border rounded-3 shadow-xs">
                            <div class="card-header bg-white py-3 border-bottom d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
                                <div>
                                    <h5 class="h6 mb-0 fw-bold text-dark">
                                        <i class="bi bi-journal-medical text-primary me-2"></i>2. Diagnostic Examination & PCB Services Encounter Ledger
                                    </h5>
                                    <span class="text-muted small">Diagnostic tests, laboratory orders, and clinical services performed or referred under the PCB1 package.</span>
                                </div>
                                <button type="button" class="btn btn-sm btn-primary px-3 fw-medium" data-bs-toggle="modal" data-bs-target="#recordPcbServiceModal">
                                    <i class="bi bi-plus-lg me-1"></i> Record Service / Test
                                </button>
                            </div>

                            <!-- Filter Pills -->
                            <div class="card-body bg-light py-2 px-3 border-bottom">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                    <div class="btn-group btn-group-sm" role="group" id="pcbCategoryFilters">
                                        <button type="button" class="btn btn-outline-primary active" onclick="filterPcbRows('all', this)">All Encounters</button>
                                        <button type="button" class="btn btn-outline-primary" onclick="filterPcbRows('Diagnostic', this)">Diagnostic Examinations</button>
                                        <button type="button" class="btn btn-outline-primary" onclick="filterPcbRows('PCB1', this)">Other PCB1 Services</button>
                                        <button type="button" class="btn btn-outline-primary" onclick="filterPcbRows('Other', this)">Other Services</button>
                                    </div>
                                    <span class="text-muted small">
                                        Total Records: <strong class="text-dark" id="pcbRowCount"><?= count($pcbServiceLogs) ?></strong>
                                    </span>
                                </div>
                            </div>

                            <!-- Encounter Records Table -->
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 small text-center" id="pcbServiceLogsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-start ps-3" style="width: 14%;">Date</th>
                                            <th style="width: 15%;">Section / Category</th>
                                            <th class="text-start" style="width: 22%;">Service / Test Type</th>
                                            <th class="text-start" style="width: 18%;">Diagnosis</th>
                                            <th style="width: 11%;">Given</th>
                                            <th style="width: 12%;">Referred</th>
                                            <th class="pe-3 text-end" style="width: 8%;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($pcbServiceLogs)): ?>
                                            <tr id="pcbNoRecordsRow">
                                                <td colspan="7" class="text-center py-5 text-muted">
                                                    <i class="bi bi-clipboard2-pulse fs-3 d-block mb-2 text-secondary"></i>
                                                    <p class="fw-medium mb-1">No diagnostic or PCB services recorded yet</p>
                                                    <p class="small text-muted mb-3">Click below to record lab examinations, diagnostic tests, or primary care benefit encounters.</p>
                                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recordPcbServiceModal">
                                                        <i class="bi bi-plus-lg me-1"></i> Record Service
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($pcbServiceLogs as $log): ?>
                                                <tr class="pcb-log-row" data-category="<?= h($log['service_category']) ?>">
                                                    <td class="text-start ps-3 fw-medium text-dark font-monospace">
                                                        <?= date('M d, Y', strtotime($log['service_date'])) ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($log['service_category'] === 'Diagnostic'): ?>
                                                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1">Diagnostic Exam</span>
                                                        <?php elseif ($log['service_category'] === 'PCB1'): ?>
                                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">Other PCB1</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">Other Services</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-start fw-bold text-dark">
                                                        <?= h($log['service_type']) ?>
                                                        <?php if (!empty($log['remarks'])): ?>
                                                            <div class="text-muted small fw-normal"><?= h($log['remarks']) ?></div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-start text-secondary">
                                                        <?= !empty($log['diagnosis']) ? h($log['diagnosis']) : '<span class="text-muted fst-italic">&mdash;</span>' ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($log['status_given'])): ?>
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                                <i class="bi bi-check-lg me-1"></i>Given
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-muted">&mdash;</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($log['status_referred'])): ?>
                                                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1" title="<?= !empty($log['referred_to']) ? 'Referred to: ' . h($log['referred_to']) : 'Referred' ?>">
                                                                <i class="bi bi-arrow-up-right me-1"></i><?= !empty($log['referred_to']) ? h($log['referred_to']) : 'Referred' ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-muted">&mdash;</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="pe-3 text-end text-nowrap">
                                                        <?php 
                                                            $canDeletePcb = is_admin();
                                                        ?>
                                                        <div class="d-inline-flex gap-1 justify-content-end align-items-center">
                                                            <button type="button" class="btn btn-sm btn-outline-primary border-0 p-1 btn-view-pcb-service"
                                                                data-id="<?= $log['id'] ?>"
                                                                data-date="<?= date('M d, Y', strtotime($log['service_date'])) ?>"
                                                                data-category="<?= h($log['service_category']) ?>"
                                                                data-type="<?= h($log['service_type']) ?>"
                                                                data-diagnosis="<?= h($log['diagnosis'] ?? 'None specified') ?>"
                                                                data-given="<?= !empty($log['status_given']) ? 'Yes (Provided/Administered)' : 'No' ?>"
                                                                data-referred="<?= !empty($log['status_referred']) ? 'Yes (' . h($log['referred_to'] ?? 'External Provider') . ')' : 'No' ?>"
                                                                data-remarks="<?= h($log['remarks'] ?? 'None') ?>"
                                                                data-recorder="<?= h($log['recorder_name'] ?? 'System') ?>"
                                                                title="View Encounter Details">
                                                                <i class="bi bi-eye fs-6"></i>
                                                            </button>
                                                                <button type="button" class="btn btn-sm btn-outline-secondary border-0 p-1 btn-edit-pcb-service"
                                                                    data-id="<?= $log['id'] ?>"
                                                                    data-date="<?= h($log['service_date']) ?>"
                                                                    data-category="<?= h($log['service_category']) ?>"
                                                                    data-type="<?= h($log['service_type']) ?>"
                                                                    data-diagnosis="<?= h($log['diagnosis'] ?? '') ?>"
                                                                    data-given="<?= !empty($log['status_given']) ? '1' : '0' ?>"
                                                                    data-referred="<?= !empty($log['status_referred']) ? '1' : '0' ?>"
                                                                    data-referred-to="<?= h($log['referred_to'] ?? '') ?>"
                                                                    data-remarks="<?= h($log['remarks'] ?? '') ?>"
                                                                    title="Edit Encounter">
                                                                    <i class="bi bi-pencil-square fs-6"></i>
                                                                </button>
                                                            <?php if ($canDeletePcb): ?>
                                                                <form action="<?= url('/pcb/service-log/' . $log['id'] . '/delete') ?>" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this <?= h($log['service_type']) ?> encounter record?');">
                                                                    <?= csrf_field() ?>
                                                                    <button type="submit" class="btn btn-sm btn-outline-danger border-0 p-1" title="Delete Entry">
                                                                        <i class="bi bi-trash fs-6"></i>
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>

                    <!-- ==============================================================
                       TAB 3: CONSULTATIONS (SOAP)
                       ============================================================== -->
                    <div class="tab-pane fade" id="tab-consultations" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="h6 fw-bold text-dark mb-0">Consultations History (SOAP Notes)</h5>
                            <a href="<?= url('/patients/' . $patient['id'] . '/consultations/create') ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus-lg me-1"></i> New Consultation
                            </a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 text-center small" id="consultationsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-start ps-3">Date</th>
                                        <th>Clinician</th>
                                        <th>Assessment / Diagnosis</th>
                                        <th>Status</th>
                                        <th class="pe-3 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($consultationsHistory)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">
                                                <i class="bi bi-clipboard2-x fs-3 d-block mb-2 text-secondary"></i>
                                                No consultation records exist for this patient.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php 
                                            $curUserId = (int)($_SESSION['user_id'] ?? 0);
                                            $curRole = $_SESSION['role'] ?? 'staff';
                                        ?>
                                        <?php foreach ($consultationsHistory as $c): 
                                            $canArchiveConsultation = is_admin();
                                            $canEditRow = ($c['status'] !== 'Cancelled');
                                            
                                            $badgeClass = 'bg-success-subtle text-success border border-success-subtle';
                                            if ($c['status'] === 'Cancelled') {
                                                $badgeClass = 'bg-danger-subtle text-danger border border-danger-subtle';
                                            } elseif ($c['status'] === 'Open') {
                                                $badgeClass = 'bg-secondary-subtle text-secondary border';
                                            }
                                        ?>
                                            <tr>
                                                <td class="text-start ps-3 fw-medium text-dark"><?= date('M d, Y h:i A', strtotime($c['consulted_at'])) ?></td>
                                                <td><?= h($c['clinician_name']) ?></td>
                                                <td class="text-start"><?= h(mb_strimwidth($c['assessment'], 0, 50, '...')) ?></td>
                                                <td><span class="badge <?= $badgeClass ?>"><?= h($c['status']) ?></span></td>
                                                <td class="pe-3 text-end text-nowrap">
                                                    <div class="d-inline-flex gap-1 justify-content-end align-items-center">
                                                        <button type="button" class="btn btn-sm btn-outline-primary border-0 p-1 view-consultation-btn" data-consultation-id="<?= $c['id'] ?>" title="View Full SOAP">
                                                            <i class="bi bi-eye fs-6"></i>
                                                        </button>
                                                        <?php if ($canEditRow): ?>
                                                            <a href="<?= url('/consultations/' . $c['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary border-0 p-1" title="Edit Consultation">
                                                                <i class="bi bi-pencil-square fs-6"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <?php if ($canArchiveConsultation): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-danger border-0 p-1 btn-archive-consultation" data-id="<?= $c['id'] ?>" data-patient-id="<?= $patient['id'] ?>" title="Archive Consultation">
                                                                <i class="bi bi-archive fs-6"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ==============================================================
                       TAB 4: VITAL SIGNS HISTORY
                       ============================================================== -->
                    <div class="tab-pane fade" id="tab-vitals" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="h6 fw-bold text-dark mb-0">Vital Signs Log</h5>
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addVitalsModal">
                                <i class="bi bi-plus-lg me-1"></i> Record Vitals
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 text-center small" id="vitalsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-start ps-3">Recorded Date</th>
                                        <th>BP (mmHg)</th>
                                        <th>Pulse (bpm)</th>
                                        <th>Temp (°C)</th>
                                        <th>Recorded By</th>
                                        <th class="pe-3 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($vitalsHistory)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-5 text-muted">
                                                <i class="bi bi-activity fs-3 d-block mb-2 text-secondary"></i>
                                                No vital signs records exist for this patient.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php 
                                            $curUserId = (int)($_SESSION['user_id'] ?? 0);
                                            $curRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'staff';
                                        ?>
                                        <?php foreach ($vitalsHistory as $v): 
                                            $canDeleteVital = is_admin();
                                        ?>
                                            <tr>
                                                <td class="text-start ps-3 fw-medium text-dark"><?= date('M d, Y h:i A', strtotime($v['recorded_at'])) ?></td>
                                                <td class="fw-bold font-monospace"><?= h($v['bp_systolic'] ?? '--') ?>/<?= h($v['bp_diastolic'] ?? '--') ?></td>
                                                <td><?= h($v['heart_rate'] ?? '--') ?></td>
                                                <td><?= h($v['temperature'] ?? '--') ?></td>
                                                <td class="text-muted"><?= h($v['recorder_name'] ?? 'Clinician') ?></td>
                                                <td class="pe-3 text-end text-nowrap">
                                                    <div class="d-inline-flex gap-1 justify-content-end align-items-center">
                                                        <button type="button" class="btn btn-sm btn-outline-primary border-0 p-1 btn-view-vitals" 
                                                            data-id="<?= $v['id'] ?>"
                                                            data-date="<?= date('M d, Y h:i A', strtotime($v['recorded_at'])) ?>"
                                                            data-bp="<?= h(($v['bp_systolic'] ?? '--') . '/' . ($v['bp_diastolic'] ?? '--')) ?>"
                                                            data-pulse="<?= h($v['heart_rate'] ?? '--') ?>"
                                                            data-temp="<?= h($v['temperature'] ?? '--') ?>"
                                                            data-resp="<?= h($v['respiratory_rate'] ?? '--') ?>"
                                                            data-spo2="<?= h($v['oxygen_saturation'] ?? '--') ?>"
                                                            data-weight="<?= h($v['weight'] ?? '--') ?>"
                                                            data-height="<?= h($v['height'] ?? '--') ?>"
                                                            data-bmi="<?= h($v['bmi'] ?? '--') ?>"
                                                            data-waist="<?= h($v['waist_circumference'] ?? '--') ?>"
                                                            data-notes="<?= h($v['notes'] ?? '') ?>"
                                                            data-recorder="<?= h($v['recorder_name'] ?? 'Clinician') ?>"
                                                            title="View Details">
                                                            <i class="bi bi-eye fs-6"></i>
                                                        </button>
                                                            <button type="button" class="btn btn-sm btn-outline-secondary border-0 p-1 btn-edit-vitals"
                                                                data-id="<?= $v['id'] ?>"
                                                                data-bp-systolic="<?= h($v['bp_systolic'] ?? '') ?>"
                                                                data-bp-diastolic="<?= h($v['bp_diastolic'] ?? '') ?>"
                                                                data-pulse="<?= h($v['heart_rate'] ?? '') ?>"
                                                                data-temp="<?= h($v['temperature'] ?? '') ?>"
                                                                data-resp="<?= h($v['respiratory_rate'] ?? '') ?>"
                                                                data-spo2="<?= h($v['oxygen_saturation'] ?? '') ?>"
                                                                data-weight="<?= h($v['weight'] ?? '') ?>"
                                                                data-height="<?= h($v['height'] ?? '') ?>"
                                                                data-waist="<?= h($v['waist_circumference'] ?? '') ?>"
                                                                data-notes="<?= h($v['notes'] ?? '') ?>"
                                                                title="Edit Vital Signs">
                                                                <i class="bi bi-pencil-square fs-6"></i>
                                                            </button>
                                                        <?php if ($canDeleteVital): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-danger border-0 p-1 btn-delete-vital" data-id="<?= $v['id'] ?>" title="Delete Vital Signs">
                                                                <i class="bi bi-trash fs-6"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ==============================================================
                       TAB 5: UNIVERSAL IMMUNIZATIONS
                       ============================================================== -->
                    <div class="tab-pane fade" id="tab-immunizations" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="h6 fw-bold text-dark mb-0">Universal Immunization Records</h5>
                                <span class="text-muted small">Tracks vaccines administered across all life stages (EPI Routine Infant, HPV, COVID-19, Flu, Pneumococcal).</span>
                            </div>
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#recordImmunizationModal">
                                <i class="bi bi-plus-lg me-1"></i> Record Vaccine Dose
                            </button>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 text-center small" id="immunizationsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-start ps-3">Vaccine Name</th>
                                        <th>Dose #</th>
                                        <th>Administered Date</th>
                                        <th>Source / Status</th>
                                        <th>Remarks / Program</th>
                                        <th>Vaccinator</th>
                                        <th class="pe-3 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($patientImmunizations)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-5 text-muted">
                                                <i class="bi bi-shield-slash fs-3 d-block mb-2 text-secondary"></i>
                                                No immunization records recorded for this patient.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php 
                                            $curUserId = (int)($_SESSION['user_id'] ?? 0);
                                            $curRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'staff';
                                        ?>
                                        <?php foreach ($patientImmunizations as $imm): 
                                            $canDeleteImm = is_admin();
                                        ?>
                                            <tr>
                                                <td class="text-start ps-3 fw-bold text-primary">
                                                    <i class="bi bi-shield-check me-1 text-success"></i><?= h($imm['vaccine_name']) ?>
                                                </td>
                                                <td><span class="badge bg-light text-dark border">Dose <?= h($imm['dose_number']) ?></span></td>
                                                <td class="fw-medium text-dark"><?= date('M d, Y', strtotime($imm['administered_date'])) ?></td>
                                                <td class="text-muted small">
                                                    <?= h($imm['source'] ?? 'Health Center') ?>
                                                    <span class="badge bg-light text-dark border"><?= h($imm['documentation_status'] ?? 'Administered') ?></span>
                                                </td>
                                                <td class="text-muted small"><?= h($imm['remarks'] ?? 'Routine') ?></td>
                                                <td class="text-muted"><?= h($imm['vaccinator_name'] ?? 'Healthcare Staff') ?></td>
                                                <td class="pe-3 text-end text-nowrap">
                                                    <div class="d-inline-flex gap-1 justify-content-end align-items-center">
                                                        <button type="button" class="btn btn-sm btn-outline-primary border-0 p-1 btn-view-immunization"
                                                            data-id="<?= $imm['id'] ?>"
                                                            data-vaccine="<?= h($imm['vaccine_name']) ?>"
                                                            data-dose="Dose <?= h($imm['dose_number']) ?>"
                                                            data-date="<?= date('M d, Y', strtotime($imm['administered_date'])) ?>"
                                                            data-source="<?= h($imm['source'] ?? 'Barangay Sinalhan Health Center') ?>"
                                                            data-status="<?= h($imm['documentation_status'] ?? 'Administered') ?>"
                                                            data-remarks="<?= h($imm['remarks'] ?? 'Routine immunisation protocol') ?>"
                                                            data-vaccinator="<?= h($imm['vaccinator_name'] ?? 'Healthcare Staff') ?>"
                                                            title="View Immunization Details">
                                                            <i class="bi bi-eye fs-6"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary border-0 p-1 btn-edit-immunization"
                                                            data-id="<?= $imm['id'] ?>"
                                                            data-vaccine="<?= h($imm['vaccine_name']) ?>"
                                                            data-dose="<?= h($imm['dose_number']) ?>"
                                                            data-date="<?= h($imm['administered_date']) ?>"
                                                            data-source="<?= h($imm['source'] ?? 'Health Center') ?>"
                                                            data-status="<?= h($imm['documentation_status'] ?? 'Administered') ?>"
                                                            data-remarks="<?= h($imm['remarks'] ?? '') ?>"
                                                            title="Edit Immunization">
                                                            <i class="bi bi-pencil-square fs-6"></i>
                                                        </button>
                                                        <?php if ($canDeleteImm): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-danger border-0 p-1 btn-delete-immunization" data-id="<?= $imm['id'] ?>" data-vaccine="<?= h($imm['vaccine_name']) ?>" data-dose="<?= h($imm['dose_number']) ?>" title="Delete Record">
                                                                <i class="bi bi-trash fs-6"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ==============================================================
                       TAB 8: APPOINTMENTS & QUEUE
                       ============================================================== -->
                    <div class="tab-pane fade" id="tab-appointments" role="tabpanel">
                        <div class="row g-3">
                            <!-- Appointments List -->
                            <div class="col-12 col-md-6">
                                <div class="card border rounded-3 p-3 shadow-xs h-100">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="fw-bold mb-0 text-dark small">Scheduled Appointments</h6>
                                        <a href="<?= url('/appointments/create?patient_id=' . $patient['id']) ?>" class="btn btn-xs btn-outline-primary py-1 px-2">
                                            <i class="bi bi-plus-circle me-1"></i> Book
                                        </a>
                                    </div>
                                    <?php if (empty($appointmentsHistory)): ?>
                                        <p class="text-muted small text-center py-3 mb-0">No upcoming appointments scheduled.</p>
                                    <?php else: ?>
                                        <ul class="list-group list-group-flush small">
                                            <?php foreach ($appointmentsHistory as $a): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                                    <div>
                                                        <strong><?= date('M d, Y', strtotime($a['appointment_date'])) ?></strong> at <?= date('h:i A', strtotime($a['appointment_time'])) ?>
                                                        <span class="text-muted d-block" style="font-size: 0.75rem;"><?= h($a['purpose']) ?></span>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="badge bg-light text-dark border"><?= h($a['status']) ?></span>
                                                        <div class="d-inline-flex gap-1 align-items-center">
                                                            <a href="<?= url('/appointments/' . $a['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary border-0 p-1" title="Reschedule / Edit">
                                                                <i class="bi bi-pencil-square fs-6"></i>
                                                            </a>
                                                            <?php if ($a['status'] !== 'Cancelled'): ?>
                                                                <button type="button" class="btn btn-sm btn-outline-danger border-0 p-1 btn-cancel-appointment" data-id="<?= $a['id'] ?>" data-date="<?= date('M d, Y', strtotime($a['appointment_date'])) ?>" title="Cancel Appointment">
                                                                    <i class="bi bi-x-circle fs-6"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Queue Logs -->
                            <div class="col-12 col-md-6">
                                <div class="card border rounded-3 p-3 shadow-xs h-100">
                                    <h6 class="fw-bold mb-2 text-dark small">Daily Queue Visits</h6>
                                    <?php if (empty($queueHistory)): ?>
                                        <p class="text-muted small text-center py-3 mb-0">No daily queue visits recorded.</p>
                                    <?php else: ?>
                                        <ul class="list-group list-group-flush small">
                                            <?php foreach ($queueHistory as $q): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                                    <div>
                                                        <strong><?= h($q['queue_date']) ?></strong> &bull; Queue #<?= sprintf('%03d', $q['queue_no']) ?>
                                                        <span class="text-muted d-block" style="font-size: 0.75rem;">Time In: <?= $q['time_in'] ? date('h:i A', strtotime($q['time_in'])) : '--' ?></span>
                                                    </div>
                                                    <span class="badge bg-primary-subtle text-primary border"><?= h($q['status']) ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

<!-- ==========================================================================
   UNIVERSAL IMMUNIZATION MODAL (Any Patient)
   ========================================================================== -->
<div class="modal fade" id="recordImmunizationModal" tabindex="-1" aria-labelledby="recordImmunizationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="recordImmunizationModalLabel">
                    <i class="bi bi-shield-plus me-2"></i>Record Vaccine Dose
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="<?= url('/patients/' . $patient['id'] . '/immunizations/record') ?>" method="POST" id="singleImmunizationForm">
                <?= csrf_field() ?>
                <input type="hidden" name="redirect_to" value="<?= url('/patients/' . $patient['id'] . '#tab-immunizations') ?>">

                <div class="modal-body p-4 bg-white small">
                    <div class="mb-3">
                        <label for="vaccine_name" class="form-label fw-semibold text-secondary">Vaccine Name <span class="text-danger">*</span></label>
                        <select name="vaccine_name" class="form-select" required>
                            <option value="">-- Select Vaccine --</option>
                            <optgroup label="Routine Infant EPI">
                                <option value="BCG">BCG</option>
                                <option value="Hepatitis B">Hepatitis B</option>
                                <option value="Pentavalent">Pentavalent (DTP-HepB-Hib)</option>
                                <option value="OPV">Oral Polio Vaccine (OPV)</option>
                                <option value="IPV">Inactivated Polio (IPV)</option>
                                <option value="Rotavirus">Rotavirus</option>
                                <option value="PCV">Pneumococcal Conjugate (PCV)</option>
                                <option value="MCV">Measles / MMR (MCV)</option>
                            </optgroup>
                            <optgroup label="Adolescent & Adult Vaccines">
                                <option value="HPV">HPV (Human Papillomavirus)</option>
                                <option value="Tetanus Toxoid">Tetanus Toxoid (TT / Td)</option>
                                <option value="Influenza">Influenza (Flu)</option>
                                <option value="Pneumococcal Polysaccharide">Pneumococcal (PPV23 / Senior)</option>
                                <option value="COVID-19">COVID-19</option>
                                <option value="Hepatitis A">Hepatitis A</option>
                            </optgroup>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="dose_number" class="form-label fw-semibold text-secondary">Dose Number <span class="text-danger">*</span></label>
                            <input type="number" name="dose_number" class="form-control font-monospace" value="1" min="1" max="10" required>
                        </div>
                        <div class="col-6">
                            <label for="administered_date" class="form-label fw-semibold text-secondary">Administered Date <span class="text-danger">*</span></label>
                            <input type="date" name="administered_date" class="form-control  bg-white" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label for="source" class="form-label fw-semibold text-secondary">Source</label>
                                <select name="source" class="form-select">
                                    <option value="Health Center">Health Center</option>
                                    <option value="External">External facility</option>
                                    <option value="Patient Reported">Patient/parent reported</option>
                                    <option value="Unknown">Unknown</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label for="documentation_status" class="form-label fw-semibold text-secondary">Documentation status</label>
                                <select name="documentation_status" class="form-select">
                                    <option value="Administered">Administered</option>
                                    <option value="Reported">Reported</option>
                                    <option value="Unknown">Unknown</option>
                                </select>
                            </div>
                        </div>
                        <label for="remarks" class="form-label fw-semibold text-secondary">Remarks / Lot No. / Site</label>
                        <input type="text" name="remarks" class="form-control" placeholder="e.g. Lot #ABC-123, Left Deltoid, Bakuna Eskwela">
                    </div>
                </div>
                
                <div class="modal-footer bg-light py-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold">Record Immunization</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==========================================================================
   VITAL SIGNS RECORDING MODAL
   ========================================================================== -->
<div class="modal fade" id="addVitalsModal" tabindex="-1" aria-labelledby="addVitalsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="addVitalsModalLabel">
                    <i class="bi bi-heart-pulse-fill me-2"></i>Record Vital Signs
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="<?= url('/vital-signs') ?>" method="POST" id="vitalsForm">
                <?= csrf_field() ?>
                <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">

                <div class="modal-body p-4 bg-white">
                    <div class="text-muted small mb-3">
                        Patient: <strong><?= h($patient['last_name']) ?>, <?= h($patient['first_name']) ?></strong> &bull; DOB: <?= h($patient['dob']) ?>
                    </div>
                    
                    <div class="row g-3">
                        <!-- Blood Pressure Systolic -->
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="bp_systolic" class="form-label fw-semibold text-secondary small">Blood Pressure - Systolic</label>
                            <div class="input-group">
                                <input type="number" name="bp_systolic" id="bp_systolic" class="form-control" placeholder="120" min="40" max="300">
                                <span class="input-group-text small">mmHg</span>
                            </div>
                        </div>

                        <!-- Blood Pressure Diastolic -->
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="bp_diastolic" class="form-label fw-semibold text-secondary small">Blood Pressure - Diastolic</label>
                            <div class="input-group">
                                <input type="number" name="bp_diastolic" id="bp_diastolic" class="form-control" placeholder="80" min="30" max="200">
                                <span class="input-group-text small">mmHg</span>
                            </div>
                        </div>

                        <!-- Heart Rate -->
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="heart_rate" class="form-label fw-semibold text-secondary small">Heart Rate / Pulse</label>
                            <div class="input-group">
                                <input type="number" name="heart_rate" id="heart_rate" class="form-control" placeholder="72" min="20" max="250">
                                <span class="input-group-text small">bpm</span>
                            </div>
                        </div>

                        <!-- Temperature -->
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="temperature" class="form-label fw-semibold text-secondary small">Body Temperature</label>
                            <div class="input-group">
                                <input type="number" name="temperature" id="temperature" class="form-control" placeholder="36.5" step="0.1" min="30" max="45">
                                <span class="input-group-text small">°C</span>
                            </div>
                        </div>

                        <!-- Respiratory Rate -->
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="respiratory_rate" class="form-label fw-semibold text-secondary small">Respiratory Rate</label>
                            <div class="input-group">
                                <input type="number" name="respiratory_rate" id="respiratory_rate" class="form-control" placeholder="18" min="5" max="80">
                                <span class="input-group-text small">cpm</span>
                            </div>
                        </div>

                        <!-- Oxygen Saturation -->
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="oxygen_saturation" class="form-label fw-semibold text-secondary small">Oxygen Saturation (SpO2)</label>
                            <div class="input-group">
                                <input type="number" name="oxygen_saturation" id="oxygen_saturation" class="form-control" placeholder="98" min="50" max="100">
                                <span class="input-group-text small">%</span>
                            </div>
                        </div>

                        <hr class="my-3 text-muted opacity-25">

                        <!-- Weight -->
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="weight" class="form-label fw-semibold text-secondary small">Weight</label>
                            <div class="input-group">
                                <input type="number" name="weight" id="weight" class="form-control" placeholder="60" step="0.01" min="1" max="500">
                                <span class="input-group-text small">kg</span>
                            </div>
                        </div>

                        <!-- Height -->
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="height" class="form-label fw-semibold text-secondary small">Height</label>
                            <div class="input-group">
                                <input type="number" name="height" id="height" class="form-control" placeholder="165" step="0.1" min="30" max="300">
                                <span class="input-group-text small">cm</span>
                            </div>
                        </div>

                        <!-- BMI (Auto-calculated) -->
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="bmi" class="form-label fw-semibold text-secondary small">Calculated BMI</label>
                            <input type="text" name="bmi" id="bmi" class="form-control bg-light" placeholder="BMI auto-calc" readonly>
                        </div>

                        <!-- Waist Circumference -->
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="waist_circumference" class="form-label fw-semibold text-secondary small">Waist Circumference</label>
                            <div class="input-group">
                                <input type="number" name="waist_circumference" id="waist_circumference" class="form-control" placeholder="75" step="0.1" min="10" max="250">
                                <span class="input-group-text small">cm</span>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="col-12">
                            <label for="notes" class="form-label fw-semibold text-secondary small">Clinical Notes / Symptoms</label>
                            <textarea name="notes" id="notes" rows="2" class="form-control" placeholder="Patient states feeling dizzy, etc."></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-light py-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Save Vitals</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==========================================================================
   VIEW CONSULTATION DETAILS MODAL
   ========================================================================== -->
<div class="modal fade" id="viewConsultationModal" tabindex="-1" aria-labelledby="viewConsultationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-white py-3 border-bottom" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold text-dark" id="viewConsultationModalLabel">
                    <i class="bi bi-journal-medical text-primary me-2"></i>Consultation Details (SOAP Notes)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4 bg-white" id="consultationDetailsContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mt-2 small">Fetching consultation record...</p>
                </div>
            </div>
            
            <div class="modal-footer bg-light py-2.5 px-3 border-top d-flex justify-content-between align-items-center" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                <button type="button" class="btn btn-outline-dark btn-sm px-3 d-inline-flex align-items-center" onclick="window.print()">
                    <i class="bi bi-printer me-1.5"></i> Print Record
                </button>
                <div class="d-flex align-items-center gap-2" id="consultationModalFooterRight">
                    <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================================================
   VIEW VITAL SIGNS MODAL
   ========================================================================== -->
<div class="modal fade" id="viewVitalsModal" tabindex="-1" aria-labelledby="viewVitalsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="viewVitalsModalLabel">
                    <i class="bi bi-activity me-2"></i>Vital Signs Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white" id="vitalsModalContent">
                <!-- Recorded Meta -->
                <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
                    <div>
                        <span class="text-muted small d-block">Recorded At</span>
                        <strong class="text-dark" id="modalVitalDate">--</strong>
                    </div>
                    <div class="text-end">
                        <span class="text-muted small d-block">Recorded By</span>
                        <strong class="text-primary" id="modalVitalRecorder">--</strong>
                    </div>
                </div>

                <!-- Grid of Vitals Cards -->
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <span class="text-muted small d-block">Blood Pressure</span>
                            <span class="fs-6 fw-bold font-monospace text-dark" id="modalVitalBP">--</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <span class="text-muted small d-block">Heart / Pulse Rate</span>
                            <span class="fs-6 fw-bold text-dark"><span id="modalVitalPulse">--</span> <small class="text-muted fw-normal">bpm</small></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <span class="text-muted small d-block">Temperature</span>
                            <span class="fs-6 fw-bold text-dark"><span id="modalVitalTemp">--</span> <small class="text-muted fw-normal">°C</small></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <span class="text-muted small d-block">Respiratory Rate</span>
                            <span class="fs-6 fw-bold text-dark"><span id="modalVitalResp">--</span> <small class="text-muted fw-normal">cpm</small></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <span class="text-muted small d-block">Oxygen Saturation (SpO2)</span>
                            <span class="fs-6 fw-bold text-dark"><span id="modalVitalSpo2">--</span><small class="text-muted fw-normal">%</small></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <span class="text-muted small d-block">Waist Circumference</span>
                            <span class="fs-6 fw-bold text-dark"><span id="modalVitalWaist">--</span> <small class="text-muted fw-normal">cm</small></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <span class="text-muted small d-block">Weight & Height</span>
                            <span class="fs-7 fw-bold text-dark"><span id="modalVitalWeight">--</span> kg / <span id="modalVitalHeight">--</span> cm</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <span class="text-muted small d-block">BMI & Category</span>
                            <span class="fs-7 fw-bold" id="modalVitalBmi">--</span>
                        </div>
                    </div>
                </div>

                <!-- Clinical Notes / Symptoms -->
                <div class="card border rounded bg-white">
                    <div class="card-header bg-light py-1.5 px-3 small fw-bold text-secondary">
                        <i class="bi bi-journal-text me-1 text-primary"></i> Clinical Notes / Symptoms
                    </div>
                    <div class="card-body p-3 small text-dark" id="modalVitalNotes" style="white-space: pre-line; min-height: 50px;">
                        No symptoms or notes recorded.
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                <button type="button" class="btn btn-outline-secondary btn-sm px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================================================
   EDIT VITAL SIGNS MODAL
   ========================================================================== -->
<div class="modal fade" id="editVitalsModal" tabindex="-1" aria-labelledby="editVitalsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="editVitalsModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Edit Vital Signs Record
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editVitalsForm" method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
                <div class="modal-body p-4 bg-white">
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <label class="form-label small fw-semibold text-secondary">Systolic (mmHg)</label>
                            <input type="number" name="bp_systolic" id="editVitalSystolic" class="form-control" placeholder="120" min="50" max="260">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small fw-semibold text-secondary">Diastolic (mmHg)</label>
                            <input type="number" name="bp_diastolic" id="editVitalDiastolic" class="form-control" placeholder="80" min="30" max="160">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small fw-semibold text-secondary">Heart Rate (bpm)</label>
                            <input type="number" name="heart_rate" id="editVitalPulse" class="form-control" placeholder="72" min="30" max="220">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small fw-semibold text-secondary">Temp (°C)</label>
                            <input type="number" step="0.1" name="temperature" id="editVitalTemp" class="form-control" placeholder="36.5" min="30" max="45">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small fw-semibold text-secondary">Resp Rate (cpm)</label>
                            <input type="number" name="respiratory_rate" id="editVitalResp" class="form-control" placeholder="18" min="5" max="80">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small fw-semibold text-secondary">SpO2 (%)</label>
                            <input type="number" name="oxygen_saturation" id="editVitalSpo2" class="form-control" placeholder="98" min="50" max="100">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small fw-semibold text-secondary">Weight (kg)</label>
                            <input type="number" step="0.1" name="weight" id="editVitalWeight" class="form-control" placeholder="60.5" min="1" max="300">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small fw-semibold text-secondary">Height (cm)</label>
                            <input type="number" step="0.1" name="height" id="editVitalHeight" class="form-control" placeholder="165" min="30" max="250">
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Waist Circumference (cm)</label>
                            <input type="number" step="0.1" name="waist_circumference" id="editVitalWaist" class="form-control" placeholder="75" min="20" max="200">
                        </div>
                        <div class="col-12 col-md-8">
                            <label class="form-label small fw-semibold text-secondary">Clinical Notes</label>
                            <input type="text" name="notes" id="editVitalNotes" class="form-control" placeholder="Optional clinical observations...">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-medium">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==========================================================================
   EDIT PCB SERVICE ENCOUNTER MODAL
   ========================================================================== -->
<div class="modal fade" id="editPcbServiceModal" tabindex="-1" aria-labelledby="editPcbServiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="editPcbServiceModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Edit Diagnostic / PCB Service
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editPcbServiceForm" method="POST" action="">
                <?= csrf_field() ?>
                <div class="modal-body p-4 bg-white">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small">Service Category *</label>
                            <select name="service_category" id="editPcbCategory" class="form-select" required>
                                <option value="Diagnostic">Diagnostic</option>
                                <option value="PCB1">PCB1</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small">Service Date *</label>
                            <input type="date" name="service_date" id="editPcbDate" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold text-secondary small">Service Name / Type *</label>
                            <input type="text" name="service_type" id="editPcbType" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold text-secondary small">Diagnosis / Indication</label>
                            <input type="text" name="diagnosis" id="editPcbDiagnosis" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold text-secondary small d-block mb-1">Status</label>
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="status_given" id="editPcbGiven" value="1">
                                    <label class="form-check-label fw-semibold text-dark small" for="editPcbGiven">
                                        <i class="bi bi-check2-circle text-success me-1"></i> Given
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="status_referred" id="editPcbReferred" value="1" onchange="document.getElementById('editReferredToWrapper').classList.toggle('d-none', !this.checked)">
                                    <label class="form-check-label fw-semibold text-dark small" for="editPcbReferred">
                                        <i class="bi bi-arrow-up-right-circle text-warning me-1"></i> Referred
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 d-none" id="editReferredToWrapper">
                            <label class="form-label fw-semibold text-secondary small">Referred Facility / Specialist</label>
                            <input type="text" name="referred_to" id="editPcbReferredTo" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold text-secondary small">Remarks / Notes</label>
                            <textarea name="remarks" id="editPcbRemarks" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-medium">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==========================================================================
   VIEW PCB SERVICE ENCOUNTER MODAL
   ========================================================================== -->
<div class="modal fade" id="viewPcbServiceModal" tabindex="-1" aria-labelledby="viewPcbServiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="viewPcbServiceModalLabel">
                    <i class="bi bi-journal-medical me-2"></i>PCB Encounter Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white">
                <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
                    <div>
                        <span class="text-muted small d-block">Service Date</span>
                        <strong class="text-dark" id="viewPcbDate">--</strong>
                    </div>
                    <div class="text-end">
                        <span class="text-muted small d-block">Recorded By</span>
                        <strong class="text-primary" id="viewPcbRecorder">--</strong>
                    </div>
                </div>
                <div class="mb-3">
                    <span class="text-muted small d-block mb-1">Service Category & Type</span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle me-2" id="viewPcbCategory">--</span>
                    <span class="fw-bold text-dark fs-6" id="viewPcbType">--</span>
                </div>
                <div class="mb-3 p-3 bg-light rounded border">
                    <span class="text-muted small d-block mb-1">Clinical Diagnosis / Indication</span>
                    <div class="text-dark fw-medium" id="viewPcbDiagnosis">--</div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <span class="text-muted small d-block">Given Status</span>
                            <span class="fw-semibold text-dark" id="viewPcbGiven">--</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <span class="text-muted small d-block">Referred Status</span>
                            <span class="fw-semibold text-dark" id="viewPcbReferred">--</span>
                        </div>
                    </div>
                </div>
                <div class="p-3 bg-light rounded border">
                    <span class="text-muted small d-block mb-1">Remarks / Clinical Notes</span>
                    <div class="text-secondary small" id="viewPcbRemarks">--</div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                <button type="button" class="btn btn-outline-secondary btn-sm px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================================================
   VIEW IMMUNIZATION DETAILS MODAL
   ========================================================================== -->
<div class="modal fade" id="viewImmunizationModal" tabindex="-1" aria-labelledby="viewImmunizationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="viewImmunizationModalLabel">
                    <i class="bi bi-shield-check me-2"></i>Immunization Record Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white">
                <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
                    <div>
                        <span class="text-muted small d-block">Administered Date</span>
                        <strong class="text-dark" id="viewImmDate">--</strong>
                    </div>
                    <div class="text-end">
                        <span class="text-muted small d-block">Vaccinator / Clinician</span>
                        <strong class="text-primary" id="viewImmVaccinator">--</strong>
                    </div>
                </div>
                <div class="p-3 bg-light rounded border mb-3">
                    <span class="text-muted small d-block">Vaccine & Schedule</span>
                    <div class="fs-5 fw-bold text-dark d-flex align-items-center mt-1">
                        <i class="bi bi-shield-fill-check text-success me-2"></i>
                        <span id="viewImmVaccine">--</span>
                        <span class="badge bg-primary text-white ms-2 fs-7" id="viewImmDose">--</span>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <span class="text-muted small d-block">Documentation Source</span>
                            <span class="fw-semibold text-dark" id="viewImmSource">--</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <span class="text-muted small d-block">Status</span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle" id="viewImmStatus">Administered</span>
                        </div>
                    </div>
                </div>
                <div class="p-3 bg-light rounded border">
                    <span class="text-muted small d-block mb-1">Clinical Remarks / Batch Notes</span>
                    <div class="text-secondary small" id="viewImmRemarks">--</div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                <button type="button" class="btn btn-outline-secondary btn-sm px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================================================
   MODAL: EDIT IMMUNIZATION RECORD
   ========================================================================== -->
<div class="modal fade" id="editImmunizationModal" tabindex="-1" aria-labelledby="editImmunizationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="editImmunizationModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Edit Vaccine Dose Record
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="" method="POST" id="editImmunizationForm">
                <?= csrf_field() ?>
                <input type="hidden" name="redirect_to" value="<?= url('/patients/' . $patient['id'] . '#tab-immunizations') ?>">

                <div class="modal-body p-4 bg-white small">
                    <div class="mb-3">
                        <label for="edit_imm_vaccine_name" class="form-label fw-semibold text-secondary">Vaccine Name <span class="text-danger">*</span></label>
                        <select name="vaccine_name" id="edit_imm_vaccine_name" class="form-select" required>
                            <option value="">-- Select Vaccine --</option>
                            <optgroup label="Routine Infant EPI">
                                <option value="BCG">BCG</option>
                                <option value="Hepatitis B">Hepatitis B</option>
                                <option value="Pentavalent">Pentavalent (DTP-HepB-Hib)</option>
                                <option value="OPV">Oral Polio Vaccine (OPV)</option>
                                <option value="IPV">Inactivated Polio (IPV)</option>
                                <option value="Rotavirus">Rotavirus</option>
                                <option value="PCV">Pneumococcal Conjugate (PCV)</option>
                                <option value="MCV">Measles / MMR (MCV)</option>
                            </optgroup>
                            <optgroup label="Adolescent & Adult Vaccines">
                                <option value="HPV">HPV (Human Papillomavirus)</option>
                                <option value="Tetanus Toxoid">Tetanus Toxoid (TT / Td)</option>
                                <option value="Influenza">Influenza (Flu)</option>
                                <option value="Pneumococcal Polysaccharide">Pneumococcal (PPV23 / Senior)</option>
                                <option value="COVID-19">COVID-19</option>
                                <option value="Hepatitis A">Hepatitis A</option>
                            </optgroup>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="edit_imm_dose_number" class="form-label fw-semibold text-secondary">Dose Number <span class="text-danger">*</span></label>
                            <input type="number" name="dose_number" id="edit_imm_dose_number" class="form-control font-monospace" min="1" max="10" required>
                        </div>
                        <div class="col-6">
                            <label for="edit_imm_administered_date" class="form-label fw-semibold text-secondary">Administered Date <span class="text-danger">*</span></label>
                            <input type="date" name="administered_date" id="edit_imm_administered_date" class="form-control bg-white" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label for="edit_imm_source" class="form-label fw-semibold text-secondary">Source</label>
                            <select name="source" id="edit_imm_source" class="form-select">
                                <option value="Health Center">Health Center</option>
                                <option value="External">External facility</option>
                                <option value="Patient Reported">Patient/parent reported</option>
                                <option value="Unknown">Unknown</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="edit_imm_documentation_status" class="form-label fw-semibold text-secondary">Documentation status</label>
                            <select name="documentation_status" id="edit_imm_documentation_status" class="form-select">
                                <option value="Administered">Administered</option>
                                <option value="Reported">Reported</option>
                                <option value="Unknown">Unknown</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_imm_remarks" class="form-label fw-semibold text-secondary">Remarks / Lot Number</label>
                        <textarea name="remarks" id="edit_imm_remarks" class="form-control" rows="2" placeholder="Optional notes, manufacturer, or adverse reactions"></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2 px-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-xs">
                        <i class="bi bi-check2 me-1"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==========================================================================
   HIDDEN WORKSTATION ACTION FORMS (CSRF-Protected)
   ========================================================================== -->
<form id="archiveConsultationForm" method="POST" class="d-none">
    <?= csrf_field() ?>
    <input type="hidden" name="reason" id="archiveConsultationReasonInput">
</form>

<form id="deleteVitalForm" method="POST" class="d-none">
    <?= csrf_field() ?>
</form>

<form id="deleteImmunizationForm" method="POST" class="d-none">
    <?= csrf_field() ?>
</form>



<form id="cancelAppointmentForm" method="POST" class="d-none">
    <?= csrf_field() ?>
    <input type="hidden" name="status" value="Cancelled">
</form>

<?php if (is_admin()): ?>
<!-- ==========================================================================
   ARCHIVE PATIENT MODAL
   ========================================================================== -->
<div class="modal fade" id="archivePatientModal" tabindex="-1" aria-labelledby="archivePatientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-danger text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="archivePatientModalLabel">
                    <i class="bi bi-archive-fill me-2"></i>Archive Patient Record
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="<?= url('/patients/' . $patient['id'] . '/archive') ?>" method="POST" id="archiveForm">
                <?= csrf_field() ?>
                <div class="modal-body p-4 bg-white">
                    <div class="alert alert-warning border-0 small mb-3">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Warning:</strong> Archiving will hide this patient from active directories, daily queues, and scheduling lists. Only administrators can view and restore archived records.
                    </div>
                    <div class="mb-3">
                        <label for="archive_reason" class="form-label fw-semibold text-secondary small">Reason for Archiving <span class="text-danger">*</span></label>
                        <textarea name="archive_reason" id="archive_reason" class="form-control bg-light" rows="3" placeholder="e.g. Patient moved, deceased, or record duplicate..." required></textarea>
                    </div>
                </div>
                
                <div class="modal-footer bg-light py-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Archive</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ==========================================================================
   RECORD DIAGNOSTIC / PCB SERVICE MODAL (PAGE 3)
   ========================================================================== -->
<div class="modal fade" id="recordPcbServiceModal" tabindex="-1" aria-labelledby="recordPcbServiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="recordPcbServiceModalLabel">
                    <i class="bi bi-journal-plus me-2"></i>Record Diagnostic / PCB Service
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="<?= url('/patients/' . $patient['id'] . '/pcb/service-log') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body p-4 bg-white">
                    <div class="row g-3">
                        <!-- Section Category -->
                        <div class="col-12">
                            <label for="modal_service_category" class="form-label fw-semibold text-secondary small">Service Section / Category <span class="text-danger">*</span></label>
                            <select name="service_category" id="modal_service_category" class="form-select" required>
                                <option value="Diagnostic">Diagnostic Examination Services</option>
                                <option value="PCB1">Other PCB1 Services</option>
                                <option value="Other">Other Services</option>
                            </select>
                        </div>

                        <!-- Service Date -->
                        <div class="col-12 col-md-6">
                            <label for="modal_service_date" class="form-label fw-semibold text-secondary small">Encounter Date <span class="text-danger">*</span></label>
                            <input type="date" name="service_date" id="modal_service_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <!-- Diagnosis -->
                        <div class="col-12 col-md-6">
                            <label for="modal_diagnosis" class="form-label fw-semibold text-secondary small">Clinical Diagnosis / Indication</label>
                            <input type="text" name="diagnosis" id="modal_diagnosis" class="form-control" placeholder="e.g. Hypertension, Routine PCB" maxlength="255">
                        </div>

                        <!-- Service / Diagnostic Test Name -->
                        <div class="col-12">
                            <label for="modal_service_type" class="form-label fw-semibold text-secondary small">Service / Test Type <span class="text-danger">*</span></label>
                            <input type="text" name="service_type" id="modal_service_type" class="form-control" list="commonPcbServices" placeholder="e.g. Complete Blood Count (CBC)" required>
                            <datalist id="commonPcbServices">
                                <option value="Complete Blood Count (CBC)">
                                <option value="Urinalysis">
                                <option value="Fecalysis">
                                <option value="Sputum Microscopy">
                                <option value="Fasting Blood Sugar (FBS)">
                                <option value="Lipid Profile">
                                <option value="Chest X-Ray">
                                <option value="Visual Inspection with Acetic Acid (VIA)">
                                <option value="Clinical Breast Examination">
                                <option value="Oral Rehydration & Counseling">
                                <option value="Family Planning Counseling">
                                <option value="Smoking Cessation Counseling">
                            </datalist>
                        </div>

                        <!-- Given / Referred Checkboxes -->
                        <div class="col-12 border-top pt-3">
                            <div class="d-flex flex-wrap gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="status_given" id="modal_status_given" value="1" checked>
                                    <label class="form-check-label fw-semibold text-dark small" for="modal_status_given">
                                        <i class="bi bi-check-circle text-success me-1"></i> Given In-Clinic
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="status_referred" id="modal_status_referred" value="1" onchange="document.getElementById('referredToWrapper').classList.toggle('d-none', !this.checked)">
                                    <label class="form-check-label fw-semibold text-dark small" for="modal_status_referred">
                                        <i class="bi bi-arrow-up-right-circle text-warning me-1"></i> Referred
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Referred To Facility -->
                        <div class="col-12 d-none" id="referredToWrapper">
                            <label for="modal_referred_to" class="form-label fw-semibold text-secondary small">Referred Facility / Specialist</label>
                            <input type="text" name="referred_to" id="modal_referred_to" class="form-control" placeholder="e.g. Santa Rosa Community Hospital, City Health Office">
                        </div>

                        <!-- Remarks -->
                        <div class="col-12">
                            <label for="modal_remarks" class="form-label fw-semibold text-secondary small">Remarks / Notes</label>
                            <textarea name="remarks" id="modal_remarks" class="form-control" rows="2" placeholder="e.g. Normal laboratory findings, sample sent to CHO laboratory..."></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-light py-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-medium">Save Service Encounter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==========================================================================
   UPDATE OBLIGATED PREVENTIVE SERVICES MODAL (PAGE 3)
   ========================================================================== -->
<div class="modal fade" id="obligatedEditModal" tabindex="-1" aria-labelledby="obligatedEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="obligatedEditModalLabel">
                    <i class="bi bi-calendar-check me-2"></i>Update Obligated Preventive Services (<?= $pcbYear ?>)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="<?= url('/patients/' . $patient['id'] . '/pcb/obligated') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="service_year" value="<?= $pcbYear ?>">
                
                <div class="modal-body p-4 bg-white">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold text-secondary small mb-1">Hypertension Classification for BP Frequency:</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="is_hypertensive" id="modal_is_htn_no" value="0" <?= empty($pcbObligated['is_hypertensive']) ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="modal_is_htn_no">
                                        <strong>Non-Hypertensive</strong> (Frequency: Once a year)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="is_hypertensive" id="modal_is_htn_yes" value="1" <?= !empty($pcbObligated['is_hypertensive']) ? 'checked' : '' ?>>
                                    <label class="form-check-label small text-danger" for="modal_is_htn_yes">
                                        <strong>Hypertensive</strong> (Frequency: Once a month / quarterly review)
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Row 1: BP Measurements -->
                        <div class="col-12">
                            <h6 class="small fw-bold text-dark mb-1">1. BP Measurements (Dates Performed)</h6>
                            <div class="row g-2">
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-muted mb-1">1st Qtr (Jan-Mar)</label>
                                    <input type="date" name="bp_q1" class="form-control form-control-sm" value="<?= h($pcbObligated['bp_q1'] ?? '') ?>">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-muted mb-1">2nd Qtr (Apr-Jun)</label>
                                    <input type="date" name="bp_q2" class="form-control form-control-sm" value="<?= h($pcbObligated['bp_q2'] ?? '') ?>">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-muted mb-1">3rd Qtr (Jul-Sep)</label>
                                    <input type="date" name="bp_q3" class="form-control form-control-sm" value="<?= h($pcbObligated['bp_q3'] ?? '') ?>">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-muted mb-1">4th Qtr (Oct-Dec)</label>
                                    <input type="date" name="bp_q4" class="form-control form-control-sm" value="<?= h($pcbObligated['bp_q4'] ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Row 2: Periodic Clinical Breast Examination -->
                        <div class="col-12">
                            <h6 class="small fw-bold text-dark mb-1">2. Periodic Clinical Breast Examination (Dates Performed)</h6>
                            <div class="row g-2">
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-muted mb-1">1st Qtr</label>
                                    <input type="date" name="cbe_q1" class="form-control form-control-sm" value="<?= h($pcbObligated['cbe_q1'] ?? '') ?>">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-muted mb-1">2nd Qtr</label>
                                    <input type="date" name="cbe_q2" class="form-control form-control-sm" value="<?= h($pcbObligated['cbe_q2'] ?? '') ?>">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-muted mb-1">3rd Qtr</label>
                                    <input type="date" name="cbe_q3" class="form-control form-control-sm" value="<?= h($pcbObligated['cbe_q3'] ?? '') ?>">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-muted mb-1">4th Qtr</label>
                                    <input type="date" name="cbe_q4" class="form-control form-control-sm" value="<?= h($pcbObligated['cbe_q4'] ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Row 3: Visual Inspection with Acetic Acid (Females Only) -->
                        <?php if ($isFemale): ?>
                            <div class="col-12">
                                <h6 class="small fw-bold text-dark mb-1">3. Visual Inspection with Acetic Acid / VIA (Dates Performed)</h6>
                                <div class="row g-2">
                                    <div class="col-6 col-md-3">
                                        <label class="form-label small text-muted mb-1">1st Qtr</label>
                                        <input type="date" name="via_q1" class="form-control form-control-sm" value="<?= h($pcbObligated['via_q1'] ?? '') ?>">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label small text-muted mb-1">2nd Qtr</label>
                                        <input type="date" name="via_q2" class="form-control form-control-sm" value="<?= h($pcbObligated['via_q2'] ?? '') ?>">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label small text-muted mb-1">3rd Qtr</label>
                                        <input type="date" name="via_q3" class="form-control form-control-sm" value="<?= h($pcbObligated['via_q3'] ?? '') ?>">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label small text-muted mb-1">4th Qtr</label>
                                        <input type="date" name="via_q4" class="form-control form-control-sm" value="<?= h($pcbObligated['via_q4'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="col-12">
                            <label class="form-label small text-muted mb-1">Clinical Remarks / Compliance Notes</label>
                            <input type="text" name="remarks" class="form-control form-control-sm" placeholder="e.g. Regular compliance, hypertensive medications prescribed..." value="<?= h($pcbObligated['remarks'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light py-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-medium">Save Obligated Dates</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Client-side Scripts -->
<script>
window.filterPcbRows = function(cat, btn) {
    const rows = document.querySelectorAll('.pcb-log-row');
    const filterBtns = document.querySelectorAll('#pcbCategoryFilters button');
    filterBtns.forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');

    let visibleCount = 0;
    rows.forEach(r => {
        if (cat === 'all' || r.dataset.category === cat) {
            r.style.display = '';
            visibleCount++;
        } else {
            r.style.display = 'none';
        }
    });
    const countEl = document.getElementById('pcbRowCount');
    if (countEl) countEl.innerText = visibleCount;
};

// Global IHP Edit / View Mode Transition Functions
let ihpFormInitialData = '';

window.enterIhpEditMode = function() {
    const viewMode = document.getElementById('ihp-view-mode');
    const editMode = document.getElementById('ihp-edit-mode');
    const form = document.getElementById('ihpForm');
    if (viewMode && editMode) {
        viewMode.classList.add('d-none');
        editMode.classList.remove('d-none');
        if (form) {
            ihpFormInitialData = new URLSearchParams(new FormData(form)).toString();
        }
        const tabEl = document.getElementById('tab-ihp');
        if (tabEl) {
            tabEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }
};

window.cancelIhpEditMode = function() {
    const form = document.getElementById('ihpForm');
    const viewMode = document.getElementById('ihp-view-mode');
    const editMode = document.getElementById('ihp-edit-mode');
    if (!form || !viewMode || !editMode) return;

    const currentData = new URLSearchParams(new FormData(form)).toString();
    const isDirty = (currentData !== ihpFormInitialData);

    if (isDirty) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Discard Changes?',
                text: 'You have modified fields in this IHP record. Are you sure you want to discard your changes?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Discard Changes',
                cancelButtonText: 'Keep Editing'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.reset();
                    const lmpInput = form.querySelector('input[name="lmp"]');
                    editMode.classList.add('d-none');
                    viewMode.classList.remove('d-none');
                }
            });
        } else {
            if (confirm('Discard unsaved changes to this IHP record?')) {
                form.reset();
                editMode.classList.add('d-none');
                viewMode.classList.remove('d-none');
            }
        }
    } else {
        editMode.classList.add('d-none');
        viewMode.classList.remove('d-none');
    }
};

window.editIhpFromOverview = function() {
    const ihpBtn = document.getElementById('tab-ihp-btn');
    if (ihpBtn) {
        ihpBtn.click();
        setTimeout(function() {
            enterIhpEditMode();
        }, 150);
    }
};

document.addEventListener('DOMContentLoaded', function() {
    // 1. BMI Auto-calculation in Vitals Modal
    const weightInput = document.getElementById('weight');
    const heightInput = document.getElementById('height');
    const bmiInput = document.getElementById('bmi');

    function calculateBMI() {
        const weight = parseFloat(weightInput.value);
        const height = parseFloat(heightInput.value);
        if (weight > 0 && height > 0) {
            const heightInMeters = height / 100;
            const bmi = weight / (heightInMeters * heightInMeters);
            bmiInput.value = bmi.toFixed(2);
        } else {
            bmiInput.value = '';
        }
    }

    if (weightInput && heightInput) {
        weightInput.addEventListener('input', calculateBMI);
        heightInput.addEventListener('input', calculateBMI);
    }

    // 2. Tab URL Synchronization (Real-time dedicated URLs, History navigation & Persistence)
    function activateTabFromHash(hash) {
        if (!hash) return false;
        const cleanHash = hash.replace(/^#/, '').trim().toLowerCase();
        if (!cleanHash) return false;

        if (cleanHash === 'edit-ihp') {
            if (typeof editIhpFromOverview === 'function') {
                editIhpFromOverview();
                return true;
            }
        }

        const aliasMap = {
            'overview': 'tab-overview',
            'ihp': 'tab-ihp',
            'ihp-history': 'tab-ihp',
            'pcb': 'tab-pcb',
            'phic': 'tab-pcb',
            'phic-pcb': 'tab-pcb',
            'consultations': 'tab-consultations',
            'soap': 'tab-consultations',
            'vitals': 'tab-vitals',
            'vitals-log': 'tab-vitals',
            'immunizations': 'tab-immunizations',
            // 'prenatal': routed to dedicated /maternal workstation,
            // 'wellbaby': routed to dedicated /well-baby workstation,
            'appointments': 'tab-appointments',
            'queue': 'tab-appointments'
        };

        let targetTabId = aliasMap[cleanHash] || (cleanHash.startsWith('tab-') ? cleanHash : 'tab-' + cleanHash);
        if (targetTabId.endsWith('-tab')) {
            targetTabId = 'tab-' + targetTabId.replace(/-tab$/, '').replace(/^tab-/, '');
        }

        const tabBtn = document.querySelector(`button[data-bs-target="#${targetTabId}"]`) 
            || document.getElementById(targetTabId + '-btn')
            || document.querySelector(`button[data-bs-target="#${cleanHash}"]`);

        if (tabBtn) {
            const bsTab = bootstrap.Tab.getOrCreateInstance(tabBtn);
            bsTab.show();
            setTimeout(() => {
                tabBtn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            }, 60);
            return true;
        }
        return false;
    }

    // Tab Scroll Buttons and Auto-Scroll Behavior
    const workstationNav = document.getElementById('workstationTabs');
    const tabScrollPrev = document.getElementById('tabScrollPrev');
    const tabScrollNext = document.getElementById('tabScrollNext');

    function updateTabScrollButtons() {
        if (!workstationNav) return;
        const scrollLeft = workstationNav.scrollLeft;
        const maxScroll = workstationNav.scrollWidth - workstationNav.clientWidth;

        if (tabScrollPrev) {
            if (scrollLeft > 15) {
                tabScrollPrev.classList.add('visible');
            } else {
                tabScrollPrev.classList.remove('visible');
            }
        }

        if (tabScrollNext) {
            if (maxScroll - scrollLeft > 15) {
                tabScrollNext.classList.add('visible');
            } else {
                tabScrollNext.classList.remove('visible');
            }
        }
    }

    if (workstationNav) {
        workstationNav.addEventListener('scroll', updateTabScrollButtons, { passive: true });
        window.addEventListener('resize', updateTabScrollButtons, { passive: true });

        if (tabScrollPrev) {
            tabScrollPrev.addEventListener('click', function() {
                workstationNav.scrollBy({ left: -260, behavior: 'smooth' });
            });
        }

        if (tabScrollNext) {
            tabScrollNext.addEventListener('click', function() {
                workstationNav.scrollBy({ left: 260, behavior: 'smooth' });
            });
        }

        // Sync address bar URL and scroll tab into view when clicked
        workstationNav.addEventListener('shown.bs.tab', function(e) {
            setTimeout(() => {
                e.target.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            }, 60);
            const target = e.target.getAttribute('data-bs-target');
            if (target && window.location.hash !== target) {
                if (window.history && window.history.pushState) {
                    window.history.pushState(null, '', target);
                } else {
                    window.location.hash = target;
                }
            }
            updateTabScrollButtons();
        });

        setTimeout(updateTabScrollButtons, 120);
    }

    // Activate tab on page load if hash present in URL
    if (window.location.hash) {
        activateTabFromHash(window.location.hash);
    }

    // Support browser Back and Forward history buttons
    window.addEventListener('popstate', function() {
        if (window.location.hash) {
            activateTabFromHash(window.location.hash);
        } else {
            activateTabFromHash('#tab-overview');
        }
        setTimeout(updateTabScrollButtons, 80);
    });

    // 3. Consultation SOAP Modal AJAX Loader
    const viewConsultationModal = document.getElementById('viewConsultationModal');
    const consultationDetailsContent = document.getElementById('consultationDetailsContent');

    if (viewConsultationModal) {
        viewConsultationModal.addEventListener('hidden.bs.modal', function () {
            if (!document.querySelector('.modal.show')) {
                document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');
            }
        });
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    document.querySelectorAll('.view-consultation-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const consultationId = this.getAttribute('data-consultation-id');
            const modal = bootstrap.Modal.getOrCreateInstance(viewConsultationModal);
            modal.show();

            const footerRight = document.getElementById('consultationModalFooterRight');
            if (footerRight) {
                footerRight.innerHTML = `<button type="button" class="btn btn-outline-secondary btn-sm px-4" data-bs-dismiss="modal">Close</button>`;
            }

            consultationDetailsContent.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mt-2 small">Fetching consultation record...</p>
                </div>
            `;

            fetch(`<?= url('/consultations/') ?>${consultationId}`)
                .then(res => {
                    if (!res.ok) throw new Error('Network response was not ok');
                    return res.json();
                })
                .then(data => {
                    if (data.error) {
                        consultationDetailsContent.innerHTML = `<div class="alert alert-danger mb-0">${escapeHtml(data.error)}</div>`;
                        return;
                    }

                    // Vitals strip formatting
                    let vitalsHtml = '';
                    if (data.bp_systolic || data.temperature || data.heart_rate || data.weight || data.height) {
                        const isHighBp = (parseInt(data.bp_systolic) >= 140 || parseInt(data.bp_diastolic) >= 90);
                        vitalsHtml = `
                            <div class="p-3 bg-light rounded-3 mb-3 border">
                                <div class="d-flex flex-wrap align-items-center gap-2 small">
                                    <span class="fw-bold text-dark me-1"><i class="bi bi-speedometer2 text-primary me-1"></i> Linked Vital Signs:</span>
                                    ${data.bp_systolic && data.bp_diastolic ? `
                                        <span class="badge ${isHighBp ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-white text-dark border'} py-2 px-2 fw-normal">
                                            BP: <strong class="${isHighBp ? 'text-danger' : 'text-dark'}">${escapeHtml(data.bp_systolic)}/${escapeHtml(data.bp_diastolic)} mmHg</strong>
                                        </span>` : ''}
                                    ${data.temperature ? `
                                        <span class="badge bg-white text-dark border py-2 px-2 fw-normal">
                                            Temp: <strong class="text-dark">${escapeHtml(data.temperature)} °C</strong>
                                        </span>` : ''}
                                    ${data.heart_rate ? `
                                        <span class="badge bg-white text-dark border py-2 px-2 fw-normal">
                                            HR: <strong class="text-dark">${escapeHtml(data.heart_rate)} bpm</strong>
                                        </span>` : ''}
                                    ${data.respiratory_rate ? `
                                        <span class="badge bg-white text-dark border py-2 px-2 fw-normal">
                                            RR: <strong class="text-dark">${escapeHtml(data.respiratory_rate)} cpm</strong>
                                        </span>` : ''}
                                    ${data.oxygen_saturation ? `
                                        <span class="badge bg-white text-dark border py-2 px-2 fw-normal">
                                            SpO2: <strong class="text-dark">${escapeHtml(data.oxygen_saturation)}%</strong>
                                        </span>` : ''}
                                    ${data.weight ? `
                                        <span class="badge bg-white text-dark border py-2 px-2 fw-normal">
                                            Weight: <strong class="text-dark">${escapeHtml(data.weight)} kg</strong>
                                        </span>` : ''}
                                    ${data.height ? `
                                        <span class="badge bg-white text-dark border py-2 px-2 fw-normal">
                                            Height: <strong class="text-dark">${escapeHtml(data.height)} cm</strong>
                                        </span>` : ''}
                                    ${data.bmi ? `
                                        <span class="badge bg-white text-dark border py-2 px-2 fw-normal">
                                            BMI: <strong class="text-dark">${escapeHtml(data.bmi)}</strong>
                                        </span>` : ''}
                                </div>
                            </div>
                        `;
                    }

                    let statusBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Completed</span>';
                    if (data.status === 'Open') {
                        statusBadge = '<span class="badge bg-secondary-subtle text-secondary border px-2 py-1">Open</span>';
                    } else if (data.status === 'Cancelled') {
                        statusBadge = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Cancelled</span>';
                    }

                    let lastEditedHtml = '';
                    if (data.formatted_updated && data.updater_name) {
                        lastEditedHtml = `
                            <div class="col-12 text-muted small mt-1">
                                <i class="bi bi-pencil me-1"></i>Last edited on ${escapeHtml(data.formatted_updated)} by ${escapeHtml(data.updater_name)}
                            </div>
                        `;
                    }

                    let cancellationHtml = '';
                    if (data.status === 'Cancelled' && data.archive_reason) {
                        cancellationHtml = `
                            <div class="col-12 alert alert-danger py-2 px-3 small mt-2 mb-0">
                                <i class="bi bi-x-octagon-fill me-1"></i><strong>Cancellation Reason:</strong> ${escapeHtml(data.archive_reason)}
                            </div>
                        `;
                    }

                    consultationDetailsContent.innerHTML = `
                        <!-- Metadata Header Strip -->
                        <div class="row g-2 pb-3 mb-3 border-bottom align-items-center">
                            <div class="col-12 col-md-5">
                                <span class="text-muted small d-block">Consultation Date & Time:</span>
                                <strong class="text-dark"><i class="bi bi-calendar-event me-1 text-primary"></i>${escapeHtml(data.formatted_date || data.consulted_at)}</strong>
                            </div>
                            <div class="col-12 col-md-4">
                                <span class="text-muted small d-block">Attending Clinician:</span>
                                <strong class="text-dark"><i class="bi bi-person-badge me-1 text-primary"></i>${escapeHtml(data.clinician_name || 'Unassigned Clinician')}</strong>
                            </div>
                            <div class="col-12 col-md-3 text-md-end">
                                <span class="text-muted small d-block">Status:</span>
                                ${statusBadge}
                            </div>
                            ${lastEditedHtml}
                            ${cancellationHtml}
                        </div>

                        ${vitalsHtml}

                        <!-- SOAP Note Clean Neutral Cards -->
                        <div class="d-flex flex-column gap-3">
                            <!-- Subjective (S) -->
                            <div class="card border rounded-3 bg-white">
                                <div class="card-header bg-light py-2 px-3 border-bottom d-flex align-items-center gap-2">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2 py-1">S</span>
                                    <span class="fw-bold text-dark small text-uppercase">Subjective &mdash; Chief Complaint & History of Illness</span>
                                </div>
                                <div class="card-body p-3 text-dark small" style="white-space: pre-line; line-height: 1.6;">${escapeHtml(data.subjective || 'No subjective complaint recorded.')}</div>
                            </div>

                            <!-- Objective (O) -->
                            <div class="card border rounded-3 bg-white">
                                <div class="card-header bg-light py-2 px-3 border-bottom d-flex align-items-center gap-2">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2 py-1">O</span>
                                    <span class="fw-bold text-dark small text-uppercase">Objective &mdash; Physical Examination & Clinical Findings</span>
                                </div>
                                <div class="card-body p-3 text-dark small" style="white-space: pre-line; line-height: 1.6;">${escapeHtml(data.objective || 'No physical examination findings recorded.')}</div>
                            </div>

                            <!-- Assessment (A) -->
                            <div class="card border rounded-3 bg-white">
                                <div class="card-header bg-light py-2 px-3 border-bottom d-flex align-items-center gap-2">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2 py-1">A</span>
                                    <span class="fw-bold text-dark small text-uppercase">Assessment &mdash; Clinical Impression / Diagnosis</span>
                                </div>
                                <div class="card-body p-3 text-dark small fw-medium" style="white-space: pre-line; line-height: 1.6;">${escapeHtml(data.assessment || 'No clinical diagnosis recorded.')}</div>
                            </div>

                            <!-- Plan (P) -->
                            <div class="card border rounded-3 bg-white">
                                <div class="card-header bg-light py-2 px-3 border-bottom d-flex align-items-center gap-2">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2 py-1">P</span>
                                    <span class="fw-bold text-dark small text-uppercase">Plan &mdash; Treatment, Prescriptions & Recommendations</span>
                                </div>
                                <div class="card-body p-3 text-dark small" style="white-space: pre-line; line-height: 1.6;">${escapeHtml(data.plan || 'No treatment plan or prescriptions recorded.')}</div>
                            </div>
                        </div>
                    `;

                    // Update modal footer actions (Edit button if authorized)
                    const footerRight = document.getElementById('consultationModalFooterRight');
                    if (footerRight) {
                        let footerBtns = '';
                        if (data.can_edit) {
                            footerBtns += `<a href="<?= url('/consultations/') ?>${data.id}/edit" class="btn btn-primary btn-sm px-3 d-inline-flex align-items-center"><i class="bi bi-pencil-square me-1.5"></i> Edit Consultation</a>`;
                        }
                        footerBtns += `<button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Close</button>`;
                        footerRight.innerHTML = footerBtns;
                    }
                })
                .catch(err => {
                    consultationDetailsContent.innerHTML = `
                        <div class="alert alert-danger mb-0">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>Failed to load consultation details. Please try again.
                        </div>
                    `;
                });
        });
    });

       // 6. Modernized Workstation Action Handlers

    // A. Archive Consultation
    document.querySelectorAll('.btn-archive-consultation').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            const patientId = this.getAttribute('data-patient-id');
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Archive Consultation?',
                    text: 'Archiving hides this consultation note from the active history. Please provide a reason:',
                    input: 'textarea',
                    inputPlaceholder: 'e.g. Inadvertent duplication, erroneous entry...',
                    inputValidator: (value) => {
                        if (!value || !value.trim()) {
                            return 'Archive reason is required!';
                        }
                    },
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Archive Consultation',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.getElementById('archiveConsultationForm');
                        form.action = `<?= url('/consultations/') ?>${id}/archive`;
                        document.getElementById('archiveConsultationReasonInput').value = result.value.trim();
                        form.submit();
                    }
                });
            } else {
                const reason = prompt('Please enter the reason for archiving this consultation:');
                if (reason && reason.trim()) {
                    const form = document.getElementById('archiveConsultationForm');
                    form.action = `<?= url('/consultations/') ?>${id}/archive`;
                    document.getElementById('archiveConsultationReasonInput').value = reason.trim();
                    form.submit();
                }
            }
        });
    });

    // B. View Vital Signs Details Modal
    const viewVitalsModalEl = document.getElementById('viewVitalsModal');
    const viewVitalsModal = viewVitalsModalEl ? bootstrap.Modal.getOrCreateInstance(viewVitalsModalEl) : null;

    if (viewVitalsModalEl) {
        viewVitalsModalEl.addEventListener('hidden.bs.modal', function () {
            if (!document.querySelector('.modal.show')) {
                document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');
            }
        });
    }

    document.querySelectorAll('.btn-view-vitals').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const date = this.getAttribute('data-date') || '--';
            const recorder = this.getAttribute('data-recorder') || 'Clinician';
            const bp = this.getAttribute('data-bp') || '--';
            const pulse = this.getAttribute('data-pulse') || '--';
            const temp = this.getAttribute('data-temp') || '--';
            const resp = this.getAttribute('data-resp') || '--';
            const spo2 = this.getAttribute('data-spo2') || '--';
            const weight = this.getAttribute('data-weight') || '--';
            const height = this.getAttribute('data-height') || '--';
            const bmi = this.getAttribute('data-bmi') || '--';
            const waist = this.getAttribute('data-waist') || '--';
            const notes = this.getAttribute('data-notes') || '';

            document.getElementById('modalVitalDate').textContent = date;
            document.getElementById('modalVitalRecorder').textContent = recorder;
            document.getElementById('modalVitalBP').textContent = bp;
            document.getElementById('modalVitalPulse').textContent = pulse;
            document.getElementById('modalVitalTemp').textContent = temp;
            document.getElementById('modalVitalResp').textContent = resp;
            document.getElementById('modalVitalSpo2').textContent = spo2;
            document.getElementById('modalVitalWeight').textContent = weight;
            document.getElementById('modalVitalHeight').textContent = height;
            document.getElementById('modalVitalWaist').textContent = waist;

            // BMI category determination
            let bmiDisplay = bmi;
            if (bmi !== '--' && !isNaN(parseFloat(bmi))) {
                const bmiVal = parseFloat(bmi);
                let cat = '';
                let badgeClass = 'bg-secondary-subtle text-secondary';
                if (bmiVal < 18.5) {
                    cat = 'Underweight';
                    badgeClass = 'bg-warning-subtle text-dark';
                } else if (bmiVal < 25) {
                    cat = 'Normal';
                    badgeClass = 'bg-success-subtle text-success';
                } else if (bmiVal < 30) {
                    cat = 'Overweight';
                    badgeClass = 'bg-warning-subtle text-dark';
                } else {
                    cat = 'Obese';
                    badgeClass = 'bg-danger-subtle text-danger';
                }
                bmiDisplay = `${bmi} <span class="badge ${badgeClass} ms-1">${cat}</span>`;
            }
            document.getElementById('modalVitalBmi').innerHTML = bmiDisplay;

            const notesEl = document.getElementById('modalVitalNotes');
            if (notes && notes.trim()) {
                notesEl.textContent = notes.trim();
            } else {
                notesEl.innerHTML = '<span class="text-muted fst-italic">No symptoms or notes recorded.</span>';
            }

            if (viewVitalsModal) {
                viewVitalsModal.show();
            }
        });
    });

    // C. Delete Vital Signs Record
    document.querySelectorAll('.btn-delete-vital').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Delete Vital Signs?',
                    text: 'Are you sure you want to delete this vital signs record? This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.getElementById('deleteVitalForm');
                        form.action = `<?= url('/vital-signs/') ?>${id}/delete`;
                        form.submit();
                    }
                });
            } else if (confirm('Delete this vital signs record?')) {
                const form = document.getElementById('deleteVitalForm');
                form.action = `<?= url('/vital-signs/') ?>${id}/delete`;
                form.submit();
            }
        });
    });

    // D. Delete Universal Immunization Record
    document.querySelectorAll('.btn-delete-immunization').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            const vaccine = this.getAttribute('data-vaccine') || 'Vaccine';
            const dose = this.getAttribute('data-dose') || '1';

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Delete Immunization Record?',
                    text: `Are you sure you want to delete Dose #${dose} of ${vaccine}?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete record',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.getElementById('deleteImmunizationForm');
                        form.action = `<?= url('/immunizations/') ?>${id}/delete`;
                        form.submit();
                    }
                });
            } else if (confirm(`Are you sure you want to delete Dose #${dose} of ${vaccine}?`)) {
                const form = document.getElementById('deleteImmunizationForm');
                form.action = `<?= url('/immunizations/') ?>${id}/delete`;
                form.submit();
            }
        });
    });

    // F. Cancel Appointment
    document.querySelectorAll('.btn-cancel-appointment').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            const date = this.getAttribute('data-date') || '';

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Cancel Appointment?',
                    text: `Are you sure you want to cancel the appointment scheduled for ${date}?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, cancel appointment',
                    cancelButtonText: 'Keep'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.getElementById('cancelAppointmentForm');
                        form.action = `<?= url('/appointments/') ?>${id}/status`;
                        form.submit();
                    }
                });
            } else if (confirm(`Cancel appointment scheduled for ${date}?`)) {
                const form = document.getElementById('cancelAppointmentForm');
                form.action = `<?= url('/appointments/') ?>${id}/status`;
                form.submit();
            }
        });
    });

    // G. Edit Vital Signs Modal Handler
    const editVitalsModalEl = document.getElementById('editVitalsModal');
    const editVitalsModal = editVitalsModalEl ? bootstrap.Modal.getOrCreateInstance(editVitalsModalEl) : null;
    document.querySelectorAll('.btn-edit-vitals').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            const form = document.getElementById('editVitalsForm');
            if (form) form.action = `<?= url('/vital-signs/') ?>${id}/update`;

            document.getElementById('editVitalSystolic').value = this.getAttribute('data-bp-systolic') || '';
            document.getElementById('editVitalDiastolic').value = this.getAttribute('data-bp-diastolic') || '';
            document.getElementById('editVitalPulse').value = this.getAttribute('data-pulse') || '';
            document.getElementById('editVitalTemp').value = this.getAttribute('data-temp') || '';
            document.getElementById('editVitalResp').value = this.getAttribute('data-resp') || '';
            document.getElementById('editVitalSpo2').value = this.getAttribute('data-spo2') || '';
            document.getElementById('editVitalWeight').value = this.getAttribute('data-weight') || '';
            document.getElementById('editVitalHeight').value = this.getAttribute('data-height') || '';
            document.getElementById('editVitalWaist').value = this.getAttribute('data-waist') || '';
            document.getElementById('editVitalNotes').value = this.getAttribute('data-notes') || '';

            if (editVitalsModal) editVitalsModal.show();
        });
    });

    // H. View PCB Service Modal Handler
    const viewPcbModalEl = document.getElementById('viewPcbServiceModal');
    const viewPcbModal = viewPcbModalEl ? bootstrap.Modal.getOrCreateInstance(viewPcbModalEl) : null;
    document.querySelectorAll('.btn-view-pcb-service').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('viewPcbDate').textContent = this.getAttribute('data-date') || '--';
            document.getElementById('viewPcbRecorder').textContent = this.getAttribute('data-recorder') || '--';
            document.getElementById('viewPcbCategory').textContent = this.getAttribute('data-category') || '--';
            document.getElementById('viewPcbType').textContent = this.getAttribute('data-type') || '--';
            document.getElementById('viewPcbDiagnosis').textContent = this.getAttribute('data-diagnosis') || '--';
            document.getElementById('viewPcbGiven').textContent = this.getAttribute('data-given') || '--';
            document.getElementById('viewPcbReferred').textContent = this.getAttribute('data-referred') || '--';
            document.getElementById('viewPcbRemarks').textContent = this.getAttribute('data-remarks') || '--';

            if (viewPcbModal) viewPcbModal.show();
        });
    });

    // I. Edit PCB Service Modal Handler
    const editPcbModalEl = document.getElementById('editPcbServiceModal');
    const editPcbModal = editPcbModalEl ? bootstrap.Modal.getOrCreateInstance(editPcbModalEl) : null;
    document.querySelectorAll('.btn-edit-pcb-service').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            const form = document.getElementById('editPcbServiceForm');
            if (form) form.action = `<?= url('/pcb/service-log/') ?>${id}/update`;

            document.getElementById('editPcbCategory').value = this.getAttribute('data-category') || 'Diagnostic';
            document.getElementById('editPcbDate').value = this.getAttribute('data-date') || '';
            document.getElementById('editPcbType').value = this.getAttribute('data-type') || '';
            document.getElementById('editPcbDiagnosis').value = this.getAttribute('data-diagnosis') || '';
            
            const isGiven = this.getAttribute('data-given') === '1';
            document.getElementById('editPcbGiven').checked = isGiven;

            const isReferred = this.getAttribute('data-referred') === '1';
            document.getElementById('editPcbReferred').checked = isReferred;
            const refWrapper = document.getElementById('editReferredToWrapper');
            if (refWrapper) refWrapper.classList.toggle('d-none', !isReferred);
            document.getElementById('editPcbReferredTo').value = this.getAttribute('data-referred-to') || '';
            document.getElementById('editPcbRemarks').value = this.getAttribute('data-remarks') || '';

            if (editPcbModal) editPcbModal.show();
        });
    });

    // J. View Immunization Modal Handler
    const viewImmModalEl = document.getElementById('viewImmunizationModal');
    const viewImmModal = viewImmModalEl ? bootstrap.Modal.getOrCreateInstance(viewImmModalEl) : null;
    document.querySelectorAll('.btn-view-immunization').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('viewImmDate').textContent = this.getAttribute('data-date') || '--';
            document.getElementById('viewImmVaccinator').textContent = this.getAttribute('data-vaccinator') || '--';
            document.getElementById('viewImmVaccine').textContent = this.getAttribute('data-vaccine') || '--';
            document.getElementById('viewImmDose').textContent = this.getAttribute('data-dose') || '--';
            document.getElementById('viewImmSource').textContent = this.getAttribute('data-source') || '--';
            document.getElementById('viewImmStatus').textContent = this.getAttribute('data-status') || '--';
            document.getElementById('viewImmRemarks').textContent = this.getAttribute('data-remarks') || '--';

            if (viewImmModal) viewImmModal.show();
        });
    });

    // K. Edit Immunization Modal Handler
    const editImmModalEl = document.getElementById('editImmunizationModal');
    const editImmModal = editImmModalEl ? bootstrap.Modal.getOrCreateInstance(editImmModalEl) : null;
    document.querySelectorAll('.btn-edit-immunization').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            const form = document.getElementById('editImmunizationForm');
            if (form) form.action = `<?= url('/immunizations/') ?>${id}/update`;

            const vaccineSelect = document.getElementById('edit_imm_vaccine_name');
            if (vaccineSelect) vaccineSelect.value = this.getAttribute('data-vaccine') || '';

            document.getElementById('edit_imm_dose_number').value = this.getAttribute('data-dose') || '1';
            document.getElementById('edit_imm_administered_date').value = this.getAttribute('data-date') || '';
            document.getElementById('edit_imm_source').value = this.getAttribute('data-source') || 'Health Center';
            document.getElementById('edit_imm_documentation_status').value = this.getAttribute('data-status') || 'Administered';
            document.getElementById('edit_imm_remarks').value = this.getAttribute('data-remarks') || '';

            if (editImmModal) editImmModal.show();
        });
    });

    });
</script>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

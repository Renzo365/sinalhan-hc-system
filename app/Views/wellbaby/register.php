<?php
/**
 * Dedicated Well-Baby Infant Registration View
 * 
 * @var array|null $preselectedPatient Pre-selected child patient record (optional)
 * @var bool $alreadyRegistered Whether preselected child already has a well-baby record
 * @var array $potentialMothers List of potential mothers
 */

$title = 'Register Infant for Well-Baby Care';
$breadcrumbs = [
    'Well-Baby / EPI' => '/well-baby',
    'Register Infant' => null
];
require dirname(__DIR__) . '/layout/header.php';

$initialPatientId = $preselectedPatient['id'] ?? '';
$initialMotherName = $preselectedPatient['mother_name'] ?? '';
$initialMotherDob = $preselectedPatient['mother_dob'] ?? '';
$initialFatherName = $preselectedPatient['father_name'] ?? '';
$initialFatherDob = $preselectedPatient['father_dob'] ?? '';
$isLockedPatient = !empty($_GET['patient_id']) && !empty($preselectedPatient);
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="h3 mb-1 fw-bold text-dark">
                <i class="bi bi-emoji-smile-fill text-success me-2"></i>Register Infant for Well-Baby Care
            </h2>
            <p class="text-secondary small mb-0">Enroll an eligible infant or child (aged 0–5 years) into the Well-Baby &amp; EPI Immunization Program.</p>
        </div>
        <div>
            <a href="<?= ($alreadyRegistered && !empty($initialPatientId)) ? url('/well-baby/' . $initialPatientId) : url('/well-baby') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to <?= ($alreadyRegistered && !empty($initialPatientId)) ? 'Workstation' : 'Well-Baby Registry' ?>
            </a>
        </div>
    </div>

    <!-- Flash Alert Messages -->
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-xs mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $_SESSION['error_message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">

            <!-- Already Registered Warning -->
            <div id="alreadyRegisteredWarning" class="alert alert-warning border-0 shadow-sm rounded-4 mb-4 <?= $alreadyRegistered ? '' : 'd-none' ?>">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-circle-fill text-warning fs-3 me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-1 text-dark">Well-Baby Record Already Exists</h6>
                            <p class="mb-0 small text-secondary">
                                This child already has an active Well-Baby health profile and birth circumstances on file.
                            </p>
                        </div>
                    </div>
                    <a href="<?= !empty($initialPatientId) ? url('/well-baby/' . $initialPatientId) : '#' ?>" id="btnViewExistingWorkstation" class="btn btn-warning btn-sm fw-semibold">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Open Workstation
                    </a>
                </div>
            </div>

            <!-- Main Registration Form -->
            <form action="<?= url('/well-baby/register') ?>" method="POST" id="wellbabyRegistrationForm" autocomplete="off">
                <?= csrf_field() ?>
                <input type="hidden" name="patient_id" id="selectedPatientId" value="<?= h($initialPatientId) ?>">

                <!-- Step 1: Child Selection & Identity Card -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                    <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-success rounded-circle p-2 me-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">1</span>
                            <h5 class="card-title h6 fw-bold mb-0 text-dark">Child / Infant Selection</h5>
                        </div>
                        <?php if ($isLockedPatient): ?>
                            <span class="badge bg-light text-secondary border px-3 py-2">
                                <i class="bi bi-lock-fill me-1 text-muted"></i> Child Locked
                            </span>
                        <?php else: ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary <?= empty($preselectedPatient) ? 'd-none' : '' ?>" id="btnChangePatient">
                                <i class="bi bi-arrow-repeat me-1"></i> Change Child
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-4 bg-white">
                        <!-- Infant Search Box -->
                        <div id="patientSearchSection" class="<?= !empty($preselectedPatient) ? 'd-none' : '' ?>">
                            <label for="patientSearchInput" class="form-label fw-semibold text-secondary small">
                                Search Unregistered Infant / Child (Aged 0–5) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-lg mb-2 shadow-xs">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                                <input type="text" 
                                       id="patientSearchInput" 
                                       class="form-control bg-light border-start-0 fs-6" 
                                       placeholder="Type child's name, patient ID (e.g. P-2026-), or envelope #..." 
                                       autocomplete="off">
                                <button type="button" class="btn btn-light border text-muted px-3 d-none" id="btnClearSearch">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                            <div class="form-text small text-muted mb-3">
                                Only children aged 0–5 registered in the patient directory who do not yet have a Well-Baby record will appear in search results.
                            </div>

                            <!-- Live Search Results Dropdown List -->
                            <div id="searchResultsContainer" class="list-group shadow-sm rounded-3 border mb-3 d-none" style="max-height: 280px; overflow-y: auto;">
                                <!-- Injected dynamically via JS -->
                            </div>
                            <div id="searchLoadingSpinner" class="text-center py-3 text-muted d-none">
                                <div class="spinner-border spinner-border-sm text-success me-2" role="status"></div>
                                <span class="small">Searching eligible children...</span>
                            </div>
                        </div>

                        <!-- Compact Child Identity Card -->
                        <div id="patientIdentityCard" class="<?= empty($preselectedPatient) ? 'd-none' : '' ?>">
                            <div class="p-3 p-md-4 rounded-4 bg-success bg-opacity-10 border border-success border-opacity-25">
                                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-3 pb-3 border-bottom border-success border-opacity-25">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center fw-bold fs-4 shadow-xs" style="width: 54px; height: 54px;">
                                            <span id="cardInitials">
                                                <?php 
                                                    $init = '';
                                                    if (!empty($preselectedPatient['first_name'])) $init .= mb_substr($preselectedPatient['first_name'], 0, 1);
                                                    if (!empty($preselectedPatient['last_name'])) $init .= mb_substr($preselectedPatient['last_name'], 0, 1);
                                                    echo strtoupper($init ?: 'WB');
                                                ?>
                                            </span>
                                        </div>
                                        <div>
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                <h5 class="h6 fw-bold text-dark mb-0" id="cardFullName">
                                                    <?= !empty($preselectedPatient) ? h($preselectedPatient['last_name'] . ', ' . $preselectedPatient['first_name'] . ' ' . (!empty($preselectedPatient['middle_name']) ? mb_substr($preselectedPatient['middle_name'], 0, 1) . '.' : '') . ' ' . ($preselectedPatient['suffix'] ?? '')) : '--' ?>
                                                </h5>
                                                <span class="badge <?= (!empty($preselectedPatient['sex']) && strtolower($preselectedPatient['sex']) === 'male') ? 'bg-primary' : 'bg-danger' ?>" id="cardSex">
                                                    <?= !empty($preselectedPatient['sex']) ? ucfirst(h($preselectedPatient['sex'])) : 'Child' ?>
                                                </span>
                                                <span class="badge bg-light text-secondary border font-monospace" id="cardPatientNo">
                                                    <?= !empty($preselectedPatient['patient_no']) ? h($preselectedPatient['patient_no']) : '--' ?>
                                                </span>
                                                <span class="badge bg-warning-subtle text-dark border font-monospace <?= empty($preselectedPatient['envelope_no']) ? 'd-none' : '' ?>" id="cardEnvelopeBadge">
                                                    Env #<span id="cardEnvelopeNo"><?= h($preselectedPatient['envelope_no'] ?? '') ?></span>
                                                </span>
                                                <?php if (!empty($preselectedPatient['family_no'])): ?>
                                                    <span class="badge bg-info-subtle text-dark border font-monospace" id="cardFamilyBadge">
                                                        Fam #<span id="cardFamilyNo"><?= h($preselectedPatient['family_no']) ?></span>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted" id="cardSubtext">
                                                Brgy. <?= h($preselectedPatient['barangay'] ?? 'Sinalhan') ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="text-md-end">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill font-monospace small">
                                            <i class="bi bi-check-circle-fill me-1"></i> Child Selected
                                        </span>
                                    </div>
                                </div>

                                <!-- Demographic Grid -->
                                <div class="row g-3 small">
                                    <div class="col-12 col-sm-6 col-md-3">
                                        <div class="p-2 bg-white rounded-3 border">
                                            <span class="text-muted d-block" style="font-size: 0.75rem;">Date of Birth</span>
                                            <span class="fw-bold text-dark" id="cardDob">
                                                <?= !empty($preselectedPatient['dob']) ? date('M d, Y', strtotime($preselectedPatient['dob'])) : '--' ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-6 col-md-3">
                                        <div class="p-2 bg-white rounded-3 border">
                                            <span class="text-muted d-block" style="font-size: 0.75rem;">Current Age</span>
                                            <span class="fw-bold text-dark" id="cardAge">
                                                <?php 
                                                    if (!empty($preselectedPatient['dob'])) {
                                                        $dobTime = new \DateTime($preselectedPatient['dob']);
                                                        $nowTime = new \DateTime();
                                                        $diff = $nowTime->diff($dobTime);
                                                        $m = ($diff->y * 12) + $diff->m;
                                                        echo $m < 12 ? "{$m} mos old" : "{$diff->y} yr " . ($diff->m ? "{$diff->m} mos" : "");
                                                    } else {
                                                        echo '--';
                                                    }
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="p-2 bg-white rounded-3 border">
                                            <span class="text-muted d-block" style="font-size: 0.75rem;">Complete Address</span>
                                            <span class="fw-semibold text-dark text-truncate d-block" id="cardAddress">
                                                <?php 
                                                    if (!empty($preselectedPatient)) {
                                                        $addr = array_filter([$preselectedPatient['address'] ?? '', $preselectedPatient['barangay'] ?? '', 'Santa Rosa, Laguna']);
                                                        echo h(implode(', ', $addr) ?: 'Barangay Sinalhan, Santa Rosa, Laguna');
                                                    } else {
                                                        echo '--';
                                                    }
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Parental Information (Hybrid Linking) -->
                <div id="parentalInfoSection" class="card border-0 shadow-sm rounded-4 mb-4 <?= empty($preselectedPatient) ? 'opacity-50 pointer-events-none' : '' ?>">
                    <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center">
                        <span class="badge bg-success rounded-circle p-2 me-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">2</span>
                        <h5 class="card-title h6 fw-bold mb-0 text-dark">Parental Information &amp; Maternal Link</h5>
                    </div>
                    <div class="card-body p-4 bg-white">
                        <div class="alert alert-light border small mb-4">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-info-circle text-primary fs-5 me-2 mt-n1"></i>
                                <div>
                                    <strong>Parental Details &amp; Health Profile Integration:</strong>
                                    Enter the parents' names and birthdates. If the mother is an active registered patient in the health center, you can optionally link her profile to connect the child to her maternal care history.
                                </div>
                            </div>
                        </div>

                        <!-- Link Registered Mother Autocomplete / Selector -->
                        <div class="mb-4 p-3 bg-light rounded-3 border">
                            <label for="motherSearchInput" class="form-label fw-semibold text-secondary small d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-link-45deg me-1"></i>Link Registered Mother Profile (Optional)</span>
                                <span class="badge bg-secondary-subtle text-secondary" id="linkedMotherBadge">Not Linked</span>
                            </label>

                            <input type="hidden" name="mother_patient_id" id="mother_patient_id" value="">

                            <!-- Search Input for Mother -->
                            <div class="input-group mb-2">
                                <span class="input-group-text bg-white text-muted"><i class="bi bi-person-search"></i></span>
                                <input type="text" 
                                       id="motherSearchInput" 
                                       class="form-control bg-white" 
                                       placeholder="Type mother's name or patient ID to link..." 
                                       autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary d-none" id="btnClearMotherLink">
                                    <i class="bi bi-x-circle me-1"></i> Unlink
                                </button>
                            </div>

                            <!-- Mother Live Search Results -->
                            <div id="motherSearchResults" class="list-group shadow-sm rounded-3 border mb-2 d-none" style="max-height: 200px; overflow-y: auto;"></div>

                            <!-- Mother Selected Alert -->
                            <div id="motherSelectedCard" class="d-none p-2 px-3 bg-white rounded border border-success-subtle d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-person-check-fill text-success fs-5"></i>
                                    <div>
                                        <div class="fw-bold text-dark small" id="linkedMotherName">--</div>
                                        <div class="text-muted" style="font-size: 0.75rem;" id="linkedMotherDetails">--</div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-link text-danger text-decoration-none p-0" id="btnRemoveMotherLink">
                                    <i class="bi bi-trash"></i> Remove
                                </button>
                            </div>
                        </div>

                        <!-- Parent Form Fields -->
                        <div class="row g-3">
                            <!-- Mother's Name -->
                            <div class="col-12 col-md-6">
                                <label for="mother_name" class="form-label fw-semibold text-secondary small">
                                    Mother's Full Name
                                </label>
                                <input type="text" 
                                       name="mother_name" 
                                       id="mother_name" 
                                       class="form-control" 
                                       placeholder="e.g. Maria Santos Dela Cruz" 
                                       value="<?= h($initialMotherName) ?>"
                                       autocomplete="new-password"
                                       data-lpignore="true">
                            </div>

                            <!-- Mother's DOB -->
                            <div class="col-12 col-md-6">
                                <label for="mother_dob" class="form-label fw-semibold text-secondary small">
                                    Mother's Date of Birth
                                </label>
                                <input type="date" 
                                       name="mother_dob" 
                                       id="mother_dob" 
                                       class="form-control" 
                                       max="<?= date('Y-m-d') ?>" 
                                       value="<?= h($initialMotherDob) ?>">
                            </div>

                            <!-- Father's Name -->
                            <div class="col-12 col-md-6">
                                <label for="father_name" class="form-label fw-semibold text-secondary small">
                                    Father's Full Name
                                </label>
                                <input type="text" 
                                       name="father_name" 
                                       id="father_name" 
                                       class="form-control" 
                                       placeholder="e.g. Juan Bautista Dela Cruz" 
                                       value="<?= h($initialFatherName) ?>"
                                       autocomplete="new-password"
                                       data-lpignore="true">
                            </div>

                            <!-- Father's DOB -->
                            <div class="col-12 col-md-6">
                                <label for="father_dob" class="form-label fw-semibold text-secondary small">
                                    Father's Date of Birth
                                </label>
                                <input type="date" 
                                       name="father_dob" 
                                       id="father_dob" 
                                       class="form-control" 
                                       max="<?= date('Y-m-d') ?>" 
                                       value="<?= h($initialFatherDob) ?>">
                            </div>

                            <!-- Maternal CPAB TT Status -->
                            <div class="col-12">
                                <label for="mother_cpab_tt" class="form-label fw-semibold text-secondary small">
                                    Maternal CPAB TT Status (Child Protected at Birth)
                                </label>
                                <input type="text" 
                                       name="mother_cpab_tt" 
                                       id="mother_cpab_tt" 
                                       class="form-control" 
                                       placeholder="e.g. Protected at Birth (TT2 given in 2025)" 
                                       value="Protected at Birth">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Birth Circumstances & Newborn Screening -->
                <div id="birthCircumstancesSection" class="card border-0 shadow-sm rounded-4 mb-4 <?= empty($preselectedPatient) ? 'opacity-50 pointer-events-none' : '' ?>">
                    <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center">
                        <span class="badge bg-success rounded-circle p-2 me-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">3</span>
                        <h5 class="card-title h6 fw-bold mb-0 text-dark">Birth Circumstances &amp; Newborn Screening</h5>
                    </div>
                    <div class="card-body p-4 bg-white">
                        <div class="row g-3">
                            <!-- Birth Weight -->
                            <div class="col-12 col-sm-6 col-md-4">
                                <label for="birth_weight_kg" class="form-label fw-semibold text-secondary small">
                                    Birth Weight (kg) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="number" 
                                           step="0.01" 
                                           name="birth_weight_kg" 
                                           id="birth_weight_kg" 
                                           class="form-control" 
                                           placeholder="e.g. 3.20" 
                                           required>
                                    <span class="input-group-text bg-light text-muted">kg</span>
                                </div>
                            </div>

                            <!-- Birth Length -->
                            <div class="col-12 col-sm-6 col-md-4">
                                <label for="birth_length_cm" class="form-label fw-semibold text-secondary small">
                                    Birth Length (cm) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="number" 
                                           step="0.1" 
                                           name="birth_length_cm" 
                                           id="birth_length_cm" 
                                           class="form-control" 
                                           placeholder="e.g. 50.0" 
                                           required>
                                    <span class="input-group-text bg-light text-muted">cm</span>
                                </div>
                            </div>

                            <!-- Birth Time -->
                            <div class="col-12 col-sm-6 col-md-4">
                                <label for="birth_time" class="form-label fw-semibold text-secondary small">
                                    Time of Birth
                                </label>
                                <input type="time" 
                                       name="birth_time" 
                                       id="birth_time" 
                                       class="form-control">
                            </div>

                            <!-- Place of Delivery -->
                            <div class="col-12 col-sm-6 col-md-4">
                                <label for="place_of_delivery" class="form-label fw-semibold text-secondary small">
                                    Place of Delivery
                                </label>
                                <select name="place_of_delivery" id="place_of_delivery" class="form-select">
                                    <option value="Lying-in">Lying-in Clinic</option>
                                    <option value="Hospital">Hospital</option>
                                    <option value="Barangay Health Station (BHS)">Barangay Health Station (BHS)</option>
                                    <option value="Home">Home</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <!-- Delivery Type -->
                            <div class="col-12 col-sm-6 col-md-4">
                                <label for="delivery_type" class="form-label fw-semibold text-secondary small">
                                    Delivery Type
                                </label>
                                <select name="delivery_type" id="delivery_type" class="form-select">
                                    <option value="Normal Spontaneous Delivery (NSD)">Normal Spontaneous Delivery (NSD)</option>
                                    <option value="Cesarean Section (CS)">Cesarean Section (CS)</option>
                                    <option value="Breech Extraction">Breech Extraction</option>
                                    <option value="Vacuum Assisted">Vacuum Assisted</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <!-- Attended By -->
                            <div class="col-12 col-sm-6 col-md-4">
                                <label for="attended_by" class="form-label fw-semibold text-secondary small">
                                    Attended By
                                </label>
                                <input type="text" 
                                       name="attended_by" 
                                       id="attended_by" 
                                       class="form-control" 
                                       placeholder="e.g. Midwife Ramos, Dr. Santos" 
                                       value="Midwife">
                            </div>

                            <!-- Feeding Method -->
                            <div class="col-12">
                                <label for="feeding_method" class="form-label fw-semibold text-secondary small">
                                    Initial Infant Feeding Practice
                                </label>
                                <select name="feeding_method" id="feeding_method" class="form-select">
                                    <option value="LAM / Exclusive Breastfeeding">LAM / Exclusive Breastfeeding</option>
                                    <option value="Bottle Feed">Bottle Feeding (Formula)</option>
                                    <option value="Mixed">Mixed Feeding</option>
                                </select>
                            </div>

                            <!-- Newborn Screening (NBS) Box -->
                            <div class="col-12 mt-4">
                                <div class="p-3 bg-light rounded-3 border">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <h6 class="fw-bold text-dark mb-0">
                                            <i class="bi bi-clipboard2-pulse text-success me-2"></i>Newborn Screening (NBS) Certificate
                                        </h6>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="newborn_screening_done" value="1" id="newborn_screening_done">
                                            <label class="form-check-label fw-semibold text-success small" for="newborn_screening_done">
                                                NBS Done
                                            </label>
                                        </div>
                                    </div>

                                    <div class="row g-3" id="nbsFieldsRow">
                                        <div class="col-12 col-md-6">
                                            <label for="newborn_screening_date" class="form-label fw-semibold text-secondary small">
                                                NBS Date Screened
                                            </label>
                                            <input type="date" 
                                                   name="newborn_screening_date" 
                                                   id="newborn_screening_date" 
                                                   class="form-control" 
                                                   max="<?= date('Y-m-d') ?>">
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label for="newborn_screening_result" class="form-label fw-semibold text-secondary small">
                                                NBS Result / Certificate #
                                            </label>
                                            <input type="text" 
                                                   name="newborn_screening_result" 
                                                   id="newborn_screening_result" 
                                                   class="form-control" 
                                                   placeholder="e.g. Normal (Cert # NBS-2026-09)" 
                                                   value="Normal">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Action Bar inside Card -->
                    <div class="card-footer bg-light py-3 px-4 border-0 d-flex justify-content-between align-items-center" style="pointer-events: auto !important;">
                        <a href="<?= url('/well-baby') ?>" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-success text-white px-4 py-2 fw-semibold shadow-sm" id="btnSubmitRegistration" <?= $alreadyRegistered ? 'disabled' : '' ?>>
                            <i class="bi bi-check-circle-fill me-1"></i> Register Infant &amp; Open Workstation
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>

<style>
.pointer-events-none,
.pointer-events-none * {
    pointer-events: none !important;
    user-select: none !important;
}
.shadow-xs {
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}
</style>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('patientSearchInput');
    const clearSearchBtn = document.getElementById('btnClearSearch');
    const resultsContainer = document.getElementById('searchResultsContainer');
    const loadingSpinner = document.getElementById('searchLoadingSpinner');
    const selectedPatientId = document.getElementById('selectedPatientId');
    const patientSearchSection = document.getElementById('patientSearchSection');
    const patientIdentityCard = document.getElementById('patientIdentityCard');
    const btnChangePatient = document.getElementById('btnChangePatient');
    const parentalInfoSection = document.getElementById('parentalInfoSection');
    const birthCircumstancesSection = document.getElementById('birthCircumstancesSection');
    const form = document.getElementById('wellbabyRegistrationForm');

    // Mother Search elements
    const motherSearchInput = document.getElementById('motherSearchInput');
    const motherResults = document.getElementById('motherSearchResults');
    const motherPatientId = document.getElementById('mother_patient_id');
    const motherSelectedCard = document.getElementById('motherSelectedCard');
    const linkedMotherName = document.getElementById('linkedMotherName');
    const linkedMotherDetails = document.getElementById('linkedMotherDetails');
    const linkedMotherBadge = document.getElementById('linkedMotherBadge');
    const btnClearMotherLink = document.getElementById('btnClearMotherLink');
    const btnRemoveMotherLink = document.getElementById('btnRemoveMotherLink');
    const motherNameInput = document.getElementById('mother_name');
    const motherDobInput = document.getElementById('mother_dob');

    let debounceTimer;

    // 1. Live Infant Search Handlers
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            if (clearSearchBtn) {
                clearSearchBtn.classList.toggle('d-none', query.length === 0);
            }

            clearTimeout(debounceTimer);
            if (query.length < 1) {
                resultsContainer.classList.add('d-none');
                resultsContainer.innerHTML = '';
                return;
            }

            loadingSpinner.classList.remove('d-none');
            resultsContainer.classList.add('d-none');

            debounceTimer = setTimeout(() => {
                fetch('<?= url('/well-baby/search-infant') ?>?q=' + encodeURIComponent(query))
                    .then(response => response.json())
                    .then(data => {
                        loadingSpinner.classList.add('d-none');
                        renderInfantResults(data.results || []);
                    })
                    .catch(err => {
                        loadingSpinner.classList.add('d-none');
                        console.error('Search error:', err);
                    });
            }, 300);
        });

        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', function() {
                searchInput.value = '';
                clearSearchBtn.classList.add('d-none');
                resultsContainer.classList.add('d-none');
                resultsContainer.innerHTML = '';
                searchInput.focus();
            });
        }
    }

    function renderInfantResults(results) {
        if (!results || results.length === 0) {
            resultsContainer.innerHTML = `
                <div class="list-group-item text-center py-3 text-muted small">
                    <i class="bi bi-info-circle me-1"></i> No unregistered children (aged 0–5) found matching your search.
                </div>
            `;
            resultsContainer.classList.remove('d-none');
            return;
        }

        let html = '';
        results.forEach(child => {
            const initials = ((child.first_name ? child.first_name[0] : '') + (child.last_name ? child.last_name[0] : '')).toUpperCase();
            const sexBadgeClass = (child.sex && child.sex.toLowerCase() === 'male') ? 'bg-primary' : 'bg-danger';
            
            html += `
                <button type="button" class="list-group-item list-group-item-action p-3 text-start btn-select-child" 
                        data-child='${JSON.stringify(child).replace(/'/g, "&apos;")}'>
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center fw-bold small" style="width: 38px; height: 38px;">
                                ${initials}
                            </div>
                            <div>
                                <div class="fw-bold text-dark">${child.name}</div>
                                <div class="small text-muted">
                                    <span class="badge ${sexBadgeClass} text-white" style="font-size: 0.68rem;">${child.sex}</span>
                                    <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.68rem;">${child.patient_no}</span>
                                    ${child.envelope_no ? `<span class="badge bg-warning-subtle text-dark border font-monospace" style="font-size: 0.68rem;">Env #${child.envelope_no}</span>` : ''}
                                    &bull; Age: <strong>${child.age_label}</strong> (DOB: ${child.dob_formatted})
                                </div>
                            </div>
                        </div>
                        <span class="btn btn-sm btn-outline-success rounded-pill px-3">
                            Select <i class="bi bi-chevron-right ms-1"></i>
                        </span>
                    </div>
                </button>
            `;
        });

        resultsContainer.innerHTML = html;
        resultsContainer.classList.remove('d-none');

        // Attach select listeners
        resultsContainer.querySelectorAll('.btn-select-child').forEach(btn => {
            btn.addEventListener('click', function() {
                const childData = JSON.parse(this.getAttribute('data-child'));
                selectChild(childData);
            });
        });
    }

    function selectChild(child) {
        selectedPatientId.value = child.id;

        // Populate Card
        const initials = ((child.first_name ? child.first_name[0] : '') + (child.last_name ? child.last_name[0] : '')).toUpperCase();
        document.getElementById('cardInitials').textContent = initials;
        document.getElementById('cardFullName').textContent = child.name;
        
        const cardSex = document.getElementById('cardSex');
        if (cardSex) {
            cardSex.textContent = child.sex;
            cardSex.className = 'badge ' + ((child.sex && child.sex.toLowerCase() === 'male') ? 'bg-primary' : 'bg-danger');
        }
        
        document.getElementById('cardPatientNo').textContent = child.patient_no;
        
        const envBadge = document.getElementById('cardEnvelopeBadge');
        if (envBadge) {
            if (child.envelope_no) {
                document.getElementById('cardEnvelopeNo').textContent = child.envelope_no;
                envBadge.classList.remove('d-none');
            } else {
                envBadge.classList.add('d-none');
            }
        }

        document.getElementById('cardDob').textContent = child.dob_formatted;
        document.getElementById('cardAge').textContent = child.age_label + ' old';
        document.getElementById('cardAddress').textContent = child.address;

        // Fill existing parental text if present
        if (child.mother_name && !motherNameInput.value) {
            motherNameInput.value = child.mother_name;
        }
        if (child.mother_dob && !motherDobInput.value) {
            motherDobInput.value = child.mother_dob;
        }
        const fatherNameInput = document.getElementById('father_name');
        if (fatherNameInput && child.father_name && !fatherNameInput.value) {
            fatherNameInput.value = child.father_name;
        }
        const fatherDobInput = document.getElementById('father_dob');
        if (fatherDobInput && child.father_dob && !fatherDobInput.value) {
            fatherDobInput.value = child.father_dob;
        }

        // Hide search, show card
        patientSearchSection.classList.add('d-none');
        patientIdentityCard.classList.remove('d-none');
        btnChangePatient.classList.remove('d-none');
        resultsContainer.classList.add('d-none');

        // Unlock steps
        parentalInfoSection.classList.remove('opacity-50', 'pointer-events-none');
        birthCircumstancesSection.classList.remove('opacity-50', 'pointer-events-none');
        setClinicalInputsDisabled(false);
    }

    function setClinicalInputsDisabled(disabled) {
        [parentalInfoSection, birthCircumstancesSection].forEach(sec => {
            if (sec) {
                sec.querySelectorAll('input, select, textarea').forEach(el => {
                    el.disabled = disabled;
                });
            }
        });
        const submitBtn = document.getElementById('btnSubmitRegistration');
        if (submitBtn) {
            submitBtn.disabled = disabled || <?= $alreadyRegistered ? 'true' : 'false' ?>;
        }
    }

    // Initial state: disable clinical sections if no child is selected
    if (!selectedPatientId || !selectedPatientId.value) {
        setClinicalInputsDisabled(true);
    } else if (<?= $alreadyRegistered ? 'true' : 'false' ?>) {
        const submitBtn = document.getElementById('btnSubmitRegistration');
        if (submitBtn) submitBtn.disabled = true;
    }

    if (btnChangePatient) {
        btnChangePatient.addEventListener('click', function() {
            selectedPatientId.value = '';
            patientIdentityCard.classList.add('d-none');
            patientSearchSection.classList.remove('d-none');
            btnChangePatient.classList.add('d-none');
            if (searchInput) {
                searchInput.value = '';
                searchInput.focus();
            }
            if (clearSearchBtn) clearSearchBtn.classList.add('d-none');
            parentalInfoSection.classList.add('opacity-50', 'pointer-events-none');
            birthCircumstancesSection.classList.add('opacity-50', 'pointer-events-none');
            setClinicalInputsDisabled(true);
        });
    }

    // 2. Mother Live Autocomplete Handlers
    let motherDebounceTimer;
    if (motherSearchInput) {
        motherSearchInput.addEventListener('input', function() {
            const query = this.value.trim();
            clearTimeout(motherDebounceTimer);

            if (query.length < 1) {
                motherResults.classList.add('d-none');
                motherResults.innerHTML = '';
                return;
            }

            motherDebounceTimer = setTimeout(() => {
                fetch('<?= url('/api/patients/search/female') ?>?q=' + encodeURIComponent(query))
                    .then(res => res.json())
                    .then(data => {
                        renderMotherResults(data.results || []);
                    })
                    .catch(err => console.error('Mother search error:', err));
            }, 300);
        });
    }

    function renderMotherResults(results) {
        if (!results || results.length === 0) {
            motherResults.innerHTML = `
                <div class="list-group-item text-center py-2 text-muted small">
                    No matching female patients found.
                </div>
            `;
            motherResults.classList.remove('d-none');
            return;
        }

        let html = '';
        results.forEach(m => {
            html += `
                <button type="button" class="list-group-item list-group-item-action py-2 px-3 btn-select-mother"
                        data-mother='${JSON.stringify(m).replace(/'/g, "&apos;")}'>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong class="text-dark">${m.name}</strong> 
                            <span class="badge bg-light text-secondary border font-monospace ms-1">${m.patient_no}</span>
                            <div class="small text-muted">${m.dob_formatted} &bull; ${m.age} yrs &bull; ${m.civil_status}</div>
                        </div>
                        <span class="btn btn-sm btn-outline-primary py-0 px-2 small">Link</span>
                    </div>
                </button>
            `;
        });

        motherResults.innerHTML = html;
        motherResults.classList.remove('d-none');

        motherResults.querySelectorAll('.btn-select-mother').forEach(btn => {
            btn.addEventListener('click', function() {
                const mom = JSON.parse(this.getAttribute('data-mother'));
                linkMother(mom);
            });
        });
    }

    function linkMother(mom) {
        motherPatientId.value = mom.id;
        linkedMotherName.textContent = mom.name + ' (' + mom.patient_no + ')';
        linkedMotherDetails.textContent = (mom.dob_formatted || '') + ' • ' + (mom.age ? mom.age + ' years old' : '') + ' • ' + (mom.address || '');
        
        motherSelectedCard.classList.remove('d-none');
        motherSearchInput.parentElement.classList.add('d-none');
        motherResults.classList.add('d-none');
        linkedMotherBadge.className = 'badge bg-success-subtle text-success border border-success-subtle';
        linkedMotherBadge.textContent = 'Linked to Patient #' + mom.patient_no;

        // Auto-populate mother text fields
        if (mom.name) motherNameInput.value = mom.first_name + ' ' + mom.last_name;
        if (mom.dob) motherDobInput.value = mom.dob;
    }

    function unlinkMother() {
        motherPatientId.value = '';
        motherSelectedCard.classList.add('d-none');
        motherSearchInput.parentElement.classList.remove('d-none');
        motherSearchInput.value = '';
        linkedMotherBadge.className = 'badge bg-secondary-subtle text-secondary';
        linkedMotherBadge.textContent = 'Not Linked';
    }

    if (btnRemoveMotherLink) {
        btnRemoveMotherLink.addEventListener('click', unlinkMother);
    }
    if (btnClearMotherLink) {
        btnClearMotherLink.addEventListener('click', unlinkMother);
    }

    // 3. NBS Checkbox Toggle requirement
    const nbsCheckbox = document.getElementById('newborn_screening_done');
    const nbsDate = document.getElementById('newborn_screening_date');
    if (nbsCheckbox && nbsDate) {
        nbsCheckbox.addEventListener('change', function() {
            if (this.checked) {
                if (!nbsDate.value) {
                    nbsDate.value = new Date().toISOString().split('T')[0];
                }
                nbsDate.setAttribute('required', 'required');
            } else {
                nbsDate.removeAttribute('required');
            }
        });
    }

    // 4. Form Validation
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!selectedPatientId.value) {
                e.preventDefault();
                alert('Please select a child to proceed with Well-Baby registration.');
                if (searchInput) searchInput.focus();
                return;
            }

            const birthWeight = parseFloat(document.getElementById('birth_weight_kg').value);
            const birthLength = parseFloat(document.getElementById('birth_length_cm').value);

            if (isNaN(birthWeight) || birthWeight <= 0) {
                e.preventDefault();
                alert('Please provide a valid birth weight (kg).');
                document.getElementById('birth_weight_kg').focus();
                return;
            }

            if (isNaN(birthLength) || birthLength <= 0) {
                e.preventDefault();
                alert('Please provide a valid birth length (cm).');
                document.getElementById('birth_length_cm').focus();
                return;
            }

            if (nbsCheckbox && nbsCheckbox.checked && !nbsDate.value) {
                e.preventDefault();
                alert('Please provide the Newborn Screening (NBS) date.');
                nbsDate.focus();
                return;
            }
        });
    }
});
</script>

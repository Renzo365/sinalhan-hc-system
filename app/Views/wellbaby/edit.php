<?php
/**
 * Dedicated Well-Baby Birth Record & Parental Edit View
 * 
 * @var array $patient Child patient demographic record
 * @var array $wellbabyRecord Initialized well-baby record
 * @var array $potentialMothers List of registered female patients for maternal linking
 */

// Child full name formatting
$lastName = trim($patient['last_name'] ?? '');
$firstName = trim($patient['first_name'] ?? '');
$middleName = trim($patient['middle_name'] ?? '');
$suffix = trim($patient['suffix'] ?? '');

$fullNameFormatted = $lastName . ', ' . $firstName;
if (!empty($middleName)) {
    $fullNameFormatted .= ' ' . mb_substr($middleName, 0, 1) . '.';
}
if (!empty($suffix)) {
    $fullNameFormatted .= ' ' . $suffix;
}

$title = 'Edit Well-Baby Record: ' . $fullNameFormatted;
$breadcrumbs = [
    'Well-Baby & EPI' => '/well-baby',
    $fullNameFormatted => '/well-baby/' . $patient['id'],
    'Edit Birth Record' => null
];
require dirname(__DIR__) . '/layout/header.php';

$fullAddress = !empty(trim($patient['address'] ?? '')) ? trim($patient['address']) : 'Barangay Sinalhan, Santa Rosa, Laguna';

$initials = '';
if (!empty($firstName)) $initials .= mb_substr($firstName, 0, 1);
if (!empty($lastName)) $initials .= mb_substr($lastName, 0, 1);
$initials = strtoupper($initials ?: 'WB');

// Preloaded Parental Info
$motherName = $patient['mother_name'] ?? '';
$motherDob = $patient['mother_dob'] ?? '';
$fatherName = $patient['father_name'] ?? '';
$fatherDob = $patient['father_dob'] ?? '';

// Fallback to linked mother if demographic mother_name is not set
if (empty($motherName) && (!empty($wellbabyRecord['mother_first_name']) || !empty($wellbabyRecord['mother_last_name']))) {
    $motherName = trim(($wellbabyRecord['mother_last_name'] ?? '') . ', ' . ($wellbabyRecord['mother_first_name'] ?? ''));
}

$motherAge = !empty($motherDob) ? calculate_age($motherDob) : (!empty($wellbabyRecord['mother_age']) ? $wellbabyRecord['mother_age'] : '');
$fatherAge = !empty($fatherDob) ? calculate_age($fatherDob) : '';

$motherLinkedId = $wellbabyRecord['mother_patient_id'] ?? '';
$motherLinkedPatientNo = $wellbabyRecord['mother_patient_no'] ?? '';
$motherLinkedName = trim(($wellbabyRecord['mother_first_name'] ?? '') . ' ' . ($wellbabyRecord['mother_last_name'] ?? ''));
$motherLinkedDetails = '';
if (!empty($wellbabyRecord['mother_dob'])) {
    $motherLinkedDetails = date('M d, Y', strtotime($wellbabyRecord['mother_dob']));
}
if (!empty($wellbabyRecord['mother_age'])) {
    $motherLinkedDetails .= ($motherLinkedDetails ? ' • ' : '') . $wellbabyRecord['mother_age'] . ' years old';
}
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="h3 mb-1 fw-bold text-dark">
                Edit Well-Baby Birth Record
            </h2>
            <p class="text-secondary small mb-0">Update infant birth circumstances, newborn screening certificate, and parental information.</p>
        </div>
        <div>
            <a href="<?= url('/well-baby/' . $patient['id']) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Workstation
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

            <!-- Compact Child Identity Card (Locked) -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
                    <h5 class="card-title h6 fw-bold mb-0 text-dark">
                        Infant / Child Demographic Profile
                    </h5>
                    <span class="badge bg-success text-white font-monospace">Record #<?= h($wellbabyRecord['id']) ?></span>
                </div>
                <div class="card-body p-4 bg-white">
                    <div class="p-3 p-md-4 rounded-4 bg-success bg-opacity-10 border border-success border-opacity-25">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-3 pb-3 border-bottom border-success border-opacity-25">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center fw-bold fs-4 shadow-xs" style="width: 54px; height: 54px;">
                                    <?= h($initials) ?>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <h5 class="h6 fw-bold text-dark mb-0"><?= h($fullNameFormatted) ?></h5>
                                        <span class="badge <?= (strtolower($patient['sex'] ?? '') === 'male') ? 'bg-primary' : 'bg-danger' ?>">
                                            <?= ucfirst(h($patient['sex'] ?? 'Child')) ?>
                                        </span>
                                        <span class="badge bg-light text-secondary border font-monospace"><?= h($patient['patient_no']) ?></span>
                                        <?php if (!empty($patient['envelope_no'])): ?>
                                            <span class="badge bg-warning-subtle text-dark border font-monospace">Env #<?= h($patient['envelope_no']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($patient['family_no'])): ?>
                                            <span class="badge bg-info-subtle text-dark border font-monospace">Fam #<?= h($patient['family_no']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted">
                                        <i class="bi bi-geo-alt me-1"></i><?= h(!empty(trim($patient['address'] ?? '')) ? trim($patient['address']) : 'Barangay Sinalhan') ?> &bull; Contact: <?= h($patient['contact_no'] ?? 'N/A') ?>
                                    </small>
                                </div>
                            </div>
                            <div class="text-md-end">
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill font-monospace small">
                                    <i class="bi bi-calendar-check me-1"></i> Registered: <?= date('M d, Y', strtotime($wellbabyRecord['created_at'])) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Demographic Grid -->
                        <div class="row g-3 small">
                            <div class="col-12 col-sm-6 col-md-3">
                                <div class="p-2 bg-white rounded-3 border">
                                    <span class="text-muted d-block" style="font-size: 0.75rem;">Date of Birth</span>
                                    <span class="fw-bold text-dark">
                                        <?= !empty($patient['dob']) ? date('M d, Y', strtotime($patient['dob'])) : '--' ?>
                                    </span>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-md-3">
                                <div class="p-2 bg-white rounded-3 border">
                                    <span class="text-muted d-block" style="font-size: 0.75rem;">Current Age</span>
                                    <span class="fw-bold text-dark">
                                        <?= !empty($patient['dob']) ? h(calculate_pediatric_age($patient['dob'])) : h($patient['age'] ?? '--') . ' yrs' ?>
                                    </span>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="p-2 bg-white rounded-3 border">
                                    <span class="text-muted d-block" style="font-size: 0.75rem;">Complete Address</span>
                                    <span class="fw-semibold text-dark text-truncate d-block">
                                        <?= h($fullAddress) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Edit Form -->
            <form action="<?= url('/well-baby/' . $patient['id'] . '/edit') ?>" method="POST" id="editWellbabyForm" autocomplete="off">
                <?= csrf_field() ?>

                <!-- Section 1: Child Information & Birth History -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center">
                        <span class="badge bg-success rounded-circle p-2 me-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">1</span>
                        <h5 class="card-title h6 fw-bold mb-0 text-dark">Child Information &amp; Birth History</h5>
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
                                           value="<?= h($wellbabyRecord['birth_weight_kg'] ?? '') ?>" 
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
                                           value="<?= h($wellbabyRecord['birth_length_cm'] ?? '') ?>" 
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
                                       class="form-control" 
                                       value="<?= h($wellbabyRecord['birth_time'] ?? '') ?>">
                            </div>

                            <!-- Place of Delivery -->
                            <div class="col-12 col-sm-6 col-md-4">
                                <label for="place_of_delivery" class="form-label fw-semibold text-secondary small">
                                    Place of Delivery <span class="text-danger">*</span>
                                </label>
                                <?php 
                                    $podVal = $wellbabyRecord['place_of_delivery'] ?? 'Lying-in';
                                    $podOther = $wellbabyRecord['place_of_delivery_other'] ?? '';
                                    if (!in_array($podVal, ['Hospital', 'Lying-in', 'Home', 'Others'])) {
                                        if (stripos($podVal, 'lying') !== false) {
                                            $podVal = 'Lying-in';
                                        } elseif (stripos($podVal, 'hospital') !== false) {
                                            $podVal = 'Hospital';
                                        } elseif (stripos($podVal, 'home') !== false) {
                                            $podVal = 'Home';
                                        } else {
                                            $podOther = $podVal;
                                            $podVal = 'Others';
                                        }
                                    }
                                ?>
                                <select name="place_of_delivery" id="place_of_delivery" class="form-select" required>
                                    <option value="Hospital" <?= $podVal === 'Hospital' ? 'selected' : '' ?>>Hospital</option>
                                    <option value="Lying-in" <?= $podVal === 'Lying-in' ? 'selected' : '' ?>>Lying-in</option>
                                    <option value="Home" <?= $podVal === 'Home' ? 'selected' : '' ?>>Home</option>
                                    <option value="Others" <?= $podVal === 'Others' ? 'selected' : '' ?>>Others</option>
                                </select>
                            </div>

                            <!-- Place of Delivery Other (Dynamic) -->
                            <div class="col-12 col-sm-6 col-md-4 <?= $podVal === 'Others' ? '' : 'd-none' ?>" id="place_of_delivery_other_wrapper">
                                <label for="place_of_delivery_other" class="form-label fw-semibold text-secondary small">
                                    Specify Place of Delivery <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       name="place_of_delivery_other" 
                                       id="place_of_delivery_other" 
                                       class="form-control" 
                                       placeholder="e.g. In Transit, Clinic Name"
                                       value="<?= h($podOther) ?>"
                                       <?= $podVal === 'Others' ? 'required' : '' ?>>
                            </div>

                            <!-- Type of Delivery -->
                            <div class="col-12 col-sm-6 col-md-4">
                                <label for="delivery_type" class="form-label fw-semibold text-secondary small">
                                    Type of Delivery
                                </label>
                                <?php 
                                    $dt = $wellbabyRecord['delivery_type'] ?? 'Normal Spontaneous Delivery (NSD)';
                                    $isCS = (stripos($dt, 'Caesarean') !== false || stripos($dt, 'Cesarean') !== false || stripos($dt, 'CS') !== false);
                                    $isOthers = ($dt === 'Others' || stripos($dt, 'Other') !== false);
                                    $isNSD = !$isCS && !$isOthers;
                                ?>
                                <select name="delivery_type" id="delivery_type" class="form-select" required>
                                    <option value="Normal Spontaneous Delivery (NSD)" <?= $isNSD ? 'selected' : '' ?>>Normal Spontaneous Delivery (NSD)</option>
                                    <option value="Caesarean Section (CS)" <?= $isCS ? 'selected' : '' ?>>Caesarean Section (CS)</option>
                                    <option value="Others" <?= $isOthers ? 'selected' : '' ?>>Others</option>
                                </select>
                            </div>

                            <!-- Attended By -->
                            <div class="col-12 col-sm-6 col-md-4">
                                <label for="attended_by" class="form-label fw-semibold text-secondary small">
                                    Attended By <span class="text-danger">*</span>
                                </label>
                                <?php 
                                    $attVal = $wellbabyRecord['attended_by'] ?? 'Midwife';
                                    $attOther = $wellbabyRecord['attended_by_other'] ?? '';
                                    if (!in_array($attVal, ['Doctor', 'Nurse', 'Midwife', 'Hilot/TBA', 'Others'])) {
                                        if (stripos($attVal, 'doc') !== false) {
                                            $attVal = 'Doctor';
                                        } elseif (stripos($attVal, 'nurse') !== false) {
                                            $attVal = 'Nurse';
                                        } elseif (stripos($attVal, 'midwife') !== false) {
                                            $attVal = 'Midwife';
                                        } elseif (stripos($attVal, 'hilot') !== false || stripos($attVal, 'tba') !== false) {
                                            $attVal = 'Hilot/TBA';
                                        } else {
                                            $attOther = $attVal;
                                            $attVal = 'Others';
                                        }
                                    }
                                ?>
                                <select name="attended_by" id="attended_by" class="form-select" required>
                                    <option value="Doctor" <?= $attVal === 'Doctor' ? 'selected' : '' ?>>Doctor</option>
                                    <option value="Nurse" <?= $attVal === 'Nurse' ? 'selected' : '' ?>>Nurse</option>
                                    <option value="Midwife" <?= $attVal === 'Midwife' ? 'selected' : '' ?>>Midwife</option>
                                    <option value="Hilot/TBA" <?= $attVal === 'Hilot/TBA' ? 'selected' : '' ?>>Hilot/TBA</option>
                                    <option value="Others" <?= $attVal === 'Others' ? 'selected' : '' ?>>Others</option>
                                </select>
                            </div>

                            <!-- Attended By Other (Dynamic) -->
                            <div class="col-12 col-sm-6 col-md-4 <?= $attVal === 'Others' ? '' : 'd-none' ?>" id="attended_by_other_wrapper">
                                <label for="attended_by_other" class="form-label fw-semibold text-secondary small">
                                    Specify Attendant <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       name="attended_by_other" 
                                       id="attended_by_other" 
                                       class="form-control" 
                                       placeholder="Specify attendant title/name"
                                       value="<?= h($attOther) ?>"
                                       <?= $attVal === 'Others' ? 'required' : '' ?>>
                            </div>

                            <!-- Newborn Screening (NBS) Box -->
                            <div class="col-12 mt-4">
                                <div class="p-3 bg-light rounded-3 border">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div>
                                            <h6 class="fw-bold text-dark mb-0">
                                                Newborn Screening (NBS)
                                            </h6>
                                            <span class="text-muted small">Record status and certification if performed</span>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   name="newborn_screening_done" 
                                                   value="1" 
                                                   id="newborn_screening_done" 
                                                   <?= !empty($wellbabyRecord['newborn_screening_done']) ? 'checked' : '' ?>>
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
                                                   max="<?= date('Y-m-d') ?>" 
                                                   value="<?= h($wellbabyRecord['newborn_screening_date'] ?? '') ?>"
                                                   <?= empty($wellbabyRecord['newborn_screening_done']) ? 'disabled' : '' ?>>
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
                                                   value="<?= h($wellbabyRecord['newborn_screening_result'] ?? 'Normal') ?>"
                                                   <?= empty($wellbabyRecord['newborn_screening_done']) ? 'disabled' : '' ?>>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Parental Information -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center">
                        <span class="badge bg-success rounded-circle p-2 me-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">2</span>
                        <h5 class="card-title h6 fw-bold mb-0 text-dark">Parental Information</h5>
                    </div>
                    <div class="card-body p-4 bg-white">
                        <!-- Smart Mother Linker (Optional Central Registry Integration) -->
                        <div class="mb-4 p-3 bg-light rounded-3 border">
                            <label for="motherSearchInput" class="form-label fw-semibold text-secondary small d-flex justify-content-between align-items-center mb-2">
                                <span><i class="bi bi-search-heart text-success me-1"></i> Search Registered Mother in Directory (Optional)</span>
                                <span class="badge <?= !empty($motherLinkedId) ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary' ?>" id="linkedMotherBadge">
                                    <?= !empty($motherLinkedId) ? 'Linked: Patient #' . h($motherLinkedPatientNo) : 'Not Linked' ?>
                                </span>
                            </label>

                            <input type="hidden" name="mother_patient_id" id="mother_patient_id" value="<?= h($motherLinkedId) ?>">

                            <!-- Search Input for Mother -->
                            <div class="input-group mb-2 <?= !empty($motherLinkedId) ? 'd-none' : '' ?>" id="motherSearchWrapper">
                                <span class="input-group-text bg-white text-muted"><i class="bi bi-person-search"></i></span>
                                <input type="text" 
                                       id="motherSearchInput" 
                                       class="form-control bg-white" 
                                       placeholder="Type mother's name or patient ID to link and auto-fill..." 
                                       autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary d-none" id="btnClearMotherSearch">
                                    <i class="bi bi-x-circle me-1"></i> Clear
                                </button>
                            </div>

                            <!-- Mother Live Search Results Dropdown -->
                            <div id="motherSearchResults" class="list-group shadow-sm rounded-3 border mb-2 d-none" style="max-height: 220px; overflow-y: auto;"></div>

                            <!-- Mother Selected Card -->
                            <div id="motherSelectedCard" class="<?= empty($motherLinkedId) ? 'd-none' : '' ?> p-2 px-3 bg-white rounded border border-success-subtle d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-person-check-fill text-success fs-5"></i>
                                    <div>
                                        <div class="fw-bold text-dark small" id="linkedMotherName">
                                            <?= !empty($motherLinkedName) ? h($motherLinkedName) . ' (' . h($motherLinkedPatientNo) . ')' : '--' ?>
                                        </div>
                                        <div class="text-muted" style="font-size: 0.75rem;" id="linkedMotherDetails">
                                            <?= !empty($motherLinkedDetails) ? h($motherLinkedDetails) : '--' ?>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-link text-danger text-decoration-none p-0" id="btnRemoveMotherLink">
                                    <i class="bi bi-x-circle me-1"></i> Unlink Mother
                                </button>
                            </div>

                            <div class="form-text text-muted small mt-1">
                                <i class="bi bi-info-circle me-1"></i> If the mother is registered in the health center, selecting her will automatically fill her details and link her profile. If not registered, simply leave this unlinked and type her information below.
                            </div>
                        </div>

                        <!-- Parent Form Fields -->
                        <div class="row g-3">
                            <!-- Mother's Name -->
                            <div class="col-12 col-md-5">
                                <label for="mother_name" class="form-label fw-semibold text-secondary small">
                                    Mother's Full Name
                                </label>
                                <input type="text" 
                                       name="mother_name" 
                                       id="mother_name" 
                                       class="form-control" 
                                       placeholder="e.g. Maria Santos Dela Cruz" 
                                       value="<?= h($motherName) ?>"
                                       autocomplete="new-password"
                                       data-lpignore="true">
                            </div>

                            <!-- Mother's DOB -->
                            <div class="col-12 col-sm-6 col-md-4">
                                <label for="mother_dob" class="form-label fw-semibold text-secondary small">
                                    Mother's Date of Birth
                                </label>
                                <input type="date" 
                                       name="mother_dob" 
                                       id="mother_dob" 
                                       class="form-control" 
                                       max="<?= date('Y-m-d') ?>" 
                                       value="<?= h($motherDob) ?>">
                            </div>

                            <!-- Mother's Age (Auto-calculated) -->
                            <div class="col-12 col-sm-6 col-md-3">
                                <label for="mother_age" class="form-label fw-semibold text-secondary small">
                                    Mother's Age
                                </label>
                                <div class="input-group">
                                    <input type="number" 
                                           id="mother_age" 
                                           class="form-control bg-light" 
                                           placeholder="Auto" 
                                           value="<?= h($motherAge) ?>"
                                           readonly>
                                    <span class="input-group-text bg-light text-muted small">yrs</span>
                                </div>
                            </div>

                            <!-- Father's Name -->
                            <div class="col-12 col-md-5">
                                <label for="father_name" class="form-label fw-semibold text-secondary small">
                                    Father's Full Name
                                </label>
                                <input type="text" 
                                       name="father_name" 
                                       id="father_name" 
                                       class="form-control" 
                                       placeholder="e.g. Juan Bautista Dela Cruz" 
                                       value="<?= h($fatherName) ?>"
                                       autocomplete="new-password"
                                       data-lpignore="true">
                            </div>

                            <!-- Father's DOB -->
                            <div class="col-12 col-sm-6 col-md-4">
                                <label for="father_dob" class="form-label fw-semibold text-secondary small">
                                    Father's Date of Birth
                                </label>
                                <input type="date" 
                                       name="father_dob" 
                                       id="father_dob" 
                                       class="form-control" 
                                       max="<?= date('Y-m-d') ?>" 
                                       value="<?= h($fatherDob) ?>">
                            </div>

                            <!-- Father's Age (Auto-calculated) -->
                            <div class="col-12 col-sm-6 col-md-3">
                                <label for="father_age" class="form-label fw-semibold text-secondary small">
                                    Father's Age
                                </label>
                                <div class="input-group">
                                    <input type="number" 
                                           id="father_age" 
                                           class="form-control bg-light" 
                                           placeholder="Auto" 
                                           value="<?= h($fatherAge) ?>"
                                           readonly>
                                    <span class="input-group-text bg-light text-muted small">yrs</span>
                                </div>
                            </div>

                            <!-- Maternal CPAB TT Status -->
                            <div class="col-12">
                                <label for="mother_cpab_tt" class="form-label fw-semibold text-secondary small">
                                    CPAB (TT given to Mother)
                                </label>
                                <input type="text" 
                                       name="mother_cpab_tt" 
                                       id="mother_cpab_tt" 
                                       class="form-control" 
                                       placeholder="e.g. Protected at Birth (TT2 given in 2025)" 
                                       value="<?= h($wellbabyRecord['mother_cpab_tt'] ?? 'Protected at Birth') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="card-footer bg-light py-3 px-4 border-0 d-flex justify-content-between align-items-center">
                        <a href="<?= url('/well-baby/' . $patient['id']) ?>" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-success text-white px-4 py-2 fw-semibold shadow-sm">
                            <i class="bi bi-save me-1"></i> Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.shadow-xs {
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
</style>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const motherNameInput = document.getElementById('mother_name');
    const motherDobInput = document.getElementById('mother_dob');
    const form = document.getElementById('editWellbabyForm');

    // --- Smart Mother Linker Handlers ---
    const motherSearchInput = document.getElementById('motherSearchInput');
    const motherSearchWrapper = document.getElementById('motherSearchWrapper');
    const motherSearchResults = document.getElementById('motherSearchResults');
    const motherPatientIdInput = document.getElementById('mother_patient_id');
    const linkedMotherBadge = document.getElementById('linkedMotherBadge');
    const motherSelectedCard = document.getElementById('motherSelectedCard');
    const linkedMotherName = document.getElementById('linkedMotherName');
    const linkedMotherDetails = document.getElementById('linkedMotherDetails');
    const btnClearMotherSearch = document.getElementById('btnClearMotherSearch');
    const btnRemoveMotherLink = document.getElementById('btnRemoveMotherLink');

    let motherSearchTimer = null;

    if (motherSearchInput) {
        motherSearchInput.addEventListener('input', function() {
            clearTimeout(motherSearchTimer);
            const query = this.value.trim();

            if (btnClearMotherSearch) {
                btnClearMotherSearch.classList.toggle('d-none', query.length === 0);
            }

            if (query.length < 2) {
                motherSearchResults.classList.add('d-none');
                motherSearchResults.innerHTML = '';
                return;
            }

            motherSearchTimer = setTimeout(() => {
                fetch('<?= url('/api/patients/search/female?q=') ?>' + encodeURIComponent(query))
                    .then(res => res.json())
                    .then(data => {
                        motherSearchResults.innerHTML = '';
                        if (!data.results || data.results.length === 0) {
                            motherSearchResults.innerHTML = '<div class="p-3 text-muted text-center small"><i class="bi bi-info-circle me-1"></i> No registered female patients found matching "' + escapeHtml(query) + '"</div>';
                            motherSearchResults.classList.remove('d-none');
                            return;
                        }

                        data.results.forEach(mom => {
                            const item = document.createElement('a');
                            item.href = '#';
                            item.className = 'list-group-item list-group-item-action p-2 px-3 d-flex align-items-center justify-content-between small';
                            item.innerHTML = `
                                <div>
                                    <span class="fw-bold text-dark">${escapeHtml(mom.name)}</span>
                                    <span class="badge bg-light text-secondary border font-monospace ms-1">${escapeHtml(mom.patient_no)}</span>
                                    <div class="text-muted" style="font-size: 0.72rem;">
                                        ${mom.dob_formatted ? mom.dob_formatted : 'DOB N/A'} • ${mom.age ? mom.age + ' yrs' : ''} • Brgy. ${escapeHtml(mom.address || 'Sinalhan')}
                                    </div>
                                </div>
                                <span class="btn btn-sm btn-outline-success py-0 px-2.5 fs-7 rounded-pill">Select &amp; Link</span>
                            `;
                            item.addEventListener('click', function(e) {
                                e.preventDefault();
                                linkMother(mom);
                            });
                            motherSearchResults.appendChild(item);
                        });

                        motherSearchResults.classList.remove('d-none');
                    })
                    .catch(() => {
                        motherSearchResults.classList.add('d-none');
                    });
            }, 300);
        });
    }

    if (btnClearMotherSearch) {
        btnClearMotherSearch.addEventListener('click', function() {
            if (motherSearchInput) {
                motherSearchInput.value = '';
                motherSearchInput.focus();
            }
            btnClearMotherSearch.classList.add('d-none');
            motherSearchResults.classList.add('d-none');
            motherSearchResults.innerHTML = '';
        });
    }

    function linkMother(mom) {
        if (motherPatientIdInput) motherPatientIdInput.value = mom.id;
        if (linkedMotherName) linkedMotherName.textContent = mom.name + ' (' + mom.patient_no + ')';
        if (linkedMotherDetails) linkedMotherDetails.textContent = (mom.dob_formatted || '') + ' • ' + (mom.age ? mom.age + ' years old' : '') + ' • ' + (mom.address || '');

        if (motherSelectedCard) motherSelectedCard.classList.remove('d-none');
        if (motherSearchWrapper) motherSearchWrapper.classList.add('d-none');
        if (motherSearchResults) motherSearchResults.classList.add('d-none');
        if (linkedMotherBadge) {
            linkedMotherBadge.className = 'badge bg-success-subtle text-success border border-success-subtle';
            linkedMotherBadge.textContent = 'Linked: Patient #' + mom.patient_no;
        }

        // Auto-fill mother's text fields directly
        if (mom.first_name || mom.last_name) {
            const formattedName = (mom.first_name + ' ' + (mom.middle_name ? mom.middle_name + ' ' : '') + mom.last_name).trim();
            if (motherNameInput) motherNameInput.value = formattedName;
        }
        if (mom.dob && motherDobInput) {
            motherDobInput.value = mom.dob;
        }
        updateParentAges();
    }

    function unlinkMother() {
        if (motherPatientIdInput) motherPatientIdInput.value = '';
        if (motherSelectedCard) motherSelectedCard.classList.add('d-none');
        if (motherSearchWrapper) motherSearchWrapper.classList.remove('d-none');
        if (motherSearchInput) motherSearchInput.value = '';
        if (btnClearMotherSearch) btnClearMotherSearch.classList.add('d-none');
        if (motherSearchResults) {
            motherSearchResults.classList.add('d-none');
            motherSearchResults.innerHTML = '';
        }
        if (linkedMotherBadge) {
            linkedMotherBadge.className = 'badge bg-secondary-subtle text-secondary';
            linkedMotherBadge.textContent = 'Not Linked';
        }
    }

    if (btnRemoveMotherLink) {
        btnRemoveMotherLink.addEventListener('click', unlinkMother);
    }

    // Parental Age Real-time Auto-calculation
    const fatherDobInput = document.getElementById('father_dob');
    function calculateAgeYears(dobStr) {
        if (!dobStr) return '';
        const parts = dobStr.split('-');
        if (parts.length !== 3) return '';
        const birthYear = parseInt(parts[0], 10);
        const birthMonth = parseInt(parts[1], 10) - 1;
        const birthDay = parseInt(parts[2], 10);
        if (isNaN(birthYear) || isNaN(birthMonth) || isNaN(birthDay)) return '';

        const today = new Date();
        let age = today.getFullYear() - birthYear;
        const m = today.getMonth() - birthMonth;
        if (m < 0 || (m === 0 && today.getDate() < birthDay)) {
            age--;
        }
        return age >= 0 ? age : 0;
    }

    function updateParentAges() {
        const motherAgeEl = document.getElementById('mother_age');
        const fatherAgeEl = document.getElementById('father_age');
        if (motherAgeEl && motherDobInput) {
            motherAgeEl.value = calculateAgeYears(motherDobInput.value);
        }
        if (fatherAgeEl && fatherDobInput) {
            fatherAgeEl.value = calculateAgeYears(fatherDobInput.value);
        }
    }

    if (motherDobInput) {
        motherDobInput.addEventListener('input', updateParentAges);
        motherDobInput.addEventListener('change', updateParentAges);
    }
    if (fatherDobInput) {
        fatherDobInput.addEventListener('input', updateParentAges);
        fatherDobInput.addEventListener('change', updateParentAges);
    }

    // Dynamic dropdowns for Place of Delivery & Attended By
    function updateDynamicDropdowns() {
        const placeSelect = document.getElementById('place_of_delivery');
        const placeOtherWrapper = document.getElementById('place_of_delivery_other_wrapper');
        const placeOtherInput = document.getElementById('place_of_delivery_other');
        if (placeSelect && placeOtherWrapper && placeOtherInput) {
            if (placeSelect.value === 'Others') {
                placeOtherWrapper.classList.remove('d-none');
                placeOtherInput.setAttribute('required', 'required');
            } else {
                placeOtherWrapper.classList.add('d-none');
                placeOtherInput.removeAttribute('required');
                placeOtherInput.value = '';
            }
        }

        const attendedSelect = document.getElementById('attended_by');
        const attendedOtherWrapper = document.getElementById('attended_by_other_wrapper');
        const attendedOtherInput = document.getElementById('attended_by_other');
        if (attendedSelect && attendedOtherWrapper && attendedOtherInput) {
            if (attendedSelect.value === 'Others') {
                attendedOtherWrapper.classList.remove('d-none');
                attendedOtherInput.setAttribute('required', 'required');
            } else {
                attendedOtherWrapper.classList.add('d-none');
                attendedOtherInput.removeAttribute('required');
                attendedOtherInput.value = '';
            }
        }
    }

    const placeSelect = document.getElementById('place_of_delivery');
    if (placeSelect) {
        placeSelect.addEventListener('change', updateDynamicDropdowns);
    }
    const attendedSelect = document.getElementById('attended_by');
    if (attendedSelect) {
        attendedSelect.addEventListener('change', updateDynamicDropdowns);
    }

    // Newborn Screening Checkbox Toggle (Strictly disable inputs when unchecked)
    const nbsCheckbox = document.getElementById('newborn_screening_done');
    const nbsDate = document.getElementById('newborn_screening_date');
    const nbsResult = document.getElementById('newborn_screening_result');

    function updateNbsState() {
        if (!nbsCheckbox || !nbsDate || !nbsResult) return;
        if (nbsCheckbox.checked) {
            nbsDate.disabled = false;
            nbsResult.disabled = false;
            nbsDate.setAttribute('required', 'required');
            if (!nbsDate.value) {
                nbsDate.value = new Date().toISOString().split('T')[0];
            }
            if (!nbsResult.value) {
                nbsResult.value = 'Normal';
            }
        } else {
            nbsDate.disabled = true;
            nbsResult.disabled = true;
            nbsDate.removeAttribute('required');
        }
    }

    if (nbsCheckbox) {
        nbsCheckbox.addEventListener('change', updateNbsState);
    }

    // Initialize states on page load
    updateDynamicDropdowns();
    updateNbsState();
    updateParentAges();

    // Client-side form validation
    if (form) {
        form.addEventListener('submit', function(e) {
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

            const placeVal = placeSelect ? placeSelect.value : '';
            const placeOther = document.getElementById('place_of_delivery_other');
            if (placeVal === 'Others' && placeOther && !placeOther.value.trim()) {
                e.preventDefault();
                alert('Please specify the Place of Delivery.');
                placeOther.focus();
                return;
            }

            const attendedVal = attendedSelect ? attendedSelect.value : '';
            const attendedOther = document.getElementById('attended_by_other');
            if (attendedVal === 'Others' && attendedOther && !attendedOther.value.trim()) {
                e.preventDefault();
                alert('Please specify who attended the birth.');
                attendedOther.focus();
                return;
            }
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
});
</script>

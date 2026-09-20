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
    'Well-Baby / EPI' => '/well-baby',
    $fullNameFormatted => '/well-baby/' . $patient['id'],
    'Edit Birth Record' => null
];
require dirname(__DIR__) . '/layout/header.php';

$addrParts = array_filter([$patient['address'] ?? '', $patient['barangay'] ?? '', 'Santa Rosa, Laguna']);
$fullAddress = implode(', ', $addrParts) ?: 'Barangay Sinalhan, Santa Rosa, Laguna';

$initials = '';
if (!empty($firstName)) $initials .= mb_substr($firstName, 0, 1);
if (!empty($lastName)) $initials .= mb_substr($lastName, 0, 1);
$initials = strtoupper($initials ?: 'WB');

// Preloaded Parental Info
$motherName = $patient['mother_name'] ?? '';
$motherDob = $patient['mother_dob'] ?? '';
$fatherName = $patient['father_name'] ?? '';
$fatherDob = $patient['father_dob'] ?? '';

$motherLinkedId = $wellbabyRecord['mother_patient_id'] ?? '';
$motherLinkedName = '';
if (!empty($wellbabyRecord['mother_first_name']) || !empty($wellbabyRecord['mother_last_name'])) {
    $motherLinkedName = trim(($wellbabyRecord['mother_last_name'] ?? '') . ', ' . ($wellbabyRecord['mother_first_name'] ?? ''));
}
$motherLinkedPatientNo = $wellbabyRecord['mother_patient_no'] ?? '';
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="h3 mb-1 fw-bold text-dark">
                <i class="bi bi-pencil-square text-success me-2"></i>Edit Well-Baby Birth Record
            </h2>
            <p class="text-secondary small mb-0">Update infant birth circumstances, maternal link, newborn screening certificate, and parental information.</p>
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
                        <i class="bi bi-person-badge text-success me-2"></i>Infant / Child Demographic Profile
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
                                        Brgy. <?= h($patient['barangay'] ?? 'Sinalhan') ?> &bull; Contact: <?= h($patient['contact_no'] ?? 'N/A') ?>
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
                                        <?php 
                                            if (!empty($patient['dob'])) {
                                                $dobTime = new \DateTime($patient['dob']);
                                                $nowTime = new \DateTime();
                                                $diff = $nowTime->diff($dobTime);
                                                $m = ($diff->y * 12) + $diff->m;
                                                echo $m < 12 ? "{$m} mos old" : "{$diff->y} yr " . ($diff->m ? "{$diff->m} mos" : "");
                                            } else {
                                                echo h($patient['age'] ?? '--') . ' yrs';
                                            }
                                        ?>
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

                <!-- Section 1: Parental Information & Maternal Link -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center">
                        <span class="badge bg-success rounded-circle p-2 me-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">1</span>
                        <h5 class="card-title h6 fw-bold mb-0 text-dark">Parental Information &amp; Maternal Link</h5>
                    </div>
                    <div class="card-body p-4 bg-white">
                        <div class="alert alert-light border small mb-4">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-info-circle text-primary fs-5 me-2 mt-n1"></i>
                                <div>
                                    <strong>Parental Details &amp; Central Registry Integration:</strong>
                                    Update parent names and dates of birth. Linking a registered mother associates this child with her existing patient file and maternal care history.
                                </div>
                            </div>
                        </div>

                        <!-- Link Registered Mother Autocomplete / Selector -->
                        <div class="mb-4 p-3 bg-light rounded-3 border">
                            <label for="motherSearchInput" class="form-label fw-semibold text-secondary small d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-link-45deg me-1"></i>Link Registered Mother Profile (Optional)</span>
                                <span class="badge <?= !empty($motherLinkedId) ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary' ?>" id="linkedMotherBadge">
                                    <?= !empty($motherLinkedId) ? 'Linked to Patient #' . h($motherLinkedPatientNo) : 'Not Linked' ?>
                                </span>
                            </label>

                            <input type="hidden" name="mother_patient_id" id="mother_patient_id" value="<?= h($motherLinkedId) ?>">

                            <!-- Search Input for Mother -->
                            <div class="input-group mb-2 <?= !empty($motherLinkedId) ? 'd-none' : '' ?>" id="motherSearchWrapper">
                                <span class="input-group-text bg-white text-muted"><i class="bi bi-person-search"></i></span>
                                <input type="text" 
                                       id="motherSearchInput" 
                                       class="form-control bg-white" 
                                       placeholder="Type mother's name or patient ID to link..." 
                                       autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary d-none" id="btnClearMotherLink">
                                    <i class="bi bi-x-circle me-1"></i> Clear
                                </button>
                            </div>

                            <!-- Mother Live Search Results -->
                            <div id="motherSearchResults" class="list-group shadow-sm rounded-3 border mb-2 d-none" style="max-height: 200px; overflow-y: auto;"></div>

                            <!-- Mother Selected Card -->
                            <div id="motherSelectedCard" class="<?= empty($motherLinkedId) ? 'd-none' : '' ?> p-2 px-3 bg-white rounded border border-success-subtle d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-person-check-fill text-success fs-5"></i>
                                    <div>
                                        <div class="fw-bold text-dark small" id="linkedMotherName">
                                            <?= !empty($motherLinkedName) ? h($motherLinkedName) . ' (' . h($motherLinkedPatientNo) . ')' : '--' ?>
                                        </div>
                                        <div class="text-muted" style="font-size: 0.75rem;" id="linkedMotherDetails">
                                            <?= !empty($wellbabyRecord['mother_age']) ? h($wellbabyRecord['mother_age']) . ' years old' : '' ?>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-link text-danger text-decoration-none p-0" id="btnRemoveMotherLink">
                                    <i class="bi bi-trash"></i> Remove Link
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
                                       value="<?= h($motherName) ?>"
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
                                       value="<?= h($motherDob) ?>">
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
                                       value="<?= h($fatherName) ?>"
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
                                       value="<?= h($fatherDob) ?>">
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
                                       value="<?= h($wellbabyRecord['mother_cpab_tt'] ?? 'Protected at Birth') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Birth Circumstances & Newborn Screening -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center">
                        <span class="badge bg-success rounded-circle p-2 me-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">2</span>
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
                                           class="form-control font-monospace fw-bold" 
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
                                           class="form-control font-monospace fw-bold" 
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
                                    Place of Delivery
                                </label>
                                <?php $pod = $wellbabyRecord['place_of_delivery'] ?? 'Lying-in Clinic'; ?>
                                <select name="place_of_delivery" id="place_of_delivery" class="form-select">
                                    <option value="Lying-in Clinic" <?= ($pod === 'Lying-in Clinic' || $pod === 'Lying-in') ? 'selected' : '' ?>>Lying-in Clinic</option>
                                    <option value="Hospital (SRCH / Public)" <?= ($pod === 'Hospital (SRCH / Public)' || $pod === 'Hospital') ? 'selected' : '' ?>>Hospital (SRCH / Public)</option>
                                    <option value="Hospital (Private)" <?= ($pod === 'Hospital (Private)') ? 'selected' : '' ?>>Hospital (Private)</option>
                                    <option value="Barangay Health Station (BHS)" <?= ($pod === 'Barangay Health Station (BHS)') ? 'selected' : '' ?>>Barangay Health Station (BHS)</option>
                                    <option value="Home" <?= ($pod === 'Home') ? 'selected' : '' ?>>Home</option>
                                    <option value="Other" <?= ($pod === 'Other') ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>

                            <!-- Delivery Type -->
                            <div class="col-12 col-sm-6 col-md-4">
                                <label for="delivery_type" class="form-label fw-semibold text-secondary small">
                                    Delivery Type
                                </label>
                                <?php $dt = $wellbabyRecord['delivery_type'] ?? 'Normal Spontaneous Delivery (NSD)'; ?>
                                <select name="delivery_type" id="delivery_type" class="form-select">
                                    <option value="Normal Spontaneous Delivery (NSD)" <?= ($dt === 'Normal Spontaneous Delivery (NSD)' || $dt === 'Normal Spontaneous') ? 'selected' : '' ?>>Normal Spontaneous Delivery (NSD)</option>
                                    <option value="Caesarean Section (CS)" <?= ($dt === 'Caesarean Section (CS)' || $dt === 'Cesarean Section (CS)') ? 'selected' : '' ?>>Caesarean Section (CS)</option>
                                    <option value="Vacuum Extraction" <?= ($dt === 'Vacuum Extraction' || $dt === 'Vacuum Assisted') ? 'selected' : '' ?>>Vacuum Extraction</option>
                                    <option value="Breech Delivery" <?= ($dt === 'Breech Delivery' || $dt === 'Breech Extraction') ? 'selected' : '' ?>>Breech Delivery</option>
                                    <option value="Other" <?= ($dt === 'Other') ? 'selected' : '' ?>>Other</option>
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
                                       value="<?= h($wellbabyRecord['attended_by'] ?? 'Midwife') ?>">
                            </div>

                            <!-- Feeding Method -->
                            <div class="col-12">
                                <label for="feeding_method" class="form-label fw-semibold text-secondary small">
                                    Initial Infant Feeding Practice
                                </label>
                                <?php $feed = $wellbabyRecord['feeding_method'] ?? 'LAM / Exclusive Breastfeeding'; ?>
                                <select name="feeding_method" id="feeding_method" class="form-select">
                                    <option value="LAM / Exclusive Breastfeeding" <?= ($feed === 'LAM / Exclusive Breastfeeding') ? 'selected' : '' ?>>LAM / Exclusive Breastfeeding</option>
                                    <option value="Bottle Feed" <?= ($feed === 'Bottle Feed') ? 'selected' : '' ?>>Bottle Feeding (Formula)</option>
                                    <option value="Mixed" <?= ($feed === 'Mixed') ? 'selected' : '' ?>>Mixed Feeding</option>
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
                                                   value="<?= h($wellbabyRecord['newborn_screening_date'] ?? '') ?>">
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
                                                   value="<?= h($wellbabyRecord['newborn_screening_result'] ?? 'Normal') ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="card-footer bg-light py-3 px-4 border-0 d-flex justify-content-between align-items-center">
                        <a href="<?= url('/well-baby/' . $patient['id']) ?>" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-success text-white px-4 py-2 fw-semibold shadow-sm">
                            <i class="bi bi-save me-1"></i> Save Birth Record Changes
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
    const motherSearchInput = document.getElementById('motherSearchInput');
    const motherSearchWrapper = document.getElementById('motherSearchWrapper');
    const motherResults = document.getElementById('motherSearchResults');
    const motherPatientId = document.getElementById('mother_patient_id');
    const linkedMotherBadge = document.getElementById('linkedMotherBadge');
    const motherSelectedCard = document.getElementById('motherSelectedCard');
    const linkedMotherName = document.getElementById('linkedMotherName');
    const linkedMotherDetails = document.getElementById('linkedMotherDetails');
    const btnRemoveMotherLink = document.getElementById('btnRemoveMotherLink');
    const btnClearMotherLink = document.getElementById('btnClearMotherLink');
    const motherNameInput = document.getElementById('mother_name');
    const motherDobInput = document.getElementById('mother_dob');
    const form = document.getElementById('editWellbabyForm');

    let motherSearchTimer = null;

    if (motherSearchInput) {
        motherSearchInput.addEventListener('input', function() {
            clearTimeout(motherSearchTimer);
            const query = this.value.trim();

            if (query.length < 2) {
                motherResults.classList.add('d-none');
                motherResults.innerHTML = '';
                if (btnClearMotherLink) btnClearMotherLink.classList.add('d-none');
                return;
            }

            if (btnClearMotherLink) btnClearMotherLink.classList.remove('d-none');

            motherSearchTimer = setTimeout(() => {
                fetch('<?= url('/api/patients/search/female?q=') ?>' + encodeURIComponent(query))
                    .then(res => res.json())
                    .then(data => {
                        motherResults.innerHTML = '';
                        if (!data.results || data.results.length === 0) {
                            motherResults.innerHTML = '<div class="p-3 text-muted text-center small">No registered female patients found matching "' + escapeHtml(query) + '"</div>';
                            motherResults.classList.remove('d-none');
                            return;
                        }

                        data.results.forEach(mom => {
                            const item = document.createElement('a');
                            item.href = '#';
                            item.className = 'list-group-item list-group-item-action p-2 d-flex align-items-center justify-content-between small';
                            item.innerHTML = `
                                <div>
                                    <span class="fw-bold text-dark">${escapeHtml(mom.name)}</span>
                                    <span class="badge bg-light text-secondary border font-monospace ms-1">${escapeHtml(mom.patient_no)}</span>
                                    <div class="text-muted" style="font-size: 0.72rem;">
                                        ${mom.dob_formatted ? mom.dob_formatted : 'DOB N/A'} • ${mom.age ? mom.age + ' yrs' : ''} • Brgy. ${escapeHtml(mom.address || 'Sinalhan')}
                                    </div>
                                </div>
                                <span class="btn btn-sm btn-outline-success py-0 px-2 fs-7">Select</span>
                            `;
                            item.addEventListener('click', function(e) {
                                e.preventDefault();
                                linkMother(mom);
                            });
                            motherResults.appendChild(item);
                        });

                        motherResults.classList.remove('d-none');
                    })
                    .catch(() => {
                        motherResults.classList.add('d-none');
                    });
            }, 300);
        });
    }

    function linkMother(mom) {
        motherPatientId.value = mom.id;
        linkedMotherName.textContent = mom.name + ' (' + mom.patient_no + ')';
        linkedMotherDetails.textContent = (mom.dob_formatted || '') + ' • ' + (mom.age ? mom.age + ' years old' : '') + ' • ' + (mom.address || '');
        
        motherSelectedCard.classList.remove('d-none');
        motherSearchWrapper.classList.add('d-none');
        motherResults.classList.add('d-none');
        linkedMotherBadge.className = 'badge bg-success-subtle text-success border border-success-subtle';
        linkedMotherBadge.textContent = 'Linked to Patient #' + mom.patient_no;

        // Auto-populate mother text fields if empty or confirm update
        if (mom.first_name || mom.last_name) {
            motherNameInput.value = (mom.first_name + ' ' + mom.last_name).trim();
        }
        if (mom.dob) {
            motherDobInput.value = mom.dob;
        }
    }

    function unlinkMother() {
        motherPatientId.value = '';
        motherSelectedCard.classList.add('d-none');
        motherSearchWrapper.classList.remove('d-none');
        if (motherSearchInput) motherSearchInput.value = '';
        linkedMotherBadge.className = 'badge bg-secondary-subtle text-secondary';
        linkedMotherBadge.textContent = 'Not Linked';
    }

    if (btnRemoveMotherLink) {
        btnRemoveMotherLink.addEventListener('click', unlinkMother);
    }
    if (btnClearMotherLink) {
        btnClearMotherLink.addEventListener('click', unlinkMother);
    }

    // Newborn Screening Checkbox Toggle
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

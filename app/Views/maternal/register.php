<?php
/**
 * Dedicated Maternal Pregnancy Registration View
 * 
 * @var array|null $preselectedPatient Pre-selected female patient record (optional)
 * @var array|null $preselectedIhp Baseline IHP record (optional)
 * @var bool $hasActiveEpisode Whether preselected patient already has active pregnancy
 */

$title = 'Register Maternal Pregnancy Episode';
$breadcrumbs = [
    'Maternal Care' => '/maternal',
    'Register Pregnancy' => null
];
require dirname(__DIR__) . '/layout/header.php';

$initialPatientId = $preselectedPatient['id'] ?? '';
$initialHusband = $preselectedPatient['spouse_name'] ?? '';
$initialGravida = 1;
if (!empty($preselectedIhp['gravida'])) {
    $initialGravida = max(1, (int)$preselectedIhp['gravida'] + 1);
}
$initialPara = (int)($preselectedIhp['para'] ?? 0);
$initialTerm = (int)($preselectedIhp['term_births'] ?? 0);
$initialPreterm = (int)($preselectedIhp['preterm_births'] ?? 0);
$initialAbortion = (int)($preselectedIhp['abortions'] ?? 0);
$initialLiving = (int)($preselectedIhp['living_children'] ?? 0);
$isLockedPatient = !empty($_GET['patient_id']) && !empty($preselectedPatient);
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="h3 mb-1 fw-bold text-primary-dark">
                <i class="bi bi-heart-pulse-fill text-pink me-2"></i>Register Maternal Pregnancy Episode
            </h2>
            <p class="text-secondary small mb-0">Enroll an eligible female patient into the CHO I Maternal Health Care Program.</p>
        </div>
        <div>
            <a href="<?= !empty($initialPatientId) ? url('/maternal/' . $initialPatientId) : url('/maternal') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to <?= !empty($initialPatientId) ? 'Workstation' : 'Maternal Roster' ?>
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

            <!-- Active Episode Warning (if preselected has active) -->
            <div id="activeEpisodeWarning" class="alert alert-warning border-0 shadow-sm rounded-4 mb-4 <?= $hasActiveEpisode ? '' : 'd-none' ?>">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-circle-fill text-warning fs-3 me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-1 text-dark">Active Pregnancy Episode Already Exists</h6>
                            <p class="mb-0 small text-secondary" id="activeEpisodeWarningText">
                                This patient already has an active pregnancy episode in progress. You must conclude the current episode before registering a new one.
                            </p>
                        </div>
                    </div>
                    <a href="<?= !empty($initialPatientId) ? url('/maternal/' . $initialPatientId) : '#' ?>" id="btnViewExistingWorkstation" class="btn btn-warning btn-sm fw-semibold">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Open Workstation
                    </a>
                </div>
            </div>

            <!-- Main Registration Form -->
            <form action="<?= url('/maternal/register') ?>" method="POST" id="maternalRegistrationForm">
                <?= csrf_field() ?>
                <input type="hidden" name="patient_id" id="selectedPatientId" value="<?= h($initialPatientId) ?>">

                <!-- Step 1: Patient Selection & Identity Card -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                    <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-primary rounded-circle p-2 me-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">1</span>
                            <h5 class="card-title h6 fw-bold mb-0 text-dark">Patient Selection</h5>
                        </div>
                        <?php if ($isLockedPatient): ?>
                            <span class="badge bg-light text-secondary border px-3 py-2">
                                <i class="bi bi-lock-fill me-1 text-muted"></i> Patient Locked
                            </span>
                        <?php else: ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary <?= empty($preselectedPatient) ? 'd-none' : '' ?>" id="btnChangePatient">
                                <i class="bi bi-arrow-repeat me-1"></i> Change Patient
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-4 bg-white">
                        <!-- Patient Search Box -->
                        <div id="patientSearchSection" class="<?= !empty($preselectedPatient) ? 'd-none' : '' ?>">
                            <label for="patientSearchInput" class="form-label fw-semibold text-secondary small">
                                Search Female Patient <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-lg mb-2 shadow-xs">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                                <input type="text" 
                                       id="patientSearchInput" 
                                       class="form-control bg-light border-start-0 fs-6" 
                                       placeholder="Type patient name, patient ID (e.g. PAT-), or envelope #..." 
                                       autocomplete="off">
                                <button type="button" class="btn btn-light border text-muted px-3 d-none" id="btnClearSearch">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                            <div class="form-text small text-muted mb-3">
                                Only female patients registered in the Barangay Sinalhan database are eligible for Maternal Care enrollment.
                            </div>

                            <!-- Live Search Results Dropdown List -->
                            <div id="searchResultsContainer" class="list-group shadow-sm rounded-3 border mb-3 d-none" style="max-height: 280px; overflow-y: auto;">
                                <!-- Injected dynamically via JS -->
                            </div>
                            <div id="searchLoadingSpinner" class="text-center py-3 text-muted d-none">
                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                <span class="small">Searching active patients...</span>
                            </div>
                        </div>

                        <!-- Compact Patient Identity Card -->
                        <div id="patientIdentityCard" class="<?= empty($preselectedPatient) ? 'd-none' : '' ?>">
                            <div class="p-3 p-md-4 rounded-4" style="background-color: #fff0f5; border: 1px solid #fbcfe8;">
                                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-3 pb-3" style="border-bottom: 1px solid #fbcfe8;">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold fs-4 shadow-xs" style="width: 54px; height: 54px; background-color: #d63384; color: #fff;">
                                            <span id="cardInitials">
                                                <?php 
                                                    $init = '';
                                                    if (!empty($preselectedPatient['first_name'])) $init .= mb_substr($preselectedPatient['first_name'], 0, 1);
                                                    if (!empty($preselectedPatient['last_name'])) $init .= mb_substr($preselectedPatient['last_name'], 0, 1);
                                                    echo strtoupper($init ?: 'PT');
                                                ?>
                                            </span>
                                        </div>
                                        <div>
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                <h5 class="h6 fw-bold text-dark mb-0" id="cardFullName">
                                                    <?= !empty($preselectedPatient) ? h($preselectedPatient['last_name'] . ', ' . $preselectedPatient['first_name'] . ' ' . (!empty($preselectedPatient['middle_name']) ? mb_substr($preselectedPatient['middle_name'], 0, 1) . '.' : '')) : '--' ?>
                                                </h5>
                                                <span class="badge text-white" style="background-color: #d63384;" id="cardSex">Female</span>
                                                <span class="badge bg-light text-secondary border font-monospace" id="cardPatientNo">
                                                    <?= !empty($preselectedPatient['patient_no']) ? h($preselectedPatient['patient_no']) : '--' ?>
                                                </span>
                                                <span class="badge bg-warning-subtle text-dark border font-monospace <?= empty($preselectedPatient['envelope_no']) ? 'd-none' : '' ?>" id="cardEnvelopeBadge">
                                                    Env #<span id="cardEnvelopeNo"><?= h($preselectedPatient['envelope_no'] ?? '') ?></span>
                                                </span>
                                            </div>
                                            <small class="text-muted" id="cardCivilStatus">
                                                <?= !empty($preselectedPatient['civil_status']) ? h($preselectedPatient['civil_status']) : 'Single' ?> &bull; Contact: <span id="cardContact"><?= h($preselectedPatient['contact_no'] ?? 'N/A') ?></span>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="text-md-end">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill font-monospace small">
                                            <i class="bi bi-check-circle-fill me-1"></i> Patient Selected
                                        </span>
                                    </div>
                                </div>

                                <!-- Demographic Grid: Name, DOB, Age, Complete Address -->
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
                                                <?= !empty($preselectedPatient['age']) ? h($preselectedPatient['age']) . ' years old' : '--' ?>
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

                <!-- Step 2: Clinical Episode Information -->
                <div id="clinicalEpisodeSection" class="card border-0 shadow-sm rounded-4 mb-4 <?= empty($preselectedPatient) ? 'opacity-50 pointer-events-none' : '' ?>">
                    <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center">
                        <span class="badge bg-primary rounded-circle p-2 me-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">2</span>
                        <h5 class="card-title h6 fw-bold mb-0 text-dark">Pregnancy Episode Details (CHO I Record)</h5>
                    </div>

                    <div class="card-body p-4 bg-white">
                        <!-- Guidance Alert -->
                        <div class="alert alert-info border-0 rounded-3 small mb-4">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            Enter the patient's <strong>Last Menstrual Period (LMP)</strong>. The system will automatically compute the <strong>EDC (Due Date)</strong> via Naegele's Rule and provide live <strong>Age of Gestation (AOG)</strong>.
                        </div>

                        <div class="row g-3">
                            <!-- LMP Date Input -->
                            <div class="col-12 col-md-4">
                                <label for="lmp" class="form-label fw-semibold text-secondary small">
                                    Last Menstrual Period (LMP) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="bi bi-calendar-event"></i></span>
                                    <input type="date" 
                                           name="lmp" 
                                           id="lmp" 
                                           class="form-control" 
                                           max="<?= date('Y-m-d') ?>" 
                                           required>
                                </div>
                                <div class="form-text small">Cannot be a future date.</div>
                            </div>

                            <!-- Live Calculated AOG -->
                            <div class="col-12 col-sm-6 col-md-4">
                                <label class="form-label fw-semibold text-secondary small">Live Calculated AOG (Current)</label>
                                <div class="p-2 bg-light rounded border d-flex align-items-center justify-content-between" style="min-height: 42px;">
                                    <span class="fw-bold text-primary font-monospace" id="live_aog_display">-- weeks</span>
                                    <span class="badge bg-secondary-subtle text-secondary" id="live_trimester_badge">Pending LMP</span>
                                </div>
                                <div class="form-text small">Calculated from LMP to today.</div>
                            </div>

                            <!-- Live Calculated EDC -->
                            <div class="col-12 col-sm-6 col-md-4">
                                <label class="form-label fw-semibold text-secondary small">Auto-calculated EDC (Due Date)</label>
                                <div class="p-2 bg-light rounded border d-flex align-items-center justify-content-between" style="min-height: 42px;">
                                    <span class="fw-bold text-pink font-monospace" id="live_edc_display">Select LMP...</span>
                                    <span class="badge bg-pink text-white" style="font-size: 0.72rem;">Naegele's Rule</span>
                                </div>
                                <div class="form-text small">+1 year, -3 months, +7 days.</div>
                            </div>

                            <!-- Husband / Partner Name -->
                            <div class="col-12">
                                <label for="husband_name" class="form-label fw-semibold text-secondary small">Husband / Partner Name</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="bi bi-person"></i></span>
                                    <input type="text" 
                                           name="husband_name" 
                                           id="husband_name" 
                                           class="form-control" 
                                           placeholder="Full Name of Husband or Partner" 
                                           value="<?= h($initialHusband) ?>">
                                </div>
                            </div>

                            <div class="col-12 my-2">
                                <hr class="text-muted opacity-25">
                            </div>

                            <!-- Obstetric History Matrix (GTPAL) -->
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                    <label class="form-label fw-bold text-dark mb-0">Obstetric History (GTPAL Score)</label>
                                    <span class="badge bg-light text-secondary border">
                                        <i class="bi bi-pencil-square me-1"></i>Pre-filled from IHP &bull; Fully Editable
                                    </span>
                                </div>
                                <p class="text-muted small mb-3">
                                    Review and verify the patient's baseline obstetric profile. If the patient reports recent miscarriages or deliveries not logged in their original IHP, adjust the values below.
                                </p>

                                <div class="row g-2 text-center">
                                    <div class="col-4 col-sm-2">
                                        <label class="form-label small fw-semibold text-secondary mb-1">Gravida (G)</label>
                                        <input type="number" 
                                               name="gravida" 
                                               id="gravida" 
                                               class="form-control form-control-lg text-center font-monospace fw-bold text-pink" 
                                               value="<?= $initialGravida ?>" 
                                               min="1" 
                                               max="25" 
                                               required>
                                        <small class="text-muted" style="font-size: 0.7rem;">Total Pregnancies</small>
                                    </div>
                                    <div class="col-4 col-sm-2">
                                        <label class="form-label small fw-semibold text-secondary mb-1">Para (P)</label>
                                        <input type="number" 
                                               name="para" 
                                               id="para" 
                                               class="form-control form-control-lg text-center font-monospace fw-semibold" 
                                               value="<?= $initialPara ?>" 
                                               min="0" 
                                               max="25">
                                        <small class="text-muted" style="font-size: 0.7rem;">Total Deliveries</small>
                                    </div>
                                    <div class="col-4 col-sm-2">
                                        <label class="form-label small fw-semibold text-secondary mb-1">Term (T)</label>
                                        <input type="number" 
                                               name="term_births" 
                                               id="term_births" 
                                               class="form-control form-control-lg text-center font-monospace" 
                                               value="<?= $initialTerm ?>" 
                                               min="0" 
                                               max="25">
                                        <small class="text-muted" style="font-size: 0.7rem;">Full Term (&ge;37w)</small>
                                    </div>
                                    <div class="col-4 col-sm-2">
                                        <label class="form-label small fw-semibold text-secondary mb-1">Preterm (P)</label>
                                        <input type="number" 
                                               name="preterm_births" 
                                               id="preterm_births" 
                                               class="form-control form-control-lg text-center font-monospace" 
                                               value="<?= $initialPreterm ?>" 
                                               min="0" 
                                               max="25">
                                        <small class="text-muted" style="font-size: 0.7rem;">Preterm (&lt;37w)</small>
                                    </div>
                                    <div class="col-4 col-sm-2">
                                        <label class="form-label small fw-semibold text-secondary mb-1">Abortion (A)</label>
                                        <input type="number" 
                                               name="abortions" 
                                               id="abortions" 
                                               class="form-control form-control-lg text-center font-monospace" 
                                               value="<?= $initialAbortion ?>" 
                                               min="0" 
                                               max="25">
                                        <small class="text-muted" style="font-size: 0.7rem;">Miscarriages / Ab</small>
                                    </div>
                                    <div class="col-4 col-sm-2">
                                        <label class="form-label small fw-semibold text-secondary mb-1">Living (L)</label>
                                        <input type="number" 
                                               name="living_children" 
                                               id="living_children" 
                                               class="form-control form-control-lg text-center font-monospace fw-bold text-success" 
                                               value="<?= $initialLiving ?>" 
                                               min="0" 
                                               max="25">
                                        <small class="text-muted" style="font-size: 0.7rem;">Living Children</small>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 my-2">
                                <hr class="text-muted opacity-25">
                            </div>

                            <!-- Clinical Notes -->
                            <div class="col-12">
                                <label for="notes" class="form-label fw-semibold text-secondary small">Clinical Notes / Midwife Remarks (Optional)</label>
                                <textarea name="notes" 
                                          id="notes" 
                                          rows="3" 
                                          class="form-control" 
                                          placeholder="Enter any initial health observations, high-risk screening notes, or referral alerts..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Action Bar -->
                    <div class="card-footer bg-light py-3 px-4 border-0 d-flex justify-content-between align-items-center">
                        <a href="<?= url('/maternal') ?>" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-pink text-white px-4 py-2 fw-semibold shadow-sm" id="btnSubmitRegistration" <?= $hasActiveEpisode ? 'disabled' : '' ?>>
                            <i class="bi bi-check2-circle me-1"></i> Register Pregnancy Episode
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.pointer-events-none {
    pointer-events: none;
}
.bg-pink {
    background-color: #d63384 !important;
}
.text-pink {
    color: #d63384 !important;
}
.border-pink {
    border-color: #d63384 !important;
}
.shadow-xs {
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.patient-search-item {
    cursor: pointer;
    transition: background-color 0.15s ease;
}
.patient-search-item:hover {
    background-color: #f8f9fa;
}
</style>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('patientSearchInput');
    const clearSearchBtn = document.getElementById('btnClearSearch');
    const resultsContainer = document.getElementById('searchResultsContainer');
    const loadingSpinner = document.getElementById('searchLoadingSpinner');
    const searchSection = document.getElementById('patientSearchSection');
    const identityCard = document.getElementById('patientIdentityCard');
    const btnChangePatient = document.getElementById('btnChangePatient');
    const selectedPatientIdInput = document.getElementById('selectedPatientId');
    const clinicalSection = document.getElementById('clinicalEpisodeSection');
    const activeEpisodeWarning = document.getElementById('activeEpisodeWarning');
    const activeEpisodeWarningText = document.getElementById('activeEpisodeWarningText');
    const btnViewExistingWorkstation = document.getElementById('btnViewExistingWorkstation');
    const btnSubmit = document.getElementById('btnSubmitRegistration');

    // LMP & Dynamic AOG/EDC elements
    const lmpInput = document.getElementById('lmp');
    const liveAogDisplay = document.getElementById('live_aog_display');
    const liveTrimesterBadge = document.getElementById('live_trimester_badge');
    const liveEdcDisplay = document.getElementById('live_edc_display');

    // GTPAL Inputs
    const gravidaInput = document.getElementById('gravida');
    const paraInput = document.getElementById('para');
    const termInput = document.getElementById('term_births');
    const pretermInput = document.getElementById('preterm_births');
    const abortionInput = document.getElementById('abortions');
    const livingInput = document.getElementById('living_children');
    const husbandInput = document.getElementById('husband_name');

    let searchDebounceTimer = null;

    // 1. Live Patient Search Handler
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            if (clearSearchBtn) {
                clearSearchBtn.classList.toggle('d-none', query.length === 0);
            }

            clearTimeout(searchDebounceTimer);
            if (query.length < 1) {
                resultsContainer.classList.add('d-none');
                resultsContainer.innerHTML = '';
                return;
            }

            loadingSpinner.classList.remove('d-none');
            searchDebounceTimer = setTimeout(() => {
                fetch('<?= url('/api/patients/search/female') ?>?q=' + encodeURIComponent(query))
                    .then(response => response.json())
                    .then(data => {
                        loadingSpinner.classList.add('d-none');
                        renderSearchResults(data.results || []);
                    })
                    .catch(err => {
                        loadingSpinner.classList.add('d-none');
                        console.error('Error searching patients:', err);
                    });
            }, 250);
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

    function renderSearchResults(patients) {
        resultsContainer.innerHTML = '';
        if (patients.length === 0) {
            resultsContainer.innerHTML = `
                <div class="list-group-item text-center py-4 text-muted">
                    <i class="bi bi-person-x fs-3 d-block mb-1 text-secondary"></i>
                    No female patients found matching your search.
                </div>
            `;
            resultsContainer.classList.remove('d-none');
            return;
        }

        patients.forEach(p => {
            const item = document.createElement('div');
            item.className = 'list-group-item list-group-item-action p-3 patient-search-item';
            
            const activeBadge = p.has_active_episode 
                ? `<span class="badge bg-warning text-dark border"><i class="bi bi-heart-pulse me-1"></i>Active Episode</span>` 
                : `<span class="badge bg-success-subtle text-success border border-success-subtle">Eligible</span>`;

            item.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-bold text-dark fs-6">${escapeHtml(p.name)}</div>
                        <div class="small text-muted d-flex align-items-center gap-2 mt-1">
                            <span class="badge bg-light text-secondary border font-monospace">${escapeHtml(p.patient_no)}</span>
                            ${p.envelope_no ? `<span class="badge bg-warning-subtle text-dark border font-monospace">Env #${escapeHtml(p.envelope_no)}</span>` : ''}
                            <span>${p.age} yrs</span>
                            <span>&bull;</span>
                            <span class="text-truncate" style="max-width: 250px;">${escapeHtml(p.address)}</span>
                        </div>
                    </div>
                    <div class="text-end">
                        ${activeBadge}
                    </div>
                </div>
            `;

            item.addEventListener('click', () => selectPatient(p.id));
            resultsContainer.appendChild(item);
        });

        resultsContainer.classList.remove('d-none');
    }

    // 2. Select Patient and fetch detailed Maternal/IHP data
    function selectPatient(patientId) {
        resultsContainer.classList.add('d-none');
        resultsContainer.innerHTML = '';
        if (searchInput) searchInput.value = '';

        fetch('<?= url('/api/patients/') ?>' + patientId + '/maternal-data')
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    return;
                }

                const patient = data.patient;
                selectedPatientIdInput.value = patient.id;

                // Update Compact Patient Identity Card
                document.getElementById('cardFullName').textContent = patient.name;
                document.getElementById('cardPatientNo').textContent = patient.patient_no;
                document.getElementById('cardDob').textContent = patient.dob_formatted;
                document.getElementById('cardAge').textContent = patient.age + ' years old';
                document.getElementById('cardAddress').textContent = patient.address;
                document.getElementById('cardCivilStatus').innerHTML = `${escapeHtml(patient.civil_status)} &bull; Contact: <span>${escapeHtml(patient.contact_no)}</span>`;

                const envBadge = document.getElementById('cardEnvelopeBadge');
                const envNo = document.getElementById('cardEnvelopeNo');
                if (patient.envelope_no) {
                    envNo.textContent = patient.envelope_no;
                    envBadge.classList.remove('d-none');
                } else {
                    envBadge.classList.add('d-none');
                }

                const initials = ((patient.first_name ? patient.first_name[0] : '') + (patient.last_name ? patient.last_name[0] : '')).toUpperCase() || 'PT';
                document.getElementById('cardInitials').textContent = initials;

                // Show identity card, hide search section
                searchSection.classList.add('d-none');
                identityCard.classList.remove('d-none');
                btnChangePatient.classList.remove('d-none');

                // Check active episode status
                if (data.has_active_episode && data.active_episode) {
                    activeEpisodeWarningText.textContent = `This patient already has an active pregnancy episode (#${data.active_episode.id}) started on ${data.active_episode.created_at} with EDC ${data.active_episode.edc}. Conclude or manage the existing episode first.`;
                    btnViewExistingWorkstation.href = '<?= url('/maternal/') ?>' + patient.id;
                    activeEpisodeWarning.classList.remove('d-none');
                    btnSubmit.disabled = true;
                } else {
                    activeEpisodeWarning.classList.add('d-none');
                    btnSubmit.disabled = false;
                }

                // Unlock clinical section
                clinicalSection.classList.remove('opacity-50', 'pointer-events-none');

                // Pre-fill GTPAL from IHP
                if (data.ihp) {
                    gravidaInput.value = data.ihp.gravida || 1;
                    paraInput.value = data.ihp.para || 0;
                    termInput.value = data.ihp.term_births || 0;
                    pretermInput.value = data.ihp.preterm_births || 0;
                    abortionInput.value = data.ihp.abortions || 0;
                    livingInput.value = data.ihp.living_children || 0;
                }

                if (patient.spouse_name && !husbandInput.value) {
                    husbandInput.value = patient.spouse_name;
                }
            })
            .catch(err => {
                console.error('Error fetching maternal patient data:', err);
                alert('Failed to load patient information.');
            });
    }

    // Change Patient Button
    if (btnChangePatient) {
        btnChangePatient.addEventListener('click', function() {
            selectedPatientIdInput.value = '';
            identityCard.classList.add('d-none');
            searchSection.classList.remove('d-none');
            btnChangePatient.classList.add('d-none');
            activeEpisodeWarning.classList.add('d-none');
            clinicalSection.classList.add('opacity-50', 'pointer-events-none');
            btnSubmit.disabled = false;
            if (searchInput) searchInput.focus();
        });
    }

    // 3. Live LMP Calculation for EDC & AOG
    function updateCalculations() {
        if (!lmpInput || !lmpInput.value) {
            liveAogDisplay.textContent = '-- weeks';
            liveTrimesterBadge.textContent = 'Pending LMP';
            liveTrimesterBadge.className = 'badge bg-secondary-subtle text-secondary';
            liveEdcDisplay.textContent = 'Select LMP...';
            return;
        }

        const lmpDate = new Date(lmpInput.value);
        if (isNaN(lmpDate.getTime())) return;

        // Calculate EDC using Naegele's Rule: +1 year, -3 months, +7 days
        const edc = new Date(lmpDate);
        edc.setFullYear(edc.getFullYear() + 1);
        edc.setMonth(edc.getMonth() - 3);
        edc.setDate(edc.getDate() + 7);

        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        liveEdcDisplay.textContent = edc.toLocaleDateString('en-US', options);

        // Calculate Age of Gestation (AOG) from LMP to Today
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        lmpDate.setHours(0, 0, 0, 0);

        const diffTime = today - lmpDate;
        const totalDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));

        if (totalDays < 0) {
            liveAogDisplay.textContent = '0 weeks (Future)';
            liveTrimesterBadge.textContent = 'Invalid LMP';
            liveTrimesterBadge.className = 'badge bg-danger text-white';
            return;
        }

        const weeks = Math.floor(totalDays / 7);
        const days = totalDays % 7;
        liveAogDisplay.textContent = `${weeks}w ${days}d`;

        // Trimester Badge
        if (weeks >= 37) {
            liveTrimesterBadge.textContent = 'Term / Near Term';
            liveTrimesterBadge.className = 'badge bg-success text-white';
        } else if (weeks >= 28) {
            liveTrimesterBadge.textContent = '3rd Trimester';
            liveTrimesterBadge.className = 'badge bg-warning text-dark';
        } else if (weeks >= 14) {
            liveTrimesterBadge.textContent = '2nd Trimester';
            liveTrimesterBadge.className = 'badge bg-info text-white';
        } else {
            liveTrimesterBadge.textContent = '1st Trimester';
            liveTrimesterBadge.className = 'badge bg-primary text-white';
        }
    }

    if (lmpInput) {
        lmpInput.addEventListener('change', updateCalculations);
        lmpInput.addEventListener('input', updateCalculations);
        if (lmpInput.value) {
            updateCalculations();
        }
    }

    const regForm = document.getElementById('maternalRegistrationForm');
    if (regForm) {
        regForm.addEventListener('submit', function(e) {
            if (!selectedPatientIdInput || !selectedPatientIdInput.value) {
                e.preventDefault();
                alert('Please search and select an eligible female patient before submitting.');
                if (searchInput) searchInput.focus();
                return false;
            }
            if (!lmpInput || !lmpInput.value) {
                e.preventDefault();
                alert('Please provide a valid Last Menstrual Period (LMP).');
                if (lmpInput) lmpInput.focus();
                return false;
            }
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
</script>

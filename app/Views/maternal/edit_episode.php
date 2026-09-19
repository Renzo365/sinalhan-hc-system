<?php
/**
 * Dedicated Maternal Pregnancy Episode Edit View
 * 
 * @var array $episode The active pregnancy episode record
 * @var array $patient The female patient demographic record
 */

// Robust patient full name formatting
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

$title = 'Edit Pregnancy Episode: ' . $fullNameFormatted;
$breadcrumbs = [
    'Maternal Care' => '/maternal',
    $fullNameFormatted => '/maternal/' . $patient['id'],
    'Edit Episode' => null
];
require dirname(__DIR__) . '/layout/header.php';

$addrParts = array_filter([$patient['address'] ?? '', $patient['barangay'] ?? '', 'Santa Rosa, Laguna']);
$fullAddress = implode(', ', $addrParts) ?: 'Barangay Sinalhan, Santa Rosa, Laguna';

$initials = '';
if (!empty($firstName)) $initials .= mb_substr($firstName, 0, 1);
if (!empty($lastName)) $initials .= mb_substr($lastName, 0, 1);
$initials = strtoupper($initials ?: 'PT');
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="h3 mb-1 fw-bold text-primary-dark">
                <i class="bi bi-pencil-square text-pink me-2"></i>Edit Pregnancy Episode Details
            </h2>
            <p class="text-secondary small mb-0">Update gestational benchmarks, LMP, and obstetric history for Episode #<?= h($episode['id']) ?>.</p>
        </div>
        <div>
            <a href="<?= url('/maternal/' . $patient['id']) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Patient Workstation
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

            <!-- Compact Patient Identity Card (Locked) -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
                    <h5 class="card-title h6 fw-bold mb-0 text-dark">
                        <i class="bi bi-person-badge text-primary me-2"></i>Enrolled Mother Information
                    </h5>
                    <span class="badge bg-pink text-white font-monospace">Episode #<?= h($episode['id']) ?></span>
                </div>
                <div class="card-body p-4 bg-white">
                    <div class="p-3 p-md-4 rounded-4" style="background-color: #fff0f5; border: 1px solid #fbcfe8;">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-3 pb-3" style="border-bottom: 1px solid #fbcfe8;">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold fs-4 shadow-xs" style="width: 54px; height: 54px; background-color: #d63384; color: #fff;">
                                    <?= h($initials) ?>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <h5 class="h6 fw-bold text-dark mb-0"><?= h($fullNameFormatted) ?></h5>
                                        <span class="badge text-white" style="background-color: #d63384;">Female</span>
                                        <span class="badge bg-light text-secondary border font-monospace"><?= h($patient['patient_no']) ?></span>
                                        <?php if (!empty($patient['envelope_no'])): ?>
                                            <span class="badge bg-warning-subtle text-dark border font-monospace">Env #<?= h($patient['envelope_no']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted">
                                        <?= h($patient['civil_status'] ?? 'Single') ?> &bull; Contact: <?= h($patient['contact_no'] ?? 'N/A') ?>
                                    </small>
                                </div>
                            </div>
                            <div class="text-md-end">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill font-monospace small">
                                    <i class="bi bi-calendar-check me-1"></i> Enrolled: <?= date('M d, Y', strtotime($episode['created_at'])) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Demographic Grid: Name, DOB, Age, Complete Address -->
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
                                        <?= !empty($patient['patient_age']) ? h($patient['patient_age']) : (!empty($patient['dob']) ? date_diff(date_create($patient['dob']), date_create('today'))->y : '--') ?> years old
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
            <form action="<?= url('/prenatal/' . $episode['id'] . '/update') ?>" method="POST" id="editMaternalForm">
                <?= csrf_field() ?>

                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center">
                        <h5 class="card-title h6 fw-bold mb-0 text-dark">
                            <i class="bi bi-sliders text-pink me-2"></i>Clinical Episode Parameters (CHO I Record)
                        </h5>
                    </div>

                    <div class="card-body p-4 bg-white">
                        <div class="alert alert-info border-0 rounded-3 small mb-4">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            Modifying the <strong>Last Menstrual Period (LMP)</strong> will automatically recalculate the <strong>EDC (Due Date)</strong> via Naegele's Rule and update gestational age throughout the workstation.
                        </div>

                        <div class="row g-3">
                            <!-- LMP Date Input -->
                            <div class="col-12 col-md-4">
                                <label for="edit_lmp" class="form-label fw-semibold text-secondary small">
                                    Last Menstrual Period (LMP) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="bi bi-calendar-event"></i></span>
                                    <input type="date" 
                                           name="lmp" 
                                           id="edit_lmp" 
                                           class="form-control" 
                                           value="<?= h($episode['lmp']) ?>" 
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
                                    <span class="badge bg-secondary-subtle text-secondary" id="live_trimester_badge">Computing...</span>
                                </div>
                                <div class="form-text small">Calculated from LMP to today.</div>
                            </div>

                            <!-- Live Calculated EDC -->
                            <div class="col-12 col-sm-6 col-md-4">
                                <label class="form-label fw-semibold text-secondary small">Auto-calculated EDC (Due Date)</label>
                                <div class="p-2 bg-light rounded border d-flex align-items-center justify-content-between" style="min-height: 42px;">
                                    <span class="fw-bold text-pink font-monospace" id="live_edc_display">
                                        <?= !empty($episode['edc']) ? date('M d, Y', strtotime($episode['edc'])) : 'Select LMP...' ?>
                                    </span>
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
                                           value="<?= h($episode['husband_name'] ?? '') ?>">
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
                                        <i class="bi bi-pencil-square me-1"></i>Clinical Baseline
                                    </span>
                                </div>
                                <p class="text-muted small mb-3">
                                    Ensure that the obstetric score accurately reflects the patient's updated parity and past pregnancy outcomes.
                                </p>

                                <div class="row g-2 text-center">
                                    <div class="col-4 col-sm-2">
                                        <label class="form-label small fw-semibold text-secondary mb-1">Gravida (G)</label>
                                        <input type="number" 
                                               name="gravida" 
                                               id="gravida" 
                                               class="form-control form-control-lg text-center font-monospace fw-bold text-pink" 
                                               value="<?= (int)$episode['gravida'] ?>" 
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
                                               value="<?= (int)$episode['para'] ?>" 
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
                                               value="<?= (int)$episode['term_births'] ?>" 
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
                                               value="<?= (int)$episode['preterm_births'] ?>" 
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
                                               value="<?= (int)$episode['abortions'] ?>" 
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
                                               value="<?= (int)$episode['living_children'] ?>" 
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
                                          placeholder="Enter any updated clinical notes or observations..."><?= h($episode['notes'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Action Bar -->
                    <div class="card-footer bg-light py-3 px-4 border-0 d-flex justify-content-between align-items-center">
                        <a href="<?= url('/maternal/' . $patient['id']) ?>" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-pink text-white px-4 py-2 fw-semibold shadow-sm">
                            <i class="bi bi-save me-1"></i> Save Episode Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
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
</style>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const lmpInput = document.getElementById('edit_lmp');
    const liveAogDisplay = document.getElementById('live_aog_display');
    const liveTrimesterBadge = document.getElementById('live_trimester_badge');
    const liveEdcDisplay = document.getElementById('live_edc_display');

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
        updateCalculations(); // Run immediately on page load
    }
});
</script>

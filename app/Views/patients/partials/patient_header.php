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
                        <?php if (!empty($patient['family_no'])): ?>
                            <a href="<?= url('/patients?search=' . urlencode($patient['family_no'])) ?>" class="badge bg-info-subtle text-info-emphasis border border-info-subtle font-monospace fs-7 text-decoration-none" title="View household in directory">
                                <i class="bi bi-house-door-fill me-1"></i>Fam #<?= h($patient['family_no']) ?>
                            </a>
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
                    if (!empty($patient['philhealth_no'])) {
                        $demographicsItems[] = '<span>PHIC: <span class="font-monospace text-dark">' . h($patient['philhealth_no']) . '</span></span>';
                    }
                    ?>
                    <div class="d-flex flex-wrap align-items-center gap-2 small text-secondary">
                        <?= implode('<span class="text-muted">&bull;</span>', $demographicsItems) ?>
                    </div>
                    <div class="mt-1 small text-secondary d-flex align-items-center gap-1">
                        <i class="bi bi-geo-alt text-muted flex-shrink-0"></i>
                        <span><?= !empty(trim($patient['address'] ?? '')) ? h(trim($patient['address'])) : '<span class="text-muted fst-italic">No address recorded</span>' ?></span>
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
            <div class="d-flex flex-wrap align-items-center gap-2 flex-shrink-0 ms-auto ms-lg-0 no-print">
                <button type="button" class="btn btn-primary btn-sm d-flex align-items-center px-3 py-1.5 shadow-xs text-nowrap" data-bs-toggle="modal" data-bs-target="#enqueuePatientModal" title="Check-in Patient to Today's Queue">
                    <i class="bi bi-person-check-fill me-1"></i> Check-in to Queue
                </button>
                <a href="<?= url('/patients/' . $patient['id'] . '/edit') ?>" class="btn btn-outline-primary btn-sm d-flex align-items-center px-3 py-1.5 shadow-xs text-nowrap" title="Edit Patient Identity & Demographics">
                    <i class="bi bi-pencil-square me-1"></i> Edit Profile
                </a>
                <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm d-flex align-items-center px-3 py-1.5 shadow-xs text-nowrap" title="Print Patient Profile">
                    <i class="bi bi-printer me-1"></i> Print Chart
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

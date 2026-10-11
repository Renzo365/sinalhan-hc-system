<!-- ==========================================================================
   PAGE HEADER (Title, Subtitle & Action)
   ========================================================================== -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div class="min-w-0">
        <h1 class="h3 mb-1 fw-bold text-primary-dark" style="text-wrap: balance;">Clinical Care Workstation</h1>
        <p class="text-secondary small mb-0" style="text-wrap: pretty;">Manage check-ups, PhilHealth IHP profile, maternal/child care, vitals, and appointments.</p>
    </div>
    <a href="<?= url('/patients') ?>" class="btn btn-outline-secondary text-nowrap flex-shrink-0">
        <i class="bi bi-arrow-left me-1"></i> Back to Directory
    </a>
</div>

<!-- ==========================================================================
   PATIENT MASTER HEADER (Compact Universal Identity & Actions Banner)
   ========================================================================== -->
<div class="card card-premium shadow-sm border-0 mb-3 patient-master-card">
    <div class="card-body p-3 p-md-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
            
            <!-- Left: Avatar, Name, Demographics & Metadata Tags -->
            <div class="d-flex align-items-start align-items-sm-center gap-3 min-w-0 flex-grow-1 patient-header-identity">
                <div class="avatar-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center rounded-circle shadow-xs flex-shrink-0" style="width: 52px; height: 52px; font-size: 1.25rem;" aria-hidden="true">
                    <?php if (!empty($avatarInitials)): ?>
                        <?= h($avatarInitials) ?>
                    <?php else: ?>
                        <i class="bi bi-person-fill fs-3"></i>
                    <?php endif; ?>
                </div>

                <div class="min-w-0 flex-grow-1">
                    <!-- Primary Patient Name (Hero Title) -->
                    <h2 class="h5 fw-bold text-primary-dark mb-1.5 lh-sm" style="text-wrap: balance;">
                        <?php if ($fullNameFormatted === 'Unnamed Patient'): ?>
                            <span class="text-muted fst-italic">Unnamed Patient</span>
                        <?php else: ?>
                            <?= h($fullNameFormatted) ?>
                        <?php endif; ?>
                    </h2>

                    <!-- Patient Core Demographics -->
                    <?php
                    $demographicsItems = [];
                    $pediatricAgeStr = ($isChild && $isValidDob) ? calculate_pediatric_age($patient['dob']) : null;
                    if ($pediatricAgeStr !== null && $patientSex !== '') {
                        $demographicsItems[] = '<span><strong class="text-dark">' . h($pediatricAgeStr) . '</strong> &bull; ' . h($patientSex) . '</span>';
                    } elseif ($pediatricAgeStr !== null) {
                        $demographicsItems[] = '<span><strong class="text-dark">' . h($pediatricAgeStr) . '</strong></span>';
                    } elseif ($ageVal !== null && $patientSex !== '') {
                        $demographicsItems[] = '<span><strong class="text-dark">' . $ageVal . '</strong> yrs &bull; ' . h($patientSex) . '</span>';
                    } elseif ($ageVal !== null) {
                        $demographicsItems[] = '<span><strong class="text-dark">' . $ageVal . '</strong> yrs</span>';
                    } elseif ($patientSex !== '') {
                        $demographicsItems[] = '<span>' . h($patientSex) . '</span>';
                    }
                    $demographicsItems[] = '<span>DOB: <strong class="' . ($isValidDob ? 'text-dark font-monospace' : 'text-muted') . '" style="font-variant-numeric: tabular-nums;">' . h($dobFormatted) . '</strong></span>';
                    if ($hasBloodType) {
                        $demographicsItems[] = '<span>Blood: <strong class="text-danger">' . h($patient['blood_type']) . '</strong></span>';
                    } else {
                        $demographicsItems[] = '<span>Blood: <span class="text-muted">Unknown</span></span>';
                    }
                    ?>
                    <div class="d-flex flex-wrap align-items-center gap-2 small text-secondary mb-2" style="font-size: 0.85rem;">
                        <?= implode('<span class="text-muted" aria-hidden="true">&bull;</span>', $demographicsItems) ?>
                    </div>

                    <!-- Clean Muted Identifier Tags Row -->
                    <div class="patient-id-tags-row d-flex flex-wrap align-items-center gap-1.5">
                        <span class="patient-id-tag font-monospace" title="System Patient Number">
                            <span class="tag-label">ID</span>
                            <span class="tag-value"><?= h($patient['patient_no']) ?></span>
                        </span>

                        <?php if (!empty($patient['envelope_no'])): ?>
                            <span class="patient-id-tag font-monospace" title="Physical Logbook Envelope No.">
                                <span class="tag-label">Env</span>
                                <span class="tag-value">#<?= h($patient['envelope_no']) ?></span>
                            </span>
                        <?php endif; ?>

                        <?php if (!empty($patient['family_no'])): ?>
                            <a href="<?= url('/patients?search=' . urlencode($patient['family_no'])) ?>" class="patient-id-tag tag-interactive font-monospace" title="Click to view household in directory" aria-label="View household directory for Family <?= h($patient['family_no']) ?>">
                                <span class="tag-label">Fam</span>
                                <span class="tag-value">#<?= h($patient['family_no']) ?></span>
                                <i class="bi bi-box-arrow-up-right ms-0.5 text-muted" style="font-size: 0.65rem;" aria-hidden="true"></i>
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($activePrenatal)): ?>
                            <a href="<?= url('/prenatal/' . $patient['id']) ?>" class="patient-id-tag tag-status-pregnant" title="Active Pregnancy Episode - EDC: <?= !empty($activePrenatal['edc']) ? date('M d, Y', strtotime($activePrenatal['edc'])) : 'N/A' ?>">
                                <i class="bi bi-heart-pulse-fill me-0.5" aria-hidden="true"></i>
                                <span class="tag-label">Pregnancy</span>
                                <span class="tag-value"><?= !empty($activePrenatal['calculated_aog']['weeks']) ? $activePrenatal['calculated_aog']['weeks'] . 'w' : 'Active' ?></span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right: Patient-Level Action Controls -->
            <div class="patient-actions-group d-flex align-items-center gap-2 flex-shrink-0 ms-auto ms-lg-0 no-print">
                <button type="button" class="btn btn-primary btn-sm d-flex align-items-center px-3 py-2 shadow-xs text-nowrap" data-bs-toggle="modal" data-bs-target="#enqueuePatientModal" title="Check-in Patient to Today's Queue" style="min-height: 38px;">
                    <i class="bi bi-person-check-fill me-1.5" aria-hidden="true"></i> Check-in to Queue
                </button>
                <a href="<?= url('/patients/' . $patient['id'] . '/edit') ?>" class="btn btn-outline-primary btn-sm d-flex align-items-center px-3 py-2 shadow-xs text-nowrap" title="Edit Patient Identity & Demographics" style="min-height: 38px;">
                    <i class="bi bi-pencil-square me-1.5" aria-hidden="true"></i> Edit Patient Information
                </a>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm px-2.5 py-2 shadow-xs d-flex align-items-center justify-content-center" type="button" id="patientMoreActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false" aria-label="More patient actions" title="More Actions" style="min-height: 38px; min-width: 38px;">
                        <i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border py-1" aria-labelledby="patientMoreActionsDropdown">
                        <?php if (!empty($activePrenatal) || (strtolower($patient['sex'] ?? '') === 'female' && ($ageVal === null || ($ageVal >= 10 && $ageVal <= 55)))): ?>
                            <li>
                                <a href="<?= !empty($activePrenatal) ? url('/prenatal/' . $patient['id']) : url('/prenatal/register?patient_id=' . $patient['id']) ?>" class="dropdown-item small d-flex align-items-center py-2 text-danger">
                                    <i class="bi <?= !empty($activePrenatal) ? 'bi-heart-pulse-fill' : 'bi-plus-circle' ?> me-2 text-danger" aria-hidden="true"></i>
                                    <?= !empty($activePrenatal) ? 'Open Prenatal Workstation' : 'Enroll in Prenatal Care' ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if (!empty($wellbabyRecord) || $isChild): ?>
                            <li>
                                <a href="<?= !empty($wellbabyRecord) ? url('/well-baby/' . $patient['id']) : url('/well-baby/register?patient_id=' . $patient['id']) ?>" class="dropdown-item small d-flex align-items-center py-2 text-success">
                                    <i class="bi <?= !empty($wellbabyRecord) ? 'bi-emoji-smile-fill' : 'bi-plus-circle' ?> me-2 text-success" aria-hidden="true"></i>
                                    <?= !empty($wellbabyRecord) ? 'Open Well-Baby Workstation' : 'Enroll in Well-Baby Care' ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        <li>
                            <button type="button" onclick="window.print()" class="dropdown-item small d-flex align-items-center py-2">
                                <i class="bi bi-printer me-2 text-secondary" aria-hidden="true"></i> Print Chart
                            </button>
                        </li>
                        <?php if (is_admin()): ?>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <button type="button" class="dropdown-item small text-danger d-flex align-items-center py-2" data-bs-toggle="modal" data-bs-target="#archivePatientModal">
                                    <i class="bi bi-archive me-2 text-danger" aria-hidden="true"></i> Archive Patient
                                </button>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

        </div>

        <!-- Clinical Alerts Safety Bar (If Any Alerts Recorded) -->
        <?php if (!empty($cdsAlerts['has_alerts'])): ?>
            <div class="clinical-alerts-bar mt-3" role="region" aria-label="Patient Safety and Clinical Alerts">
                <span class="clinical-alerts-label">
                    <i class="bi bi-shield-exclamation text-danger" aria-hidden="true"></i>
                    <span>Clinical Alerts:</span>
                </span>
                <div class="d-flex flex-wrap align-items-center gap-1.5">
                    <?php foreach ($cdsAlerts['flags'] as $flag): ?>
                        <?php $isDangerAlert = ($flag['type'] === 'danger' || strpos($flag['code'] ?? '', 'ALLERGY') !== false); ?>
                        <span class="clinical-chip <?= $isDangerAlert ? 'clinical-chip-danger' : 'clinical-chip-neutral' ?>">
                            <i class="bi <?= $flag['icon'] ?> chip-icon" aria-hidden="true"></i>
                            <span><?= h($flag['label']) ?></span>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

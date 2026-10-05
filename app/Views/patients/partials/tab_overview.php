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
                                        <a href="<?= url('/patients/' . $patient['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary py-1 px-2" title="Edit Demographics">
                                            Edit
                                        </a>
                                    </div>
                                    <div class="card-body p-3 small">
                                        <div class="row g-2">
                                            <!-- Row 1: Residential Address (Full Width) -->
                                            <div class="col-12">
                                                <span class="overview-micro-label d-block">Residential Address</span>
                                                <span class="text-dark fw-medium">
                                                    <?= !empty(trim($patient['address'] ?? '')) ? h(trim($patient['address'])) : '<span class="text-muted fst-italic">No address recorded</span>' ?>
                                                </span>
                                            </div>

                                            <!-- Row 2: Primary Contact & Civil Status -->
                                            <div class="col-6 border-top pt-2 mt-1">
                                                <span class="overview-micro-label d-block">Primary Contact Number</span>
                                                <?php if (!empty($patient['contact_no'])): ?>
                                                    <div class="d-flex align-items-center">
                                                        <span class="font-monospace fw-semibold text-dark"><a href="tel:<?= h($patient['contact_no']) ?>" class="text-decoration-none text-dark"><?= h($patient['contact_no']) ?></a></span>
                                                        <button type="button" class="btn btn-link btn-xs p-0 text-secondary ms-1.5 copy-clipboard-btn" data-clipboard="<?= h($patient['contact_no']) ?>" title="Copy phone number" aria-label="Copy primary phone number">
                                                            <i class="bi bi-copy"></i>
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted fst-italic">None registered</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-6 border-top pt-2 mt-1">
                                                <span class="overview-micro-label d-block">Civil Status</span>
                                                <span class="fw-semibold text-dark">
                                                    <?= (($patient['civil_status'] ?? '') === 'Others' && !empty($patient['civil_status_other'])) ? 'Others (' . h($patient['civil_status_other']) . ')' : h($patient['civil_status'] ?? 'Unspecified') ?>
                                                </span>
                                            </div>

                                            <!-- Row 3: Religion & Education -->
                                            <div class="col-6 border-top pt-2 mt-1">
                                                <span class="overview-micro-label d-block">Religion</span>
                                                <span class="text-dark fw-medium"><?= h($patient['religion'] ?? 'Unspecified') ?></span>
                                            </div>
                                            <div class="col-6 border-top pt-2 mt-1">
                                                <span class="overview-micro-label d-block">Educational Attainment</span>
                                                <span class="text-dark fw-medium"><?= h($patient['education_attainment'] ?? 'Unspecified') ?></span>
                                            </div>

                                            <!-- Row 4: Occupation -->
                                            <div class="col-12 border-top pt-2 mt-1">
                                                <span class="overview-micro-label d-block">Occupation</span>
                                                <span class="text-dark fw-medium"><?= h($patient['occupation'] ?? 'Unspecified') ?></span>
                                            </div>

                                            <!-- PhilHealth Section Divider -->
                                            <div class="col-12 border-top pt-2 mt-1">
                                                <div class="d-flex align-items-center gap-1 text-primary small fw-bold">
                                                    PhilHealth Information
                                                </div>
                                            </div>

                                            <!-- Row 5: PhilHealth Status & PHIC Category / Type (Separated) -->
                                            <div class="col-6">
                                                <span class="overview-micro-label d-block">PhilHealth Status</span>
                                                <span class="badge <?= ($patient['phic_status'] ?? '') === 'Member' ? 'bg-success' : (($patient['phic_status'] ?? '') === 'Dependent' ? 'bg-info text-dark' : 'bg-secondary') ?>">
                                                    <?= h($patient['phic_status'] ?? 'Non-Member') ?>
                                                </span>
                                            </div>
                                            <div class="col-6">
                                                <span class="overview-micro-label d-block">PHIC Category / Type</span>
                                                <span class="text-dark fw-medium"><?= !empty($patient['phic_type']) ? h($patient['phic_type']) : '<span class="text-muted">None / Unspecified</span>' ?></span>
                                            </div>

                                            <!-- Row 6: PhilHealth Identification No. (PIN) (Separated) -->
                                            <div class="col-12 border-top pt-2 mt-1">
                                                <span class="overview-micro-label d-block">PhilHealth Identification No. (PIN)</span>
                                                <?php if (!empty($patient['philhealth_no'])): ?>
                                                    <div class="d-flex align-items-center">
                                                        <span class="font-monospace fw-semibold text-dark fs-7"><?= h($patient['philhealth_no']) ?></span>
                                                        <button type="button" class="btn btn-link btn-xs p-0 text-secondary ms-1.5 copy-clipboard-btn" data-clipboard="<?= h($patient['philhealth_no']) ?>" title="Copy PhilHealth PIN" aria-label="Copy PhilHealth PIN">
                                                            <i class="bi bi-copy"></i>
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted fst-italic">None on record</span>
                                                <?php endif; ?>
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
                                        <div class="d-flex align-items-center gap-2">
                                            <?php if (!empty($patient['family_no'])): ?>
                                                <span class="badge bg-light text-dark border">Fam # <?= h($patient['family_no']) ?></span>
                                            <?php endif; ?>
                                            <a href="<?= url('/patients/' . $patient['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary py-1 px-2" title="Edit Family & Emergency Contacts">
                                                Edit
                                            </a>
                                        </div>
                                    </div>
                                    <div class="card-body p-3 small">
                                        <div class="row g-2">
                                            <div class="col-12">
                                                <span class="overview-micro-label d-block">Father's Full Name</span>
                                                <span class="text-dark">
                                                    <?= !empty($patient['father_name']) ? h($patient['father_name']) . ($formatDateSafe($patient['father_dob'] ?? null) ? ' <span class="text-muted small">(DOB: ' . $formatDateSafe($patient['father_dob']) . ')</span>' : '') : '<span class="text-muted">None on record</span>' ?>
                                                </span>
                                            </div>
                                            <div class="col-12 border-top pt-2 mt-1">
                                                <span class="overview-micro-label d-block">Mother's Maiden Name</span>
                                                <span class="text-dark">
                                                    <?= !empty($patient['mother_name']) ? h($patient['mother_name']) . ($formatDateSafe($patient['mother_dob'] ?? null) ? ' <span class="text-muted small">(DOB: ' . $formatDateSafe($patient['mother_dob']) . ')</span>' : '') : '<span class="text-muted">None on record</span>' ?>
                                                </span>
                                            </div>
                                            <div class="col-12 border-top pt-2 mt-1">
                                                <span class="overview-micro-label d-block">Spouse's Full Name</span>
                                                <span class="text-dark">
                                                    <?= !empty($patient['spouse_name']) ? h($patient['spouse_name']) . ($formatDateSafe($patient['spouse_dob'] ?? null) ? ' <span class="text-muted small">(DOB: ' . $formatDateSafe($patient['spouse_dob']) . ')</span>' : '') : '<span class="text-muted">None on record</span>' ?>
                                                </span>
                                            </div>
                                            <div class="col-6 border-top pt-2 mt-1">
                                                <span class="overview-micro-label d-block">Emergency Contact</span>
                                                <span class="fw-semibold text-dark"><?= h($patient['emergency_name'] ?? 'None registered') ?></span>
                                            </div>
                                            <div class="col-6 border-top pt-2 mt-1">
                                                <span class="overview-micro-label d-block">Relationship</span>
                                                <span class="text-dark"><?= h($patient['emergency_relationship'] ?? 'N/A') ?></span>
                                            </div>
                                            <div class="col-12 border-top pt-2 mt-1">
                                                <span class="overview-micro-label d-block">Emergency Phone</span>
                                                <?php if (!empty($patient['emergency_no'])): ?>
                                                    <div class="d-flex align-items-center">
                                                        <span class="font-monospace fw-semibold text-dark"><a href="tel:<?= h($patient['emergency_no']) ?>" class="text-decoration-none text-dark"><?= h($patient['emergency_no']) ?></a></span>
                                                        <button type="button" class="btn btn-link btn-xs p-0 text-secondary ms-1.5 copy-clipboard-btn" data-clipboard="<?= h($patient['emergency_no']) ?>" title="Copy emergency phone" aria-label="Copy emergency phone">
                                                            <i class="bi bi-copy"></i>
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted fst-italic">None provided</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Latest Vital Signs Snapshot Card (Full Width) -->
                            <div class="col-12">
                                <div class="card border rounded-3 shadow-xs">
                                    <div class="card-header bg-light py-2 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <h5 class="h6 mb-0 fw-bold text-dark">
                                                <i class="bi bi-heart-pulse text-danger me-2"></i>Latest Vital Signs Snapshot
                                            </h5>
                                            <?php if ($latestVitals): ?>
                                                <?php
                                                $vitalsTime = strtotime($latestVitals['recorded_at']);
                                                $daysDiff = floor((time() - $vitalsTime) / 86400);
                                                if ($daysDiff <= 0) {
                                                    echo '<span class="badge bg-success-subtle text-success border border-success-subtle fw-medium">Recorded Today</span>';
                                                } elseif ($daysDiff == 1) {
                                                    echo '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-medium">Recorded Yesterday &bull; Retake Recommended for Today</span>';
                                                } elseif ($daysDiff <= 7) {
                                                    echo '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-medium">Recorded ' . $daysDiff . ' days ago &bull; Retake for Today</span>';
                                                } else {
                                                    echo '<span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle fw-medium">Recorded ' . $daysDiff . ' days ago &bull; Stale Vitals</span>';
                                                }
                                                ?>
                                            <?php endif; ?>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" data-bs-toggle="modal" data-bs-target="#addVitalsModal">
                                            + Record Vitals
                                        </button>
                                    </div>
                                    <div class="card-body p-3">
                                        <?php if ($latestVitals): ?>
                                            <?php
                                            $bpSys = !empty($latestVitals['bp_systolic']) ? (int)$latestVitals['bp_systolic'] : null;
                                            $bpDia = !empty($latestVitals['bp_diastolic']) ? (int)$latestVitals['bp_diastolic'] : null;
                                            $hr = !empty($latestVitals['heart_rate']) ? (int)$latestVitals['heart_rate'] : null;
                                            $temp = !empty($latestVitals['temperature']) ? (float)$latestVitals['temperature'] : null;
                                            $rr = !empty($latestVitals['respiratory_rate']) ? (int)$latestVitals['respiratory_rate'] : null;
                                            $spo2 = !empty($latestVitals['oxygen_saturation']) ? (int)$latestVitals['oxygen_saturation'] : null;
                                            $bmiVal = !empty($latestVitals['bmi']) ? (float)$latestVitals['bmi'] : null;

                                            // BP Classification
                                            $bpTileClass = 'bg-light text-dark';
                                            $bpBadge = ($bpSys !== null && $bpDia !== null) ? '<span class="text-muted" style="font-size: 0.7rem;">Normal</span>' : '<span class="text-muted" style="font-size: 0.7rem;">—</span>';
                                            if ($bpSys !== null && $bpDia !== null) {
                                                if ($bpSys >= 140 || $bpDia >= 90) {
                                                    $bpTileClass = 'bg-danger-subtle border-danger-subtle text-danger';
                                                    $bpBadge = '<span class="badge bg-danger text-white px-1">Hypertension</span>';
                                                } elseif (($bpSys >= 120 && $bpSys <= 139) || ($bpDia >= 80 && $bpDia <= 89)) {
                                                    $bpTileClass = 'bg-warning-subtle border-warning-subtle text-warning-emphasis';
                                                    $bpBadge = '<span class="badge bg-warning text-dark px-1">Pre-HTN</span>';
                                                } elseif (($bpSys > 0 && $bpSys < 90) || ($bpDia > 0 && $bpDia < 60)) {
                                                    $bpTileClass = 'bg-warning-subtle border-warning-subtle text-warning-emphasis';
                                                    $bpBadge = '<span class="badge bg-warning text-dark px-1">Hypotension</span>';
                                                }
                                            }

                                            // Heart Rate Classification
                                            $hrTileClass = 'bg-light text-dark';
                                            $hrBadge = ($hr !== null && $hr > 0) ? '<span class="text-muted" style="font-size: 0.7rem;">Normal (60-100)</span>' : '<span class="text-muted" style="font-size: 0.7rem;">—</span>';
                                            if ($hr !== null && $hr > 0) {
                                                if ($hr > 100) {
                                                    $hrTileClass = 'bg-warning-subtle border-warning-subtle text-warning-emphasis';
                                                    $hrBadge = '<span class="badge bg-warning text-dark px-1">Tachycardia</span>';
                                                } elseif ($hr < 60) {
                                                    $hrTileClass = 'bg-warning-subtle border-warning-subtle text-warning-emphasis';
                                                    $hrBadge = '<span class="badge bg-warning text-dark px-1">Bradycardia</span>';
                                                }
                                            }

                                            // Temperature Classification
                                            $tempTileClass = 'bg-light text-dark';
                                            $tempBadge = ($temp !== null && $temp > 0) ? '<span class="text-muted" style="font-size: 0.7rem;">Normal (36.5-37.5)</span>' : '<span class="text-muted" style="font-size: 0.7rem;">—</span>';
                                            if ($temp !== null && $temp > 0) {
                                                if ($temp >= 38.0) {
                                                    $tempTileClass = 'bg-danger-subtle border-danger-subtle text-danger';
                                                    $tempBadge = '<span class="badge bg-danger text-white px-1">Fever</span>';
                                                } elseif ($temp >= 37.6) {
                                                    $tempTileClass = 'bg-warning-subtle border-warning-subtle text-warning-emphasis';
                                                    $tempBadge = '<span class="badge bg-warning text-dark px-1">Low Fever</span>';
                                                } elseif ($temp < 35.5) {
                                                    $tempTileClass = 'bg-info-subtle border-info-subtle text-info-emphasis';
                                                    $tempBadge = '<span class="badge bg-info text-white px-1">Hypothermia</span>';
                                                }
                                            }

                                            // Respiratory Rate Classification
                                            $rrTileClass = 'bg-light text-dark';
                                            $rrBadge = ($rr !== null && $rr > 0) ? '<span class="text-muted" style="font-size: 0.7rem;">Normal (12-20)</span>' : '<span class="text-muted" style="font-size: 0.7rem;">—</span>';
                                            if ($rr !== null && $rr > 0) {
                                                if ($rr > 20) {
                                                    $rrTileClass = 'bg-warning-subtle border-warning-subtle text-warning-emphasis';
                                                    $rrBadge = '<span class="badge bg-warning text-dark px-1">Tachypnea</span>';
                                                } elseif ($rr < 12) {
                                                    $rrTileClass = 'bg-warning-subtle border-warning-subtle text-warning-emphasis';
                                                    $rrBadge = '<span class="badge bg-warning text-dark px-1">Bradypnea</span>';
                                                }
                                            }

                                            // Oxygen Saturation Classification
                                            $spo2TileClass = 'bg-light text-dark';
                                            $spo2Badge = ($spo2 !== null && $spo2 > 0) ? '<span class="text-muted" style="font-size: 0.7rem;">Normal (&ge;95%)</span>' : '<span class="text-muted" style="font-size: 0.7rem;">—</span>';
                                            if ($spo2 !== null && $spo2 > 0) {
                                                if ($spo2 < 95) {
                                                    $spo2TileClass = 'bg-danger-subtle border-danger-subtle text-danger';
                                                    $spo2Badge = '<span class="badge bg-danger text-white px-1">Hypoxia Risk</span>';
                                                }
                                            }

                                            // BMI Classification
                                            $bmiClassified = classify_bmi($bmiVal);
                                            $bmiTileClass = 'bg-light text-dark';
                                            if ($bmiVal !== null && $bmiVal > 0) {
                                                if ($bmiVal >= 27.5) {
                                                    $bmiTileClass = 'bg-danger-subtle border-danger-subtle text-danger';
                                                } elseif ($bmiVal >= 23.0) {
                                                    $bmiTileClass = 'bg-warning-subtle border-warning-subtle text-warning-emphasis';
                                                } elseif ($bmiVal < 18.5) {
                                                    $bmiTileClass = 'bg-info-subtle border-info-subtle text-info-emphasis';
                                                }
                                            }
                                            ?>

                                            <div class="row g-2 text-center mb-2">
                                                <!-- 1. BP -->
                                                <div class="col-6 col-sm-4 col-lg-3">
                                                    <div class="p-2 rounded border h-100 d-flex flex-column justify-content-between <?= $bpTileClass ?>">
                                                        <span class="overview-micro-label d-block">Blood Pressure</span>
                                                        <div class="my-1">
                                                            <span class="fw-bold fs-6"><?= h($latestVitals['bp_systolic'] ?? '--') ?>/<?= h($latestVitals['bp_diastolic'] ?? '--') ?></span>
                                                            <span class="text-muted small ms-1" style="font-size: 0.7rem;">mmHg</span>
                                                        </div>
                                                        <div style="min-height: 1.25rem;">
                                                            <?= $bpBadge ?>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- 2. Pulse / HR -->
                                                <div class="col-6 col-sm-4 col-lg-3">
                                                    <div class="p-2 rounded border h-100 d-flex flex-column justify-content-between <?= $hrTileClass ?>">
                                                        <span class="overview-micro-label d-block">Heart Rate</span>
                                                        <div class="my-1">
                                                            <span class="fw-bold fs-6"><?= h($latestVitals['heart_rate'] ?? '--') ?></span>
                                                            <span class="text-muted small ms-1" style="font-size: 0.7rem;">bpm</span>
                                                        </div>
                                                        <div style="min-height: 1.25rem;">
                                                            <?= $hrBadge ?>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- 3. Temp -->
                                                <div class="col-6 col-sm-4 col-lg-3">
                                                    <div class="p-2 rounded border h-100 d-flex flex-column justify-content-between <?= $tempTileClass ?>">
                                                        <span class="overview-micro-label d-block">Temperature</span>
                                                        <div class="my-1">
                                                            <span class="fw-bold fs-6"><?= !empty($latestVitals['temperature']) ? number_format((float)$latestVitals['temperature'], 1) : '--' ?></span>
                                                            <span class="text-muted small ms-1" style="font-size: 0.7rem;">°C</span>
                                                        </div>
                                                        <div style="min-height: 1.25rem;">
                                                            <?= $tempBadge ?>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- 4. Resp Rate -->
                                                <div class="col-6 col-sm-4 col-lg-3">
                                                    <div class="p-2 rounded border h-100 d-flex flex-column justify-content-between <?= $rrTileClass ?>">
                                                        <span class="overview-micro-label d-block">Resp Rate</span>
                                                        <div class="my-1">
                                                            <span class="fw-bold fs-6"><?= h($latestVitals['respiratory_rate'] ?? '--') ?></span>
                                                            <span class="text-muted small ms-1" style="font-size: 0.7rem;">cpm</span>
                                                        </div>
                                                        <div style="min-height: 1.25rem;">
                                                            <?= $rrBadge ?>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- 5. SpO2 -->
                                                <div class="col-6 col-sm-4 col-lg-3">
                                                    <div class="p-2 rounded border h-100 d-flex flex-column justify-content-between <?= $spo2TileClass ?>">
                                                        <span class="overview-micro-label d-block">Oxygen Saturation</span>
                                                        <div class="my-1">
                                                            <span class="fw-bold fs-6"><?= !empty($latestVitals['oxygen_saturation']) ? h($latestVitals['oxygen_saturation']) . '%' : '--' ?></span>
                                                            <span class="text-muted small ms-1" style="font-size: 0.7rem;">SpO2</span>
                                                        </div>
                                                        <div style="min-height: 1.25rem;">
                                                            <?= $spo2Badge ?>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- 6. Weight / Height -->
                                                <div class="col-6 col-sm-4 col-lg-3">
                                                    <div class="p-2 rounded border h-100 d-flex flex-column justify-content-between bg-light text-dark">
                                                        <span class="overview-micro-label d-block">Weight / Height</span>
                                                        <div class="my-1">
                                                            <span class="fw-bold fs-6"><?= !empty($latestVitals['weight']) ? h($latestVitals['weight']) . ' kg' : '--' ?></span>
                                                        </div>
                                                        <div style="min-height: 1.25rem;">
                                                            <span class="text-muted small" style="font-size: 0.75rem;"><?= !empty($latestVitals['height']) ? h($latestVitals['height']) . ' cm' : '-- cm' ?></span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- 7. BMI -->
                                                <div class="col-6 col-sm-4 col-lg-3">
                                                    <div class="p-2 rounded border h-100 d-flex flex-column justify-content-between <?= $bmiTileClass ?>">
                                                        <span class="overview-micro-label d-block">Body Mass Index</span>
                                                        <div class="my-1">
                                                            <span class="fw-bold fs-6"><?= !empty($latestVitals['bmi']) ? number_format((float)$latestVitals['bmi'], 2) : '--' ?></span>
                                                        </div>
                                                        <div style="min-height: 1.25rem;">
                                                            <span class="badge <?= $bmiClassified['badge'] ?> px-1"><?= h($bmiClassified['label']) ?></span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- 8. Waistline -->
                                                <div class="col-6 col-sm-4 col-lg-3">
                                                    <div class="p-2 rounded border h-100 d-flex flex-column justify-content-between bg-light text-dark">
                                                        <span class="overview-micro-label d-block">Waistline</span>
                                                        <div class="my-1">
                                                            <span class="fw-bold fs-6"><?= !empty($latestVitals['waist_circumference']) ? h($latestVitals['waist_circumference']) . ' cm' : '--' ?></span>
                                                        </div>
                                                        <div style="min-height: 1.25rem;">
                                                            <span class="text-muted small" style="font-size: 0.7rem;">Circumference</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="text-muted small d-flex flex-wrap justify-content-between align-items-center pt-2 border-top mt-2" style="font-size: 0.75rem;">
                                                <span>Recorded: <strong><?= date('M d, Y h:i A', strtotime($latestVitals['recorded_at'])) ?></strong></span>
                                                <span>Recorded By: <strong><?= h($latestVitals['recorder_name'] ?? 'Clinician') ?></strong></span>
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

                            <!-- 3b. Recent Clinical Encounter Hub (Full Width) -->
                            <?php
                            $latestConsultation = $latestConsultation ?? (!empty($consultationsHistory[0]) ? $consultationsHistory[0] : null);
                            $latestConsultationPrescriptions = $latestConsultationPrescriptions ?? ($latestConsultation ? (new \App\Models\Prescription())->findByConsultationId($latestConsultation['id']) : []);
                            ?>
                            <div class="col-12">
                                <div class="card border rounded-3 shadow-xs">
                                    <div class="card-header bg-light py-2 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <h5 class="h6 mb-0 fw-bold text-dark">
                                                <i class="bi bi-clipboard2-pulse-fill text-primary me-2"></i>Recent Clinical Encounter
                                            </h5>
                                            <?php if (!empty($latestConsultation)): ?>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-medium">
                                                    <?= date('M d, Y', strtotime($latestConsultation['consulted_at'])) ?>
                                                </span>
                                                <span class="badge <?= ($latestConsultation['status'] ?? '') === 'Completed' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' ?> fw-medium">
                                                    <?= h($latestConsultation['status'] ?? 'Open') ?>
                                                </span>
                                                <span class="text-muted small">
                                                    Clinician: <strong><?= h($latestConsultation['clinician_name'] ?? 'Clinician') ?></strong>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php if (!empty($latestConsultation)): ?>
                                                <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 view-consultation-btn" data-consultation-id="<?= $latestConsultation['id'] ?>" title="View Full Encounter">
                                                    View Record
                                                </button>
                                            <?php endif; ?>
                                            <a href="<?= url('/patients/' . $patient['id'] . '/consultations/create') ?>" class="btn btn-sm btn-primary py-1 px-2.5 shadow-xs fw-semibold">
                                                + New Consultation Entry
                                            </a>
                                        </div>
                                    </div>
                                    <div class="card-body p-3">
                                        <?php if (!empty($latestConsultation)): ?>
                                            <div class="row g-3">
                                                <!-- Left: Clinical Assessment & Chief Complaint -->
                                                <div class="col-12 col-md-7 border-end-md">
                                                    <div class="mb-2">
                                                        <span class="text-muted d-block small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Assessment / Clinical Impression</span>
                                                        <div class="p-2 rounded bg-light border border-primary-subtle mt-1">
                                                            <span class="fw-bold text-dark fs-6">
                                                                <?= !empty($latestConsultation['assessment']) ? nl2br(h($latestConsultation['assessment'])) : '<span class="text-muted fst-italic">No assessment entered</span>' ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <?php if (!empty($latestConsultation['subjective'])): ?>
                                                        <div class="mt-2">
                                                            <span class="text-muted d-block small" style="font-size: 0.75rem;">Chief Complaint / Subjective Notes:</span>
                                                            <p class="text-secondary small mb-0 mt-0.5" style="font-size: 0.8rem; line-height: 1.4;">
                                                                <?= nl2br(h(mb_strimwidth($latestConsultation['subjective'], 0, 220, '...'))) ?>
                                                            </p>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- Right: Active Prescriptions Attached to Encounter -->
                                                <div class="col-12 col-md-5">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                                                            Prescribed Medications
                                                        </span>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.7rem;">
                                                            <?= count($latestConsultationPrescriptions ?? []) ?> item<?= count($latestConsultationPrescriptions ?? []) === 1 ? '' : 's' ?>
                                                        </span>
                                                    </div>
                                                    <?php if (!empty($latestConsultationPrescriptions)): ?>
                                                        <div class="d-flex flex-column gap-1 mt-1" style="max-height: 125px; overflow-y: auto;">
                                                            <?php foreach ($latestConsultationPrescriptions as $rx): ?>
                                                                <div class="p-1.5 rounded bg-light border d-flex justify-content-between align-items-center small">
                                                                    <div>
                                                                        <strong class="text-dark"><?= h($rx['medicine_name']) ?></strong>
                                                                        <?php if (!empty($rx['dosage'])): ?>
                                                                            <span class="text-muted small ms-1">(<?= h($rx['dosage']) ?>)</span>
                                                                        <?php endif; ?>
                                                                        <?php if (!empty($rx['instructions'])): ?>
                                                                            <div class="text-muted" style="font-size: 0.72rem;"><?= h($rx['instructions']) ?></div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <?php if (!empty($rx['frequency'])): ?>
                                                                        <span class="badge bg-white text-secondary border ms-1" style="font-size: 0.68rem;"><?= h($rx['frequency']) ?></span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="p-2 rounded bg-light border text-muted small fst-italic text-center mt-1">
                                                            No prescription orders recorded for this encounter.
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center py-3 text-muted">
                                                <i class="bi bi-journal-medical fs-2 text-secondary opacity-50 d-block mb-1"></i>
                                                <p class="mb-2 fw-medium text-secondary small">No consultation encounters recorded yet for this patient.</p>
                                                <a href="<?= url('/patients/' . $patient['id'] . '/consultations/create') ?>" class="btn btn-sm btn-primary shadow-xs px-3">
                                                    <i class="bi bi-plus-circle me-1"></i> Begin First Clinical Consultation
                                                </a>
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
                                                            <strong><?= h($member['last_name']) ?>, <?= h($member['first_name']) ?> <?= h($member['suffix'] ?? '') ?></strong>
                                                            <span class="text-muted ms-1">(<?= h($member['age']) ?> yrs / <?= h($member['sex']) ?>)</span>
                                                        </div>
                                                        <a href="<?= url('/patients/' . $member['id']) ?>" class="btn btn-sm btn-outline-primary py-1 px-2">
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
                                            Edit IHP
                                        </button>
                                    </div>
                                    <div class="card-body p-3 small">
                                        <?php if ($medicalHistory): ?>
                                            <?php
                                            $allergyItem = null;
                                            $highRiskItems = [];
                                            $otherPmhItems = [];

                                            $highRiskKeywords = [
                                                'hypertension' => ['class' => 'bg-danger-subtle text-danger border border-danger-subtle', 'icon' => 'bi-heart-pulse'],
                                                'diabetes' => ['class' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle', 'icon' => 'bi-droplet'],
                                                'asthma' => ['class' => 'bg-primary-subtle text-primary border border-primary-subtle', 'icon' => 'bi-lungs'],
                                                'tuberculosis' => ['class' => 'bg-danger-subtle text-danger border border-danger-subtle', 'icon' => 'bi-virus'],
                                                'ptb' => ['class' => 'bg-danger-subtle text-danger border border-danger-subtle', 'icon' => 'bi-virus'],
                                                'heart' => ['class' => 'bg-danger-subtle text-danger border border-danger-subtle', 'icon' => 'bi-heart-fill'],
                                                'coronary' => ['class' => 'bg-danger-subtle text-danger border border-danger-subtle', 'icon' => 'bi-heart-fill'],
                                                'kidney' => ['class' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle', 'icon' => 'bi-activity'],
                                                'cancer' => ['class' => 'bg-danger-subtle text-danger border border-danger-subtle', 'icon' => 'bi-shield-exclamation'],
                                                'stroke' => ['class' => 'bg-danger-subtle text-danger border border-danger-subtle', 'icon' => 'bi-activity'],
                                                'cerebrovascular' => ['class' => 'bg-danger-subtle text-danger border border-danger-subtle', 'icon' => 'bi-activity'],
                                            ];

                                            if (!empty($medicalHistory['past_medical_history']) && is_array($medicalHistory['past_medical_history'])) {
                                                foreach ($medicalHistory['past_medical_history'] as $cond => $det) {
                                                    if (empty($cond)) continue;
                                                    $condLower = strtolower($cond);
                                                    $fullText = !empty($det) ? "{$cond} ({$det})" : $cond;

                                                    if (strpos($condLower, 'allerg') !== false) {
                                                        $allergyItem = !empty($det) ? $det : $cond;
                                                    } else {
                                                        $isHighRisk = false;
                                                        foreach ($highRiskKeywords as $key => $style) {
                                                            if (strpos($condLower, $key) !== false) {
                                                                $highRiskItems[] = [
                                                                    'label' => $fullText,
                                                                    'class' => $style['class'],
                                                                    'icon' => $style['icon']
                                                                ];
                                                                $isHighRisk = true;
                                                                break;
                                                            }
                                                        }
                                                        if (!$isHighRisk) {
                                                            $otherPmhItems[] = $fullText;
                                                        }
                                                    }
                                                }
                                            }
                                            if (!$allergyItem && !empty($cdsAlerts['allergy_alert'])) {
                                                $allergyItem = $cdsAlerts['allergy_alert'];
                                            }
                                            ?>

                                            <!-- Prominent Allergy Alert Banner -->
                                            <?php if ($allergyItem): ?>
                                                <div class="alert alert-danger py-2 px-2.5 mb-2.5 d-flex align-items-center gap-2 border-danger-subtle bg-danger-subtle text-danger shadow-xs">
                                                    <i class="bi bi-exclamation-octagon-fill fs-5 flex-shrink-0"></i>
                                                    <div class="small lh-sm">
                                                        <strong class="d-block text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Known Patient Allergy</strong>
                                                        <span class="fw-bold text-danger-emphasis fs-7"><?= h($allergyItem) ?></span>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Active & High-Risk Chronic Conditions -->
                                            <div class="mb-2">
                                                <strong class="d-block mb-1 text-dark" style="font-size: 0.75rem;">
                                                    Active &amp; Chronic Conditions:
                                                </strong>
                                                <?php if (!empty($highRiskItems)): ?>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <?php foreach ($highRiskItems as $hrItem): ?>
                                                            <span class="badge <?= $hrItem['class'] ?> fw-medium px-2 py-1">
                                                                <?= h($hrItem['label']) ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted small fst-italic">No high-risk chronic conditions recorded.</span>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Other Past Illnesses -->
                                            <?php if (!empty($otherPmhItems)): ?>
                                                <div class="mb-2">
                                                    <strong class="d-block mb-1 text-muted" style="font-size: 0.75rem;">Other Past Illnesses:</strong>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <?php foreach ($otherPmhItems as $otherItem): ?>
                                                            <span class="badge bg-light text-dark border px-1.5 py-1"><?= h($otherItem) ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Social Habits -->
                                            <div class="mb-2 border-top pt-2">
                                                <strong class="d-block mb-1 text-dark" style="font-size: 0.75rem;">Social Habits:</strong> 
                                                <span class="text-dark small">Smoking: <strong><?= h($medicalHistory['smoking_status'] ?? 'Never') ?></strong> &bull; Alcohol: <strong><?= h($medicalHistory['alcohol_status'] ?? 'Never') ?></strong></span>
                                            </div>

                                            <!-- Family Heredity -->
                                            <div class="border-top pt-2">
                                                <strong class="d-block mb-1 text-dark" style="font-size: 0.75rem;">Family Heredity:</strong> 
                                                <?php 
                                                $overviewFamList = [];
                                                if (!empty($medicalHistory['family_history']) && is_array($medicalHistory['family_history'])) {
                                                    foreach ($medicalHistory['family_history'] as $cond => $det) {
                                                        if (empty($cond)) continue;
                                                        $overviewFamList[] = !empty($det) ? "{$cond}: {$det}" : "{$cond}";
                                                    }
                                                }
                                                ?>
                                                <?php if (!empty($overviewFamList)): ?>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <?php foreach ($overviewFamList as $famItem): ?>
                                                            <span class="badge bg-light text-dark border px-1.5 py-1"><?= h($famItem) ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted small fst-italic">None declared.</span>
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

                            <!-- Prenatal Care Workstation Quick Card (Only if registered in Prenatal program) -->
                            <?php 
                            $hasMaternalRegistration = !empty($activePrenatal) || !empty($allPrenatalEpisodes);
                            $hasWellbabyRegistration = !empty($wellbabyRecord);
                            ?>
                            <?php if ($hasMaternalRegistration): ?>
                                <div class="col-12 <?= $hasWellbabyRegistration ? 'col-md-6' : '' ?>">
                                    <div class="card border rounded-3 h-100 shadow-xs">
                                        <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                                            <h5 class="h6 mb-0 fw-bold text-dark">
                                                <i class="bi bi-heart-pulse-fill text-pink me-2"></i>Prenatal Care
                                            </h5>
                                            <a href="<?= url('/prenatal/' . $patient['id']) ?>" class="btn btn-sm btn-outline-primary py-1 px-2">
                                                Open Workstation <i class="bi bi-arrow-right ms-1"></i>
                                            </a>
                                        </div>
                                        <div class="card-body p-3 small">
                                            <?php if ($activePrenatal): ?>
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <span class="badge bg-pink text-white">Active Pregnancy Episode</span>
                                                    <span class="fw-bold text-pink"><?= h($activePrenatal['calculated_aog']['weeks'] ?? '--') ?> weeks AOG</span>
                                                </div>
                                                <div class="row g-2 text-muted">
                                                    <div class="col-6"><strong>LMP:</strong> <?= !empty($activePrenatal['lmp']) ? date('M d, Y', strtotime($activePrenatal['lmp'])) : 'N/A' ?></div>
                                                    <div class="col-6"><strong>EDC:</strong> <?= !empty($activePrenatal['edc']) ? date('M d, Y', strtotime($activePrenatal['edc'])) : 'N/A' ?></div>
                                                    <div class="col-6"><strong>Gravida/Para:</strong> G<?= h($activePrenatal['gravida'] ?? 1) ?> P<?= h($activePrenatal['para'] ?? 0) ?></div>
                                                    <div class="col-6"><strong>Trimester:</strong> <?= h($activePrenatal['calculated_aog']['trimester'] ?? '1st') ?></div>
                                                </div>
                                                <div class="mt-3">
                                                    <a href="<?= url('/prenatal/' . $patient['id']) ?>" class="btn btn-sm btn-pink text-white w-100 shadow-xs">
                                                        Open Prenatal Workstation
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <span class="badge bg-secondary text-white">Concluded / Past Episodes</span>
                                                    <span class="text-muted"><?= count($allPrenatalEpisodes) ?> episode(s)</span>
                                                </div>
                                                <p class="text-muted mb-2">Patient has past prenatal health and delivery records on file.</p>
                                                <a href="<?= url('/prenatal/' . $patient['id']) ?>" class="btn btn-sm btn-outline-primary w-100 shadow-xs">
                                                    Open Prenatal Workstation
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Well-Baby & EPI Workstation Quick Card (If registered or eligible) -->
                            <?php if ($hasWellbabyRegistration): ?>
                                <div class="col-12 <?= $hasMaternalRegistration ? 'col-md-6' : '' ?>">
                                    <div class="card border rounded-3 h-100 shadow-xs">
                                        <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                                            <h5 class="h6 mb-0 fw-bold text-dark">
                                                <i class="bi bi-emoji-smile-fill text-success me-2"></i>Well-Baby &amp; EPI
                                            </h5>
                                            <a href="<?= url('/well-baby/' . $patient['id']) ?>" class="btn btn-sm btn-outline-success py-1 px-2">
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
                            <?php elseif (isset($patient['age']) && (int)$patient['age'] <= 5): ?>
                                <div class="col-12 <?= $hasMaternalRegistration ? 'col-md-6' : '' ?>">
                                    <div class="card border rounded-3 h-100 shadow-xs">
                                        <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                                            <h5 class="h6 mb-0 fw-bold text-dark">
                                                <i class="bi bi-emoji-smile text-primary me-2"></i>Well-Baby &amp; EPI Care
                                            </h5>
                                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">Eligible (Aged 0–5)</span>
                                        </div>
                                        <div class="card-body p-3 small">
                                            <p class="text-muted mb-3">
                                                This child is eligible for routine infant/under-5 immunization (DOH EPI schedule), monthly growth monitoring, and vitamin supplementation.
                                            </p>
                                            <a href="<?= url('/well-baby/register?patient_id=' . $patient['id']) ?>" class="btn btn-sm btn-primary text-white w-100 shadow-xs">
                                                <i class="bi bi-plus-circle me-1"></i> Register in Well-Baby Program
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- 6. Metadata Footer -->
                            <div class="col-12 text-center text-muted pt-2" style="font-size: 0.75rem;">
                                <span>Patient chart registered on <?= !empty($patient['created_at']) ? date('M d, Y \a\t h:i A', strtotime($patient['created_at'])) : 'Unknown Date' ?> <?= !empty($patient['creator_name']) ? 'by ' . h($patient['creator_name']) : '' ?></span>
                            </div>

                        </div>
                    </div>

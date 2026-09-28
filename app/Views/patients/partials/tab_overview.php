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
                                            <!-- Row 1: Civil Status & Religion -->
                                            <div class="col-6">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">Civil Status</span>
                                                <span class="fw-semibold text-dark">
                                                    <?= ($patient['civil_status'] === 'Others' && !empty($patient['civil_status_other'])) ? 'Others (' . h($patient['civil_status_other']) . ')' : h($patient['civil_status']) ?>
                                                </span>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">Religion</span>
                                                <span class="text-dark fw-medium"><?= h($patient['religion'] ?? 'Unspecified') ?></span>
                                            </div>

                                            <!-- Row 2: Education & Occupation -->
                                            <div class="col-6 border-top pt-2 mt-1">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">Educational Attainment</span>
                                                <span class="text-dark fw-medium"><?= h($patient['education_attainment'] ?? 'Unspecified') ?></span>
                                            </div>
                                            <div class="col-6 border-top pt-2 mt-1">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">Occupation</span>
                                                <span class="text-dark fw-medium"><?= h($patient['occupation'] ?? 'Unspecified') ?></span>
                                            </div>

                                            <!-- Row 3: PhilHealth Membership & Primary Contact -->
                                            <div class="col-6 border-top pt-2 mt-1">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">PhilHealth Status / Category</span>
                                                <span class="badge bg-light text-dark border"><?= h($patient['phic_status'] ?? 'Non-Member') ?></span>
                                                <?php if (!empty($patient['phic_type'])): ?>
                                                    <span class="text-muted d-block" style="font-size: 0.7rem;"><?= h($patient['phic_type']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-6 border-top pt-2 mt-1">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">Primary Contact Number</span>
                                                <?php if (!empty($patient['contact_no'])): ?>
                                                    <span class="font-monospace fw-semibold text-dark"><i class="bi bi-telephone text-secondary me-1"></i><a href="tel:<?= h($patient['contact_no']) ?>" class="text-decoration-none text-dark"><?= h($patient['contact_no']) ?></a></span>
                                                <?php else: ?>
                                                    <span class="text-muted fst-italic"><i class="bi bi-telephone text-muted me-1"></i>None registered</span>
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
                                                        <span class="d-block small" style="font-size: 0.65rem;">
                                                            <?php 
                                                            $bmiClassified = classify_bmi($latestVitals['bmi'] ?? null);
                                                            echo '<span class="' . $bmiClassified['class'] . '">' . h($bmiClassified['label']) . '</span>';
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

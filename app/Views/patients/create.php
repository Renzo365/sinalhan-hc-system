<?php
$title = 'Register Patient';
$breadcrumbs = [
    'Patients' => '/patients',
    'Register Patient' => null
];
require dirname(__DIR__) . '/layout/header.php';
?>

<div class="patient-form-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h3 mb-1 fw-bold text-primary-dark">Register Patient (Stage 1 Intake)</h2>
            <p class="text-secondary small mb-0">Fast clinical intake: Enter demographic, household, and basic identity details.</p>
        </div>
        <a href="<?= url('/patients') ?>" class="btn btn-outline-secondary btn-nav-guard">
            <i class="bi bi-arrow-left me-1"></i> Back to Patients List
        </a>
    </div>

    <!-- Errors Alert Box with Accessible In-Page Anchors -->
    <?php if (isset($errors) && !empty($errors)): ?>
        <?php
        $fieldMap = [
            'First Name' => 'first_name',
            'Middle Name' => 'middle_name',
            'Last Name' => 'last_name',
            'Date of Birth' => 'dob',
            'Biological Sex' => 'sex',
            'Civil Status' => 'civil_status',
            'Full Address' => 'address',
            'Address' => 'address',
            'Primary Contact No' => 'contact_no',
            'Contact No' => 'contact_no',
            'Emergency Contact Number' => 'emergency_no',
            'Emergency Contact Person' => 'emergency_name',
            'PhilHealth' => 'philhealth_no',
            'Extension' => 'suffix',
            'Family Number' => 'family_no'
        ];
        ?>
        <div class="alert alert-danger mb-4 shadow-sm" role="alert">
            <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Please correct the following errors:</div>
            <ul class="mb-0 ps-3 small">
                <?php foreach ($errors as $err): 
                    $targetId = '';
                    foreach ($fieldMap as $needle => $id) {
                        if (stripos($err, $needle) !== false) {
                            $targetId = $id;
                            break;
                        }
                    }
                ?>
                    <li>
                        <?php if ($targetId): ?>
                            <a href="#<?= $targetId ?>" class="text-danger text-decoration-underline error-jump-link" data-target="<?= $targetId ?>"><?= h($err) ?></a>
                        <?php else: ?>
                            <?= h($err) ?>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Duplicate Warning Banner -->
    <div class="alert alert-warning mb-4 shadow-sm d-none" id="duplicateWarningBanner" role="alert">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Possible Duplicate Patient Detected</div>
                <div class="small text-dark" id="duplicateBannerText">Matching records were found in the database.</div>
            </div>
            <button type="button" class="btn btn-warning btn-sm fw-semibold text-dark" id="btnViewDuplicates">
                <i class="bi bi-eye me-1"></i> View Matches
            </button>
        </div>
    </div>

    <!-- Duplicate Patient Soft-Warning Modal -->
    <div class="modal fade" id="duplicateWarningModal" tabindex="-1" aria-labelledby="duplicateWarningModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow border-0">
                <div class="modal-header bg-warning-subtle text-dark border-bottom py-3">
                    <h5 class="modal-title h6 fw-bold mb-0 d-flex align-items-center gap-2" id="duplicateWarningModalLabel">
                        <i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i>
                        <span>Possible Duplicate Patient Record Detected</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-secondary mb-3">
                        The system found existing patient record(s) matching the <strong>Full Name</strong> or <strong>Last Name + Date of Birth</strong> entered. Please verify whether this patient is already registered before proceeding.
                    </p>
                    <div class="table-responsive border rounded-3 mb-3">
                        <table class="table table-sm table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Patient No.</th>
                                    <th>Full Name</th>
                                    <th>Date of Birth</th>
                                    <th>Sex</th>
                                    <th>Address</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="duplicateModalTableBody">
                            </tbody>
                        </table>
                    </div>
                    <div class="alert alert-info py-2 px-3 small mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle-fill text-primary"></i>
                        <span>If this is indeed the same person, click <strong>Cancel & Review</strong> and search for their existing record. If this is a different individual with a similar name, you may click <strong>Proceed with Registration</strong>.</span>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top py-2 px-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal" id="btnCancelDuplicate">
                        <i class="bi bi-x-circle me-1"></i> Cancel & Review
                    </button>
                    <button type="button" class="btn btn-warning btn-sm px-3 fw-semibold text-dark" id="btnProceedDuplicate">
                        <i class="bi bi-person-check-fill me-1"></i> Proceed with Registration
                    </button>
                </div>
            </div>
        </div>
    </div>

    <form action="<?= url('/patients') ?>" method="POST" id="patientForm">
        <?= csrf_field() ?>
        <input type="hidden" name="duplicate_acknowledged" id="duplicate_acknowledged" value="0">

        <!-- CARD 1: Personal Identity -->
        <div class="card card-premium mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
                <span class="badge bg-primary-subtle text-primary fw-bold me-2 px-2 py-1">1</span>
                <h3 class="card-title h6 mb-0 fw-bold text-dark">
                    <i class="bi bi-person-badge text-primary me-2"></i>Personal Identity
                </h3>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <!-- Row 1: First Name -> Middle Name -> Last Name -> Extension -->
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label for="first_name" class="form-label fw-semibold text-secondary-emphasis small">First Name <span class="text-danger">*</span></label>
                        <input type="text" 
                               name="first_name" 
                               id="first_name" 
                               class="form-control name-input" 
                               value="<?= h($input['first_name'] ?? '') ?>" 
                               placeholder="e.g. Juan"
                               maxlength="50"
                               minlength="2"
                               autocomplete="given-name"
                               required>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label for="middle_name" class="form-label fw-semibold text-secondary-emphasis small">Middle Name</label>
                        <input type="text" 
                               name="middle_name" 
                               id="middle_name" 
                               class="form-control name-input" 
                               placeholder="e.g. Mercado"
                               maxlength="50"
                               autocomplete="additional-name"
                               value="<?= h($input['middle_name'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label for="last_name" class="form-label fw-semibold text-secondary-emphasis small">Last Name <span class="text-danger">*</span></label>
                        <input type="text" 
                               name="last_name" 
                               id="last_name" 
                               class="form-control name-input" 
                               value="<?= h($input['last_name'] ?? '') ?>" 
                               placeholder="e.g. Dela Cruz"
                               maxlength="50"
                               minlength="2"
                               autocomplete="family-name"
                               required>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label for="suffix" class="form-label fw-semibold text-secondary-emphasis small">Extension (Sr., Jr., III)</label>
                        <input type="text" 
                               name="suffix" 
                               id="suffix" 
                               class="form-control" 
                               placeholder="e.g. Jr., III"
                               maxlength="20"
                               autocomplete="honorific-suffix"
                               value="<?= h($input['suffix'] ?? '') ?>">
                    </div>

                    <!-- Row 2: Date of Birth, Calculated Age, Biological Sex, Civil Status -->
                    <div class="col-12 col-sm-6 col-md-3">
                        <label for="dob" class="form-label fw-semibold text-secondary-emphasis small">Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" 
                               name="dob" 
                               id="dob" 
                               class="form-control bg-white" 
                               value="<?= h($input['dob'] ?? '') ?>" 
                               max="<?= date('Y-m-d') ?>"
                               autocomplete="bday"
                               required>
                    </div>

                    <div class="col-12 col-sm-6 col-md-3">
                        <label for="calculated_age" class="form-label fw-semibold text-secondary-emphasis small">Calculated Age</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-clock-history"></i></span>
                            <input type="text" 
                                   id="calculated_age" 
                                   class="form-control bg-light fw-bold text-dark font-monospace" 
                                   placeholder="Auto-calculated" 
                                   readonly 
                                   tabindex="-1">
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-md-3">
                        <label for="sex" class="form-label fw-semibold text-secondary-emphasis small">Biological Sex <span class="text-danger">*</span></label>
                        <select name="sex" id="sex" class="form-select" autocomplete="sex" required>
                            <option value="" disabled <?= empty($input['sex']) ? 'selected' : '' ?>>Select Sex</option>
                            <option value="Male" <?= ($input['sex'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($input['sex'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                        </select>
                    </div>

                    <div class="col-12 col-sm-6 col-md-3">
                        <label for="civil_status" class="form-label fw-semibold text-secondary-emphasis small">Civil Status <span class="text-danger">*</span></label>
                        <select name="civil_status" id="civil_status" class="form-select" required>
                            <option value="" disabled <?= empty($input['civil_status']) ? 'selected' : '' ?>>Select Status</option>
                            <?php foreach (['Single', 'Married', 'Widow/Widower', 'Annulled', 'Separated', 'Others'] as $status): ?>
                                <option value="<?= $status ?>" <?= ($input['civil_status'] ?? '') === $status ? 'selected' : '' ?>>
                                    <?= $status ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Row 3: Blood Type, Physical Envelope No., Religion -->
                    <div class="col-12 col-md-4">
                        <label for="blood_type" class="form-label fw-semibold text-secondary-emphasis small">Blood Type</label>
                        <select name="blood_type" id="blood_type" class="form-select">
                            <option value="Unknown" <?= ($input['blood_type'] ?? 'Unknown') === 'Unknown' ? 'selected' : '' ?>>Unknown</option>
                            <?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bt): ?>
                                <option value="<?= $bt ?>" <?= ($input['blood_type'] ?? '') === $bt ? 'selected' : '' ?>>
                                    <?= $bt ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="envelope_no" class="form-label fw-semibold text-secondary-emphasis small">
                            Physical Envelope No. <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1 font-monospace" style="font-size: 0.7rem;">Logbook #</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-folder2-open"></i></span>
                            <input type="text" 
                                   name="envelope_no" 
                                   id="envelope_no" 
                                   class="form-control" 
                                   placeholder="e.g. 1001" 
                                   maxlength="50" 
                                   value="<?= h($input['envelope_no'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="religion" class="form-label fw-semibold text-secondary-emphasis small">Religion</label>
                        <input type="text" 
                               name="religion" 
                               id="religion" 
                               class="form-control" 
                               placeholder="e.g. Roman Catholic, INC, Islam" 
                               maxlength="100" 
                               value="<?= h($input['religion'] ?? '') ?>">
                    </div>

                    <!-- Row 4: Specify Civil Status (Conditionally visible ONLY when Others is selected) -->
                    <div class="col-12 col-md-6 <?= ($input['civil_status'] ?? '') === 'Others' ? '' : 'd-none' ?>" id="civilStatusOtherContainer">
                        <label for="civil_status_other" class="form-label fw-semibold text-secondary-emphasis small">Specify Civil Status <span class="text-danger">*</span></label>
                        <input type="text" 
                               name="civil_status_other" 
                               id="civil_status_other" 
                               class="form-control" 
                               placeholder="Please specify civil status" 
                               maxlength="100" 
                               value="<?= h($input['civil_status_other'] ?? '') ?>"
                               <?= ($input['civil_status'] ?? '') === 'Others' ? '' : 'disabled' ?>>
                    </div>
                </div>
            </div>
        </div>

        <!-- CARD 2: Household & Address -->
        <div class="card card-premium mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
                <span class="badge bg-primary-subtle text-primary fw-bold me-2 px-2 py-1">2</span>
                <h3 class="card-title h6 mb-0 fw-bold text-dark">
                    <i class="bi bi-house-door text-primary me-2"></i>Household & Address
                </h3>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <!-- Row 1: Family Number & Contact No -->
                    <div class="col-12 col-md-6">
                        <label for="family_no" class="form-label fw-semibold text-secondary-emphasis small">
                            Family Number (Household ID)
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-people-fill"></i></span>
                            <input type="text" 
                                   name="family_no" 
                                   id="family_no" 
                                   class="form-control" 
                                   placeholder="e.g. FAM-0428" 
                                   maxlength="50"
                                   value="<?= h($input['family_no'] ?? '') ?>">
                        </div>
                        <div class="form-text small text-muted">Links household members together in the directory.</div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="contact_no" class="form-label fw-semibold text-secondary-emphasis small">Contact No. (Mobile)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-telephone"></i></span>
                            <input type="tel" 
                                   name="contact_no" 
                                   id="contact_no" 
                                   class="form-control phone-input" 
                                   placeholder="09171234567" 
                                   maxlength="11"
                                   autocomplete="tel"
                                   value="<?= h($input['contact_no'] ?? '') ?>">
                        </div>
                        <div class="form-text small text-muted">11-digit mobile number starting with 09.</div>
                    </div>

                    <!-- Row 2: Full Address -->
                    <div class="col-12">
                        <label for="address" class="form-label fw-semibold text-secondary-emphasis small">Full Address <span class="text-danger">*</span></label>
                        <input type="text" 
                               name="address" 
                               id="address" 
                               class="form-control" 
                               placeholder="e.g. Blk 4 Lot 12 Purok 3, Barangay Sinalhan, Santa Rosa, Laguna" 
                               maxlength="500" 
                               autocomplete="street-address"
                               value="<?= h($input['address'] ?? '') ?>" 
                               required>
                        <div class="form-text small text-muted">Enter complete residential address (House No., Street, Purok, Barangay, City/Municipality).</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CARD 3: PhilHealth & Socioeconomic -->
        <div class="card card-premium mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
                <span class="badge bg-primary-subtle text-primary fw-bold me-2 px-2 py-1">3</span>
                <h3 class="card-title h6 mb-0 fw-bold text-dark">
                    <i class="bi bi-card-heading text-primary me-2"></i>PhilHealth & Socioeconomic
                </h3>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <!-- Row 1: PhilHealth Status & PHIC Category / Type -->
                    <div class="col-12 col-md-6">
                        <label for="phic_status" class="form-label fw-semibold text-secondary-emphasis small">PhilHealth Status <span class="text-danger">*</span></label>
                        <select name="phic_status" id="phic_status" class="form-select" required>
                            <option value="Member" <?= ($input['phic_status'] ?? '') === 'Member' ? 'selected' : '' ?>>Member</option>
                            <option value="Dependent" <?= ($input['phic_status'] ?? '') === 'Dependent' ? 'selected' : '' ?>>Dependent</option>
                            <option value="Non-Member" <?= ($input['phic_status'] ?? 'Non-Member') === 'Non-Member' ? 'selected' : '' ?>>Non-Member</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="phic_type" class="form-label fw-semibold text-secondary-emphasis small">PHIC Category / Type</label>
                        <select name="phic_type" id="phic_type" class="form-select">
                            <option value="">-- Select Category --</option>
                            <optgroup label="1. Sponsored">
                                <option value="Sponsored - NHTS" <?= ($input['phic_type'] ?? '') === 'Sponsored - NHTS' ? 'selected' : '' ?>>Sponsored - NHTS (National Household Targeting System)</option>
                                <option value="Sponsored - NGS / NGA" <?= in_array(($input['phic_type'] ?? ''), ['Sponsored - NGS / NGA', 'Sponsored - NGS', 'Sponsored - NGA'], true) ? 'selected' : '' ?>>Sponsored - NGS / NGA (National Government)</option>
                                <option value="Sponsored - LGU" <?= ($input['phic_type'] ?? '') === 'Sponsored - LGU' ? 'selected' : '' ?>>Sponsored - LGU (Local Government Unit)</option>
                                <option value="Sponsored - Private" <?= ($input['phic_type'] ?? '') === 'Sponsored - Private' ? 'selected' : '' ?>>Sponsored - Private</option>
                            </optgroup>
                            <optgroup label="2. Individually Paying Program (IPP)">
                                <option value="IPP - Organized Group (OG)" <?= in_array(($input['phic_type'] ?? ''), ['IPP - Organized Group (OG)', 'IPP - OG'], true) ? 'selected' : '' ?>>IPP - Organized Group (OG)</option>
                                <option value="IPP - OFW" <?= ($input['phic_type'] ?? '') === 'IPP - OFW' ? 'selected' : '' ?>>IPP - OFW (Overseas Filipino Worker)</option>
                                <option value="IPP - Voluntary / Self-Employed" <?= in_array(($input['phic_type'] ?? ''), ['IPP - Voluntary / Self-Employed', 'Informal / Self-Earning'], true) ? 'selected' : '' ?>>IPP - Voluntary / Self-Employed</option>
                            </optgroup>
                            <optgroup label="3. Employed">
                                <option value="Employed - Government" <?= ($input['phic_type'] ?? '') === 'Employed - Government' ? 'selected' : '' ?>>Employed - Government (Formal Sector)</option>
                                <option value="Employed - Private" <?= ($input['phic_type'] ?? '') === 'Employed - Private' ? 'selected' : '' ?>>Employed - Private (Formal Sector)</option>
                            </optgroup>
                            <optgroup label="4. Lifetime">
                                <option value="Lifetime Member" <?= ($input['phic_type'] ?? '') === 'Lifetime Member' ? 'selected' : '' ?>>Lifetime Member (Retirees / Pensioners)</option>
                            </optgroup>
                        </select>
                    </div>

                    <!-- Row 2: PIN & Educational Attainment -->
                    <div class="col-12 col-md-6">
                        <label for="philhealth_no" class="form-label fw-semibold text-secondary-emphasis small">PhilHealth Identification No. (PIN)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-credit-card-2-front"></i></span>
                            <input type="text" 
                                   name="philhealth_no" 
                                   id="philhealth_no" 
                                   class="form-control phic-mask" 
                                   placeholder="XX-XXXXXXXXX-X" 
                                   maxlength="14"
                                   value="<?= h($input['philhealth_no'] ?? '') ?>">
                        </div>
                        <div class="form-text small text-muted" id="phicHelpText">Standard 12-digit PIN format (leave blank if none).</div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="education_attainment" class="form-label fw-semibold text-secondary-emphasis small">Educational Attainment</label>
                        <select name="education_attainment" id="education_attainment" class="form-select">
                            <option value="">-- Select Education --</option>
                            <?php foreach (['No Schooling', 'Elementary', 'High School', 'Vocational', 'College degree, post graduate'] as $edu): ?>
                                <option value="<?= $edu ?>" <?= ($input['education_attainment'] ?? '') === $edu ? 'selected' : '' ?>>
                                    <?= $edu ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Row 3: Occupation -->
                    <div class="col-12 col-md-6">
                        <label for="occupation" class="form-label fw-semibold text-secondary-emphasis small">Occupation</label>
                        <input type="text" 
                               name="occupation" 
                               id="occupation" 
                               class="form-control" 
                               placeholder="e.g. Vendor, Driver, Student" 
                               maxlength="100"
                               value="<?= h($input['occupation'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- CARD 4: Immediate Family & Emergency Contacts -->
        <div class="card card-premium mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
                <span class="badge bg-primary-subtle text-primary fw-bold me-2 px-2 py-1">4</span>
                <h3 class="card-title h6 mb-0 fw-bold text-dark">
                    <i class="bi bi-people text-primary me-2"></i>Immediate Family & Emergency Contacts
                </h3>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <!-- Section 4A: Family Background -->
                    <div class="col-12 mb-1">
                        <div class="d-flex align-items-center justify-content-between pb-1 border-bottom">
                            <span class="fw-bold text-dark small text-uppercase">
                                <i class="bi bi-person-heart text-primary me-1"></i>4A. Family Background (Parents & Spouse)
                            </span>
                            <span class="text-muted small" style="font-size: 0.75rem;">Optional demographic & hereditary reference</span>
                        </div>
                    </div>

                    <!-- Row 1: Father's Name & Father's DOB -->
                    <div class="col-12 col-md-7">
                        <label for="father_name" class="form-label fw-semibold text-secondary-emphasis small">Father's Full Name</label>
                        <input type="text" 
                               name="father_name" 
                               id="father_name" 
                               class="form-control name-input" 
                               placeholder="Full Name of Father" 
                               maxlength="150"
                               value="<?= h($input['father_name'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-md-5">
                        <label for="father_dob" class="form-label fw-semibold text-secondary-emphasis small">Father's Date of Birth</label>
                        <input type="date" 
                               name="father_dob" 
                               id="father_dob" 
                               class="form-control" 
                               max="<?= date('Y-m-d') ?>"
                               value="<?= h($input['father_dob'] ?? '') ?>">
                    </div>

                    <!-- Row 2: Mother's Name & Mother's DOB -->
                    <div class="col-12 col-md-7">
                        <label for="mother_name" class="form-label fw-semibold text-secondary-emphasis small">Mother's Maiden Name</label>
                        <input type="text" 
                               name="mother_name" 
                               id="mother_name" 
                               class="form-control name-input" 
                               placeholder="Maiden Name of Mother" 
                               maxlength="150"
                               value="<?= h($input['mother_name'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-md-5">
                        <label for="mother_dob" class="form-label fw-semibold text-secondary-emphasis small">Mother's Date of Birth</label>
                        <input type="date" 
                               name="mother_dob" 
                               id="mother_dob" 
                               class="form-control" 
                               max="<?= date('Y-m-d') ?>"
                               value="<?= h($input['mother_dob'] ?? '') ?>">
                    </div>

                    <!-- Row 3: Spouse's Name & Spouse's DOB -->
                    <div class="col-12 col-md-7">
                        <label for="spouse_name" class="form-label fw-semibold text-secondary-emphasis small">Spouse's Full Name (if married / live-in)</label>
                        <input type="text" 
                               name="spouse_name" 
                               id="spouse_name" 
                               class="form-control name-input" 
                               placeholder="Full Name of Spouse" 
                               maxlength="150"
                               value="<?= h($input['spouse_name'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-md-5">
                        <label for="spouse_dob" class="form-label fw-semibold text-secondary-emphasis small">Spouse's Date of Birth</label>
                        <input type="date" 
                               name="spouse_dob" 
                               id="spouse_dob" 
                               class="form-control" 
                               max="<?= date('Y-m-d') ?>"
                               value="<?= h($input['spouse_dob'] ?? '') ?>">
                    </div>

                    <!-- Section 4B: Emergency Contact & Notification -->
                    <div class="col-12 mt-4 mb-1">
                        <div class="d-flex align-items-center justify-content-between pb-1 border-bottom">
                            <span class="fw-bold text-dark small text-uppercase">
                                <i class="bi bi-telephone-plus-fill text-danger me-1"></i>4B. Emergency Contact & Notification
                            </span>
                            <span class="text-muted small" style="font-size: 0.75rem;">Primary contact person to notify</span>
                        </div>
                    </div>

                    <!-- Row 4: Emergency Contacts -->
                    <div class="col-12 col-md-4">
                        <label for="emergency_name" class="form-label fw-semibold text-secondary-emphasis small">Emergency Contact Person</label>
                        <input type="text" 
                               name="emergency_name" 
                               id="emergency_name" 
                               class="form-control name-input" 
                               placeholder="e.g. Maria Dela Cruz" 
                               maxlength="100"
                               value="<?= h($input['emergency_name'] ?? '') ?>">
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="emergency_relationship" class="form-label fw-semibold text-secondary-emphasis small">Relationship</label>
                        <input type="text" 
                               name="emergency_relationship" 
                               id="emergency_relationship" 
                               class="form-control" 
                               placeholder="e.g. Mother, Spouse, Sister" 
                               maxlength="50"
                               value="<?= h($input['emergency_relationship'] ?? '') ?>">
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="emergency_no" class="form-label fw-semibold text-secondary-emphasis small">Emergency Phone</label>
                        <input type="tel" 
                               name="emergency_no" 
                               id="emergency_no" 
                               class="form-control phone-input" 
                               placeholder="09187654321" 
                               maxlength="11"
                               autocomplete="tel"
                               value="<?= h($input['emergency_no'] ?? '') ?>">
                        <div class="form-text small text-muted">11-digit mobile number starting with 09.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit & Navigation Action Bar (Sticky floating bar) -->
        <div class="card card-premium sticky-form-action-bar mb-4 shadow">
            <div class="card-body p-3 px-md-4 py-md-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="text-secondary-emphasis small">
                    <i class="bi bi-info-circle me-1 text-primary"></i> Fields marked with (<span class="text-danger">*</span>) are mandatory.
                </span>
                <div class="d-flex gap-3">
                    <a href="<?= url('/patients') ?>" class="btn btn-light px-4 btn-nav-guard">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold" id="btnSubmitPatient">
                        <i class="bi bi-check2-circle me-1 fs-5 align-middle"></i> Register & View Profile
                    </button>
                </div>
            </div>
        </div>
    </form>
</div> <!-- Close patient-form-wrapper -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Unsaved Changes Guard with SweetAlert for In-App Navigation
    let isFormDirty = false;
    const patientForm = document.getElementById('patientForm');
    if (patientForm) {
        patientForm.addEventListener('input', () => { isFormDirty = true; });
        patientForm.addEventListener('change', () => { isFormDirty = true; });
        patientForm.addEventListener('submit', () => { isFormDirty = false; });
    }

    // Intercept navigation links across the page to show SweetAlert
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a[href]');
        if (!link || !isFormDirty) return;

        const href = link.getAttribute('href');
        // Ignore in-page hash anchors (e.g. #first_name error anchors) or empty/javascript links
        if (!href || href.startsWith('#') || href.startsWith('javascript:') || link.getAttribute('target') === '_blank') {
            return;
        }

        e.preventDefault();
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Discard Unsaved Patient Details?',
                text: 'You have entered patient details in this form. If you leave now, the registration intake will be discarded.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bi bi-box-arrow-left me-1"></i> Discard & Leave',
                cancelButtonText: '<i class="bi bi-arrow-return-left me-1"></i> Stay on Page',
                reverseButtons: true,
                focusCancel: true
            }).then((result) => {
                if (result.isConfirmed) {
                    isFormDirty = false;
                    window.location.href = href;
                }
            });
        } else {
            if (confirm('You have unsaved changes. Discard and leave this page?')) {
                isFormDirty = false;
                window.location.href = href;
            }
        }
    });

    // Native browser beforeunload as safety net for browser tab close/refresh
    window.addEventListener('beforeunload', function(e) {
        if (isFormDirty) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // 2. Real-time Computed Age Calculator on DOB
    const dobInput = document.getElementById('dob');
    const calcAgeInput = document.getElementById('calculated_age');

    function updateAgeDisplay() {
        if (!dobInput || !calcAgeInput) return;
        const val = dobInput.value;
        if (!val) {
            calcAgeInput.value = '';
            calcAgeInput.placeholder = 'Auto-calculated';
            calcAgeInput.classList.remove('text-danger', 'text-success', 'text-primary');
            calcAgeInput.classList.add('text-dark');
            return;
        }

        const dob = new Date(val);
        if (isNaN(dob.getTime())) {
            calcAgeInput.value = '';
            calcAgeInput.placeholder = 'Auto-calculated';
            return;
        }

        const today = new Date();
        if (dob > today) {
            calcAgeInput.value = 'Invalid: Future Date';
            calcAgeInput.classList.remove('text-dark', 'text-success', 'text-primary');
            calcAgeInput.classList.add('text-danger');
            return;
        }

        let years = today.getFullYear() - dob.getFullYear();
        let months = today.getMonth() - dob.getMonth();
        let days = today.getDate() - dob.getDate();

        if (days < 0) {
            months--;
        }
        if (months < 0) {
            years--;
            months += 12;
        }

        let text = '';
        if (years === 0) {
            if (months === 0) {
                const diffTime = Math.abs(today - dob);
                const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
                text = `${diffDays} day${diffDays === 1 ? '' : 's'} (Neonate)`;
            } else {
                text = `${months} mo${months === 1 ? '' : 's'} (Infant)`;
            }
        } else if (years <= 5) {
            text = `${years}y ${months}m (Well-Baby)`;
        } else if (years < 18) {
            text = `${years} yrs (Minor)`;
        } else if (years >= 60) {
            text = `${years} yrs (Senior Citizen)`;
        } else {
            text = `${years} yrs (Adult)`;
        }

        calcAgeInput.value = text;
        calcAgeInput.classList.remove('text-danger');
        calcAgeInput.classList.add('text-dark');
    }

    if (dobInput) {
        dobInput.addEventListener('input', updateAgeDisplay);
        dobInput.addEventListener('change', updateAgeDisplay);
        updateAgeDisplay();
    }

    // 3. Real-time Name Masking (Letters, spaces, hyphens, apostrophes, dots, ñ/Ñ only)
    const nameInputs = document.querySelectorAll('.name-input');
    nameInputs.forEach(input => {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/[^a-zA-ZñÑ\s\-\'\.]/g, '');
        });
    });

    // 4. Real-time Philippine Phone Number Normalization (+63 / 63 auto-converted to 09)
    const phoneInputs = document.querySelectorAll('.phone-input');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function() {
            let val = this.value.replace(/[^\d+]/g, '');
            if (val.startsWith('+63')) {
                val = '0' + val.substring(3);
            } else if (val.startsWith('63') && val.length > 10) {
                val = '0' + val.substring(2);
            }
            this.value = val.replace(/\D/g, '').substring(0, 11);
        });
    });

    // 5. PhilHealth Status Reactive Disablement
    const phicStatusSelect = document.getElementById('phic_status');
    const phicTypeSelect = document.getElementById('phic_type');
    const phicInput = document.getElementById('philhealth_no');
    const phicHelpText = document.getElementById('phicHelpText');

    function syncPhilHealthStatus() {
        if (!phicStatusSelect || !phicTypeSelect || !phicInput) return;
        const isNonMember = phicStatusSelect.value === 'Non-Member';

        phicTypeSelect.disabled = isNonMember;
        phicInput.disabled = isNonMember;

        if (isNonMember) {
            phicTypeSelect.value = '';
            phicInput.value = '';
            phicTypeSelect.classList.add('bg-light');
            phicInput.classList.add('bg-light');
            if (phicHelpText) {
                phicHelpText.textContent = 'Not applicable for Non-Member.';
            }
        } else {
            phicTypeSelect.classList.remove('bg-light');
            phicInput.classList.remove('bg-light');
            if (phicHelpText) {
                phicHelpText.textContent = 'Standard 12-digit PIN format (leave blank if none).';
            }
        }
    }

    if (phicStatusSelect) {
        phicStatusSelect.addEventListener('change', syncPhilHealthStatus);
        syncPhilHealthStatus();
    }

    // 6. Real-time PhilHealth PIN Masking (XX-XXXXXXXXX-X)
    if (phicInput) {
        phicInput.addEventListener('input', function(e) {
            let val = this.value.replace(/\D/g, '').substring(0, 12);
            let formatted = '';
            if (val.length > 0) formatted += val.substring(0, 2);
            if (val.length > 2) formatted += '-' + val.substring(2, 11);
            if (val.length > 11) formatted += '-' + val.substring(11, 12);
            this.value = formatted;
        });
    }

    // 7. Civil Status Toggle for 'Others'
    const civilStatusSelect = document.getElementById('civil_status');
    const civilStatusOther = document.getElementById('civil_status_other');
    const civilStatusOtherContainer = document.getElementById('civilStatusOtherContainer');

    function toggleCivilStatusOther() {
        if (civilStatusSelect && civilStatusOther) {
            if (civilStatusSelect.value === 'Others') {
                if (civilStatusOtherContainer) civilStatusOtherContainer.classList.remove('d-none');
                civilStatusOther.disabled = false;
                civilStatusOther.focus();
            } else {
                if (civilStatusOtherContainer) civilStatusOtherContainer.classList.add('d-none');
                civilStatusOther.disabled = true;
                civilStatusOther.value = '';
            }
        }
    }

    if (civilStatusSelect) {
        civilStatusSelect.addEventListener('change', toggleCivilStatusOther);
        toggleCivilStatusOther();
    }

    // 8. Accessible Error Anchor Click Handler
    document.querySelectorAll('.error-jump-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-target');
            const targetEl = document.getElementById(targetId);
            if (targetEl) {
                targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                targetEl.focus();
                targetEl.classList.add('is-invalid');
                setTimeout(() => {
                    targetEl.classList.remove('is-invalid');
                }, 3000);
            }
        });
    });

    // Helper: HTML Escape
    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Helper: Safe Bootstrap Modal Retrieval
    function getDuplicateModal() {
        const modalEl = document.getElementById('duplicateWarningModal');
        if (!modalEl) return null;
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            return bootstrap.Modal.getOrCreateInstance(modalEl);
        }
        return null;
    }

    // 9. AJAX Duplicate Check & Soft-Warning Modal
    const firstName = document.getElementById('first_name');
    const lastName = document.getElementById('last_name');
    const warningBanner = document.getElementById('duplicateWarningBanner');
    const duplicateBannerText = document.getElementById('duplicateBannerText');
    const btnViewDuplicates = document.getElementById('btnViewDuplicates');
    const duplicateModalTableBody = document.getElementById('duplicateModalTableBody');
    const btnProceedDuplicate = document.getElementById('btnProceedDuplicate');
    const ackInput = document.getElementById('duplicate_acknowledged');

    let duplicateDetected = false;
    let duplicateRecords = [];

    function populateDuplicateModal(data) {
        if (!duplicateModalTableBody) return;
        let html = '';
        data.forEach(p => {
            const fullName = [p.first_name, p.middle_name, p.last_name, p.suffix].filter(Boolean).join(' ');
            html += `
                <tr>
                    <td class="fw-bold text-primary font-monospace">${escapeHtml(p.patient_no)}</td>
                    <td class="fw-semibold text-dark">${escapeHtml(fullName)}</td>
                    <td>${escapeHtml(p.dob || '—')}</td>
                    <td>${escapeHtml(p.sex || '—')}</td>
                    <td>${escapeHtml(p.address || '—')}</td>
                    <td class="text-center">
                        <a href="<?= url('/patients/') ?>${p.id}" target="_blank" class="btn btn-outline-primary btn-sm py-0 px-2" title="Open record in new tab">
                            <i class="bi bi-box-arrow-up-right me-1"></i> View
                        </a>
                    </td>
                </tr>
            `;
        });
        duplicateModalTableBody.innerHTML = html;
        if (duplicateBannerText) {
            duplicateBannerText.textContent = `Found ${data.length} record(s) with matching name or birthdate.`;
        }
        if (warningBanner) {
            warningBanner.classList.remove('d-none');
        }
    }

    async function checkDuplicates() {
        const fn = firstName ? firstName.value.trim() : '';
        const ln = lastName ? lastName.value.trim() : '';
        const dobVal = dobInput ? dobInput.value.trim() : '';

        // Trigger if last name is at least 2 chars AND (first name is at least 2 chars OR DOB is set)
        if (ln.length >= 2 && (fn.length >= 2 || dobVal.length > 0)) {
            try {
                const res = await fetch(`<?= url('/patients/check-duplicate') ?>?first_name=${encodeURIComponent(fn)}&last_name=${encodeURIComponent(ln)}&dob=${encodeURIComponent(dobVal)}`);
                if (res.ok) {
                    const data = await res.json();
                    if (Array.isArray(data) && data.length > 0) {
                        duplicateDetected = true;
                        duplicateRecords = data;
                        populateDuplicateModal(data);
                        return data;
                    }
                }
            } catch (err) {
                console.error('Duplicate check error:', err);
            }
        }

        duplicateDetected = false;
        duplicateRecords = [];
        if (warningBanner) {
            warningBanner.classList.add('d-none');
        }
        return [];
    }

    // Trigger check on blur and change
    if (firstName) firstName.addEventListener('blur', checkDuplicates);
    if (lastName) lastName.addEventListener('blur', checkDuplicates);
    if (dobInput) dobInput.addEventListener('change', checkDuplicates);

    // Reset acknowledgment if user modifies personal identity fields
    [firstName, lastName, dobInput].forEach(el => {
        if (el) {
            el.addEventListener('input', function() {
                if (ackInput) ackInput.value = '0';
            });
        }
    });

    if (btnViewDuplicates) {
        btnViewDuplicates.addEventListener('click', function() {
            const modal = getDuplicateModal();
            if (modal) {
                modal.show();
            }
        });
    }

    // Intercept form submit to ensure duplicates are checked and warned before saving
    if (patientForm) {
        patientForm.addEventListener('submit', async function(e) {
            if (ackInput && ackInput.value === '1') {
                return; // User explicitly clicked "Proceed with Registration" in the modal
            }

            e.preventDefault();

            const fn = firstName ? firstName.value.trim() : '';
            const ln = lastName ? lastName.value.trim() : '';
            const dobVal = dobInput ? dobInput.value.trim() : '';

            if (ln.length >= 2 && (fn.length >= 2 || dobVal.length > 0)) {
                const btnSubmit = document.getElementById('btnSubmitPatient');
                const origHtml = btnSubmit ? btnSubmit.innerHTML : '';
                if (btnSubmit) {
                    btnSubmit.disabled = true;
                    btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Checking...';
                }

                const data = await checkDuplicates();

                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = origHtml;
                }

                if (data && data.length > 0) {
                    const modal = getDuplicateModal();
                    if (modal) {
                        modal.show();
                    }
                    return false;
                }
            }

            // No duplicate detected -> submit normally
            if (ackInput) ackInput.value = '1';
            if (patientForm.checkValidity()) {
                patientForm.submit();
            } else {
                patientForm.reportValidity();
            }
        });
    }

    if (btnProceedDuplicate) {
        btnProceedDuplicate.addEventListener('click', function() {
            if (ackInput) {
                ackInput.value = '1';
            }
            const modal = getDuplicateModal();
            if (modal) {
                modal.hide();
            }
            if (patientForm) {
                if (patientForm.checkValidity()) {
                    patientForm.submit();
                } else {
                    patientForm.reportValidity();
                }
            }
        });
    }

    // Auto-open modal if server-side validation caught a duplicate
    const serverDuplicates = <?= json_encode($duplicateWarnings ?? []) ?>;
    if (Array.isArray(serverDuplicates) && serverDuplicates.length > 0) {
        duplicateDetected = true;
        duplicateRecords = serverDuplicates;
        populateDuplicateModal(serverDuplicates);
        const modal = getDuplicateModal();
        if (modal) {
            modal.show();
        }
    }
});
</script>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

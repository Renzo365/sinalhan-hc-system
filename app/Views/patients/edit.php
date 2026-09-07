<?php
$title = 'Edit Patient Demographics';
$breadcrumbs = [
    'Patients' => '/patients',
    'Profile' => '/patients/' . $patient['id'],
    'Edit Demographics' => null
];
require dirname(__DIR__) . '/layout/header.php';
?>

<div class="patient-form-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h3 mb-1 fw-bold text-primary-dark">Edit Demographics</h2>
            <p class="text-secondary small mb-0">Modify patient identity, household, socioeconomic, and family contact details.</p>
        </div>
        <a href="<?= url('/patients/' . $patient['id']) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Profile
        </a>
    </div>

    <!-- Errors Alert Box -->
    <?php if (isset($errors) && !empty($errors)): ?>
        <div class="alert alert-danger mb-4 shadow-sm" role="alert">
            <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Please correct the following errors:</div>
            <ul class="mb-0 ps-3 small">
                <?php foreach ($errors as $err): ?>
                    <li><?= h($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="<?= url('/patients/' . $patient['id']) ?>" method="POST" id="patientEditForm" autocomplete="off">
        <?= csrf_field() ?>

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
                    <!-- Row 1: Last Name & First Name -->
                    <div class="col-12 col-md-6">
                        <label for="last_name" class="form-label fw-semibold text-secondary small">Last Name <span class="text-danger">*</span></label>
                        <input type="text" 
                               name="last_name" 
                               id="last_name" 
                               class="form-control name-input" 
                               value="<?= h($patient['last_name']) ?>" 
                               placeholder="e.g. Dela Cruz"
                               maxlength="50"
                               minlength="2"
                               required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="first_name" class="form-label fw-semibold text-secondary small">First Name <span class="text-danger">*</span></label>
                        <input type="text" 
                               name="first_name" 
                               id="first_name" 
                               class="form-control name-input" 
                               value="<?= h($patient['first_name']) ?>" 
                               placeholder="e.g. Juan"
                               maxlength="50"
                               minlength="2"
                               required>
                    </div>

                    <!-- Row 2: Middle Name & Extension -->
                    <div class="col-12 col-md-6">
                        <label for="middle_name" class="form-label fw-semibold text-secondary small">Middle Name</label>
                        <input type="text" 
                               name="middle_name" 
                               id="middle_name" 
                               class="form-control name-input" 
                               placeholder="e.g. Mercado"
                               maxlength="50"
                               value="<?= h($patient['middle_name'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="suffix" class="form-label fw-semibold text-secondary small">Extension (Sr., Jr., III, etc.)</label>
                        <input type="text" 
                               name="suffix" 
                               id="suffix" 
                               class="form-control" 
                               placeholder="e.g. Jr., III"
                               maxlength="20"
                               value="<?= h($patient['suffix'] ?? '') ?>">
                    </div>

                    <!-- Row 3: Date of Birth, Biological Sex, Civil Status, Blood Type -->
                    <div class="col-12 col-sm-6 col-md-3">
                        <label for="dob" class="form-label fw-semibold text-secondary small">Date of Birth <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-calendar-date"></i></span>
                            <input type="text" 
                                   name="dob" 
                                   id="dob" 
                                   class="form-control bg-white dob-picker" 
                                   placeholder="YYYY-MM-DD" 
                                   value="<?= h($patient['dob']) ?>" 
                                   required>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-md-3">
                        <label for="sex" class="form-label fw-semibold text-secondary small">Biological Sex <span class="text-danger">*</span></label>
                        <select name="sex" id="sex" class="form-select" required>
                            <option value="Male" <?= $patient['sex'] === 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= $patient['sex'] === 'Female' ? 'selected' : '' ?>>Female</option>
                        </select>
                    </div>

                    <div class="col-12 col-sm-6 col-md-3">
                        <label for="civil_status" class="form-label fw-semibold text-secondary small">Civil Status <span class="text-danger">*</span></label>
                        <select name="civil_status" id="civil_status" class="form-select" required>
                            <?php foreach (['Single', 'Married', 'Widow/Widower', 'Annulled', 'Separated', 'Others'] as $status): ?>
                                <option value="<?= $status ?>" <?= ($patient['civil_status'] ?? '') === $status ? 'selected' : '' ?>>
                                    <?= $status ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-sm-6 col-md-3">
                        <label for="blood_type" class="form-label fw-semibold text-secondary small">Blood Type</label>
                        <select name="blood_type" id="blood_type" class="form-select">
                            <?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'Unknown'] as $bt): ?>
                                <option value="<?= $bt ?>" <?= ($patient['blood_type'] ?? 'Unknown') === $bt ? 'selected' : '' ?>>
                                    <?= $bt ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Row 4: Religion & Specify Civil Status -->
                    <div class="col-12 col-md-6">
                        <label for="religion" class="form-label fw-semibold text-secondary small">Religion</label>
                        <input type="text" 
                               name="religion" 
                               id="religion" 
                               class="form-control" 
                               placeholder="e.g. Roman Catholic, INC, Islam" 
                               maxlength="100"
                               value="<?= h($patient['religion'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="civil_status_other" class="form-label fw-semibold text-secondary small">Specify Civil Status (if applicable)</label>
                        <input type="text" 
                               name="civil_status_other" 
                               id="civil_status_other" 
                               class="form-control" 
                               placeholder="Please specify if Others is selected" 
                               maxlength="100"
                               value="<?= h($patient['civil_status_other'] ?? '') ?>"
                               <?= ($patient['civil_status'] ?? '') === 'Others' ? '' : 'disabled' ?>>
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
                        <label for="family_no" class="form-label fw-semibold text-secondary small">
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
                                   value="<?= h($patient['family_no'] ?? '') ?>">
                        </div>
                        <div class="form-text small text-muted">Links household members together in the directory.</div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="contact_no" class="form-label fw-semibold text-secondary small">Contact No. (Mobile)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-telephone"></i></span>
                            <input type="tel" 
                                   name="contact_no" 
                                   id="contact_no" 
                                   class="form-control phone-input" 
                                   placeholder="09171234567" 
                                   maxlength="11"
                                   value="<?= h($patient['contact_no'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Row 2: Barangay & Street Address -->
                    <div class="col-12 col-md-6">
                        <label for="barangay" class="form-label fw-semibold text-secondary small">Barangay <span class="text-danger">*</span></label>
                        <select name="barangay" id="barangay" class="form-select" required>
                            <?php 
                            $brgys = ['Sinalhan', 'Aplaya', 'Caingin', 'Dila', 'Dita', 'Don Jose', 'Ibaba', 'Labas', 'Macabling', 'Malitlit', 'Market Area', 'Pooc', 'Pulong Santa Cruz', 'Santo Domingo', 'Tagapo', 'Other'];
                            foreach ($brgys as $brgy): ?>
                                <option value="<?= $brgy ?>" <?= ($patient['barangay'] ?? '') === $brgy ? 'selected' : '' ?>>
                                    <?= $brgy . ($brgy === 'Sinalhan' ? ' (Catchment Area)' : '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="address" class="form-label fw-semibold text-secondary small">House No. / Street / Purok <span class="text-danger">*</span></label>
                        <input type="text" 
                               name="address" 
                               id="address" 
                               class="form-control" 
                               placeholder="e.g. Blk 4 Lot 12 Purok 3" 
                               maxlength="255" 
                               value="<?= h($patient['address'] ?? '') ?>" 
                               required>
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
                        <label for="phic_status" class="form-label fw-semibold text-secondary small">PhilHealth Status <span class="text-danger">*</span></label>
                        <select name="phic_status" id="phic_status" class="form-select" required>
                            <option value="Member" <?= ($patient['phic_status'] ?? '') === 'Member' ? 'selected' : '' ?>>Member</option>
                            <option value="Dependent" <?= ($patient['phic_status'] ?? '') === 'Dependent' ? 'selected' : '' ?>>Dependent</option>
                            <option value="Non-Member" <?= ($patient['phic_status'] ?? 'Non-Member') === 'Non-Member' ? 'selected' : '' ?>>Non-Member</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="phic_type" class="form-label fw-semibold text-secondary small">PHIC Category / Type</label>
                        <select name="phic_type" id="phic_type" class="form-select">
                            <option value="">-- Select Category --</option>
                            <?php 
                            $phicTypes = ['Sponsored - NHTS', 'Sponsored - LGU', 'Employed - Private', 'Employed - Government', 'Indigent', 'Senior Citizen', 'Lifetime Member', 'Informal / Self-Earning', 'IPP - OFW', 'Others'];
                            foreach ($phicTypes as $pt): ?>
                                <option value="<?= $pt ?>" <?= ($patient['phic_type'] ?? '') === $pt ? 'selected' : '' ?>>
                                    <?= $pt ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Row 2: PIN & Educational Attainment -->
                    <div class="col-12 col-md-6">
                        <label for="philhealth_no" class="form-label fw-semibold text-secondary small">PhilHealth Identification No. (PIN)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-credit-card-2-front"></i></span>
                            <input type="text" 
                                   name="philhealth_no" 
                                   id="philhealth_no" 
                                   class="form-control phic-mask" 
                                   placeholder="XX-XXXXXXXXX-X" 
                                   maxlength="14"
                                   value="<?= h($patient['philhealth_no'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="education_attainment" class="form-label fw-semibold text-secondary small">Educational Attainment</label>
                        <select name="education_attainment" id="education_attainment" class="form-select">
                            <option value="">-- Select Education --</option>
                            <?php foreach (['No Schooling', 'Elementary', 'High School', 'Vocational', 'College degree, post graduate'] as $edu): ?>
                                <option value="<?= $edu ?>" <?= ($patient['education_attainment'] ?? '') === $edu ? 'selected' : '' ?>>
                                    <?= $edu ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Row 3: Occupation -->
                    <div class="col-12 col-md-6">
                        <label for="occupation" class="form-label fw-semibold text-secondary small">Occupation</label>
                        <input type="text" 
                               name="occupation" 
                               id="occupation" 
                               class="form-control" 
                               placeholder="e.g. Vendor, Driver, Student" 
                               maxlength="100"
                               value="<?= h($patient['occupation'] ?? '') ?>">
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
                    <!-- Row 1: Father's Name & Father's DOB -->
                    <div class="col-12 col-md-7">
                        <label for="father_name" class="form-label fw-semibold text-secondary small">Father's Full Name</label>
                        <input type="text" 
                               name="father_name" 
                               id="father_name" 
                               class="form-control name-input" 
                               placeholder="Full Name of Father" 
                               maxlength="150"
                               value="<?= h($patient['father_name'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-md-5">
                        <label for="father_dob" class="form-label fw-semibold text-secondary small">Father's Date of Birth</label>
                        <input type="text" 
                               name="father_dob" 
                               id="father_dob" 
                               class="form-control dob-picker" 
                               placeholder="YYYY-MM-DD" 
                               value="<?= h($patient['father_dob'] ?? '') ?>">
                    </div>

                    <!-- Row 2: Mother's Name & Mother's DOB -->
                    <div class="col-12 col-md-7">
                        <label for="mother_name" class="form-label fw-semibold text-secondary small">Mother's Maiden Name</label>
                        <input type="text" 
                               name="mother_name" 
                               id="mother_name" 
                               class="form-control name-input" 
                               placeholder="Maiden Name of Mother" 
                               maxlength="150"
                               value="<?= h($patient['mother_name'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-md-5">
                        <label for="mother_dob" class="form-label fw-semibold text-secondary small">Mother's Date of Birth</label>
                        <input type="text" 
                               name="mother_dob" 
                               id="mother_dob" 
                               class="form-control dob-picker" 
                               placeholder="YYYY-MM-DD" 
                               value="<?= h($patient['mother_dob'] ?? '') ?>">
                    </div>

                    <!-- Row 3: Spouse's Name & Spouse's DOB -->
                    <div class="col-12 col-md-7">
                        <label for="spouse_name" class="form-label fw-semibold text-secondary small">Spouse's Full Name</label>
                        <input type="text" 
                               name="spouse_name" 
                               id="spouse_name" 
                               class="form-control name-input" 
                               placeholder="Full Name of Spouse (if married)" 
                               maxlength="150"
                               value="<?= h($patient['spouse_name'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-md-5">
                        <label for="spouse_dob" class="form-label fw-semibold text-secondary small">Spouse's Date of Birth</label>
                        <input type="text" 
                               name="spouse_dob" 
                               id="spouse_dob" 
                               class="form-control dob-picker" 
                               placeholder="YYYY-MM-DD" 
                               value="<?= h($patient['spouse_dob'] ?? '') ?>">
                    </div>

                    <div class="col-12"><hr class="my-2 border-light-subtle"></div>

                    <!-- Row 4: Emergency Contacts -->
                    <div class="col-12 col-md-4">
                        <label for="emergency_name" class="form-label fw-semibold text-secondary small">Emergency Contact Person</label>
                        <input type="text" 
                               name="emergency_name" 
                               id="emergency_name" 
                               class="form-control name-input" 
                               placeholder="e.g. Maria Dela Cruz" 
                               maxlength="100"
                               value="<?= h($patient['emergency_name'] ?? '') ?>">
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="emergency_relationship" class="form-label fw-semibold text-secondary small">Relationship</label>
                        <input type="text" 
                               name="emergency_relationship" 
                               id="emergency_relationship" 
                               class="form-control" 
                               placeholder="e.g. Mother, Spouse, Sister" 
                               maxlength="50"
                               value="<?= h($patient['emergency_relationship'] ?? '') ?>">
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="emergency_no" class="form-label fw-semibold text-secondary small">Emergency Phone</label>
                        <input type="tel" 
                               name="emergency_no" 
                               id="emergency_no" 
                               class="form-control phone-input" 
                               placeholder="09187654321" 
                               maxlength="11"
                               value="<?= h($patient['emergency_no'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit & Navigation Action Bar (Sticky floating bar) -->
        <div class="card card-premium sticky-form-action-bar mb-4 shadow">
            <div class="card-body p-3 px-md-4 py-md-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="text-muted small">
                    <i class="bi bi-info-circle me-1 text-primary"></i> Fields marked with (<span class="text-danger">*</span>) are mandatory.
                </span>
                <div class="d-flex gap-3">
                    <a href="<?= url('/patients/' . $patient['id']) ?>" class="btn btn-light px-4">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold" id="btnUpdatePatient">
                        <i class="bi bi-check2-circle me-1 fs-5 align-middle"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>
    </form>
</div> <!-- Close patient-form-wrapper -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Initialize Flatpickr for Date Inputs
    if (typeof flatpickr !== 'undefined') {
        flatpickr(".dob-picker", {
            dateFormat: "Y-m-d",
            maxDate: "today",
            allowInput: true
        });
    }

    // 2. Real-time Name Masking (Letters, spaces, hyphens, apostrophes, dots, ñ/Ñ only)
    const nameInputs = document.querySelectorAll('.name-input');
    nameInputs.forEach(input => {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/[^a-zA-ZñÑ\s\-\'\.]/g, '');
        });
    });

    // 3. Real-time Philippine Phone Number Masking (Digits only, max 11)
    const phoneInputs = document.querySelectorAll('.phone-input');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').substring(0, 11);
        });
    });

    // 4. Real-time PhilHealth PIN Masking (XX-XXXXXXXXX-X)
    const phicInput = document.getElementById('philhealth_no');
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

    // 5. Civil Status Toggle for 'Others'
    const civilStatusSelect = document.getElementById('civil_status');
    const civilStatusOther = document.getElementById('civil_status_other');

    function toggleCivilStatusOther() {
        if (civilStatusSelect && civilStatusOther) {
            if (civilStatusSelect.value === 'Others') {
                civilStatusOther.disabled = false;
                civilStatusOther.focus();
            } else {
                civilStatusOther.disabled = true;
                civilStatusOther.value = '';
            }
        }
    }

    if (civilStatusSelect) {
        civilStatusSelect.addEventListener('change', toggleCivilStatusOther);
    }
});
</script>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

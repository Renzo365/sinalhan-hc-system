<?php
$title = 'Register Patient';
$breadcrumbs = [
    'Patients' => '/patients',
    'Register Patient' => null
];
require dirname(__DIR__) . '/layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h3 mb-1 fw-bold text-primary-dark">Register Patient</h2>
        <p class="text-secondary small mb-0">Enter demographic details to create a new patient record.</p>
    </div>
    <a href="<?= url('/patients') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Patients List
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

<!-- Duplicate Name AJAX Warning Banner -->
<div class="alert alert-warning mb-4 shadow-sm d-none" id="duplicateWarningBanner" role="alert">
    <div class="fw-bold mb-2"><i class="bi bi-exclamation-circle-fill me-2"></i>Possible Duplicate Patient Found!</div>
    <p class="small mb-2">The system detected existing active record(s) with the exact same name:</p>
    <div id="duplicateList" class="small fw-semibold ps-3 mb-2"></div>
    <p class="small mb-0 text-muted">Please confirm that you are registering a different person before saving.</p>
</div>

<form action="<?= url('/patients') ?>" method="POST" id="patientForm" autocomplete="off">
    <?= csrf_field() ?>

    <div class="row g-4">
        <!-- 1. Personal Information -->
        <div class="col-12 col-lg-8">
            <div class="card card-premium mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h3 class="card-title h6 mb-0 fw-bold text-dark">
                        <i class="bi bi-person-fill text-primary me-2"></i>Personal Information
                    </h3>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <!-- First Name -->
                        <div class="col-12 col-sm-4">
                            <label for="first_name" class="form-label fw-semibold text-secondary small">First Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   name="first_name" 
                                   id="first_name" 
                                   class="form-control" 
                                   value="<?= h($input['first_name'] ?? '') ?>" 
                                   required>
                        </div>
                        
                        <!-- Middle Name -->
                        <div class="col-12 col-sm-4">
                            <label for="middle_name" class="form-label fw-semibold text-secondary small">Middle Name</label>
                            <input type="text" 
                                   name="middle_name" 
                                   id="middle_name" 
                                   class="form-control" 
                                   value="<?= h($input['middle_name'] ?? '') ?>">
                        </div>

                        <!-- Last Name -->
                        <div class="col-12 col-sm-4">
                            <label for="last_name" class="form-label fw-semibold text-secondary small">Last Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   name="last_name" 
                                   id="last_name" 
                                   class="form-control" 
                                   value="<?= h($input['last_name'] ?? '') ?>" 
                                   required>
                        </div>

                        <!-- Date of Birth -->
                        <div class="col-12 col-sm-4">
                            <label for="dob" class="form-label fw-semibold text-secondary small">Date of Birth <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-calendar-date"></i></span>
                                <input type="text" 
                                       name="dob" 
                                       id="dob" 
                                       class="form-control bg-white" 
                                       placeholder="Select date" 
                                       value="<?= h($input['dob'] ?? '') ?>" 
                                       required>
                            </div>
                        </div>

                        <!-- Sex -->
                        <div class="col-12 col-sm-4">
                            <label for="sex" class="form-label fw-semibold text-secondary small">Biological Sex <span class="text-danger">*</span></label>
                            <select name="sex" id="sex" class="form-select" required>
                                <option value="" disabled selected>Select sex</option>
                                <option value="Male" <?= ($input['sex'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= ($input['sex'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                            </select>
                        </div>

                        <!-- Civil Status -->
                        <div class="col-12 col-sm-4">
                            <label for="civil_status" class="form-label fw-semibold text-secondary small">Civil Status <span class="text-danger">*</span></label>
                            <select name="civil_status" id="civil_status" class="form-select" required>
                                <option value="" disabled selected>Select status</option>
                                <?php $statuses = ['Single', 'Married', 'Widowed', 'Divorced', 'Separated'];
                                foreach ($statuses as $status): ?>
                                    <option value="<?= $status ?>" <?= ($input['civil_status'] ?? '') === $status ? 'selected' : '' ?>>
                                        <?= $status ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Contact and Address -->
            <div class="card card-premium">
                <div class="card-header bg-white py-3 border-bottom">
                    <h3 class="card-title h6 mb-0 fw-bold text-dark">
                        <i class="bi bi-geo-alt-fill text-primary me-2"></i>Contact & Address
                    </h3>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <!-- Contact Number -->
                        <div class="col-12 col-sm-4">
                            <label for="contact_no" class="form-label fw-semibold text-secondary small">Primary Contact No.</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-telephone"></i></span>
                                <input type="text" 
                                       name="contact_no" 
                                       id="contact_no" 
                                       class="form-control" 
                                       placeholder="e.g. 0917XXXXXXX" 
                                       value="<?= h($input['contact_no'] ?? '') ?>">
                            </div>
                        </div>

                        <!-- Barangay -->
                        <div class="col-12 col-sm-8">
                            <label for="barangay" class="form-label fw-semibold text-secondary small">Barangay <span class="text-danger">*</span></label>
                            <input type="text" 
                                   name="barangay" 
                                   id="barangay" 
                                   class="form-control" 
                                   value="<?= h($input['barangay'] ?? 'Sinalhan') ?>" 
                                   required>
                        </div>

                        <!-- Complete Address -->
                        <div class="col-12">
                            <label for="address" class="form-label fw-semibold text-secondary small">Complete Address <span class="text-danger">*</span></label>
                            <textarea name="address" 
                                      id="address" 
                                      rows="3" 
                                      class="form-control" 
                                      placeholder="House no., street, block, subdivision..." 
                                      required><?= h($input['address'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side Panel: emergency & identifiers -->
        <div class="col-12 col-lg-4">
            <!-- 3. Emergency Contact -->
            <div class="card card-premium mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h3 class="card-title h6 mb-0 fw-bold text-dark">
                        <i class="bi bi-exclamation-circle text-primary me-2"></i>Emergency Contact
                    </h3>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <!-- Contact Person Name -->
                        <div class="col-12">
                            <label for="emergency_name" class="form-label fw-semibold text-secondary small">Contact Person Name</label>
                            <input type="text" 
                                   name="emergency_name" 
                                   id="emergency_name" 
                                   class="form-control" 
                                   placeholder="Full Name" 
                                   value="<?= h($input['emergency_name'] ?? '') ?>">
                        </div>

                        <!-- Contact Person Number -->
                        <div class="col-12">
                            <label for="emergency_no" class="form-label fw-semibold text-secondary small">Contact Person Number</label>
                            <input type="text" 
                                   name="emergency_no" 
                                   id="emergency_no" 
                                   class="form-control" 
                                   placeholder="Phone Number" 
                                   value="<?= h($input['emergency_no'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Identifiers -->
            <div class="card card-premium mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h3 class="card-title h6 mb-0 fw-bold text-dark">
                        <i class="bi bi-card-heading text-primary me-2"></i>Health Identifiers
                    </h3>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <!-- PhilHealth No -->
                        <div class="col-12">
                            <label for="philhealth_no" class="form-label fw-semibold text-secondary small">PhilHealth ID No.</label>
                            <input type="text" 
                                   name="philhealth_no" 
                                   id="philhealth_no" 
                                   class="form-control" 
                                   placeholder="XX-XXXXXXXXX-X" 
                                   value="<?= h($input['philhealth_no'] ?? '') ?>">
                            <div class="form-text text-muted small">Enter PhilHealth number if available.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Action Card -->
            <div class="card card-premium bg-light border-0">
                <div class="card-body p-4">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary py-2 fw-semibold shadow-sm">
                            <i class="bi bi-save me-2"></i>Save Patient
                        </button>
                        <a href="<?= url('/patients') ?>" class="btn btn-outline-secondary py-2 fw-semibold">
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize flatpickr on DOB
    flatpickr("#dob", {
        dateFormat: "Y-m-d",
        maxDate: "today",
        allowInput: true
    });

    // AJAX duplicate checking
    const firstNameInput = document.getElementById('first_name');
    const lastNameInput = document.getElementById('last_name');
    const warningBanner = document.getElementById('duplicateWarningBanner');
    const duplicateList = document.getElementById('duplicateList');

    function checkDuplicateName() {
        const fname = firstNameInput.value.trim();
        const lname = lastNameInput.value.trim();

        if (fname.length > 0 && lname.length > 0) {
            const checkUrl = `<?= url('/patients/check-duplicate') ?>?first_name=${encodeURIComponent(fname)}&last_name=${encodeURIComponent(lname)}`;
            
            fetch(checkUrl)
                .then(response => response.json())
                .then(data => {
                    if (data && data.length > 0) {
                        duplicateList.innerHTML = '';
                        data.forEach(match => {
                            const li = document.createElement('div');
                            li.className = 'mb-1';
                            li.innerHTML = `<i class="bi bi-person-fill-exclamation me-1"></i> ${match.patient_no} &bull; ${match.last_name}, ${match.first_name} (${match.sex}, born ${match.dob}) resident of Brgy. ${match.barangay}`;
                            duplicateList.appendChild(li);
                        });
                        warningBanner.classList.remove('d-none');
                    } else {
                        warningBanner.classList.add('d-none');
                        duplicateList.innerHTML = '';
                    }
                })
                .catch(err => console.error('Failed to run duplicate name check:', err));
        }
    }

    if (firstNameInput && lastNameInput) {
        firstNameInput.addEventListener('blur', checkDuplicateName);
        lastNameInput.addEventListener('blur', checkDuplicateName);
    }
});
</script>

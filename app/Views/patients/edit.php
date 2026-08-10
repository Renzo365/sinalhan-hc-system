<?php
$title = 'Edit Patient Demographics';
$breadcrumbs = [
    'Patients' => '/patients',
    'Profile' => '/patients/' . $patient['id'],
    'Edit Demographics' => null
];
require dirname(__DIR__) . '/layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h3 mb-1 fw-bold text-primary-dark">Edit Demographics</h2>
        <p class="text-secondary small mb-0">Modify patient contact details, address, or basic clinical identity.</p>
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

<form action="<?= url('/patients/' . $patient['id']) ?>" method="POST" autocomplete="off">
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
                                   class="form-control name-input" 
                                   value="<?= h($patient['first_name']) ?>" 
                                   pattern="[a-zA-ZñÑ\s\-\'\.]{2,50}"
                                   maxlength="50"
                                   minlength="2"
                                   required>
                        </div>
                        
                        <!-- Middle Name -->
                        <div class="col-12 col-sm-4">
                            <label for="middle_name" class="form-label fw-semibold text-secondary small">Middle Name</label>
                            <input type="text" 
                                   name="middle_name" 
                                   id="middle_name" 
                                   class="form-control name-input" 
                                   pattern="[a-zA-ZñÑ\s\-\'\.]{2,50}"
                                   maxlength="50"
                                   value="<?= h($patient['middle_name'] ?? '') ?>">
                        </div>

                        <!-- Last Name -->
                        <div class="col-12 col-sm-4">
                            <label for="last_name" class="form-label fw-semibold text-secondary small">Last Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   name="last_name" 
                                   id="last_name" 
                                   class="form-control name-input" 
                                   value="<?= h($patient['last_name']) ?>" 
                                   pattern="[a-zA-ZñÑ\s\-\'\.]{2,50}"
                                   maxlength="50"
                                   minlength="2"
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
                                       placeholder="YYYY-MM-DD" 
                                       value="<?= h($patient['dob']) ?>" 
                                       required>
                            </div>
                        </div>

                        <!-- Sex -->
                        <div class="col-12 col-sm-4">
                            <label for="sex" class="form-label fw-semibold text-secondary small">Biological Sex <span class="text-danger">*</span></label>
                            <select name="sex" id="sex" class="form-select" required>
                                <option value="Male" <?= $patient['sex'] === 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= $patient['sex'] === 'Female' ? 'selected' : '' ?>>Female</option>
                            </select>
                        </div>

                        <!-- Civil Status -->
                        <div class="col-12 col-sm-4">
                            <label for="civil_status" class="form-label fw-semibold text-secondary small">Civil Status <span class="text-danger">*</span></label>
                            <select name="civil_status" id="civil_status" class="form-select" required>
                                <?php $statuses = ['Single', 'Married', 'Widowed', 'Divorced', 'Separated'];
                                foreach ($statuses as $status): ?>
                                    <option value="<?= $status ?>" <?= $patient['civil_status'] === $status ? 'selected' : '' ?>>
                                        <?= $status ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Blood Type -->
                        <div class="col-12 col-sm-4">
                            <label for="blood_type" class="form-label fw-semibold text-secondary small">Blood Type</label>
                            <select name="blood_type" id="blood_type" class="form-select">
                                <option value="Unknown" <?= ($patient['blood_type'] ?? 'Unknown') === 'Unknown' ? 'selected' : '' ?>>Unknown / Unspecified</option>
                                <?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bt): ?>
                                    <option value="<?= $bt ?>" <?= ($patient['blood_type'] ?? '') === $bt ? 'selected' : '' ?>><?= $bt ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Occupation -->
                        <div class="col-12 col-sm-8">
                            <label for="occupation" class="form-label fw-semibold text-secondary small">Occupation / Employment Status</label>
                            <input type="text" 
                                   name="occupation" 
                                   id="occupation" 
                                   class="form-control" 
                                   placeholder="e.g. Self-Employed, Student, Vendor" 
                                   maxlength="100" 
                                   value="<?= h($patient['occupation'] ?? '') ?>">
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
                                       class="form-control phone-input" 
                                       placeholder="09XXXXXXXXX" 
                                       inputmode="numeric"
                                       maxlength="11"
                                       pattern="09[0-9]{9}"
                                       value="<?= h($patient['contact_no'] ?? '') ?>">
                            </div>
                            <div class="form-text text-muted small">11-digit mobile (09XXXXXXXXX)</div>
                        </div>

                        <!-- Barangay -->
                        <div class="col-12 col-sm-8">
                            <label for="barangay" class="form-label fw-semibold text-secondary small">Barangay <span class="text-danger">*</span></label>
                            <input type="text" 
                                   name="barangay" 
                                   id="barangay" 
                                   class="form-control" 
                                   value="<?= h($patient['barangay']) ?>" 
                                   maxlength="100"
                                   minlength="2"
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
                                      minlength="5"
                                      maxlength="500"
                                      required><?= h($patient['address']) ?></textarea>
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
                                   class="form-control name-input" 
                                   placeholder="Full Name" 
                                   maxlength="100"
                                   value="<?= h($patient['emergency_name'] ?? '') ?>">
                        </div>

                        <!-- Emergency Relationship -->
                        <div class="col-12">
                            <label for="emergency_relationship" class="form-label fw-semibold text-secondary small">Relationship to Patient</label>
                            <input type="text" 
                                   name="emergency_relationship" 
                                   id="emergency_relationship" 
                                   class="form-control" 
                                   placeholder="e.g. Spouse, Mother, Guardian" 
                                   maxlength="50"
                                   value="<?= h($patient['emergency_relationship'] ?? '') ?>">
                        </div>

                        <!-- Contact Person Number -->
                        <div class="col-12">
                            <label for="emergency_no" class="form-label fw-semibold text-secondary small">Contact Person Number</label>
                            <input type="text" 
                                   name="emergency_no" 
                                   id="emergency_no" 
                                   class="form-control phone-input" 
                                   placeholder="09XXXXXXXXX" 
                                   inputmode="numeric"
                                   maxlength="11"
                                   pattern="09[0-9]{9}"
                                   value="<?= h($patient['emergency_no'] ?? '') ?>">
                            <div class="form-text text-muted small">11-digit mobile (09XXXXXXXXX)</div>
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
                                   maxlength="14"
                                   pattern="\d{2}-\d{9}-\d{1}"
                                   value="<?= h($patient['philhealth_no'] ?? '') ?>">
                            <div class="form-text text-muted small">Format: XX-XXXXXXXXX-X</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Action Card -->
            <div class="card card-premium bg-light border-0">
                <div class="card-body p-4">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary py-2 fw-semibold shadow-sm">
                            <i class="bi bi-save me-2"></i>Save Changes
                        </button>
                        <a href="<?= url('/patients/' . $patient['id']) ?>" class="btn btn-outline-secondary py-2 fw-semibold">
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
        minDate: "1900-01-01",
        allowInput: true
    });

    // Real-time Keystroke Filters
    
    // 1. Name inputs: Block numeric digits and special symbols
    const nameFields = document.querySelectorAll('.name-input');
    nameFields.forEach(field => {
        field.addEventListener('input', function() {
            this.value = this.value.replace(/[0-9!@#$%^&*()_+=~`{}|\\:;<>?\/]/g, '');
        });
    });

    // 2. Mobile Phone inputs: Restrict to numeric digits only and cap at 11 chars
    const phoneFields = document.querySelectorAll('.phone-input');
    phoneFields.forEach(field => {
        field.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length > 11) {
                this.value = this.value.slice(0, 11);
            }
        });
    });

    // 3. PhilHealth ID Auto-Formatter Mask (XX-XXXXXXXXX-X)
    const philhealthInput = document.getElementById('philhealth_no');
    if (philhealthInput) {
        philhealthInput.addEventListener('input', function() {
            let val = this.value.replace(/[^0-9]/g, '');
            if (val.length > 12) val = val.slice(0, 12);
            
            let formatted = '';
            if (val.length > 0) {
                formatted += val.slice(0, 2);
            }
            if (val.length > 2) {
                formatted += '-' + val.slice(2, 11);
            }
            if (val.length > 11) {
                formatted += '-' + val.slice(11, 12);
            }
            this.value = formatted;
        });
    }
});
</script>

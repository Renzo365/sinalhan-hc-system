<?php
$title = 'Reschedule Appointment';
$breadcrumbs = [
    'Appointments' => '/appointments',
    'Edit Appointment' => null
];
require dirname(__DIR__) . '/layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h3 mb-1 fw-bold text-primary-dark">Reschedule / Edit Appointment</h2>
        <p class="text-secondary small mb-0">Modify appointment timing, change booking status, or append clinical notes.</p>
    </div>
    <a href="<?= url('/appointments') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Appointments List
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <!-- Error Alert Banner -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger shadow-sm border-0 mb-4" role="alert" style="border-radius: 12px;">
                <h5 class="fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>Please correct the following:</h5>
                <ul class="mb-0 small ps-3">
                    <?php foreach ($errors as $error): ?>
                        <li><?= h($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Form Card -->
        <div class="card card-premium">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
                <h3 class="card-title h5 mb-0 fw-bold text-primary-dark">
                    <i class="bi bi-pencil-square me-2"></i>Reschedule / Edit Appointment
                </h3>
            </div>
            
            <form action="<?= url('/appointments/' . $appointment['id']) ?>" method="POST" id="appointmentForm">
                <?= csrf_field() ?>

                <div class="card-body p-4 bg-white">
                    <!-- 1. Linked Patient Card -->
                    <div class="mb-4">
                        <h4 class="h6 fw-bold text-dark mb-3 border-bottom pb-2">Patient Details</h4>
                        
                        <div class="card bg-light border-0 p-3 rounded-3 d-flex flex-row align-items-center gap-3">
                            <div class="bg-primary-soft text-primary rounded-circle d-flex align-items-center justify-content-center fs-4" style="width: 48px; height: 48px;">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <div>
                                <span class="text-muted small fw-bold text-uppercase tracking-wider">Patient Record Linked</span>
                                <h5 class="h6 fw-bold text-primary-dark mb-1">
                                    <?= h($appointment['patient_last']) ?>, <?= h($appointment['patient_first']) ?> <?= h($appointment['patient_middle'] ?? '') ?>
                                </h5>
                                <div class="d-flex align-items-center gap-3 text-secondary small">
                                    <span><strong>No:</strong> <?= h($appointment['patient_no']) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Appointment Details -->
                    <div>
                        <h4 class="h6 fw-bold text-dark mb-3 border-bottom pb-2">Appointment Schedule</h4>
                        
                        <div class="row g-3">
                            <!-- Date Picker -->
                            <div class="col-12 col-sm-6">
                                <label for="appointment_date" class="form-label fw-semibold text-secondary small">Appointment Date <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-calendar-event"></i></span>
                                    <input type="text" 
                                           name="appointment_date" 
                                           id="appointment_date" 
                                           class="form-control bg-light border-start-0 flatpickr-date" 
                                           placeholder="Select Date" 
                                           value="<?= h($appointment['appointment_date']) ?>" 
                                           required>
                                </div>
                            </div>

                            <!-- Time Picker -->
                            <div class="col-12 col-sm-6">
                                <label for="appointment_time" class="form-label fw-semibold text-secondary small">Appointment Time <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-clock"></i></span>
                                    <input type="text" 
                                           name="appointment_time" 
                                           id="appointment_time" 
                                           class="form-control bg-light border-start-0 flatpickr-time-input" 
                                           placeholder="Select Time" 
                                           value="<?= date('H:i', strtotime($appointment['appointment_time'])) ?>" 
                                           required>
                                </div>
                            </div>

                            <!-- Conflict Warning Alert -->
                            <div class="col-12 d-none" id="conflictAlertContainer">
                                <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-0" role="alert" style="border-radius: 8px;">
                                    <i class="bi bi-exclamation-triangle-fill fs-5 text-warning me-3"></i>
                                    <div>
                                        <span class="fw-bold d-block">Schedule Conflict Warning</span>
                                        <span class="small text-secondary">Another active appointment is already scheduled at this date and time. You may still proceed with this booking if necessary, but expect potential wait times.</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Purpose Selection -->
                            <div class="col-12 col-sm-6">
                                <label for="purpose" class="form-label fw-semibold text-secondary small">Purpose of Visit <span class="text-danger">*</span></label>
                                <select name="purpose" id="purpose" class="form-select bg-light" required>
                                    <option value="">-- Select Purpose --</option>
                                    <option value="General Check-up" <?= $appointment['purpose'] === 'General Check-up' ? 'selected' : '' ?>>General Check-up</option>
                                    <option value="Consultation (SOAP)" <?= $appointment['purpose'] === 'Consultation (SOAP)' ? 'selected' : '' ?>>Consultation (SOAP)</option>
                                    <option value="Prenatal Care" <?= $appointment['purpose'] === 'Prenatal Care' ? 'selected' : '' ?>>Prenatal Care</option>
                                    <option value="Immunization" <?= $appointment['purpose'] === 'Immunization' ? 'selected' : '' ?>>Immunization</option>
                                    <option value="Dental Check-up" <?= $appointment['purpose'] === 'Dental Check-up' ? 'selected' : '' ?>>Dental Check-up</option>
                                    <option value="Follow-up Visit" <?= $appointment['purpose'] === 'Follow-up Visit' ? 'selected' : '' ?>>Follow-up Visit</option>
                                    <option value="Others / Referrals" <?= $appointment['purpose'] === 'Others / Referrals' ? 'selected' : '' ?>>Others / Referrals</option>
                                </select>
                            </div>

                            <!-- Status Dropdown -->
                            <div class="col-12 col-sm-6">
                                <label for="status" class="form-label fw-semibold text-secondary small">Appointment Status</label>
                                <select name="status" id="status" class="form-select bg-light">
                                    <option value="Scheduled" <?= $appointment['status'] === 'Scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                    <option value="Completed" <?= $appointment['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="Cancelled" <?= $appointment['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    <option value="Missed" <?= $appointment['status'] === 'Missed' ? 'selected' : '' ?>>Missed</option>
                                </select>
                            </div>

                            <!-- Notes -->
                            <div class="col-12">
                                <label for="notes" class="form-label fw-semibold text-secondary small">Notes / Symptoms described</label>
                                <textarea name="notes" id="notes" rows="3" class="form-control bg-light" placeholder="Describe symptoms or reasons..."><?= h($appointment['notes'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-light py-3 border-0 d-flex justify-content-between" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                    <a href="<?= url('/patients/' . $appointment['patient_id'] . '#appointments-tab') ?>" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Initialize Datepicker
    flatpickr('.flatpickr-date', {
        dateFormat: 'Y-m-d',
        allowInput: true
    });

    // 2. Initialize Timepicker
    flatpickr('.flatpickr-time-input', {
        enableTime: true,
        noCalendar: true,
        dateFormat: 'H:i',
        time_24hr: true,
        allowInput: true
    });

    // 3. Conflict Detector AJAX (excluding current appointment id)
    const dateInput = document.getElementById('appointment_date');
    const timeInput = document.getElementById('appointment_time');
    const warningAlert = document.getElementById('conflictAlertContainer');
    const excludeId = '<?= $appointment['id'] ?>';

    function checkSchedulingConflict() {
        const d = dateInput.value;
        const t = timeInput.value;

        if (d && t) {
            fetch(`<?= url('/appointments/check-conflict') ?>?date=${d}&time=${t}&exclude_id=${excludeId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.conflict) {
                        warningAlert.classList.remove('d-none');
                    } else {
                        warningAlert.classList.add('d-none');
                    }
                })
                .catch(error => {
                    console.error('Error checking conflict:', error);
                });
        } else {
            warningAlert.classList.add('d-none');
        }
    }

    if (dateInput && timeInput) {
        dateInput.addEventListener('change', checkSchedulingConflict);
        timeInput.addEventListener('change', checkSchedulingConflict);
    }
});
</script>

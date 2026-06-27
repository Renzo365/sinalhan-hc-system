<?php
$title = 'Schedule Appointment';
if (isset($patient) && $patient) {
    $breadcrumbs = [
        'Patients' => '/patients',
        'Profile' => '/patients/' . $patient['id'],
        'Schedule Appointment' => null
    ];
} else {
    $breadcrumbs = [
        'Appointments' => '/appointments',
        'Schedule Appointment' => null
    ];
}
require dirname(__DIR__) . '/layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h3 mb-1 fw-bold text-primary-dark">Schedule Appointment</h2>
        <p class="text-secondary small mb-0">Select a patient, date, and purpose to book a health center checkup.</p>
    </div>
    <a href="<?= isset($patient) && $patient ? url('/patients/' . $patient['id']) : url('/appointments') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to <?= isset($patient) && $patient ? 'Profile' : 'Appointments List' ?>
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
                    <i class="bi bi-calendar-event me-2"></i>Schedule Appointment
                </h3>
            </div>
            
            <form action="<?= url('/appointments') ?>" method="POST" id="appointmentForm">
                <?= csrf_field() ?>

                <div class="card-body p-4 bg-white">
                    <!-- 1. Patient Context Section -->
                    <div class="mb-4">
                        <h4 class="h6 fw-bold text-dark mb-3 border-bottom pb-2">1. Select Patient</h4>
                        
                        <?php if ($patient): ?>
                            <!-- Patient pre-selected card -->
                            <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
                            <div class="card bg-light border-0 p-3 rounded-3 d-flex flex-row align-items-center gap-3">
                                <div class="bg-primary-soft text-primary rounded-circle d-flex align-items-center justify-content-center fs-4" style="width: 48px; height: 48px;">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                                <div>
                                    <span class="text-muted small fw-bold text-uppercase tracking-wider">Patient Record Linked</span>
                                    <h5 class="h6 fw-bold text-primary-dark mb-1">
                                        <?= h($patient['last_name']) ?>, <?= h($patient['first_name']) ?> <?= h($patient['middle_name'] ?? '') ?>
                                    </h5>
                                    <div class="d-flex align-items-center gap-3 text-secondary small">
                                        <span><strong>No:</strong> <?= h($patient['patient_no']) ?></span>
                                        <span class="vr"></span>
                                        <span><strong>Age/Sex:</strong> <?= h($patient['age']) ?> yrs / <?= h($patient['sex']) ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Search & dropdown for patient -->
                            <div class="mb-3">
                                <label for="patient_id" class="form-label fw-semibold text-secondary small">Choose Patient <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                                    <select name="patient_id" id="patient_id" class="form-select bg-light border-start-0" required>
                                        <option value="">-- Select Patient --</option>
                                        <?php foreach ($patients as $p): ?>
                                            <option value="<?= $p['id'] ?>" <?= (isset($input['patient_id']) && $input['patient_id'] == $p['id']) ? 'selected' : '' ?>>
                                                <?= h($p['last_name']) ?>, <?= h($p['first_name']) ?> (<?= h($p['patient_no']) ?>) &bull; Age: <?= h($p['age']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-text small">Only patients currently registered in the database can be selected. To schedule a new patient, please <a href="<?= url('/patients/create') ?>" target="_blank">register them first</a>.</div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- 2. Schedule Details -->
                    <div>
                        <h4 class="h6 fw-bold text-dark mb-3 border-bottom pb-2">2. Appointment Schedule</h4>
                        
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
                                           value="<?= h($input['appointment_date'] ?? '') ?>" 
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
                                           value="<?= h($input['appointment_time'] ?? '') ?>" 
                                           required>
                                </div>
                            </div>

                            <!-- Conflict Warning alert (Hidden initially) -->
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
                                    <option value="General Check-up" <?= (isset($input['purpose']) && $input['purpose'] === 'General Check-up') ? 'selected' : '' ?>>General Check-up</option>
                                    <option value="Consultation (SOAP)" <?= (isset($input['purpose']) && $input['purpose'] === 'Consultation (SOAP)') ? 'selected' : '' ?>>Consultation (SOAP)</option>
                                    <option value="Prenatal Care" <?= (isset($input['purpose']) && $input['purpose'] === 'Prenatal Care') ? 'selected' : '' ?>>Prenatal Care</option>
                                    <option value="Immunization" <?= (isset($input['purpose']) && $input['purpose'] === 'Immunization') ? 'selected' : '' ?>>Immunization</option>
                                    <option value="Dental Check-up" <?= (isset($input['purpose']) && $input['purpose'] === 'Dental Check-up') ? 'selected' : '' ?>>Dental Check-up</option>
                                    <option value="Follow-up Visit" <?= (isset($input['purpose']) && $input['purpose'] === 'Follow-up Visit') ? 'selected' : '' ?>>Follow-up Visit</option>
                                    <option value="Others / Referrals" <?= (isset($input['purpose']) && $input['purpose'] === 'Others / Referrals') ? 'selected' : '' ?>>Others / Referrals</option>
                                </select>
                            </div>

                            <!-- Status Dropdown -->
                            <div class="col-12 col-sm-6">
                                <label for="status" class="form-label fw-semibold text-secondary small">Initial Status</label>
                                <select name="status" id="status" class="form-select bg-light">
                                    <option value="Scheduled" <?= (isset($input['status']) && $input['status'] === 'Scheduled') ? 'selected' : '' ?>>Scheduled</option>
                                    <option value="Completed" <?= (isset($input['status']) && $input['status'] === 'Completed') ? 'selected' : '' ?>>Completed</option>
                                    <option value="Cancelled" <?= (isset($input['status']) && $input['status'] === 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>
                                    <option value="Missed" <?= (isset($input['status']) && $input['status'] === 'Missed') ? 'selected' : '' ?>>Missed</option>
                                </select>
                            </div>

                            <!-- Notes -->
                            <div class="col-12">
                                <label for="notes" class="form-label fw-semibold text-secondary small">Notes / Symptoms described</label>
                                <textarea name="notes" id="notes" rows="3" class="form-control bg-light" placeholder="Describe symptoms or reasons (e.g. Patient requests follow-up for high blood pressure check-up)..."><?= h($input['notes'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-light py-3 border-0 d-flex justify-content-between" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                    <?php if ($patient): ?>
                        <a href="<?= url('/patients/' . $patient['id']) ?>" class="btn btn-outline-secondary">Cancel</a>
                    <?php else: ?>
                        <a href="<?= url('/appointments') ?>" class="btn btn-outline-secondary">Cancel</a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary px-4">Schedule Appointment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Initialize Datepicker (Date from today onwards)
    flatpickr('.flatpickr-date', {
        dateFormat: 'Y-m-d',
        minDate: 'today',
        allowInput: true
    });

    // 2. Initialize Timepicker (12-hour AM/PM format)
    flatpickr('.flatpickr-time-input', {
        enableTime: true,
        noCalendar: true,
        dateFormat: 'H:i',
        time_24hr: true,
        allowInput: true
    });

    // 3. Conflict Detector AJAX
    const dateInput = document.getElementById('appointment_date');
    const timeInput = document.getElementById('appointment_time');
    const warningAlert = document.getElementById('conflictAlertContainer');

    function checkSchedulingConflict() {
        const d = dateInput.value;
        const t = timeInput.value;

        if (d && t) {
            fetch(`<?= url('/appointments/check-conflict') ?>?date=${d}&time=${t}`)
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

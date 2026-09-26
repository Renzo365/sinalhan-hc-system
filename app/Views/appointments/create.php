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

                    <!-- 2. Schedule Details & Service Category -->
                    <div>
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3 border-bottom pb-2 gap-2">
                            <h4 class="h6 fw-bold text-dark mb-0">2. Appointment Schedule & Time Slot <span class="text-danger">*</span></h4>
                            <span class="badge bg-teal-subtle text-teal fw-semibold small" id="sessionIndicator" style="background-color: #e6fffa; color: #0d9488; border: 1px solid #b2f5ea;">
                                <i class="bi bi-clock me-1"></i>Select a Time Slot
                            </span>
                        </div>
                        
                        <div class="row g-3 mb-3">
                            <!-- Date Picker with Quick Sets -->
                            <div class="col-12 col-md-6">
                                <label for="appointment_date" class="form-label fw-semibold text-secondary small">Appointment Date <span class="text-danger">*</span></label>
                                <div class="input-group mb-2">
                                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-calendar-event"></i></span>
                                    <input type="date" 
                                           name="appointment_date" 
                                           id="appointment_date" 
                                           class="form-control bg-light border-start-0" 
                                           value="<?= h($input['appointment_date'] ?? date('Y-m-d')) ?>" 
                                           required>
                                </div>
                                <div class="d-flex align-items-center gap-1">
                                    <span class="text-muted small me-1">Quick set:</span>
                                    <button type="button" class="btn btn-xs btn-outline-secondary quick-date-btn" data-days="0">Today</button>
                                    <button type="button" class="btn btn-xs btn-outline-secondary quick-date-btn" data-days="1">Tomorrow</button>
                                    <button type="button" class="btn btn-xs btn-outline-secondary quick-date-btn" data-days="7">+1 Week</button>
                                </div>
                            </div>

                            <!-- Initial Status -->
                            <div class="col-12 col-md-6">
                                <label for="status" class="form-label fw-semibold text-secondary small">Initial Status <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-select bg-light">
                                    <option value="Scheduled" <?= (!isset($input['status']) || $input['status'] === 'Scheduled') ? 'selected' : '' ?>>Scheduled (Confirmed by Patient)</option>
                                    <option value="Completed" <?= (isset($input['status']) && $input['status'] === 'Completed') ? 'selected' : '' ?>>Completed</option>
                                    <option value="Cancelled" <?= (isset($input['status']) && $input['status'] === 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>
                                    <option value="Missed" <?= (isset($input['status']) && $input['status'] === 'Missed') ? 'selected' : '' ?>>Missed</option>
                                </select>
                                <div class="form-text small text-muted">Defaulting to "Scheduled" places the patient directly in the clinic's daily roster.</div>
                            </div>

                            <!-- Service Category / Program Type -->
                            <div class="col-12 col-md-6">
                                <label for="program_type" class="form-label fw-semibold text-secondary small">Service Category <span class="text-danger">*</span></label>
                                <select name="program_type" id="program_type" class="form-select bg-light" required>
                                    <option value="General OPD" <?= (isset($input['program_type']) && $input['program_type'] === 'General OPD') ? 'selected' : '' ?>>General OPD / Consultation</option>
                                    <option value="Maternal Care" <?= (isset($input['program_type']) && ($input['program_type'] === 'Maternal Care' || $input['program_type'] === 'Prenatal Care')) ? 'selected' : '' ?>>Maternal Care (Prenatal / Postpartum)</option>
                                    <option value="Well-Baby Care" <?= (isset($input['program_type']) && ($input['program_type'] === 'Well-Baby Care' || $input['program_type'] === 'Well Baby Immunization')) ? 'selected' : '' ?>>Well-Baby Care (Immunization / Growth)</option>
                                    <option value="Senior Care" <?= (isset($input['program_type']) && $input['program_type'] === 'Senior Care') ? 'selected' : '' ?>>Senior Citizen Care</option>
                                    <option value="Family Planning" <?= (isset($input['program_type']) && $input['program_type'] === 'Family Planning') ? 'selected' : '' ?>>Family Planning</option>
                                    <option value="NCD / Hypertension" <?= (isset($input['program_type']) && $input['program_type'] === 'NCD / Hypertension') ? 'selected' : '' ?>>NCD / Hypertension & Diabetes</option>
                                    <option value="Dental Care" <?= (isset($input['program_type']) && $input['program_type'] === 'Dental Care') ? 'selected' : '' ?>>Dental Care</option>
                                </select>
                            </div>

                            <!-- Purpose Input with Datalist Suggestions -->
                            <div class="col-12 col-md-6">
                                <label for="purpose" class="form-label fw-semibold text-secondary small">Purpose of Visit <span class="text-danger">*</span></label>
                                <input type="text" 
                                       name="purpose" 
                                       id="purpose" 
                                       class="form-control bg-light" 
                                       placeholder="e.g. Follow-up consultation, Routine EPI Vaccine, Blood pressure check..." 
                                       value="<?= h($input['purpose'] ?? '') ?>" 
                                       list="purposeSuggestions" 
                                       required>
                                <datalist id="purposeSuggestions">
                                    <option value="Routine Prenatal Checkup">
                                    <option value="Postpartum Follow-up">
                                    <option value="Routine EPI Vaccine">
                                    <option value="Growth Monitoring / Weighing">
                                    <option value="General Consultation">
                                    <option value="Blood Pressure Follow-up">
                                    <option value="Maintenance Medicine Refill">
                                    <option value="Senior Citizen Checkup">
                                    <option value="Deworming / Vitamin A">
                                    <option value="Dental Check-up">
                                    <option value="Others / Referrals">
                                </datalist>
                            </div>
                        </div>

                        <!-- 3. Time Slots Grid -->
                        <div class="mb-4">
                            <input type="hidden" name="appointment_time" id="appointment_time" value="<?= h($input['appointment_time'] ?? '') ?>" required>
                            
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-semibold text-secondary small mb-0">Select Available Time Slot <span class="text-danger">*</span></label>
                                <span class="small text-muted" id="slotLoadingNotice" style="display: none;">
                                    <span class="spinner-border spinner-border-sm me-1"></span>Checking capacities...
                                </span>
                            </div>

                            <!-- Morning OPD Session -->
                            <div class="mb-3 p-3 bg-light rounded-3 border">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold small text-dark"><i class="bi bi-brightness-alt-high-fill text-warning me-1"></i> Morning OPD (08:30 AM - 12:00 PM)</span>
                                </div>
                                <div class="row g-2" id="morningSlotsContainer">
                                    <?php
                                    $morningSlots = [
                                        ['slot' => 1, 'time' => '08:30', 'label' => '08:30 AM'],
                                        ['slot' => 2, 'time' => '09:00', 'label' => '09:00 AM'],
                                        ['slot' => 3, 'time' => '09:30', 'label' => '09:30 AM'],
                                        ['slot' => 4, 'time' => '10:00', 'label' => '10:00 AM'],
                                        ['slot' => 5, 'time' => '10:30', 'label' => '10:30 AM'],
                                        ['slot' => 6, 'time' => '11:00', 'label' => '11:00 AM'],
                                        ['slot' => 7, 'time' => '11:30', 'label' => '11:30 AM'],
                                    ];
                                    foreach ($morningSlots as $s):
                                        $slotTime = $s['time'];
                                        $isSelected = (!empty($input['appointment_time']) && strpos($input['appointment_time'], $slotTime) === 0);
                                    ?>
                                    <div class="col-6 col-sm-4 col-md-3">
                                        <div class="time-slot-card <?= $isSelected ? 'active' : '' ?>" data-time="<?= $slotTime ?>" data-slot="<?= $s['slot'] ?>" data-session="Morning OPD (08:30 AM - 12:00 PM)">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="fw-bold text-dark fs-6"><?= $s['label'] ?></span>
                                                <span class="slot-radio-dot"></span>
                                            </div>
                                            <div class="small text-secondary slot-subtext mt-1" style="font-size: 0.75rem;">
                                                Slot #<?= $s['slot'] ?> &bull; <span class="slot-status-label"><?= $isSelected ? 'Selected' : 'Available' ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Afternoon OPD Session -->
                            <div class="p-3 bg-light rounded-3 border">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold small text-dark"><i class="bi bi-sunset-fill text-danger me-1"></i> Afternoon OPD (01:00 PM - 04:30 PM)</span>
                                </div>
                                <div class="row g-2" id="afternoonSlotsContainer">
                                    <?php
                                    $afternoonSlots = [
                                        ['slot' => 8, 'time' => '13:00', 'label' => '01:00 PM'],
                                        ['slot' => 9, 'time' => '13:30', 'label' => '01:30 PM'],
                                        ['slot' => 10, 'time' => '14:00', 'label' => '02:00 PM'],
                                        ['slot' => 11, 'time' => '14:30', 'label' => '02:30 PM'],
                                        ['slot' => 12, 'time' => '15:00', 'label' => '03:00 PM'],
                                        ['slot' => 13, 'time' => '15:30', 'label' => '03:30 PM'],
                                        ['slot' => 14, 'time' => '16:00', 'label' => '04:00 PM'],
                                    ];
                                    foreach ($afternoonSlots as $s):
                                        $slotTime = $s['time'];
                                        $isSelected = (!empty($input['appointment_time']) && strpos($input['appointment_time'], $slotTime) === 0);
                                    ?>
                                    <div class="col-6 col-sm-4 col-md-3">
                                        <div class="time-slot-card <?= $isSelected ? 'active' : '' ?>" data-time="<?= $slotTime ?>" data-slot="<?= $s['slot'] ?>" data-session="Afternoon OPD (01:00 PM - 04:30 PM)">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="fw-bold text-dark fs-6"><?= $s['label'] ?></span>
                                                <span class="slot-radio-dot"></span>
                                            </div>
                                            <div class="small text-secondary slot-subtext mt-1" style="font-size: 0.75rem;">
                                                Slot #<?= $s['slot'] ?> &bull; <span class="slot-status-label"><?= $isSelected ? 'Selected' : 'Available' ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Notes / Symptoms -->
                        <div class="mb-3">
                            <label for="notes" class="form-label fw-semibold text-secondary small">Notes / Symptoms described</label>
                            <textarea name="notes" id="notes" rows="3" class="form-control bg-light" placeholder="Describe symptoms or reasons (e.g. Patient requests follow-up for maternal care / high blood pressure check-up)..."><?= h($input['notes'] ?? '') ?></textarea>
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

<style>
.time-slot-card {
    border: 1.5px solid #dee2e6;
    border-radius: 8px;
    padding: 10px 12px;
    background: #ffffff;
    cursor: pointer;
    transition: all 0.15s ease-in-out;
    user-select: none;
    height: 100%;
}
.time-slot-card:hover {
    border-color: #0d9488;
    background-color: #f0fdfa;
    transform: translateY(-1px);
}
.time-slot-card.active {
    border-color: #0d9488 !important;
    background-color: #f0fdfa !important;
    box-shadow: 0 0 0 2px rgba(13, 148, 136, 0.2);
}
.time-slot-card .slot-radio-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background-color: #cbd5e1;
    display: inline-block;
    transition: background-color 0.15s;
}
.time-slot-card.active .slot-radio-dot {
    background-color: #0d9488;
    box-shadow: 0 0 0 2px rgba(13, 148, 136, 0.3);
}
.time-slot-card.has-booked {
    border-color: #fed7aa;
    background-color: #fffaf0;
}
.time-slot-card.has-booked:hover {
    border-color: #f97316;
}
.quick-date-btn {
    font-size: 0.75rem;
    padding: 2px 10px;
    border-radius: 20px;
}
.quick-date-btn.active-quick {
    background-color: #0d9488;
    color: #fff;
    border-color: #0d9488;
}
</style>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.getElementById('appointment_date');
    const timeInput = document.getElementById('appointment_time');
    const slotCards = document.querySelectorAll('.time-slot-card');
    const sessionIndicator = document.getElementById('sessionIndicator');
    const loadingNotice = document.getElementById('slotLoadingNotice');
    const quickButtons = document.querySelectorAll('.quick-date-btn');
    const form = document.getElementById('appointmentForm');

    let currentCapacities = {};

    function updateQuickButtonsHighlight() {
        const selectedDate = dateInput.value;
        const now = new Date();
        const yyyy = now.getFullYear();
        const mm = String(now.getMonth() + 1).padStart(2, '0');
        const dd = String(now.getDate()).padStart(2, '0');
        const todayStr = `${yyyy}-${mm}-${dd}`;

        const tom = new Date();
        tom.setDate(tom.getDate() + 1);
        const tomStr = `${tom.getFullYear()}-${String(tom.getMonth() + 1).padStart(2, '0')}-${String(tom.getDate()).padStart(2, '0')}`;

        const week = new Date();
        week.setDate(week.getDate() + 7);
        const weekStr = `${week.getFullYear()}-${String(week.getMonth() + 1).padStart(2, '0')}-${String(week.getDate()).padStart(2, '0')}`;

        quickButtons.forEach(btn => {
            const days = parseInt(btn.dataset.days, 10);
            btn.classList.remove('active-quick');
            if (days === 0 && selectedDate === todayStr) btn.classList.add('active-quick');
            if (days === 1 && selectedDate === tomStr) btn.classList.add('active-quick');
            if (days === 7 && selectedDate === weekStr) btn.classList.add('active-quick');
        });
    }

    function applyCapacities(capacities) {
        slotCards.forEach(card => {
            const time = card.dataset.time;
            const slotNum = card.dataset.slot;
            const statusLabel = card.querySelector('.slot-status-label');
            const count = capacities[time] || 0;

            if (card.classList.contains('active')) {
                if (count > 0) {
                    statusLabel.textContent = `Selected (${count} Booked)`;
                } else {
                    statusLabel.textContent = 'Selected';
                }
            } else if (count > 0) {
                card.classList.add('has-booked');
                statusLabel.textContent = `${count} Booked`;
            } else {
                card.classList.remove('has-booked');
                statusLabel.textContent = 'Available';
            }
        });
    }

    function loadDayCapacity() {
        if (!dateInput || !dateInput.value) return;

        if (loadingNotice) loadingNotice.style.display = 'inline-block';

        fetch(`<?= url('/appointments/day-capacity') ?>?date=${dateInput.value}`)
            .then(res => res.json())
            .then(data => {
                currentCapacities = data.capacities || {};
                applyCapacities(currentCapacities);
            })
            .catch(err => {
                console.error('Failed to load slot capacities:', err);
            })
            .finally(() => {
                if (loadingNotice) loadingNotice.style.display = 'none';
            });
    }

    // Quick set buttons handler
    quickButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const days = parseInt(this.dataset.days, 10);
            const target = new Date();
            target.setDate(target.getDate() + days);
            const yyyy = target.getFullYear();
            const mm = String(target.getMonth() + 1).padStart(2, '0');
            const dd = String(target.getDate()).padStart(2, '0');
            dateInput.value = `${yyyy}-${mm}-${dd}`;
            updateQuickButtonsHighlight();
            loadDayCapacity();
        });
    });

    // Time slot selection
    slotCards.forEach(card => {
        card.addEventListener('click', function() {
            slotCards.forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            timeInput.value = this.dataset.time;

            const sessionName = this.dataset.session || '';
            if (sessionIndicator && sessionName) {
                sessionIndicator.innerHTML = `<i class="bi bi-clock-fill me-1"></i>Session: ${sessionName}`;
            }

            applyCapacities(currentCapacities);
        });
    });

    // Date change handler
    if (dateInput) {
        dateInput.addEventListener('change', function() {
            updateQuickButtonsHighlight();
            loadDayCapacity();
        });
    }

    // Form submit validation
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!timeInput.value) {
                e.preventDefault();
                alert('Please select an available time slot for the appointment.');
                document.getElementById('morningSlotsContainer').scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }

    // Initialize on load
    updateQuickButtonsHighlight();
    loadDayCapacity();

    // If initial time is pre-selected, update session indicator
    const initialActive = document.querySelector('.time-slot-card.active');
    if (initialActive && initialActive.dataset.session && sessionIndicator) {
        sessionIndicator.innerHTML = `<i class="bi bi-clock-fill me-1"></i>Session: ${initialActive.dataset.session}`;
    }
});
</script>

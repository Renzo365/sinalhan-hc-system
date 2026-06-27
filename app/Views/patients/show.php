<?php
/**
 * @var array $patient Patient demographic record
 * @var array $vitals Vital signs history
 * @var array $consultations Consultation records
 * @var array $appointments Appointment history
 * @var array $queueHistory Daily queue history logs
 */

$title = 'Patient Profile';
$breadcrumbs = [
    'Patients' => '/patients',
    'Profile' => null
];
require dirname(__DIR__) . '/layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h3 mb-1 fw-bold text-primary-dark">Patient Profile</h2>
        <p class="text-secondary small mb-0">Detailed clinical record folder, demographics database, and medical checkup history.</p>
    </div>
    <a href="<?= url('/patients') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Directory
    </a>
</div>

<!-- Header Card -->
<div class="card card-premium mb-4 bg-white">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <span class="text-muted small fw-bold text-uppercase tracking-wider">Patient Record</span>
                <h2 class="h3 fw-bold text-primary-dark mb-2">
                    <?= h($patient['last_name']) ?>, <?= h($patient['first_name']) ?> <?= h($patient['middle_name'] ?? '') ?>
                </h2>
                <div class="d-flex flex-wrap align-items-center gap-3 text-secondary small">
                    <div><i class="bi bi-card-text me-1 text-primary"></i><strong>ID:</strong> <?= h($patient['patient_no']) ?></div>
                    <div class="vr d-none d-sm-block"></div>
                    <div><i class="bi bi-calendar3 me-1 text-primary"></i><strong>Age/Sex:</strong> <?= h($patient['age']) ?> yrs / <?= h($patient['sex']) ?></div>
                    <div class="vr d-none d-sm-block"></div>
                    <div><i class="bi bi-geo-alt me-1 text-primary"></i><strong>Barangay:</strong> Brgy. <?= h($patient['barangay']) ?></div>
                    <?php if (!empty($patient['contact_no'])): ?>
                        <div class="vr d-none d-sm-block"></div>
                        <div><i class="bi bi-telephone me-1 text-primary"></i><strong>Contact:</strong> <?= h($patient['contact_no']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= url('/patients/' . $patient['id'] . '/edit') ?>" class="btn btn-outline-primary d-flex align-items-center px-3 py-2">
                    <i class="bi bi-pencil-square me-2"></i>
                    <span>Edit Demographics</span>
                </a>
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <button class="btn btn-outline-danger d-flex align-items-center px-3 py-2" data-bs-toggle="modal" data-bs-target="#archivePatientModal">
                        <i class="bi bi-archive me-2"></i>
                        <span>Archive Record</span>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Patient Demographic Details & Metadata -->
    <div class="col-12 col-lg-4">
        <!-- Profile Details Card -->
        <div class="card card-premium mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h3 class="card-title h6 mb-0 fw-bold text-dark">
                    <i class="bi bi-person-lines-fill text-primary me-2"></i>Demographics
                </h3>
            </div>
            <div class="card-body p-4">
                <ul class="list-unstyled mb-0 small">
                    <li class="mb-3 d-flex justify-content-between">
                        <span class="text-muted">Date of Birth:</span>
                        <span class="fw-semibold"><?= date('F d, Y', strtotime($patient['dob'])) ?></span>
                    </li>
                    <li class="mb-3 d-flex justify-content-between">
                        <span class="text-muted">Civil Status:</span>
                        <span class="fw-semibold"><?= h($patient['civil_status']) ?></span>
                    </li>
                    <li class="mb-3 d-flex justify-content-between">
                        <span class="text-muted">PhilHealth ID:</span>
                        <span class="fw-semibold"><?= h($patient['philhealth_no'] ?? 'Not Registered') ?></span>
                    </li>
                    <li class="mb-3">
                        <span class="text-muted d-block mb-1">Complete Address:</span>
                        <span class="fw-semibold d-block text-dark"><?= h($patient['address']) ?></span>
                    </li>
                    <hr class="my-3 text-muted opacity-25">
                    
                    <!-- Emergency Contacts -->
                    <li class="mb-2">
                        <span class="text-muted d-block mb-1 fw-bold text-uppercase tracking-wider" style="font-size: 0.7rem;">Emergency Contact</span>
                        <?php if (!empty($patient['emergency_name'])): ?>
                            <span class="fw-semibold d-block text-dark mb-1"><i class="bi bi-person me-1"></i><?= h($patient['emergency_name']) ?></span>
                            <?php if (!empty($patient['emergency_no'])): ?>
                                <span class="fw-semibold d-block text-secondary"><i class="bi bi-telephone me-1"></i><?= h($patient['emergency_no']) ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted italic small">No emergency contact registered.</span>
                        <?php endif; ?>
                    </li>
                    <hr class="my-3 text-muted opacity-25">
                    
                    <!-- Record Metadata -->
                    <li class="mb-2 text-muted" style="font-size: 0.75rem;">
                        <span class="d-block mb-1"><strong>Created By:</strong> <?= h($patient['creator_name']) ?></span>
                        <span class="d-block"><strong>Created On:</strong> <?= date('M d, Y h:i A', strtotime($patient['created_at'])) ?></span>
                    </li>
                    <?php if ($patient['updated_by']): ?>
                        <li class="text-muted mt-2" style="font-size: 0.75rem;">
                            <span class="d-block mb-1"><strong>Last Updated By:</strong> <?= h($patient['updater_name']) ?></span>
                            <span class="d-block"><strong>Last Updated On:</strong> <?= date('M d, Y h:i A', strtotime($patient['updated_at'])) ?></span>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Quick Actions Card -->
        <div class="card card-premium">
            <div class="card-header bg-white py-3 border-bottom">
                <h3 class="card-title h6 mb-0 fw-bold text-dark">
                    <i class="bi bi-lightning-charge-fill text-primary me-2"></i>Quick Actions
                </h3>
            </div>
            <div class="card-body p-4">
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary py-2 text-start small d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#addVitalsModal">
                        <i class="bi bi-heart-pulse-fill me-2 fs-5"></i>
                        <div>
                            <span class="fw-bold d-block">Record Vital Signs</span>
                            <span class="text-muted d-block" style="font-size: 0.75rem;">Height, weight, blood pressure...</span>
                        </div>
                    </button>
                    
                    <form action="<?= url('/queue') ?>" method="POST" class="m-0 p-0">
                        <?= csrf_field() ?>
                        <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
                        <button type="submit" class="btn btn-outline-primary py-2 text-start small d-flex align-items-center w-100">
                            <i class="bi bi-person-lines-fill me-2 fs-5"></i>
                            <div>
                                <span class="fw-bold d-block">Add to Queue</span>
                                <span class="text-muted d-block" style="font-size: 0.75rem;">Register patient in daily queue</span>
                            </div>
                        </button>
                    </form>
                    
                    <a href="<?= url('/patients/' . $patient['id'] . '/consultations/create') ?>" class="btn btn-outline-primary py-2 text-start small d-flex align-items-center">
                        <i class="bi bi-journal-medical me-2 fs-5"></i>
                        <div>
                            <span class="fw-bold d-block">New Consultation</span>
                            <span class="text-muted d-block" style="font-size: 0.75rem;">Record check-up notes (SOAP)</span>
                        </div>
                    </a>

                    <a href="<?= url('/appointments/create?patient_id=' . $patient['id']) ?>" class="btn btn-outline-primary py-2 text-start small d-flex align-items-center">
                        <i class="bi bi-calendar-event me-2 fs-5"></i>
                        <div>
                            <span class="fw-bold d-block">Schedule Appointment</span>
                            <span class="text-muted d-block" style="font-size: 0.75rem;">Book upcoming date/time slot</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: History Tabs (Vitals, Consultations, Appointments, Queue) -->
    <div class="col-12 col-lg-8">
        <!-- Tabs Header -->
        <ul class="nav nav-tabs border-bottom mb-3" id="profileTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold text-primary-dark" id="vitals-tab" data-bs-toggle="tab" data-bs-target="#vitals-panel" type="button" role="tab" aria-controls="vitals-panel" aria-selected="true">
                    <i class="bi bi-activity me-1"></i> Vital Signs
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link text-muted" id="consultations-tab" data-bs-toggle="tab" data-bs-target="#consultations-panel" type="button" role="tab" aria-controls="consultations-panel" aria-selected="false">
                    <i class="bi bi-clipboard2-pulse me-1"></i> Consultations
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link text-muted" id="appointments-tab" data-bs-toggle="tab" data-bs-target="#appointments-panel" type="button" role="tab" aria-controls="appointments-panel" aria-selected="false">
                    <i class="bi bi-calendar3 me-1"></i> Appointments
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link text-muted" id="queue-tab" data-bs-toggle="tab" data-bs-target="#queue-panel" type="button" role="tab" aria-controls="queue-panel" aria-selected="false">
                    <i class="bi bi-list-ol me-1"></i> Queue
                </button>
            </li>
        </ul>

        <!-- Tabs Content -->
        <div class="tab-content" id="profileTabsContent">
            <!-- 1. Vital Signs Panel -->
            <div class="tab-pane fade show active" id="vitals-panel" role="tabpanel" aria-labelledby="vitals-tab">
                <div class="card card-premium">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h4 class="h6 mb-0 fw-bold text-dark">Vital Signs History</h4>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addVitalsModal">
                            <i class="bi bi-plus-lg me-1"></i> Record Vitals
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 text-center small" id="vitalsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-start ps-3">Recorded Date</th>
                                        <th>BP (mmHg)</th>
                                        <th>Pulse (bpm)</th>
                                        <th>Temp (°C)</th>
                                        <th>Resp (cpm)</th>
                                        <th>SpO2</th>
                                        <th>Wt/Ht (kg/cm)</th>
                                        <th>BMI</th>
                                        <th>Notes</th>
                                        <th class="pe-3">Recorded By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($vitalsHistory)): ?>
                                        <tr>
                                            <td colspan="10" class="text-center py-5 text-muted">
                                                <i class="bi bi-activity d-block fs-3 mb-2 text-muted"></i>
                                                No vital signs records exist for this patient.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($vitalsHistory as $v): 
                                            // BP status highlighting
                                            $bpColor = '';
                                            $bpSystolic = $v['bp_systolic'];
                                            $bpDiastolic = $v['bp_diastolic'];
                                            if ($bpSystolic >= 140 || $bpDiastolic >= 90) {
                                                $bpColor = 'text-danger fw-bold'; // Hypertension
                                            } elseif ($bpSystolic > 0 && ($bpSystolic < 90 || $bpDiastolic < 60)) {
                                                $bpColor = 'text-primary fw-bold'; // Hypotension
                                            }

                                            // Temperature status highlighting
                                            $tempColor = '';
                                            $temp = $v['temperature'];
                                            if ($temp >= 37.8) {
                                                $tempColor = 'badge bg-danger-bg text-danger'; // Fever
                                            } elseif ($temp > 0 && $temp < 35.0) {
                                                $tempColor = 'badge bg-info-bg text-info'; // Hypothermia
                                            } else {
                                                $tempColor = 'fw-semibold text-dark';
                                            }

                                            // SpO2 Hypoxia highlighting
                                            $spo2Color = '';
                                            $spo2 = $v['oxygen_saturation'];
                                            if ($spo2 > 0 && $spo2 < 95) {
                                                $spo2Color = 'text-danger fw-bold border border-danger rounded px-1 bg-danger-bg'; // Hypoxia
                                            }
                                        ?>
                                            <tr>
                                                <td class="text-start ps-3 text-secondary" style="white-space: nowrap;">
                                                    <?= date('M d, Y h:i A', strtotime($v['recorded_at'])) ?>
                                                </td>
                                                <td>
                                                    <?php if ($bpSystolic && $bpDiastolic): ?>
                                                        <span class="<?= $bpColor ?>"><?= h($bpSystolic) ?>/<?= h($bpDiastolic) ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= $v['heart_rate'] ?: '<span class="text-muted">-</span>' ?></td>
                                                <td>
                                                    <?php if ($temp): ?>
                                                        <span class="<?= $tempColor ?>"><?= number_format($temp, 1) ?>°C</span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= $v['respiratory_rate'] ?: '<span class="text-muted">-</span>' ?></td>
                                                <td>
                                                    <?php if ($spo2): ?>
                                                        <span class="<?= $spo2Color ?>"><?= h($spo2) ?>%</span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($v['weight'] || $v['height']): ?>
                                                        <span><?= $v['weight'] ? h($v['weight']) . ' kg' : '-' ?> / <?= $v['height'] ? h($v['height']) . ' cm' : '-' ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($v['bmi']): 
                                                        $bmiClass = 'bg-secondary';
                                                        if ($v['bmi'] < 18.5) $bmiClass = 'bg-info';
                                                        elseif ($v['bmi'] < 25) $bmiClass = 'bg-success';
                                                        elseif ($v['bmi'] < 30) $bmiClass = 'bg-warning text-dark';
                                                        else $bmiClass = 'bg-danger';
                                                    ?>
                                                        <span class="badge <?= $bmiClass ?>"><?= number_format($v['bmi'], 2) ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-start" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= h($v['notes'] ?? '') ?>">
                                                    <?= h($v['notes'] ?? '-') ?>
                                                </td>
                                                <td class="pe-3 fw-semibold text-secondary" style="white-space: nowrap;">
                                                    <?= h($v['recorder_name']) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Consultations Panel -->
            <div class="tab-pane fade" id="consultations-panel" role="tabpanel" aria-labelledby="consultations-tab">
                <div class="card card-premium">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h4 class="h6 mb-0 fw-bold text-dark">Consultation History</h4>
                        <a href="<?= url('/patients/' . $patient['id'] . '/consultations/create') ?>" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-lg me-1"></i> New Consultation
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 text-center small" id="consultationsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-start ps-3">Consulted Date</th>
                                        <th class="text-start">Diagnosis (Assessment)</th>
                                        <th>Consulting Clinician</th>
                                        <th>Status</th>
                                        <th class="pe-3 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($consultationsHistory)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">
                                                <i class="bi bi-clipboard2-pulse d-block fs-3 mb-2 text-muted"></i>
                                                No consultation records exist for this patient.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($consultationsHistory as $c): 
                                            $statusBadge = 'bg-success';
                                            if ($c['status'] === 'Open') $statusBadge = 'bg-info';
                                            elseif ($c['status'] === 'Cancelled') $statusBadge = 'bg-danger';
                                        ?>
                                            <tr>
                                                <td class="text-start ps-3 text-secondary" style="white-space: nowrap;">
                                                    <?= date('M d, Y h:i A', strtotime($c['consulted_at'])) ?>
                                                </td>
                                                <td class="text-start fw-semibold text-dark text-truncate" style="max-width: 250px;" title="<?= h($c['assessment']) ?>">
                                                    <?= h($c['assessment']) ?>
                                                </td>
                                                <td style="white-space: nowrap;"><?= h($c['clinician_name']) ?></td>
                                                <td>
                                                    <span class="badge <?= $statusBadge ?>"><?= h($c['status']) ?></span>
                                                </td>
                                                <td class="pe-3 text-end" style="white-space: nowrap;">
                                                    <button class="btn btn-sm btn-outline-primary btn-view-consultation" data-id="<?= $c['id'] ?>" data-bs-toggle="modal" data-bs-target="#viewConsultationModal">
                                                        <i class="bi bi-eye me-1"></i> View Details
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Appointments Panel -->
            <div class="tab-pane fade" id="appointments-panel" role="tabpanel" aria-labelledby="appointments-tab">
                <div class="card card-premium">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h4 class="h6 mb-0 fw-bold text-dark">Appointment History</h4>
                        <a href="<?= url('/appointments/create?patient_id=' . $patient['id']) ?>" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-lg me-1"></i> Schedule Appointment
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 text-center small" id="appointmentsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-start ps-3">Scheduled Date</th>
                                        <th>Time</th>
                                        <th class="text-start">Purpose</th>
                                        <th>Status</th>
                                        <th class="pe-3 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($appointmentsHistory)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">
                                                <i class="bi bi-calendar3 d-block fs-3 mb-2 text-muted"></i>
                                                No appointments scheduled for this patient.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($appointmentsHistory as $a): 
                                            $statusBadge = 'bg-secondary';
                                            if ($a['status'] === 'Scheduled') $statusBadge = 'bg-info text-dark';
                                            elseif ($a['status'] === 'Completed') $statusBadge = 'bg-success text-white';
                                            elseif ($a['status'] === 'Cancelled') $statusBadge = 'bg-danger text-white';
                                            elseif ($a['status'] === 'Missed') $statusBadge = 'bg-dark text-white';
                                        ?>
                                            <tr>
                                                <td class="text-start ps-3 text-secondary fw-semibold" style="white-space: nowrap;">
                                                    <?= date('M d, Y', strtotime($a['appointment_date'])) ?>
                                                </td>
                                                <td class="fw-semibold text-dark" style="white-space: nowrap;">
                                                    <?= date('h:i A', strtotime($a['appointment_time'])) ?>
                                                </td>
                                                <td class="text-start text-truncate text-secondary" style="max-width: 250px;" title="<?= h($a['purpose']) ?>">
                                                    <?= h($a['purpose']) ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $statusBadge ?>"><?= h($a['status']) ?></span>
                                                </td>
                                                <td class="pe-3 text-end" style="white-space: nowrap;">
                                                    <div class="d-inline-flex gap-1">
                                                        <?php if ($a['status'] === 'Scheduled'): ?>
                                                            <!-- Quick complete action -->
                                                            <form action="<?= url('/appointments/' . $a['id'] . '/status') ?>" method="POST" class="d-inline">
                                                                <?= csrf_field() ?>
                                                                <input type="hidden" name="status" value="Completed">
                                                                <button type="submit" class="btn btn-sm btn-outline-success border-0 px-2" title="Mark as Completed" data-confirm="Mark this appointment as Completed?">
                                                                    <i class="bi bi-check-lg"></i>
                                                                </button>
                                                            </form>
                                                            
                                                            <!-- Quick missed action -->
                                                            <form action="<?= url('/appointments/' . $a['id'] . '/status') ?>" method="POST" class="d-inline">
                                                                <?= csrf_field() ?>
                                                                <input type="hidden" name="status" value="Missed">
                                                                <button type="submit" class="btn btn-sm btn-outline-dark border-0 px-2" title="Mark as Missed" data-confirm="Mark this appointment as Missed?">
                                                                    <i class="bi bi-clock-history"></i>
                                                                </button>
                                                            </form>

                                                            <!-- Quick cancel action -->
                                                            <form action="<?= url('/appointments/' . $a['id'] . '/status') ?>" method="POST" class="d-inline">
                                                                <?= csrf_field() ?>
                                                                <input type="hidden" name="status" value="Cancelled">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger border-0 px-2" title="Cancel Appointment" data-confirm="Are you sure you want to cancel this appointment?">
                                                                    <i class="bi bi-x-lg"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>

                                                        <!-- Reschedule / Edit action -->
                                                        <a href="<?= url('/appointments/' . $a['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary border-0 px-2" title="Edit/Reschedule">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Queue Panel -->
            <div class="tab-pane fade" id="queue-panel" role="tabpanel" aria-labelledby="queue-tab">
                <div class="card card-premium">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h4 class="h6 mb-0 fw-bold text-dark">Queue History</h4>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 text-center small" id="queueTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-start ps-3">Date</th>
                                        <th>Queue No.</th>
                                        <th>Time In</th>
                                        <th>Time Called</th>
                                        <th>Time Completed</th>
                                        <th class="pe-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($queueHistory)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">
                                                <i class="bi bi-list-ol d-block fs-3 mb-2 text-muted"></i>
                                                No queue records exist for this patient.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($queueHistory as $q): ?>
                                            <?php 
                                                $statusBadge = 'bg-secondary';
                                                switch ($q['status']) {
                                                    case 'Waiting':
                                                        $statusBadge = 'bg-warning text-dark';
                                                        break;
                                                    case 'Called':
                                                        $statusBadge = 'bg-info text-dark';
                                                        break;
                                                    case 'Serving':
                                                        $statusBadge = 'bg-primary text-white';
                                                        break;
                                                    case 'Completed':
                                                        $statusBadge = 'bg-success text-white';
                                                        break;
                                                    case 'Cancelled':
                                                        $statusBadge = 'bg-danger text-white';
                                                        break;
                                                }
                                            ?>
                                            <tr>
                                                <td class="text-start ps-3 fw-medium text-dark"><?= h($q['queue_date']) ?></td>
                                                <td><span class="badge bg-primary-bg text-primary fw-bold">#<?= sprintf('%03d', $q['queue_no']) ?></span></td>
                                                <td><?= $q['time_in'] ? date('h:i A', strtotime($q['time_in'])) : '-' ?></td>
                                                <td><?= $q['time_called'] ? date('h:i A', strtotime($q['time_called'])) : '-' ?></td>
                                                <td><?= $q['time_completed'] ? date('h:i A', strtotime($q['time_completed'])) : '-' ?></td>
                                                <td class="pe-3">
                                                    <span class="badge <?= $statusBadge ?>"><?= h($q['status']) ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
<!-- ==========================================================================
   ARCHIVE PATIENT MODAL
   ========================================================================== -->
<div class="modal fade" id="archivePatientModal" tabindex="-1" aria-labelledby="archivePatientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-danger text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="archivePatientModalLabel">
                    <i class="bi bi-archive-fill me-2"></i>Archive Patient Record
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="<?= url('/patients/' . $patient['id'] . '/archive') ?>" method="POST" id="archiveForm">
                <?= csrf_field() ?>
                <div class="modal-body p-4 bg-white">
                    <div class="alert alert-warning border-0 small mb-3">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Warning:</strong> Archiving will hide this patient from active directories, daily queues, and scheduling lists. Only administrators can view and restore archived patient profiles.
                    </div>
                    <div class="mb-3">
                        <label for="archive_reason" class="form-label fw-semibold text-secondary small">Reason for Archiving <span class="text-danger">*</span></label>
                        <textarea name="archive_reason" id="archive_reason" class="form-control bg-light" rows="3" placeholder="e.g. Patient moved, deceased, or record duplicate..." required></textarea>
                    </div>
                </div>
                
                <div class="modal-footer bg-light py-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Archive</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ==========================================================================
   VITAL SIGNS RECORDING MODAL
   ========================================================================== -->
<div class="modal fade" id="addVitalsModal" tabindex="-1" aria-labelledby="addVitalsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="addVitalsModalLabel">
                    <i class="bi bi-heart-pulse-fill me-2"></i>Record Vital Signs
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="<?= url('/vital-signs') ?>" method="POST" id="vitalsForm">
                <?= csrf_field() ?>
                <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">

                <div class="modal-body p-4 bg-white">
                    <div class="text-muted small mb-3">
                        Patient: <strong><?= h($patient['last_name']) ?>, <?= h($patient['first_name']) ?></strong> &bull; DOB: <?= h($patient['dob']) ?>
                    </div>
                    
                    <div class="row g-3">
                        <!-- Blood Pressure Systolic -->
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="bp_systolic" class="form-label fw-semibold text-secondary small">Blood Pressure - Systolic</label>
                            <div class="input-group">
                                <input type="number" name="bp_systolic" id="bp_systolic" class="form-control" placeholder="120" min="40" max="300">
                                <span class="input-group-text small">mmHg</span>
                            </div>
                        </div>

                        <!-- Blood Pressure Diastolic -->
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="bp_diastolic" class="form-label fw-semibold text-secondary small">Blood Pressure - Diastolic</label>
                            <div class="input-group">
                                <input type="number" name="bp_diastolic" id="bp_diastolic" class="form-control" placeholder="80" min="30" max="200">
                                <span class="input-group-text small">mmHg</span>
                            </div>
                        </div>

                        <!-- Heart Rate -->
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="heart_rate" class="form-label fw-semibold text-secondary small">Heart Rate / Pulse</label>
                            <div class="input-group">
                                <input type="number" name="heart_rate" id="heart_rate" class="form-control" placeholder="72" min="20" max="250">
                                <span class="input-group-text small">bpm</span>
                            </div>
                        </div>

                        <!-- Temperature -->
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="temperature" class="form-label fw-semibold text-secondary small">Body Temperature</label>
                            <div class="input-group">
                                <input type="number" name="temperature" id="temperature" class="form-control" placeholder="36.5" step="0.1" min="30" max="45">
                                <span class="input-group-text small">°C</span>
                            </div>
                        </div>

                        <!-- Respiratory Rate -->
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="respiratory_rate" class="form-label fw-semibold text-secondary small">Respiratory Rate</label>
                            <div class="input-group">
                                <input type="number" name="respiratory_rate" id="respiratory_rate" class="form-control" placeholder="18" min="5" max="80">
                                <span class="input-group-text small">cpm</span>
                            </div>
                        </div>

                        <!-- Oxygen Saturation -->
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="oxygen_saturation" class="form-label fw-semibold text-secondary small">Oxygen Saturation (SpO2)</label>
                            <div class="input-group">
                                <input type="number" name="oxygen_saturation" id="oxygen_saturation" class="form-control" placeholder="98" min="50" max="100">
                                <span class="input-group-text small">%</span>
                            </div>
                        </div>

                        <hr class="my-3 text-muted opacity-25">

                        <!-- Weight -->
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="weight" class="form-label fw-semibold text-secondary small">Weight</label>
                            <div class="input-group">
                                <input type="number" name="weight" id="weight" class="form-control" placeholder="60" step="0.01" min="1" max="500">
                                <span class="input-group-text small">kg</span>
                            </div>
                        </div>

                        <!-- Height -->
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="height" class="form-label fw-semibold text-secondary small">Height</label>
                            <div class="input-group">
                                <input type="number" name="height" id="height" class="form-control" placeholder="165" step="0.1" min="30" max="300">
                                <span class="input-group-text small">cm</span>
                            </div>
                        </div>

                        <!-- BMI (Auto-calculated) -->
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="bmi" class="form-label fw-semibold text-secondary small">Calculated BMI</label>
                            <input type="text" name="bmi" id="bmi" class="form-control bg-light" placeholder="BMI auto-calc" readonly>
                        </div>

                        <!-- Notes -->
                        <div class="col-12">
                            <label for="notes" class="form-label fw-semibold text-secondary small">Clinical Notes / Symptoms</label>
                            <textarea name="notes" id="notes" rows="2" class="form-control" placeholder="Patient states feeling dizzy, etc."></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-light py-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Save Vitals</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==========================================================================
   VIEW CONSULTATION DETAILS MODAL
   ========================================================================== -->
<div class="modal fade" id="viewConsultationModal" tabindex="-1" aria-labelledby="viewConsultationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="viewConsultationModalLabel">
                    <i class="bi bi-journal-medical me-2"></i>Consultation Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4 bg-white" id="consultationDetailsContent">
                <!-- Content loaded dynamically via AJAX -->
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mt-2 small">Fetching consultation record...</p>
                </div>
            </div>
            
            <div class="modal-footer bg-light py-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
@media (min-width: 768px) {
    .border-end-md {
        border-right: 1px solid var(--color-border);
    }
}
.fs-7 {
    font-size: 0.85rem !important;
}
.bg-primary-bg {
    background-color: var(--color-primary-soft) !important;
}
</style>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Real-time BMI calculator
    const weightInput = document.getElementById('weight');
    const heightInput = document.getElementById('height');
    const bmiInput = document.getElementById('bmi');

    function updateBMI() {
        const w = parseFloat(weightInput.value);
        const h = parseFloat(heightInput.value);
        
        if (w > 0 && h > 0) {
            const hMeters = h / 100;
            const bmi = w / (hMeters * hMeters);
            bmiInput.value = bmi.toFixed(2);
        } else {
            bmiInput.value = '';
        }
    }

    if (weightInput && heightInput) {
        weightInput.addEventListener('input', updateBMI);
        heightInput.addEventListener('input', updateBMI);
    }

    // 2. Initialize DataTables for Vitals
    <?php if (!empty($vitalsHistory)): ?>
        $('#vitalsTable').DataTable({
            "paging": true,
            "lengthChange": false,
            "searching": false,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "order": [[0, "desc"]],
            "language": {
                "paginate": {
                    "previous": "<i class='bi bi-chevron-left'></i>",
                    "next": "<i class='bi bi-chevron-right'></i>"
                }
            }
        });
    <?php endif; ?>

    // 3. Initialize DataTables for Consultations
    <?php if (!empty($consultationsHistory)): ?>
        $('#consultationsTable').DataTable({
            "paging": true,
            "lengthChange": false,
            "searching": false,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "order": [[0, "desc"]],
            "columnDefs": [
                { "orderable": false, "targets": 4 }
            ],
            "language": {
                "paginate": {
                    "previous": "<i class='bi bi-chevron-left'></i>",
                    "next": "<i class='bi bi-chevron-right'></i>"
                }
            }
        });
    <?php endif; ?>

    // 3.5. Initialize DataTables for Appointments
    <?php if (!empty($appointmentsHistory)): ?>
        $('#appointmentsTable').DataTable({
            "paging": true,
            "lengthChange": false,
            "searching": false,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "order": [[0, "desc"], [1, "asc"]],
            "columnDefs": [
                { "orderable": false, "targets": 4 }
            ],
            "language": {
                "paginate": {
                    "previous": "<i class='bi bi-chevron-left'></i>",
                    "next": "<i class='bi bi-chevron-right'></i>"
                }
            }
        });
    <?php endif; ?>

    // 3.6. Initialize DataTables for Queue History
    <?php if (!empty($queueHistory)): ?>
        $('#queueTable').DataTable({
            "paging": true,
            "lengthChange": false,
            "searching": false,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "order": [[0, "desc"], [1, "desc"]],
            "language": {
                "paginate": {
                    "previous": "<i class='bi bi-chevron-left'></i>",
                    "next": "<i class='bi bi-chevron-right'></i>"
                }
            }
        });
    <?php endif; ?>

    // 4. Tab switching based on URL hash
    function switchTabByHash() {
        const hash = window.location.hash;
        if (hash) {
            const tabBtn = document.querySelector(`#profileTabs button${hash}`) 
                        || document.querySelector(`#profileTabs button[data-bs-target="${hash}"]`)
                        || document.querySelector(`#profileTabs button[data-bs-target="${hash.replace('-tab', '-panel')}"]`);
            if (tabBtn) {
                const tab = new bootstrap.Tab(tabBtn);
                tab.show();
            }
        }
    }

    switchTabByHash();
    window.addEventListener('hashchange', switchTabByHash);

    // Keep tab state on reload
    const tabButtons = document.querySelectorAll('#profileTabs button[data-bs-toggle="tab"]');
    tabButtons.forEach(btn => {
        btn.addEventListener('shown.bs.tab', function(e) {
            const targetId = e.target.id;
            if (targetId) {
                history.replaceState(null, null, '#' + targetId);
            }
        });
    });

    // 5. AJAX view consultation details
    const viewModal = document.getElementById('viewConsultationModal');
    const detailsContent = document.getElementById('consultationDetailsContent');
    const consultationsTable = document.getElementById('consultationsTable');

    if (consultationsTable) {
        consultationsTable.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-view-consultation');
            if (btn) {
                const id = btn.getAttribute('data-id');
                
                detailsContent.innerHTML = `
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="text-muted mt-2 small">Fetching consultation record...</p>
                    </div>
                `;
                
                fetch(`<?= url('/consultations/') ?>${id}`)
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(data => {
                        if (data.error) {
                            detailsContent.innerHTML = `
                                <div class="alert alert-danger" role="alert">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i> ${escapeHtml(data.error)}
                                </div>
                            `;
                            return;
                        }
                        
                        // Format Status Badge
                        let statusBadgeClass = 'bg-success text-white';
                        if (data.status === 'Open') statusBadgeClass = 'bg-info text-dark';
                        else if (data.status === 'Cancelled') statusBadgeClass = 'bg-danger text-white';
                        
                        // Format Vitals HTML
                        let vitalsHtml = '';
                        if (data.vital_signs_id) {
                            let bmiClass = 'bg-secondary';
                            const bmi = parseFloat(data.bmi);
                            if (bmi < 18.5) bmiClass = 'bg-info';
                            else if (bmi < 25) bmiClass = 'bg-success';
                            else if (bmi < 30) bmiClass = 'bg-warning text-dark';
                            else bmiClass = 'bg-danger';
                            
                            vitalsHtml = `
                                <ul class="list-unstyled mb-0 small">
                                    <li class="mb-2 d-flex justify-content-between">
                                        <span class="text-muted">BP:</span>
                                        <span class="fw-bold text-dark">${data.bp_systolic && data.bp_diastolic ? data.bp_systolic + '/' + data.bp_diastolic + ' mmHg' : '-'}</span>
                                    </li>
                                    <li class="mb-2 d-flex justify-content-between">
                                        <span class="text-muted">Pulse / Heart Rate:</span>
                                        <span class="fw-bold text-dark">${data.heart_rate ? data.heart_rate + ' bpm' : '-'}</span>
                                    </li>
                                    <li class="mb-2 d-flex justify-content-between">
                                        <span class="text-muted">Body Temp:</span>
                                        <span class="fw-bold text-dark">${data.temperature ? parseFloat(data.temperature).toFixed(1) + ' °C' : '-'}</span>
                                    </li>
                                    <li class="mb-2 d-flex justify-content-between">
                                        <span class="text-muted">Respiratory Rate:</span>
                                        <span class="fw-bold text-dark">${data.respiratory_rate ? data.respiratory_rate + ' cpm' : '-'}</span>
                                    </li>
                                    <li class="mb-2 d-flex justify-content-between">
                                        <span class="text-muted">Oxygen Saturation:</span>
                                        <span class="fw-bold text-dark">${data.oxygen_saturation ? data.oxygen_saturation + '%' : '-'}</span>
                                    </li>
                                    <li class="mb-2 d-flex justify-content-between">
                                        <span class="text-muted">Wt / Ht:</span>
                                        <span class="fw-bold text-dark">${data.weight || data.height ? (data.weight ? data.weight + ' kg' : '-') + ' / ' + (data.height ? data.height + ' cm' : '-') : '-'}</span>
                                    </li>
                                    <li class="mb-2 d-flex justify-content-between align-items-center">
                                        <span class="text-muted">BMI:</span>
                                        <span class="badge ${bmiClass}">${data.bmi ? parseFloat(data.bmi).toFixed(2) : '-'}</span>
                                    </li>
                                    ${data.vital_notes ? `
                                    <li class="mt-2 pt-2 border-top">
                                        <span class="text-muted d-block mb-1 small fw-semibold">Notes:</span>
                                        <span class="text-dark italic small">${escapeHtml(data.vital_notes)}</span>
                                    </li>` : ''}
                                </ul>
                            `;
                        } else {
                            vitalsHtml = `
                                <div class="text-center py-3 text-muted small">
                                    <i class="bi bi-heart-pulse-fill d-block fs-4 mb-2 text-muted opacity-50"></i>
                                    No vital signs linked.
                                </div>
                            `;
                        }
                        
                        detailsContent.innerHTML = `
                            <div class="row g-4">
                                <!-- SOAP Notes Section -->
                                <div class="col-12 col-md-7 border-end-md">
                                    <div class="mb-3">
                                        <span class="text-muted small fw-bold text-uppercase tracking-wider">Clinical Notes</span>
                                        <div class="d-flex align-items-center gap-2 mt-1">
                                            <span class="badge bg-primary-bg text-primary fw-semibold fs-7"><i class="bi bi-person-badge me-1"></i> ${escapeHtml(data.clinician_name)}</span>
                                            <span class="text-secondary small">&bull; ${escapeHtml(data.formatted_date)}</span>
                                        </div>
                                    </div>

                                    <div class="soap-block mb-3 p-3 bg-light rounded-3">
                                        <h6 class="fw-bold text-primary-dark mb-2 small"><span class="badge bg-primary me-2">S</span>Subjective (Chief Complaint)</h6>
                                        <p class="mb-0 text-dark small" style="white-space: pre-wrap;">${escapeHtml(data.subjective)}</p>
                                    </div>

                                    <div class="soap-block mb-3 p-3 bg-light rounded-3">
                                        <h6 class="fw-bold text-primary-dark mb-2 small"><span class="badge bg-primary me-2">O</span>Objective (Physical Findings)</h6>
                                        <p class="mb-0 text-dark small" style="white-space: pre-wrap;">${escapeHtml(data.objective)}</p>
                                    </div>

                                    <div class="soap-block mb-3 p-3 bg-light rounded-3">
                                        <h6 class="fw-bold text-primary-dark mb-2 small"><span class="badge bg-primary me-2">A</span>Assessment (Diagnosis)</h6>
                                        <p class="mb-0 text-dark small fw-semibold" style="white-space: pre-wrap;">${escapeHtml(data.assessment)}</p>
                                    </div>

                                    <div class="soap-block mb-0 p-3 bg-light rounded-3">
                                        <h6 class="fw-bold text-primary-dark mb-2 small"><span class="badge bg-primary me-2">P</span>Plan (Treatment & Advice)</h6>
                                        <p class="mb-0 text-dark small" style="white-space: pre-wrap;">${escapeHtml(data.plan)}</p>
                                    </div>
                                </div>

                                <!-- Vital Signs Section -->
                                <div class="col-12 col-md-5">
                                    <div class="mb-3">
                                        <span class="text-muted small fw-bold text-uppercase tracking-wider">Status</span>
                                        <div class="mt-2">
                                            <span class="badge ${statusBadgeClass}">${escapeHtml(data.status)}</span>
                                        </div>
                                    </div>

                                    <div class="card border-0 bg-light p-3 rounded-3">
                                        <h6 class="fw-bold text-dark mb-3 small"><i class="bi bi-heart-pulse-fill text-danger me-2"></i>Linked Vital Signs</h6>
                                        ${vitalsHtml}
                                    </div>
                                </div>
                            </div>
                        `;
                    })
                    .catch(error => {
                        console.error('Error fetching consultation details:', error);
                        detailsContent.innerHTML = `
                            <div class="alert alert-danger mb-0" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i> Failed to load consultation details.
                            </div>
                        `;
                    });
            }
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
});
</script>

<?php
$title = 'Queue Management';
require dirname(__DIR__) . '/layout/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold text-primary-dark" style="text-wrap: balance;">Daily Operations Queue</h1>
        <p class="text-secondary small mb-0" style="text-wrap: pretty;">Manage patient intake, calls, and service status for today: <strong style="font-variant-numeric: tabular-nums;"><?= date('F d, Y') ?></strong></p>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <!-- Live Polling Sync Indicator -->
        <div class="d-inline-flex align-items-center bg-white border rounded-3 px-2.5 py-1.5 shadow-xs" id="liveSyncWidget">
            <span class="badge bg-success-subtle text-success border border-success-subtle d-inline-flex align-items-center gap-1.5 py-1" id="liveSyncStatus">
                <span class="spinner-grow spinner-grow-sm text-success" role="status" aria-hidden="true" style="width: 8px; height: 8px;" id="syncPulse"></span>
                <span id="syncText">Live Sync (15s)</span>
            </span>
            <button type="button" class="btn btn-sm btn-link text-secondary p-0 ms-2" id="btnToggleSync" title="Pause Automatic Sync" aria-label="Pause automatic sync" style="text-decoration: none; min-height: 32px; min-width: 24px; display: inline-flex; align-items: center; justify-content: center;">
                <i class="bi bi-pause-fill fs-6" id="syncIcon" aria-hidden="true"></i>
            </button>
        </div>

        <a href="<?= url('/queue/display') ?>" target="_blank" class="btn btn-outline-primary d-inline-flex align-items-center px-3 gap-1" style="min-height: 38px;" aria-label="Open public queue display in new tab">
            <span>Open Public Display &nearr;</span>
        </a>
        <a href="<?= url('/queue') ?>" class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center px-2" title="Manual Refresh" aria-label="Refresh daily queue" style="min-width: 38px; min-height: 38px;">
            <i class="bi bi-arrow-clockwise fs-5" aria-hidden="true"></i>
        </a>
    </div>
</div>

<!-- Daily Queue Operational KPI Summary Cards -->
<div class="row g-3 mb-4" id="queueKpiCards">
    <!-- 1. Now Serving -->
    <div class="col-6 col-lg-3">
        <div class="card card-premium shadow-sm h-100 border">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Now Serving</div>
                        <div class="h3 mb-0 fw-bold text-primary-dark mt-1 font-monospace" id="kpiServingNo" style="font-variant-numeric: tabular-nums;">
                            <?= !empty($queueStats['serving_no']) ? sprintf('%03d', $queueStats['serving_no']) : '--' ?>
                        </div>
                    </div>
                    <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;" aria-hidden="true">
                        <i class="bi bi-broadcast fs-5"></i>
                    </div>
                </div>
                <div class="small text-muted mt-2 d-flex align-items-center gap-1 text-truncate" style="font-size: 0.75rem;" id="kpiServingService">
                    <?php if (!empty($queueStats['serving_no'])): ?>
                        <span class="fw-semibold text-primary"><?= h($queueStats['serving_service'] ?: 'General OPD') ?></span>
                    <?php else: ?>
                        <span>No patient active</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Waiting in Lobby -->
    <div class="col-6 col-lg-3">
        <div class="card card-premium shadow-sm h-100 border">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Waiting in Lobby</div>
                        <div class="h3 mb-0 fw-bold text-warning-emphasis mt-1" id="kpiWaitingCount" style="font-variant-numeric: tabular-nums;">
                            <?= (int)($queueStats['waiting'] ?? 0) ?>
                        </div>
                    </div>
                    <div class="bg-warning-subtle text-warning-emphasis rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;" aria-hidden="true">
                        <i class="bi bi-people fs-5"></i>
                    </div>
                </div>
                <div class="small text-muted mt-2" style="font-size: 0.75rem;">
                    <span>First-Come, First-Served</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Called / In Transit -->
    <div class="col-6 col-lg-3">
        <div class="card card-premium shadow-sm h-100 border">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Called / In Transit</div>
                        <div class="h3 mb-0 fw-bold text-info-emphasis mt-1" id="kpiCalledCount" style="font-variant-numeric: tabular-nums;">
                            <?= (int)($queueStats['called'] ?? 0) ?>
                        </div>
                    </div>
                    <div class="bg-info-subtle text-info-emphasis rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;" aria-hidden="true">
                        <i class="bi bi-megaphone fs-5"></i>
                    </div>
                </div>
                <div class="small text-muted mt-2" style="font-size: 0.75rem;">
                    <span>Awaiting room entry</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Completed Today -->
    <div class="col-6 col-lg-3">
        <div class="card card-premium shadow-sm h-100 border">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Completed Today</div>
                        <div class="h3 mb-0 fw-bold text-success mt-1" id="kpiCompletedCount" style="font-variant-numeric: tabular-nums;">
                            <?= (int)($queueStats['completed'] ?? 0) ?>
                        </div>
                    </div>
                    <div class="bg-success-subtle text-success rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;" aria-hidden="true">
                        <i class="bi bi-check-circle fs-5"></i>
                    </div>
                </div>
                <div class="small text-muted mt-2" style="font-size: 0.75rem;">
                    <span>Services finished</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Add Patient to Queue Form -->
    <div class="col-12 col-lg-4">
        <!-- Error Alert Banner -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger shadow-sm border-0 mb-4" role="alert" style="border-radius: 12px;">
                <h5 class="fw-bold mb-2 small"><i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i>Registration Failed:</h5>
                <ul class="mb-0 small ps-3">
                    <?php foreach ($errors as $error): ?>
                        <li><?= h($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Queue Registration Form Card -->
        <div class="card card-premium shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h2 class="card-title h6 mb-0 fw-bold text-dark" style="text-wrap: balance;">
                    Intake &amp; Enqueue Patient
                </h2>
                <button type="button" class="btn btn-sm btn-outline-secondary <?= empty($preselectedPatient) ? 'd-none' : '' ?>" id="btnChangePatient" style="min-height: 32px;">
                    Change Patient
                </button>
            </div>
            
            <form action="<?= url('/queue') ?>" method="POST" id="enqueueForm">
                <?= csrf_field() ?>
                <input type="hidden" name="patient_id" id="selectedPatientId" value="<?= !empty($preselectedPatient) ? h($preselectedPatient['id']) : '' ?>" required>

                <div class="card-body p-4 bg-white">
                    <!-- Client-side Error Alert Banner -->
                    <div class="alert alert-danger shadow-xs py-2 px-3 small mb-3 d-none" id="patientErrorAlert" role="alert">
                        <i class="bi bi-exclamation-circle-fill me-1" aria-hidden="true"></i> <strong>Patient Required:</strong> Please search and select an active patient before adding to the queue.
                    </div>

                    <!-- 1. Patient Live Search Section -->
                    <div id="patientSearchSection" class="<?= !empty($preselectedPatient) ? 'd-none' : '' ?>">
                        <label for="patientSearchInput" class="form-label fw-semibold text-secondary small">
                            Search Patient <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <div class="input-group mb-2 shadow-xs">
                            <span class="input-group-text bg-light border-end-0 text-muted" aria-hidden="true"><i class="bi bi-search"></i></span>
                            <input type="text" 
                                   id="patientSearchInput" 
                                   class="form-control bg-light border-start-0 fs-6" 
                                   placeholder="Type patient name, ID (PAT-), or env #…" 
                                   autocomplete="off"
                                   spellcheck="false"
                                   inputmode="search"
                                   role="combobox"
                                   aria-autocomplete="list"
                                   aria-expanded="false"
                                   aria-controls="searchResultsContainer">
                            <button type="button" class="btn btn-light border text-muted px-3 d-none" id="btnClearSearch" aria-label="Clear patient search" style="min-width: 38px; min-height: 38px;">
                                <i class="bi bi-x-lg" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="form-text small text-muted mb-3">
                            Search by name, ID, or envelope. Unregistered? <a href="<?= url('/patients/create') ?>" target="_blank" class="fw-semibold text-primary">Register first</a>.
                        </div>

                        <!-- Live Search Results Dropdown List -->
                        <div id="searchResultsContainer" class="list-group shadow-sm rounded-3 border mb-3 d-none" role="region" aria-live="polite" style="max-height: 250px; overflow-y: auto;">
                            <!-- Injected dynamically via JS -->
                        </div>
                        <div id="searchLoadingSpinner" class="text-center py-3 text-muted d-none" aria-live="polite">
                            <div class="spinner-border spinner-border-sm text-primary me-2" role="status" aria-hidden="true"></div>
                            <span class="small">Searching active patients…</span>
                        </div>
                    </div>

                    <!-- 2. Compact Patient Identity Card -->
                    <div id="patientIdentityCard" class="<?= empty($preselectedPatient) ? 'd-none' : '' ?> mb-3">
                        <div class="p-3 rounded-3 bg-light border">
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar-circle rounded-circle bg-primary-subtle text-primary border border-primary-subtle d-flex align-items-center justify-content-center fw-bold fs-5 shadow-xs flex-shrink-0" style="width: 44px; height: 44px;" aria-hidden="true">
                                    <span id="cardInitials">
                                        <?php 
                                            $init = '';
                                            if (!empty($preselectedPatient['first_name'])) $init .= mb_substr($preselectedPatient['first_name'], 0, 1);
                                            if (!empty($preselectedPatient['last_name'])) $init .= mb_substr($preselectedPatient['last_name'], 0, 1);
                                            echo strtoupper($init ?: 'PT');
                                        ?>
                                    </span>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <h3 class="h6 fw-bold text-dark mb-0.5 lh-sm text-truncate" id="cardFullName">
                                        <?= !empty($preselectedPatient) ? h($preselectedPatient['last_name'] . ', ' . $preselectedPatient['first_name']) : '--' ?>
                                    </h3>
                                    <div class="small text-secondary d-flex flex-wrap align-items-center" style="font-size: 0.8125rem; gap: 0.35rem;">
                                        <span id="cardAge" style="font-variant-numeric: tabular-nums;"><?= (!empty($preselectedPatient['age']) || (isset($preselectedPatient['age']) && $preselectedPatient['age'] === 0)) ? h($preselectedPatient['age']) . ' yrs' : '--' ?></span>
                                        <span class="text-secondary" aria-hidden="true">&bull;</span>
                                        <span id="cardSex"><?= !empty($preselectedPatient['sex']) ? ucfirst(h($preselectedPatient['sex'])) : '--' ?></span>
                                        <span class="text-secondary" aria-hidden="true">&bull;</span>
                                        <span class="font-monospace text-muted" id="cardPatientNo"><?= !empty($preselectedPatient['patient_no']) ? h($preselectedPatient['patient_no']) : '--' ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Service / Consultation Category Selection -->
                    <div class="mb-3">
                        <label for="service_type" class="form-label fw-semibold text-secondary small">
                            Service / Consultation Category <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <select name="service_type" id="service_type" class="form-select bg-light" required>
                            <option value="General OPD" <?= (!isset($input['service_type']) || $input['service_type'] === 'General OPD') ? 'selected' : '' ?>>General OPD / Adult Consultation</option>
                            <option value="Prenatal Care" <?= (isset($input['service_type']) && $input['service_type'] === 'Prenatal Care') ? 'selected' : '' ?>>Prenatal Care (Maternal Health)</option>
                            <option value="Well Baby Immunization" <?= (isset($input['service_type']) && $input['service_type'] === 'Well Baby Immunization') ? 'selected' : '' ?>>Well Baby Immunization (Pediatric/EPI)</option>
                            <option value="Senior Care" <?= (isset($input['service_type']) && $input['service_type'] === 'Senior Care') ? 'selected' : '' ?>>Senior Care (Geriatric Health)</option>
                            <option value="Family Planning" <?= (isset($input['service_type']) && $input['service_type'] === 'Family Planning') ? 'selected' : '' ?>>Family Planning</option>
                            <option value="Dental Care" <?= (isset($input['service_type']) && $input['service_type'] === 'Dental Care') ? 'selected' : '' ?>>Dental Care</option>
                            <option value="NCD / Hypertension" <?= (isset($input['service_type']) && $input['service_type'] === 'NCD / Hypertension') ? 'selected' : '' ?>>NCD / Hypertension &amp; Diabetes</option>
                        </select>
                    </div>
                </div>

                <div class="card-footer bg-light py-3 border-top d-grid">
                    <button type="submit" class="btn btn-primary fw-semibold py-2 d-inline-flex align-items-center justify-content-center gap-1" style="min-height: 42px;">
                        <i class="bi bi-ticket-perforated me-1" aria-hidden="true"></i> Issue Ticket &amp; Add to Queue
                    </button>
                </div>
            </form>
        </div>

        <!-- Today's Scheduled Appointments Check-In Card -->
        <div class="card card-premium shadow-sm mt-4" id="scheduledAppointmentsCard">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h2 class="card-title h6 mb-0 fw-bold text-dark" style="text-wrap: balance;">
                    Today's Bookings
                </h2>
                <?php
                    $enqueuedPatientIds = !empty($queueList) ? array_column($queueList, 'patient_id') : [];
                    $enqueuedMap = [];
                    foreach ($queueList as $item) {
                        $enqueuedMap[$item['patient_id']] = sprintf('%03d', $item['queue_no']);
                    }
                    $pendingAppointments = array_filter($todayAppointments ?? [], function($a) use ($enqueuedPatientIds) {
                        return !in_array($a['patient_id'], $enqueuedPatientIds);
                    });
                ?>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle small fw-semibold" id="pendingAppointmentsBadge" style="font-variant-numeric: tabular-nums;">
                    <?= count($pendingAppointments) ?> pending
                </span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($todayAppointments)): ?>
                    <div class="p-4 text-center text-muted small">
                        <i class="bi bi-calendar2-check d-block fs-3 mb-2 text-muted" aria-hidden="true"></i>
                        No appointments scheduled for today.
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush small" style="max-height: 290px; overflow-y: auto;" id="todayAppointmentsList">
                        <?php foreach ($todayAppointments as $appt): 
                            $isAlreadyQueued = isset($enqueuedMap[$appt['patient_id']]);
                            $qNo = $isAlreadyQueued ? $enqueuedMap[$appt['patient_id']] : null;
                            
                            $prog = $appt['program_type'] ?? 'General OPD';
                            $serviceVal = 'General OPD';
                            if (in_array($prog, ['Maternal Care', 'Prenatal Care'])) $serviceVal = 'Prenatal Care';
                            elseif (in_array($prog, ['Well-Baby Care', 'Well Baby Immunization'])) $serviceVal = 'Well Baby Immunization';
                            elseif ($prog === 'Senior Care') $serviceVal = 'Senior Care';
                            elseif ($prog === 'Dental Care') $serviceVal = 'Dental Care';
                            elseif ($prog === 'Family Planning') $serviceVal = 'Family Planning';
                            elseif ($prog === 'NCD / Hypertension') $serviceVal = 'NCD / Hypertension';
                        ?>
                            <div class="list-group-item p-3 d-flex align-items-center justify-content-between gap-2 border-bottom">
                                <div class="min-w-0">
                                    <div class="fw-bold text-dark text-truncate">
                                        <?= h($appt['patient_last']) ?>, <?= h($appt['patient_first']) ?>
                                    </div>
                                    <div class="text-muted d-flex align-items-center gap-1.5 flex-wrap mt-0.5" style="font-size: 0.73rem;">
                                        <span class="badge-patient-id"><?= h($appt['patient_no']) ?></span>
                                        <span style="font-variant-numeric: tabular-nums;"><?= date('h:i A', strtotime($appt['appointment_time'])) ?></span>
                                        <span aria-hidden="true">&bull;</span>
                                        <span class="badge bg-light text-primary border"><?= h($serviceVal) ?></span>
                                    </div>
                                </div>
                                <div class="flex-shrink-0 text-end">
                                    <?php if ($isAlreadyQueued): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle" title="Already added to today's queue" style="font-variant-numeric: tabular-nums;">
                                            Queue #<?= $qNo ?>
                                        </span>
                                    <?php else: ?>
                                        <form action="<?= url('/queue') ?>" method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="patient_id" value="<?= (int)$appt['patient_id'] ?>">
                                            <input type="hidden" name="service_type" value="<?= h($serviceVal) ?>">
                                            <input type="hidden" name="appointment_id" value="<?= (int)$appt['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-primary py-1 px-2.5 btn-queue-action d-inline-flex align-items-center gap-1" data-action="checkin" data-patient="<?= h($appt['patient_first'] . ' ' . $appt['patient_last']) ?>" data-service="<?= h($serviceVal) ?>" title="Check-in to Queue" style="min-height: 31px;">
                                                <i class="bi bi-ticket-perforated me-1" aria-hidden="true"></i> Check In
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Today's Queue Active List -->
    <div class="col-12 col-lg-8">
        <div class="card card-premium shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h2 class="card-title h6 mb-0 fw-bold text-dark" style="text-wrap: balance;">
                    Today's Active Queue List
                </h2>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-secondary border font-monospace" title="Standard clinic intake order">
                        FCFS Order
                    </span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle small fw-semibold" style="font-variant-numeric: tabular-nums;"><?= count($queueList) ?> total entries</span>
                </div>
            </div>

            <!-- Quick Status Filter Chips Toolbar -->
            <div class="px-3 py-2 bg-light border-bottom d-flex flex-wrap align-items-center gap-2" id="queueStatusFilters">
                <span class="small fw-semibold text-muted me-1">Filter:</span>
                <button type="button" class="btn btn-sm btn-primary active quick-queue-filter" data-filter="all" style="min-height: 31px;">
                    All (<span id="filterCountAll" style="font-variant-numeric: tabular-nums;"><?= count($queueList) ?></span>)
                </button>
                <button type="button" class="btn btn-sm btn-light border quick-queue-filter" data-filter="Waiting" style="min-height: 31px;">
                    Waiting (<span id="filterCountWaiting" style="font-variant-numeric: tabular-nums;"><?= (int)($queueStats['waiting'] ?? 0) ?></span>)
                </button>
                <button type="button" class="btn btn-sm btn-light border quick-queue-filter" data-filter="Called|Serving" style="min-height: 31px;">
                    Called / Serving (<span id="filterCountCalledServing" style="font-variant-numeric: tabular-nums;"><?= (int)(($queueStats['called'] ?? 0) + ($queueStats['serving'] ?? 0)) ?></span>)
                </button>
                <button type="button" class="btn btn-sm btn-light border quick-queue-filter" data-filter="Completed" style="min-height: 31px;">
                    Completed (<span id="filterCountCompleted" style="font-variant-numeric: tabular-nums;"><?= (int)($queueStats['completed'] ?? 0) ?></span>)
                </button>
            </div>
            
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center small" id="queueTable">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Queue No.</th>
                                <th scope="col" class="text-start">Patient Name</th>
                                <th scope="col" class="text-start">Service</th>
                                <th scope="col">Time In</th>
                                <th scope="col">Time Called</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="pe-4 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($queueList)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-people d-block fs-3 mb-2 text-muted" aria-hidden="true"></i>
                                        No patients are currently queued for today.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($queueList as $q): 
                                    $statusBadge = 'bg-secondary-subtle text-secondary border';
                                    if ($q['status'] === 'Waiting') $statusBadge = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                                    elseif ($q['status'] === 'Called') $statusBadge = 'bg-primary-subtle text-primary border border-primary-subtle';
                                    elseif ($q['status'] === 'Serving') $statusBadge = 'bg-info-subtle text-info-emphasis border border-info-subtle';
                                    elseif ($q['status'] === 'Completed') $statusBadge = 'bg-success-subtle text-success border border-success-subtle';
                                    elseif ($q['status'] === 'Cancelled') $statusBadge = 'bg-danger-subtle text-danger border border-danger-subtle';

                                    // Service type styling
                                    $service = $q['service_type'] ?? 'General OPD';
                                    $serviceBadgeClass = 'bg-light text-secondary border';
                                    if ($service === 'Prenatal Care') $serviceBadgeClass = 'bg-primary-subtle text-primary border border-primary-subtle';
                                    elseif ($service === 'Well Baby Immunization') $serviceBadgeClass = 'bg-info-subtle text-info-emphasis border border-info-subtle';
                                    elseif ($service === 'Senior Care') $serviceBadgeClass = 'bg-secondary-subtle text-secondary-emphasis border';
                                    elseif ($service === 'Dental Care') $serviceBadgeClass = 'bg-dark-subtle text-dark border';
                                    elseif ($service === 'NCD / Hypertension') $serviceBadgeClass = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';

                                    // Time In & Wait Duration
                                    $timeInStamp = !empty($q['time_in']) ? strtotime($q['time_in']) : null;
                                    $elapsedWaitMins = null;
                                    if ($timeInStamp && in_array($q['status'], ['Waiting', 'Called'])) {
                                        $elapsedWaitMins = max(0, (int)floor((time() - $timeInStamp) / 60));
                                    }
                                ?>
                                    <tr>
                                        <td class="fw-bold fs-6 text-primary-dark" data-order="<?= (int)$q['queue_no'] ?>" style="font-variant-numeric: tabular-nums;">
                                            <?= sprintf('%03d', $q['queue_no']) ?>
                                        </td>
                                        <td class="text-start fw-bold">
                                            <a href="<?= url('/patients/' . $q['patient_id']) ?>" class="link-primary-dark">
                                                <?= h($q['patient_last']) ?>, <?= h($q['patient_first']) ?>
                                                <span class="text-muted fw-normal font-monospace fs-7" style="font-variant-numeric: tabular-nums;">(<?= h($q['patient_no']) ?>)</span>
                                            </a>
                                        </td>
                                        <td class="text-start">
                                            <span class="badge <?= $serviceBadgeClass ?> d-inline-block"><?= h($service) ?></span>
                                        </td>
                                        <td class="text-secondary" data-order="<?= $timeInStamp ?: 0 ?>">
                                            <div style="font-variant-numeric: tabular-nums;"><?= $timeInStamp ? date('h:i A', $timeInStamp) : '--' ?></div>
                                            <?php if ($elapsedWaitMins !== null): ?>
                                                <?php if ($elapsedWaitMins > 30): ?>
                                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle mt-1" style="font-size: 0.72rem; font-variant-numeric: tabular-nums;" title="Waiting over 30 minutes">
                                                        <i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i><?= $elapsedWaitMins ?>m wait
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-muted border mt-1" style="font-size: 0.72rem; font-variant-numeric: tabular-nums;">
                                                        <?= $elapsedWaitMins ?>m wait
                                                    </span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-secondary" data-order="<?= !empty($q['time_called']) ? strtotime($q['time_called']) : 0 ?>" style="font-variant-numeric: tabular-nums;">
                                            <?= !empty($q['time_called']) ? date('h:i A', strtotime($q['time_called'])) : '<span class="text-muted">-</span>' ?>
                                        </td>
                                        <td data-search="<?= h($q['status']) ?>" data-filter="<?= h($q['status']) ?>">
                                            <span class="badge <?= $statusBadge ?>"><?= h($q['status']) ?></span>
                                        </td>
                                        <td class="pe-4 text-end" style="white-space: nowrap;">
                                            <div class="d-inline-flex gap-1 align-items-center">
                                                <?php if ($q['status'] === 'Waiting'): ?>
                                                    <!-- Transition to Called -->
                                                    <form action="<?= url('/queue/' . $q['id'] . '/status') ?>" method="POST" class="d-inline">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="status" value="Called">
                                                        <button type="submit" class="btn btn-sm btn-primary px-2 py-1 btn-queue-action" data-action="call" data-queue-no="<?= sprintf('%03d', $q['queue_no']) ?>" data-patient="<?= h($q['patient_first'] . ' ' . $q['patient_last']) ?>" data-service="<?= h($service) ?>" title="Call Patient" style="min-height: 32px;">
                                                            Call
                                                        </button>
                                                    </form>
                                                <?php elseif ($q['status'] === 'Called'): ?>
                                                    <!-- Transition to Serving -->
                                                    <form action="<?= url('/queue/' . $q['id'] . '/status') ?>" method="POST" class="d-inline">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="status" value="Serving">
                                                        <button type="submit" class="btn btn-sm btn-info px-2 py-1 text-white btn-queue-action" data-action="serve" data-queue-no="<?= sprintf('%03d', $q['queue_no']) ?>" data-patient="<?= h($q['patient_first'] . ' ' . $q['patient_last']) ?>" title="Start Serving" style="min-height: 32px;">
                                                            Serve
                                                        </button>
                                                    </form>
                                                    <!-- Re-call Patient (plays chime) -->
                                                    <form action="<?= url('/queue/' . $q['id'] . '/status') ?>" method="POST" class="d-inline">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="status" value="Called">
                                                        <button type="submit" class="btn btn-sm btn-outline-primary px-2 py-1 btn-queue-action" data-action="recall" data-queue-no="<?= sprintf('%03d', $q['queue_no']) ?>" title="Re-Call Patient (Audio chime)" data-confirm="Re-call Queue No: <?= sprintf('%03d', $q['queue_no']) ?>? This will re-trigger the chime on the public display." style="min-height: 32px;">
                                                            Re-Call
                                                        </button>
                                                    </form>
                                                <?php elseif ($q['status'] === 'Serving'): ?>
                                                    <!-- Transition to Completed -->
                                                    <form action="<?= url('/queue/' . $q['id'] . '/status') ?>" method="POST" class="d-inline">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="status" value="Completed">
                                                        <button type="submit" class="btn btn-sm btn-success px-2 py-1 btn-queue-action" data-action="complete" data-queue-no="<?= sprintf('%03d', $q['queue_no']) ?>" data-patient="<?= h($q['patient_first'] . ' ' . $q['patient_last']) ?>" title="Complete Service" style="min-height: 32px;">
                                                            Complete
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <?php if (in_array($q['status'], ['Waiting', 'Called', 'Serving'])): ?>
                                                    <!-- Transition to Cancelled -->
                                                    <form action="<?= url('/queue/' . $q['id'] . '/status') ?>" method="POST" class="d-inline">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="status" value="Cancelled">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger border-0 px-2 btn-queue-cancel" data-queue-no="<?= sprintf('%03d', $q['queue_no']) ?>" data-patient="<?= h($q['patient_first'] . ' ' . $q['patient_last']) ?>" title="Cancel Queue Number" aria-label="Cancel queue number <?= sprintf('%03d', $q['queue_no']) ?>" style="min-width: 32px; min-height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                                                            <i class="bi bi-x-lg" aria-hidden="true"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
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
</div>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Initialize DataTable only if table is not empty
    <?php if (!empty($queueList)): ?>
        const queueDataTable = $('#queueTable').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "order": [[0, "asc"]], // Order by Queue No ascending
            "columnDefs": [
                { "orderable": false, "targets": 6 } // Disable sorting on action column (index 6)
            ],
            "language": {
                "paginate": {
                    "previous": "<i class='bi bi-chevron-left'></i>",
                    "next": "<i class='bi bi-chevron-right'></i>"
                }
            }
        });

        // 1-Click Quick Status Filter Buttons
        const quickFilterBtns = document.querySelectorAll('.quick-queue-filter');
        quickFilterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                quickFilterBtns.forEach(b => {
                    b.classList.remove('active', 'btn-primary', 'text-white');
                    b.classList.add('btn-light', 'border');
                });
                this.classList.remove('btn-light', 'border');
                this.classList.add('active', 'btn-primary', 'text-white');

                const filterVal = this.getAttribute('data-filter');
                if (filterVal === 'all') {
                    queueDataTable.column(5).search('').draw();
                } else if (filterVal === 'Called|Serving') {
                    queueDataTable.column(5).search('Called|Serving', true, false).draw();
                } else {
                    queueDataTable.column(5).search(filterVal, false, false).draw();
                }
            });
        });
    <?php endif; ?>

    // 2. Patient Live Search
    const searchInput = document.getElementById('patientSearchInput');
    const clearSearchBtn = document.getElementById('btnClearSearch');
    const resultsContainer = document.getElementById('searchResultsContainer');
    const loadingSpinner = document.getElementById('searchLoadingSpinner');
    const selectedPatientIdInput = document.getElementById('selectedPatientId');
    const searchSection = document.getElementById('patientSearchSection');
    const identityCard = document.getElementById('patientIdentityCard');
    const btnChangePatient = document.getElementById('btnChangePatient');
    const enqueueForm = document.getElementById('enqueueForm');
    const patientErrorAlert = document.getElementById('patientErrorAlert');
    const serviceTypeSelect = document.getElementById('service_type');

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    let searchDebounceTimer = null;

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            if (clearSearchBtn) {
                clearSearchBtn.classList.toggle('d-none', query.length === 0);
            }

            clearTimeout(searchDebounceTimer);
            if (query.length < 1) {
                resultsContainer.classList.add('d-none');
                resultsContainer.innerHTML = '';
                searchInput.setAttribute('aria-expanded', 'false');
                return;
            }

            loadingSpinner.classList.remove('d-none');
            searchDebounceTimer = setTimeout(() => {
                fetch('<?= url('/api/patients/search/all') ?>?q=' + encodeURIComponent(query))
                    .then(response => response.json())
                    .then(data => {
                        loadingSpinner.classList.add('d-none');
                        renderSearchResults(data.results || []);
                    })
                    .catch(err => {
                        loadingSpinner.classList.add('d-none');
                        console.error('Error searching patients:', err);
                    });
            }, 250);
        });

        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', function() {
                searchInput.value = '';
                clearSearchBtn.classList.add('d-none');
                resultsContainer.classList.add('d-none');
                resultsContainer.innerHTML = '';
                searchInput.setAttribute('aria-expanded', 'false');
                searchInput.focus();
            });
        }
    }

    function renderSearchResults(patients) {
        resultsContainer.innerHTML = '';
        if (patients.length === 0) {
            resultsContainer.innerHTML = `
                <div class="list-group-item text-center py-4 text-muted">
                    <i class="bi bi-person-x fs-3 d-block mb-1 text-secondary" aria-hidden="true"></i>
                    No registered patients found matching your search.
                </div>
            `;
            resultsContainer.classList.remove('d-none');
            if (searchInput) searchInput.setAttribute('aria-expanded', 'true');
            return;
        }

        patients.forEach(p => {
            const item = document.createElement('div');
            item.className = 'list-group-item list-group-item-action py-2.5 px-3 patient-search-item';
            item.style.cursor = 'pointer';
            item.setAttribute('role', 'button');
            item.setAttribute('tabindex', '0');
            item.setAttribute('aria-label', `Select patient ${p.name || ''}, ${p.patient_no || ''}`);

            const ageText = (p.age !== undefined && p.age !== null) ? `${p.age} yrs` : '--';
            const sexText = p.sex ? escapeHtml(p.sex) : 'N/A';
            const idText = p.patient_no ? escapeHtml(p.patient_no) : '';

            item.innerHTML = `
                <div>
                    <div class="fw-bold text-dark fs-6 mb-0.5">${escapeHtml(p.name)}</div>
                    <div class="small text-muted d-flex align-items-center flex-wrap" style="gap: 0.35rem;">
                        <span>${ageText}</span>
                        <span class="text-secondary" aria-hidden="true">&bull;</span>
                        <span>${sexText}</span>
                        ${idText ? `<span class="text-secondary" aria-hidden="true">&bull;</span><span class="font-monospace text-secondary">${idText}</span>` : ''}
                    </div>
                </div>
            `;

            item.addEventListener('click', () => selectPatient(p));
            item.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    selectPatient(p);
                }
            });
            resultsContainer.appendChild(item);
        });

        resultsContainer.classList.remove('d-none');
        if (searchInput) searchInput.setAttribute('aria-expanded', 'true');
    }

    function selectPatient(patient) {
        resultsContainer.classList.add('d-none');
        resultsContainer.innerHTML = '';
        if (searchInput) {
            searchInput.value = '';
            searchInput.setAttribute('aria-expanded', 'false');
        }
        if (patientErrorAlert) patientErrorAlert.classList.add('d-none');

        selectedPatientIdInput.value = patient.id;

        // Update Identity Card
        document.getElementById('cardFullName').textContent = patient.name;
        document.getElementById('cardPatientNo').textContent = patient.patient_no || '--';
        document.getElementById('cardAge').textContent = (patient.age !== undefined && patient.age !== null) ? (patient.age + ' yrs') : '--';
        const cardAddressEl = document.getElementById('cardAddress');
        if (cardAddressEl) cardAddressEl.textContent = patient.address || 'Barangay Sinalhan';

        const sexEl = document.getElementById('cardSex');
        if (sexEl) {
            sexEl.textContent = patient.sex || 'N/A';
            sexEl.className = '';
        }

        const initials = ((patient.first_name ? patient.first_name[0] : '') + (patient.last_name ? patient.last_name[0] : '')).toUpperCase() || 'PT';
        document.getElementById('cardInitials').textContent = initials;

        // Show identity card, hide search section
        if (searchSection) searchSection.classList.add('d-none');
        if (identityCard) identityCard.classList.remove('d-none');
        if (btnChangePatient) btnChangePatient.classList.remove('d-none');
    }

    // Change patient button
    if (btnChangePatient) {
        btnChangePatient.addEventListener('click', function() {
            selectedPatientIdInput.value = '';
            if (identityCard) identityCard.classList.add('d-none');
            if (searchSection) searchSection.classList.remove('d-none');
            if (btnChangePatient) btnChangePatient.classList.add('d-none');
            if (searchInput) {
                searchInput.value = '';
                searchInput.focus();
            }
        });
    }

    // Form submit validation
    if (enqueueForm) {
        enqueueForm.addEventListener('submit', function(e) {
            if (!selectedPatientIdInput.value || selectedPatientIdInput.value === '0') {
                e.preventDefault();
                if (patientErrorAlert) {
                    patientErrorAlert.classList.remove('d-none');
                    patientErrorAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                if (searchInput) searchInput.focus();
                return false;
            }
        });
    }

    // 3. Accessible SweetAlert2 Confirmation Handlers for Queue Actions
    document.addEventListener('click', function(e) {
        // A. Queue Ticket Cancellation with Reason Selection
        const cancelBtn = e.target.closest('.btn-queue-cancel');
        if (cancelBtn) {
            e.preventDefault();
            e.stopPropagation();
            const form = cancelBtn.closest('form');
            const queueNo = cancelBtn.getAttribute('data-queue-no') || '';
            const patientName = cancelBtn.getAttribute('data-patient') || 'patient';

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Cancel Queue Ticket #' + queueNo + '?',
                    text: 'Please select the reason for cancelling ticket #' + queueNo + ' (' + patientName + '):',
                    icon: 'warning',
                    input: 'select',
                    inputOptions: {
                        'No Show / Patient Left': 'No Show / Patient Left',
                        'Duplicate Entry': 'Duplicate Entry',
                        'Patient Request / Rescheduled': 'Patient Request / Rescheduled',
                        'Doctor Rescheduled / Clinic Closed': 'Doctor Rescheduled / Clinic Closed',
                        'Other': 'Other Reason'
                    },
                    inputPlaceholder: '-- Select Cancellation Reason --',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Confirm Cancellation',
                    cancelButtonText: 'Keep Ticket',
                    inputValidator: (value) => {
                        if (!value) {
                            return 'You must select a cancellation reason!';
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        let reasonInput = form.querySelector('input[name="cancel_reason"]');
                        if (!reasonInput) {
                            reasonInput = document.createElement('input');
                            reasonInput.type = 'hidden';
                            reasonInput.name = 'cancel_reason';
                            form.appendChild(reasonInput);
                        }
                        reasonInput.value = result.value;
                        form.submit();
                    }
                });
            } else {
                if (confirm('Cancel Queue Ticket #' + queueNo + '?')) {
                    form.submit();
                }
            }
            return;
        }

        // B. Queue Lifecycle Actions (Check-In, Call, Serve, Re-Call, Complete)
        const actionBtn = e.target.closest('.btn-queue-action');
        if (actionBtn) {
            e.preventDefault();
            e.stopPropagation();
            const form = actionBtn.closest('form');
            const action = actionBtn.getAttribute('data-action');
            const queueNo = actionBtn.getAttribute('data-queue-no') || '';
            const patientName = actionBtn.getAttribute('data-patient') || '';
            const service = actionBtn.getAttribute('data-service') || '';

            let title = 'Confirm Action';
            let text = 'Proceed with updating Queue No: ' + queueNo + '?';
            let icon = 'question';
            let confirmText = 'Yes, Proceed';
            let confirmColor = '#0D7377';

            if (action === 'checkin') {
                title = 'Check In Patient?';
                text = 'Issue a queue ticket for ' + patientName + ' [' + service + '] and add to waiting line?';
                icon = 'question';
                confirmText = 'Issue Ticket';
                confirmColor = '#0D7377';
            } else if (action === 'call') {
                title = 'Call Ticket #' + queueNo + '?';
                text = 'Announce ticket #' + queueNo + (patientName ? ' (' + patientName + ')' : '') + (service ? ' for ' + service : '') + ' in the waiting lobby?';
                icon = 'question';
                confirmText = 'Call Patient';
                confirmColor = '#0D7377';
            } else if (action === 'serve') {
                title = 'Start Serving Ticket #' + queueNo + '?';
                text = 'Mark ticket #' + queueNo + (patientName ? ' (' + patientName + ')' : '') + ' as currently in consultation?';
                icon = 'info';
                confirmText = 'Start Serving';
                confirmColor = '#087990';
            } else if (action === 'recall') {
                title = 'Re-Call Ticket #' + queueNo + '?';
                text = 'Re-trigger the audio chime and public monitor alert for ticket #' + queueNo + '?';
                icon = 'question';
                confirmText = 'Re-Call (Chime)';
                confirmColor = '#0D7377';
            } else if (action === 'complete') {
                title = 'Complete Consultation for #' + queueNo + '?';
                text = 'Mark visit for ticket #' + queueNo + (patientName ? ' (' + patientName + ')' : '') + ' as successfully finished?';
                icon = 'success';
                confirmText = 'Complete Visit';
                confirmColor = '#198754';
            }

            const executeAction = () => {
                if ((action === 'call' || action === 'recall') && typeof window.playHospitalChime === 'function') {
                    window.playHospitalChime(true);
                }

                // Temporary loading state on action button
                actionBtn.disabled = true;
                const originalHtml = actionBtn.innerHTML;
                actionBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

                const formData = new FormData(form);
                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: action === 'recall' ? 'Announcement Re-triggered!' : 'Success',
                                text: data.message,
                                timer: 1500,
                                timerProgressBar: true,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            window.location.reload();
                        }
                    } else {
                        actionBtn.disabled = false;
                        actionBtn.innerHTML = originalHtml;
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Action Failed',
                                text: data.error || 'Failed to update queue status.'
                            });
                        } else {
                            alert(data.error || 'Failed to update queue status.');
                        }
                    }
                })
                .catch(err => {
                    // Fallback to traditional form submit if network fails
                    form.submit();
                });
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: title,
                    text: text,
                    icon: icon,
                    showCancelButton: true,
                    confirmButtonColor: confirmColor,
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: confirmText,
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        executeAction();
                    }
                });
            } else {
                if (confirm(text)) {
                    executeAction();
                }
            }
            return;
        }
    }, true);

    // 4. Multi-Station Live Background Polling (15-second sync)
    let isSyncActive = true;
    let lastKnownQueueHash = null;

    const liveSyncStatus = document.getElementById('liveSyncStatus');
    const syncPulse = document.getElementById('syncPulse');
    const syncText = document.getElementById('syncText');
    const btnToggleSync = document.getElementById('btnToggleSync');
    const syncIcon = document.getElementById('syncIcon');

    function updateSyncUI(active) {
        if (active) {
            if (liveSyncStatus) liveSyncStatus.className = 'badge bg-success-subtle text-success border border-success-subtle d-inline-flex align-items-center gap-1.5 py-1';
            if (syncPulse) syncPulse.classList.remove('d-none');
            if (syncText) syncText.textContent = 'Live Sync (15s)';
            if (syncIcon) syncIcon.className = 'bi bi-pause-fill fs-6';
            if (btnToggleSync) btnToggleSync.title = 'Pause Automatic Sync';
        } else {
            if (liveSyncStatus) liveSyncStatus.className = 'badge bg-secondary-subtle text-secondary border d-inline-flex align-items-center gap-1.5 py-1';
            if (syncPulse) syncPulse.classList.add('d-none');
            if (syncText) syncText.textContent = 'Sync Paused';
            if (syncIcon) syncIcon.className = 'bi bi-play-fill fs-6';
            if (btnToggleSync) btnToggleSync.title = 'Resume Automatic Sync';
        }
    }

    if (btnToggleSync) {
        btnToggleSync.addEventListener('click', function() {
            isSyncActive = !isSyncActive;
            updateSyncUI(isSyncActive);
            if (isSyncActive) {
                triggerBackgroundPoll();
            }
        });
    }

    function triggerBackgroundPoll() {
        if (!isSyncActive) return;

        fetch('<?= url('/api/queue/active-today') ?>', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (!data || !data.success) return;

            // Update KPI Card Numbers
            const stats = data.stats || {};
            const elServingNo = document.getElementById('kpiServingNo');
            const elServingService = document.getElementById('kpiServingService');
            const elWaitingCount = document.getElementById('kpiWaitingCount');
            const elCalledCount = document.getElementById('kpiCalledCount');
            const elCompletedCount = document.getElementById('kpiCompletedCount');

            if (elServingNo) {
                elServingNo.textContent = stats.serving_no ? String(stats.serving_no).padStart(3, '0') : '--';
            }
            if (elServingService) {
                elServingService.innerHTML = stats.serving_no 
                    ? '<span class="fw-semibold text-primary">' + escapeHtml(stats.serving_service || 'General OPD') + '</span>'
                    : '<span>No patient active</span>';
            }
            if (elWaitingCount) elWaitingCount.textContent = stats.waiting || 0;
            if (elCalledCount) elCalledCount.textContent = stats.called || 0;
            if (elCompletedCount) elCompletedCount.textContent = stats.completed || 0;

            // Update Filter Button Counters
            const fcAll = document.getElementById('filterCountAll');
            const fcWait = document.getElementById('filterCountWaiting');
            const fcCalled = document.getElementById('filterCountCalledServing');
            const fcComp = document.getElementById('filterCountCompleted');

            if (fcAll) fcAll.textContent = (data.queue ? data.queue.length : 0);
            if (fcWait) fcWait.textContent = stats.waiting || 0;
            if (fcCalled) fcCalled.textContent = ((stats.called || 0) + (stats.serving || 0));
            if (fcComp) fcComp.textContent = stats.completed || 0;

            // Multi-station state synchronization
            const queueSignature = (data.queue || []).map(q => q.id + ':' + q.status + ':' + (q.time_called || '')).join('|');
            if (lastKnownQueueHash !== null && lastKnownQueueHash !== queueSignature) {
                const isTyping = searchInput && searchInput.value.trim().length > 0;
                const isModalOpen = document.querySelector('.swal2-container') !== null;
                if (!isTyping && !isModalOpen) {
                    window.location.reload();
                }
            }
            lastKnownQueueHash = queueSignature;
        })
        .catch(err => {
            console.warn('Queue live sync error:', err);
        });
    }

    // Set 15-second background interval timer
    setInterval(triggerBackgroundPoll, 15000);
});
</script>

<?php
$title = 'Health Center Reports';
require dirname(__DIR__) . '/layout/header.php';

// Format Report Type Labels
$reportLabels = [
    'daily_visits' => 'Daily Patient Visits Log',
    'consultations' => 'Clinical Consultations Summary',
    'registrations' => 'Patient Registrations Summary',
    'queue_summary' => 'Daily Queue Operations Summary',
    'vitals' => 'Recorded Vital Signs Log',
    'maternal_health' => 'Maternal & Prenatal Health Registry',
    'epi_coverage' => 'Childhood Routine Immunization (EPI) Coverage',
    'chronic_morbidity' => 'Morbidity & Chronic Disease Registry (IHP)'
];

$reportName = $reportLabels[$type] ?? '';
$allTime = !empty($allTime);
?>

<style>
/* Print Styles */
@media print {
    body {
        background: #fff !important;
        color: #000 !important;
        font-size: 11pt !important;
    }
    .app-sidebar, 
    .app-main > header, 
    .breadcrumb, 
    .no-print, 
    .card-filter-card,
    .btn, 
    form, 
    footer,
    .dataTables_length,
    .dataTables_filter,
    .dataTables_info,
    .dataTables_paginate {
        display: none !important;
    }
    .app-main {
        padding: 0 !important;
        margin: 0 !important;
    }
    .print-report-card {
        border: none !important;
        box-shadow: none !important;
        background: transparent !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .table-responsive {
        overflow: visible !important;
    }
    table {
        width: 100% !important;
        border-collapse: collapse !important;
        page-break-inside: auto;
    }
    tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }
    thead {
        display: table-header-group;
    }
    th, td {
        border: 1px solid #6c757d !important;
        color: #000 !important;
        padding: 6px 10px !important;
        font-size: 9.5pt !important;
    }
    .print-header {
        display: block !important;
        border-bottom: 3px double #333;
        margin-bottom: 25px;
        padding-bottom: 10px;
    }
    .print-signatory {
        display: block !important;
        page-break-inside: avoid;
    }
}
@media screen {
    .print-header,
    .print-signatory {
        display: none !important;
    }
}
.btn-preset {
    font-size: 0.72rem;
    padding: 0.2rem 0.45rem;
    border-radius: 6px;
}
.bg-pink {
    background-color: #d63384;
}
.bg-teal {
    background-color: #20c997;
}
.bg-teal-soft {
    background-color: rgba(32, 201, 151, 0.15);
}
</style>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 no-print">
    <div>
        <h2 class="h3 mb-1 fw-bold text-primary-dark">Operational Reports</h2>
        <p class="text-secondary small mb-0">Generate, analyze, print, and export official daily registers, morbidity statistics, and DOH program registries.</p>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Filter Panel (Hidden in print) -->
    <div class="col-12 col-lg-3 no-print">
        <div class="card card-premium card-filter-card">
            <div class="card-header bg-white py-3 border-bottom">
                <h3 class="card-title h6 mb-0 fw-bold text-dark">
                    <i class="bi bi-funnel text-primary me-2"></i>Report Criteria
                </h3>
            </div>
            
            <form action="<?= url('/reports') ?>" method="GET" id="reportForm">
                <div class="card-body p-4 bg-white">
                    <!-- Report Type -->
                    <div class="mb-3">
                        <label for="type" class="form-label text-secondary small fw-semibold">Report Category <span class="text-danger">*</span></label>
                        <select name="type" id="type" class="form-select bg-light" required>
                            <option value="">-- Select Report --</option>
                            <option value="daily_visits" <?= $type === 'daily_visits' ? 'selected' : '' ?>>Daily Patient Visits</option>
                            <option value="consultations" <?= $type === 'consultations' ? 'selected' : '' ?>>Consultations Summary</option>
                            <option value="registrations" <?= $type === 'registrations' ? 'selected' : '' ?>>Patient Registrations</option>
                            <option value="maternal_health" <?= $type === 'maternal_health' ? 'selected' : '' ?>>Maternal & Prenatal Health Registry</option>
                            <option value="epi_coverage" <?= $type === 'epi_coverage' ? 'selected' : '' ?>>Childhood Routine Immunization (EPI)</option>
                            <option value="chronic_morbidity" <?= $type === 'chronic_morbidity' ? 'selected' : '' ?>>Morbidity & Chronic Disease Registry</option>
                            <option value="queue_summary" <?= $type === 'queue_summary' ? 'selected' : '' ?>>Daily Queue Operations</option>
                            <option value="vitals" <?= $type === 'vitals' ? 'selected' : '' ?>>Recorded Vital Signs Log</option>
                        </select>
                    </div>

                    <!-- Quick Date Presets -->
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-semibold d-flex justify-content-between">
                            <span>Quick Presets</span>
                        </label>
                        <div class="d-flex flex-wrap gap-1">
                            <button type="button" class="btn btn-outline-secondary btn-preset" onclick="setDatePreset('today')">Today</button>
                            <button type="button" class="btn btn-outline-secondary btn-preset" onclick="setDatePreset('this_week')">This Week</button>
                            <button type="button" class="btn btn-outline-secondary btn-preset" onclick="setDatePreset('this_month')">This Month</button>
                            <button type="button" class="btn btn-outline-secondary btn-preset" onclick="setDatePreset('last_month')">Last Month</button>
                            <button type="button" class="btn btn-outline-secondary btn-preset" onclick="setDatePreset('this_year')">This Year</button>
                        </div>
                    </div>

                    <!-- Date From -->
                    <div class="mb-3">
                        <label for="date_from" class="form-label text-secondary small fw-semibold">Date From <span class="text-danger">*</span></label>
                        <input type="date" name="date_from" id="date_from" class="form-control bg-light text-secondary" value="<?= h($dateFrom) ?>" <?= $allTime ? 'disabled' : 'required' ?>>
                    </div>

                    <!-- Date To -->
                    <div class="mb-3">
                        <label for="date_to" class="form-label text-secondary small fw-semibold">Date To <span class="text-danger">*</span></label>
                        <input type="date" name="date_to" id="date_to" class="form-control bg-light text-secondary" value="<?= h($dateTo) ?>" <?= $allTime ? 'disabled' : 'required' ?>>
                    </div>

                    <!-- All-Time Master Registry Checkbox -->
                    <div class="form-check form-switch pt-1">
                        <input class="form-check-input" type="checkbox" name="all_time" value="1" id="all_time" <?= $allTime ? 'checked' : '' ?> onchange="toggleAllTime(this.checked)">
                        <label class="form-check-label small text-muted fw-semibold" for="all_time">
                            Cumulative Master Registry <span class="d-block text-secondary" style="font-size: 0.7rem; font-weight: normal;">Include all historical records</span>
                        </label>
                    </div>
                </div>

                <div class="card-footer bg-light py-3 border-0 d-grid" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-play-fill me-1"></i> Generate Report
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Right Column: Results Panel -->
    <div class="col-12 col-lg-9">
        <?php if (empty($type)): ?>
            <!-- Empty Welcome state -->
            <div class="card card-premium text-center py-5 no-print">
                <div class="card-body">
                    <i class="bi bi-file-earmark-bar-graph d-block fs-1 text-muted mb-3 opacity-75"></i>
                    <h4 class="fw-bold text-dark">Run a Health Center Report</h4>
                    <p class="text-muted small mx-auto" style="max-width: 440px;">
                        Select a report category and date range on the left sidebar to generate verified clinical registers, operational summaries, or DOH program indicators.
                    </p>
                </div>
            </div>
        <?php else: ?>
            <!-- Actions bar (Hidden in print) -->
            <div class="d-flex justify-content-between align-items-center mb-3 no-print">
                <div class="small text-muted">
                    Showing <strong><?= number_format(count($results)) ?></strong> record(s) 
                    &bull; <?= $allTime ? '<span class="badge bg-secondary-soft text-secondary">All Time (Master Registry)</span>' : 'Period: <strong>' . date('M d, Y', strtotime($dateFrom)) . '</strong> to <strong>' . date('M d, Y', strtotime($dateTo)) . '</strong>' ?>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" onclick="window.print()" class="btn btn-outline-primary d-flex align-items-center">
                        <i class="bi bi-printer me-2 fs-5"></i> Print Report
                    </button>
                    <a href="<?= url('/reports/export?type=' . urlencode($type) . '&date_from=' . urlencode($dateFrom) . '&date_to=' . urlencode($dateTo) . ($allTime ? '&all_time=1' : '')) ?>" class="btn btn-primary d-flex align-items-center">
                        <i class="bi bi-file-earmark-spreadsheet me-2 fs-5"></i> Export CSV
                    </a>
                </div>
            </div>

            <!-- KPI Metric Summary Cards (Hidden in print) -->
            <?php if (!empty($metrics)): ?>
                <div class="row g-3 mb-4 no-print">
                    <?php foreach ($metrics as $m): ?>
                        <div class="col-6 col-md-3">
                            <div class="card card-premium h-100 p-3 border">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="text-secondary small fw-semibold"><?= h($m['label']) ?></span>
                                    <div class="rounded-circle bg-<?= h($m['color']) ?>-soft p-2 text-<?= h($m['color']) ?>">
                                        <i class="bi <?= h($m['icon']) ?> fs-5"></i>
                                    </div>
                                </div>
                                <div class="h4 fw-bold mb-0 text-dark"><?= h($m['value']) ?></div>
                                <?php if (!empty($m['sub'])): ?>
                                    <small class="text-muted mt-1" style="font-size: 0.72rem;"><?= h($m['sub']) ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Report Container (Includes printable headers) -->
            <div class="card card-premium print-report-card">
                <!-- PRINT HEADER ONLY -->
                <div class="print-header text-center">
                    <h2 class="h4 fw-bold mb-1 text-uppercase text-dark">Barangay Sinalhan Health Center</h2>
                    <p class="text-secondary small mb-3">Sinalhan Road, Brgy. Sinalhan, Santa Rosa City, Laguna, Philippines</p>
                    <hr class="mb-3">
                    <h3 class="h5 fw-bold text-dark text-uppercase"><?= h($reportName) ?></h3>
                    <p class="text-muted small">
                        Period: <strong><?= $allTime ? 'All Time (Cumulative Master Registry)' : date('M d, Y', strtotime($dateFrom)) . ' to ' . date('M d, Y', strtotime($dateTo)) ?></strong> 
                        &bull; Generated By: <?= h($_SESSION['user_fullname'] ?? $_SESSION['username'] ?? 'Staff') ?> on <?= date('Y-m-d h:i A') ?>
                    </p>
                </div>

                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between no-print">
                    <h3 class="card-title h6 mb-0 fw-bold text-dark">
                        <i class="bi bi-card-text text-primary me-2"></i><?= h($reportName) ?>
                    </h3>
                    <span class="badge bg-primary-soft text-primary"><?= number_format(count($results)) ?> Record(s)</span>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive p-3">
                        <!-- Table Render depends on Category -->
                        
                        <?php if ($type === 'daily_visits'): ?>
                            <!-- Daily Visits Table -->
                            <table class="table table-hover align-middle mb-0 text-center small" id="reportTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Queue No.</th>
                                        <th>Patient ID</th>
                                        <th class="text-start">Patient Name</th>
                                        <th>Service Type</th>
                                        <th>Check In</th>
                                        <th>Time Called</th>
                                        <th>Completed</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($results)): ?>
                                        <tr><td colspan="9" class="text-muted py-4">No visits logged in this period.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($results as $row): ?>
                                            <tr>
                                                <td><?= h($row['queue_date']) ?></td>
                                                <td class="fw-bold">#<?= sprintf('%03d', $row['queue_no']) ?></td>
                                                <td><?= h($row['patient_no']) ?></td>
                                                <td class="text-start fw-bold"><?= h($row['patient_last']) ?>, <?= h($row['patient_first']) ?></td>
                                                <td><span class="badge bg-light text-primary border"><?= h($row['service_type'] ?: 'General OPD') ?></span></td>
                                                <td><?= date('h:i A', strtotime($row['time_in'])) ?></td>
                                                <td><?= $row['time_called'] ? date('h:i A', strtotime($row['time_called'])) : '-' ?></td>
                                                <td><?= $row['time_completed'] ? date('h:i A', strtotime($row['time_completed'])) : '-' ?></td>
                                                <td>
                                                    <?php 
                                                        $statusClass = 'bg-secondary';
                                                        if ($row['status'] === 'Completed') $statusClass = 'bg-success';
                                                        elseif ($row['status'] === 'Cancelled') $statusClass = 'bg-danger';
                                                        elseif (in_array($row['status'], ['Called', 'Serving'])) $statusClass = 'bg-primary';
                                                        elseif ($row['status'] === 'Waiting') $statusClass = 'bg-warning text-dark';
                                                    ?>
                                                    <span class="badge <?= $statusClass ?>"><?= h($row['status']) ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>

                        <?php elseif ($type === 'consultations'): ?>
                            <!-- Consultation Summary Table -->
                            <table class="table table-hover align-middle mb-0 text-center small" id="reportTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Patient ID</th>
                                        <th class="text-start">Patient Name</th>
                                        <th class="text-start">Assessment (Diagnosis)</th>
                                        <th class="text-start">Subjective Notes</th>
                                        <th>Clinician</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($results)): ?>
                                        <tr><td colspan="6" class="text-muted py-4">No consultations logged in this period.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($results as $row): ?>
                                            <tr>
                                                <td><?= date('Y-m-d', strtotime($row['consulted_at'])) ?></td>
                                                <td><?= h($row['patient_no']) ?></td>
                                                <td class="text-start fw-bold">
                                                    <a href="<?= url('/patients/' . $row['patient_id']) ?>" class="link-primary-dark">
                                                        <?= h($row['patient_last']) ?>, <?= h($row['patient_first']) ?>
                                                    </a>
                                                </td>
                                                <td class="text-start fw-semibold"><?= h($row['assessment']) ?></td>
                                                <td class="text-start text-secondary text-truncate" style="max-width: 180px;"><?= h($row['subjective']) ?></td>
                                                <td><?= h($row['clinician_name']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>

                        <?php elseif ($type === 'registrations'): ?>
                            <!-- Registrations Summary Table -->
                            <table class="table table-hover align-middle mb-0 text-center small" id="reportTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Reg Date</th>
                                        <th>Patient ID</th>
                                        <th class="text-start">Last Name</th>
                                        <th class="text-start">First Name</th>
                                        <th>Birth Date</th>
                                        <th>Age/Sex</th>
                                        <th>Barangay</th>
                                        <th>Contact No.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($results)): ?>
                                        <tr><td colspan="8" class="text-muted py-4">No registrations in this period.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($results as $row): ?>
                                            <tr>
                                                <td><?= date('Y-m-d', strtotime($row['created_at'])) ?></td>
                                                <td class="fw-bold">
                                                    <a href="<?= url('/patients/' . $row['id']) ?>" class="link-primary-dark">
                                                        <?= h($row['patient_no']) ?>
                                                    </a>
                                                </td>
                                                <td class="text-start fw-bold"><?= h($row['last_name']) ?></td>
                                                <td class="text-start"><?= h($row['first_name']) ?></td>
                                                <td><?= h($row['dob']) ?></td>
                                                <td><?= h($row['age']) ?> yrs / <?= h($row['sex']) ?></td>
                                                <td><?= h($row['barangay']) ?></td>
                                                <td><?= h($row['contact_no'] ?? '-') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>

                        <?php elseif ($type === 'queue_summary'): ?>
                            <!-- Queue Operations Stats Table -->
                            <table class="table table-hover align-middle mb-0 text-center small" id="reportTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Total Enqueued</th>
                                        <th>Completed Visits</th>
                                        <th>Cancelled Tickets</th>
                                        <th>Waiting Tickets</th>
                                        <th>Serving/Called</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($results)): ?>
                                        <tr><td colspan="6" class="text-muted py-4">No queue activity logs in this period.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($results as $row): ?>
                                            <tr>
                                                <td class="fw-bold"><?= h($row['date']) ?></td>
                                                <td><strong><?= number_format($row['total']) ?></strong></td>
                                                <td><span class="badge bg-success"><?= number_format($row['completed']) ?></span></td>
                                                <td><span class="badge bg-danger"><?= number_format($row['cancelled']) ?></span></td>
                                                <td><span class="badge bg-warning text-dark"><?= number_format($row['waiting']) ?></span></td>
                                                <td><span class="badge bg-primary"><?= number_format($row['called_serving']) ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>

                        <?php elseif ($type === 'vitals'): ?>
                            <!-- Recorded Vitals Log Table -->
                            <table class="table table-hover align-middle mb-0 text-center small" id="reportTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Patient ID</th>
                                        <th class="text-start">Patient Name</th>
                                        <th>BP (mmHg)</th>
                                        <th>Pulse (bpm)</th>
                                        <th>Temp (°C)</th>
                                        <th>Resp (cpm)</th>
                                        <th>SpO2 (%)</th>
                                        <th>BMI</th>
                                        <th>Recorded By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($results)): ?>
                                        <tr><td colspan="10" class="text-muted py-4">No vital signs logged in this period.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($results as $row): 
                                            $isHighBp = ($row['bp_systolic'] >= 140 || $row['bp_diastolic'] >= 90);
                                        ?>
                                            <tr>
                                                <td><?= date('Y-m-d', strtotime($row['recorded_at'])) ?></td>
                                                <td class="fw-bold">
                                                    <a href="<?= url('/patients/' . $row['patient_id']) ?>" class="link-primary-dark">
                                                        <?= h($row['patient_no']) ?>
                                                    </a>
                                                </td>
                                                <td class="text-start fw-bold"><?= h($row['patient_last']) ?>, <?= h($row['patient_first']) ?></td>
                                                <td>
                                                    <?php if ($row['bp_systolic'] && $row['bp_diastolic']): ?>
                                                        <span class="badge <?= $isHighBp ? 'bg-danger text-white' : 'bg-light text-dark border' ?>">
                                                            <?= "{$row['bp_systolic']}/{$row['bp_diastolic']}" ?>
                                                        </span>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= h($row['heart_rate'] ?? '-') ?></td>
                                                <td>
                                                    <?php if ($row['temperature']): ?>
                                                        <span class="<?= (float)$row['temperature'] >= 37.8 ? 'text-danger fw-bold' : '' ?>">
                                                            <?= number_format((float)$row['temperature'], 1) ?>°C
                                                        </span>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= h($row['respiratory_rate'] ?? '-') ?></td>
                                                <td><?= $row['oxygen_saturation'] ? "{$row['oxygen_saturation']}%" : '-' ?></td>
                                                <td><span class="badge bg-light text-dark border"><?= h($row['bmi'] ?? '-') ?></span></td>
                                                <td><?= h($row['recorded_by_name']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>

                        <?php elseif ($type === 'maternal_health'): ?>
                            <!-- Maternal Health Registry Table -->
                            <table class="table table-hover align-middle mb-0 text-center small" id="reportTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Patient ID</th>
                                        <th class="text-start">Mother Name</th>
                                        <th>Age</th>
                                        <th>Barangay</th>
                                        <th>Obstetric (GTPAL)</th>
                                        <th>LMP</th>
                                        <th>Expected Delivery (EDC)</th>
                                        <th>Gestational Age</th>
                                        <th>Pre-Eclampsia Risk</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($results)): ?>
                                        <tr><td colspan="10" class="text-muted py-4">No maternal records found matching criteria.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($results as $row): ?>
                                            <tr>
                                                <td class="fw-bold"><?= h($row['patient_no']) ?></td>
                                                <td class="text-start fw-bold">
                                                    <a href="<?= url('/patients/' . $row['patient_id']) ?>" class="link-primary-dark">
                                                        <?= h($row['last_name']) ?>, <?= h($row['first_name']) ?>
                                                    </a>
                                                </td>
                                                <td><?= h($row['patient_age']) ?> yrs</td>
                                                <td><?= h($row['barangay']) ?></td>
                                                <td>
                                                    <span class="badge bg-light text-dark border">G<?= h($row['gravida']) ?>P<?= h($row['para']) ?></span>
                                                    <small class="text-muted d-block font-monospace" style="font-size: 0.68rem;">T<?= h($row['term_births']) ?> P<?= h($row['preterm_births']) ?> A<?= h($row['abortions']) ?> L<?= h($row['living_children']) ?></small>
                                                </td>
                                                <td><?= date('M d, Y', strtotime($row['lmp'])) ?></td>
                                                <td class="fw-bold text-primary"><?= date('M d, Y', strtotime($row['edc'])) ?></td>
                                                <td>
                                                    <?php if (!empty($row['is_active'])): ?>
                                                        <strong><?= h($row['calculated_aog'] !== null ? $row['calculated_aog'] . ' wks' : '0 wks') ?></strong>
                                                    <?php else: ?>
                                                        <?= !empty($row['delivery_date']) ? '<span class="text-success small fw-semibold">Delivered (' . date('M d, Y', strtotime($row['delivery_date'])) . ')</span>' : '<span class="text-muted small">Concluded</span>' ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($row['pre_eclampsia'])): ?>
                                                        <span class="badge bg-danger text-white">⚡ High Risk</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success-soft text-success">Low Risk</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($row['is_active'])): ?>
                                                        <span class="badge bg-pink text-white">Active Pregnancy</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Concluded</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>

                        <?php elseif ($type === 'epi_coverage'): ?>
                            <!-- Childhood Routine Immunization (EPI) Coverage Table -->
                            <table class="table table-hover align-middle mb-0 text-center small" id="reportTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Child ID</th>
                                        <th class="text-start">Child Name</th>
                                        <th>DOB</th>
                                        <th>Age (Mos)</th>
                                        <th>BCG</th>
                                        <th>HepB</th>
                                        <th>Penta 1-2-3</th>
                                        <th>OPV 1-2-3</th>
                                        <th>IPV</th>
                                        <th>MCV 1-2</th>
                                        <th>FIC Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($results)): ?>
                                        <tr><td colspan="11" class="text-muted py-4">No child immunization records found.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($results as $row): 
                                            $isFIC = !empty($row['is_fic']);
                                            $isCIC = !empty($row['is_cic']);
                                        ?>
                                            <tr>
                                                <td class="fw-bold"><?= h($row['patient_no']) ?></td>
                                                <td class="text-start fw-bold">
                                                    <a href="<?= url('/patients/' . $row['patient_id']) ?>" class="link-primary-dark">
                                                        <?= h($row['last_name']) ?>, <?= h($row['first_name']) ?>
                                                    </a>
                                                    <?php if ($row['mother_name']): ?>
                                                        <div class="text-muted font-monospace" style="font-size: 0.72rem;">M: <?= h($row['mother_name']) ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= date('M d, Y', strtotime($row['dob'])) ?></td>
                                                <td><strong><?= h($row['age_months']) ?>m</strong></td>
                                                <td><?= $row['bcg_date'] ? '<i class="bi bi-check-circle-fill text-success" title="BCG: ' . $row['bcg_date'] . '"></i>' : '<span class="text-muted">-</span>' ?></td>
                                                <td><?= $row['hepb_date'] ? '<i class="bi bi-check-circle-fill text-success" title="HepB: ' . $row['hepb_date'] . '"></i>' : '<span class="text-muted">-</span>' ?></td>
                                                <td>
                                                    <span class="badge <?= $row['penta1_date'] ? 'bg-success' : 'bg-light text-muted border' ?>" title="Penta 1">1</span>
                                                    <span class="badge <?= $row['penta2_date'] ? 'bg-success' : 'bg-light text-muted border' ?>" title="Penta 2">2</span>
                                                    <span class="badge <?= $row['penta3_date'] ? 'bg-success' : 'bg-light text-muted border' ?>" title="Penta 3">3</span>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $row['opv1_date'] ? 'bg-success' : 'bg-light text-muted border' ?>" title="OPV 1">1</span>
                                                    <span class="badge <?= $row['opv2_date'] ? 'bg-success' : 'bg-light text-muted border' ?>" title="OPV 2">2</span>
                                                    <span class="badge <?= $row['opv3_date'] ? 'bg-success' : 'bg-light text-muted border' ?>" title="OPV 3">3</span>
                                                </td>
                                                <td><?= $row['ipv_date'] ? '<i class="bi bi-check-circle-fill text-success" title="IPV: ' . $row['ipv_date'] . '"></i>' : '<span class="text-muted">-</span>' ?></td>
                                                <td>
                                                    <span class="badge <?= $row['mcv1_date'] ? 'bg-success' : 'bg-light text-muted border' ?>" title="MCV 1">1</span>
                                                    <span class="badge <?= $row['mcv2_date'] ? 'bg-success' : 'bg-light text-muted border' ?>" title="MCV 2">2</span>
                                                </td>
                                                <td>
                                                    <?php if ($isFIC): ?>
                                                        <span class="badge bg-success text-white" title="Fully Immunized Child (<= 12 mos)"><i class="bi bi-shield-check me-1"></i>FIC</span>
                                                    <?php elseif ($isCIC): ?>
                                                        <span class="badge bg-info text-white" title="Completely Immunized Child (> 12 mos)"><i class="bi bi-patch-check-fill me-1"></i>CIC</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark">Incomplete</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>

                        <?php elseif ($type === 'chronic_morbidity'): ?>
                            <!-- Morbidity / Chronic Disease Registry (IHP) Table -->
                            <table class="table table-hover align-middle mb-0 text-center small" id="reportTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Patient ID</th>
                                        <th class="text-start">Patient Name</th>
                                        <th>Age/Sex</th>
                                        <th>Barangay</th>
                                        <th>Diagnosed Conditions</th>
                                        <th class="text-start">Allergies</th>
                                        <th>Lifestyle History</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($results)): ?>
                                        <tr><td colspan="7" class="text-muted py-4">No chronic morbidity records found.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($results as $row): 
                                            $conds = $row['conditions_map'] ?? [];
                                        ?>
                                            <tr>
                                                <td class="fw-bold"><?= h($row['patient_no']) ?></td>
                                                <td class="text-start fw-bold">
                                                    <a href="<?= url('/patients/' . $row['patient_id']) ?>" class="link-primary-dark">
                                                        <?= h($row['last_name']) ?>, <?= h($row['first_name']) ?>
                                                    </a>
                                                </td>
                                                <td><?= h($row['patient_age']) ?> yrs / <?= h($row['sex']) ?></td>
                                                <td><?= h($row['barangay']) ?></td>
                                                <td>
                                                    <div class="d-flex flex-wrap gap-1 justify-content-center">
                                                        <?php foreach ($conds as $cName => $cRemarks): 
                                                            $upper = strtoupper($cName);
                                                            $badgeClass = 'bg-primary-soft text-primary';
                                                            if (stripos($upper, 'HYPERTEN') !== false) $badgeClass = 'bg-danger text-white';
                                                            elseif (stripos($upper, 'DIABET') !== false) $badgeClass = 'bg-warning text-dark';
                                                            elseif (stripos($upper, 'ASTHMA') !== false) $badgeClass = 'bg-info text-dark';
                                                            elseif (stripos($upper, 'CARDIO') !== false || stripos($upper, 'HEART') !== false) $badgeClass = 'bg-danger text-white';
                                                            elseif (stripos($upper, 'KIDNEY') !== false) $badgeClass = 'bg-dark text-white';
                                                            elseif (stripos($upper, 'TUBERC') !== false || stripos($upper, 'PTB') !== false) $badgeClass = 'bg-secondary text-white';
                                                        ?>
                                                            <span class="badge <?= $badgeClass ?>" title="<?= h($cRemarks) ?>">
                                                                <?= h($cName) ?><?= !empty($cRemarks) ? '*' : '' ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </td>
                                                <td class="text-start">
                                                    <?php if (!empty($row['allergies_text'])): ?>
                                                        <span class="text-danger fw-bold"><i class="bi bi-exclamation-triangle-fill me-1"></i><?= h($row['allergies_text']) ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">None Reported</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="small text-muted d-block">Smoke: <strong><?= h($row['smoking_status'] ?? 'Never') ?></strong></span>
                                                    <span class="small text-muted d-block">Alcohol: <strong><?= h($row['alcohol_status'] ?? 'Never') ?></strong></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>

                    </div>
                </div>

                <!-- PRINT FOOTER SIGNATORY ONLY -->
                <div class="print-signatory mt-5 pt-4">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-top border-dark pt-2 mx-auto" style="max-width: 250px;">
                                <strong class="text-uppercase"><?= h($_SESSION['user_fullname'] ?? $_SESSION['username'] ?? 'Staff Personnel') ?></strong>
                                <div class="small text-muted">Prepared By / Health Staff</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border-top border-dark pt-2 mx-auto" style="max-width: 250px;">
                                <strong class="text-uppercase">Medical Officer / Midwife In-Charge</strong>
                                <div class="small text-muted">Noted & Verified By</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Initialize DataTable on report table if records exist
    <?php if (!empty($results)): ?>
    if ($('#reportTable').length) {
        const reportTable = $('#reportTable').DataTable({
            "paging": true,
            "pageLength": 25,
            "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Filter table rows...",
                "paginate": {
                    "previous": "<i class='bi bi-chevron-left'></i>",
                    "next": "<i class='bi bi-chevron-right'></i>"
                }
            }
        });

        // 2. Synchronize before/after print so all records render in print preview
        window.addEventListener('beforeprint', function() {
            if ($.fn.DataTable.isDataTable('#reportTable')) {
                reportTable.page.len(-1).draw();
            }
        });

        window.addEventListener('afterprint', function() {
            if ($.fn.DataTable.isDataTable('#reportTable')) {
                reportTable.page.len(25).draw();
            }
        });
    }
    <?php endif; ?>
});

// Quick Date Presets Helper
function setDatePreset(preset) {
    const today = new Date();
    const format = (d) => {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    };

    const fromInput = document.getElementById('date_from');
    const toInput = document.getElementById('date_to');
    const allTimeCheck = document.getElementById('all_time');
    if (allTimeCheck) {
        allTimeCheck.checked = false;
        toggleAllTime(false);
    }

    if (preset === 'today') {
        fromInput.value = format(today);
        toInput.value = format(today);
    } else if (preset === 'this_week') {
        const currentDay = today.getDay(); // 0 is Sun, 1 is Mon
        const diffToMon = today.getDate() - currentDay + (currentDay === 0 ? -6 : 1);
        const monday = new Date(new Date().setDate(diffToMon));
        fromInput.value = format(monday);
        toInput.value = format(today);
    } else if (preset === 'this_month') {
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        fromInput.value = format(firstDay);
        toInput.value = format(today);
    } else if (preset === 'last_month') {
        const firstDayLastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        const lastDayLastMonth = new Date(today.getFullYear(), today.getMonth(), 0);
        fromInput.value = format(firstDayLastMonth);
        toInput.value = format(lastDayLastMonth);
    } else if (preset === 'this_year') {
        const firstDayYear = new Date(today.getFullYear(), 0, 1);
        fromInput.value = format(firstDayYear);
        toInput.value = format(today);
    }
}

// Toggle date input fields when "Cumulative Master Registry" is checked
function toggleAllTime(isChecked) {
    const fromInput = document.getElementById('date_from');
    const toInput = document.getElementById('date_to');
    if (isChecked) {
        fromInput.setAttribute('disabled', 'disabled');
        toInput.setAttribute('disabled', 'disabled');
        fromInput.removeAttribute('required');
        toInput.removeAttribute('required');
    } else {
        fromInput.removeAttribute('disabled');
        toInput.removeAttribute('disabled');
        fromInput.setAttribute('required', 'required');
        toInput.setAttribute('required', 'required');
    }
}
</script>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

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
    footer {
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
        font-size: 10pt !important;
    }
    .print-header {
        display: block !important;
        border-bottom: 3px double #333;
        margin-bottom: 25px;
        padding-bottom: 10px;
    }
}
@media screen {
    .print-header {
        display: none !important;
    }
}
</style>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 no-print">
    <div>
        <h2 class="h3 mb-1 fw-bold text-primary-dark">Operational Reports</h2>
        <p class="text-secondary small mb-0">Generate, print, and export daily statistics, patient registers, and clinical operations log.</p>
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

                    <!-- Date From -->
                    <div class="mb-3">
                        <label for="date_from" class="form-label text-secondary small fw-semibold">Date From <span class="text-danger">*</span></label>
                        <input type="date" name="date_from" id="date_from" class="form-control bg-light text-secondary" value="<?= h($dateFrom) ?>" required>
                    </div>

                    <!-- Date To -->
                    <div class="mb-0">
                        <label for="date_to" class="form-label text-secondary small fw-semibold">Date To <span class="text-danger">*</span></label>
                        <input type="date" name="date_to" id="date_to" class="form-control bg-light text-secondary" value="<?= h($dateTo) ?>" required>
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
                    <h4 class="fw-bold text-dark">Run a Report</h4>
                    <p class="text-muted small mx-auto" style="max-width: 400px;">
                        Select a report category and date range on the left sidebar to generate data.
                    </p>
                </div>
            </div>
        <?php else: ?>
            <!-- Actions bar (Hidden in print) -->
            <div class="d-flex justify-content-end gap-2 mb-3 no-print">
                <button type="button" onclick="window.print()" class="btn btn-outline-primary d-flex align-items-center">
                    <i class="bi bi-printer me-2 fs-5"></i> Print Report
                </button>
                <a href="<?= url('/reports/export?type=' . urlencode($type) . '&date_from=' . urlencode($dateFrom) . '&date_to=' . urlencode($dateTo)) ?>" class="btn btn-primary d-flex align-items-center">
                    <i class="bi bi-file-earmark-spreadsheet me-2 fs-5"></i> Export CSV
                </a>
            </div>

            <!-- Report Container (Includes printable headers) -->
            <div class="card card-premium print-report-card">
                <!-- PRINT HEADER ONLY -->
                <div class="print-header text-center">
                    <h2 class="h4 fw-bold mb-1 text-uppercase text-dark">Barangay Sinalhan Health Center</h2>
                    <p class="text-secondary small mb-3">Sinalhan Road, Brgy. Sinalhan, Santa Rosa City, Laguna, Philippines</p>
                    <hr class="mb-3">
                    <h3 class="h5 fw-bold text-dark text-uppercase"><?= h($reportName) ?></h3>
                    <p class="text-muted small">Period: <strong><?= date('M d, Y', strtotime($dateFrom)) ?></strong> to <strong><?= date('M d, Y', strtotime($dateTo)) ?></strong> &bull; Generated By: <?= h($_SESSION['username']) ?> on <?= date('Y-m-d h:i A') ?></p>
                </div>

                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between no-print">
                    <h3 class="card-title h6 mb-0 fw-bold text-dark">
                        <i class="bi bi-card-text text-primary me-2"></i><?= h($reportName) ?>
                    </h3>
                    <span class="badge bg-primary-soft text-primary"><?= number_format(count($results)) ?> Record(s)</span>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <!-- Table Render depends on Category -->
                        
                        <?php if ($type === 'daily_visits'): ?>
                            <!-- Daily Visits Table -->
                            <table class="table table-hover align-middle mb-0 text-center small">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Queue No.</th>
                                        <th>Patient ID</th>
                                        <th class="text-start">Patient Name</th>
                                        <th>Check In</th>
                                        <th>Time Called</th>
                                        <th>Completed</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($results)): ?>
                                        <tr><td colspan="8" class="text-muted py-4">No visits logged in this period.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($results as $row): ?>
                                            <tr>
                                                <td><?= h($row['queue_date']) ?></td>
                                                <td class="fw-bold">#<?= sprintf('%03d', $row['queue_no']) ?></td>
                                                <td><?= h($row['patient_no']) ?></td>
                                                <td class="text-start fw-bold"><?= h($row['patient_last']) ?>, <?= h($row['patient_first']) ?></td>
                                                <td><?= date('h:i A', strtotime($row['time_in'])) ?></td>
                                                <td><?= $row['time_called'] ? date('h:i A', strtotime($row['time_called'])) : '-' ?></td>
                                                <td><?= $row['time_completed'] ? date('h:i A', strtotime($row['time_completed'])) : '-' ?></td>
                                                <td><span class="badge bg-secondary"><?= h($row['status']) ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>

                        <?php elseif ($type === 'consultations'): ?>
                            <!-- Consultation Summary Table -->
                            <table class="table table-hover align-middle mb-0 text-center small">
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
                                                <td class="text-start fw-bold"><?= h($row['patient_last']) ?>, <?= h($row['patient_first']) ?></td>
                                                <td class="text-start fw-semibold"><?= h($row['assessment']) ?></td>
                                                <td class="text-start text-secondary text-truncate" style="max-width: 150px;"><?= h($row['subjective']) ?></td>
                                                <td><?= h($row['clinician_name']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>

                        <?php elseif ($type === 'registrations'): ?>
                            <!-- Registrations Summary Table -->
                            <table class="table table-hover align-middle mb-0 text-center small">
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
                                                <td class="fw-bold"><?= h($row['patient_no']) ?></td>
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
                            <table class="table table-hover align-middle mb-0 text-center small">
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
                            <table class="table table-hover align-middle mb-0 text-center small">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
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
                                        <tr><td colspan="9" class="text-muted py-4">No vital signs logged in this period.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($results as $row): ?>
                                            <tr>
                                                <td><?= date('Y-m-d', strtotime($row['recorded_at'])) ?></td>
                                                <td class="text-start fw-bold"><?= h($row['patient_last']) ?>, <?= h($row['patient_first']) ?></td>
                                                <td><?= ($row['bp_systolic'] && $row['bp_diastolic']) ? "{$row['bp_systolic']}/{$row['bp_diastolic']}" : '-' ?></td>
                                                <td><?= h($row['heart_rate'] ?? '-') ?></td>
                                                <td><?= $row['temperature'] ? number_format((float)$row['temperature'], 1) : '-' ?></td>
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
                            <table class="table table-hover align-middle mb-0 text-center small">
                                <thead class="table-light">
                                    <tr>
                                        <th>Patient ID</th>
                                        <th class="text-start">Mother Name</th>
                                        <th>Age</th>
                                        <th>Barangay</th>
                                        <th>GTPAL</th>
                                        <th>LMP</th>
                                        <th>Expected Delivery (EDC)</th>
                                        <th>Current AOG</th>
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
                                                <td><span class="badge bg-light text-dark border">G<?= h($row['gravida']) ?>P<?= h($row['para']) ?></span></td>
                                                <td><?= date('M d, Y', strtotime($row['lmp'])) ?></td>
                                                <td class="fw-bold text-primary"><?= date('M d, Y', strtotime($row['edc'])) ?></td>
                                                <td><strong><?= h($row['calculated_aog'] ?: '0') ?> wks</strong></td>
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
                            <table class="table table-hover align-middle mb-0 text-center small">
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
                                            $isFIC = (!empty($row['bcg_date']) && !empty($row['hepb_date']) && !empty($row['penta1_date']) && !empty($row['penta2_date']) && !empty($row['penta3_date']) && !empty($row['opv1_date']) && !empty($row['opv2_date']) && !empty($row['opv3_date']) && !empty($row['mcv1_date']));
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
                                                <td><?= $row['bcg_date'] ? '<i class="bi bi-check-circle-fill text-success" title="' . $row['bcg_date'] . '"></i>' : '<span class="text-muted">-</span>' ?></td>
                                                <td><?= $row['hepb_date'] ? '<i class="bi bi-check-circle-fill text-success" title="' . $row['hepb_date'] . '"></i>' : '<span class="text-muted">-</span>' ?></td>
                                                <td>
                                                    <span class="badge <?= $row['penta1_date'] ? 'bg-success' : 'bg-light text-muted border' ?>">1</span>
                                                    <span class="badge <?= $row['penta2_date'] ? 'bg-success' : 'bg-light text-muted border' ?>">2</span>
                                                    <span class="badge <?= $row['penta3_date'] ? 'bg-success' : 'bg-light text-muted border' ?>">3</span>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $row['opv1_date'] ? 'bg-success' : 'bg-light text-muted border' ?>">1</span>
                                                    <span class="badge <?= $row['opv2_date'] ? 'bg-success' : 'bg-light text-muted border' ?>">2</span>
                                                    <span class="badge <?= $row['opv3_date'] ? 'bg-success' : 'bg-light text-muted border' ?>">3</span>
                                                </td>
                                                <td><?= $row['ipv_date'] ? '<i class="bi bi-check-circle-fill text-success" title="' . $row['ipv_date'] . '"></i>' : '<span class="text-muted">-</span>' ?></td>
                                                <td>
                                                    <span class="badge <?= $row['mcv1_date'] ? 'bg-success' : 'bg-light text-muted border' ?>">1</span>
                                                    <span class="badge <?= $row['mcv2_date'] ? 'bg-success' : 'bg-light text-muted border' ?>">2</span>
                                                </td>
                                                <td>
                                                    <?php if ($isFIC): ?>
                                                        <span class="badge bg-success text-white"><i class="bi bi-shield-check me-1"></i>FIC</span>
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
                            <table class="table table-hover align-middle mb-0 text-center small">
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
                                            $pmh = is_array($row['past_medical_history']) ? $row['past_medical_history'] : (json_decode($row['past_medical_history'] ?? '[]', true) ?: []);
                                            $allergies = $pmh['Allergy'] ?? $pmh['Allergies'] ?? '';
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
                                                    <?php if (isset($pmh['Hypertension']) || in_array('Hypertension', $pmh)): ?><span class="badge bg-danger mb-1">Hypertension</span><br><?php endif; ?>
                                                    <?php if (isset($pmh['Diabetes Mellitus']) || in_array('Diabetes Mellitus', $pmh)): ?><span class="badge bg-warning text-dark mb-1">Diabetes</span><br><?php endif; ?>
                                                    <?php if (isset($pmh['Asthma']) || isset($pmh['Bronchial Asthma']) || in_array('Asthma', $pmh)): ?><span class="badge bg-info text-dark mb-1">Asthma</span><br><?php endif; ?>
                                                    <?php if (isset($pmh['Cardiovascular Disease']) || isset($pmh['Heart Disease']) || isset($pmh['Coronary Artery Disease']) || in_array('Cardiovascular Disease', $pmh) || in_array('Coronary Artery Disease', $pmh)): ?><span class="badge bg-danger mb-1">Heart Disease</span><br><?php endif; ?>
                                                    <?php if (isset($pmh['Chronic Kidney Disease']) || isset($pmh['Kidney Disease']) || in_array('Kidney Disease', $pmh)): ?><span class="badge bg-dark text-white mb-1">Kidney Disease</span><br><?php endif; ?>
                                                    <?php if (isset($pmh['Pulmonary Tuberculosis (PTB)']) || isset($pmh['Pulmonary Tuberculosis']) || isset($pmh['PTB']) || isset($pmh['Tuberculosis']) || in_array('Pulmonary Tuberculosis (PTB)', $pmh) || in_array('PTB', $pmh)): ?><span class="badge bg-secondary mb-1">PTB</span><br><?php endif; ?>
                                                </td>
                                                <td class="text-start">
                                                    <?php if (!empty($allergies)): ?>
                                                        <span class="text-danger fw-bold"><i class="bi bi-exclamation-triangle-fill me-1"></i><?= h($allergies) ?></span>
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
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

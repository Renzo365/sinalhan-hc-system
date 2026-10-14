<?php
/**
 * @var array $activeRoster Active prenatal pregnancy records
 * @var array $recentlyDelivered Concluded delivery records within 90 days
 * @var string $search Active search query
 */

$title = 'Prenatal Care Workstation';
$breadcrumbs = [
    'Prenatal Care' => null
];
require dirname(__DIR__) . '/layout/header.php';

// Quick stats calculation
$totalActive = count($activeRoster);
$nearTermCount = 0;
$overdueCount = 0;

foreach ($activeRoster as $row) {
    $weeks = (int)($row['calculated_aog']['weeks'] ?? 0);
    $daysLeft = (int)($row['days_until_edc'] ?? 0);
    if ($weeks >= 37 && $daysLeft >= 0) {
        $nearTermCount++;
    }
    if ($daysLeft < 0) {
        $overdueCount++;
    }
}
$deliveredCount = count($recentlyDelivered);
?>

<!-- Header Title Banner -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1 fw-bold text-primary-dark" style="text-wrap: balance;">Prenatal Care Workstation</h1>
        <p class="text-secondary small mb-0" style="text-wrap: pretty;">Active pregnancy cohorts, prenatal checkup tracking, and delivery outcomes.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= url('/prenatal/register') ?>" class="btn btn-primary d-flex align-items-center py-2 px-3 shadow-sm">
            <i class="bi bi-plus-lg me-2" aria-hidden="true"></i>
            <span>Register Pregnancy</span>
        </a>
    </div>
</div>

    <!-- Prenatal Cohort Operational KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <!-- 1. Active Pregnancies -->
        <div class="col-6 col-lg-3">
            <div class="card card-premium shadow-sm h-100 border">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Active Pregnancies</div>
                            <div class="h3 mb-0 fw-bold text-primary mt-1" style="font-variant-numeric: tabular-nums;"><?= $totalActive ?></div>
                        </div>
                        <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-person-hearts fs-5" aria-hidden="true"></i>
                        </div>
                    </div>
                    <div class="small text-muted mt-2" style="font-size: 0.75rem;">
                        <span>Active antenatal cohort</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Near Term -->
        <div class="col-6 col-lg-3">
            <div class="card card-premium shadow-sm h-100 border">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Near Term (≥37w)</div>
                            <div class="h3 mb-0 fw-bold text-warning-emphasis mt-1" style="font-variant-numeric: tabular-nums;"><?= $nearTermCount ?></div>
                        </div>
                        <div class="bg-warning-subtle text-warning-emphasis rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-bell fs-5" aria-hidden="true"></i>
                        </div>
                    </div>
                    <div class="small text-muted mt-2" style="font-size: 0.75rem;">
                        <span>Due within 3 weeks</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. High-Risk / Overdue -->
        <div class="col-6 col-lg-3">
            <div class="card card-premium shadow-sm h-100 border">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">High-Risk / Overdue</div>
                            <div class="h3 mb-0 fw-bold <?= $overdueCount > 0 ? 'text-danger' : 'text-secondary' ?> mt-1" style="font-variant-numeric: tabular-nums;"><?= $overdueCount ?></div>
                        </div>
                        <div class="<?= $overdueCount > 0 ? 'bg-danger-subtle text-danger' : 'bg-light text-secondary' ?> rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-exclamation-triangle fs-5" aria-hidden="true"></i>
                        </div>
                    </div>
                    <div class="small text-muted mt-2" style="font-size: 0.75rem;">
                        <span><?= $overdueCount > 0 ? 'Past estimated EDC date' : 'No overdue records' ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Recent Deliveries (90d) -->
        <div class="col-6 col-lg-3">
            <div class="card card-premium shadow-sm h-100 border">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Recent Deliveries</div>
                            <div class="h3 mb-0 fw-bold text-success mt-1" style="font-variant-numeric: tabular-nums;"><?= $deliveredCount ?></div>
                        </div>
                        <div class="bg-success-subtle text-success rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-check-circle fs-5" aria-hidden="true"></i>
                        </div>
                    </div>
                    <div class="small text-muted mt-2" style="font-size: 0.75rem;">
                        <span>Last 90 days concluded</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card card-premium mb-4">
        <div class="card-body p-4">
            <form action="<?= url('/prenatal') ?>" method="GET" class="row g-3 align-items-end" id="filtersForm">
                <!-- Search Keyword -->
                <div class="col-12 col-md-6">
                    <label for="search" class="form-label fw-semibold text-secondary small">Search Mother / Patient</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search" aria-hidden="true"></i></span>
                        <input type="text" 
                               name="search" 
                               id="search" 
                               class="form-control bg-light border-start-0" 
                               placeholder="Search mother name, ID, envelope…" 
                               autocomplete="off"
                               spellcheck="false"
                               value="<?= h($search) ?>">
                    </div>
                </div>

                <!-- Gestation Stage Filter -->
                <div class="col-12 col-sm-6 col-md-4">
                    <label for="stage" class="form-label fw-semibold text-secondary small">Gestation Stage</label>
                    <select name="stage" id="stage" class="form-select bg-light">
                        <option value="">-- All Gestation Stages --</option>
                        <option value="near_term" <?= ($stage ?? '') === 'near_term' ? 'selected' : '' ?>>Near Term (≥ 37 Weeks)</option>
                        <option value="1st" <?= ($stage ?? '') === '1st' ? 'selected' : '' ?>>1st Trimester (&lt; 14 Weeks)</option>
                        <option value="2nd" <?= ($stage ?? '') === '2nd' ? 'selected' : '' ?>>2nd Trimester (14–27 Weeks)</option>
                        <option value="3rd" <?= ($stage ?? '') === '3rd' ? 'selected' : '' ?>>3rd Trimester (≥ 28 Weeks)</option>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        Filter
                    </button>
                    <a href="<?= url('/prenatal') ?>" class="btn btn-outline-secondary" title="Clear Filters" aria-label="Clear Filters">
                        <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Active Pregnancies Roster Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h5 fw-bold mb-0 text-dark" style="text-wrap: balance;">Active Pregnant Mothers Cohort</h2>
                <small class="text-muted" style="text-wrap: pretty;">Mothers currently enrolled in active pregnancy episodes</small>
            </div>
            <span class="badge bg-light text-secondary border px-2.5 py-1.5 font-monospace" style="font-variant-numeric: tabular-nums;">
                Active Records: <strong class="text-dark"><?= count($activeRoster) ?></strong>
            </span>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small" id="maternalRosterTable">
                    <thead class="bg-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4 py-3">Mother / Patient</th>
                            <th class="py-3">Obstetric Score</th>
                            <th class="py-3">LMP Date</th>
                            <th class="py-3">Estimated Due Date (EDC)</th>
                            <th class="py-3" style="min-width: 150px;">Gestation (AOG)</th>
                            <th class="py-3 text-center">Visits</th>
                            <th class="pe-4 py-3 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($activeRoster)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-person-x fs-1 d-block mb-2 text-secondary opacity-50" aria-hidden="true"></i>
                                    <?php if (!empty($search) || !empty($stage)): ?>
                                        No active pregnant patients match your search/filter criteria.
                                        <div class="mt-2"><a href="<?= url('/prenatal') ?>" class="btn btn-sm btn-outline-primary">Clear Filters</a></div>
                                    <?php else: ?>
                                        No active pregnancy episodes currently registered.
                                        <div class="small text-muted mt-1">To enroll a pregnant mother, click <strong>Register Pregnancy</strong> above.</div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($activeRoster as $row): 
                                $aogWeeks = (int)($row['calculated_aog']['weeks'] ?? 0);
                                $aogDays = (int)($row['calculated_aog']['days'] ?? 0);
                                $progressPct = min(100, round(($aogWeeks / 40) * 100));

                                $progressClass = 'bg-primary';
                                if ($aogWeeks >= 37 && $aogWeeks < 42) {
                                    $progressClass = 'bg-success';
                                } elseif ($aogWeeks >= 42) {
                                    $progressClass = 'bg-danger';
                                }

                                $daysLeft = (int)($row['days_until_edc'] ?? 0);
                            ?>
                                <tr>
                                    <td class="ps-4 py-3">
                                        <div>
                                            <a href="<?= url('/prenatal/' . $row['patient_id']) ?>" class="fw-bold text-dark text-decoration-none hover-primary">
                                                <?= h($row['last_name']) ?>, <?= h($row['first_name']) ?> <?= !empty($row['middle_name']) ? h(mb_substr($row['middle_name'], 0, 1)) . '.' : '' ?>
                                            </a>
                                            <div class="small text-muted d-flex align-items-center gap-1 mt-1">
                                                <span class="badge badge-patient-id font-monospace" style="font-size: 0.72rem; font-variant-numeric: tabular-nums;">
                                                    <span class="visually-hidden">Patient ID: </span><?= h($row['patient_no']) ?>
                                                </span>
                                                <?php if (!empty($row['envelope_no'])): ?>
                                                    <span class="badge badge-envelope-no font-monospace" style="font-size: 0.72rem; font-variant-numeric: tabular-nums;">
                                                        <span class="visually-hidden">Envelope No: </span>Env #<?= h($row['envelope_no']) ?>
                                                    </span>
                                                <?php endif; ?>
                                                <span class="ms-1"><?= h($row['patient_age']) ?> yrs</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-pink text-white px-2 py-1 fw-bold" style="font-variant-numeric: tabular-nums;">G<?= h($row['gravida']) ?> P<?= h($row['para']) ?></span>
                                        <div class="text-muted font-monospace mt-1" style="font-size: 0.75rem; font-variant-numeric: tabular-nums;">
                                            T:<?= (int)$row['term_births'] ?> P:<?= (int)$row['preterm_births'] ?> A:<?= (int)$row['abortions'] ?> L:<?= (int)$row['living_children'] ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-medium text-dark"><?= date('M d, Y', strtotime($row['lmp'])) ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= date('M d, Y', strtotime($row['edc'])) ?></div>
                                        <?php if ($daysLeft > 0): ?>
                                            <span class="badge bg-info-subtle text-info border small mt-1" style="font-variant-numeric: tabular-nums;">In <?= $daysLeft ?> days</span>
                                        <?php elseif ($daysLeft === 0): ?>
                                            <span class="badge bg-warning text-dark small mt-1">Due Today!</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger border small mt-1" style="font-variant-numeric: tabular-nums;"><?= abs($daysLeft) ?> days overdue</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-between align-items-center small mb-1">
                                            <span class="fw-bold text-dark" style="font-variant-numeric: tabular-nums;"><?= h($row['calculated_aog']['formatted'] ?? '0 weeks') ?></span>
                                            <span class="text-muted" style="font-variant-numeric: tabular-nums;"><?= $progressPct ?>%</span>
                                        </div>
                                        <div class="progress" style="height: 5px;">
                                            <div class="progress-bar <?= $progressClass ?>" role="progressbar" style="width: <?= $progressPct ?>%;" aria-valuenow="<?= $progressPct ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Gestational progress: <?= $progressPct ?>%"></div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill bg-light text-dark border px-3 py-1 fw-bold" style="font-variant-numeric: tabular-nums;">
                                            <?= (int)$row['visit_count'] ?>
                                        </span>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <a href="<?= url('/prenatal/' . $row['patient_id']) ?>" class="btn btn-sm btn-primary px-3 shadow-xs">
                                            Open Workstation
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recently Concluded / Delivered Deliveries Card -->
    <?php if (!empty($recentlyDelivered)): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 py-3 px-4">
                <h2 class="h5 fw-bold mb-0 text-dark" style="text-wrap: balance;">Recently Delivered / Concluded (Last 90 Days)</h2>
                <small class="text-muted" style="text-wrap: pretty;">Postpartum mothers and recently concluded pregnancy episodes</small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="bg-light text-secondary small text-uppercase">
                            <tr>
                                <th class="ps-4 py-3">Mother</th>
                                <th class="py-3">Obstetric Score</th>
                                <th class="py-3">Delivery Date</th>
                                <th class="py-3">Outcome</th>
                                <th class="py-3 text-center">Visits Logged</th>
                                <th class="pe-4 py-3 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentlyDelivered as $deliv): ?>
                                <tr>
                                    <td class="ps-4 py-3">
                                        <div class="fw-bold text-dark">
                                            <?= h($deliv['last_name']) ?>, <?= h($deliv['first_name']) ?> <?= !empty($deliv['middle_name']) ? h(mb_substr($deliv['middle_name'], 0, 1)) . '.' : '' ?>
                                        </div>
                                        <span class="badge badge-patient-id font-monospace" style="font-size: 0.72rem;"><?= h($deliv['patient_no']) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary text-white">G<?= h($deliv['gravida']) ?> P<?= h($deliv['para']) ?></span>
                                    </td>
                                    <td>
                                        <span class="fw-medium text-dark"><?= !empty($deliv['delivery_date']) ? date('M d, Y', strtotime($deliv['delivery_date'])) : 'Concluded' ?></span>
                                    </td>
                                    <td>
                                        <?php 
                                        $outcome = $deliv['delivery_outcome'] ?? 'Concluded';
                                        $outcomeClass = 'bg-success text-white';
                                        if ($outcome === 'Stillbirth') $outcomeClass = 'bg-dark text-white';
                                        elseif ($outcome === 'Miscarriage') $outcomeClass = 'bg-warning text-dark';
                                        elseif ($outcome === 'Ectopic') $outcomeClass = 'bg-danger text-white';
                                        elseif ($outcome === 'Transferred Out / Referred') $outcomeClass = 'bg-info text-white';
                                        elseif ($outcome === 'Unreturned / Discontinued') $outcomeClass = 'bg-secondary text-white';
                                        ?>
                                        <span class="badge <?= $outcomeClass ?> px-2 py-1"><?= h($outcome) ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-secondary border"><?= (int)$deliv['visit_count'] ?> visits</span>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <a href="<?= url('/prenatal/' . $deliv['patient_id']) ?>" class="btn btn-sm btn-outline-primary px-3 shadow-xs">
                                            View Records
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

<style>
.hover-primary:hover {
    color: var(--bs-primary) !important;
}
.bg-pink {
    background-color: #d63384 !important;
}
</style>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Initialize DataTable for Active Cohort Roster
    <?php if (!empty($activeRoster)): ?>
        $('#maternalRosterTable').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": false, // Handled by custom filter card
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "pageLength": <?= (int)user_pref('default_page_size', 10) ?>,
            "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            "order": [[3, "asc"]], // Order by EDC ascending
            "columnDefs": [
                { "orderable": false, "targets": 6 } // Disable sorting on Action column (index 6)
            ],
            "language": {
                "lengthMenu": "Show _MENU_ mothers",
                "paginate": {
                    "previous": "<i class='bi bi-chevron-left'></i>",
                    "next": "<i class='bi bi-chevron-right'></i>"
                }
            }
        });
    <?php endif; ?>
});
</script>
<?php
/**
 * @var array $activeRoster Active maternal pregnancy records
 * @var array $recentlyDelivered Concluded delivery records within 90 days
 * @var string $search Active search query
 */

$title = 'Maternal Care Workstation';
$breadcrumbs = [
    'Maternal Care' => null
];
require dirname(__DIR__) . '/layout/header.php';

// Quick stats calculation
$totalActive = count($activeRoster);
$nearTermCount = 0;
foreach ($activeRoster as $row) {
    $weeks = (int)($row['calculated_aog']['weeks'] ?? 0);
    if ($weeks >= 37) {
        $nearTermCount++;
    }
}
$deliveredCount = count($recentlyDelivered);
?>

<div class="container-fluid py-4">
    <!-- Header Title Banner -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-1 text-dark">
                <i class="bi bi-heart-pulse-fill text-danger me-2"></i>Maternal Care Workstation
            </h2>
            <p class="text-muted mb-0">Active pregnancy cohorts, prenatal checkup tracking, and delivery outcomes.</p>
        </div>
            <a href="<?= url('/maternal/register') ?>" class="btn btn-primary d-flex align-items-center py-2 px-3 shadow-sm">
                <i class="bi bi-person-plus-fill me-2 fs-5"></i>
                <span>Register Pregnancy</span>
            </a>
    </div>

    <!-- Metrics Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white border-start border-4 border-primary">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary-subtle text-primary p-3 me-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-person-heart fs-4"></i>
                    </div>
                    <div>
                        <div class="text-secondary small fw-medium text-uppercase tracking-wider">Active Pregnancies</div>
                        <div class="fs-3 fw-bold text-dark"><?= $totalActive ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white border-start border-4 border-warning">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-warning-subtle text-warning p-3 me-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-bell-fill fs-4"></i>
                    </div>
                    <div>
                        <div class="text-secondary small fw-medium text-uppercase tracking-wider">Near Term (≥37 Weeks)</div>
                        <div class="fs-3 fw-bold text-dark"><?= $nearTermCount ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white border-start border-4 border-success">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-success-subtle text-success p-3 me-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-check-circle-fill fs-4"></i>
                    </div>
                    <div>
                        <div class="text-secondary small fw-medium text-uppercase tracking-wider">Recent Deliveries (90d)</div>
                        <div class="fs-3 fw-bold text-dark"><?= $deliveredCount ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card card-premium mb-4">
        <div class="card-body p-4">
            <form action="<?= url('/maternal') ?>" method="GET" class="row g-3 align-items-end" id="filtersForm">
                <!-- Search Keyword -->
                <div class="col-12 col-md-6">
                    <label for="search" class="form-label fw-semibold text-secondary small">Search Mother / Patient</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" 
                               name="search" 
                               id="search" 
                               class="form-control bg-light border-start-0" 
                               placeholder="Search mother name, ID, envelope..." 
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
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    <a href="<?= url('/maternal') ?>" class="btn btn-outline-secondary" title="Clear Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Active Pregnancies Roster Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-list-stars text-primary me-2"></i>Active Pregnant Mothers Cohort
                </h5>
                <small class="text-muted">Mothers currently enrolled in active pregnancy episodes</small>
            </div>
            <span class="badge bg-light text-secondary border px-2.5 py-1.5 font-monospace">
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
                                    <i class="bi bi-person-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    <?php if (!empty($search) || !empty($stage)): ?>
                                        No active pregnant patients match your search/filter criteria.
                                        <div class="mt-2"><a href="<?= url('/maternal') ?>" class="btn btn-sm btn-outline-primary">Clear Filters</a></div>
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
                                            <a href="<?= url('/maternal/' . $row['patient_id']) ?>" class="fw-bold text-dark text-decoration-none hover-primary">
                                                <?= h($row['last_name']) ?>, <?= h($row['first_name']) ?> <?= !empty($row['middle_name']) ? h(mb_substr($row['middle_name'], 0, 1)) . '.' : '' ?>
                                            </a>
                                            <div class="small text-muted d-flex align-items-center gap-1 mt-1">
                                                <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.72rem;"><?= h($row['patient_no']) ?></span>
                                                <?php if (!empty($row['envelope_no'])): ?>
                                                    <span class="badge bg-warning-subtle text-dark border font-monospace" style="font-size: 0.72rem;">Env #<?= h($row['envelope_no']) ?></span>
                                                <?php endif; ?>
                                                <span class="ms-1"><?= h($row['patient_age']) ?> yrs</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-pink text-white px-2 py-1 fw-bold">G<?= h($row['gravida']) ?> P<?= h($row['para']) ?></span>
                                        <div class="text-muted font-monospace mt-1" style="font-size: 0.75rem;">
                                            T:<?= (int)$row['term_births'] ?> P:<?= (int)$row['preterm_births'] ?> A:<?= (int)$row['abortions'] ?> L:<?= (int)$row['living_children'] ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-medium text-dark"><?= date('M d, Y', strtotime($row['lmp'])) ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= date('M d, Y', strtotime($row['edc'])) ?></div>
                                        <?php if ($daysLeft > 0): ?>
                                            <span class="badge bg-info-subtle text-info border small mt-1">In <?= $daysLeft ?> days</span>
                                        <?php elseif ($daysLeft === 0): ?>
                                            <span class="badge bg-warning text-dark small mt-1">Due Today!</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger border small mt-1"><?= abs($daysLeft) ?> days overdue</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-between align-items-center small mb-1">
                                            <span class="fw-bold text-dark"><?= h($row['calculated_aog']['formatted'] ?? '0 weeks') ?></span>
                                            <span class="text-muted"><?= $progressPct ?>%</span>
                                        </div>
                                        <div class="progress" style="height: 5px;">
                                            <div class="progress-bar <?= $progressClass ?>" role="progressbar" style="width: <?= $progressPct ?>%;" aria-valuenow="<?= $progressPct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill bg-light text-dark border px-3 py-1 fw-bold">
                                            <?= (int)$row['visit_count'] ?>
                                        </span>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <a href="<?= url('/maternal/' . $row['patient_id']) ?>" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm">
                                            <i class="bi bi-folder2-open me-1"></i> Open Workstation
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
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-clock-history text-success me-2"></i>Recently Delivered / Concluded (Last 90 Days)
                </h5>
                <small class="text-muted">Postpartum mothers and recently concluded pregnancy episodes</small>
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
                                        <div class="small text-muted font-monospace"><?= h($deliv['patient_no']) ?></div>
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
                                        ?>
                                        <span class="badge <?= $outcomeClass ?> px-2 py-1"><?= h($outcome) ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-secondary border"><?= (int)$deliv['visit_count'] ?> visits</span>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <a href="<?= url('/maternal/' . $deliv['patient_id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                            <i class="bi bi-eye me-1"></i> View Records
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
</div>

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
            "pageLength": 10,
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
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
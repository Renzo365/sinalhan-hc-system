<?php
/**
 * @var array $registeredRoster Registered well-baby infants/children
 * @var array $unregisteredChildren Children aged 0-5 without wellbaby records
 * @var string $search Active search query
 */

$title = 'Well-Baby & EPI Workstation';
$breadcrumbs = [
    'Well-Baby / EPI' => null
];
require dirname(__DIR__) . '/layout/header.php';

$totalRegistered = count($registeredRoster);
$totalUnregistered = count($unregisteredChildren);

// Count infants with active growth monitoring (growth log within last 60 days)
$activeMonitoringCount = 0;
foreach ($registeredRoster as $child) {
    if (!empty($child['growth_log_count']) && (int)$child['growth_log_count'] > 0) {
        $activeMonitoringCount++;
    }
}
?>

<div class="container-fluid py-4">
    <!-- Header Title Banner -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-1 text-dark">
                <i class="bi bi-emoji-smile-fill text-success me-2"></i>Well-Baby &amp; EPI Workstation
            </h2>
            <p class="text-muted mb-0">Infant birth circumstances, DOH EPI immunization schedule tracking, and pediatric growth logs.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= url('/well-baby/register') ?>" class="btn btn-success d-flex align-items-center py-2 px-3 shadow-sm text-white">
                <i class="bi bi-person-plus-fill me-2 fs-5"></i>
                <span>Register Infant</span>
            </a>
        </div>
    </div>

    <!-- Metrics Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white border-start border-4 border-success">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-success-subtle text-success p-3 me-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-emoji-smile-fill fs-4"></i>
                    </div>
                    <div>
                        <div class="text-secondary small fw-medium text-uppercase tracking-wider">Registered Infants (0-5y)</div>
                        <div class="fs-3 fw-bold text-dark"><?= $totalRegistered ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white border-start border-4 border-info">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-info-subtle text-info p-3 me-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-graph-up-arrow fs-4"></i>
                    </div>
                    <div>
                        <div class="text-secondary small fw-medium text-uppercase tracking-wider">Growth Monitored</div>
                        <div class="fs-3 fw-bold text-dark"><?= $activeMonitoringCount ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white border-start border-4 border-warning">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-warning-subtle text-warning p-3 me-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-person-plus-fill fs-4"></i>
                    </div>
                    <div>
                        <div class="text-secondary small fw-medium text-uppercase tracking-wider">Unregistered (Age ≤5)</div>
                        <div class="fs-3 fw-bold text-dark"><?= $totalUnregistered ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card card-premium mb-4">
        <div class="card-body p-4">
            <form action="<?= url('/well-baby') ?>" method="GET" class="row g-3 align-items-end" id="filtersForm">
                <!-- Search Keyword -->
                <div class="col-12 col-md-9">
                    <label for="search" class="form-label fw-semibold text-secondary small">Search Infant / Child or Mother</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" 
                               name="search" 
                               id="search" 
                               class="form-control bg-light border-start-0" 
                               placeholder="Search by child name, patient no, envelope, or mother's name..." 
                               value="<?= h($search) ?>">
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="col-12 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-success text-white flex-grow-1">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    <a href="<?= url('/well-baby') ?>" class="btn btn-outline-secondary" title="Clear Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Registered Infants Roster Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-list-check text-success me-2"></i>Registered Well-Baby Registry
                </h5>
                <small class="text-muted">Children with initialized birth records and pediatric health profiles</small>
            </div>
            <span class="badge bg-light text-secondary border px-2.5 py-1.5 font-monospace">
                Total Registered: <strong class="text-dark"><?= count($registeredRoster) ?></strong>
            </span>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small" id="wellbabyRosterTable">
                    <thead class="bg-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4 py-3">Child / Patient</th>
                            <th class="py-3">Age</th>
                            <th class="py-3">Linked Mother</th>
                            <th class="py-3">Birth Circumstances</th>
                            <th class="py-3 text-center">EPI Vaccines</th>
                            <th class="py-3 text-center">Growth Logs</th>
                            <th class="pe-4 py-3 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($registeredRoster)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-emoji-neutral fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    <?php if (!empty($search)): ?>
                                        No registered infants match "<strong><?= h($search) ?></strong>".
                                        <div class="mt-2"><a href="<?= url('/well-baby') ?>" class="btn btn-sm btn-outline-success">Clear Filter</a></div>
                                    <?php else: ?>
                                        No well-baby records registered yet.
                                        <div class="small text-muted mt-1">Initialize birth records for unregistered children using the Register Infant button above.</div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($registeredRoster as $child): 
                                $months = (int)($child['age_months'] ?? 0);
                                $ageLabel = $months < 12 ? "{$months} mos" : floor($months / 12) . " yr " . ($months % 12) . " mos";
                                $sexBadge = strtolower($child['sex'] ?? '') === 'male' 
                                    ? '<span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.7rem;">Male</span>'
                                    : '<span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.7rem;">Female</span>';
                            ?>
                                <tr>
                                    <td class="ps-4 py-3">
                                        <div>
                                            <a href="<?= url('/well-baby/' . $child['patient_id']) ?>" class="fw-bold text-dark text-decoration-none hover-success">
                                                <?= h($child['last_name']) ?>, <?= h($child['first_name']) ?> <?= !empty($child['middle_name']) ? h(mb_substr($child['middle_name'], 0, 1)) . '.' : '' ?>
                                            </a>
                                            <div class="small text-muted d-flex align-items-center gap-1 mt-1">
                                                <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.72rem;"><?= h($child['patient_no']) ?></span>
                                                <?php if (!empty($child['envelope_no'])): ?>
                                                    <span class="badge bg-warning-subtle text-dark border font-monospace" style="font-size: 0.72rem;">Env #<?= h($child['envelope_no']) ?></span>
                                                <?php endif; ?>
                                                <?= $sexBadge ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-dark"><?= $ageLabel ?></span>
                                        <div class="text-muted" style="font-size: 0.75rem;"><?= !empty($child['dob']) ? date('M d, Y', strtotime($child['dob'])) : '--' ?></div>
                                    </td>
                                    <td>
                                        <?php if (!empty($child['mother_id'])): ?>
                                            <a href="<?= url('/patients/' . $child['mother_id']) ?>" class="text-decoration-none fw-medium text-primary">
                                                <i class="bi bi-person-heart me-1"></i><?= h($child['mother_last_name']) ?>, <?= h($child['mother_first_name']) ?>
                                            </a>
                                            <div class="text-muted font-monospace" style="font-size: 0.72rem;"><?= h($child['mother_patient_no'] ?? '') ?></div>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">Not linked</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?= !empty($child['birth_weight_kg']) ? h($child['birth_weight_kg']) . ' kg' : '--' ?></strong> / 
                                            <strong><?= !empty($child['birth_length_cm']) ? h($child['birth_length_cm']) . ' cm' : '--' ?></strong>
                                        </div>
                                        <div class="text-muted text-truncate" style="max-width: 170px; font-size: 0.75rem;">
                                            <?= h($child['place_of_delivery'] ?? 'Lying-in') ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill bg-light text-success border border-success-subtle px-3 py-1 fw-bold">
                                            <i class="bi bi-shield-check me-1"></i><?= (int)($child['imm_count'] ?? 0) ?> doses
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill bg-light text-dark border px-3 py-1 fw-bold">
                                            <?= (int)($child['growth_log_count'] ?? 0) ?> visits
                                        </span>
                                        <?php if (!empty($child['last_growth_date'])): ?>
                                            <div class="text-muted mt-1" style="font-size: 0.7rem;">Last: <?= date('M d, Y', strtotime($child['last_growth_date'])) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <a href="<?= url('/well-baby/' . $child['patient_id']) ?>" class="btn btn-sm btn-success rounded-pill px-3 shadow-sm text-white">
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

    <!-- Unregistered Children (Age <= 5) Card -->
    <?php if (!empty($unregisteredChildren)): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-exclamation-circle text-warning me-2"></i>Unregistered Children (Aged 0–5 Years)
                    </h5>
                    <small class="text-muted">Children registered in the patient directory who do not have a well-baby birth record initialized yet</small>
                </div>
                <span class="badge bg-warning-subtle text-dark border px-3 py-1"><?= count($unregisteredChildren) ?> Pending Registration</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="bg-light text-secondary small text-uppercase">
                            <tr>
                                <th class="ps-4 py-3">Child Name</th>
                                <th class="py-3">Patient No.</th>
                                <th class="py-3">Age</th>
                                <th class="py-3">Sex</th>
                                <th class="pe-4 py-3 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($unregisteredChildren as $unreg): 
                                $uMonths = (int)($unreg['age_months'] ?? 0);
                                $uAgeLabel = $uMonths < 12 ? "{$uMonths} mos" : floor($uMonths / 12) . " yr " . ($uMonths % 12) . " mos";
                            ?>
                                <tr>
                                    <td class="ps-4 py-3 fw-bold text-dark">
                                        <?= h($unreg['last_name']) ?>, <?= h($unreg['first_name']) ?> <?= !empty($unreg['middle_name']) ? h(mb_substr($unreg['middle_name'], 0, 1)) . '.' : '' ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-secondary border font-monospace"><?= h($unreg['patient_no']) ?></span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold"><?= $uAgeLabel ?></span>
                                        <div class="small text-muted"><?= !empty($unreg['dob']) ? date('M d, Y', strtotime($unreg['dob'])) : '--' ?></div>
                                    </td>
                                    <td><?= h($unreg['sex'] ?? 'Unknown') ?></td>
                                    <td class="pe-4 text-end">
                                        <a href="<?= url('/well-baby/register?patient_id=' . $unreg['id']) ?>" class="btn btn-sm btn-outline-success rounded-pill px-3">
                                            <i class="bi bi-plus-circle me-1"></i> Initialize Well-Baby Record
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
.hover-success:hover {
    color: var(--bs-success) !important;
}
</style>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Initialize DataTable for Registered Well-Baby Registry
    <?php if (!empty($registeredRoster)): ?>
        $('#wellbabyRosterTable').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": false, // Handled by custom filter card
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "pageLength": 10,
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
            "order": [[0, "asc"]], // Order by child name ascending
            "columnDefs": [
                { "orderable": false, "targets": 6 } // Disable sorting on Action column (index 6)
            ],
            "language": {
                "lengthMenu": "Show _MENU_ children",
                "paginate": {
                    "previous": "<i class='bi bi-chevron-left'></i>",
                    "next": "<i class='bi bi-chevron-right'></i>"
                }
            }
        });
    <?php endif; ?>
});
</script>

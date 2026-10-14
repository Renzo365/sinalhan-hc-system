<?php
/**
 * Shared Reusable Patient Demographics & Family Contacts Drawer
 * 
 * Displays full biographical, contact, family lineage, and PhilHealth coverage details
 * inside a sleek, seamless 4-column collapsible drawer within the Patient Master Header.
 * 
 * Expects $patient array in scope.
 */

$formatDateLocal = function($dateStr, $format = 'M d, Y') {
    if (empty($dateStr) || $dateStr === '0000-00-00' || $dateStr === '0000-00-00 00:00:00') {
        return null;
    }
    $ts = strtotime($dateStr);
    return ($ts !== false && $ts > 0) ? date($format, $ts) : null;
};
?>

<!-- Collapsible Seamless 4-Column Demographics & Contacts Drawer -->
<div class="collapse patient-demographics-drawer mt-3 pt-3 border-top" id="patientDemographicsCollapse" role="region" aria-label="Full Patient Demographics and Emergency Contacts">
    <div class="px-1 py-1">
        
        <!-- Drawer Header Bar -->
        <div class="d-flex flex-wrap justify-content-between align-items-center pb-2 mb-3 border-bottom gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="fw-bold text-dark small text-uppercase" style="letter-spacing: 0.5px; font-size: 0.78rem;">
                    <i class="bi bi-person-lines-fill text-primary me-1.5" aria-hidden="true"></i> Complete Biographical &amp; Family Record
                </span>
            </div>
            <a href="<?= url('/patients/' . $patient['id'] . '/edit') ?>" class="btn btn-xs btn-outline-primary py-1 px-2.5 d-inline-flex align-items-center shadow-xs" title="Edit Patient Information & Demographics" style="font-size: 0.8125rem;">
                <i class="bi bi-pencil me-1.5"></i> Edit Profile Details
            </a>
        </div>

        <!-- 4-Column Responsive Grid -->
        <div class="row g-3 g-lg-4 small">
            
            <!-- Column 1: Contact & Residence (Location & Reachability) -->
            <div class="col-12 col-md-6 col-xl-3">
                <div class="drawer-section-heading text-uppercase fw-bold text-secondary mb-2" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                    <i class="bi bi-geo-alt-fill text-danger me-1"></i> Contact &amp; Residence
                </div>

                <div class="mb-2">
                    <span class="overview-micro-label d-block text-muted" style="font-size: 0.72rem;">Residential Address</span>
                    <span class="text-dark fw-medium lh-sm d-block">
                        <?= !empty(trim($patient['address'] ?? '')) ? h(trim($patient['address'])) : '<span class="text-muted fst-italic">No address recorded</span>' ?>
                    </span>
                </div>

                <div class="mb-2 border-top pt-2">
                    <span class="overview-micro-label d-block text-muted" style="font-size: 0.72rem;">Primary Contact Number</span>
                    <?php if (!empty($patient['contact_no'])): ?>
                        <div class="d-flex align-items-center">
                            <span class="font-monospace fw-semibold text-dark"><a href="tel:<?= h($patient['contact_no']) ?>" class="text-decoration-none text-dark"><?= h($patient['contact_no']) ?></a></span>
                            <button type="button" class="btn btn-link btn-xs p-0 text-secondary ms-1.5 copy-clipboard-btn" data-clipboard="<?= h($patient['contact_no']) ?>" title="Copy phone number" aria-label="Copy primary phone number">
                                <i class="bi bi-copy"></i>
                            </button>
                        </div>
                    <?php else: ?>
                        <span class="text-muted fst-italic">None registered</span>
                    <?php endif; ?>
                </div>

                <div class="border-top pt-2">
                    <span class="overview-micro-label d-block text-muted" style="font-size: 0.72rem;">Emergency Contact &amp; Phone</span>
                    <span class="fw-semibold text-dark d-block"><?= h($patient['emergency_name'] ?? 'None registered') ?></span>
                    <?php if (!empty($patient['emergency_relationship'])): ?>
                        <span class="text-muted small d-block"><?= h($patient['emergency_relationship']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($patient['emergency_no'])): ?>
                        <div class="d-flex align-items-center mt-1">
                            <i class="bi bi-telephone-fill text-danger me-1 small"></i>
                            <span class="font-monospace fw-semibold text-dark"><a href="tel:<?= h($patient['emergency_no']) ?>" class="text-decoration-none text-dark"><?= h($patient['emergency_no']) ?></a></span>
                            <button type="button" class="btn btn-link btn-xs p-0 text-secondary ms-1.5 copy-clipboard-btn" data-clipboard="<?= h($patient['emergency_no']) ?>" title="Copy emergency phone" aria-label="Copy emergency phone">
                                <i class="bi bi-copy"></i>
                            </button>
                        </div>
                    <?php else: ?>
                        <span class="text-muted fst-italic small d-block">No emergency phone</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Column 2: Civil & Social Profile -->
            <div class="col-12 col-md-6 col-xl-3">
                <div class="drawer-section-heading text-uppercase fw-bold text-secondary mb-2" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                    <i class="bi bi-person-badge-fill text-primary me-1"></i> Civil &amp; Social Profile
                </div>

                <div class="mb-2">
                    <span class="overview-micro-label d-block text-muted" style="font-size: 0.72rem;">Civil Status</span>
                    <span class="fw-semibold text-dark">
                        <?= (($patient['civil_status'] ?? '') === 'Others' && !empty($patient['civil_status_other'])) ? 'Others (' . h($patient['civil_status_other']) . ')' : h($patient['civil_status'] ?? 'Unspecified') ?>
                    </span>
                </div>

                <div class="mb-2 border-top pt-2">
                    <span class="overview-micro-label d-block text-muted" style="font-size: 0.72rem;">Religion</span>
                    <span class="text-dark fw-medium"><?= h($patient['religion'] ?? 'Unspecified') ?></span>
                </div>

                <div class="mb-2 border-top pt-2">
                    <span class="overview-micro-label d-block text-muted" style="font-size: 0.72rem;">Educational Attainment</span>
                    <span class="text-dark fw-medium"><?= h($patient['education_attainment'] ?? 'Unspecified') ?></span>
                </div>

                <div class="border-top pt-2">
                    <span class="overview-micro-label d-block text-muted" style="font-size: 0.72rem;">Occupation</span>
                    <span class="text-dark fw-medium"><?= h($patient['occupation'] ?? 'Unspecified') ?></span>
                </div>
            </div>

            <!-- Column 3: Family Lineage & Household -->
            <div class="col-12 col-md-6 col-xl-3">
                <div class="drawer-section-heading text-uppercase fw-bold text-secondary mb-2" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                    <i class="bi bi-people-fill text-success me-1"></i> Family Lineage
                </div>

                <div class="mb-2">
                    <span class="overview-micro-label d-block text-muted" style="font-size: 0.72rem;">Father's Full Name</span>
                    <span class="text-dark fw-medium d-block">
                        <?= !empty($patient['father_name']) ? h($patient['father_name']) . ($formatDateLocal($patient['father_dob'] ?? null) ? ' <span class="text-muted small">(DOB: ' . $formatDateLocal($patient['father_dob']) . ')</span>' : '') : '<span class="text-muted">None on record</span>' ?>
                    </span>
                </div>

                <div class="mb-2 border-top pt-2">
                    <span class="overview-micro-label d-block text-muted" style="font-size: 0.72rem;">Mother's Maiden Name</span>
                    <span class="text-dark fw-medium d-block">
                        <?= !empty($patient['mother_name']) ? h($patient['mother_name']) . ($formatDateLocal($patient['mother_dob'] ?? null) ? ' <span class="text-muted small">(DOB: ' . $formatDateLocal($patient['mother_dob']) . ')</span>' : '') : '<span class="text-muted">None on record</span>' ?>
                    </span>
                </div>

                <div class="mb-2 border-top pt-2">
                    <span class="overview-micro-label d-block text-muted" style="font-size: 0.72rem;">Spouse's Full Name</span>
                    <span class="text-dark fw-medium d-block">
                        <?= !empty($patient['spouse_name']) ? h($patient['spouse_name']) . ($formatDateLocal($patient['spouse_dob'] ?? null) ? ' <span class="text-muted small">(DOB: ' . $formatDateLocal($patient['spouse_dob']) . ')</span>' : '') : '<span class="text-muted">None on record</span>' ?>
                    </span>
                </div>

                <div class="border-top pt-2">
                    <span class="overview-micro-label d-block text-muted" style="font-size: 0.72rem;">Household Family Record</span>
                    <?php if (!empty($patient['family_no'])): ?>
                        <a href="<?= url('/patients?search=' . urlencode($patient['family_no'])) ?>" class="patient-id-tag tag-family tag-interactive font-monospace mt-0.5 d-inline-flex" title="View household directory" aria-label="View household directory for Family <?= h($patient['family_no']) ?>">
                            <span class="tag-label">Fam</span>
                            <span class="tag-value">#<?= h($patient['family_no']) ?></span>
                            <i class="bi bi-box-arrow-up-right ms-0.5 text-muted" style="font-size: 0.65rem;" aria-hidden="true"></i>
                        </a>
                    <?php else: ?>
                        <span class="text-muted fst-italic">No family number assigned</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Column 4: PhilHealth & Healthcare Coverage -->
            <div class="col-12 col-md-6 col-xl-3">
                <div class="drawer-section-heading text-uppercase fw-bold text-secondary mb-2" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                    <i class="bi bi-shield-check text-info me-1"></i> PhilHealth Coverage
                </div>

                <div class="mb-2">
                    <span class="overview-micro-label d-block text-muted" style="font-size: 0.72rem;">PhilHealth Membership Status</span>
                    <?php
                    $phicStatus = $patient['phic_status'] ?? 'Non-Member';
                    $phicClass = ($phicStatus === 'Member') ? 'badge-phic-member' : (($phicStatus === 'Dependent') ? 'badge-phic-dependent' : 'badge-phic-none');
                    ?>
                    <span class="badge <?= $phicClass ?> mt-1">
                        <?= h($phicStatus) ?>
                    </span>
                </div>

                <div class="mb-2 border-top pt-2">
                    <span class="overview-micro-label d-block text-muted" style="font-size: 0.72rem;">PHIC Category / Type</span>
                    <span class="text-dark fw-medium d-block">
                        <?= !empty($patient['phic_type']) ? h($patient['phic_type']) : '<span class="text-muted">None / Unspecified</span>' ?>
                    </span>
                </div>

                <div class="border-top pt-2">
                    <span class="overview-micro-label d-block text-muted" style="font-size: 0.72rem;">PhilHealth Identification No. (PIN)</span>
                    <?php if (!empty($patient['philhealth_no'])): ?>
                        <div class="d-flex align-items-center mt-1">
                            <span class="font-monospace fw-semibold text-dark fs-7"><?= h($patient['philhealth_no']) ?></span>
                            <button type="button" class="btn btn-link btn-xs p-0 text-secondary ms-1.5 copy-clipboard-btn" data-clipboard="<?= h($patient['philhealth_no']) ?>" title="Copy PhilHealth PIN" aria-label="Copy PhilHealth PIN">
                                <i class="bi bi-copy"></i>
                            </button>
                        </div>
                    <?php else: ?>
                        <span class="text-muted fst-italic">None on record</span>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

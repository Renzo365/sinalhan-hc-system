<!-- Client-side Scripts -->
<script>
window.filterPcbRows = function(cat, btn) {
    const rows = document.querySelectorAll('.pcb-log-row');
    const filterBtns = document.querySelectorAll('#pcbCategoryFilters button');
    filterBtns.forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');

    let visibleCount = 0;
    rows.forEach(r => {
        if (cat === 'all' || r.dataset.category === cat) {
            r.style.display = '';
            visibleCount++;
        } else {
            r.style.display = 'none';
        }
    });
    const countEl = document.getElementById('pcbRowCount');
    if (countEl) countEl.innerText = visibleCount;
};

// Dedicated IHP Edit Page Navigation
window.enterIhpEditMode = function() {
    window.location.href = '<?= url('/patients/' . $patient['id'] . '/ihp/edit') ?>';
};

window.editIhpFromOverview = function() {
    window.location.href = '<?= url('/patients/' . $patient['id'] . '/ihp/edit') ?>';
};

document.addEventListener('DOMContentLoaded', function() {
    // 1. BMI Auto-calculation in Vitals Modal
    const weightInput = document.getElementById('weight');
    const heightInput = document.getElementById('height');
    const bmiInput = document.getElementById('bmi');

    function calculateBMI() {
        const weight = parseFloat(weightInput.value);
        const height = parseFloat(heightInput.value);
        if (weight > 0 && height > 0) {
            const heightInMeters = height / 100;
            const bmi = weight / (heightInMeters * heightInMeters);
            bmiInput.value = bmi.toFixed(2);
        } else {
            bmiInput.value = '';
        }
    }

    if (weightInput && heightInput) {
        weightInput.addEventListener('input', calculateBMI);
        heightInput.addEventListener('input', calculateBMI);
    }

    // BMI Auto-calculation in Edit Vitals Modal
    const editWeightInput = document.getElementById('editVitalWeight');
    const editHeightInput = document.getElementById('editVitalHeight');
    const editBmiInput = document.getElementById('editVitalBmi');

    function calculateEditBMI() {
        if (!editWeightInput || !editHeightInput || !editBmiInput) return;
        const weight = parseFloat(editWeightInput.value);
        const height = parseFloat(editHeightInput.value);
        if (weight > 0 && height > 0) {
            const heightInMeters = height / 100;
            const bmi = weight / (heightInMeters * heightInMeters);
            editBmiInput.value = bmi.toFixed(2);
        } else {
            editBmiInput.value = '';
        }
    }

    if (editWeightInput && editHeightInput) {
        editWeightInput.addEventListener('input', calculateEditBMI);
        editHeightInput.addEventListener('input', calculateEditBMI);
    }

    // 2. Tab URL Synchronization (Real-time dedicated URLs, History navigation & Persistence)
    function activateTabFromHash(hash) {
        if (!hash) return false;
        const cleanHash = hash.replace(/^#/, '').trim().toLowerCase();
        if (!cleanHash) return false;

        if (cleanHash === 'edit-ihp') {
            if (typeof editIhpFromOverview === 'function') {
                editIhpFromOverview();
                return true;
            }
        }

        const aliasMap = {
            'overview': 'tab-overview',
            'ihp': 'tab-ihp',
            'ihp-history': 'tab-ihp',
            'pcb': 'tab-pcb',
            'phic': 'tab-pcb',
            'phic-pcb': 'tab-pcb',
            'consultations': 'tab-consultations',
            'soap': 'tab-consultations',
            'vitals': 'tab-vitals',
            'vitals-log': 'tab-vitals',
            'immunizations': 'tab-immunizations',
            // 'prenatal': routed to dedicated /maternal workstation,
            // 'wellbaby': routed to dedicated /well-baby workstation,
            'appointments': 'tab-appointments',
            'appointment': 'tab-appointments',
            'queue': 'tab-appointments',
            'queue-tab': 'tab-appointments'
        };

        let targetTabId = aliasMap[cleanHash] || (cleanHash.startsWith('tab-') ? cleanHash : 'tab-' + cleanHash);
        if (targetTabId.endsWith('-tab')) {
            targetTabId = 'tab-' + targetTabId.replace(/-tab$/, '').replace(/^tab-/, '');
        }

        const tabBtn = document.querySelector(`button[data-bs-target="#${targetTabId}"]`) 
            || document.getElementById(targetTabId + '-btn')
            || document.querySelector(`button[data-bs-target="#${cleanHash}"]`);

        if (tabBtn) {
            const bsTab = bootstrap.Tab.getOrCreateInstance(tabBtn);
            bsTab.show();
            setTimeout(() => {
                tabBtn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            }, 60);
            return true;
        }
        return false;
    }

    // Tab Scroll Buttons and Auto-Scroll Behavior
    const workstationNav = document.getElementById('workstationTabs');
    const tabScrollPrev = document.getElementById('tabScrollPrev');
    const tabScrollNext = document.getElementById('tabScrollNext');

    function updateTabScrollButtons() {
        if (!workstationNav) return;
        const scrollLeft = workstationNav.scrollLeft;
        const maxScroll = workstationNav.scrollWidth - workstationNav.clientWidth;

        if (tabScrollPrev) {
            if (scrollLeft > 15) {
                tabScrollPrev.classList.add('visible');
            } else {
                tabScrollPrev.classList.remove('visible');
            }
        }

        if (tabScrollNext) {
            if (maxScroll - scrollLeft > 15) {
                tabScrollNext.classList.add('visible');
            } else {
                tabScrollNext.classList.remove('visible');
            }
        }
    }

    if (workstationNav) {
        workstationNav.addEventListener('scroll', updateTabScrollButtons, { passive: true });
        window.addEventListener('resize', updateTabScrollButtons, { passive: true });

        if (tabScrollPrev) {
            tabScrollPrev.addEventListener('click', function() {
                workstationNav.scrollBy({ left: -260, behavior: 'smooth' });
            });
        }

        if (tabScrollNext) {
            tabScrollNext.addEventListener('click', function() {
                workstationNav.scrollBy({ left: 260, behavior: 'smooth' });
            });
        }

        // Sync address bar URL and scroll tab into view when clicked
        workstationNav.addEventListener('shown.bs.tab', function(e) {
            setTimeout(() => {
                e.target.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            }, 60);
            const target = e.target.getAttribute('data-bs-target');
            if (target && window.location.hash !== target) {
                if (window.history && window.history.pushState) {
                    window.history.pushState(null, '', target);
                } else {
                    window.location.hash = target;
                }
            }
            updateTabScrollButtons();
        });

        setTimeout(updateTabScrollButtons, 120);
    }

    // Keep patient history tables consistent while avoiding pagination for short lists.
    if (window.jQuery && $.fn.DataTable) {
        ['#consultationsTable', '#vitalsTable', '#immunizationsTable', '#pcbServiceLogsTable'].forEach(function(selector) {
            const table = document.querySelector(selector);
            if (!table || table.querySelector('tbody tr td[colspan]')) return;

            $(table).DataTable({
                pageLength: 10,
                lengthChange: false,
                searching: true,
                ordering: true,
                order: [[0, 'desc']],
                autoWidth: false,
                responsive: false,
                language: {
                    search: 'Filter records:',
                    searchPlaceholder: 'Search this history'
                },
                columnDefs: [
                    { targets: -1, orderable: false, searchable: false }
                ]
            });
        });
    }

    // Activate tab on page load if hash present in URL
    if (window.location.hash) {
        activateTabFromHash(window.location.hash);
    }

    // Support browser Back and Forward history buttons
    window.addEventListener('popstate', function() {
        if (window.location.hash) {
            activateTabFromHash(window.location.hash);
        } else {
            activateTabFromHash('#tab-overview');
        }
        setTimeout(updateTabScrollButtons, 80);
    });

    // 3. Clinical Consultation Modal AJAX Loader
    const viewConsultationModal = document.getElementById('viewConsultationModal');
    const consultationDetailsContent = document.getElementById('consultationDetailsContent');

    if (viewConsultationModal) {
        viewConsultationModal.addEventListener('hidden.bs.modal', function () {
            if (!document.querySelector('.modal.show')) {
                document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');
            }
        });
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.view-consultation-btn');
        if (!btn) return;
        e.preventDefault();

        const consultationId = btn.getAttribute('data-consultation-id');
        if (!consultationId) return;

        const modal = bootstrap.Modal.getOrCreateInstance(viewConsultationModal);
        modal.show();

        const footerRight = document.getElementById('consultationModalFooterRight');
        if (footerRight) {
            footerRight.innerHTML = `<button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Close</button>`;
        }

        consultationDetailsContent.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="text-muted mt-2 small">Fetching consultation record...</p>
            </div>
        `;

        fetch(`<?= url('/consultations/') ?>${consultationId}`, {
            headers: {
                'Accept': 'application/json'
            }
        })
                .then(res => {
                    if (!res.ok) throw new Error('Network response was not ok');
                    return res.json();
                })
                .then(data => {
                    if (data.error) {
                        consultationDetailsContent.innerHTML = `<div class="alert alert-danger mb-0">${escapeHtml(data.error)}</div>`;
                        return;
                    }

                    // Vitals strip formatting
                    let vitalsHtml = '';
                    if (data.bp_systolic || data.temperature || data.heart_rate || data.weight || data.height) {
                        const isHighBp = (parseInt(data.bp_systolic) >= 140 || parseInt(data.bp_diastolic) >= 90);
                        vitalsHtml = `
                            <div class="p-3 bg-light rounded-3 mb-3 border">
                                <div class="d-flex flex-wrap align-items-center gap-2 small">
                                    <span class="fw-bold text-dark me-1">Linked Vital Signs:</span>
                                    ${data.bp_systolic && data.bp_diastolic ? `
                                        <span class="badge ${isHighBp ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-white text-dark border'} py-2 px-2 fw-normal">
                                            BP: <strong class="${isHighBp ? 'text-danger' : 'text-dark'}">${escapeHtml(data.bp_systolic)}/${escapeHtml(data.bp_diastolic)} mmHg</strong>
                                        </span>` : ''}
                                    ${data.temperature ? `
                                        <span class="badge bg-white text-dark border py-2 px-2 fw-normal">
                                            Temp: <strong class="text-dark">${escapeHtml(data.temperature)} °C</strong>
                                        </span>` : ''}
                                    ${data.heart_rate ? `
                                        <span class="badge bg-white text-dark border py-2 px-2 fw-normal">
                                            HR: <strong class="text-dark">${escapeHtml(data.heart_rate)} bpm</strong>
                                        </span>` : ''}
                                    ${data.respiratory_rate ? `
                                        <span class="badge bg-white text-dark border py-2 px-2 fw-normal">
                                            RR: <strong class="text-dark">${escapeHtml(data.respiratory_rate)} cpm</strong>
                                        </span>` : ''}
                                    ${data.oxygen_saturation ? `
                                        <span class="badge bg-white text-dark border py-2 px-2 fw-normal">
                                            SpO2: <strong class="text-dark">${escapeHtml(data.oxygen_saturation)}%</strong>
                                        </span>` : ''}
                                    ${data.weight ? `
                                        <span class="badge bg-white text-dark border py-2 px-2 fw-normal">
                                            Weight: <strong class="text-dark">${escapeHtml(data.weight)} kg</strong>
                                        </span>` : ''}
                                    ${data.height ? `
                                        <span class="badge bg-white text-dark border py-2 px-2 fw-normal">
                                            Height: <strong class="text-dark">${escapeHtml(data.height)} cm</strong>
                                        </span>` : ''}
                                    ${data.bmi ? `
                                        <span class="badge bg-white text-dark border py-2 px-2 fw-normal">
                                            BMI: <strong class="text-dark">${escapeHtml(data.bmi)}</strong>
                                        </span>` : ''}
                                </div>
                            </div>
                        `;
                    }

                    consultationDetailsContent.innerHTML = `
                        <!-- Metadata Header Strip -->
                        <div class="row g-2 pb-3 mb-3 border-bottom align-items-center">
                            <div class="col-12 col-md-6">
                                <span class="text-muted small d-block">Consultation Date & Time:</span>
                                <strong class="text-dark">${escapeHtml(data.formatted_date || data.consulted_at)}</strong>
                            </div>
                            <div class="col-12 col-md-6">
                                <span class="text-muted small d-block">Attending Clinician:</span>
                                <strong class="text-dark">${escapeHtml(data.clinician_name || 'Unassigned Clinician')}</strong>
                            </div>
                        </div>

                        ${vitalsHtml}

                        <!-- Clinical Consultation Ledger Cards -->
                        <div class="d-flex flex-column gap-3">
                            <!-- History of Present Illness -->
                            <div class="card border rounded-3 bg-white">
                                <div class="card-header bg-light py-2 px-3 border-bottom">
                                    <span class="fw-bold text-dark small text-uppercase">History of Present Illness</span>
                                </div>
                                <div class="card-body p-3 text-dark small" style="white-space: pre-line; line-height: 1.6;">${escapeHtml(data.subjective || 'No history of present illness recorded.')}</div>
                            </div>

                            <!-- Physical Examination -->
                            <div class="card border rounded-3 bg-white">
                                <div class="card-header bg-light py-2 px-3 border-bottom">
                                    <span class="fw-bold text-dark small text-uppercase">Physical Examination</span>
                                </div>
                                <div class="card-body p-3 text-dark small" style="white-space: pre-line; line-height: 1.6;">${escapeHtml(data.objective || 'No physical examination findings recorded.')}</div>
                            </div>

                            <!-- Assessment / Impression -->
                            <div class="card border rounded-3 bg-white">
                                <div class="card-header bg-light py-2 px-3 border-bottom">
                                    <span class="fw-bold text-dark small text-uppercase">Assessment / Impression</span>
                                </div>
                                <div class="card-body p-3 text-dark small fw-medium" style="white-space: pre-line; line-height: 1.6;">${escapeHtml(data.assessment || 'No clinical diagnosis recorded.')}</div>
                            </div>

                            <!-- Treatment / Management -->
                            <div class="card border rounded-3 bg-white">
                                <div class="card-header bg-light py-2 px-3 border-bottom">
                                    <span class="fw-bold text-dark small text-uppercase">Treatment / Management</span>
                                </div>
                                <div class="card-body p-3 text-dark small" style="white-space: pre-line; line-height: 1.6;">${escapeHtml(data.plan || 'No treatment plan recorded.')}</div>
                            </div>

                            <!-- Structured Prescriptions (Rx) -->
                            ${data.prescriptions && data.prescriptions.length > 0 ? `
                            <div class="card border rounded-3 bg-white">
                                <div class="card-header bg-light py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5">Rx</span>
                                        <span class="fw-bold text-dark small text-uppercase">Prescribed Medications</span>
                                    </div>
                                    <span class="badge bg-success">${data.prescriptions.length} item${data.prescriptions.length > 1 ? 's' : ''}</span>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped align-middle mb-0 small">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Medicine</th>
                                                    <th>Dosage</th>
                                                    <th>Frequency</th>
                                                    <th>Duration</th>
                                                    <th>Instructions / Sig</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${data.prescriptions.map(p => `
                                                    <tr>
                                                        <td class="fw-semibold text-primary">${escapeHtml(p.medicine_name)}</td>
                                                        <td>${escapeHtml(p.dosage || '—')}</td>
                                                        <td>${escapeHtml(p.frequency || '—')}</td>
                                                        <td>${escapeHtml(p.duration || '—')}</td>
                                                        <td class="text-muted">${escapeHtml(p.instructions || '—')}</td>
                                                    </tr>
                                                `).join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            ` : ''}
                        </div>

                        <!-- Bottom Audit Footer Strip -->
                        <div class="d-flex flex-wrap justify-content-between align-items-center text-muted small border-top pt-2 mt-3">
                            <span><strong>Recorded by:</strong> ${escapeHtml(data.creator_name || 'System')}${data.creator_role ? ` (${escapeHtml(data.creator_role)})` : ''} on ${escapeHtml(data.formatted_created || data.created_at)}</span>
                            ${data.formatted_updated ? `<span><strong>Last modified:</strong> ${escapeHtml(data.updater_name || 'Staff')}${data.updater_role ? ` (${escapeHtml(data.updater_role)})` : ''} on ${escapeHtml(data.formatted_updated)}</span>` : ''}
                        </div>
                    `;

                    // Update modal footer actions (Edit button if authorized)
                    const footerRight = document.getElementById('consultationModalFooterRight');
                    if (footerRight) {
                        let footerBtns = '';
                        if (data.can_edit) {
                            footerBtns += `<a href="<?= url('/consultations/') ?>${data.id}/edit" class="btn btn-primary btn-sm px-3">Edit Consultation</a>`;
                        }
                        footerBtns += `<button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Close</button>`;
                        footerRight.innerHTML = footerBtns;
                    }
                })
                .catch(err => {
                    consultationDetailsContent.innerHTML = `
                        <div class="alert alert-danger mb-0">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>Failed to load consultation details. Please try again.
                        </div>
                    `;
                });
    });

    // 6. Modernized Workstation Action Handlers

    // A. Archive Consultation
    document.querySelectorAll('.btn-archive-consultation').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            const patientId = this.getAttribute('data-patient-id');
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Archive Consultation?',
                    text: 'Archiving hides this consultation note from the active history. Please provide a reason:',
                    input: 'textarea',
                    inputPlaceholder: 'e.g. Inadvertent duplication, erroneous entry...',
                    inputValidator: (value) => {
                        if (!value || !value.trim()) {
                            return 'Archive reason is required!';
                        }
                    },
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Archive Consultation',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.getElementById('archiveConsultationForm');
                        form.action = `<?= url('/consultations/') ?>${id}/archive`;
                        document.getElementById('archiveConsultationReasonInput').value = result.value.trim();
                        form.submit();
                    }
                });
            } else {
                const reason = prompt('Please enter the reason for archiving this consultation:');
                if (reason && reason.trim()) {
                    const form = document.getElementById('archiveConsultationForm');
                    form.action = `<?= url('/consultations/') ?>${id}/archive`;
                    document.getElementById('archiveConsultationReasonInput').value = reason.trim();
                    form.submit();
                }
            }
        });
    });

    // B. View Vital Signs Details Modal
    const viewVitalsModalEl = document.getElementById('viewVitalsModal');
    const viewVitalsModal = viewVitalsModalEl ? bootstrap.Modal.getOrCreateInstance(viewVitalsModalEl) : null;

    if (viewVitalsModalEl) {
        viewVitalsModalEl.addEventListener('hidden.bs.modal', function () {
            if (!document.querySelector('.modal.show')) {
                document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');
            }
        });
    }

    document.querySelectorAll('.btn-view-vitals').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const date = this.getAttribute('data-date') || '--';
            const recorder = this.getAttribute('data-recorder') || 'Clinician';
            const bp = this.getAttribute('data-bp') || '--';
            const pulse = this.getAttribute('data-pulse') || '--';
            const temp = this.getAttribute('data-temp') || '--';
            const resp = this.getAttribute('data-resp') || '--';
            const spo2 = this.getAttribute('data-spo2') || '--';
            const weight = this.getAttribute('data-weight') || '--';
            const height = this.getAttribute('data-height') || '--';
            const bmi = this.getAttribute('data-bmi') || '--';
            const waist = this.getAttribute('data-waist') || '--';
            const notes = this.getAttribute('data-notes') || '';

            document.getElementById('modalVitalDate').textContent = date;
            document.getElementById('modalVitalRecorder').textContent = recorder;
            document.getElementById('modalVitalBP').textContent = bp;
            document.getElementById('modalVitalPulse').textContent = pulse;
            document.getElementById('modalVitalTemp').textContent = temp;
            document.getElementById('modalVitalResp').textContent = resp;
            document.getElementById('modalVitalSpo2').textContent = spo2;
            document.getElementById('modalVitalWeight').textContent = weight;
            document.getElementById('modalVitalHeight').textContent = height;
            document.getElementById('modalVitalWaist').textContent = waist;

            // BMI category determination
            let bmiDisplay = bmi;
            if (bmi !== '--' && !isNaN(parseFloat(bmi))) {
                const bmiVal = parseFloat(bmi);
                let cat = '';
                let badgeClass = 'bg-secondary-subtle text-secondary';
                if (bmiVal < 18.5) {
                    cat = 'Underweight';
                    badgeClass = 'bg-info-subtle text-info border border-info-subtle';
                } else if (bmiVal <= 22.9) {
                    cat = 'Normal';
                    badgeClass = 'bg-success-subtle text-success border border-success-subtle';
                } else if (bmiVal <= 27.4) {
                    cat = 'Overweight';
                    badgeClass = 'bg-warning-subtle text-dark border border-warning-subtle';
                } else {
                    cat = 'Obese';
                    badgeClass = 'bg-danger-subtle text-danger border border-danger-subtle';
                }
                bmiDisplay = `${bmi} <span class="badge ${badgeClass} ms-1">${cat}</span>`;
            }
            document.getElementById('modalVitalBmi').innerHTML = bmiDisplay;

            const notesEl = document.getElementById('modalVitalNotes');
            if (notes && notes.trim()) {
                notesEl.textContent = notes.trim();
            } else {
                notesEl.innerHTML = '<span class="text-muted fst-italic">No symptoms or notes recorded.</span>';
            }

            if (viewVitalsModal) {
                viewVitalsModal.show();
            }
        });
    });

    // C. Delete Vital Signs Record
    document.querySelectorAll('.btn-delete-vital').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Delete Vital Signs?',
                    text: 'Are you sure you want to delete this vital signs record? This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.getElementById('deleteVitalForm');
                        form.action = `<?= url('/vital-signs/') ?>${id}/delete`;
                        form.submit();
                    }
                });
            } else if (confirm('Delete this vital signs record?')) {
                const form = document.getElementById('deleteVitalForm');
                form.action = `<?= url('/vital-signs/') ?>${id}/delete`;
                form.submit();
            }
        });
    });

    // D. Delete Universal Immunization Record
    document.querySelectorAll('.btn-delete-immunization').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            const vaccine = this.getAttribute('data-vaccine') || 'Vaccine';
            const dose = this.getAttribute('data-dose') || '1';

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Delete Immunization Record?',
                    text: `Are you sure you want to delete Dose #${dose} of ${vaccine}?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete record',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.getElementById('deleteImmunizationForm');
                        form.action = `<?= url('/immunizations/') ?>${id}/delete`;
                        form.submit();
                    }
                });
            } else if (confirm(`Are you sure you want to delete Dose #${dose} of ${vaccine}?`)) {
                const form = document.getElementById('deleteImmunizationForm');
                form.action = `<?= url('/immunizations/') ?>${id}/delete`;
                form.submit();
            }
        });
    });

    // E. Delete PCB Service Encounter Record
    document.querySelectorAll('.btn-delete-pcb-service').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            const serviceName = this.getAttribute('data-service') || 'PCB service encounter';

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Delete PCB Service?',
                    text: `Are you sure you want to delete this "${serviceName}" encounter record? This action cannot be undone.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.getElementById('deletePcbServiceForm');
                        form.action = `<?= url('/pcb/service-log/') ?>${id}/delete`;
                        form.submit();
                    }
                });
            } else if (confirm(`Are you sure you want to delete this "${serviceName}" encounter record?`)) {
                const form = document.getElementById('deletePcbServiceForm');
                form.action = `<?= url('/pcb/service-log/') ?>${id}/delete`;
                form.submit();
            }
        });
    });

    // F. Cancel Appointment
    document.querySelectorAll('.btn-cancel-appointment').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            const date = this.getAttribute('data-date') || '';

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Cancel Appointment?',
                    text: `Are you sure you want to cancel the appointment scheduled for ${date}?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, cancel appointment',
                    cancelButtonText: 'Keep'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.getElementById('cancelAppointmentForm');
                        form.action = `<?= url('/appointments/') ?>${id}/status`;
                        form.submit();
                    }
                });
            } else if (confirm(`Cancel appointment scheduled for ${date}?`)) {
                const form = document.getElementById('cancelAppointmentForm');
                form.action = `<?= url('/appointments/') ?>${id}/status`;
                form.submit();
            }
        });
    });

    // G. Edit Vital Signs Modal Handler
    const editVitalsModalEl = document.getElementById('editVitalsModal');
    const editVitalsModal = editVitalsModalEl ? bootstrap.Modal.getOrCreateInstance(editVitalsModalEl) : null;
    document.querySelectorAll('.btn-edit-vitals').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            const form = document.getElementById('editVitalsForm');
            if (form) form.action = `<?= url('/vital-signs/') ?>${id}/update`;

            document.getElementById('editVitalSystolic').value = this.getAttribute('data-bp-systolic') || '';
            document.getElementById('editVitalDiastolic').value = this.getAttribute('data-bp-diastolic') || '';
            document.getElementById('editVitalPulse').value = this.getAttribute('data-pulse') || '';
            document.getElementById('editVitalTemp').value = this.getAttribute('data-temp') || '';
            document.getElementById('editVitalResp').value = this.getAttribute('data-resp') || '';
            document.getElementById('editVitalSpo2').value = this.getAttribute('data-spo2') || '';
            document.getElementById('editVitalWeight').value = this.getAttribute('data-weight') || '';
            document.getElementById('editVitalHeight').value = this.getAttribute('data-height') || '';
            document.getElementById('editVitalWaist').value = this.getAttribute('data-waist') || '';
            document.getElementById('editVitalNotes').value = this.getAttribute('data-notes') || '';
            calculateEditBMI();

            if (editVitalsModal) editVitalsModal.show();
        });
    });

    // H. View PCB Service Modal Handler
    const viewPcbModalEl = document.getElementById('viewPcbServiceModal');
    const viewPcbModal = viewPcbModalEl ? bootstrap.Modal.getOrCreateInstance(viewPcbModalEl) : null;
    document.querySelectorAll('.btn-view-pcb-service').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('viewPcbDate').textContent = this.getAttribute('data-date') || '--';
            document.getElementById('viewPcbRecorder').textContent = this.getAttribute('data-recorder') || '--';
            document.getElementById('viewPcbCategory').textContent = this.getAttribute('data-category') || '--';
            document.getElementById('viewPcbType').textContent = this.getAttribute('data-type') || '--';
            document.getElementById('viewPcbDiagnosis').textContent = this.getAttribute('data-diagnosis') || '--';
            document.getElementById('viewPcbGiven').textContent = this.getAttribute('data-given') || '--';
            document.getElementById('viewPcbReferred').textContent = this.getAttribute('data-referred') || '--';
            document.getElementById('viewPcbRemarks').textContent = this.getAttribute('data-remarks') || '--';

            if (viewPcbModal) viewPcbModal.show();
        });
    });

    // I. Edit PCB Service Modal Handler
    const editPcbModalEl = document.getElementById('editPcbServiceModal');
    const editPcbModal = editPcbModalEl ? bootstrap.Modal.getOrCreateInstance(editPcbModalEl) : null;
    document.querySelectorAll('.btn-edit-pcb-service').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            const form = document.getElementById('editPcbServiceForm');
            if (form) form.action = `<?= url('/pcb/service-log/') ?>${id}/update`;

            document.getElementById('editPcbCategory').value = this.getAttribute('data-category') || 'Diagnostic';
            document.getElementById('editPcbDate').value = this.getAttribute('data-date') || '';
            document.getElementById('editPcbType').value = this.getAttribute('data-type') || '';
            document.getElementById('editPcbDiagnosis').value = this.getAttribute('data-diagnosis') || '';
            
            const isGiven = this.getAttribute('data-given') === '1';
            document.getElementById('editPcbGiven').checked = isGiven;

            const isReferred = this.getAttribute('data-referred') === '1';
            document.getElementById('editPcbReferred').checked = isReferred;
            const refWrapper = document.getElementById('editReferredToWrapper');
            if (refWrapper) refWrapper.classList.toggle('d-none', !isReferred);
            document.getElementById('editPcbReferredTo').value = this.getAttribute('data-referred-to') || '';
            document.getElementById('editPcbRemarks').value = this.getAttribute('data-remarks') || '';

            if (editPcbModal) editPcbModal.show();
        });
    });

    // J. View Immunization Modal Handler
    const viewImmModalEl = document.getElementById('viewImmunizationModal');
    const viewImmModal = viewImmModalEl ? bootstrap.Modal.getOrCreateInstance(viewImmModalEl) : null;
    document.querySelectorAll('.btn-view-immunization').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('viewImmDate').textContent = this.getAttribute('data-date') || '--';
            document.getElementById('viewImmVaccinator').textContent = this.getAttribute('data-vaccinator') || '--';
            document.getElementById('viewImmVaccine').textContent = this.getAttribute('data-vaccine') || '--';
            document.getElementById('viewImmDose').textContent = this.getAttribute('data-dose') || '--';
            document.getElementById('viewImmSource').textContent = this.getAttribute('data-source') || '--';
            document.getElementById('viewImmStatus').textContent = this.getAttribute('data-status') || '--';
            document.getElementById('viewImmRemarks').textContent = this.getAttribute('data-remarks') || '--';

            if (viewImmModal) viewImmModal.show();
        });
    });

    // K. Edit Immunization Modal Handler
    const editImmModalEl = document.getElementById('editImmunizationModal');
    const editImmModal = editImmModalEl ? bootstrap.Modal.getOrCreateInstance(editImmModalEl) : null;
    document.querySelectorAll('.btn-edit-immunization').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            const form = document.getElementById('editImmunizationForm');
            if (form) form.action = `<?= url('/immunizations/') ?>${id}/update`;

            const vaccineSelect = document.getElementById('edit_imm_vaccine_name');
            if (vaccineSelect) vaccineSelect.value = this.getAttribute('data-vaccine') || '';

            document.getElementById('edit_imm_dose_number').value = this.getAttribute('data-dose') || '1';
            document.getElementById('edit_imm_administered_date').value = this.getAttribute('data-date') || '';
            document.getElementById('edit_imm_source').value = this.getAttribute('data-source') || 'Health Center';
            document.getElementById('edit_imm_documentation_status').value = this.getAttribute('data-status') || 'Administered';
            document.getElementById('edit_imm_remarks').value = this.getAttribute('data-remarks') || '';

            if (editImmModal) editImmModal.show();
        });
    });

    // L. Blood Pressure Physiological Sanity Check (Systolic must be strictly > Diastolic)
    function validateBloodPressure(sysInput, diaInput, event) {
        if (!sysInput || !diaInput) return true;
        const sysVal = parseInt(sysInput.value, 10);
        const diaVal = parseInt(diaInput.value, 10);
        if (!isNaN(sysVal) && !isNaN(diaVal) && sysVal > 0 && diaVal > 0) {
            if (sysVal <= diaVal) {
                event.preventDefault();
                const errMsg = `Physiological validation error: Systolic blood pressure (${sysVal} mmHg) must be strictly higher than Diastolic pressure (${diaVal} mmHg). Please verify the reading.`;
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Blood Pressure Reading',
                        text: errMsg,
                        confirmButtonColor: '#0d6efd'
                    });
                } else {
                    alert(errMsg);
                }
                sysInput.focus();
                return false;
            }
        }
        return true;
    }

    const addVitalsForm = document.getElementById('vitalsForm');
    if (addVitalsForm) {
        addVitalsForm.addEventListener('submit', function(e) {
            const sysInput = document.getElementById('bp_systolic');
            const diaInput = document.getElementById('bp_diastolic');
            validateBloodPressure(sysInput, diaInput, e);
        });
    }

    const editVitalsForm = document.getElementById('editVitalsForm');
    if (editVitalsForm) {
        editVitalsForm.addEventListener('submit', function(e) {
            const sysInput = document.getElementById('editVitalSystolic');
            const diaInput = document.getElementById('editVitalDiastolic');
            validateBloodPressure(sysInput, diaInput, e);
        });
    }

    // 12. 1-Click Clipboard Copy Handler for Overview Workstation
    document.addEventListener('click', function(e) {
        const copyBtn = e.target.closest('.copy-clipboard-btn');
        if (!copyBtn) return;
        const textToCopy = copyBtn.getAttribute('data-clipboard');
        if (!textToCopy) return;

        navigator.clipboard.writeText(textToCopy).then(() => {
            const originalHtml = copyBtn.innerHTML;
            copyBtn.innerHTML = '<i class="bi bi-check-lg text-success"></i>';
            copyBtn.setAttribute('title', 'Copied!');
            setTimeout(() => {
                copyBtn.innerHTML = originalHtml;
                copyBtn.setAttribute('title', copyBtn.getAttribute('aria-label') || 'Copy');
            }, 1500);
        }).catch(err => {
            console.error('Failed to copy to clipboard: ', err);
        });
    });

});
</script>

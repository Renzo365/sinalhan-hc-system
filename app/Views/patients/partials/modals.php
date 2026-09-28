<!-- ==========================================================================
   UNIVERSAL IMMUNIZATION MODAL (Any Patient)
   ========================================================================== -->
<div class="modal fade" id="recordImmunizationModal" tabindex="-1" aria-labelledby="recordImmunizationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="recordImmunizationModalLabel">
                    <i class="bi bi-shield-plus me-2"></i>Record Vaccine Dose
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="<?= url('/patients/' . $patient['id'] . '/immunizations/record') ?>" method="POST" id="singleImmunizationForm">
                <?= csrf_field() ?>
                <input type="hidden" name="redirect_to" value="<?= url('/patients/' . $patient['id'] . '#tab-immunizations') ?>">

                <div class="modal-body p-4 bg-white small">
                    <div class="mb-3">
                        <label for="vaccine_name" class="form-label fw-semibold text-secondary">Vaccine Name <span class="text-danger">*</span></label>
                        <select name="vaccine_name" class="form-select" required>
                            <option value="">-- Select Vaccine --</option>
                            <optgroup label="Routine Infant EPI">
                                <option value="BCG">BCG</option>
                                <option value="Hepatitis B">Hepatitis B</option>
                                <option value="Pentavalent">Pentavalent (DTP-HepB-Hib)</option>
                                <option value="OPV">Oral Polio Vaccine (OPV)</option>
                                <option value="IPV">Inactivated Polio (IPV)</option>
                                <option value="Rotavirus">Rotavirus</option>
                                <option value="PCV">Pneumococcal Conjugate (PCV)</option>
                                <option value="MCV">Measles / MMR (MCV)</option>
                            </optgroup>
                            <optgroup label="Adolescent & Adult Vaccines">
                                <option value="HPV">HPV (Human Papillomavirus)</option>
                                <option value="Tetanus Toxoid">Tetanus Toxoid (TT / Td)</option>
                                <option value="Influenza">Influenza (Flu)</option>
                                <option value="Pneumococcal Polysaccharide">Pneumococcal (PPV23 / Senior)</option>
                                <option value="COVID-19">COVID-19</option>
                                <option value="Hepatitis A">Hepatitis A</option>
                            </optgroup>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="dose_number" class="form-label fw-semibold text-secondary">Dose Number <span class="text-danger">*</span></label>
                            <input type="number" name="dose_number" class="form-control font-monospace" value="1" min="1" max="10" required>
                        </div>
                        <div class="col-6">
                            <label for="administered_date" class="form-label fw-semibold text-secondary">Administered Date <span class="text-danger">*</span></label>
                            <input type="date" name="administered_date" class="form-control  bg-white" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label for="source" class="form-label fw-semibold text-secondary">Source</label>
                                <select name="source" class="form-select">
                                    <option value="Health Center">Health Center</option>
                                    <option value="External">External facility</option>
                                    <option value="Patient Reported">Patient/parent reported</option>
                                    <option value="Unknown">Unknown</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label for="documentation_status" class="form-label fw-semibold text-secondary">Documentation status</label>
                                <select name="documentation_status" class="form-select">
                                    <option value="Administered">Administered</option>
                                    <option value="Reported">Reported</option>
                                    <option value="Unknown">Unknown</option>
                                </select>
                            </div>
                        </div>
                        <label for="remarks" class="form-label fw-semibold text-secondary">Remarks / Lot No. / Site</label>
                        <input type="text" name="remarks" class="form-control" placeholder="e.g. Lot #ABC-123, Left Deltoid, Bakuna Eskwela">
                    </div>
                </div>
                
                <div class="modal-footer bg-light py-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold">Record Immunization</button>
                </div>
            </form>
        </div>
    </div>
</div>

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
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="weight" class="form-label fw-semibold text-secondary small">Weight</label>
                            <div class="input-group">
                                <input type="number" name="weight" id="weight" class="form-control" placeholder="60" step="0.01" min="1" max="500">
                                <span class="input-group-text small">kg</span>
                            </div>
                        </div>

                        <!-- Height -->
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="height" class="form-label fw-semibold text-secondary small">Height</label>
                            <div class="input-group">
                                <input type="number" name="height" id="height" class="form-control" placeholder="165" step="0.1" min="30" max="300">
                                <span class="input-group-text small">cm</span>
                            </div>
                        </div>

                        <!-- BMI (Auto-calculated) -->
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="bmi" class="form-label fw-semibold text-secondary small">Calculated BMI</label>
                            <input type="text" name="bmi" id="bmi" class="form-control bg-light" placeholder="BMI auto-calc" readonly>
                        </div>

                        <!-- Waist Circumference -->
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="waist_circumference" class="form-label fw-semibold text-secondary small">Waist Circumference</label>
                            <div class="input-group">
                                <input type="number" name="waist_circumference" id="waist_circumference" class="form-control" placeholder="75" step="0.1" min="10" max="250">
                                <span class="input-group-text small">cm</span>
                            </div>
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
            <div class="modal-header bg-white py-3 border-bottom" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold text-dark" id="viewConsultationModalLabel">
                    <i class="bi bi-journal-medical text-primary me-2"></i>Consultation Details (SOAP Notes)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4 bg-white" id="consultationDetailsContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mt-2 small">Fetching consultation record...</p>
                </div>
            </div>
            
            <div class="modal-footer bg-light py-2.5 px-3 border-top d-flex justify-content-between align-items-center" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                <button type="button" class="btn btn-outline-dark btn-sm px-3 d-inline-flex align-items-center" onclick="window.print()">
                    <i class="bi bi-printer me-1.5"></i> Print Record
                </button>
                <div class="d-flex align-items-center gap-2" id="consultationModalFooterRight">
                    <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================================================
   VIEW VITAL SIGNS MODAL
   ========================================================================== -->
<div class="modal fade" id="viewVitalsModal" tabindex="-1" aria-labelledby="viewVitalsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="viewVitalsModalLabel">
                    <i class="bi bi-activity me-2"></i>Vital Signs Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white" id="vitalsModalContent">
                <!-- Recorded Meta -->
                <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
                    <div>
                        <span class="text-muted small d-block">Recorded At</span>
                        <strong class="text-dark" id="modalVitalDate">--</strong>
                    </div>
                    <div class="text-end">
                        <span class="text-muted small d-block">Recorded By</span>
                        <strong class="text-primary" id="modalVitalRecorder">--</strong>
                    </div>
                </div>

                <!-- Grid of Vitals Cards -->
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <span class="text-muted small d-block">Blood Pressure</span>
                            <span class="fs-6 fw-bold font-monospace text-dark" id="modalVitalBP">--</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <span class="text-muted small d-block">Heart / Pulse Rate</span>
                            <span class="fs-6 fw-bold text-dark"><span id="modalVitalPulse">--</span> <small class="text-muted fw-normal">bpm</small></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <span class="text-muted small d-block">Temperature</span>
                            <span class="fs-6 fw-bold text-dark"><span id="modalVitalTemp">--</span> <small class="text-muted fw-normal">°C</small></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <span class="text-muted small d-block">Respiratory Rate</span>
                            <span class="fs-6 fw-bold text-dark"><span id="modalVitalResp">--</span> <small class="text-muted fw-normal">cpm</small></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <span class="text-muted small d-block">Oxygen Saturation (SpO2)</span>
                            <span class="fs-6 fw-bold text-dark"><span id="modalVitalSpo2">--</span><small class="text-muted fw-normal">%</small></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <span class="text-muted small d-block">Waist Circumference</span>
                            <span class="fs-6 fw-bold text-dark"><span id="modalVitalWaist">--</span> <small class="text-muted fw-normal">cm</small></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <span class="text-muted small d-block">Weight & Height</span>
                            <span class="fs-7 fw-bold text-dark"><span id="modalVitalWeight">--</span> kg / <span id="modalVitalHeight">--</span> cm</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <span class="text-muted small d-block">BMI & Category</span>
                            <span class="fs-7 fw-bold" id="modalVitalBmi">--</span>
                        </div>
                    </div>
                </div>

                <!-- Clinical Notes / Symptoms -->
                <div class="card border rounded bg-white">
                    <div class="card-header bg-light py-1.5 px-3 small fw-bold text-secondary">
                        <i class="bi bi-journal-text me-1 text-primary"></i> Clinical Notes / Symptoms
                    </div>
                    <div class="card-body p-3 small text-dark" id="modalVitalNotes" style="white-space: pre-line; min-height: 50px;">
                        No symptoms or notes recorded.
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                <button type="button" class="btn btn-outline-secondary btn-sm px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================================================
   EDIT VITAL SIGNS MODAL
   ========================================================================== -->
<div class="modal fade" id="editVitalsModal" tabindex="-1" aria-labelledby="editVitalsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="editVitalsModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Edit Vital Signs
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editVitalsForm" method="POST" action="#" onsubmit="if(!this.getAttribute('action') || this.getAttribute('action') === '#' || this.getAttribute('action') === '') return false;">
                <?= csrf_field() ?>
                <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">

                <div class="modal-body p-4 bg-white">
                    <div class="text-muted small mb-3">
                        Patient: <strong><?= h($patient['last_name']) ?>, <?= h($patient['first_name']) ?></strong> &bull; DOB: <?= h($patient['dob']) ?>
                    </div>

                    <div class="row g-3">
                        <!-- Blood Pressure Systolic -->
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="editVitalSystolic" class="form-label fw-semibold text-secondary small">Blood Pressure - Systolic</label>
                            <div class="input-group">
                                <input type="number" name="bp_systolic" id="editVitalSystolic" class="form-control" placeholder="120" min="40" max="300">
                                <span class="input-group-text small">mmHg</span>
                            </div>
                        </div>

                        <!-- Blood Pressure Diastolic -->
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="editVitalDiastolic" class="form-label fw-semibold text-secondary small">Blood Pressure - Diastolic</label>
                            <div class="input-group">
                                <input type="number" name="bp_diastolic" id="editVitalDiastolic" class="form-control" placeholder="80" min="30" max="200">
                                <span class="input-group-text small">mmHg</span>
                            </div>
                        </div>

                        <!-- Heart Rate -->
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="editVitalPulse" class="form-label fw-semibold text-secondary small">Heart Rate / Pulse</label>
                            <div class="input-group">
                                <input type="number" name="heart_rate" id="editVitalPulse" class="form-control" placeholder="72" min="20" max="250">
                                <span class="input-group-text small">bpm</span>
                            </div>
                        </div>

                        <!-- Temperature -->
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="editVitalTemp" class="form-label fw-semibold text-secondary small">Body Temperature</label>
                            <div class="input-group">
                                <input type="number" name="temperature" id="editVitalTemp" class="form-control" placeholder="36.5" step="0.1" min="30" max="45">
                                <span class="input-group-text small">°C</span>
                            </div>
                        </div>

                        <!-- Respiratory Rate -->
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="editVitalResp" class="form-label fw-semibold text-secondary small">Respiratory Rate</label>
                            <div class="input-group">
                                <input type="number" name="respiratory_rate" id="editVitalResp" class="form-control" placeholder="18" min="5" max="80">
                                <span class="input-group-text small">cpm</span>
                            </div>
                        </div>

                        <!-- Oxygen Saturation -->
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="editVitalSpo2" class="form-label fw-semibold text-secondary small">Oxygen Saturation (SpO2)</label>
                            <div class="input-group">
                                <input type="number" name="oxygen_saturation" id="editVitalSpo2" class="form-control" placeholder="98" min="50" max="100">
                                <span class="input-group-text small">%</span>
                            </div>
                        </div>

                        <hr class="my-3 text-muted opacity-25">

                        <!-- Weight -->
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="editVitalWeight" class="form-label fw-semibold text-secondary small">Weight</label>
                            <div class="input-group">
                                <input type="number" name="weight" id="editVitalWeight" class="form-control" placeholder="60" step="0.01" min="1" max="500">
                                <span class="input-group-text small">kg</span>
                            </div>
                        </div>

                        <!-- Height -->
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="editVitalHeight" class="form-label fw-semibold text-secondary small">Height</label>
                            <div class="input-group">
                                <input type="number" name="height" id="editVitalHeight" class="form-control" placeholder="165" step="0.1" min="30" max="300">
                                <span class="input-group-text small">cm</span>
                            </div>
                        </div>

                        <!-- BMI (Auto-calculated) -->
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="editVitalBmi" class="form-label fw-semibold text-secondary small">Calculated BMI</label>
                            <input type="text" name="bmi" id="editVitalBmi" class="form-control bg-light" placeholder="BMI auto-calc" readonly>
                        </div>

                        <!-- Waist Circumference -->
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="editVitalWaist" class="form-label fw-semibold text-secondary small">Waist Circumference</label>
                            <div class="input-group">
                                <input type="number" name="waist_circumference" id="editVitalWaist" class="form-control" placeholder="75" step="0.1" min="10" max="250">
                                <span class="input-group-text small">cm</span>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="col-12">
                            <label for="editVitalNotes" class="form-label fw-semibold text-secondary small">Clinical Notes / Symptoms</label>
                            <textarea name="notes" id="editVitalNotes" rows="2" class="form-control" placeholder="Patient states feeling dizzy, etc."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light py-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==========================================================================
   EDIT PCB SERVICE ENCOUNTER MODAL
   ========================================================================== -->
<div class="modal fade" id="editPcbServiceModal" tabindex="-1" aria-labelledby="editPcbServiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="editPcbServiceModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Edit Diagnostic / PCB Service
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editPcbServiceForm" method="POST" action="#" onsubmit="if(!this.getAttribute('action') || this.getAttribute('action') === '#' || this.getAttribute('action') === '') return false;">
                <?= csrf_field() ?>
                <input type="hidden" name="service_year" value="<?= h($pcbYear ?? '') ?>">
                <div class="modal-body p-4 bg-white">
                    <div class="row g-3">
                        <!-- Section Category -->
                        <div class="col-12">
                            <label for="editPcbCategory" class="form-label fw-semibold text-secondary small">Service Section / Category <span class="text-danger">*</span></label>
                            <select name="service_category" id="editPcbCategory" class="form-select" required>
                                <option value="Diagnostic">Diagnostic Examination Services</option>
                                <option value="PCB1">Other PCB1 Services</option>
                                <option value="Other">Other Services</option>
                            </select>
                        </div>

                        <!-- Service Date -->
                        <div class="col-12 col-md-6">
                            <label for="editPcbDate" class="form-label fw-semibold text-secondary small">Encounter Date <span class="text-danger">*</span></label>
                            <input type="date" name="service_date" id="editPcbDate" class="form-control" required>
                        </div>

                        <!-- Diagnosis -->
                        <div class="col-12 col-md-6">
                            <label for="editPcbDiagnosis" class="form-label fw-semibold text-secondary small">Clinical Diagnosis / Indication</label>
                            <input type="text" name="diagnosis" id="editPcbDiagnosis" class="form-control" placeholder="e.g. Hypertension, Routine PCB" maxlength="255">
                        </div>

                        <!-- Service / Diagnostic Test Name -->
                        <div class="col-12">
                            <label for="editPcbType" class="form-label fw-semibold text-secondary small">Service / Test Type <span class="text-danger">*</span></label>
                            <input type="text" name="service_type" id="editPcbType" class="form-control" list="commonPcbServices" placeholder="e.g. Complete Blood Count (CBC)" required>
                        </div>

                        <!-- Given / Referred Checkboxes -->
                        <div class="col-12 border-top pt-3">
                            <div class="d-flex flex-wrap gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="status_given" id="editPcbGiven" value="1">
                                    <label class="form-check-label fw-semibold text-dark small" for="editPcbGiven">
                                        <i class="bi bi-check-circle text-success me-1"></i> Given In-Clinic
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="status_referred" id="editPcbReferred" value="1" onchange="document.getElementById('editReferredToWrapper').classList.toggle('d-none', !this.checked)">
                                    <label class="form-check-label fw-semibold text-dark small" for="editPcbReferred">
                                        <i class="bi bi-arrow-up-right-circle text-warning me-1"></i> Referred
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Referred To Facility -->
                        <div class="col-12 d-none" id="editReferredToWrapper">
                            <label for="editPcbReferredTo" class="form-label fw-semibold text-secondary small">Referred Facility / Specialist</label>
                            <input type="text" name="referred_to" id="editPcbReferredTo" class="form-control" placeholder="e.g. Santa Rosa Community Hospital, City Health Office">
                        </div>

                        <!-- Remarks -->
                        <div class="col-12">
                            <label for="editPcbRemarks" class="form-label fw-semibold text-secondary small">Remarks / Notes</label>
                            <textarea name="remarks" id="editPcbRemarks" class="form-control" rows="2" placeholder="e.g. Normal laboratory findings, sample sent to CHO laboratory..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==========================================================================
   VIEW PCB SERVICE ENCOUNTER MODAL
   ========================================================================== -->
<div class="modal fade" id="viewPcbServiceModal" tabindex="-1" aria-labelledby="viewPcbServiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="viewPcbServiceModalLabel">
                    <i class="bi bi-journal-medical me-2"></i>PCB Encounter Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white">
                <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
                    <div>
                        <span class="text-muted small d-block">Service Date</span>
                        <strong class="text-dark" id="viewPcbDate">--</strong>
                    </div>
                    <div class="text-end">
                        <span class="text-muted small d-block">Recorded By</span>
                        <strong class="text-primary" id="viewPcbRecorder">--</strong>
                    </div>
                </div>
                <div class="mb-3">
                    <span class="text-muted small d-block mb-1">Service Category & Type</span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle me-2" id="viewPcbCategory">--</span>
                    <span class="fw-bold text-dark fs-6" id="viewPcbType">--</span>
                </div>
                <div class="mb-3 p-3 bg-light rounded border">
                    <span class="text-muted small d-block mb-1">Clinical Diagnosis / Indication</span>
                    <div class="text-dark fw-medium" id="viewPcbDiagnosis">--</div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <span class="text-muted small d-block">Given Status</span>
                            <span class="fw-semibold text-dark" id="viewPcbGiven">--</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <span class="text-muted small d-block">Referred Status</span>
                            <span class="fw-semibold text-dark" id="viewPcbReferred">--</span>
                        </div>
                    </div>
                </div>
                <div class="p-3 bg-light rounded border">
                    <span class="text-muted small d-block mb-1">Remarks / Clinical Notes</span>
                    <div class="text-secondary small" id="viewPcbRemarks">--</div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                <button type="button" class="btn btn-outline-secondary btn-sm px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================================================
   VIEW IMMUNIZATION DETAILS MODAL
   ========================================================================== -->
<div class="modal fade" id="viewImmunizationModal" tabindex="-1" aria-labelledby="viewImmunizationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="viewImmunizationModalLabel">
                    <i class="bi bi-shield-check me-2"></i>Immunization Record Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white">
                <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
                    <div>
                        <span class="text-muted small d-block">Administered Date</span>
                        <strong class="text-dark" id="viewImmDate">--</strong>
                    </div>
                    <div class="text-end">
                        <span class="text-muted small d-block">Vaccinator / Clinician</span>
                        <strong class="text-primary" id="viewImmVaccinator">--</strong>
                    </div>
                </div>
                <div class="p-3 bg-light rounded border mb-3">
                    <span class="text-muted small d-block">Vaccine & Schedule</span>
                    <div class="fs-5 fw-bold text-dark d-flex align-items-center mt-1">
                        <i class="bi bi-shield-fill-check text-success me-2"></i>
                        <span id="viewImmVaccine">--</span>
                        <span class="badge bg-primary text-white ms-2 fs-7" id="viewImmDose">--</span>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <span class="text-muted small d-block">Documentation Source</span>
                            <span class="fw-semibold text-dark" id="viewImmSource">--</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <span class="text-muted small d-block">Status</span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle" id="viewImmStatus">Administered</span>
                        </div>
                    </div>
                </div>
                <div class="p-3 bg-light rounded border">
                    <span class="text-muted small d-block mb-1">Clinical Remarks / Batch Notes</span>
                    <div class="text-secondary small" id="viewImmRemarks">--</div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                <button type="button" class="btn btn-outline-secondary btn-sm px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================================================
   MODAL: EDIT IMMUNIZATION RECORD
   ========================================================================== -->
<div class="modal fade" id="editImmunizationModal" tabindex="-1" aria-labelledby="editImmunizationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="editImmunizationModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Edit Vaccine Dose
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="#" method="POST" id="editImmunizationForm" onsubmit="if(!this.getAttribute('action') || this.getAttribute('action') === '#' || this.getAttribute('action') === '') return false;">
                <?= csrf_field() ?>
                <input type="hidden" name="redirect_to" value="<?= url('/patients/' . $patient['id'] . '#tab-immunizations') ?>">

                <div class="modal-body p-4 bg-white small">
                    <div class="mb-3">
                        <label for="edit_imm_vaccine_name" class="form-label fw-semibold text-secondary">Vaccine Name <span class="text-danger">*</span></label>
                        <select name="vaccine_name" id="edit_imm_vaccine_name" class="form-select" required>
                            <option value="">-- Select Vaccine --</option>
                            <optgroup label="Routine Infant EPI">
                                <option value="BCG">BCG</option>
                                <option value="Hepatitis B">Hepatitis B</option>
                                <option value="Pentavalent">Pentavalent (DTP-HepB-Hib)</option>
                                <option value="OPV">Oral Polio Vaccine (OPV)</option>
                                <option value="IPV">Inactivated Polio (IPV)</option>
                                <option value="Rotavirus">Rotavirus</option>
                                <option value="PCV">Pneumococcal Conjugate (PCV)</option>
                                <option value="MCV">Measles / MMR (MCV)</option>
                            </optgroup>
                            <optgroup label="Adolescent & Adult Vaccines">
                                <option value="HPV">HPV (Human Papillomavirus)</option>
                                <option value="Tetanus Toxoid">Tetanus Toxoid (TT / Td)</option>
                                <option value="Influenza">Influenza (Flu)</option>
                                <option value="Pneumococcal Polysaccharide">Pneumococcal (PPV23 / Senior)</option>
                                <option value="COVID-19">COVID-19</option>
                                <option value="Hepatitis A">Hepatitis A</option>
                            </optgroup>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="edit_imm_dose_number" class="form-label fw-semibold text-secondary">Dose Number <span class="text-danger">*</span></label>
                            <input type="number" name="dose_number" id="edit_imm_dose_number" class="form-control font-monospace" min="1" max="10" required>
                        </div>
                        <div class="col-6">
                            <label for="edit_imm_administered_date" class="form-label fw-semibold text-secondary">Administered Date <span class="text-danger">*</span></label>
                            <input type="date" name="administered_date" id="edit_imm_administered_date" class="form-control bg-white" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label for="edit_imm_source" class="form-label fw-semibold text-secondary">Source</label>
                                <select name="source" id="edit_imm_source" class="form-select">
                                    <option value="Health Center">Health Center</option>
                                    <option value="External">External facility</option>
                                    <option value="Patient Reported">Patient/parent reported</option>
                                    <option value="Unknown">Unknown</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label for="edit_imm_documentation_status" class="form-label fw-semibold text-secondary">Documentation status</label>
                                <select name="documentation_status" id="edit_imm_documentation_status" class="form-select">
                                    <option value="Administered">Administered</option>
                                    <option value="Reported">Reported</option>
                                    <option value="Unknown">Unknown</option>
                                </select>
                            </div>
                        </div>
                        <label for="edit_imm_remarks" class="form-label fw-semibold text-secondary">Remarks / Lot No. / Site</label>
                        <input type="text" name="remarks" id="edit_imm_remarks" class="form-control" placeholder="e.g. Lot #ABC-123, Left Deltoid, Bakuna Eskwela">
                    </div>
                </div>

                <div class="modal-footer bg-light py-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==========================================================================
   HIDDEN WORKSTATION ACTION FORMS (CSRF-Protected)
   ========================================================================== -->
<form id="archiveConsultationForm" method="POST" class="d-none">
    <?= csrf_field() ?>
    <input type="hidden" name="reason" id="archiveConsultationReasonInput">
</form>

<form id="deleteVitalForm" method="POST" class="d-none">
    <?= csrf_field() ?>
</form>

<form id="deleteImmunizationForm" method="POST" class="d-none">
    <?= csrf_field() ?>
</form>

<form id="deletePcbServiceForm" method="POST" class="d-none">
    <?= csrf_field() ?>
    <input type="hidden" name="service_year" value="<?= h($pcbYear ?? '') ?>">
</form>



<form id="cancelAppointmentForm" method="POST" class="d-none">
    <?= csrf_field() ?>
    <input type="hidden" name="status" value="Cancelled">
</form>

<?php if (is_admin()): ?>
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
                        <strong>Warning:</strong> Archiving will hide this patient from active directories, daily queues, and scheduling lists. Only administrators can view and restore archived records.
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
   CHECK-IN TO QUEUE MODAL (Workstation Header Action)
   ========================================================================== -->
<div class="modal fade" id="enqueuePatientModal" tabindex="-1" aria-labelledby="enqueuePatientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="enqueuePatientModalLabel">
                    <i class="bi bi-person-check-fill me-2"></i>Check-in Patient to Today's Queue
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= url('/queue') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
                <input type="hidden" name="redirect_to" value="<?= url('/patients/' . $patient['id'] . '#tab-appointments') ?>">
                <div class="modal-body p-4 bg-white">
                    <div class="alert alert-info py-2 px-3 small mb-3">
                        <i class="bi bi-info-circle me-1"></i>Checking in <strong><?= h($fullNameFormatted) ?></strong> (<?= h($patient['patient_no']) ?>) to the active health center queue for today (<?= date('F d, Y') ?>).
                    </div>
                    <div class="mb-3">
                        <label for="modal_queue_service_type" class="form-label fw-semibold text-secondary small">Service / Consultation Category <span class="text-danger">*</span></label>
                        <select name="service_type" id="modal_queue_service_type" class="form-select" required>
                            <option value="General OPD" selected>General OPD / Adult Consultation</option>
                            <option value="Prenatal Care">Prenatal Care</option>
                            <option value="Well Baby Immunization">Well Baby Immunization / Pediatric</option>
                            <option value="Senior Care">Senior Care</option>
                            <option value="Family Planning">Family Planning</option>
                            <option value="Dental Care">Dental Care</option>
                            <option value="NCD / Hypertension">NCD / Hypertension & Diabetes</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-4 border-top" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold">
                        <i class="bi bi-check-lg me-1"></i> Issue Queue Number
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==========================================================================
   RECORD DIAGNOSTIC / PCB SERVICE MODAL (PAGE 3)
   ========================================================================== -->
<div class="modal fade" id="recordPcbServiceModal" tabindex="-1" aria-labelledby="recordPcbServiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="recordPcbServiceModalLabel">
                    <i class="bi bi-journal-plus me-2"></i>Record Diagnostic / PCB Service
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="<?= url('/patients/' . $patient['id'] . '/pcb/service-log') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="service_year" value="<?= h($pcbYear ?? '') ?>">
                <div class="modal-body p-4 bg-white">
                    <div class="row g-3">
                        <!-- Section Category -->
                        <div class="col-12">
                            <label for="modal_service_category" class="form-label fw-semibold text-secondary small">Service Section / Category <span class="text-danger">*</span></label>
                            <select name="service_category" id="modal_service_category" class="form-select" required>
                                <option value="Diagnostic">Diagnostic Examination Services</option>
                                <option value="PCB1">Other PCB1 Services</option>
                                <option value="Other">Other Services</option>
                            </select>
                        </div>

                        <!-- Service Date -->
                        <div class="col-12 col-md-6">
                            <label for="modal_service_date" class="form-label fw-semibold text-secondary small">Encounter Date <span class="text-danger">*</span></label>
                            <input type="date" name="service_date" id="modal_service_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <!-- Diagnosis -->
                        <div class="col-12 col-md-6">
                            <label for="modal_diagnosis" class="form-label fw-semibold text-secondary small">Clinical Diagnosis / Indication</label>
                            <input type="text" name="diagnosis" id="modal_diagnosis" class="form-control" placeholder="e.g. Hypertension, Routine PCB" maxlength="255">
                        </div>

                        <!-- Service / Diagnostic Test Name -->
                        <div class="col-12">
                            <label for="modal_service_type" class="form-label fw-semibold text-secondary small">Service / Test Type <span class="text-danger">*</span></label>
                            <input type="text" name="service_type" id="modal_service_type" class="form-control" list="commonPcbServices" placeholder="e.g. Complete Blood Count (CBC)" required>
                            <datalist id="commonPcbServices">
                                <option value="Complete Blood Count (CBC)">
                                <option value="Urinalysis">
                                <option value="Fecalysis">
                                <option value="Sputum Microscopy">
                                <option value="Fasting Blood Sugar (FBS)">
                                <option value="Lipid Profile">
                                <option value="Chest X-Ray">
                                <option value="Visual Inspection with Acetic Acid (VIA)">
                                <option value="Clinical Breast Examination">
                                <option value="Oral Rehydration & Counseling">
                                <option value="Family Planning Counseling">
                                <option value="Smoking Cessation Counseling">
                            </datalist>
                        </div>

                        <!-- Given / Referred Checkboxes -->
                        <div class="col-12 border-top pt-3">
                            <div class="d-flex flex-wrap gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="status_given" id="modal_status_given" value="1" checked>
                                    <label class="form-check-label fw-semibold text-dark small" for="modal_status_given">
                                        <i class="bi bi-check-circle text-success me-1"></i> Given In-Clinic
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="status_referred" id="modal_status_referred" value="1" onchange="document.getElementById('referredToWrapper').classList.toggle('d-none', !this.checked)">
                                    <label class="form-check-label fw-semibold text-dark small" for="modal_status_referred">
                                        <i class="bi bi-arrow-up-right-circle text-warning me-1"></i> Referred
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Referred To Facility -->
                        <div class="col-12 d-none" id="referredToWrapper">
                            <label for="modal_referred_to" class="form-label fw-semibold text-secondary small">Referred Facility / Specialist</label>
                            <input type="text" name="referred_to" id="modal_referred_to" class="form-control" placeholder="e.g. Santa Rosa Community Hospital, City Health Office">
                        </div>

                        <!-- Remarks -->
                        <div class="col-12">
                            <label for="modal_remarks" class="form-label fw-semibold text-secondary small">Remarks / Notes</label>
                            <textarea name="remarks" id="modal_remarks" class="form-control" rows="2" placeholder="e.g. Normal laboratory findings, sample sent to CHO laboratory..."></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-light py-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-medium">Save Service Encounter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==========================================================================
   UPDATE OBLIGATED PREVENTIVE SERVICES MODAL (PAGE 3)
   ========================================================================== -->
<div class="modal fade" id="obligatedEditModal" tabindex="-1" aria-labelledby="obligatedEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="obligatedEditModalLabel">
                    <i class="bi bi-calendar-check me-2"></i>Update Obligated Preventive Services (<?= $pcbYear ?>)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="<?= url('/patients/' . $patient['id'] . '/pcb/obligated') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="service_year" value="<?= $pcbYear ?>">
                
                <div class="modal-body p-4 bg-white">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold text-secondary small mb-1">Hypertension Classification for BP Frequency:</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="is_hypertensive" id="modal_is_htn_no" value="0" <?= empty($pcbObligated['is_hypertensive']) ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="modal_is_htn_no">
                                        <strong>Non-Hypertensive</strong> (Frequency: Once a year)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="is_hypertensive" id="modal_is_htn_yes" value="1" <?= !empty($pcbObligated['is_hypertensive']) ? 'checked' : '' ?>>
                                    <label class="form-check-label small text-danger" for="modal_is_htn_yes">
                                        <strong>Hypertensive</strong> (Frequency: Once a month / quarterly review)
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Row 1: BP Measurements -->
                        <div class="col-12">
                            <h6 class="small fw-bold text-dark mb-1">1. BP Measurements (Dates Performed)</h6>
                            <div class="row g-2">
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-muted mb-1">1st Qtr (Jan-Mar)</label>
                                    <input type="date" name="bp_q1" class="form-control form-control-sm" value="<?= h($pcbObligated['bp_q1'] ?? '') ?>">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-muted mb-1">2nd Qtr (Apr-Jun)</label>
                                    <input type="date" name="bp_q2" class="form-control form-control-sm" value="<?= h($pcbObligated['bp_q2'] ?? '') ?>">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-muted mb-1">3rd Qtr (Jul-Sep)</label>
                                    <input type="date" name="bp_q3" class="form-control form-control-sm" value="<?= h($pcbObligated['bp_q3'] ?? '') ?>">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-muted mb-1">4th Qtr (Oct-Dec)</label>
                                    <input type="date" name="bp_q4" class="form-control form-control-sm" value="<?= h($pcbObligated['bp_q4'] ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Row 2: Periodic Clinical Breast Examination -->
                        <div class="col-12">
                            <h6 class="small fw-bold text-dark mb-1">2. Periodic Clinical Breast Examination (Dates Performed)</h6>
                            <div class="row g-2">
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-muted mb-1">1st Qtr</label>
                                    <input type="date" name="cbe_q1" class="form-control form-control-sm" value="<?= h($pcbObligated['cbe_q1'] ?? '') ?>">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-muted mb-1">2nd Qtr</label>
                                    <input type="date" name="cbe_q2" class="form-control form-control-sm" value="<?= h($pcbObligated['cbe_q2'] ?? '') ?>">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-muted mb-1">3rd Qtr</label>
                                    <input type="date" name="cbe_q3" class="form-control form-control-sm" value="<?= h($pcbObligated['cbe_q3'] ?? '') ?>">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-muted mb-1">4th Qtr</label>
                                    <input type="date" name="cbe_q4" class="form-control form-control-sm" value="<?= h($pcbObligated['cbe_q4'] ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Row 3: Visual Inspection with Acetic Acid (Females Only) -->
                        <?php if ($isFemale): ?>
                            <div class="col-12">
                                <h6 class="small fw-bold text-dark mb-1">3. Visual Inspection with Acetic Acid / VIA (Dates Performed)</h6>
                                <div class="row g-2">
                                    <div class="col-6 col-md-3">
                                        <label class="form-label small text-muted mb-1">1st Qtr</label>
                                        <input type="date" name="via_q1" class="form-control form-control-sm" value="<?= h($pcbObligated['via_q1'] ?? '') ?>">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label small text-muted mb-1">2nd Qtr</label>
                                        <input type="date" name="via_q2" class="form-control form-control-sm" value="<?= h($pcbObligated['via_q2'] ?? '') ?>">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label small text-muted mb-1">3rd Qtr</label>
                                        <input type="date" name="via_q3" class="form-control form-control-sm" value="<?= h($pcbObligated['via_q3'] ?? '') ?>">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label small text-muted mb-1">4th Qtr</label>
                                        <input type="date" name="via_q4" class="form-control form-control-sm" value="<?= h($pcbObligated['via_q4'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="col-12">
                            <label class="form-label small text-muted mb-1">Clinical Remarks / Compliance Notes</label>
                            <input type="text" name="remarks" class="form-control form-control-sm" placeholder="e.g. Regular compliance, hypertensive medications prescribed..." value="<?= h($pcbObligated['remarks'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light py-3 border-0" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-medium">Save Obligated Dates</button>
                </div>
            </form>
        </div>
    </div>
</div>

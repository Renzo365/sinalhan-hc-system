import sys

file_path = r'c:\xampp\htdocs\sinalhan-hc-system\app\Views\patients\show.php'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. PCB service logs variable
old_pcb_var = '''                                                        <?php 
                                                            $canManagePcb = is_admin() || ((int)($_SESSION['user_id'] ?? 0) === (int)($log['recorded_by'] ?? 0));
                                                        ?>'''
new_pcb_var = '''                                                        <?php 
                                                            $canDeletePcb = is_admin();
                                                        ?>'''
content = content.replace(old_pcb_var, new_pcb_var)

# PCB buttons
old_pcb_btn = '''                                                            <?php if ($canManagePcb): ?>
                                                                <button type="button" class="btn btn-sm btn-outline-secondary border-0 p-1 btn-edit-pcb-service"
                                                                    data-id="<?= $log['id'] ?>"
                                                                    data-date="<?= h($log['service_date']) ?>"
                                                                    data-category="<?= h($log['service_category']) ?>"
                                                                    data-type="<?= h($log['service_type']) ?>"
                                                                    data-diagnosis="<?= h($log['diagnosis'] ?? '') ?>"
                                                                    data-given="<?= !empty($log['status_given']) ? '1' : '0' ?>"
                                                                    data-referred="<?= !empty($log['status_referred']) ? '1' : '0' ?>"
                                                                    data-referred-to="<?= h($log['referred_to'] ?? '') ?>"
                                                                    data-remarks="<?= h($log['remarks'] ?? '') ?>"
                                                                    title="Edit Encounter">
                                                                    <i class="bi bi-pencil-square fs-6"></i>
                                                                </button>
                                                                <form action="<?= url('/pcb/service-log/' . $log['id'] . '/delete') ?>" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this <?= h($log['service_type']) ?> encounter record?');">
                                                                    <?= csrf_field() ?>
                                                                    <button type="submit" class="btn btn-sm btn-outline-danger border-0 p-1" title="Delete Entry">
                                                                        <i class="bi bi-trash fs-6"></i>
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>'''

new_pcb_btn = '''                                                                <button type="button" class="btn btn-sm btn-outline-secondary border-0 p-1 btn-edit-pcb-service"
                                                                    data-id="<?= $log['id'] ?>"
                                                                    data-date="<?= h($log['service_date']) ?>"
                                                                    data-category="<?= h($log['service_category']) ?>"
                                                                    data-type="<?= h($log['service_type']) ?>"
                                                                    data-diagnosis="<?= h($log['diagnosis'] ?? '') ?>"
                                                                    data-given="<?= !empty($log['status_given']) ? '1' : '0' ?>"
                                                                    data-referred="<?= !empty($log['status_referred']) ? '1' : '0' ?>"
                                                                    data-referred-to="<?= h($log['referred_to'] ?? '') ?>"
                                                                    data-remarks="<?= h($log['remarks'] ?? '') ?>"
                                                                    title="Edit Encounter">
                                                                    <i class="bi bi-pencil-square fs-6"></i>
                                                                </button>
                                                            <?php if ($canDeletePcb): ?>
                                                                <form action="<?= url('/pcb/service-log/' . $log['id'] . '/delete') ?>" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this <?= h($log['service_type']) ?> encounter record?');">
                                                                    <?= csrf_field() ?>
                                                                    <button type="submit" class="btn btn-sm btn-outline-danger border-0 p-1" title="Delete Entry">
                                                                        <i class="bi bi-trash fs-6"></i>
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>'''
content = content.replace(old_pcb_btn, new_pcb_btn)

# Consultations
old_cons = '''                                        <?php foreach ($consultationsHistory as $c): 
                                            $canArchiveConsultation = is_admin() || $curUserId === (int)($c['created_by'] ?? 0) || $curUserId === (int)($c['consulted_by'] ?? 0);
                                            $canEditRow = $canArchiveConsultation && ($c['status'] !== 'Cancelled');'''

new_cons = '''                                        <?php foreach ($consultationsHistory as $c): 
                                            $canArchiveConsultation = is_admin();
                                            $canEditRow = ($c['status'] !== 'Cancelled');'''
content = content.replace(old_cons, new_cons)

# Vitals var
old_vital_var = '''                                        <?php foreach ($vitalsHistory as $v): 
                                            $canManageVital = is_admin() || $curUserId === (int)($v['recorded_by'] ?? 0);
                                        ?>'''
new_vital_var = '''                                        <?php foreach ($vitalsHistory as $v): 
                                            $canDeleteVital = is_admin();
                                        ?>'''
content = content.replace(old_vital_var, new_vital_var)


# Vitals btn
old_vital_btn = '''                                                        <?php if ($canManageVital): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-secondary border-0 p-1 btn-edit-vitals"
                                                                data-id="<?= $v['id'] ?>"
                                                                data-bp-systolic="<?= h($v['bp_systolic'] ?? '') ?>"
                                                                data-bp-diastolic="<?= h($v['bp_diastolic'] ?? '') ?>"
                                                                data-pulse="<?= h($v['heart_rate'] ?? '') ?>"
                                                                data-temp="<?= h($v['temperature'] ?? '') ?>"
                                                                data-resp="<?= h($v['respiratory_rate'] ?? '') ?>"
                                                                data-spo2="<?= h($v['oxygen_saturation'] ?? '') ?>"
                                                                data-weight="<?= h($v['weight'] ?? '') ?>"
                                                                data-height="<?= h($v['height'] ?? '') ?>"
                                                                data-waist="<?= h($v['waist_circumference'] ?? '') ?>"
                                                                data-notes="<?= h($v['notes'] ?? '') ?>"
                                                                title="Edit Vital Signs">
                                                                <i class="bi bi-pencil-square fs-6"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-danger border-0 p-1 btn-delete-vital" data-id="<?= $v['id'] ?>" title="Delete Vital Signs">
                                                                <i class="bi bi-trash fs-6"></i>
                                                            </button>
                                                        <?php endif; ?>'''

new_vital_btn = '''                                                            <button type="button" class="btn btn-sm btn-outline-secondary border-0 p-1 btn-edit-vitals"
                                                                data-id="<?= $v['id'] ?>"
                                                                data-bp-systolic="<?= h($v['bp_systolic'] ?? '') ?>"
                                                                data-bp-diastolic="<?= h($v['bp_diastolic'] ?? '') ?>"
                                                                data-pulse="<?= h($v['heart_rate'] ?? '') ?>"
                                                                data-temp="<?= h($v['temperature'] ?? '') ?>"
                                                                data-resp="<?= h($v['respiratory_rate'] ?? '') ?>"
                                                                data-spo2="<?= h($v['oxygen_saturation'] ?? '') ?>"
                                                                data-weight="<?= h($v['weight'] ?? '') ?>"
                                                                data-height="<?= h($v['height'] ?? '') ?>"
                                                                data-waist="<?= h($v['waist_circumference'] ?? '') ?>"
                                                                data-notes="<?= h($v['notes'] ?? '') ?>"
                                                                title="Edit Vital Signs">
                                                                <i class="bi bi-pencil-square fs-6"></i>
                                                            </button>
                                                        <?php if ($canDeleteVital): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-danger border-0 p-1 btn-delete-vital" data-id="<?= $v['id'] ?>" title="Delete Vital Signs">
                                                                <i class="bi bi-trash fs-6"></i>
                                                            </button>
                                                        <?php endif; ?>'''
content = content.replace(old_vital_btn, new_vital_btn)

# Imm var
old_imm_var = '''                                        <?php foreach ($patientImmunizations as $imm): 
                                            $canDeleteImm = is_admin() || $curUserId === (int)($imm['administered_by'] ?? 0);
                                        ?>'''
new_imm_var = '''                                        <?php foreach ($patientImmunizations as $imm): 
                                            $canDeleteImm = is_admin();
                                        ?>'''
content = content.replace(old_imm_var, new_imm_var)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)
print('Done!')

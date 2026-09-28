<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AuditLog;
use PDO;

class ReportController extends Controller {
    /**
     * Display reports interface and generate filtered listings.
     */
    public function index() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Default to current month
        $defaultFrom = date('Y-m-01');
        $defaultTo = date('Y-m-d');

        $type = $_GET['type'] ?? '';
        $dateFrom = $_GET['date_from'] ?? $defaultFrom;
        $dateTo = $_GET['date_to'] ?? $defaultTo;
        $allTime = !empty($_GET['all_time']);
        [$dateFrom, $dateTo] = $this->normalizeDateRange($dateFrom, $dateTo);

        $results = [];
        $metrics = [];
        if (!empty($type)) {
            $results = $this->queryReportData($type, $dateFrom, $dateTo, $allTime);
            $metrics = $this->computeReportMetrics($type, $results);
            $rangeText = $allTime ? 'All Time (Cumulative Master Registry)' : "from {$dateFrom} to {$dateTo}";
            AuditLog::log('REPORT_GENERATED', 'Reports', "Generated report type: {$type} {$rangeText}");
        }

        $this->view('reports/index', [
            'type' => $type,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'allTime' => $allTime,
            'results' => $results,
            'metrics' => $metrics
        ]);
    }

    /**
     * Export report data directly as CSV.
     */
    public function export() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $type = $_GET['type'] ?? '';
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        $allTime = !empty($_GET['all_time']);
        [$dateFrom, $dateTo] = $this->normalizeDateRange($dateFrom, $dateTo);

        if (empty($type)) {
            $_SESSION['error_message'] = 'Report type is required for CSV export.';
            $this->redirect('/reports');
            return;
        }

        $data = $this->queryReportData($type, $dateFrom, $dateTo, $allTime);
        $rangeText = $allTime ? 'All Time' : "from {$dateFrom} to {$dateTo}";
        AuditLog::log('REPORT_EXPORTED', 'Reports', "Exported report type: {$type} as CSV {$rangeText}");

        $filename = "report_{$type}_" . date('Ymd_His') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');

        // Write UTF-8 BOM so Microsoft Excel correctly displays ñ, Ñ, °C, and other UTF-8 characters
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Column headers and mapping
        switch ($type) {
            case 'daily_visits':
                fputcsv($output, ['Queue Date', 'Queue No.', 'Patient ID', 'Patient Name', 'Service Type', 'Time In', 'Time Called', 'Time Completed', 'Status']);
                foreach ($data as $row) {
                    fputcsv($output, [
                        $row['queue_date'],
                        sprintf('%03d', $row['queue_no']),
                        $row['patient_no'],
                        "{$row['patient_last']}, {$row['patient_first']}",
                        $row['service_type'] ?: 'General OPD',
                        $row['time_in'],
                        $row['time_called'] ?: '-',
                        $row['time_completed'] ?: '-',
                        $row['status']
                    ]);
                }
                break;
                
            case 'consultations':
                fputcsv($output, ['Date & Time', 'Patient ID', 'Patient Name', 'Assessment (Diagnosis)', 'Subjective (Complaint)', 'Clinician']);
                foreach ($data as $row) {
                    fputcsv($output, [
                        $row['consulted_at'],
                        $row['patient_no'],
                        "{$row['patient_last']}, {$row['patient_first']}",
                        $row['assessment'],
                        $row['subjective'],
                        $row['clinician_name']
                    ]);
                }
                break;
                
            case 'registrations':
                fputcsv($output, ['Reg Date', 'Patient ID', 'Last Name', 'First Name', 'Birth Date', 'Age', 'Sex', 'Barangay', 'Contact No.']);
                foreach ($data as $row) {
                    fputcsv($output, [
                        date('Y-m-d', strtotime($row['created_at'])),
                        $row['patient_no'],
                        $row['last_name'],
                        $row['first_name'],
                        $row['dob'],
                        $row['age'] . ' yrs',
                        $row['sex'],
                        $row['barangay'],
                        $row['contact_no'] ?: '-'
                    ]);
                }
                break;
                
            case 'queue_summary':
                fputcsv($output, ['Date', 'Total Enqueued', 'Completed Visits', 'Cancelled Tickets', 'Waiting Tickets', 'Called/Serving']);
                foreach ($data as $row) {
                    fputcsv($output, [
                        $row['date'],
                        $row['total'],
                        $row['completed'],
                        $row['cancelled'],
                        $row['waiting'],
                        $row['called_serving']
                    ]);
                }
                break;
                
            case 'vitals':
                fputcsv($output, ['Recorded Date', 'Patient ID', 'Patient Name', 'BP (Systolic/Diastolic)', 'Pulse (bpm)', 'Temp (°C)', 'Resp (cpm)', 'SpO2 (%)', 'BMI', 'Recorded By']);
                foreach ($data as $row) {
                    fputcsv($output, [
                        $row['recorded_at'],
                        $row['patient_no'],
                        "{$row['patient_last']}, {$row['patient_first']}",
                        ($row['bp_systolic'] && $row['bp_diastolic']) ? "{$row['bp_systolic']}/{$row['bp_diastolic']}" : '-',
                        $row['heart_rate'] ?: '-',
                        $row['temperature'] ? number_format((float)$row['temperature'], 1) : '-',
                        $row['respiratory_rate'] ?: '-',
                        $row['oxygen_saturation'] ? "{$row['oxygen_saturation']}%" : '-',
                        $row['bmi'] ?: '-',
                        $row['recorded_by_name']
                    ]);
                }
                break;

            case 'maternal_health':
                fputcsv($output, ['Patient ID', 'Patient Name', 'Age', 'Barangay', 'Gravida', 'Para', 'GTPAL', 'LMP', 'EDC', 'Gestational Age (AOG)', 'Pre-Eclampsia Risk', 'Status']);
                foreach ($data as $row) {
                    $gtpal = "G{$row['gravida']}P{$row['para']} (T{$row['term_births']} P{$row['preterm_births']} A{$row['abortions']} L{$row['living_children']})";
                    $aogText = !empty($row['is_active']) 
                        ? ($row['calculated_aog'] !== null ? "{$row['calculated_aog']} wks" : '0 wks') 
                        : (!empty($row['delivery_date']) ? 'Delivered (' . $row['delivery_date'] . ')' : 'Concluded');

                    fputcsv($output, [
                        $row['patient_no'],
                        "{$row['last_name']}, {$row['first_name']}",
                        $row['patient_age'] . ' yrs',
                        $row['barangay'],
                        'G' . $row['gravida'],
                        'P' . $row['para'],
                        $gtpal,
                        $row['lmp'],
                        $row['edc'],
                        $aogText,
                        !empty($row['pre_eclampsia']) ? 'YES (High Risk)' : 'No',
                        !empty($row['is_active']) ? 'Active Pregnancy' : 'Concluded'
                    ]);
                }
                break;

            case 'epi_coverage':
                fputcsv($output, ['Patient ID', 'Child Name', 'DOB', 'Age (Mos)', 'Barangay', 'Mother', 'BCG', 'HepB', 'Penta 1', 'Penta 2', 'Penta 3', 'OPV 1', 'OPV 2', 'OPV 3', 'IPV', 'MCV 1', 'MCV 2', 'FIC Status']);
                foreach ($data as $row) {
                    fputcsv($output, [
                        $row['patient_no'],
                        "{$row['last_name']}, {$row['first_name']}",
                        $row['dob'],
                        $row['age_months'],
                        $row['barangay'],
                        $row['mother_name'] ?: '-',
                        $row['bcg_date'] ?: 'Pending',
                        $row['hepb_date'] ?: 'Pending',
                        $row['penta1_date'] ?: 'Pending',
                        $row['penta2_date'] ?: 'Pending',
                        $row['penta3_date'] ?: 'Pending',
                        $row['opv1_date'] ?: 'Pending',
                        $row['opv2_date'] ?: 'Pending',
                        $row['opv3_date'] ?: 'Pending',
                        $row['ipv_date'] ?: 'Pending',
                        $row['mcv1_date'] ?: 'Pending',
                        $row['mcv2_date'] ?: 'Pending',
                        $row['fic_status'] ?? 'Incomplete'
                    ]);
                }
                break;

            case 'chronic_morbidity':
                fputcsv($output, ['Patient ID', 'Patient Name', 'Age', 'Sex', 'Barangay', 'Hypertension', 'Diabetes', 'Asthma', 'Heart Disease', 'Kidney Disease', 'PTB', 'Other Conditions', 'Allergies', 'Smoking', 'Alcohol']);
                foreach ($data as $row) {
                    $conds = $row['conditions_map'] ?? [];
                    $condKeys = array_keys($conds);
                    $condStr = strtoupper(implode(' ', $condKeys));

                    $hasHtn = (stripos($condStr, 'HYPERTEN') !== false) ? 'YES' : 'No';
                    $hasDm = (stripos($condStr, 'DIABET') !== false) ? 'YES' : 'No';
                    $hasAsthma = (stripos($condStr, 'ASTHMA') !== false) ? 'YES' : 'No';
                    $hasCvd = (stripos($condStr, 'CARDIO') !== false || stripos($condStr, 'HEART') !== false || stripos($condStr, 'CORONARY') !== false) ? 'YES' : 'No';
                    $hasCkd = (stripos($condStr, 'KIDNEY') !== false) ? 'YES' : 'No';
                    $hasPtb = (stripos($condStr, 'TUBERC') !== false || stripos($condStr, 'PTB') !== false) ? 'YES' : 'No';

                    // Collect other conditions
                    $others = [];
                    foreach ($conds as $name => $rem) {
                        $nUpper = strtoupper($name);
                        if (stripos($nUpper, 'HYPERTEN') === false && stripos($nUpper, 'DIABET') === false && 
                            stripos($nUpper, 'ASTHMA') === false && stripos($nUpper, 'CARDIO') === false && 
                            stripos($nUpper, 'HEART') === false && stripos($nUpper, 'KIDNEY') === false && 
                            stripos($nUpper, 'TUBERC') === false && stripos($nUpper, 'PTB') === false) {
                            $others[] = !empty($rem) ? "{$name} ({$rem})" : $name;
                        }
                    }

                    fputcsv($output, [
                        $row['patient_no'],
                        "{$row['last_name']}, {$row['first_name']}",
                        $row['patient_age'] . ' yrs',
                        $row['sex'],
                        $row['barangay'],
                        $hasHtn,
                        $hasDm,
                        $hasAsthma,
                        $hasCvd,
                        $hasCkd,
                        $hasPtb,
                        !empty($others) ? implode('; ', $others) : '-',
                        !empty($row['allergies_text']) ? $row['allergies_text'] : 'None Reported',
                        $row['smoking_status'] ?: 'Never',
                        $row['alcohol_status'] ?: 'Never'
                    ]);
                }
                break;
        }

        fclose($output);
        exit;
    }

    /**
     * Database querying helper based on report criteria.
     */
    private function queryReportData($type, $dateFrom, $dateTo, $allTime = false) {
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            $dateToExclusive = date('Y-m-d', strtotime($dateTo . ' +1 day'));
            
            switch ($type) {
                case 'daily_visits':
                    $sql = "SELECT q.*, p.patient_no, p.first_name AS patient_first, p.last_name AS patient_last 
                            FROM queue_entries q
                            JOIN patients p ON q.patient_id = p.id
                            WHERE q.queue_date BETWEEN :date_from AND :date_to
                              AND p.deleted_at IS NULL
                            ORDER BY q.queue_date DESC, q.queue_no ASC";
                    $params = ['date_from' => $dateFrom, 'date_to' => $dateTo];
                    break;
                    
                case 'consultations':
                    $sql = "SELECT c.*, p.patient_no, p.first_name AS patient_first, p.last_name AS patient_last,
                                   COALESCE(NULLIF(TRIM(c.consulting_provider), ''), CONCAT(u.first_name, ' ', u.last_name), 'Unassigned Clinician') AS clinician_name
                            FROM consultations c
                            JOIN patients p ON c.patient_id = p.id
                            LEFT JOIN users u ON c.consulted_by = u.id
                            WHERE c.consulted_at >= :date_from AND c.consulted_at < :date_to_exclusive
                              AND c.deleted_at IS NULL
                              AND p.deleted_at IS NULL
                              AND c.status != 'Cancelled'
                            ORDER BY c.consulted_at DESC";
                    $params = ['date_from' => $dateFrom, 'date_to_exclusive' => $dateToExclusive];
                    break;
                    
                case 'registrations':
                    $sql = "SELECT *, TIMESTAMPDIFF(YEAR, dob, CURRENT_DATE()) AS age 
                            FROM patients 
                            WHERE deleted_at IS NULL AND created_at >= :date_from AND created_at < :date_to_exclusive
                            ORDER BY created_at DESC";
                    $params = ['date_from' => $dateFrom, 'date_to_exclusive' => $dateToExclusive];
                    break;
                    
                case 'queue_summary':
                    $sql = "SELECT q.queue_date AS date, 
                                   COUNT(*) AS total,
                                   SUM(CASE WHEN q.status = 'Completed' THEN 1 ELSE 0 END) AS completed,
                                   SUM(CASE WHEN q.status = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled,
                                   SUM(CASE WHEN q.status = 'Waiting' THEN 1 ELSE 0 END) AS waiting,
                                   SUM(CASE WHEN q.status IN ('Called', 'Serving') THEN 1 ELSE 0 END) AS called_serving
                            FROM queue_entries q
                            JOIN patients p ON q.patient_id = p.id
                            WHERE q.queue_date BETWEEN :date_from AND :date_to
                              AND p.deleted_at IS NULL
                            GROUP BY q.queue_date
                            ORDER BY q.queue_date DESC";
                    $params = ['date_from' => $dateFrom, 'date_to' => $dateTo];
                    break;
                    
                case 'vitals':
                    $sql = "SELECT v.*, p.patient_no, p.first_name AS patient_first, p.last_name AS patient_last,
                                   CONCAT(u.first_name, ' ', u.last_name) AS recorded_by_name
                            FROM vital_signs v
                            JOIN patients p ON v.patient_id = p.id
                            JOIN users u ON v.recorded_by = u.id
                            WHERE v.recorded_at >= :date_from AND v.recorded_at < :date_to_exclusive
                              AND v.deleted_at IS NULL
                              AND p.deleted_at IS NULL
                            ORDER BY v.recorded_at DESC";
                    $params = ['date_from' => $dateFrom, 'date_to_exclusive' => $dateToExclusive];
                    break;

                case 'maternal_health':
                    $dateFilter = '';
                    $params = [];
                    if (!$allTime) {
                        $dateFilter = "AND (
                            (pr.created_at >= :reg_from AND pr.created_at < :reg_to_exclusive)
                            OR EXISTS (
                                SELECT 1 FROM prenatal_visits pv 
                                WHERE pv.prenatal_id = pr.id 
                                  AND pv.visit_date BETWEEN :visit_from AND :visit_to 
                                  AND pv.deleted_at IS NULL
                            )
                            OR (pr.delivery_date BETWEEN :del_from AND :del_to)
                            OR (pr.edc BETWEEN :edc_from AND :edc_to)
                            OR (pr.is_active = 1 AND :active_to >= CURRENT_DATE() AND pr.lmp <= :active_lmp_to)
                        )";
                        $params = [
                            'reg_from' => $dateFrom,
                            'reg_to_exclusive' => $dateToExclusive,
                            'visit_from' => $dateFrom,
                            'visit_to' => $dateTo,
                            'del_from' => $dateFrom,
                            'del_to' => $dateTo,
                            'edc_from' => $dateFrom,
                            'edc_to' => $dateTo,
                            'active_to' => $dateTo,
                            'active_lmp_to' => $dateTo
                        ];
                    }

                    $sql = "SELECT pr.*, 
                                   p.patient_no, p.first_name, p.last_name, p.middle_name, p.dob, p.contact_no, p.barangay, p.blood_type, p.philhealth_no,
                                   TIMESTAMPDIFF(YEAR, p.dob, CURRENT_DATE()) AS patient_age,
                                   CASE 
                                       WHEN pr.is_active = 1 THEN TIMESTAMPDIFF(WEEK, pr.lmp, CURRENT_DATE())
                                       WHEN pr.delivery_date IS NOT NULL THEN TIMESTAMPDIFF(WEEK, pr.lmp, pr.delivery_date)
                                       ELSE NULL
                                   END AS calculated_aog,
                                   COALESCE(pmh.pre_eclampsia, 0) AS pre_eclampsia,
                                   (SELECT COUNT(*) FROM prenatal_visits pv WHERE pv.prenatal_id = pr.id AND pv.deleted_at IS NULL) AS total_visits
                            FROM prenatal_records pr
                            JOIN patients p ON pr.patient_id = p.id
                            LEFT JOIN patient_medical_histories pmh ON pmh.patient_id = p.id AND pmh.deleted_at IS NULL
                            WHERE p.deleted_at IS NULL AND pr.deleted_at IS NULL
                              {$dateFilter}
                            ORDER BY pr.is_active DESC, pr.edc ASC";
                    
                    $stmt = $db->prepare($sql);
                    $stmt->execute($params);
                    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

                case 'epi_coverage':
                    $dateFilter = '';
                    $params = [];
                    if (!$allTime) {
                        $dateFilter = "AND (
                            EXISTS (
                                SELECT 1 FROM immunizations imm 
                                WHERE imm.patient_id = p.id 
                                  AND imm.administered_date BETWEEN :imm_from AND :imm_to 
                                  AND imm.deleted_at IS NULL
                            )
                            OR (p.dob BETWEEN :dob_from AND :dob_to)
                        )";
                        $params = [
                            'imm_from' => $dateFrom,
                            'imm_to' => $dateTo,
                            'dob_from' => $dateFrom,
                            'dob_to' => $dateTo
                        ];
                    }

                    $sql = "SELECT p.id AS patient_id, p.patient_no, p.first_name, p.last_name, p.dob, p.sex, p.barangay, p.mother_name,
                                   TIMESTAMPDIFF(MONTH, p.dob, CURRENT_DATE()) AS age_months,
                                   wb.birth_weight_kg, wb.birth_length_cm, wb.newborn_screening_done,
                                   (SELECT administered_date FROM immunizations WHERE patient_id = p.id AND UPPER(vaccine_name) = 'BCG' AND deleted_at IS NULL LIMIT 1) AS bcg_date,
                                   (SELECT administered_date FROM immunizations WHERE patient_id = p.id AND UPPER(vaccine_name) LIKE '%HEPATITIS%' AND deleted_at IS NULL LIMIT 1) AS hepb_date,
                                   (SELECT administered_date FROM immunizations WHERE patient_id = p.id AND UPPER(vaccine_name) LIKE '%PENTA%' AND dose_number = 1 AND deleted_at IS NULL LIMIT 1) AS penta1_date,
                                   (SELECT administered_date FROM immunizations WHERE patient_id = p.id AND UPPER(vaccine_name) LIKE '%PENTA%' AND dose_number = 2 AND deleted_at IS NULL LIMIT 1) AS penta2_date,
                                   (SELECT administered_date FROM immunizations WHERE patient_id = p.id AND UPPER(vaccine_name) LIKE '%PENTA%' AND dose_number = 3 AND deleted_at IS NULL LIMIT 1) AS penta3_date,
                                   (SELECT administered_date FROM immunizations WHERE patient_id = p.id AND UPPER(vaccine_name) LIKE '%OPV%' AND dose_number = 1 AND deleted_at IS NULL LIMIT 1) AS opv1_date,
                                   (SELECT administered_date FROM immunizations WHERE patient_id = p.id AND UPPER(vaccine_name) LIKE '%OPV%' AND dose_number = 2 AND deleted_at IS NULL LIMIT 1) AS opv2_date,
                                   (SELECT administered_date FROM immunizations WHERE patient_id = p.id AND UPPER(vaccine_name) LIKE '%OPV%' AND dose_number = 3 AND deleted_at IS NULL LIMIT 1) AS opv3_date,
                                   (SELECT administered_date FROM immunizations WHERE patient_id = p.id AND UPPER(vaccine_name) LIKE '%IPV%' AND deleted_at IS NULL LIMIT 1) AS ipv_date,
                                   (SELECT administered_date FROM immunizations WHERE patient_id = p.id AND (UPPER(vaccine_name) LIKE '%MCV%' OR UPPER(vaccine_name) LIKE '%MEASLES%') AND dose_number = 1 AND deleted_at IS NULL LIMIT 1) AS mcv1_date,
                                   (SELECT administered_date FROM immunizations WHERE patient_id = p.id AND (UPPER(vaccine_name) LIKE '%MCV%' OR UPPER(vaccine_name) LIKE '%MMR%') AND dose_number = 2 AND deleted_at IS NULL LIMIT 1) AS mcv2_date
                            FROM patients p
                            LEFT JOIN wellbaby_records wb ON wb.patient_id = p.id AND wb.deleted_at IS NULL
                            WHERE p.deleted_at IS NULL AND TIMESTAMPDIFF(YEAR, p.dob, CURRENT_DATE()) <= 5
                              {$dateFilter}
                            ORDER BY p.dob DESC";
                    
                    $stmt = $db->prepare($sql);
                    $stmt->execute($params);
                    $results = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

                    foreach ($results as &$r) {
                        $hasAll = (!empty($r['bcg_date']) && !empty($r['hepb_date']) 
                            && !empty($r['penta1_date']) && !empty($r['penta2_date']) && !empty($r['penta3_date']) 
                            && !empty($r['opv1_date']) && !empty($r['opv2_date']) && !empty($r['opv3_date']) 
                            && !empty($r['ipv_date']) && !empty($r['mcv1_date']));
                        $r['is_fic'] = $hasAll && ($r['age_months'] <= 12);
                        $r['is_cic'] = $hasAll && ($r['age_months'] > 12);
                        $r['fic_status'] = $r['is_fic'] ? 'FIC' : ($r['is_cic'] ? 'CIC' : 'Incomplete');
                    }
                    unset($r);
                    return $results;

                case 'chronic_morbidity':
                    $dateFilter = '';
                    $params = [];
                    if (!$allTime) {
                        $dateFilter = "AND EXISTS (
                            SELECT 1 FROM patient_conditions pc
                            WHERE pc.patient_id = p.id AND pc.condition_type = 'Past' AND pc.deleted_at IS NULL
                              AND (
                                  (pc.created_at >= :pc_from AND pc.created_at < :pc_to_exclusive)
                                  OR EXISTS (
                                      SELECT 1 FROM consultations c 
                                      WHERE c.patient_id = p.id 
                                        AND c.consulted_at >= :c_from AND c.consulted_at < :c_to_exclusive
                                        AND c.deleted_at IS NULL
                                  )
                                  OR EXISTS (
                                      SELECT 1 FROM queue_entries q
                                      WHERE q.patient_id = p.id
                                        AND q.queue_date BETWEEN :q_from AND :q_to
                                  )
                              )
                        )";
                        $params = [
                            'pc_from' => $dateFrom,
                            'pc_to_exclusive' => $dateToExclusive,
                            'c_from' => $dateFrom,
                            'c_to_exclusive' => $dateToExclusive,
                            'q_from' => $dateFrom,
                            'q_to' => $dateTo
                        ];
                    }

                    $sql = "SELECT pmh.smoking_status, pmh.smoking_pack_years, pmh.alcohol_status, pmh.alcohol_bottles_per_day,
                                   p.id AS patient_id, p.patient_no, p.first_name, p.last_name, p.middle_name, p.dob, p.sex, p.contact_no, p.barangay, p.philhealth_no,
                                   TIMESTAMPDIFF(YEAR, p.dob, CURRENT_DATE()) AS patient_age
                            FROM patients p
                            LEFT JOIN patient_medical_histories pmh ON p.id = pmh.patient_id AND pmh.deleted_at IS NULL
                            WHERE p.deleted_at IS NULL 
                              AND EXISTS (
                                  SELECT 1 FROM patient_conditions 
                                  WHERE patient_id = p.id AND condition_type = 'Past' AND deleted_at IS NULL
                              )
                              {$dateFilter}
                            ORDER BY p.last_name ASC, p.first_name ASC";
                    
                    $stmt = $db->prepare($sql);
                    $stmt->execute($params);
                    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

                    if (!empty($patients)) {
                        $patientIds = array_column($patients, 'patient_id');
                        $placeholders = implode(',', array_fill(0, count($patientIds), '?'));
                        
                        $condStmt = $db->prepare("SELECT patient_id, condition_name, remarks 
                                                  FROM patient_conditions 
                                                  WHERE patient_id IN ($placeholders) 
                                                    AND condition_type = 'Past' 
                                                    AND deleted_at IS NULL 
                                                  ORDER BY condition_name ASC");
                        $condStmt->execute($patientIds);
                        $conditionsRaw = $condStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

                        $conditionsMap = [];
                        $allergiesMap = [];
                        foreach ($conditionsRaw as $cRow) {
                            $pid = $cRow['patient_id'];
                            $cName = trim($cRow['condition_name']);
                            $cRemarks = trim($cRow['remarks'] ?? '');
                            
                            if (stripos($cName, 'Allerg') !== false) {
                                $allergiesMap[$pid][] = !empty($cRemarks) ? "{$cName} ({$cRemarks})" : $cName;
                            } else {
                                $conditionsMap[$pid][$cName] = $cRemarks;
                            }
                        }

                        foreach ($patients as &$pt) {
                            $pid = $pt['patient_id'];
                            $pt['conditions_map'] = $conditionsMap[$pid] ?? [];
                            $pt['allergies_text'] = isset($allergiesMap[$pid]) ? implode('; ', $allergiesMap[$pid]) : '';
                        }
                        unset($pt);
                    }
                    return $patients;
                    
                default:
                    return [];
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll() ?: [];
        } catch (\PDOException $e) {
            error_log("Report query failure: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Compute summary KPI metrics for the generated report dataset.
     */
    private function computeReportMetrics($type, array $results): array {
        $count = count($results);
        if ($count === 0) {
            return [];
        }

        switch ($type) {
            case 'daily_visits':
                $completed = 0;
                $inProgress = 0;
                $cancelled = 0;
                foreach ($results as $r) {
                    if ($r['status'] === 'Completed') $completed++;
                    elseif (in_array($r['status'], ['Waiting', 'Called', 'Serving'])) $inProgress++;
                    elseif ($r['status'] === 'Cancelled') $cancelled++;
                }
                $compRate = round(($completed / $count) * 100, 1);
                return [
                    ['label' => 'Total Enqueued', 'value' => number_format($count), 'icon' => 'bi-people-fill', 'color' => 'primary'],
                    ['label' => 'Completed Visits', 'value' => number_format($completed), 'sub' => "{$compRate}% completion rate", 'icon' => 'bi-check-circle-fill', 'color' => 'success'],
                    ['label' => 'In-Queue / Serving', 'value' => number_format($inProgress), 'icon' => 'bi-hourglass-split', 'color' => 'warning'],
                    ['label' => 'Cancelled Tickets', 'value' => number_format($cancelled), 'icon' => 'bi-x-circle-fill', 'color' => 'danger'],
                ];

            case 'consultations':
                $uniquePatients = count(array_unique(array_column($results, 'patient_id')));
                $clinicians = count(array_unique(array_column($results, 'clinician_name')));
                $assessments = array_filter(array_column($results, 'assessment'));
                $topDiag = '-';
                if (!empty($assessments)) {
                    $counts = array_count_values($assessments);
                    arsort($counts);
                    $topDiag = array_key_first($counts);
                    if (strlen($topDiag) > 22) $topDiag = substr($topDiag, 0, 22) . '...';
                }
                return [
                    ['label' => 'Total Consultations', 'value' => number_format($count), 'icon' => 'bi-journal-medical', 'color' => 'primary'],
                    ['label' => 'Unique Patients', 'value' => number_format($uniquePatients), 'icon' => 'bi-person-check-fill', 'color' => 'teal'],
                    ['label' => 'Top Diagnosis', 'value' => $topDiag, 'icon' => 'bi-clipboard2-pulse-fill', 'color' => 'info'],
                    ['label' => 'Active Clinicians', 'value' => number_format($clinicians), 'icon' => 'bi-person-badge-fill', 'color' => 'secondary'],
                ];

            case 'registrations':
                $pedia = 0; $adults = 0; $seniors = 0;
                foreach ($results as $r) {
                    $age = (int)($r['age'] ?? 0);
                    if ($age <= 12) $pedia++;
                    elseif ($age >= 60) $seniors++;
                    else $adults++;
                }
                return [
                    ['label' => 'New Registrations', 'value' => number_format($count), 'icon' => 'bi-person-plus-fill', 'color' => 'primary'],
                    ['label' => 'Pediatric (0-12 yrs)', 'value' => number_format($pedia), 'icon' => 'bi-emoji-smile-fill', 'color' => 'info'],
                    ['label' => 'Adults (13-59 yrs)', 'value' => number_format($adults), 'icon' => 'bi-person-fill', 'color' => 'teal'],
                    ['label' => 'Senior Citizens (60+)', 'value' => number_format($seniors), 'icon' => 'bi-heart-pulse-fill', 'color' => 'warning'],
                ];

            case 'queue_summary':
                $totalEnqueued = array_sum(array_column($results, 'total'));
                $totalCompleted = array_sum(array_column($results, 'completed'));
                $totalCancelled = array_sum(array_column($results, 'cancelled'));
                $avgDaily = round($totalEnqueued / max(1, $count), 1);
                $rate = round(($totalCompleted / max(1, $totalEnqueued)) * 100, 1);
                return [
                    ['label' => 'Operating Days', 'value' => number_format($count), 'icon' => 'bi-calendar-check', 'color' => 'primary'],
                    ['label' => 'Total Enqueued', 'value' => number_format($totalEnqueued), 'sub' => "~{$avgDaily} / day", 'icon' => 'bi-people-fill', 'color' => 'teal'],
                    ['label' => 'Total Completed', 'value' => number_format($totalCompleted), 'sub' => "{$rate}% completion rate", 'icon' => 'bi-check-circle-fill', 'color' => 'success'],
                    ['label' => 'Total Cancelled', 'value' => number_format($totalCancelled), 'icon' => 'bi-x-circle-fill', 'color' => 'danger'],
                ];

            case 'vitals':
                $elevatedBp = 0; $fevers = 0;
                foreach ($results as $r) {
                    if (($r['bp_systolic'] >= 140 || $r['bp_diastolic'] >= 90)) $elevatedBp++;
                    if ((float)($r['temperature'] ?? 0) >= 37.8) $fevers++;
                }
                return [
                    ['label' => 'Vital Signs Recorded', 'value' => number_format($count), 'icon' => 'bi-activity', 'color' => 'primary'],
                    ['label' => 'Elevated BP (≥140/90)', 'value' => number_format($elevatedBp), 'icon' => 'bi-heart-pulse-fill', 'color' => $elevatedBp > 0 ? 'danger' : 'success'],
                    ['label' => 'Fever Detected (≥37.8°C)', 'value' => number_format($fevers), 'icon' => 'bi-thermometer-high', 'color' => $fevers > 0 ? 'warning' : 'success'],
                    ['label' => 'Normal / Stable', 'value' => number_format(max(0, $count - $elevatedBp - $fevers)), 'icon' => 'bi-shield-check', 'color' => 'teal'],
                ];

            case 'maternal_health':
                $active = 0; $highRisk = 0; $concluded = 0;
                foreach ($results as $r) {
                    if (!empty($r['is_active'])) $active++;
                    else $concluded++;
                    if (!empty($r['pre_eclampsia'])) $highRisk++;
                }
                return [
                    ['label' => 'Total Maternal Cases', 'value' => number_format($count), 'icon' => 'bi-person-heart', 'color' => 'primary'],
                    ['label' => 'Active Pregnancies', 'value' => number_format($active), 'icon' => 'bi-balloon-heart-fill', 'color' => 'pink'],
                    ['label' => 'Pre-Eclampsia High Risk', 'value' => number_format($highRisk), 'icon' => 'bi-exclamation-triangle-fill', 'color' => $highRisk > 0 ? 'danger' : 'success'],
                    ['label' => 'Concluded / Delivered', 'value' => number_format($concluded), 'icon' => 'bi-check-all', 'color' => 'secondary'],
                ];

            case 'epi_coverage':
                $fic = 0; $cic = 0; $incomplete = 0;
                foreach ($results as $r) {
                    if (!empty($r['is_fic'])) $fic++;
                    elseif (!empty($r['is_cic'])) $cic++;
                    else $incomplete++;
                }
                $ficRate = round(($fic / max(1, $count)) * 100, 1);
                return [
                    ['label' => 'Monitored Children (≤5y)', 'value' => number_format($count), 'icon' => 'bi-person-badge', 'color' => 'primary'],
                    ['label' => 'Fully Immunized (FIC)', 'value' => number_format($fic), 'sub' => "{$ficRate}% FIC rate (≤1 yr)", 'icon' => 'bi-shield-check', 'color' => 'success'],
                    ['label' => 'Completely Immunized (CIC)', 'value' => number_format($cic), 'sub' => 'Completed >1 yr', 'icon' => 'bi-patch-check-fill', 'color' => 'info'],
                    ['label' => 'Incomplete Vaccine Series', 'value' => number_format($incomplete), 'icon' => 'bi-clock-history', 'color' => $incomplete > 0 ? 'warning' : 'success'],
                ];

            case 'chronic_morbidity':
                $htn = 0; $dm = 0; $asthma = 0; $cvdCkd = 0;
                foreach ($results as $r) {
                    $conds = $r['conditions_map'] ?? [];
                    $condKeys = array_keys($conds);
                    $condStr = strtoupper(implode(' ', $condKeys));
                    if (stripos($condStr, 'HYPERTEN') !== false) $htn++;
                    if (stripos($condStr, 'DIABET') !== false) $dm++;
                    if (stripos($condStr, 'ASTHMA') !== false) $asthma++;
                    if (stripos($condStr, 'CARDIO') !== false || stripos($condStr, 'HEART') !== false || stripos($condStr, 'KIDNEY') !== false) $cvdCkd++;
                }
                return [
                    ['label' => 'Registry Patients', 'value' => number_format($count), 'icon' => 'bi-journal-medical', 'color' => 'primary'],
                    ['label' => 'Hypertension Cases', 'value' => number_format($htn), 'icon' => 'bi-heart-pulse', 'color' => 'danger'],
                    ['label' => 'Diabetes Mellitus', 'value' => number_format($dm), 'icon' => 'bi-droplet-half', 'color' => 'warning'],
                    ['label' => 'Asthma / Pulmonary', 'value' => number_format($asthma), 'icon' => 'bi-lungs', 'color' => 'info'],
                ];

            default:
                return [];
        }
    }

    private function normalizeDateRange($dateFrom, $dateTo) {
        $from = \DateTimeImmutable::createFromFormat('!Y-m-d', (string)$dateFrom);
        $to = \DateTimeImmutable::createFromFormat('!Y-m-d', (string)$dateTo);
        $validFrom = $from && $from->format('Y-m-d') === $dateFrom;
        $validTo = $to && $to->format('Y-m-d') === $dateTo;

        if (!$validFrom || !$validTo) {
            return [date('Y-m-01'), date('Y-m-d')];
        }

        if ($from > $to) {
            return [$to->format('Y-m-d'), $from->format('Y-m-d')];
        }

        return [$from->format('Y-m-d'), $to->format('Y-m-d')];
    }
}

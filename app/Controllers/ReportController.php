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

        $results = [];
        if (!empty($type)) {
            $results = $this->queryReportData($type, $dateFrom, $dateTo);
            AuditLog::log('REPORT_GENERATED', 'Reports', "Generated report type: {$type} from {$dateFrom} to {$dateTo}");
        }

        $this->view('reports/index', [
            'type' => $type,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'results' => $results
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

        if (empty($type)) {
            $_SESSION['error_message'] = 'Report type is required for CSV export.';
            $this->redirect('/reports');
            return;
        }

        $data = $this->queryReportData($type, $dateFrom, $dateTo);
        AuditLog::log('REPORT_EXPORTED', 'Reports', "Exported report type: {$type} as CSV from {$dateFrom} to {$dateTo}");

        $filename = "report_{$type}_" . date('Ymd_His') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');

        // Column headers and mapping
        switch ($type) {
            case 'daily_visits':
                fputcsv($output, ['Queue Date', 'Queue No.', 'Patient ID', 'Patient Name', 'Time In', 'Time Called', 'Time Completed', 'Status']);
                foreach ($data as $row) {
                    fputcsv($output, [
                        $row['queue_date'],
                        sprintf('%03d', $row['queue_no']),
                        $row['patient_no'],
                        "{$row['patient_last']}, {$row['patient_first']}",
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
                fputcsv($output, ['Reg Date', 'Patient ID', 'Last Name', 'First Name', 'Birth Date', 'Sex', 'Barangay', 'Contact No.']);
                foreach ($data as $row) {
                    fputcsv($output, [
                        date('Y-m-d', strtotime($row['created_at'])),
                        $row['patient_no'],
                        $row['last_name'],
                        $row['first_name'],
                        $row['dob'],
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
                fputcsv($output, ['Recorded Date', 'Patient Name', 'BP (Systolic/Diastolic)', 'Pulse (bpm)', 'Temp (°C)', 'Resp (cpm)', 'SpO2 (%)', 'BMI', 'Recorded By']);
                foreach ($data as $row) {
                    fputcsv($output, [
                        $row['recorded_at'],
                        "{$row['patient_last']}, {$row['patient_first']}",
                        ($row['bp_systolic'] && $row['bp_diastolic']) ? "{$row['bp_systolic']}/{$row['bp_diastolic']}" : '-',
                        $row['heart_rate'] ?: '-',
                        $row['temperature'] ?: '-',
                        $row['respiratory_rate'] ?: '-',
                        $row['oxygen_saturation'] ?: '-',
                        $row['bmi'] ?: '-',
                        $row['recorded_by_name']
                    ]);
                }
                break;

            case 'maternal_health':
                fputcsv($output, ['Patient ID', 'Patient Name', 'Age', 'Barangay', 'Gravida', 'Para', 'LMP', 'EDC', 'AOG (Wks)', 'Pre-Eclampsia Risk', 'Status']);
                foreach ($data as $row) {
                    fputcsv($output, [
                        $row['patient_no'],
                        "{$row['last_name']}, {$row['first_name']}",
                        $row['patient_age'],
                        $row['barangay'],
                        'G' . $row['gravida'],
                        'P' . $row['para'],
                        $row['lmp'],
                        $row['edc'],
                        $row['calculated_aog'] ?: '0',
                        !empty($row['pre_eclampsia']) ? 'YES (High Risk)' : 'No',
                        !empty($row['is_active']) ? 'Active Pregnancy' : 'Concluded'
                    ]);
                }
                break;

            case 'epi_coverage':
                fputcsv($output, ['Patient ID', 'Child Name', 'DOB', 'Age (Mos)', 'Barangay', 'Mother', 'BCG', 'HepB', 'Penta 1', 'Penta 2', 'Penta 3', 'OPV 1', 'OPV 2', 'OPV 3', 'IPV', 'MCV 1', 'MCV 2']);
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
                        $row['mcv2_date'] ?: 'Pending'
                    ]);
                }
                break;

            case 'chronic_morbidity':
                fputcsv($output, ['Patient ID', 'Patient Name', 'Age', 'Sex', 'Barangay', 'Hypertension', 'Diabetes', 'Asthma', 'Heart Disease', 'Kidney Disease', 'PTB', 'Allergies', 'Smoking', 'Alcohol']);
                foreach ($data as $row) {
                    $pmh = is_array($row['past_medical_history']) ? $row['past_medical_history'] : (json_decode($row['past_medical_history'] ?? '[]', true) ?: []);
                    $allergies = $pmh['Allergy'] ?? $pmh['Allergies'] ?? '-';
                    $hasHtn = isset($pmh['Hypertension']) ? 'YES' : 'No';
                    $hasDm = isset($pmh['Diabetes Mellitus']) ? 'YES' : 'No';
                    $hasAsthma = (isset($pmh['Asthma']) || isset($pmh['Bronchial Asthma'])) ? 'YES' : 'No';
                    $hasCvd = (isset($pmh['Cardiovascular Disease']) || isset($pmh['Heart Disease'])) ? 'YES' : 'No';
                    $hasCkd = (isset($pmh['Chronic Kidney Disease']) || isset($pmh['Kidney Disease'])) ? 'YES' : 'No';
                    $hasPtb = (isset($pmh['Pulmonary Tuberculosis']) || isset($pmh['PTB'])) ? 'YES' : 'No';

                    fputcsv($output, [
                        $row['patient_no'],
                        "{$row['last_name']}, {$row['first_name']}",
                        $row['patient_age'],
                        $row['sex'],
                        $row['barangay'],
                        $hasHtn,
                        $hasDm,
                        $hasAsthma,
                        $hasCvd,
                        $hasCkd,
                        $hasPtb,
                        $allergies,
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
    private function queryReportData($type, $dateFrom, $dateTo) {
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            
            switch ($type) {
                case 'daily_visits':
                    $sql = "SELECT q.*, p.patient_no, p.first_name AS patient_first, p.last_name AS patient_last 
                            FROM queue_entries q
                            JOIN patients p ON q.patient_id = p.id
                            WHERE q.queue_date BETWEEN :date_from AND :date_to
                            ORDER BY q.queue_date DESC, q.queue_no ASC";
                    $params = ['date_from' => $dateFrom, 'date_to' => $dateTo];
                    break;
                    
                case 'consultations':
                    $sql = "SELECT c.*, p.patient_no, p.first_name AS patient_first, p.last_name AS patient_last,
                                   CONCAT(u.first_name, ' ', u.last_name) AS clinician_name
                            FROM consultations c
                            JOIN patients p ON c.patient_id = p.id
                            JOIN users u ON c.consulted_by = u.id
                            WHERE DATE(c.consulted_at) BETWEEN :date_from AND :date_to
                            ORDER BY c.consulted_at DESC";
                    $params = ['date_from' => $dateFrom, 'date_to' => $dateTo];
                    break;
                    
                case 'registrations':
                    $sql = "SELECT *, TIMESTAMPDIFF(YEAR, dob, CURRENT_DATE()) AS age 
                            FROM patients 
                            WHERE deleted_at IS NULL AND DATE(created_at) BETWEEN :date_from AND :date_to
                            ORDER BY created_at DESC";
                    $params = ['date_from' => $dateFrom, 'date_to' => $dateTo];
                    break;
                    
                case 'queue_summary':
                    $sql = "SELECT queue_date AS date, 
                                   COUNT(*) AS total,
                                   SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed,
                                   SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled,
                                   SUM(CASE WHEN status = 'Waiting' THEN 1 ELSE 0 END) AS waiting,
                                   SUM(CASE WHEN status IN ('Called', 'Serving') THEN 1 ELSE 0 END) AS called_serving
                            FROM queue_entries
                            WHERE queue_date BETWEEN :date_from AND :date_to
                            GROUP BY queue_date
                            ORDER BY queue_date DESC";
                    $params = ['date_from' => $dateFrom, 'date_to' => $dateTo];
                    break;
                    
                case 'vitals':
                    $sql = "SELECT v.*, p.patient_no, p.first_name AS patient_first, p.last_name AS patient_last,
                                   CONCAT(u.first_name, ' ', u.last_name) AS recorded_by_name
                            FROM vital_signs v
                            JOIN patients p ON v.patient_id = p.id
                            JOIN users u ON v.recorded_by = u.id
                            WHERE DATE(v.recorded_at) BETWEEN :date_from AND :date_to
                            ORDER BY v.recorded_at DESC";
                    $params = ['date_from' => $dateFrom, 'date_to' => $dateTo];
                    break;

                case 'maternal_health':
                    $sql = "SELECT pr.*, 
                                   p.patient_no, p.first_name, p.last_name, p.middle_name, p.dob, p.contact_no, p.barangay, p.blood_type, p.philhealth_no,
                                   TIMESTAMPDIFF(YEAR, p.dob, CURRENT_DATE()) AS patient_age,
                                   TIMESTAMPDIFF(WEEK, pr.lmp, CURRENT_DATE()) AS calculated_aog,
                                   (SELECT COUNT(*) FROM prenatal_visits pv WHERE pv.prenatal_id = pr.id) AS total_visits
                            FROM prenatal_records pr
                            JOIN patients p ON pr.patient_id = p.id
                            WHERE p.deleted_at IS NULL AND (pr.is_active = 1 OR pr.edc BETWEEN :date_from AND :date_to)
                            ORDER BY pr.edc ASC";
                    $params = ['date_from' => $dateFrom, 'date_to' => $dateTo];
                    break;

                case 'epi_coverage':
                    $sql = "SELECT p.id AS patient_id, p.patient_no, p.first_name, p.last_name, p.dob, p.sex, p.barangay, p.mother_name,
                                   TIMESTAMPDIFF(MONTH, p.dob, CURRENT_DATE()) AS age_months,
                                   wb.birth_weight_kg, wb.birth_length_cm, wb.newborn_screening_done,
                                   (SELECT administered_date FROM immunizations WHERE patient_id = p.id AND UPPER(vaccine_name) = 'BCG' LIMIT 1) AS bcg_date,
                                   (SELECT administered_date FROM immunizations WHERE patient_id = p.id AND UPPER(vaccine_name) LIKE '%HEPATITIS%' LIMIT 1) AS hepb_date,
                                   (SELECT administered_date FROM immunizations WHERE patient_id = p.id AND UPPER(vaccine_name) LIKE '%PENTA%' AND dose_number = 1 LIMIT 1) AS penta1_date,
                                   (SELECT administered_date FROM immunizations WHERE patient_id = p.id AND UPPER(vaccine_name) LIKE '%PENTA%' AND dose_number = 2 LIMIT 1) AS penta2_date,
                                   (SELECT administered_date FROM immunizations WHERE patient_id = p.id AND UPPER(vaccine_name) LIKE '%PENTA%' AND dose_number = 3 LIMIT 1) AS penta3_date,
                                   (SELECT administered_date FROM immunizations WHERE patient_id = p.id AND UPPER(vaccine_name) LIKE '%OPV%' AND dose_number = 1 LIMIT 1) AS opv1_date,
                                   (SELECT administered_date FROM immunizations WHERE patient_id = p.id AND UPPER(vaccine_name) LIKE '%OPV%' AND dose_number = 2 LIMIT 1) AS opv2_date,
                                   (SELECT administered_date FROM immunizations WHERE patient_id = p.id AND UPPER(vaccine_name) LIKE '%OPV%' AND dose_number = 3 LIMIT 1) AS opv3_date,
                                   (SELECT administered_date FROM immunizations WHERE patient_id = p.id AND UPPER(vaccine_name) LIKE '%IPV%' LIMIT 1) AS ipv_date,
                                   (SELECT administered_date FROM immunizations WHERE patient_id = p.id AND (UPPER(vaccine_name) LIKE '%MCV%' OR UPPER(vaccine_name) LIKE '%MEASLES%') AND dose_number = 1 LIMIT 1) AS mcv1_date,
                                   (SELECT administered_date FROM immunizations WHERE patient_id = p.id AND (UPPER(vaccine_name) LIKE '%MCV%' OR UPPER(vaccine_name) LIKE '%MMR%') AND dose_number = 2 LIMIT 1) AS mcv2_date
                            FROM patients p
                            LEFT JOIN wellbaby_records wb ON wb.patient_id = p.id
                            WHERE p.deleted_at IS NULL AND TIMESTAMPDIFF(YEAR, p.dob, CURRENT_DATE()) <= 5
                            ORDER BY p.dob DESC";
                    $params = [];
                    break;

                case 'chronic_morbidity':
                    $sql = "SELECT pmh.*, 
                                   p.patient_no, p.first_name, p.last_name, p.middle_name, p.dob, p.sex, p.contact_no, p.barangay, p.philhealth_no,
                                   TIMESTAMPDIFF(YEAR, p.dob, CURRENT_DATE()) AS patient_age
                            FROM patient_medical_histories pmh
                            JOIN patients p ON pmh.patient_id = p.id
                            WHERE p.deleted_at IS NULL 
                              AND pmh.past_medical_history IS NOT NULL 
                              AND pmh.past_medical_history != '' 
                              AND pmh.past_medical_history != '[]'
                            ORDER BY p.last_name ASC, p.first_name ASC";
                    $params = [];
                    break;
                    
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
}

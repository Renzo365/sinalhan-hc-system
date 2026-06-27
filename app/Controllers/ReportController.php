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
                    break;
                    
                case 'consultations':
                    $sql = "SELECT c.*, p.patient_no, p.first_name AS patient_first, p.last_name AS patient_last,
                                   CONCAT(u.first_name, ' ', u.last_name) AS clinician_name
                            FROM consultations c
                            JOIN patients p ON c.patient_id = p.id
                            JOIN users u ON c.consulted_by = u.id
                            WHERE DATE(c.consulted_at) BETWEEN :date_from AND :date_to
                            ORDER BY c.consulted_at DESC";
                    break;
                    
                case 'registrations':
                    $sql = "SELECT *, TIMESTAMPDIFF(YEAR, dob, CURRENT_DATE()) AS age 
                            FROM patients 
                            WHERE deleted_at IS NULL AND DATE(created_at) BETWEEN :date_from AND :date_to
                            ORDER BY created_at DESC";
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
                    break;
                    
                case 'vitals':
                    $sql = "SELECT v.*, p.patient_no, p.first_name AS patient_first, p.last_name AS patient_last,
                                   CONCAT(u.first_name, ' ', u.last_name) AS recorded_by_name
                            FROM vital_signs v
                            JOIN patients p ON v.patient_id = p.id
                            JOIN users u ON v.recorded_by = u.id
                            WHERE DATE(v.recorded_at) BETWEEN :date_from AND :date_to
                            ORDER BY v.recorded_at DESC";
                    break;
                    
                default:
                    return [];
            }

            $stmt = $db->prepare($sql);
            $stmt->execute([
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ]);
            return $stmt->fetchAll() ?: [];
        } catch (\PDOException $e) {
            error_log("Report query failure: " . $e->getMessage());
            return [];
        }
    }
}

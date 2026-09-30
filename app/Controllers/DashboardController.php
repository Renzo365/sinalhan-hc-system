<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\QueueEntry;
use PDO;

class DashboardController extends Controller {
    protected $patientModel;
    protected $appointmentModel;
    protected $queueModel;

    public function __construct() {
        $this->patientModel = new Patient();
        $this->appointmentModel = new Appointment();
        $this->queueModel = new QueueEntry();
    }

    /**
     * Show the main primary care executive dashboard.
     */
    public function index() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userFullName = $_SESSION['user_fullname'] ?? $_SESSION['username'] ?? 'Staff Personnel';
        $userRole = $_SESSION['user_role'] ?? 'Health Center Staff';
        $userRoleDisplay = 'Health Center Staff';
        $normalizedRole = strtolower($userRole);
        if ($normalizedRole === 'super_admin') {
            $userRoleDisplay = 'Super Admin';
        } elseif ($normalizedRole === 'admin') {
            $userRoleDisplay = 'Administrator';
        } elseif (in_array($normalizedRole, ['nurse', 'midwife', 'physician', 'doctor', 'bhw'])) {
            $userRoleDisplay = ucfirst($userRole);
        }

        $enqueuedMap = [];
        $stats = [
            'total_patients' => 0,
            'new_patients_today' => 0,
            'today_appointments' => 0,
            'queue_now' => 0,
            'today_visits' => 0,
            'today_traffic' => 0
        ];

        $apptStats = [
            'total_today' => 0,
            'completed_today' => 0,
            'scheduled_today' => 0,
            'missed_today' => 0,
            'cancelled_today' => 0
        ];

        $todayAppointments = [];
        $queueStats = [
            'waiting' => 0,
            'called' => 0,
            'completed' => 0,
            'serving_no' => 0,
            'serving_service' => ''
        ];

        $censusMetrics = [
            'total_patients' => 0,
            'seniors' => 0,
            'under5' => 0,
            'phic_covered' => 0,
            'households' => 0
        ];

        $maternalWatch = [
            'active_pregnancies' => 0,
            'due_this_month' => 0,
            'past_due' => 0
        ];

        $upcomingDeliveries = [];
        $childHealth = [
            'total_under5' => 0,
            'infants_under1' => 0
        ];

        $recentConsultations = [];

        try {
            $db = Database::getInstance()->getConnection();

            // 1. Demographic Census Metrics
            $censusMetrics = $this->patientModel->getCensusMetrics();
            $stats['total_patients'] = $censusMetrics['total_patients'] ?? 0;

            // New patients registered today
            $stmt = $db->prepare("SELECT COUNT(*) FROM patients WHERE DATE(created_at) = CURRENT_DATE() AND deleted_at IS NULL");
            $stmt->execute();
            $stats['new_patients_today'] = (int)$stmt->fetchColumn();

            // 2. Today's Appointments Stats (Total, Completed, Scheduled, etc.)
            $stmt = $db->prepare("
                SELECT 
                    COUNT(*) as total_today,
                    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_today,
                    SUM(CASE WHEN status = 'Scheduled' THEN 1 ELSE 0 END) as scheduled_today,
                    SUM(CASE WHEN status = 'Missed' THEN 1 ELSE 0 END) as missed_today,
                    SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_today
                FROM appointments 
                WHERE appointment_date = CURRENT_DATE()
            ");
            $stmt->execute();
            $apptRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $apptStats = [
                'total_today' => (int)($apptRow['total_today'] ?? 0),
                'completed_today' => (int)($apptRow['completed_today'] ?? 0),
                'scheduled_today' => (int)($apptRow['scheduled_today'] ?? 0),
                'missed_today' => (int)($apptRow['missed_today'] ?? 0),
                'cancelled_today' => (int)($apptRow['cancelled_today'] ?? 0)
            ];
            $stats['today_appointments'] = $apptStats['total_today'];

            // 3. Queue Daily Stats
            $queueStats = $this->queueModel->getTodayStats();
            $stats['queue_now'] = (int)($queueStats['waiting'] ?? 0);
            $stats['today_visits'] = (int)($queueStats['completed'] ?? 0);

            // Daily Patient Traffic (Appointments completed + Walk-in queue visits completed)
            $stats['today_traffic'] = $apptStats['completed_today'] + $stats['today_visits'];

            // Today's scheduled appointment list
            $todayAppointments = $this->appointmentModel->getTodayAppointments();

            // Build map of patients already enqueued today for fast check-in state detection
            $todayQueueEntries = $this->queueModel->findAllToday();
            foreach ($todayQueueEntries as $qItem) {
                if (!empty($qItem['patient_id'])) {
                    $enqueuedMap[$qItem['patient_id']] = sprintf('%03d', $qItem['queue_no']);
                }
            }

            // 4. Maternal Health Radar
            $stmt = $db->query("
                SELECT 
                    COUNT(*) as active_pregnancies,
                    SUM(CASE WHEN DATEDIFF(edc, CURRENT_DATE()) BETWEEN 0 AND 30 THEN 1 ELSE 0 END) as due_this_month,
                    SUM(CASE WHEN DATEDIFF(edc, CURRENT_DATE()) < 0 THEN 1 ELSE 0 END) as past_due
                FROM prenatal_records
                WHERE is_active = 1 AND deleted_at IS NULL
            ");
            $maternalRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $maternalWatch = [
                'active_pregnancies' => (int)($maternalRow['active_pregnancies'] ?? 0),
                'due_this_month' => (int)($maternalRow['due_this_month'] ?? 0),
                'past_due' => (int)($maternalRow['past_due'] ?? 0)
            ];

            // Next 3 upcoming deliveries
            $stmt = $db->prepare("
                SELECT pr.id, pr.patient_id, pr.edc, pr.lmp, p.patient_no, p.first_name, p.last_name, p.contact_no, p.address,
                       DATEDIFF(pr.edc, CURRENT_DATE()) as days_to_delivery
                FROM prenatal_records pr
                JOIN patients p ON pr.patient_id = p.id
                WHERE pr.is_active = 1 AND pr.deleted_at IS NULL AND p.deleted_at IS NULL
                  AND DATEDIFF(pr.edc, CURRENT_DATE()) >= -7
                ORDER BY pr.edc ASC
                LIMIT 3
            ");
            $stmt->execute();
            $upcomingDeliveries = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // 5. Child Health (Under-5 & Infants under 1 year, and EPI records)
            $stmt = $db->query("
                SELECT 
                    COUNT(*) as total_under5,
                    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, dob, CURRENT_DATE()) <= 1 THEN 1 ELSE 0 END) as infants_under1
                FROM patients
                WHERE deleted_at IS NULL AND TIMESTAMPDIFF(YEAR, dob, CURRENT_DATE()) <= 5
            ");
            $childRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $stmtImm = $db->query("SELECT COUNT(*) FROM immunizations WHERE deleted_at IS NULL");
            $totalImm = (int)($stmtImm ? $stmtImm->fetchColumn() : 0);

            $stmtWb = $db->query("SELECT COUNT(*) FROM wellbaby_records WHERE deleted_at IS NULL");
            $totalWb = (int)($stmtWb ? $stmtWb->fetchColumn() : 0);

            $childHealth = [
                'total_under5' => (int)($childRow['total_under5'] ?? 0),
                'infants_under1' => (int)($childRow['infants_under1'] ?? 0),
                'total_immunizations' => $totalImm,
                'total_wellbaby' => $totalWb
            ];

            // 6. Recent Clinical Encounters
            $stmt = $db->prepare("
                SELECT c.id, c.patient_id, c.consulted_at, c.subjective, c.assessment,
                       p.patient_no, p.first_name, p.last_name, p.sex, TIMESTAMPDIFF(YEAR, p.dob, CURRENT_DATE()) as age,
                       COALESCE(NULLIF(TRIM(c.consulting_provider), ''), CONCAT(u.first_name, ' ', u.last_name), 'Staff') as clinician_name
                FROM consultations c
                JOIN patients p ON c.patient_id = p.id
                LEFT JOIN users u ON c.consulted_by = u.id
                WHERE c.deleted_at IS NULL AND p.deleted_at IS NULL
                ORDER BY c.consulted_at DESC
                LIMIT 5
            ");
            $stmt->execute();
            $recentConsultations = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        } catch (\PDOException $e) {
            error_log("Failed to query dashboard stats: " . $e->getMessage());
        }

        $this->view('dashboard', [
            'userFullName' => $userFullName,
            'userRole' => $userRole,
            'userRoleDisplay' => $userRoleDisplay,
            'stats' => $stats,
            'apptStats' => $apptStats,
            'todayAppointments' => $todayAppointments,
            'enqueuedMap' => $enqueuedMap,
            'queueStats' => $queueStats,
            'censusMetrics' => $censusMetrics,
            'maternalWatch' => $maternalWatch,
            'upcomingDeliveries' => $upcomingDeliveries,
            'childHealth' => $childHealth,
            'recentConsultations' => $recentConsultations
        ]);
    }
}

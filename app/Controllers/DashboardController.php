<?php

namespace App\Controllers;

use App\Core\Controller;

class DashboardController extends Controller {
    /**
     * Show the main dashboard.
     */
    public function index() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Setup some placeholder counts to demonstrate UI integration
        $stats = [
            'total_patients' => 0,
            'today_appointments' => 0,
            'queue_now' => 0,
            'today_visits' => 0
        ];

        // Fetch counts from DB if tables are empty, which they are initially
        // We will keep them as 0 or query database if tables exist.
        // Let's write queries to get real counts since it is extremely clean and professional!
        $todayAppointments = [];
        $queueStats = [
            'waiting' => 0,
            'called' => 0,
            'completed' => 0,
            'serving_no' => 0
        ];

        try {
            $db = \App\Core\Database::getInstance()->getConnection();
            
            // Total active patients
            $stmt = $db->query("SELECT COUNT(*) FROM patients WHERE deleted_at IS NULL");
            $stats['total_patients'] = $stmt->fetchColumn();
            
            // Today's scheduled appointments
            $stmt = $db->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURRENT_DATE() AND status = 'Scheduled'");
            $stmt->execute();
            $stats['today_appointments'] = $stmt->fetchColumn();
            
            // Queue active today (Waiting, Called, Serving)
            $stmt = $db->prepare("SELECT COUNT(*) FROM queue_entries WHERE queue_date = CURRENT_DATE() AND status IN ('Waiting', 'Called', 'Serving')");
            $stmt->execute();
            $stats['queue_now'] = $stmt->fetchColumn();
            
            // Completed visits today
            $stmt = $db->prepare("SELECT COUNT(*) FROM queue_entries WHERE queue_date = CURRENT_DATE() AND status = 'Completed'");
            $stmt->execute();
            $stats['today_visits'] = $stmt->fetchColumn();

            // Today's scheduled appointment list
            $todayAppointments = (new \App\Models\Appointment())->getTodayAppointments();
            
            // Get detailed daily queue stats
            $queueStats = (new \App\Models\QueueEntry())->getTodayStats();
        } catch (\PDOException $e) {
            // Fallback to placeholder if query fails
            error_log("Failed to query dashboard stats: " . $e->getMessage());
        }

        $this->view('dashboard', [
            'stats' => $stats,
            'todayAppointments' => $todayAppointments,
            'queueStats' => $queueStats
        ]);
    }
}

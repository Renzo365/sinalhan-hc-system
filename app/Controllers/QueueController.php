<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\QueueEntry;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\AuditLog;
use PDO;

class QueueController extends Controller {
    protected $queueModel;
    protected $patientModel;
    protected $appointmentModel;

    public function __construct() {
        $this->queueModel = new QueueEntry();
        $this->patientModel = new Patient();
        $this->appointmentModel = new Appointment();
    }

    /**
     * Display today's queue management board.
     */
    public function index() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $queueList = $this->queueModel->findAllToday();
        $queueStats = $this->queueModel->getTodayStats();

        // Fetch today's scheduled appointments for quick intake check-in
        $today = date('Y-m-d');
        $todayAppointments = $this->appointmentModel->findAll([
            'date_from' => $today,
            'date_to' => $today,
            'status' => 'Scheduled'
        ]);

        $preselectedPatient = null;
        if (!empty($_GET['patient_id'])) {
            $preselectedPatient = $this->patientModel->findById((int)$_GET['patient_id']);
        }

        $errors = $_SESSION['form_errors'] ?? [];
        $input = $_SESSION['form_input'] ?? [];

        unset($_SESSION['form_errors']);
        unset($_SESSION['form_input']);

        $this->view('queue/index', [
            'queueList' => $queueList,
            'queueStats' => $queueStats,
            'todayAppointments' => $todayAppointments,
            'preselectedPatient' => $preselectedPatient,
            'errors' => $errors,
            'input' => $input
        ]);
    }

    /**
     * Add a patient to today's queue.
     */
    public function store() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $patientId = (int)($_POST['patient_id'] ?? 0);
        $patient = $this->patientModel->findById($patientId);

        if (!$patient) {
            $_SESSION['form_errors'] = ['Selected patient record does not exist.'];
            $this->redirect('/queue');
            return;
        }

        // Enforce unique active queue constraint per day
        if ($this->queueModel->isPatientQueuedToday($patientId)) {
            $_SESSION['error_message'] = 'Patient is already registered in today\'s active queue.';
            $_SESSION['form_errors'] = ['Patient is already registered in today\'s active queue.'];
            $referrer = $_SERVER['HTTP_REFERER'] ?? '';
            if (strpos($referrer, 'patients') !== false) {
                $this->redirect("/patients/{$patientId}#tab-appointments");
            } else {
                $this->redirect('/queue');
            }
            return;
        }

        $serviceType = trim($_POST['service_type'] ?? 'General OPD');
        $validServices = ['General OPD', 'Prenatal Care', 'Well Baby Immunization', 'Senior Care', 'Family Planning', 'Dental Care', 'NCD / Hypertension'];
        if (!in_array($serviceType, $validServices)) {
            $serviceType = 'General OPD';
        }

        $data = [
            'patient_id' => $patientId,
            'service_type' => $serviceType,
            'created_by' => $_SESSION['user_id']
        ];

        $newId = $this->queueModel->create($data);

        if ($newId) {
            $queueEntry = $this->queueModel->findById($newId);
            $queueNoStr = sprintf('%03d', $queueEntry['queue_no']);
            $appointmentId = !empty($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : null;
            
            $logExtra = $appointmentId ? " (Checked in from Appointment #{$appointmentId})" : "";
            AuditLog::log('QUEUE_REGISTERED', 'Queue', "Enqueued patient: {$patient['first_name']} {$patient['last_name']} ({$patient['patient_no']}) for [{$serviceType}] with Queue No: {$queueNoStr}{$logExtra}");
            
            $_SESSION['success_message'] = "Patient successfully enqueued! Queue No: {$queueNoStr}";
            
            // Redirect back to designated URL or patient profile if enqueued from profile, otherwise back to queue board
            $referrer = $_SERVER['HTTP_REFERER'] ?? '';
            if (!empty($_POST['redirect_to'])) {
                $this->redirect($_POST['redirect_to']);
            } elseif (strpos($referrer, 'patients') !== false) {
                $this->redirect("/patients/{$patientId}#tab-appointments");
            } else {
                $this->redirect('/queue');
            }
        } else {
            $_SESSION['error_message'] = 'Database transaction failed. Please try again.';
            $_SESSION['form_errors'] = ['Database transaction failed. Please try again.'];
            $this->redirect('/queue');
        }
    }

    /**
     * Update the status of a queue entry.
     */
    public function updateStatus($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $queueEntry = $this->queueModel->findById($id);

        if (!$queueEntry) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Queue entry not found.'], 404);
            } else {
                http_response_code(404);
                $this->view('errors/404');
            }
            return;
        }

        $status = $_POST['status'] ?? '';
        $validStatuses = ['Waiting', 'Called', 'Serving', 'Completed', 'Cancelled'];

        if (!in_array($status, $validStatuses)) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Invalid queue status.'], 400);
            } else {
                $_SESSION['error_message'] = 'Invalid queue status.';
                $this->redirect('/queue');
            }
            return;
        }

        $userId = $_SESSION['user_id'];
        $queueNoStr = sprintf('%03d', $queueEntry['queue_no']);

        $isRecall = ($queueEntry['status'] === 'Called' && $status === 'Called');
        $cancelReason = !empty($_POST['cancel_reason']) ? ' [Reason: ' . trim($_POST['cancel_reason']) . ']' : '';

        if ($this->queueModel->updateStatus($id, $status, $userId)) {
            $logAction = $isRecall 
                ? "Re-called Queue No: {$queueNoStr} (audio announcement triggered)" 
                : "Updated Queue No: {$queueNoStr} status to: {$status}{$cancelReason} for patient ID: {$queueEntry['patient_id']}";
            AuditLog::log('QUEUE_STATUS_UPDATED', 'Queue', $logAction);
            
            $successMsg = $isRecall 
                ? "Queue No: {$queueNoStr} announcement re-triggered on public display!" 
                : ($status === 'Cancelled' ? "Queue No: {$queueNoStr} has been cancelled." : "Queue No: {$queueNoStr} updated to {$status}.");

            if ($this->isAjax()) {
                $this->json(['success' => true, 'message' => $successMsg]);
            } else {
                $_SESSION['success_message'] = $successMsg;
                $referrer = $_SERVER['HTTP_REFERER'] ?? '';
                if (strpos($referrer, 'patients') !== false) {
                    $this->redirect("/patients/{$queueEntry['patient_id']}#tab-appointments");
                } else {
                    $this->redirect('/queue');
                }
            }
        } else {
            if ($this->isAjax()) {
                $this->json(['error' => 'The queue status transition is no longer valid. Refresh the board and try again.'], 409);
            } else {
                $_SESSION['error_message'] = 'The queue status transition is no longer valid. Refresh the board and try again.';
                $this->redirect('/queue');
            }
        }
    }

    /**
     * Render the public queue display board view.
     */
    public function display() {
        // Disables layout wrappers since the display board is full screen for monitors
        $this->view('queue/display', ['disable_layout' => true]);
    }

    /**
     * AJAX JSON endpoint delivering live queue data for public display polling.
     */
    public function displayData() {
        $data = $this->queueModel->getPublicDisplayData();
        $this->json($data);
    }

    /**
     * AJAX JSON endpoint delivering active queue items, metrics, and bookings for 15s multi-station sync.
     */
    public function activeToday() {
        $queueList = $this->queueModel->findAllToday();
        $stats = $this->queueModel->getTodayStats();

        $today = date('Y-m-d');
        $todayAppointments = $this->appointmentModel->findAll([
            'date_from' => $today,
            'date_to' => $today,
            'status' => 'Scheduled'
        ]);

        $this->json([
            'success' => true,
            'stats' => $stats,
            'queue' => $queueList,
            'appointments' => $todayAppointments,
            'timestamp' => date('Y-m-d H:i:s'),
            'server_time' => date('h:i:s A')
        ]);
    }

    /**
     * Helper to verify AJAX request headers.
     */
    private function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
}

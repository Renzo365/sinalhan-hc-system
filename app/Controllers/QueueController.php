<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\QueueEntry;
use App\Models\Patient;
use App\Models\AuditLog;
use PDO;

class QueueController extends Controller {
    protected $queueModel;
    protected $patientModel;

    public function __construct() {
        $this->queueModel = new QueueEntry();
        $this->patientModel = new Patient();
    }

    /**
     * Display today's queue management board.
     */
    public function index() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $queueList = $this->queueModel->findAllToday();
        $patients = $this->patientModel->allActive();

        $errors = $_SESSION['form_errors'] ?? [];
        $input = $_SESSION['form_input'] ?? [];

        unset($_SESSION['form_errors']);
        unset($_SESSION['form_input']);

        $this->view('queue/index', [
            'queueList' => $queueList,
            'patients' => $patients,
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
            $this->redirect($_SERVER['HTTP_REFERER'] ?? '/queue');
            return;
        }

        $data = [
            'patient_id' => $patientId,
            'created_by' => $_SESSION['user_id']
        ];

        $newId = $this->queueModel->create($data);

        if ($newId) {
            $queueEntry = $this->queueModel->findById($newId);
            $queueNoStr = sprintf('%03d', $queueEntry['queue_no']);
            
            AuditLog::log('QUEUE_REGISTERED', 'Queue', "Enqueued patient: {$patient['first_name']} {$patient['last_name']} ({$patient['patient_no']}) with Queue No: {$queueNoStr}");
            
            $_SESSION['success_message'] = "Patient successfully enqueued! Queue No: {$queueNoStr}";
            
            // Redirect back to patient profile if enqueued from profile, otherwise back to queue board
            $referrer = $_SERVER['HTTP_REFERER'] ?? '';
            if (strpos($referrer, 'patients') !== false) {
                $this->redirect("/patients/{$patientId}#queue-tab");
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

        if ($this->queueModel->updateStatus($id, $status, $userId)) {
            AuditLog::log('QUEUE_STATUS_UPDATED', 'Queue', "Updated Queue No: {$queueNoStr} status to: {$status} for patient ID: {$queueEntry['patient_id']}");
            
            if ($this->isAjax()) {
                $this->json(['success' => true, 'message' => "Queue No: {$queueNoStr} marked as {$status}."]);
            } else {
                $_SESSION['success_message'] = "Queue No: {$queueNoStr} updated to {$status}.";
                $this->redirect($_SERVER['HTTP_REFERER'] ?? '/queue');
            }
        } else {
            if ($this->isAjax()) {
                $this->json(['error' => 'Database update failed.'], 500);
            } else {
                $_SESSION['error_message'] = 'Failed to update queue status.';
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
     * Helper to verify AJAX request headers.
     */
    private function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
}

<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Patient;
use App\Models\PrenatalRecord;
use App\Models\PrenatalVisit;
use App\Models\PastObstetricHistory;
use App\Models\PatientMedicalHistory;
use App\Models\VitalSigns;

class MaternalController extends Controller {
    protected $patientModel;
    protected $prenatalModel;
    protected $visitModel;
    protected $pastObstetricModel;
    protected $medicalHistoryModel;
    protected $vitalsModel;

    public function __construct() {
        $this->patientModel = new Patient();
        $this->prenatalModel = new PrenatalRecord();
        $this->visitModel = new PrenatalVisit();
        $this->pastObstetricModel = new PastObstetricHistory();
        $this->medicalHistoryModel = new PatientMedicalHistory();
        $this->vitalsModel = new VitalSigns();
    }

    /**
     * Display the Maternal Care Roster (Active Pregnancies & Recent Deliveries).
     */
    public function index() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $search = trim($_GET['search'] ?? '');
        $stage = trim($_GET['stage'] ?? '');
        $activeRoster = $this->prenatalModel->getActiveRoster($search);

        if (!empty($stage)) {
            $activeRoster = array_values(array_filter($activeRoster, function($row) use ($stage) {
                $weeks = (int)($row['calculated_aog']['weeks'] ?? 0);
                if ($stage === 'near_term') return $weeks >= 37;
                if ($stage === '1st') return $weeks < 14;
                if ($stage === '2nd') return $weeks >= 14 && $weeks < 28;
                if ($stage === '3rd') return $weeks >= 28;
                return true;
            }));
        }

        $recentlyDelivered = $this->prenatalModel->getRecentlyDelivered(90);
        $eligiblePatients = $this->patientModel->getEligibleMaternalPatients();

        $this->view('maternal/index', [
            'activeRoster' => $activeRoster,
            'recentlyDelivered' => $recentlyDelivered,
            'eligiblePatients' => $eligiblePatients,
            'search' => $search,
            'stage' => $stage
        ]);
    }

    /**
     * Display the Individual Maternal Care Workstation for a patient.
     * 
     * @param int $patientId
     */
    public function show($patientId) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $patient = $this->patientModel->findById($patientId);
        if (!$patient) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        if (strtolower($patient['sex'] ?? '') !== 'female') {
            $_SESSION['error_message'] = 'Maternal care workstation is only applicable for female patients.';
            $this->redirect("/patients/{$patientId}");
            return;
        }

        $activePrenatal = $this->prenatalModel->findActiveByPatientId($patientId);
        $prenatalVisits = [];
        if ($activePrenatal) {
            $prenatalVisits = $this->visitModel->findByPrenatalId($activePrenatal['id']);
        }

        $pastDeliveries = $this->pastObstetricModel->findByPatientId($patientId);
        $allPrenatalEpisodes = $this->prenatalModel->findAllByPatientId($patientId);
        $medicalHistory = $this->medicalHistoryModel->findByPatientId($patientId);
        $latestVitals = $this->vitalsModel->latestByPatientId($patientId);

        $pastEpisodesVisits = [];
        if (!empty($allPrenatalEpisodes)) {
            foreach ($allPrenatalEpisodes as $ep) {
                if (empty($ep['is_active'])) {
                    $pastEpisodesVisits[$ep['id']] = $this->visitModel->findByPrenatalId($ep['id']);
                }
            }
        }

        $this->view('maternal/show', [
            'patient' => $patient,
            'activePrenatal' => $activePrenatal,
            'prenatalVisits' => $prenatalVisits,
            'pastDeliveries' => $pastDeliveries,
            'allPrenatalEpisodes' => $allPrenatalEpisodes,
            'pastEpisodesVisits' => $pastEpisodesVisits,
            'medicalHistory' => $medicalHistory,
            'latestVitals' => $latestVitals
        ]);
    }
}
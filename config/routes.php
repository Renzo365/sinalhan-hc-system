<?php

use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\AdminMiddleware;

return function (\App\Core\Router $router) {
    // Guest Routes
    $router->get('/login', 'AuthController@showLogin', [GuestMiddleware::class]);
    $router->post('/login', 'AuthController@login', [GuestMiddleware::class]);

    // Authenticated Routes
    $router->get('/', 'DashboardController@index', [AuthMiddleware::class]);
    $router->get('/dashboard', 'DashboardController@index', [AuthMiddleware::class]);
    
    // Password Change Policy Enforcement Route
    $router->get('/change-password', 'AuthController@showChangePassword', [AuthMiddleware::class]);
    $router->post('/change-password', 'AuthController@changePassword', [AuthMiddleware::class]);
    
    // Logout & Session Heartbeat Actions
    $router->get('/logout', 'AuthController@logout');
    $router->post('/logout', 'AuthController@logout');
    $router->get('/api/session-ping', 'AuthController@ping', [AuthMiddleware::class]);
    $router->post('/api/session-ping', 'AuthController@ping', [AuthMiddleware::class]);

    // User Profile & Self-Service Account Routes
    $router->get('/profile', 'ProfileController@index', [AuthMiddleware::class]);
    $router->post('/profile/update', 'ProfileController@update', [AuthMiddleware::class]);
    $router->post('/profile/password', 'ProfileController@updatePassword', [AuthMiddleware::class]);

    // Patient Management Routes
    $router->get('/patients', 'PatientController@index', [AuthMiddleware::class]);
    $router->get('/patients/create', 'PatientController@create', [AuthMiddleware::class]);
    $router->post('/patients', 'PatientController@store', [AuthMiddleware::class]);
    $router->get('/patients/check-duplicate', 'PatientController@checkDuplicate', [AuthMiddleware::class]);
    $router->get('/patients/{id}', 'PatientController@show', [AuthMiddleware::class]);
    $router->get('/patients/{id}/edit', 'PatientController@edit', [AuthMiddleware::class]);
    $router->post('/patients/{id}', 'PatientController@update', [AuthMiddleware::class]);
    $router->post('/patients/{id}/medical-history', 'PatientMedicalHistoryController@save', [AuthMiddleware::class]);
    // Maternal Care Workstation Routes
    $router->get('/maternal', 'MaternalController@index', [AuthMiddleware::class]);
    $router->get('/maternal/register', 'PrenatalController@createEpisode', [AuthMiddleware::class]);
    $router->post('/maternal/register', 'PrenatalController@storeEpisode', [AuthMiddleware::class]);
    $router->get('/maternal/episode/{id}/edit', 'PrenatalController@editEpisode', [AuthMiddleware::class]);
    $router->post('/maternal/episode/{id}/edit', 'PrenatalController@updateEpisode', [AuthMiddleware::class]);
    $router->get('/maternal/{id}', 'MaternalController@show', [AuthMiddleware::class]);
    $router->post('/patients/{id}/prenatal/episode', 'PrenatalController@storeEpisode', [AuthMiddleware::class]);
    $router->post('/patients/{id}/past-obstetric', 'PrenatalController@storePastObstetric', [AuthMiddleware::class]);
    $router->post('/past-obstetric/{id}/delete', 'PrenatalController@deletePastObstetric', [AuthMiddleware::class]);
    $router->post('/past-obstetric/{id}/update', 'PrenatalController@updatePastObstetric', [AuthMiddleware::class]);
    $router->post('/prenatal/{id}/update', 'PrenatalController@updateEpisode', [AuthMiddleware::class]);
    $router->post('/prenatal/{id}/visit', 'PrenatalController@storeVisit', [AuthMiddleware::class]);
    $router->post('/prenatal/{id}/conclude', 'PrenatalController@concludeEpisode', [AuthMiddleware::class]);
    $router->post('/prenatal/{id}/cancel', 'PrenatalController@cancelEpisode', [AuthMiddleware::class]);
    $router->post('/prenatal/visit/{id}/delete', 'PrenatalController@deleteVisit', [AuthMiddleware::class]);
    $router->post('/prenatal/visit/{id}/update', 'PrenatalController@updateVisit', [AuthMiddleware::class]);

    // Maternal AJAX Search & Patient Data API
    $router->get('/api/patients/search/female', 'PatientController@searchFemale', [AuthMiddleware::class]);
    $router->get('/api/patients/{id}/maternal-data', 'PatientController@getMaternalData', [AuthMiddleware::class]);

    // PhilHealth PCB Patient Ledger Routes (Page 3)
    $router->post('/patients/{id}/pcb/obligated', 'PcbLedgerController@saveObligated', [AuthMiddleware::class]);
    $router->post('/patients/{id}/pcb/service-log', 'PcbLedgerController@storeLog', [AuthMiddleware::class]);
    $router->post('/pcb/service-log/{id}/delete', 'PcbLedgerController@deleteLog', [AuthMiddleware::class]);
    $router->post('/pcb/service-log/{id}/update', 'PcbLedgerController@updateLog', [AuthMiddleware::class]);

    // Well Baby & Pediatric Routes
    $router->get('/well-baby', 'WellbabyController@index', [AuthMiddleware::class]);
    $router->get('/well-baby/register', 'WellbabyController@register', [AuthMiddleware::class]);
    $router->post('/well-baby/register', 'WellbabyController@store', [AuthMiddleware::class]);
    $router->get('/well-baby/search-infant', 'WellbabyController@searchInfant', [AuthMiddleware::class]);
    $router->get('/well-baby/{id}', 'WellbabyController@show', [AuthMiddleware::class]);
    $router->get('/well-baby/{id}/edit', 'WellbabyController@editBirthRecord', [AuthMiddleware::class]);
    $router->post('/well-baby/{id}/edit', 'WellbabyController@storeBirthRecord', [AuthMiddleware::class]);
    $router->post('/patients/{id}/wellbaby/birth-record', 'WellbabyController@storeBirthRecord', [AuthMiddleware::class]);
    $router->post('/wellbaby/{id}/growth-log', 'WellbabyController@storeGrowthLog', [AuthMiddleware::class]);
    $router->post('/wellbaby/growth-log/{id}/delete', 'WellbabyController@deleteGrowthLog', [AuthMiddleware::class]);
    $router->post('/wellbaby/growth-log/{id}/update', 'WellbabyController@updateGrowthLog', [AuthMiddleware::class]);
    $router->post('/patients/{id}/wellbaby/epi-schedule', 'WellbabyController@batchSaveEPI', [AuthMiddleware::class]);
    $router->post('/patients/{id}/immunizations/record', 'WellbabyController@recordImmunization', [AuthMiddleware::class]);
    $router->post('/immunizations/{id}/delete', 'WellbabyController@deleteImmunization', [AuthMiddleware::class]);
    $router->post('/immunizations/{id}/update', 'WellbabyController@updateImmunization', [AuthMiddleware::class]);

    // Vital Signs Routes
    $router->post('/vital-signs', 'VitalSignsController@store', [AuthMiddleware::class]);
    $router->post('/vital-signs/{id}/delete', 'VitalSignsController@delete', [AuthMiddleware::class]);
    $router->post('/vital-signs/{id}/update', 'VitalSignsController@update', [AuthMiddleware::class]);

    // Consultation Routes
    $router->get('/patients/{id}/consultations/create', 'ConsultationController@create', [AuthMiddleware::class]);
    $router->post('/consultations', 'ConsultationController@store', [AuthMiddleware::class]);
    $router->get('/consultations/{id}', 'ConsultationController@show', [AuthMiddleware::class]);
    $router->get('/consultations/{id}/edit', 'ConsultationController@edit', [AuthMiddleware::class]);
    $router->post('/consultations/{id}', 'ConsultationController@update', [AuthMiddleware::class]);
    $router->post('/consultations/{id}/cancel', 'ConsultationController@cancel', [AuthMiddleware::class]);
    $router->post('/consultations/{id}/archive', 'ConsultationController@archive', [AuthMiddleware::class]);
    $router->post('/archive/consultations/{id}/restore', 'ConsultationController@restore', [AdminMiddleware::class]);

    // Appointment Routes
    $router->get('/appointments', 'AppointmentController@index', [AuthMiddleware::class]);
    $router->get('/appointments/create', 'AppointmentController@create', [AuthMiddleware::class]);
    $router->post('/appointments', 'AppointmentController@store', [AuthMiddleware::class]);
    $router->get('/appointments/check-conflict', 'AppointmentController@checkConflict', [AuthMiddleware::class]);
    $router->get('/appointments/{id}/edit', 'AppointmentController@edit', [AuthMiddleware::class]);
    $router->post('/appointments/{id}', 'AppointmentController@update', [AuthMiddleware::class]);
    $router->post('/appointments/{id}/status', 'AppointmentController@updateStatus', [AuthMiddleware::class]);

    // Queue Routes
    $router->get('/queue', 'QueueController@index', [AuthMiddleware::class]);
    $router->post('/queue', 'QueueController@store', [AuthMiddleware::class]);
    $router->post('/queue/{id}/status', 'QueueController@updateStatus', [AuthMiddleware::class]);
    $router->get('/queue/display', 'QueueController@display');
    $router->get('/queue/display-data', 'QueueController@displayData');

    // Reports Routes
    $router->get('/reports', 'ReportController@index', [AuthMiddleware::class]);
    $router->get('/reports/export', 'ReportController@export', [AuthMiddleware::class]);

    // Audit Logs Routes (Admin Only)
    $router->get('/audit-logs', 'AuditLogController@index', [AdminMiddleware::class]);

    // Backup Routes (Admin Only)
    $router->get('/backup', 'BackupController@index', [AdminMiddleware::class]);
    $router->post('/backup', 'BackupController@store', [AdminMiddleware::class]);
    $router->get('/backup/download', 'BackupController@download', [AdminMiddleware::class]);
    $router->post('/backup/delete', 'BackupController@delete', [AdminMiddleware::class]);

    // Archived Records Hub Routes (Admin Only)
    $router->get('/archive', 'PatientController@archivedIndex', [AdminMiddleware::class]);
    $router->post('/patients/{id}/archive', 'PatientController@archive', [AdminMiddleware::class]);
    $router->get('/archive/patients', 'PatientController@archivedIndex', [AdminMiddleware::class]);
    $router->post('/archive/patients/{id}/restore', 'PatientController@restore', [AdminMiddleware::class]);

    // User Management Routes (Admin Only)
    $router->get('/users', 'UserController@index', [AdminMiddleware::class]);
    $router->get('/users/create', 'UserController@create', [AdminMiddleware::class]);
    $router->post('/users', 'UserController@store', [AdminMiddleware::class]);
    $router->get('/users/{id}/edit', 'UserController@edit', [AdminMiddleware::class]);
    $router->post('/users/{id}', 'UserController@update', [AdminMiddleware::class]);
    $router->post('/users/{id}/reset-password', 'UserController@resetPassword', [AdminMiddleware::class]);
    $router->post('/users/{id}/archive', 'UserController@archive', [AdminMiddleware::class]);
    $router->post('/users/{id}/restore', 'UserController@restore', [AdminMiddleware::class]);
    $router->post('/users/{id}/toggle-status', 'UserController@archive', [AdminMiddleware::class]);
    $router->post('/users/{id}/reset-lockout', 'UserController@resetLockout', [AdminMiddleware::class]);
};



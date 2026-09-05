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
    
    // Logout Action
    $router->get('/logout', 'AuthController@logout');
    $router->post('/logout', 'AuthController@logout');

    // Patient Management Routes
    $router->get('/patients', 'PatientController@index', [AuthMiddleware::class]);
    $router->get('/patients/create', 'PatientController@create', [AuthMiddleware::class]);
    $router->post('/patients', 'PatientController@store', [AuthMiddleware::class]);
    $router->get('/patients/check-duplicate', 'PatientController@checkDuplicate', [AuthMiddleware::class]);
    $router->get('/patients/{id}', 'PatientController@show', [AuthMiddleware::class]);
    $router->get('/patients/{id}/edit', 'PatientController@edit', [AuthMiddleware::class]);
    $router->post('/patients/{id}', 'PatientController@update', [AuthMiddleware::class]);
    $router->post('/patients/{id}/medical-history', 'PatientMedicalHistoryController@save', [AuthMiddleware::class]);
    $router->post('/patients/{id}/prenatal/episode', 'PrenatalController@storeEpisode', [AuthMiddleware::class]);
    $router->post('/patients/{id}/past-obstetric', 'PrenatalController@storePastObstetric', [AuthMiddleware::class]);
    $router->post('/past-obstetric/{id}/delete', 'PrenatalController@deletePastObstetric', [AuthMiddleware::class]);
    $router->post('/prenatal/{id}/update', 'PrenatalController@updateEpisode', [AuthMiddleware::class]);
    $router->post('/prenatal/{id}/visit', 'PrenatalController@storeVisit', [AuthMiddleware::class]);
    $router->post('/prenatal/{id}/conclude', 'PrenatalController@concludeEpisode', [AuthMiddleware::class]);

    // Well Baby & Pediatric Routes
    $router->post('/patients/{id}/wellbaby/birth-record', 'WellbabyController@storeBirthRecord', [AuthMiddleware::class]);
    $router->post('/wellbaby/{id}/growth-log', 'WellbabyController@storeGrowthLog', [AuthMiddleware::class]);
    $router->post('/wellbaby/growth-log/{id}/delete', 'WellbabyController@deleteGrowthLog', [AuthMiddleware::class]);
    $router->post('/patients/{id}/wellbaby/epi-schedule', 'WellbabyController@batchSaveEPI', [AuthMiddleware::class]);
    $router->post('/patients/{id}/immunizations/record', 'WellbabyController@recordImmunization', [AuthMiddleware::class]);

    // Vital Signs Routes
    $router->post('/vital-signs', 'VitalSignsController@store', [AuthMiddleware::class]);

    // Consultation Routes
    $router->get('/patients/{id}/consultations/create', 'ConsultationController@create', [AuthMiddleware::class]);
    $router->post('/consultations', 'ConsultationController@store', [AuthMiddleware::class]);
    $router->get('/consultations/{id}', 'ConsultationController@show', [AuthMiddleware::class]);
    $router->get('/consultations/{id}/edit', 'ConsultationController@edit', [AuthMiddleware::class]);
    $router->post('/consultations/{id}', 'ConsultationController@update', [AuthMiddleware::class]);
    $router->post('/consultations/{id}/cancel', 'ConsultationController@cancel', [AuthMiddleware::class]);

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
    $router->get('/queue/display', 'QueueController@display', [AuthMiddleware::class]);
    $router->get('/queue/display-data', 'QueueController@displayData', [AuthMiddleware::class]);

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

    // Patient Archiving Routes
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
    $router->post('/users/{id}/toggle-status', 'UserController@toggleStatus', [AdminMiddleware::class]);
    $router->post('/users/{id}/reset-lockout', 'UserController@resetLockout', [AdminMiddleware::class]);
};



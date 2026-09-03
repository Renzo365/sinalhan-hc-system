-- Barangay Sinalhan Health Center PMS Database Backup
-- Generated: 2026-09-02 16:35:30
-- --------------------------------------------------

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `appointments`;
CREATE TABLE `appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `program_type` varchar(50) DEFAULT 'General OPD',
  `status` enum('Scheduled','Completed','Cancelled','Missed') NOT NULL DEFAULT 'Scheduled',
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_appointment_created_by` (`created_by`),
  KEY `fk_appointment_updated_by` (`updated_by`),
  KEY `idx_appointment_date_time` (`appointment_date`,`appointment_time`),
  KEY `idx_appointment_status` (`status`),
  KEY `idx_appointment_patient_date` (`patient_id`,`appointment_date`),
  CONSTRAINT `fk_appointment_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_appointment_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_appointment_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `appointments` VALUES('1','1','2026-06-25','18:00:00','Follow-up Visit','General OPD','Completed','Patient requests a follow-up for a high blood pressure check-up','1','1','2026-06-25 14:37:40','2026-06-25 21:39:59');
INSERT INTO `appointments` VALUES('2','75','2026-09-09','09:00:00','Prenatal Follow-up','Prenatal Care','Scheduled','Second trimester prenatal checkup & BP monitoring','1',NULL,'2026-09-02 22:35:16','2026-09-02 22:35:16');
INSERT INTO `appointments` VALUES('3','77','2026-09-09','10:00:00','Routine EPI Vaccine','Well Baby Immunization','Scheduled','Pentavalent 2 and OPV 2 vaccination','1',NULL,'2026-09-02 22:35:16','2026-09-02 22:35:16');
INSERT INTO `appointments` VALUES('4','78','2026-09-09','09:00:00','Prenatal Follow-up','Prenatal Care','Scheduled','Second trimester prenatal checkup & BP monitoring','1',NULL,'2026-09-02 22:35:30','2026-09-02 22:35:30');
INSERT INTO `appointments` VALUES('5','80','2026-09-09','10:00:00','Routine EPI Vaccine','Well Baby Immunization','Scheduled','Pentavalent 2 and OPV 2 vaccination','1',NULL,'2026-09-02 22:35:30','2026-09-02 22:35:30');

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(50) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_audit_user` (`user_id`),
  KEY `idx_audit_logs_search` (`created_at`,`module`,`action`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=368 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `audit_logs` VALUES('1','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-25 13:27:32');
INSERT INTO `audit_logs` VALUES('2','1','admin','PASSWORD_CHANGED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User updated their password on first login.','2026-06-25 13:27:55');
INSERT INTO `audit_logs` VALUES('3','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-25 13:32:10');
INSERT INTO `audit_logs` VALUES('4','2','records_staff','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-25 13:32:36');
INSERT INTO `audit_logs` VALUES('5','2','records_staff','PASSWORD_CHANGED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User updated their password on first login.','2026-06-25 13:33:03');
INSERT INTO `audit_logs` VALUES('6','2','records_staff','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-25 13:33:17');
INSERT INTO `audit_logs` VALUES('7','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-25 13:33:55');
INSERT INTO `audit_logs` VALUES('8','1','admin','PATIENT_REGISTERED','Patients','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Registered new patient: Ralph Lawrence Pore (P-2026-00001)','2026-06-25 13:53:03');
INSERT INTO `audit_logs` VALUES('9','1','admin','PATIENT_UPDATED','Patients','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Updated patient profile: Ralph Lawrence Pore (P-2026-00001)','2026-06-25 13:54:46');
INSERT INTO `audit_logs` VALUES('10','1','admin','VITAL_SIGNS_RECORDED','Patients','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Recorded vital signs for patient: Ralph Lawrence Pore (P-2026-00001)','2026-06-25 13:58:04');
INSERT INTO `audit_logs` VALUES('11','1','admin','VITAL_SIGNS_RECORDED','Patients','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Recorded vital signs for patient: Ralph Lawrence Pore (P-2026-00001)','2026-06-25 13:59:48');
INSERT INTO `audit_logs` VALUES('12','1','admin','CONSULTATION_CREATED','Clinical','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Recorded new consultation SOAP note for patient: Ralph Lawrence Pore (P-2026-00001)','2026-06-25 14:19:08');
INSERT INTO `audit_logs` VALUES('13','1','admin','APPOINTMENT_CREATED','Appointments','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Scheduled appointment for patient: Ralph Lawrence Pore on Jun 25, 2026 at 06:00 PM','2026-06-25 14:37:40');
INSERT INTO `audit_logs` VALUES('14','1','admin','APPOINTMENT_STATUS_UPDATED','Appointments','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Marked appointment ID: 1 for patient: Ralph Lawrence Pore as Completed','2026-06-25 14:39:12');
INSERT INTO `audit_logs` VALUES('15','1','admin','QUEUE_REGISTERED','Queue','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Enqueued patient: Ralph Lawrence Pore (P-2026-00001) with Queue No: 001','2026-06-25 14:58:53');
INSERT INTO `audit_logs` VALUES('16','1','admin','QUEUE_STATUS_UPDATED','Queue','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Updated Queue No: 001 status to: Called for patient ID: 1','2026-06-25 15:01:23');
INSERT INTO `audit_logs` VALUES('17','1','admin','QUEUE_STATUS_UPDATED','Queue','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Updated Queue No: 001 status to: Serving for patient ID: 1','2026-06-25 15:01:43');
INSERT INTO `audit_logs` VALUES('18','1','admin','QUEUE_STATUS_UPDATED','Queue','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Updated Queue No: 001 status to: Completed for patient ID: 1','2026-06-25 15:02:31');
INSERT INTO `audit_logs` VALUES('19','1','admin','QUEUE_REGISTERED','Queue','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Enqueued patient: Ralph Lawrence Pore (P-2026-00001) with Queue No: 002','2026-06-25 15:02:50');
INSERT INTO `audit_logs` VALUES('20','1','admin','QUEUE_STATUS_UPDATED','Queue','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Updated Queue No: 002 status to: Called for patient ID: 1','2026-06-25 15:03:05');
INSERT INTO `audit_logs` VALUES('21','1','admin','QUEUE_STATUS_UPDATED','Queue','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Updated Queue No: 002 status to: Serving for patient ID: 1','2026-06-25 15:03:14');
INSERT INTO `audit_logs` VALUES('22','1','admin','QUEUE_STATUS_UPDATED','Queue','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Updated Queue No: 002 status to: Completed for patient ID: 1','2026-06-25 15:03:23');
INSERT INTO `audit_logs` VALUES('23','1','admin','QUEUE_REGISTERED','Queue','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Enqueued patient: Ralph Lawrence Pore (P-2026-00001) with Queue No: 003','2026-06-25 15:05:55');
INSERT INTO `audit_logs` VALUES('24','1','admin','QUEUE_STATUS_UPDATED','Queue','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Updated Queue No: 003 status to: Cancelled for patient ID: 1','2026-06-25 15:06:48');
INSERT INTO `audit_logs` VALUES('25','1','admin','QUEUE_REGISTERED','Queue','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Enqueued patient: Ralph Lawrence Pore (P-2026-00001) with Queue No: 004','2026-06-25 15:10:21');
INSERT INTO `audit_logs` VALUES('26','1','admin','QUEUE_STATUS_UPDATED','Queue','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Updated Queue No: 004 status to: Called for patient ID: 1','2026-06-25 15:10:30');
INSERT INTO `audit_logs` VALUES('27','1','admin','QUEUE_STATUS_UPDATED','Queue','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Updated Queue No: 004 status to: Serving for patient ID: 1','2026-06-25 15:10:37');
INSERT INTO `audit_logs` VALUES('28','1','admin','QUEUE_STATUS_UPDATED','Queue','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Updated Queue No: 004 status to: Completed for patient ID: 1','2026-06-25 15:10:42');
INSERT INTO `audit_logs` VALUES('29','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-25 21:28:04');
INSERT INTO `audit_logs` VALUES('30','1','admin','APPOINTMENT_UPDATED','Appointments','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Rescheduled appointment ID: 1 for patient ID: 1 to Jun 10, 2026 (Completed)','2026-06-25 21:39:47');
INSERT INTO `audit_logs` VALUES('31','1','admin','APPOINTMENT_UPDATED','Appointments','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Rescheduled appointment ID: 1 for patient ID: 1 to Jun 25, 2026 (Completed)','2026-06-25 21:39:59');
INSERT INTO `audit_logs` VALUES('32','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-25 22:37:55');
INSERT INTO `audit_logs` VALUES('33',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Failed login attempt for username: wqe','2026-06-25 23:13:04');
INSERT INTO `audit_logs` VALUES('34','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-25 23:13:18');
INSERT INTO `audit_logs` VALUES('35','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-25 23:13:36');
INSERT INTO `audit_logs` VALUES('36',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Failed login attempt for username: admin','2026-06-25 23:48:09');
INSERT INTO `audit_logs` VALUES('37','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-25 23:48:16');
INSERT INTO `audit_logs` VALUES('38','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-25 23:56:19');
INSERT INTO `audit_logs` VALUES('39','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-26 00:09:07');
INSERT INTO `audit_logs` VALUES('40','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-26 00:10:02');
INSERT INTO `audit_logs` VALUES('41','2','records_staff','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-26 00:10:52');
INSERT INTO `audit_logs` VALUES('42','2','records_staff','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-26 00:11:47');
INSERT INTO `audit_logs` VALUES('43','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-26 12:47:08');
INSERT INTO `audit_logs` VALUES('44','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-26 13:15:47');
INSERT INTO `audit_logs` VALUES('45',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Failed login attempt for username: admin','2026-06-26 13:31:00');
INSERT INTO `audit_logs` VALUES('46',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Failed login attempt for username: admin','2026-06-26 13:31:08');
INSERT INTO `audit_logs` VALUES('47',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Failed login attempt for username: admin','2026-06-26 13:31:10');
INSERT INTO `audit_logs` VALUES('48',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Failed login attempt for username: admin','2026-06-26 13:31:16');
INSERT INTO `audit_logs` VALUES('49',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Failed login attempt for username: admin','2026-06-26 13:48:42');
INSERT INTO `audit_logs` VALUES('50',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Failed login attempt for username: admin','2026-06-26 13:48:47');
INSERT INTO `audit_logs` VALUES('51',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Failed login attempt for username: admin','2026-06-27 13:10:47');
INSERT INTO `audit_logs` VALUES('52',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Failed login attempt for username: admin','2026-06-27 13:10:50');
INSERT INTO `audit_logs` VALUES('53','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-27 13:10:59');
INSERT INTO `audit_logs` VALUES('54','1','admin','QUEUE_REGISTERED','Queue','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Enqueued patient: Ralph Lawrence Pore (P-2026-00001) with Queue No: 001','2026-06-27 13:15:47');
INSERT INTO `audit_logs` VALUES('55','1','admin','QUEUE_STATUS_UPDATED','Queue','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Updated Queue No: 001 status to: Called for patient ID: 1','2026-06-27 13:16:13');
INSERT INTO `audit_logs` VALUES('56','1','admin','QUEUE_STATUS_UPDATED','Queue','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Updated Queue No: 001 status to: Serving for patient ID: 1','2026-06-27 13:16:15');
INSERT INTO `audit_logs` VALUES('57','1','admin','QUEUE_STATUS_UPDATED','Queue','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Updated Queue No: 001 status to: Completed for patient ID: 1','2026-06-27 13:16:18');
INSERT INTO `audit_logs` VALUES('58','1','admin','QUEUE_REGISTERED','Queue','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Enqueued patient: Ralph Lawrence Pore (P-2026-00001) with Queue No: 002','2026-06-27 13:16:37');
INSERT INTO `audit_logs` VALUES('59','1','admin','QUEUE_STATUS_UPDATED','Queue','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Updated Queue No: 002 status to: Called for patient ID: 1','2026-06-27 13:16:59');
INSERT INTO `audit_logs` VALUES('60','1','admin','QUEUE_STATUS_UPDATED','Queue','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Updated Queue No: 002 status to: Serving for patient ID: 1','2026-06-27 13:17:09');
INSERT INTO `audit_logs` VALUES('61','1','admin','QUEUE_STATUS_UPDATED','Queue','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Updated Queue No: 002 status to: Completed for patient ID: 1','2026-06-27 13:17:19');
INSERT INTO `audit_logs` VALUES('62','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-27 13:34:25');
INSERT INTO `audit_logs` VALUES('63','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-27 13:34:32');
INSERT INTO `audit_logs` VALUES('64','1','admin','PATIENT_ARCHIVED','Patients','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Archived patient: Ralph Lawrence Pore (P-2026-00001). Reason: duplicate','2026-06-27 13:37:58');
INSERT INTO `audit_logs` VALUES('65','1','admin','PATIENT_RESTORED','Patients','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Restored patient: Ralph Lawrence Pore (P-2026-00001)','2026-06-27 13:38:07');
INSERT INTO `audit_logs` VALUES('66','1','admin','PATIENT_UPDATED','Patients','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Updated patient profile: Ralph Lawrence Pore (P-2026-00001)','2026-06-27 13:53:11');
INSERT INTO `audit_logs` VALUES('67','1','admin','PATIENT_UPDATED','Patients','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Updated patient profile: Ralph Lawrence Pore (P-2026-00001)','2026-06-27 13:53:32');
INSERT INTO `audit_logs` VALUES('68','1','admin','REPORT_GENERATED','Reports','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Generated report type: registrations from 2026-06-01 to 2026-06-27','2026-06-27 13:55:42');
INSERT INTO `audit_logs` VALUES('69','1','admin','REPORT_EXPORTED','Reports','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Exported report type: registrations as CSV from 2026-06-01 to 2026-06-27','2026-06-27 13:55:44');
INSERT INTO `audit_logs` VALUES('70','1','admin','BACKUP_CREATED','Backup','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Created database backup: backup-2026-06-27_13-56-40.sql','2026-06-27 13:56:40');
INSERT INTO `audit_logs` VALUES('71','1','admin','BACKUP_DELETED','Backup','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Deleted database backup file: backup-2026-06-27_13-56-40.sql','2026-06-27 13:56:45');
INSERT INTO `audit_logs` VALUES('72','1','admin','REPORT_GENERATED','Reports','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Generated report type: daily_visits from 2026-06-01 to 2026-06-27','2026-06-27 14:09:22');
INSERT INTO `audit_logs` VALUES('73','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-27 14:47:23');
INSERT INTO `audit_logs` VALUES('74',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Failed login attempt (non-existent username) for: jdoe','2026-06-27 14:47:29');
INSERT INTO `audit_logs` VALUES('75','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-27 14:47:38');
INSERT INTO `audit_logs` VALUES('76','1','admin','USER_CREATED','Users','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Created new user account: jdoe (John Doe) with role: staff','2026-06-27 14:53:32');
INSERT INTO `audit_logs` VALUES('77','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-27 14:53:51');
INSERT INTO `audit_logs` VALUES('78','5','jdoe','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-27 14:53:56');
INSERT INTO `audit_logs` VALUES('79','5','jdoe','PASSWORD_CHANGED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User updated their password on first login.','2026-06-27 14:54:26');
INSERT INTO `audit_logs` VALUES('80','5','jdoe','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-27 14:55:19');
INSERT INTO `audit_logs` VALUES('81',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Failed login attempt (password mismatch) for username: admin. Failed attempts count: 1/5','2026-06-27 14:55:26');
INSERT INTO `audit_logs` VALUES('82','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-27 14:55:43');
INSERT INTO `audit_logs` VALUES('83','1','admin','REPORT_GENERATED','Reports','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Generated report type: consultations from 2026-06-01 to 2026-06-27','2026-06-27 15:43:57');
INSERT INTO `audit_logs` VALUES('84','1','admin','REPORT_EXPORTED','Reports','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Exported report type: consultations as CSV from 2026-06-01 to 2026-06-27','2026-06-27 15:44:10');
INSERT INTO `audit_logs` VALUES('85','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-27 21:18:10');
INSERT INTO `audit_logs` VALUES('86','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-27 21:29:52');
INSERT INTO `audit_logs` VALUES('87',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Failed login attempt (password mismatch) for username: admin. Failed attempts count: 1/5','2026-06-27 21:36:19');
INSERT INTO `audit_logs` VALUES('88','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-27 21:36:30');
INSERT INTO `audit_logs` VALUES('89','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-27 22:26:08');
INSERT INTO `audit_logs` VALUES('90','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-27 22:26:16');
INSERT INTO `audit_logs` VALUES('91','1','admin','QUEUE_REGISTERED','Queue','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Enqueued patient: Ralph Lawrence Pore (P-2026-00001) with Queue No: 003','2026-06-27 22:37:24');
INSERT INTO `audit_logs` VALUES('92','1','admin','QUEUE_STATUS_UPDATED','Queue','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Updated Queue No: 003 status to: Cancelled for patient ID: 1','2026-06-27 22:37:53');
INSERT INTO `audit_logs` VALUES('93','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-27 22:52:09');
INSERT INTO `audit_logs` VALUES('94','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-27 22:52:20');
INSERT INTO `audit_logs` VALUES('95','1','admin','PATIENT_REGISTERED','Patients','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Registered new patient: 123 1341 (P-2026-00002)','2026-06-27 22:53:42');
INSERT INTO `audit_logs` VALUES('96','1','admin','PATIENT_ARCHIVED','Patients','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Archived patient: 123 1341 (P-2026-00002). Reason: error','2026-06-27 22:53:50');
INSERT INTO `audit_logs` VALUES('97','1','admin','REPORT_GENERATED','Reports','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Generated report type: vitals from 2026-06-01 to 2026-06-27','2026-06-27 22:55:48');
INSERT INTO `audit_logs` VALUES('98','1','admin','REPORT_GENERATED','Reports','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Generated report type: daily_visits from 2026-06-01 to 2026-06-27','2026-06-27 22:55:57');
INSERT INTO `audit_logs` VALUES('99',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Failed login attempt (password mismatch) for username: admin. Failed attempts count: 1/5','2026-06-28 11:03:10');
INSERT INTO `audit_logs` VALUES('100',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Failed login attempt (password mismatch) for username: admin. Failed attempts count: 2/5','2026-06-28 11:03:12');
INSERT INTO `audit_logs` VALUES('101',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Failed login attempt (password mismatch) for username: admin. Failed attempts count: 3/5','2026-06-28 11:03:14');
INSERT INTO `audit_logs` VALUES('102',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Failed login attempt (password mismatch) for username: admin. Failed attempts count: 4/5','2026-06-28 11:03:33');
INSERT INTO `audit_logs` VALUES('104',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Failed login attempt (password mismatch) for username: admin. Failed attempts count: 1/5','2026-06-28 11:22:26');
INSERT INTO `audit_logs` VALUES('105',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Failed login attempt (password mismatch) for username: admin. Failed attempts count: 2/5','2026-06-28 11:22:34');
INSERT INTO `audit_logs` VALUES('106',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Failed login attempt (non-existent username) for: wewq','2026-06-28 11:22:47');
INSERT INTO `audit_logs` VALUES('107','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-28 11:23:21');
INSERT INTO `audit_logs` VALUES('108','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-28 11:23:40');
INSERT INTO `audit_logs` VALUES('109',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Failed login attempt (password mismatch) for username: jdoe. Failed attempts count: 1/5','2026-06-28 11:23:47');
INSERT INTO `audit_logs` VALUES('110','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-28 11:23:56');
INSERT INTO `audit_logs` VALUES('111','1','admin','USER_DELETED','Users','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Soft-deleted user account: jdoe','2026-06-28 11:25:03');
INSERT INTO `audit_logs` VALUES('112','1','admin','USER_CREATED','Users','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Created new user account: jdoe (John Doe) with role: staff','2026-06-28 11:34:30');
INSERT INTO `audit_logs` VALUES('113','1','admin','USER_UPDATED','Users','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Updated user account settings for: jdoe. Name: John Doe, Role: staff, Status: inactive','2026-06-28 11:34:58');
INSERT INTO `audit_logs` VALUES('114','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-28 11:35:10');
INSERT INTO `audit_logs` VALUES('115',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Blocked login attempt: inactive account (jdoe)','2026-06-28 11:35:17');
INSERT INTO `audit_logs` VALUES('116',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Failed login attempt (password mismatch) for username: admin. Failed attempts count: 1/5','2026-06-28 11:35:29');
INSERT INTO `audit_logs` VALUES('117','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-28 11:35:34');
INSERT INTO `audit_logs` VALUES('118','1','admin','USER_UPDATED','Users','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Updated user account settings for: jdoe. Name: John Doe, Role: admin, Status: inactive','2026-06-28 11:56:28');
INSERT INTO `audit_logs` VALUES('119','1','admin','USER_UPDATED','Users','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Updated user account settings for: jdoe. Name: John Doe, Role: admin, Status: active','2026-06-28 11:57:36');
INSERT INTO `audit_logs` VALUES('120','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-28 11:57:42');
INSERT INTO `audit_logs` VALUES('121','8','jdoe','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-28 11:57:57');
INSERT INTO `audit_logs` VALUES('122','8','jdoe','PASSWORD_CHANGED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User updated their password on first login.','2026-06-28 11:58:19');
INSERT INTO `audit_logs` VALUES('127','8','jdoe','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-28 12:20:12');
INSERT INTO `audit_logs` VALUES('128','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-28 12:20:19');
INSERT INTO `audit_logs` VALUES('129','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-28 12:20:39');
INSERT INTO `audit_logs` VALUES('130','8','jdoe','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-28 12:20:49');
INSERT INTO `audit_logs` VALUES('131','8','jdoe','SECURITY_VIOLATION','Users','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Blocked attempt by user ID 8 to modify primary administrator details.','2026-06-28 12:21:50');
INSERT INTO `audit_logs` VALUES('132','8','jdoe','SECURITY_VIOLATION','Users','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Blocked attempt by user ID 8 to modify primary administrator details.','2026-06-28 12:22:23');
INSERT INTO `audit_logs` VALUES('133','8','jdoe','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-28 12:27:35');
INSERT INTO `audit_logs` VALUES('134','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-28 12:27:46');
INSERT INTO `audit_logs` VALUES('135','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-28 12:28:10');
INSERT INTO `audit_logs` VALUES('136','8','jdoe','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-28 12:28:51');
INSERT INTO `audit_logs` VALUES('137','8','jdoe','USER_CREATED','Users','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Created new user account: ralph20 (Ralph Lawrence Pore) with role: admin','2026-06-28 12:33:27');
INSERT INTO `audit_logs` VALUES('138','8','jdoe','USER_UPDATED','Users','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Updated user account settings for: jdoe. Name: John Doe, Role: admin, Status: active','2026-06-28 12:50:12');
INSERT INTO `audit_logs` VALUES('139','8','jdoe','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-28 12:55:36');
INSERT INTO `audit_logs` VALUES('140','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-28 12:55:43');
INSERT INTO `audit_logs` VALUES('141','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-28 12:56:41');
INSERT INTO `audit_logs` VALUES('142','11','ralph20','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-28 12:56:52');
INSERT INTO `audit_logs` VALUES('143','11','ralph20','PASSWORD_CHANGED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User updated their password on first login.','2026-06-28 12:57:10');
INSERT INTO `audit_logs` VALUES('144','11','ralph20','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-28 13:03:04');
INSERT INTO `audit_logs` VALUES('145',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Failed login attempt (password mismatch) for username: admin. Failed attempts count: 1/5','2026-06-28 13:03:09');
INSERT INTO `audit_logs` VALUES('146','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-28 13:03:17');
INSERT INTO `audit_logs` VALUES('147','1','admin','USER_UPDATED','Users','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Updated user account settings for: ralph20. Name: Ralph Lawrence Pore, Role: admin, Status: active','2026-06-28 13:03:30');
INSERT INTO `audit_logs` VALUES('148','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-28 13:03:38');
INSERT INTO `audit_logs` VALUES('149',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Failed login attempt (password mismatch) for username: ralph20. Failed attempts count: 1/5','2026-06-28 13:03:51');
INSERT INTO `audit_logs` VALUES('150','11','ralph20','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-28 13:03:56');
INSERT INTO `audit_logs` VALUES('151','11','ralph20','PASSWORD_CHANGED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User updated their password on first login.','2026-06-28 13:04:14');
INSERT INTO `audit_logs` VALUES('152','11','ralph20','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-28 13:04:38');
INSERT INTO `audit_logs` VALUES('153',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Failed login attempt (non-existent username) for: ralphp20','2026-06-28 13:04:51');
INSERT INTO `audit_logs` VALUES('154',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Failed login attempt (non-existent username) for: ralphp20','2026-06-28 13:04:58');
INSERT INTO `audit_logs` VALUES('155','11','ralph20','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-28 13:05:09');
INSERT INTO `audit_logs` VALUES('156','11','ralph20','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-28 13:05:14');
INSERT INTO `audit_logs` VALUES('157','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-28 13:05:24');
INSERT INTO `audit_logs` VALUES('158','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-28 13:06:39');
INSERT INTO `audit_logs` VALUES('159','11','ralph20','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-28 13:06:48');
INSERT INTO `audit_logs` VALUES('160','11','ralph20','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-28 13:09:17');
INSERT INTO `audit_logs` VALUES('161',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Failed login attempt (password mismatch) for username: admin. Failed attempts count: 1/5','2026-06-28 13:09:24');
INSERT INTO `audit_logs` VALUES('162','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-28 13:09:29');
INSERT INTO `audit_logs` VALUES('163','1','admin','USER_UPDATED','Users','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Updated user account settings for: ralph20. Name: Ralph Lawrence Pore, Role: staff, Status: active','2026-06-28 13:38:00');
INSERT INTO `audit_logs` VALUES('164','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-28 13:38:27');
INSERT INTO `audit_logs` VALUES('165',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Failed login attempt (password mismatch) for username: admin. Failed attempts count: 1/5','2026-06-28 13:38:43');
INSERT INTO `audit_logs` VALUES('166','8','jdoe','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-28 13:38:54');
INSERT INTO `audit_logs` VALUES('167','8','jdoe','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-28 13:39:13');
INSERT INTO `audit_logs` VALUES('168','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-28 13:39:21');
INSERT INTO `audit_logs` VALUES('169','1','admin','USER_UPDATED','Users','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Updated user account settings for: ralph20. Name: Ralph Lawrence Pore, Role: admin, Status: active','2026-06-28 13:39:33');
INSERT INTO `audit_logs` VALUES('170','1','admin','USER_UPDATED','Users','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Updated user account settings for: jdoe. Name: John Doe, Role: staff, Status: active','2026-06-28 13:46:24');
INSERT INTO `audit_logs` VALUES('171','1','admin','USER_UPDATED','Users','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Updated user account settings for: ralph20. Name: Ralph Lawrence Pore, Role: staff, Status: active','2026-06-28 13:46:29');
INSERT INTO `audit_logs` VALUES('172','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-28 13:51:11');
INSERT INTO `audit_logs` VALUES('173','11','ralph20','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-28 13:51:30');
INSERT INTO `audit_logs` VALUES('174','11','ralph20','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-28 13:53:09');
INSERT INTO `audit_logs` VALUES('175','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-28 13:53:20');
INSERT INTO `audit_logs` VALUES('176','1','admin','USER_UPDATED','Users','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Updated user account settings for: ralph20. Name: Ralph Lawrence Pore, Role: admin, Status: active','2026-06-28 13:53:46');
INSERT INTO `audit_logs` VALUES('177','1','admin','PATIENT_RESTORED','Patients','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Restored patient: 123 1341 (P-2026-00002)','2026-06-28 13:55:56');
INSERT INTO `audit_logs` VALUES('178','1','admin','PATIENT_ARCHIVED','Patients','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Archived patient: 123 1341 (P-2026-00002). Reason: error','2026-06-28 13:56:07');
INSERT INTO `audit_logs` VALUES('179','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-28 13:56:20');
INSERT INTO `audit_logs` VALUES('180','11','ralph20','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-28 13:56:27');
INSERT INTO `audit_logs` VALUES('181','11','ralph20','PATIENT_RESTORED','Patients','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Restored patient: 123 1341 (P-2026-00002)','2026-06-28 13:56:33');
INSERT INTO `audit_logs` VALUES('182','11','ralph20','PATIENT_ARCHIVED','Patients','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Archived patient: 123 1341 (P-2026-00002). Reason: error','2026-06-28 13:57:43');
INSERT INTO `audit_logs` VALUES('183','11','ralph20','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-28 13:58:22');
INSERT INTO `audit_logs` VALUES('184','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-28 13:58:27');
INSERT INTO `audit_logs` VALUES('185','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-28 14:02:09');
INSERT INTO `audit_logs` VALUES('186','11','ralph20','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-28 14:02:19');
INSERT INTO `audit_logs` VALUES('187','11','ralph20','USER_UPDATED','Users','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Updated user account settings for: ralph20. Name: Ralph Lawrence Pore, Role: admin, Status: active','2026-06-28 14:08:43');
INSERT INTO `audit_logs` VALUES('188','11','ralph20','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User logged out.','2026-06-28 14:09:53');
INSERT INTO `audit_logs` VALUES('189','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-28 14:10:07');
INSERT INTO `audit_logs` VALUES('190','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','User successfully logged in.','2026-06-28 21:15:48');
INSERT INTO `audit_logs` VALUES('191','1','admin','REPORT_GENERATED','Reports','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Generated report type: queue_summary from 2026-06-01 to 2026-06-28','2026-06-28 21:24:31');
INSERT INTO `audit_logs` VALUES('192','1','admin','USER_UPDATED','Users','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','Updated user account settings for: admin. Name: System Administrator, Role: admin, Status: active','2026-06-28 21:42:42');
INSERT INTO `audit_logs` VALUES('193','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','User successfully logged in.','2026-07-03 14:44:07');
INSERT INTO `audit_logs` VALUES('194','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','User successfully logged in.','2026-07-06 22:25:39');
INSERT INTO `audit_logs` VALUES('195','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','User successfully logged in.','2026-07-12 13:56:18');
INSERT INTO `audit_logs` VALUES('196','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','User successfully logged in.','2026-07-14 12:02:31');
INSERT INTO `audit_logs` VALUES('197',NULL,NULL,'LOGIN_FAILED','Auth','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0','Failed login attempt (password mismatch) for username: admin. Failed attempts count: 1/5','2026-07-28 16:20:57');
INSERT INTO `audit_logs` VALUES('198',NULL,NULL,'LOGIN_FAILED','Auth','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0','Failed login attempt (password mismatch) for username: admin. Failed attempts count: 2/5','2026-07-28 16:21:09');
INSERT INTO `audit_logs` VALUES('199',NULL,NULL,'LOGIN_FAILED','Auth','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0','Failed login attempt (password mismatch) for username: admin. Failed attempts count: 3/5','2026-07-28 16:21:17');
INSERT INTO `audit_logs` VALUES('200','1','admin','LOGIN_SUCCESS','Auth','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0','User successfully logged in.','2026-07-28 16:21:22');
INSERT INTO `audit_logs` VALUES('201',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','Failed login attempt (password mismatch) for username: admin. Failed attempts count: 1/5','2026-07-29 20:48:13');
INSERT INTO `audit_logs` VALUES('202','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','User successfully logged in.','2026-07-29 20:48:24');
INSERT INTO `audit_logs` VALUES('203','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','User logged out.','2026-07-29 21:25:54');
INSERT INTO `audit_logs` VALUES('204',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Failed login attempt (non-existent username) for: er','2026-08-03 22:18:40');
INSERT INTO `audit_logs` VALUES('205','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User successfully logged in.','2026-08-03 22:38:18');
INSERT INTO `audit_logs` VALUES('206','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User successfully logged in.','2026-08-04 21:20:45');
INSERT INTO `audit_logs` VALUES('207','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User successfully logged in.','2026-08-08 22:02:12');
INSERT INTO `audit_logs` VALUES('208','1','admin','USER_PASSWORD_RESET','Users','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Administrative password reset for account: ralph20','2026-08-08 22:16:38');
INSERT INTO `audit_logs` VALUES('209','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User logged out.','2026-08-08 22:16:45');
INSERT INTO `audit_logs` VALUES('210','11','ralph20','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User successfully logged in.','2026-08-08 22:16:56');
INSERT INTO `audit_logs` VALUES('211','11','ralph20','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User logged out.','2026-08-08 22:19:08');
INSERT INTO `audit_logs` VALUES('212','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User successfully logged in.','2026-08-08 22:19:42');
INSERT INTO `audit_logs` VALUES('213','1','admin','USER_UPDATED','Users','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Updated user account settings for: midwife_user. Name: Juana Dela Cruz, Role: staff, Status: inactive','2026-08-08 22:28:25');
INSERT INTO `audit_logs` VALUES('214','1','admin','USER_UPDATED','Users','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Updated user account settings for: midwife_user. Name: Juana Dela Cruz, Role: staff, Status: suspended','2026-08-08 22:28:29');
INSERT INTO `audit_logs` VALUES('215',NULL,'system','USER_UNLOCKED','Auth','127.0.0.1','CLI','CLI administrative unlock executed for username: test_nurse_1786199990. Current status restored to Active.','2026-08-08 22:39:50');
INSERT INTO `audit_logs` VALUES('216','1','admin','USER_UPDATED','Users','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Updated user account settings for: admin. Name: System Administrator, Role: admin','2026-08-08 22:44:35');
INSERT INTO `audit_logs` VALUES('217','1','admin','USER_ACTIVATED','Users','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Toggled status of user account midwife_user to active.','2026-08-08 22:47:25');
INSERT INTO `audit_logs` VALUES('218','1','admin','USER_DEACTIVATED','Users','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Toggled status of user account midwife_user to inactive.','2026-08-08 22:47:29');
INSERT INTO `audit_logs` VALUES('219','1','admin','PATIENT_UPDATED','Patients','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Updated patient profile: Ralph Lawrence Pore (P-2026-00001)','2026-08-08 22:51:34');
INSERT INTO `audit_logs` VALUES('220','1','admin','PATIENT_ARCHIVED','Patients','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Archived patient: Maria Cruz (P-2026-00004). Reason: duplicate record','2026-08-08 23:07:44');
INSERT INTO `audit_logs` VALUES('221',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Failed login attempt (password mismatch) for username: admin. Failed attempts count: 1/5','2026-08-09 19:45:17');
INSERT INTO `audit_logs` VALUES('222',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Failed login attempt (password mismatch) for username: admin. Failed attempts count: 2/5','2026-08-09 19:45:33');
INSERT INTO `audit_logs` VALUES('223','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User successfully logged in.','2026-08-09 19:49:05');
INSERT INTO `audit_logs` VALUES('224','1','admin','USER_UPDATED','Users','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Updated user account settings for: midwife_user. Name: Juana Dela Cruz, Role: staff','2026-08-09 19:56:09');
INSERT INTO `audit_logs` VALUES('225','1','admin','SESSION_TIMEOUT','Auth','127.0.0.1',NULL,'Test session expired due to inactivity.','2026-08-09 22:55:28');
INSERT INTO `audit_logs` VALUES('226',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Failed login attempt (non-existent username) for: 12','2026-08-09 22:59:58');
INSERT INTO `audit_logs` VALUES('227','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User successfully logged in.','2026-08-09 23:01:59');
INSERT INTO `audit_logs` VALUES('228','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User logged out.','2026-08-09 23:02:48');
INSERT INTO `audit_logs` VALUES('229',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Failed login attempt (non-existent username) for: er','2026-08-09 23:10:20');
INSERT INTO `audit_logs` VALUES('230',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Failed login attempt (password mismatch) for username: admin. Failed attempts count: 1/5','2026-08-09 23:10:30');
INSERT INTO `audit_logs` VALUES('231',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Failed login attempt (password mismatch) for username: admin. Failed attempts count: 2/5','2026-08-09 23:22:23');
INSERT INTO `audit_logs` VALUES('232','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User successfully logged in.','2026-08-09 23:22:28');
INSERT INTO `audit_logs` VALUES('233','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User logged out.','2026-08-09 23:37:26');
INSERT INTO `audit_logs` VALUES('234',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Failed login attempt (non-existent username) for: ad','2026-08-09 23:37:29');
INSERT INTO `audit_logs` VALUES('235',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Failed login attempt (password mismatch) for username: admin. Failed attempts count: 1/5','2026-08-09 23:37:35');
INSERT INTO `audit_logs` VALUES('236','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User successfully logged in.','2026-08-09 23:37:42');
INSERT INTO `audit_logs` VALUES('237','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User logged out.','2026-08-09 23:38:22');
INSERT INTO `audit_logs` VALUES('238',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Failed login attempt (password mismatch) for username: jdoe. Failed attempts count: 1/5','2026-08-09 23:38:26');
INSERT INTO `audit_logs` VALUES('239',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Failed login attempt (password mismatch) for username: jdoe. Failed attempts count: 2/5','2026-08-09 23:38:28');
INSERT INTO `audit_logs` VALUES('240',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Failed login attempt (non-existent username) for: 1jdoe','2026-08-09 23:38:31');
INSERT INTO `audit_logs` VALUES('241',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Failed login attempt (password mismatch) for username: jdoe. Failed attempts count: 3/5','2026-08-09 23:38:35');
INSERT INTO `audit_logs` VALUES('242',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Failed login attempt (password mismatch) for username: jdoe. Failed attempts count: 4/5','2026-08-09 23:38:37');
INSERT INTO `audit_logs` VALUES('243',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Failed login attempt (password mismatch) for username: jdoe. Failed attempts count: 5/5','2026-08-09 23:38:39');
INSERT INTO `audit_logs` VALUES('244',NULL,NULL,'ACCOUNT_LOCKED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Account temporarily locked for 15 minutes due to 5 consecutive failed attempts: jdoe','2026-08-09 23:38:39');
INSERT INTO `audit_logs` VALUES('245',NULL,NULL,'LOGIN_BLOCKED_LOCKOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Blocked login attempt during temporary lockout for username: jdoe','2026-08-09 23:38:41');
INSERT INTO `audit_logs` VALUES('246',NULL,NULL,'LOGIN_BLOCKED_LOCKOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Blocked login attempt during temporary lockout for username: jdoe','2026-08-09 23:38:52');
INSERT INTO `audit_logs` VALUES('247','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User successfully logged in.','2026-08-09 23:39:03');
INSERT INTO `audit_logs` VALUES('248','1','admin','USER_LOCKOUT_RESET','Users','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Administrative lockout override executed for user: jdoe. Failed attempts reset to 0.','2026-08-09 23:39:47');
INSERT INTO `audit_logs` VALUES('249','1','admin','PATIENT_UPDATED','Patients','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Updated patient profile: Ralph Lawrence Pore (P-2026-00001)','2026-08-09 23:42:45');
INSERT INTO `audit_logs` VALUES('250','1','admin','USER_ACTIVATED','Users','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Toggled status of user account jdoe_deleted_1782638703 to active.','2026-08-09 23:47:29');
INSERT INTO `audit_logs` VALUES('251','1','admin','USER_DEACTIVATED','Users','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Toggled status of user account jdoe_deleted_1782638703 to inactive.','2026-08-10 00:04:18');
INSERT INTO `audit_logs` VALUES('252','1','admin','SESSION_TIMEOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Session expired due to inactivity for user: admin','2026-08-10 00:23:51');
INSERT INTO `audit_logs` VALUES('253','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User successfully logged in.','2026-08-10 00:29:26');
INSERT INTO `audit_logs` VALUES('254','1','admin','USER_UPDATED','Users','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Updated user account settings for: admin. Name: System Administrator, Role: admin','2026-08-10 00:29:35');
INSERT INTO `audit_logs` VALUES('255','1','admin','SESSION_TIMEOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Session expired due to inactivity for user: admin','2026-08-10 00:44:50');
INSERT INTO `audit_logs` VALUES('256','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User successfully logged in.','2026-08-10 09:39:46');
INSERT INTO `audit_logs` VALUES('257','1','admin','SESSION_TIMEOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Session expired due to inactivity for user: admin','2026-08-10 10:01:56');
INSERT INTO `audit_logs` VALUES('258',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Failed login attempt (password mismatch) for username: admin. Failed attempts count: 1/5','2026-08-10 10:02:15');
INSERT INTO `audit_logs` VALUES('259','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User successfully logged in.','2026-08-10 10:02:37');
INSERT INTO `audit_logs` VALUES('260','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User logged out.','2026-08-10 10:02:45');
INSERT INTO `audit_logs` VALUES('261','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User successfully logged in.','2026-08-10 10:02:54');
INSERT INTO `audit_logs` VALUES('262','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User logged out.','2026-08-10 10:03:56');
INSERT INTO `audit_logs` VALUES('263','8','jdoe','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User successfully logged in.','2026-08-10 10:04:01');
INSERT INTO `audit_logs` VALUES('264','8','jdoe','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User logged out.','2026-08-10 10:04:26');
INSERT INTO `audit_logs` VALUES('265','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User successfully logged in.','2026-08-10 20:47:39');
INSERT INTO `audit_logs` VALUES('266','1','admin','REPORT_GENERATED','Reports','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Generated report type: consultations from 2026-08-01 to 2026-08-10','2026-08-10 20:49:59');
INSERT INTO `audit_logs` VALUES('267','1','admin','REPORT_GENERATED','Reports','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Generated report type: daily_visits from 2026-08-01 to 2026-08-10','2026-08-10 20:50:03');
INSERT INTO `audit_logs` VALUES('268','1','admin','REPORT_GENERATED','Reports','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Generated report type: vitals from 2026-08-01 to 2026-08-10','2026-08-10 20:50:06');
INSERT INTO `audit_logs` VALUES('269','1','admin','REPORT_GENERATED','Reports','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Generated report type: vitals from 2026-06-01 to 2026-08-10','2026-08-10 20:50:14');
INSERT INTO `audit_logs` VALUES('270','1','admin','SESSION_TIMEOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Session expired due to inactivity for user: admin','2026-08-10 21:05:55');
INSERT INTO `audit_logs` VALUES('271','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User successfully logged in.','2026-08-10 21:06:14');
INSERT INTO `audit_logs` VALUES('272','1','admin','SESSION_TIMEOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Session expired due to inactivity for user: admin','2026-08-10 21:36:39');
INSERT INTO `audit_logs` VALUES('273','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User successfully logged in.','2026-08-10 22:20:45');
INSERT INTO `audit_logs` VALUES('274','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User logged out.','2026-08-10 22:23:48');
INSERT INTO `audit_logs` VALUES('275',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Failed login attempt (non-existent username) for: w','2026-08-10 22:24:06');
INSERT INTO `audit_logs` VALUES('276','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User successfully logged in.','2026-08-10 22:24:14');
INSERT INTO `audit_logs` VALUES('277','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User logged out.','2026-08-10 22:24:17');
INSERT INTO `audit_logs` VALUES('278','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User successfully logged in.','2026-08-10 22:26:20');
INSERT INTO `audit_logs` VALUES('279','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User logged out.','2026-08-10 22:26:24');
INSERT INTO `audit_logs` VALUES('280','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User successfully logged in.','2026-08-10 22:26:40');
INSERT INTO `audit_logs` VALUES('281','1','admin','SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Exception: Test Verification Exception: Global Error Handler & 500 View Working as Intended! in DebugController.php:13','2026-08-10 22:26:42');
INSERT INTO `audit_logs` VALUES('282','1','admin','SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Exception: Test Verification Exception: Global Error Handler & 500 View Working as Intended! in DebugController.php:13','2026-08-10 22:26:56');
INSERT INTO `audit_logs` VALUES('283','1','admin','SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Exception: Test Verification Exception: Global Error Handler & 500 View Working as Intended! in DebugController.php:13','2026-08-10 22:26:57');
INSERT INTO `audit_logs` VALUES('284','1','admin','SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Exception: Test Verification Exception: Global Error Handler & 500 View Working as Intended! in DebugController.php:13','2026-08-10 22:27:01');
INSERT INTO `audit_logs` VALUES('285','1','admin','SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Exception: Test Verification Exception: Global Error Handler & 500 View Working as Intended! in DebugController.php:13','2026-08-10 22:27:33');
INSERT INTO `audit_logs` VALUES('286','1','admin','SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Exception: Test Verification Exception: Global Error Handler & 500 View Working as Intended! in DebugController.php:13','2026-08-10 22:27:37');
INSERT INTO `audit_logs` VALUES('287','1','admin','SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Exception: Test Verification Exception: Global Error Handler & 500 View Working as Intended! in DebugController.php:13','2026-08-10 22:28:48');
INSERT INTO `audit_logs` VALUES('288','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User logged out.','2026-08-10 22:30:00');
INSERT INTO `audit_logs` VALUES('289','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User successfully logged in.','2026-08-10 22:30:30');
INSERT INTO `audit_logs` VALUES('290','1','admin','SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Exception: Test Verification Exception: Global Error Handler & 500 View Working as Intended! in DebugController.php:13','2026-08-10 22:30:33');
INSERT INTO `audit_logs` VALUES('291','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User logged out.','2026-08-10 22:36:09');
INSERT INTO `audit_logs` VALUES('292','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User successfully logged in.','2026-08-10 22:36:23');
INSERT INTO `audit_logs` VALUES('293','1','admin','SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Exception: System Error Test: Demonstrating Global 500 Error Handling & Logging Engine. in AuthController.php:20','2026-08-10 22:36:38');
INSERT INTO `audit_logs` VALUES('294','1','admin','SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Exception: System Error Test: Demonstrating Global 500 Error Handling & Logging Engine. in AuthController.php:20','2026-08-10 22:40:04');
INSERT INTO `audit_logs` VALUES('295','1','admin','SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Exception: System Error Test: Demonstrating Global 500 Error Handling & Logging Engine. in AuthController.php:20','2026-08-10 22:40:21');
INSERT INTO `audit_logs` VALUES('296','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User logged out.','2026-08-10 22:40:55');
INSERT INTO `audit_logs` VALUES('297',NULL,NULL,'SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Exception: System Error Test: Demonstrating Global 500 Error Handling & Logging Engine. in AuthController.php:20','2026-08-10 22:40:57');
INSERT INTO `audit_logs` VALUES('298',NULL,NULL,'SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Exception: System Error Test: Demonstrating Global 500 Error Handling & Logging Engine. in AuthController.php:20','2026-08-10 22:41:03');
INSERT INTO `audit_logs` VALUES('299',NULL,NULL,'SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Exception: System Error Test: Demonstrating Global 500 Error Handling & Logging Engine. in AuthController.php:20','2026-08-10 22:41:40');
INSERT INTO `audit_logs` VALUES('300',NULL,NULL,'SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Exception: System Error Test: Demonstrating Global 500 Error Handling & Logging Engine. in AuthController.php:20','2026-08-10 22:42:15');
INSERT INTO `audit_logs` VALUES('301',NULL,NULL,'SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Exception: System Error Test: Demonstrating Global 500 Error Handling & Logging Engine. in AuthController.php:20','2026-08-10 22:42:45');
INSERT INTO `audit_logs` VALUES('302','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User successfully logged in.','2026-08-10 22:45:33');
INSERT INTO `audit_logs` VALUES('303','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User logged out.','2026-08-10 22:45:40');
INSERT INTO `audit_logs` VALUES('304','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User successfully logged in.','2026-08-10 22:51:07');
INSERT INTO `audit_logs` VALUES('305','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User logged out.','2026-08-10 22:57:37');
INSERT INTO `audit_logs` VALUES('306','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User successfully logged in.','2026-08-11 09:12:35');
INSERT INTO `audit_logs` VALUES('307','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User successfully logged in.','2026-08-16 20:58:12');
INSERT INTO `audit_logs` VALUES('308','1','admin','SESSION_TIMEOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Session expired due to inactivity for user: admin','2026-08-16 21:13:32');
INSERT INTO `audit_logs` VALUES('309','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User successfully logged in.','2026-08-17 11:35:16');
INSERT INTO `audit_logs` VALUES('310','1','admin','SESSION_TIMEOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Session expired due to inactivity for user: admin','2026-08-17 11:58:26');
INSERT INTO `audit_logs` VALUES('311',NULL,NULL,'LOGIN_FAILED','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Failed login attempt (password mismatch) for username: admin. Failed attempts count: 1/5','2026-08-17 12:37:28');
INSERT INTO `audit_logs` VALUES('312','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','User successfully logged in.','2026-08-17 12:37:38');
INSERT INTO `audit_logs` VALUES('313','1','admin','SESSION_TIMEOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Session expired due to inactivity for user: admin','2026-08-17 13:46:32');
INSERT INTO `audit_logs` VALUES('314','1','admin','LOGIN_SUCCESS','Auth','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0','User successfully logged in.','2026-08-17 14:37:42');
INSERT INTO `audit_logs` VALUES('315','1','admin','PATIENT_UPDATED','Patients','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0','Updated patient profile: Limuel Dien Makiyama (P-2026-00003)','2026-08-17 14:38:50');
INSERT INTO `audit_logs` VALUES('316','1','admin','LOGOUT','Auth','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0','User logged out.','2026-08-17 15:00:34');
INSERT INTO `audit_logs` VALUES('317','1','admin','LOGIN_SUCCESS','Auth','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0','User successfully logged in.','2026-08-17 15:03:55');
INSERT INTO `audit_logs` VALUES('318','1','admin','LOGOUT','Auth','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0','User logged out.','2026-08-17 15:13:09');
INSERT INTO `audit_logs` VALUES('319','1','admin','LOGIN_SUCCESS','Auth','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0','User successfully logged in.','2026-08-17 15:24:22');
INSERT INTO `audit_logs` VALUES('320','1','admin','QUEUE_REGISTERED','Queue','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0','Enqueued patient: Limuel Dien Makiyama (P-2026-00003) with Queue No: 001','2026-08-17 15:25:18');
INSERT INTO `audit_logs` VALUES('321','1','admin','SESSION_TIMEOUT','Auth','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0','Session expired due to inactivity for user: admin','2026-08-17 15:53:00');
INSERT INTO `audit_logs` VALUES('322','1','admin','LOGIN_SUCCESS','Auth','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0','User successfully logged in.','2026-08-17 15:53:33');
INSERT INTO `audit_logs` VALUES('323','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','User successfully logged in.','2026-08-30 21:07:43');
INSERT INTO `audit_logs` VALUES('324','1','admin','SESSION_TIMEOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','Session expired due to inactivity for user: admin','2026-08-30 21:33:26');
INSERT INTO `audit_logs` VALUES('325','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','User successfully logged in.','2026-09-02 14:40:48');
INSERT INTO `audit_logs` VALUES('326','1','admin','SESSION_TIMEOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','Session expired due to inactivity for user: admin','2026-09-02 14:59:39');
INSERT INTO `audit_logs` VALUES('327','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','User successfully logged in.','2026-09-02 15:24:45');
INSERT INTO `audit_logs` VALUES('328','1','admin','SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','Error: Class \"App\\Models\\Immunization\" not found in PatientController.php:211','2026-09-02 15:25:12');
INSERT INTO `audit_logs` VALUES('329','1','admin','SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','Error: Class \"App\\Models\\Immunization\" not found in PatientController.php:211','2026-09-02 15:25:14');
INSERT INTO `audit_logs` VALUES('330','1','admin','SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','Error: Class \"App\\Models\\Immunization\" not found in PatientController.php:211','2026-09-02 15:25:17');
INSERT INTO `audit_logs` VALUES('331','1','admin','SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','Error: Class \"App\\Models\\Immunization\" not found in PatientController.php:211','2026-09-02 15:25:22');
INSERT INTO `audit_logs` VALUES('332','1','admin','SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','Error: Class \"App\\Models\\Immunization\" not found in PatientController.php:211','2026-09-02 15:25:29');
INSERT INTO `audit_logs` VALUES('333','1','admin','SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','Error: Class \"App\\Models\\Immunization\" not found in PatientController.php:211','2026-09-02 15:25:30');
INSERT INTO `audit_logs` VALUES('334','1','admin','SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','Error: Class \"App\\Models\\Immunization\" not found in PatientController.php:211','2026-09-02 15:26:18');
INSERT INTO `audit_logs` VALUES('335','1','admin','SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','Error: Class \"App\\Models\\Immunization\" not found in PatientController.php:211','2026-09-02 15:26:20');
INSERT INTO `audit_logs` VALUES('336','1','admin','SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','PDOException: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'pr.status\' in \'where clause\' in QueueEntry.php:27','2026-09-02 15:26:28');
INSERT INTO `audit_logs` VALUES('337','1','admin','SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','PDOException: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'pr.status\' in \'where clause\' in QueueEntry.php:27','2026-09-02 15:26:29');
INSERT INTO `audit_logs` VALUES('338','1','admin','SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','PDOException: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'pr.status\' in \'where clause\' in QueueEntry.php:27','2026-09-02 15:26:31');
INSERT INTO `audit_logs` VALUES('339','1','admin','SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','PDOException: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'pr.status\' in \'where clause\' in QueueEntry.php:27','2026-09-02 15:27:03');
INSERT INTO `audit_logs` VALUES('340','1','admin','SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','PDOException: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'pr.status\' in \'where clause\' in QueueEntry.php:27','2026-09-02 15:27:57');
INSERT INTO `audit_logs` VALUES('341','1','admin','SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','Error: Class \"App\\Models\\Immunization\" not found in PatientController.php:211','2026-09-02 15:28:00');
INSERT INTO `audit_logs` VALUES('342','1','admin','SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','Error: Class \"App\\Models\\Immunization\" not found in PatientController.php:211','2026-09-02 15:28:02');
INSERT INTO `audit_logs` VALUES('343','1','admin','SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','Error: Class \"App\\Models\\Immunization\" not found in PatientController.php:211','2026-09-02 15:28:07');
INSERT INTO `audit_logs` VALUES('344','1','admin','SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','Error: Class \"App\\Models\\Immunization\" not found in PatientController.php:211','2026-09-02 15:29:10');
INSERT INTO `audit_logs` VALUES('345','1','admin','PATIENT_REGISTERED','Patients','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','Registered new patient: Zack Tabudlo (P-2026-00005)','2026-09-02 15:49:59');
INSERT INTO `audit_logs` VALUES('346','1','admin','PATIENT_REGISTERED','Patients','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','Registered new patient: Maria Dela Cruz (P-2026-00006)','2026-09-02 15:52:39');
INSERT INTO `audit_logs` VALUES('347','1','admin','IHP_HISTORY_UPDATED','Patients','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','Updated Individual Health Profile (IHP) medical history for P-2026-00006','2026-09-02 15:58:41');
INSERT INTO `audit_logs` VALUES('348','1','admin','LOGOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','User logged out.','2026-09-02 15:59:47');
INSERT INTO `audit_logs` VALUES('349','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','User successfully logged in.','2026-09-02 16:00:24');
INSERT INTO `audit_logs` VALUES('350','1','admin','SESSION_TIMEOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','Session expired due to inactivity for user: admin','2026-09-02 16:21:13');
INSERT INTO `audit_logs` VALUES('351','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','User successfully logged in.','2026-09-02 16:37:07');
INSERT INTO `audit_logs` VALUES('352','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','User successfully logged in.','2026-09-02 20:14:39');
INSERT INTO `audit_logs` VALUES('353','1','admin','PATIENT_UPDATED','Patients','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','Updated patient profile: Maria Dela Cruz (P-2026-00006)','2026-09-02 20:30:33');
INSERT INTO `audit_logs` VALUES('354','1','admin','PATIENT_UPDATED','Patients','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','Updated patient profile: Zack Tabudlo (P-2026-00005)','2026-09-02 20:30:59');
INSERT INTO `audit_logs` VALUES('355','1','admin','SESSION_TIMEOUT','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','Session expired due to inactivity for user: admin','2026-09-02 21:16:52');
INSERT INTO `audit_logs` VALUES('356','1','admin','LOGIN_SUCCESS','Auth','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','User successfully logged in.','2026-09-02 21:24:18');
INSERT INTO `audit_logs` VALUES('357','1','admin','PATIENT_UPDATED','Patients','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','Updated patient profile: Maria Dela Cruz (P-2026-00006)','2026-09-02 21:26:40');
INSERT INTO `audit_logs` VALUES('358','1','admin','PATIENT_IHP_UPDATED','Patients','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','Updated PhilHealth IHP Medical History for P-2026-00006 (Maria Dela Cruz)','2026-09-02 21:30:15');
INSERT INTO `audit_logs` VALUES('359','1','admin','VITAL_SIGNS_RECORDED','Patients','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','Recorded vital signs for patient: Ralph Lawrence Pore (P-2026-00001)','2026-09-02 21:32:30');
INSERT INTO `audit_logs` VALUES('360','1','admin','SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','Error: Call to undefined method App\\Models\\Patient::getDb() in PatientController.php:196','2026-09-02 22:21:39');
INSERT INTO `audit_logs` VALUES('361','1','admin','SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','Error: Call to undefined method App\\Models\\Patient::getDb() in PatientController.php:196','2026-09-02 22:21:40');
INSERT INTO `audit_logs` VALUES('362','1','admin','SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','Error: Call to undefined method App\\Models\\Patient::getDb() in PatientController.php:196','2026-09-02 22:21:43');
INSERT INTO `audit_logs` VALUES('363','1','admin','SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','Error: Call to undefined method App\\Models\\Patient::getDb() in PatientController.php:196','2026-09-02 22:22:01');
INSERT INTO `audit_logs` VALUES('364','1','admin','SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','Error: Call to undefined method App\\Models\\Patient::getDb() in PatientController.php:196','2026-09-02 22:22:02');
INSERT INTO `audit_logs` VALUES('365','1','admin','SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','Error: Call to undefined method App\\Models\\Patient::getDb() in PatientController.php:196','2026-09-02 22:22:50');
INSERT INTO `audit_logs` VALUES('366','1','admin','SYSTEM_ERROR','System','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','Error: Call to undefined method App\\Models\\Patient::getDb() in PatientController.php:196','2026-09-02 22:22:52');
INSERT INTO `audit_logs` VALUES('367','1',NULL,'BACKUP_CREATED','Backup','127.0.0.1',NULL,'Created database backup: backup-2026-09-02_16-35-16.sql','2026-09-02 22:35:16');

DROP TABLE IF EXISTS `child_growth_logs`;
CREATE TABLE `child_growth_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wellbaby_id` int(11) NOT NULL,
  `log_date` date NOT NULL,
  `age_months` decimal(4,1) NOT NULL,
  `weight_kg` decimal(5,2) NOT NULL,
  `height_cm` decimal(5,2) NOT NULL,
  `head_circumference_cm` decimal(4,1) DEFAULT NULL,
  `chest_circumference_cm` decimal(4,1) DEFAULT NULL,
  `temperature` decimal(4,2) DEFAULT NULL,
  `feeding_method` enum('LAM / Exclusive Breastfeeding','Bottle Feed','Mixed') NOT NULL DEFAULT 'LAM / Exclusive Breastfeeding',
  `vaccines_administered` varchar(255) DEFAULT NULL,
  `vitamin_a_dose` tinyint(1) DEFAULT 0,
  `deworming_dose` tinyint(1) DEFAULT 0,
  `tcb_notes` text DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_growth_log_wellbaby` (`wellbaby_id`),
  KEY `fk_growth_log_recorded_by` (`recorded_by`),
  CONSTRAINT `fk_growth_log_recorded_by` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_growth_log_wellbaby` FOREIGN KEY (`wellbaby_id`) REFERENCES `wellbaby_records` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `child_growth_logs` VALUES('2','2','2026-09-02','2.0','4.80','56.00','38.5','38.0','36.60','LAM / Exclusive Breastfeeding','Penta 1, OPV 1, Rota 1','0','0','Responsive, smiling, healthy weight gain','1','2026-09-02 16:28:35');
INSERT INTO `child_growth_logs` VALUES('3','3','2026-09-02','2.0','4.80','56.00','38.5','38.0','36.60','LAM / Exclusive Breastfeeding','Penta 1, OPV 1, Rota 1','0','0','Responsive, smiling, healthy weight gain','1','2026-09-02 16:29:15');
INSERT INTO `child_growth_logs` VALUES('4','4','2026-09-02','2.0','4.80','56.00','38.5','38.0','36.60','LAM / Exclusive Breastfeeding','Penta 1, OPV 1, Rota 1','0','0','Responsive, smiling, healthy weight gain','1','2026-09-02 16:30:29');
INSERT INTO `child_growth_logs` VALUES('5','5','2026-09-02','2.0','4.80','56.00','38.5','38.0','36.60','LAM / Exclusive Breastfeeding','Penta 1, OPV 1, Rota 1','0','0','Responsive, smiling, healthy weight gain','1','2026-09-02 16:35:58');
INSERT INTO `child_growth_logs` VALUES('6','6','2026-09-02','2.0','4.80','56.00','38.5','38.0','36.60','LAM / Exclusive Breastfeeding','Penta 1, OPV 1, Rota 1','0','0','Responsive, smiling, healthy weight gain','1','2026-09-02 20:35:46');
INSERT INTO `child_growth_logs` VALUES('7','7','2026-09-02','2.0','4.80','56.00','38.5','38.0','36.60','LAM / Exclusive Breastfeeding','Penta 1, OPV 1, Rota 1','0','0','Responsive, smiling, healthy weight gain','1','2026-09-02 22:06:25');
INSERT INTO `child_growth_logs` VALUES('8','8','2026-08-13','1.5','4.40','54.00','37.5','37.0','36.50','LAM / Exclusive Breastfeeding','Pentavalent 1, OPV 1, Rotavirus 1','0','0','Active, responsive, good muscle tone.','1','2026-09-02 22:20:44');
INSERT INTO `child_growth_logs` VALUES('10','9','2026-09-02','2.0','4.80','56.00','38.5','38.0','36.60','LAM / Exclusive Breastfeeding','Penta 1, OPV 1, Rota 1','0','0','Responsive, smiling, healthy weight gain','1','2026-09-02 22:20:48');
INSERT INTO `child_growth_logs` VALUES('11','10','2026-08-13','1.5','4.40','54.00','37.5','37.0','36.50','LAM / Exclusive Breastfeeding','Pentavalent 1, OPV 1, Rotavirus 1','0','0','Active, responsive, good muscle tone.','1','2026-09-02 22:21:02');
INSERT INTO `child_growth_logs` VALUES('13','11','2026-09-02','2.0','4.80','56.00','38.5','38.0','36.60','LAM / Exclusive Breastfeeding','Penta 1, OPV 1, Rota 1','0','0','Responsive, smiling, healthy weight gain','1','2026-09-02 22:24:56');
INSERT INTO `child_growth_logs` VALUES('14','12','2026-08-13','1.5','4.40','54.00','37.5','37.0','36.50','LAM / Exclusive Breastfeeding','Pentavalent 1, OPV 1, Rotavirus 1','0','0','Active, responsive, good muscle tone.','1','2026-09-02 22:25:10');

DROP TABLE IF EXISTS `child_health_records`;
CREATE TABLE `child_health_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `birth_weight_kg` decimal(4,2) DEFAULT NULL,
  `birth_height_cm` decimal(4,1) DEFAULT NULL,
  `delivery_type` varchar(50) DEFAULT NULL,
  `place_of_delivery` varchar(150) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_child_patient` (`patient_id`),
  KEY `fk_child_created_by` (`created_by`),
  CONSTRAINT `fk_child_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_child_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `consultations`;
CREATE TABLE `consultations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `vital_signs_id` int(11) DEFAULT NULL,
  `subjective` text NOT NULL,
  `objective` text NOT NULL,
  `assessment` text NOT NULL,
  `plan` text NOT NULL,
  `status` enum('Open','Completed','Cancelled') NOT NULL DEFAULT 'Completed',
  `consulted_by` int(11) NOT NULL,
  `consulted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `archive_reason` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_consultation_vitals` (`vital_signs_id`),
  KEY `fk_consultation_consulted_by` (`consulted_by`),
  KEY `fk_consultation_created_by` (`created_by`),
  KEY `fk_consultation_updated_by` (`updated_by`),
  KEY `fk_consultation_deleted_by` (`deleted_by`),
  KEY `idx_consultation_patient_date` (`patient_id`,`consulted_at`),
  KEY `idx_consultation_deleted` (`deleted_at`),
  CONSTRAINT `fk_consultation_consulted_by` FOREIGN KEY (`consulted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_consultation_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_consultation_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_consultation_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_consultation_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_consultation_vitals` FOREIGN KEY (`vital_signs_id`) REFERENCES `vital_signs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `consultations` VALUES('1','1','2','headache and fever for 2 days','sore throat','Tonsilitis','Paracetamol 500 mg','Open','3','2026-06-25 11:20:00','1',NULL,'2026-06-25 14:19:08','2026-06-25 14:19:08',NULL,NULL,NULL);
INSERT INTO `consultations` VALUES('2','75','11','Patient reports mild morning headaches, feeling fetal movements.','BP 145/95 mmHg, FHT 148 bpm, clear breath sounds, mild pedal edema.','G2P1 16 wks AOG; Gestational Hypertension / High-Risk Pre-Eclampsia.','Prescribed Methyldopa 250mg BID. Avoided penicillin-group antibiotics. Scheduled follow-up next week.','Completed','1','2026-09-02 16:35:16','1',NULL,'2026-09-02 22:35:16','2026-09-02 22:35:16',NULL,NULL,NULL);
INSERT INTO `consultations` VALUES('3','78','12','Patient reports mild morning headaches, feeling fetal movements.','BP 145/95 mmHg, FHT 148 bpm, clear breath sounds, mild pedal edema.','G2P1 16 wks AOG; Gestational Hypertension / High-Risk Pre-Eclampsia.','Prescribed Methyldopa 250mg BID. Avoided penicillin-group antibiotics. Scheduled follow-up next week.','Completed','1','2026-09-02 16:35:30','1',NULL,'2026-09-02 22:35:30','2026-09-02 22:35:30',NULL,NULL,NULL);

DROP TABLE IF EXISTS `immunizations`;
CREATE TABLE `immunizations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `vaccine_name` varchar(100) NOT NULL,
  `dose_number` int(11) NOT NULL DEFAULT 1,
  `administered_date` date NOT NULL,
  `remarks` text DEFAULT NULL,
  `administered_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_immunization_administered_by` (`administered_by`),
  KEY `idx_immunization_lookup` (`patient_id`,`vaccine_name`),
  CONSTRAINT `fk_immunization_administered_by` FOREIGN KEY (`administered_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_immunization_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `immunizations` VALUES('1','50','BCG','1','2026-07-02','Right deltoid at birth','1','2026-09-02 22:20:44','2026-09-02 22:20:44');
INSERT INTO `immunizations` VALUES('2','50','Hepatitis B','1','2026-07-02','Within 24 hours of birth','1','2026-09-02 22:20:44','2026-09-02 22:20:44');
INSERT INTO `immunizations` VALUES('3','50','Pentavalent','1','2026-08-13','EPI 6 weeks','1','2026-09-02 22:20:44','2026-09-02 22:20:44');
INSERT INTO `immunizations` VALUES('4','59','BCG','1','2026-07-02','Right deltoid at birth','1','2026-09-02 22:21:02','2026-09-02 22:21:02');
INSERT INTO `immunizations` VALUES('5','59','Hepatitis B','1','2026-07-02','Within 24 hours of birth','1','2026-09-02 22:21:02','2026-09-02 22:21:02');
INSERT INTO `immunizations` VALUES('6','59','Pentavalent','1','2026-08-13','EPI 6 weeks','1','2026-09-02 22:21:02','2026-09-02 22:21:02');
INSERT INTO `immunizations` VALUES('7','68','BCG','1','2026-07-02','Right deltoid at birth','1','2026-09-02 22:25:10','2026-09-02 22:25:10');
INSERT INTO `immunizations` VALUES('8','68','Hepatitis B','1','2026-07-02','Within 24 hours of birth','1','2026-09-02 22:25:10','2026-09-02 22:25:10');
INSERT INTO `immunizations` VALUES('9','68','Pentavalent','1','2026-08-13','EPI 6 weeks','1','2026-09-02 22:25:10','2026-09-02 22:25:10');
INSERT INTO `immunizations` VALUES('10','77','BCG','1','2026-06-02','At birth','1','2026-09-02 22:35:16','2026-09-02 22:35:16');
INSERT INTO `immunizations` VALUES('11','77','Hepatitis B','1','2026-06-02','Within 24 hours','1','2026-09-02 22:35:16','2026-09-02 22:35:16');
INSERT INTO `immunizations` VALUES('12','77','Pentavalent','1','2026-07-14','EPI Dose 1','1','2026-09-02 22:35:16','2026-09-02 22:35:16');
INSERT INTO `immunizations` VALUES('13','77','OPV','1','2026-07-14','EPI Dose 1','1','2026-09-02 22:35:16','2026-09-02 22:35:16');
INSERT INTO `immunizations` VALUES('14','80','BCG','1','2026-06-02','At birth','1','2026-09-02 22:35:30','2026-09-02 22:35:30');
INSERT INTO `immunizations` VALUES('15','80','Hepatitis B','1','2026-06-02','Within 24 hours','1','2026-09-02 22:35:30','2026-09-02 22:35:30');
INSERT INTO `immunizations` VALUES('16','80','Pentavalent','1','2026-07-14','EPI Dose 1','1','2026-09-02 22:35:30','2026-09-02 22:35:30');
INSERT INTO `immunizations` VALUES('17','80','OPV','1','2026-07-14','EPI Dose 1','1','2026-09-02 22:35:30','2026-09-02 22:35:30');

DROP TABLE IF EXISTS `lab_requests`;
CREATE TABLE `lab_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `consultation_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `test_name` varchar(100) NOT NULL,
  `status` enum('Pending','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `requested_by` int(11) NOT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_lab_request_consultation` (`consultation_id`),
  KEY `fk_lab_request_patient` (`patient_id`),
  KEY `fk_lab_request_requested_by` (`requested_by`),
  CONSTRAINT `fk_lab_request_consultation` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_lab_request_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_lab_request_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `lab_results`;
CREATE TABLE `lab_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lab_request_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `result_details` text NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_lab_result_request` (`lab_request_id`),
  KEY `fk_lab_result_patient` (`patient_id`),
  KEY `fk_lab_result_recorded_by` (`recorded_by`),
  CONSTRAINT `fk_lab_result_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_lab_result_recorded_by` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_lab_result_request` FOREIGN KEY (`lab_request_id`) REFERENCES `lab_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `maternal_records`;
CREATE TABLE `maternal_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `lmp` date DEFAULT NULL,
  `edc` date DEFAULT NULL,
  `gravida` int(11) DEFAULT NULL,
  `para` int(11) DEFAULT NULL,
  `abortion` int(11) DEFAULT NULL,
  `stillbirth` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_maternal_patient` (`patient_id`),
  KEY `fk_maternal_created_by` (`created_by`),
  CONSTRAINT `fk_maternal_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_maternal_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `past_obstetric_histories`;
CREATE TABLE `past_obstetric_histories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `gravida_no` int(11) NOT NULL,
  `delivery_type` enum('NSD','CS','Abortion','Other') NOT NULL DEFAULT 'NSD',
  `infant_sex` enum('Male','Female','Unknown') DEFAULT 'Unknown',
  `place_of_delivery` varchar(150) DEFAULT NULL,
  `year_delivered` int(11) DEFAULT NULL,
  `attended_by` varchar(100) DEFAULT NULL,
  `status` enum('Alive','Not Alive') NOT NULL DEFAULT 'Alive',
  `birth_date` date DEFAULT NULL,
  `tt_status` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_past_obs_patient` (`patient_id`),
  CONSTRAINT `fk_past_obs_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `past_obstetric_histories` VALUES('2','20','1','NSD','Male','Sinalhan Lying-in','2024','Midwife Ramos','Alive',NULL,'TT1, TT2 given','2026-09-02 16:28:35');
INSERT INTO `past_obstetric_histories` VALUES('3','23','1','NSD','Male','Sinalhan Lying-in','2024','Midwife Ramos','Alive',NULL,'TT1, TT2 given','2026-09-02 16:29:15');
INSERT INTO `past_obstetric_histories` VALUES('4','25','1','NSD','Male','Sinalhan Lying-in','2024','Midwife Ramos','Alive',NULL,'TT1, TT2 given','2026-09-02 16:30:29');
INSERT INTO `past_obstetric_histories` VALUES('5','30','1','NSD','Male','Sinalhan Lying-in','2024','Midwife Ramos','Alive',NULL,'TT1, TT2 given','2026-09-02 16:35:58');
INSERT INTO `past_obstetric_histories` VALUES('6','33','1','NSD','Male','Sinalhan Lying-in','2024','Midwife Ramos','Alive',NULL,'TT1, TT2 given','2026-09-02 20:35:46');
INSERT INTO `past_obstetric_histories` VALUES('7','40','1','NSD','Male','Sta. Rosa Lying-in Clinic','2024','Midwife Ramos','Alive',NULL,'TT2 in 2024','2026-09-02 22:05:59');
INSERT INTO `past_obstetric_histories` VALUES('8','41','1','NSD','Male','Sta. Rosa Lying-in Clinic','2024','Midwife Ramos','Alive',NULL,'TT2 in 2024','2026-09-02 22:06:20');
INSERT INTO `past_obstetric_histories` VALUES('9','42','1','NSD','Male','Sinalhan Lying-in','2024','Midwife Ramos','Alive',NULL,'TT1, TT2 given','2026-09-02 22:06:25');
INSERT INTO `past_obstetric_histories` VALUES('10','48','1','NSD','Male','Sta. Rosa Lying-in Clinic','2024','Midwife Ramos','Alive',NULL,'TT2 in 2024','2026-09-02 22:06:40');
INSERT INTO `past_obstetric_histories` VALUES('11','51','1','NSD','Male','Sinalhan Lying-in','2024','Midwife Ramos','Alive',NULL,'TT1, TT2 given','2026-09-02 22:20:48');
INSERT INTO `past_obstetric_histories` VALUES('12','57','1','NSD','Male','Sta. Rosa Lying-in Clinic','2024','Midwife Ramos','Alive',NULL,'TT2 in 2024','2026-09-02 22:21:00');
INSERT INTO `past_obstetric_histories` VALUES('13','60','1','NSD','Male','Sinalhan Lying-in','2024','Midwife Ramos','Alive',NULL,'TT1, TT2 given','2026-09-02 22:24:56');
INSERT INTO `past_obstetric_histories` VALUES('14','66','1','NSD','Male','Sta. Rosa Lying-in Clinic','2024','Midwife Ramos','Alive',NULL,'TT2 in 2024','2026-09-02 22:25:07');

DROP TABLE IF EXISTS `patient_medical_histories`;
CREATE TABLE `patient_medical_histories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `past_medical_history` text DEFAULT NULL,
  `surgical_history` text DEFAULT NULL,
  `family_history` text DEFAULT NULL,
  `smoking_status` enum('Never','Yes','Quit') DEFAULT 'Never',
  `smoking_pack_years` decimal(4,1) DEFAULT NULL,
  `alcohol_status` enum('Never','Yes','Quit') DEFAULT 'Never',
  `alcohol_bottles_per_day` decimal(4,1) DEFAULT NULL,
  `illicit_drugs` tinyint(1) DEFAULT 0,
  `menarche_age` int(11) DEFAULT NULL,
  `sexual_onset_age` int(11) DEFAULT NULL,
  `lmp` date DEFAULT NULL,
  `period_duration_days` int(11) DEFAULT NULL,
  `cycle_interval_days` int(11) DEFAULT NULL,
  `pads_per_day` int(11) DEFAULT NULL,
  `is_menopausal` tinyint(1) DEFAULT 0,
  `menopause_age` int(11) DEFAULT NULL,
  `birth_control_method` varchar(100) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_patient_med_hist` (`patient_id`),
  KEY `fk_med_hist_updated_by` (`updated_by`),
  CONSTRAINT `fk_med_hist_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_med_hist_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `patient_medical_histories` VALUES('6','19','[]','[]','[]','Never',NULL,'Never',NULL,'0',NULL,NULL,NULL,NULL,NULL,NULL,'0',NULL,NULL,'1','2026-09-02 15:58:41','2026-09-02 21:30:15');
INSERT INTO `patient_medical_histories` VALUES('7','20','[\"Allergy: Penicillin\",\"Asthma: Maintenance Inhaler\"]','[\"Appendectomy (2019)\"]','[\"Hypertension (Mother)\",\"Diabetes (Father)\"]','Never',NULL,'Quit',NULL,'0','13','22',NULL,'5','28','3','0',NULL,'None (Pregnant)','1','2026-09-02 16:28:35','2026-09-02 16:28:35');
INSERT INTO `patient_medical_histories` VALUES('8','23','[\"Allergy: Penicillin\",\"Asthma: Maintenance Inhaler\"]','[\"Appendectomy (2019)\"]','[\"Hypertension (Mother)\",\"Diabetes (Father)\"]','Never',NULL,'Quit',NULL,'0','13','22',NULL,'5','28','3','0',NULL,'None (Pregnant)','1','2026-09-02 16:29:15','2026-09-02 16:29:15');
INSERT INTO `patient_medical_histories` VALUES('9','25','[\"Allergy: Penicillin\",\"Asthma: Maintenance Inhaler\"]','[\"Appendectomy (2019)\"]','[\"Hypertension (Mother)\",\"Diabetes (Father)\"]','Never',NULL,'Quit',NULL,'0','13','22',NULL,'5','28','3','0',NULL,'None (Pregnant)','1','2026-09-02 16:30:29','2026-09-02 16:30:29');
INSERT INTO `patient_medical_histories` VALUES('10','30','[\"Allergy: Penicillin\",\"Asthma: Maintenance Inhaler\"]','[\"Appendectomy (2019)\"]','[\"Hypertension (Mother)\",\"Diabetes (Father)\"]','Never',NULL,'Quit',NULL,'0','13','22',NULL,'5','28','3','0',NULL,'None (Pregnant)','1','2026-09-02 16:35:58','2026-09-02 16:35:58');
INSERT INTO `patient_medical_histories` VALUES('11','32','{\"Allergy\":\"Penicillin, Shellfish\",\"Hypertension\":\"Highest BP: 160\\/100\",\"Asthma\":\"Asthma\",\"Diabetes Mellitus\":\"Diabetes Mellitus\"}','[{\"operation\":\"Appendectomy\",\"date\":\"2019-03-12\",\"hospital\":\"Sta. Rosa Community Hospital\"},{\"operation\":\"Cholecystectomy\",\"date\":\"2022-08-20\",\"hospital\":\"Qualimed Hospital\"}]','[\"Hypertension\",\"Diabetes Mellitus\",\"Asthma\",\"Kidney Disease\"]','Yes','5.5','Quit',NULL,'0','12','22','2026-08-01','5','28','3','0',NULL,'Oral Contraceptive Pills','1','2026-09-02 20:35:44','2026-09-02 20:35:44');
INSERT INTO `patient_medical_histories` VALUES('12','33','[\"Allergy: Penicillin\",\"Asthma: Maintenance Inhaler\"]','[\"Appendectomy (2019)\"]','[\"Hypertension (Mother)\",\"Diabetes (Father)\"]','Never',NULL,'Quit',NULL,'0','13','22',NULL,'5','28','3','0',NULL,'None (Pregnant)','1','2026-09-02 20:35:46','2026-09-02 20:35:46');
INSERT INTO `patient_medical_histories` VALUES('13','38','{\"Allergy\":\"Penicillin, Shellfish\",\"Hypertension\":\"Highest BP: 160\\/100\",\"Asthma\":\"Asthma\",\"Diabetes Mellitus\":\"Diabetes Mellitus\"}','[{\"operation\":\"Appendectomy\",\"date\":\"2019-03-12\",\"hospital\":\"Sta. Rosa Community Hospital\"},{\"operation\":\"Cholecystectomy\",\"date\":\"2022-08-20\",\"hospital\":\"Qualimed Hospital\"}]','[\"Hypertension\",\"Diabetes Mellitus\",\"Asthma\",\"Kidney Disease\"]','Yes','5.5','Quit',NULL,'0','12','22','2026-08-01','5','28','3','0',NULL,'Oral Contraceptive Pills','1','2026-09-02 20:44:02','2026-09-02 20:44:02');
INSERT INTO `patient_medical_histories` VALUES('14','39','{\"Allergy\":\"Penicillin, Shellfish\",\"Hypertension\":\"Highest BP: 160\\/100\",\"Asthma\":\"Asthma\",\"Diabetes Mellitus\":\"Diabetes Mellitus\"}','[{\"operation\":\"Appendectomy\",\"date\":\"2019-03-12\",\"hospital\":\"Sta. Rosa Community Hospital\"},{\"operation\":\"Cholecystectomy\",\"date\":\"2022-08-20\",\"hospital\":\"Qualimed Hospital\"}]','[\"Hypertension\",\"Diabetes Mellitus\",\"Asthma\",\"Kidney Disease\"]','Yes','5.5','Quit',NULL,'0','12','22','2026-08-01','5','28','3','0',NULL,'Oral Contraceptive Pills','1','2026-09-02 20:59:43','2026-09-02 20:59:43');
INSERT INTO `patient_medical_histories` VALUES('16','42','[\"Allergy: Penicillin\",\"Asthma: Maintenance Inhaler\"]','[\"Appendectomy (2019)\"]','[\"Hypertension (Mother)\",\"Diabetes (Father)\"]','Never',NULL,'Quit',NULL,'0','13','22',NULL,'5','28','3','0',NULL,'None (Pregnant)','1','2026-09-02 22:06:25','2026-09-02 22:06:25');
INSERT INTO `patient_medical_histories` VALUES('17','47','{\"Allergy\":\"Penicillin, Shellfish\",\"Hypertension\":\"Highest BP: 160\\/100\",\"Asthma\":\"Asthma\",\"Diabetes Mellitus\":\"Diabetes Mellitus\"}','[{\"operation\":\"Appendectomy\",\"date\":\"2019-03-12\",\"hospital\":\"Sta. Rosa Community Hospital\"},{\"operation\":\"Cholecystectomy\",\"date\":\"2022-08-20\",\"hospital\":\"Qualimed Hospital\"}]','[\"Hypertension\",\"Diabetes Mellitus\",\"Asthma\",\"Kidney Disease\"]','Yes','5.5','Quit',NULL,'0','12','22','2026-08-01','5','28','3','0',NULL,'Oral Contraceptive Pills','1','2026-09-02 22:06:37','2026-09-02 22:06:37');
INSERT INTO `patient_medical_histories` VALUES('18','51','[\"Allergy: Penicillin\",\"Asthma: Maintenance Inhaler\"]','[\"Appendectomy (2019)\"]','[\"Hypertension (Mother)\",\"Diabetes (Father)\"]','Never',NULL,'Quit',NULL,'0','13','22',NULL,'5','28','3','0',NULL,'None (Pregnant)','1','2026-09-02 22:20:48','2026-09-02 22:20:48');
INSERT INTO `patient_medical_histories` VALUES('19','56','{\"Allergy\":\"Penicillin, Shellfish\",\"Hypertension\":\"Highest BP: 160\\/100\",\"Asthma\":\"Asthma\",\"Diabetes Mellitus\":\"Diabetes Mellitus\"}','[{\"operation\":\"Appendectomy\",\"date\":\"2019-03-12\",\"hospital\":\"Sta. Rosa Community Hospital\"},{\"operation\":\"Cholecystectomy\",\"date\":\"2022-08-20\",\"hospital\":\"Qualimed Hospital\"}]','[\"Hypertension\",\"Diabetes Mellitus\",\"Asthma\",\"Kidney Disease\"]','Yes','5.5','Quit',NULL,'0','12','22','2026-08-01','5','28','3','0',NULL,'Oral Contraceptive Pills','1','2026-09-02 22:20:55','2026-09-02 22:20:55');
INSERT INTO `patient_medical_histories` VALUES('20','60','[\"Allergy: Penicillin\",\"Asthma: Maintenance Inhaler\"]','[\"Appendectomy (2019)\"]','[\"Hypertension (Mother)\",\"Diabetes (Father)\"]','Never',NULL,'Quit',NULL,'0','13','22',NULL,'5','28','3','0',NULL,'None (Pregnant)','1','2026-09-02 22:24:56','2026-09-02 22:24:56');
INSERT INTO `patient_medical_histories` VALUES('21','65','{\"Allergy\":\"Penicillin, Shellfish\",\"Hypertension\":\"Highest BP: 160\\/100\",\"Asthma\":\"Asthma\",\"Diabetes Mellitus\":\"Diabetes Mellitus\"}','[{\"operation\":\"Appendectomy\",\"date\":\"2019-03-12\",\"hospital\":\"Sta. Rosa Community Hospital\"},{\"operation\":\"Cholecystectomy\",\"date\":\"2022-08-20\",\"hospital\":\"Qualimed Hospital\"}]','[\"Hypertension\",\"Diabetes Mellitus\",\"Asthma\",\"Kidney Disease\"]','Yes','5.5','Quit',NULL,'0','12','22','2026-08-01','5','28','3','0',NULL,'Oral Contraceptive Pills','1','2026-09-02 22:25:04','2026-09-02 22:25:04');
INSERT INTO `patient_medical_histories` VALUES('22','72','{\"Allergy\":\"Penicillin, Amoxicillin, Shellfish\",\"Hypertension\":\"Highest BP: 160\\/100\",\"Asthma\":\"Mild intermittent\"}',NULL,NULL,'Never',NULL,'Never',NULL,'0',NULL,NULL,NULL,NULL,NULL,NULL,'0',NULL,NULL,'1','2026-09-02 22:34:24','2026-09-02 22:34:24');
INSERT INTO `patient_medical_histories` VALUES('23','75','{\"Allergy\":\"Penicillin, Amoxicillin, Shellfish\",\"Hypertension\":\"Highest BP: 160\\/100\",\"Asthma\":\"Mild intermittent\"}',NULL,NULL,'Never',NULL,'Never',NULL,'0',NULL,NULL,NULL,NULL,NULL,NULL,'0',NULL,NULL,'1','2026-09-02 22:35:16','2026-09-02 22:35:16');
INSERT INTO `patient_medical_histories` VALUES('24','78','{\"Allergy\":\"Penicillin, Amoxicillin, Shellfish\",\"Hypertension\":\"Highest BP: 160\\/100\",\"Asthma\":\"Mild intermittent\"}',NULL,NULL,'Never',NULL,'Never',NULL,'0',NULL,NULL,NULL,NULL,NULL,NULL,'0',NULL,NULL,'1','2026-09-02 22:35:30','2026-09-02 22:35:30');

DROP TABLE IF EXISTS `patients`;
CREATE TABLE `patients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_no` varchar(20) NOT NULL,
  `family_no` varchar(50) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `dob` date NOT NULL,
  `sex` enum('Male','Female') NOT NULL,
  `civil_status` enum('Single','Married','Widowed','Divorced','Separated','Annulled','Others') NOT NULL,
  `blood_type` enum('A+','A-','B+','B-','AB+','AB-','O+','O-','Unknown') NOT NULL DEFAULT 'Unknown',
  `religion` varchar(100) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `education_attainment` enum('No Schooling','Elementary','High School','Vocational','College / Post-Graduate') DEFAULT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `barangay` varchar(100) NOT NULL DEFAULT 'Sinalhan',
  `address` text NOT NULL,
  `phic_status` enum('Member','Dependent','Non-Member') NOT NULL DEFAULT 'Non-Member',
  `phic_type` varchar(100) DEFAULT NULL,
  `emergency_name` varchar(100) DEFAULT NULL,
  `emergency_relationship` varchar(50) DEFAULT NULL,
  `emergency_no` varchar(20) DEFAULT NULL,
  `philhealth_no` varchar(20) DEFAULT NULL,
  `father_name` varchar(150) DEFAULT NULL,
  `father_dob` date DEFAULT NULL,
  `mother_name` varchar(150) DEFAULT NULL,
  `mother_dob` date DEFAULT NULL,
  `spouse_name` varchar(150) DEFAULT NULL,
  `spouse_dob` date DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `archive_reason` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_patient_no` (`patient_no`),
  UNIQUE KEY `idx_philhealth` (`philhealth_no`),
  KEY `fk_patients_created_by` (`created_by`),
  KEY `fk_patients_updated_by` (`updated_by`),
  KEY `fk_patients_deleted_by` (`deleted_by`),
  KEY `idx_patients_search` (`last_name`,`first_name`),
  KEY `idx_patients_dob` (`dob`),
  KEY `idx_patients_sex` (`sex`),
  KEY `idx_patients_barangay` (`barangay`),
  KEY `idx_patients_archived` (`deleted_at`),
  KEY `idx_family_no` (`family_no`),
  KEY `idx_phic_status` (`phic_status`),
  KEY `idx_patients_family_no` (`family_no`),
  CONSTRAINT `fk_patients_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_patients_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_patients_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `patients` VALUES('1','P-2026-00001',NULL,'Ralph Lawrence','Basbas','Pore',NULL,'2004-05-20','Male','Single','O+',NULL,NULL,NULL,NULL,'Sinalhan','Purok 6, Brgy. Sinalhan, Sta. Rosa City, Laguna','Non-Member',NULL,'Lea Pore',NULL,'09998698088','12-234567897-9',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1','1','2026-06-25 13:53:03','2026-08-09 23:42:45');
INSERT INTO `patients` VALUES('2','P-2026-00002',NULL,'123',NULL,'1341',NULL,'2026-06-02','Male','Single','Unknown',NULL,NULL,NULL,NULL,'Sinalhan','bahaybahay','Non-Member',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-28 13:57:43','11','error','1',NULL,'2026-06-27 22:53:42','2026-06-28 13:57:43');
INSERT INTO `patients` VALUES('3','P-2026-00003',NULL,'Limuel Dien',NULL,'Makiyama',NULL,'2005-08-12','Male','Single','O+',NULL,NULL,NULL,NULL,'Sinalhan','Purok 2, Brgy. Sinalhan, Sta. Rosa City, Laguna','Non-Member',NULL,NULL,NULL,NULL,'12-345678901-2',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1','1','2026-08-08 23:04:23','2026-08-17 14:38:50');
INSERT INTO `patients` VALUES('4','P-2026-00004',NULL,'Maria','Santos','Cruz',NULL,'1990-08-15','Female','Married','O+',NULL,'Sari-Sari Store Owner',NULL,'09998698088','Sinalhan','Purok 3, Brgy. Sinalhan, Sta. Rosa City, Laguna','Non-Member',NULL,'Pedro Cruz','Spouse','09189998877','12-345678571-2',NULL,NULL,NULL,NULL,NULL,NULL,'2026-08-08 23:07:44','1','duplicate record','1',NULL,'2026-08-08 23:07:16','2026-08-08 23:07:44');
INSERT INTO `patients` VALUES('18','P-2026-00005','FAM-0428','Zack','Santos','Tabudlo',NULL,'2023-09-01','Male','Single','A+',NULL,NULL,NULL,NULL,'Sinalhan','Purok 6, Barangay Sinalhan, City of Santa Rosa, Laguna','Non-Member',NULL,'Ralph Lawrence Pore','Guardian','09293885098',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1','1','2026-09-02 15:49:59','2026-09-02 20:30:59');
INSERT INTO `patients` VALUES('19','P-2026-00006','FAM-0428','Maria','Santos','Dela Cruz',NULL,'1985-09-05','Female','Married','A-',NULL,'Housewife','Vocational','09191444402','Sinalhan','Sinalhan','Non-Member',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1','1','2026-09-02 15:52:39','2026-09-02 21:26:40');
INSERT INTO `patients` VALUES('78','P-2026-00007','FAM-TEST-P6-AUDIT','TestP6Mother','Santos','Reyes',NULL,'1998-05-15','Female','Married','O+','Roman Catholic',NULL,NULL,'09181112233','Sinalhan','Purok 2 Lakeside','Non-Member',NULL,NULL,NULL,NULL,'12-998877665-1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1',NULL,'2026-09-02 22:35:30','2026-09-02 22:35:30');
INSERT INTO `patients` VALUES('79','P-2026-00008','FAM-TEST-P6-AUDIT','TestP6Father','Gomez','Reyes',NULL,'1995-02-20','Male','Married','O+','Roman Catholic',NULL,NULL,'09181112244','Sinalhan','Purok 2 Lakeside','Non-Member',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1',NULL,'2026-09-02 22:35:30','2026-09-02 22:35:30');
INSERT INTO `patients` VALUES('80','P-2026-00009','FAM-TEST-P6-AUDIT','TestP6Infant','Santos','Reyes','Jr.','2026-06-02','Male','Single','O+','Roman Catholic',NULL,NULL,NULL,'Sinalhan','Purok 2 Lakeside','Non-Member',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'TestP6Mother Reyes',NULL,NULL,NULL,NULL,NULL,NULL,'1',NULL,'2026-09-02 22:35:30','2026-09-02 22:35:30');

DROP TABLE IF EXISTS `prenatal_records`;
CREATE TABLE `prenatal_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `husband_name` varchar(150) DEFAULT NULL,
  `gravida` int(11) NOT NULL DEFAULT 1,
  `para` int(11) NOT NULL DEFAULT 0,
  `term_births` int(11) NOT NULL DEFAULT 0,
  `preterm_births` int(11) NOT NULL DEFAULT 0,
  `abortions` int(11) NOT NULL DEFAULT 0,
  `living_children` int(11) NOT NULL DEFAULT 0,
  `lmp` date NOT NULL,
  `edc` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `pre_eclampsia` tinyint(1) NOT NULL DEFAULT 0,
  `fp_counselling` tinyint(1) NOT NULL DEFAULT 1,
  `delivery_date` date DEFAULT NULL,
  `delivery_outcome` enum('Live Birth','Stillbirth','Miscarriage','Ectopic','Other') DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_prenatal_patient` (`patient_id`),
  KEY `idx_prenatal_active` (`is_active`),
  KEY `fk_prenatal_created_by` (`created_by`),
  CONSTRAINT `fk_prenatal_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_prenatal_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `prenatal_records` VALUES('4','20','TestFather PhaseOne','2','1','1','0','0','1','2026-04-15','2027-01-22','1','1','1',NULL,NULL,NULL,'1','2026-09-02 16:28:35','2026-09-02 16:28:35');
INSERT INTO `prenatal_records` VALUES('5','23','TestFather PhaseOne','2','1','1','0','0','1','2026-04-15','2027-01-22','1','1','1',NULL,NULL,NULL,'1','2026-09-02 16:29:15','2026-09-02 16:29:15');
INSERT INTO `prenatal_records` VALUES('6','25','TestFather PhaseOne','2','1','1','0','0','1','2026-04-15','2027-01-22','1','1','1',NULL,NULL,NULL,'1','2026-09-02 16:30:29','2026-09-02 16:30:29');
INSERT INTO `prenatal_records` VALUES('7','27','TestP2Father Dela Cruz','1','0','0','0','0','0','2026-06-10','2027-03-17','1','0','1',NULL,NULL,NULL,'1','2026-09-02 16:35:49','2026-09-02 16:35:49');
INSERT INTO `prenatal_records` VALUES('8','30','TestFather PhaseOne','2','1','1','0','0','1','2026-04-15','2027-01-22','1','1','1',NULL,NULL,NULL,'1','2026-09-02 16:35:58','2026-09-02 16:35:58');
INSERT INTO `prenatal_records` VALUES('9','33','TestFather PhaseOne','2','1','1','0','0','1','2026-04-15','2027-01-22','1','1','1',NULL,NULL,NULL,'1','2026-09-02 20:35:46','2026-09-02 20:35:46');
INSERT INTO `prenatal_records` VALUES('10','35','TestP2Father Dela Cruz','1','0','0','0','0','0','2026-06-10','2027-03-17','1','0','1',NULL,NULL,NULL,'1','2026-09-02 20:35:49','2026-09-02 20:35:49');
INSERT INTO `prenatal_records` VALUES('11','40','Juan Dela Cruz','2','1','1','0','0','2','2026-02-10','2026-11-17','1','1','1',NULL,NULL,'Updated clinical notes during 3rd trimester','1','2026-09-02 22:05:59','2026-09-02 22:05:59');
INSERT INTO `prenatal_records` VALUES('12','41','Juan Dela Cruz','2','1','1','0','0','2','2026-02-10','2026-11-17','0','1','1','2026-11-15','','Updated clinical notes during 3rd trimester\nDelivery Notes: Delivered healthy baby boy at CHO Lying-in','1','2026-09-02 22:06:20','2026-09-02 22:06:20');
INSERT INTO `prenatal_records` VALUES('13','42','TestFather PhaseOne','2','1','1','0','0','1','2026-04-15','2027-01-22','1','1','1',NULL,NULL,NULL,'1','2026-09-02 22:06:25','2026-09-02 22:06:25');
INSERT INTO `prenatal_records` VALUES('14','44','TestP2Father Dela Cruz','1','0','0','0','0','0','2026-06-10','2027-03-17','1','0','1',NULL,NULL,NULL,'1','2026-09-02 22:06:30','2026-09-02 22:06:30');
INSERT INTO `prenatal_records` VALUES('15','48','Juan Dela Cruz','2','1','1','0','0','2','2026-02-10','2026-11-17','0','1','1','2026-11-15','','Updated clinical notes during 3rd trimester\nDelivery Notes: Delivered healthy baby boy at CHO Lying-in','1','2026-09-02 22:06:40','2026-09-02 22:06:40');
INSERT INTO `prenatal_records` VALUES('16','51','TestFather PhaseOne','2','1','1','0','0','1','2026-04-15','2027-01-22','1','1','1',NULL,NULL,NULL,'1','2026-09-02 22:20:48','2026-09-02 22:20:48');
INSERT INTO `prenatal_records` VALUES('17','53','TestP2Father Dela Cruz','1','0','0','0','0','0','2026-06-10','2027-03-17','1','0','1',NULL,NULL,NULL,'1','2026-09-02 22:20:51','2026-09-02 22:20:51');
INSERT INTO `prenatal_records` VALUES('18','57','Juan Dela Cruz','2','1','1','0','0','2','2026-02-10','2026-11-17','0','1','1','2026-11-15','','Updated clinical notes during 3rd trimester\nDelivery Notes: Delivered healthy baby boy at CHO Lying-in','1','2026-09-02 22:21:00','2026-09-02 22:21:00');
INSERT INTO `prenatal_records` VALUES('19','60','TestFather PhaseOne','2','1','1','0','0','1','2026-04-15','2027-01-22','1','1','1',NULL,NULL,NULL,'1','2026-09-02 22:24:56','2026-09-02 22:24:56');
INSERT INTO `prenatal_records` VALUES('20','62','TestP2Father Dela Cruz','1','0','0','0','0','0','2026-06-10','2027-03-17','1','0','1',NULL,NULL,NULL,'1','2026-09-02 22:25:00','2026-09-02 22:25:00');
INSERT INTO `prenatal_records` VALUES('21','66','Juan Dela Cruz','2','1','1','0','0','2','2026-02-10','2026-11-17','0','1','1','2026-11-15','','Updated clinical notes during 3rd trimester\nDelivery Notes: Delivered healthy baby boy at CHO Lying-in','1','2026-09-02 22:25:07','2026-09-02 22:25:07');
INSERT INTO `prenatal_records` VALUES('22','75',NULL,'2','1','1','0','0','1','2026-05-13','2027-02-17','1','1','1',NULL,NULL,'History of gestational hypertension','1','2026-09-02 22:35:16','2026-09-02 22:35:16');
INSERT INTO `prenatal_records` VALUES('23','78',NULL,'2','1','1','0','0','1','2026-05-13','2027-02-17','1','1','1',NULL,NULL,'History of gestational hypertension','1','2026-09-02 22:35:30','2026-09-02 22:35:30');

DROP TABLE IF EXISTS `prenatal_visits`;
CREATE TABLE `prenatal_visits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `prenatal_id` int(11) NOT NULL,
  `visit_date` date NOT NULL,
  `chief_complaint` varchar(255) DEFAULT NULL,
  `aog_weeks` decimal(4,1) NOT NULL,
  `bp_systolic` int(11) DEFAULT NULL,
  `bp_diastolic` int(11) DEFAULT NULL,
  `weight_kg` decimal(5,2) DEFAULT NULL,
  `height_cm` decimal(5,2) DEFAULT NULL,
  `fetal_heart_tone` int(11) DEFAULT NULL,
  `fundal_height_cm` decimal(4,1) DEFAULT NULL,
  `fetal_presentation` enum('Cephalic','Breech','Transverse','Undetermined') DEFAULT 'Cephalic',
  `tcb` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `attended_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_prenatal_visit_prenatal` (`prenatal_id`),
  KEY `fk_visit_attended_by` (`attended_by`),
  CONSTRAINT `fk_visit_attended_by` FOREIGN KEY (`attended_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_visit_prenatal` FOREIGN KEY (`prenatal_id`) REFERENCES `prenatal_records` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `prenatal_visits` VALUES('2','4','2026-09-02','Routine 2nd Trimester Prenatal Check','20.0','120','80','58.50',NULL,'145','18.0','Cephalic','TT3 given','FHT clear, prescribed FeSO4 + Folic Acid','1','2026-09-02 16:28:35');
INSERT INTO `prenatal_visits` VALUES('3','5','2026-09-02','Routine 2nd Trimester Prenatal Check','20.0','120','80','58.50',NULL,'145','18.0','Cephalic','TT3 given','FHT clear, prescribed FeSO4 + Folic Acid','1','2026-09-02 16:29:15');
INSERT INTO `prenatal_visits` VALUES('4','6','2026-09-02','Routine 2nd Trimester Prenatal Check','20.0','120','80','58.50',NULL,'145','18.0','Cephalic','TT3 given','FHT clear, prescribed FeSO4 + Folic Acid','1','2026-09-02 16:30:29');
INSERT INTO `prenatal_visits` VALUES('5','8','2026-09-02','Routine 2nd Trimester Prenatal Check','20.0','120','80','58.50',NULL,'145','18.0','Cephalic','TT3 given','FHT clear, prescribed FeSO4 + Folic Acid','1','2026-09-02 16:35:58');
INSERT INTO `prenatal_visits` VALUES('6','9','2026-09-02','Routine 2nd Trimester Prenatal Check','20.0','120','80','58.50',NULL,'145','18.0','Cephalic','TT3 given','FHT clear, prescribed FeSO4 + Folic Acid','1','2026-09-02 20:35:46');
INSERT INTO `prenatal_visits` VALUES('7','11','2026-05-15','Routine prenatal checkup','13.5','120','80','54.00','158.00','144','14.0','Cephalic','TT3 given','Fetal heart tone clear, normal range.','1','2026-09-02 22:05:59');
INSERT INTO `prenatal_visits` VALUES('8','11','2026-08-10','Mild leg edema','26.0','135','85','58.50','158.00','148','25.0','Cephalic','Iron/Folic acid supplied','Advised low salt diet, monitor BP regularly.','1','2026-09-02 22:05:59');
INSERT INTO `prenatal_visits` VALUES('9','12','2026-05-15','Routine prenatal checkup','13.5','120','80','54.00','158.00','144','14.0','Cephalic','TT3 given','Fetal heart tone clear, normal range.','1','2026-09-02 22:06:20');
INSERT INTO `prenatal_visits` VALUES('10','12','2026-08-10','Mild leg edema','26.0','135','85','58.50','158.00','148','25.0','Cephalic','Iron/Folic acid supplied','Advised low salt diet, monitor BP regularly.','1','2026-09-02 22:06:20');
INSERT INTO `prenatal_visits` VALUES('11','13','2026-09-02','Routine 2nd Trimester Prenatal Check','20.0','120','80','58.50',NULL,'145','18.0','Cephalic','TT3 given','FHT clear, prescribed FeSO4 + Folic Acid','1','2026-09-02 22:06:25');
INSERT INTO `prenatal_visits` VALUES('12','15','2026-05-15','Routine prenatal checkup','13.5','120','80','54.00','158.00','144','14.0','Cephalic','TT3 given','Fetal heart tone clear, normal range.','1','2026-09-02 22:06:40');
INSERT INTO `prenatal_visits` VALUES('13','15','2026-08-10','Mild leg edema','26.0','135','85','58.50','158.00','148','25.0','Cephalic','Iron/Folic acid supplied','Advised low salt diet, monitor BP regularly.','1','2026-09-02 22:06:40');
INSERT INTO `prenatal_visits` VALUES('14','16','2026-09-02','Routine 2nd Trimester Prenatal Check','20.0','120','80','58.50',NULL,'145','18.0','Cephalic','TT3 given','FHT clear, prescribed FeSO4 + Folic Acid','1','2026-09-02 22:20:48');
INSERT INTO `prenatal_visits` VALUES('15','18','2026-05-15','Routine prenatal checkup','13.5','120','80','54.00','158.00','144','14.0','Cephalic','TT3 given','Fetal heart tone clear, normal range.','1','2026-09-02 22:21:00');
INSERT INTO `prenatal_visits` VALUES('16','18','2026-08-10','Mild leg edema','26.0','135','85','58.50','158.00','148','25.0','Cephalic','Iron/Folic acid supplied','Advised low salt diet, monitor BP regularly.','1','2026-09-02 22:21:00');
INSERT INTO `prenatal_visits` VALUES('17','19','2026-09-02','Routine 2nd Trimester Prenatal Check','20.0','120','80','58.50',NULL,'145','18.0','Cephalic','TT3 given','FHT clear, prescribed FeSO4 + Folic Acid','1','2026-09-02 22:24:56');
INSERT INTO `prenatal_visits` VALUES('18','21','2026-05-15','Routine prenatal checkup','13.5','120','80','54.00','158.00','144','14.0','Cephalic','TT3 given','Fetal heart tone clear, normal range.','1','2026-09-02 22:25:07');
INSERT INTO `prenatal_visits` VALUES('19','21','2026-08-10','Mild leg edema','26.0','135','85','58.50','158.00','148','25.0','Cephalic','Iron/Folic acid supplied','Advised low salt diet, monitor BP regularly.','1','2026-09-02 22:25:07');

DROP TABLE IF EXISTS `prescriptions`;
CREATE TABLE `prescriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `consultation_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `medicine_name` varchar(150) NOT NULL,
  `dosage` varchar(50) NOT NULL,
  `frequency` varchar(50) NOT NULL,
  `duration` varchar(50) NOT NULL,
  `instructions` text DEFAULT NULL,
  `prescribed_by` int(11) NOT NULL,
  `prescribed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_prescription_consultation` (`consultation_id`),
  KEY `fk_prescription_patient` (`patient_id`),
  KEY `fk_prescription_prescribed_by` (`prescribed_by`),
  CONSTRAINT `fk_prescription_consultation` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_prescription_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_prescription_prescribed_by` FOREIGN KEY (`prescribed_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `queue_entries`;
CREATE TABLE `queue_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `queue_date` date NOT NULL,
  `queue_no` int(11) NOT NULL,
  `status` enum('Waiting','Called','Serving','Completed','Cancelled') NOT NULL DEFAULT 'Waiting',
  `service_type` varchar(50) DEFAULT 'General OPD',
  `time_in` time NOT NULL,
  `time_called` time DEFAULT NULL,
  `time_completed` time DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_queue_date_no` (`queue_date`,`queue_no`),
  KEY `fk_queue_patient` (`patient_id`),
  KEY `fk_queue_created_by` (`created_by`),
  KEY `fk_queue_updated_by` (`updated_by`),
  KEY `idx_queue_daily` (`queue_date`,`queue_no`),
  KEY `idx_queue_status_date` (`queue_date`,`status`),
  CONSTRAINT `fk_queue_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_queue_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_queue_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `queue_entries` VALUES('1','1','2026-06-25','1','Completed','General OPD','14:58:53','15:01:23','15:02:31','1','1','2026-06-25 14:58:53','2026-06-25 15:02:31');
INSERT INTO `queue_entries` VALUES('2','1','2026-06-25','2','Completed','General OPD','15:02:50','15:03:05','15:03:23','1','1','2026-06-25 15:02:50','2026-06-25 15:03:23');
INSERT INTO `queue_entries` VALUES('3','1','2026-06-25','3','Cancelled','General OPD','15:05:55',NULL,NULL,'1','1','2026-06-25 15:05:55','2026-06-25 15:06:48');
INSERT INTO `queue_entries` VALUES('4','1','2026-06-25','4','Completed','General OPD','15:10:21','15:10:30','15:10:42','1','1','2026-06-25 15:10:21','2026-06-25 15:10:42');
INSERT INTO `queue_entries` VALUES('5','1','2026-06-27','1','Completed','General OPD','13:15:47','13:16:13','13:16:18','1','1','2026-06-27 13:15:47','2026-06-27 13:16:18');
INSERT INTO `queue_entries` VALUES('6','1','2026-06-27','2','Completed','General OPD','13:16:37','13:16:59','13:17:19','1','1','2026-06-27 13:16:37','2026-06-27 13:17:19');
INSERT INTO `queue_entries` VALUES('7','1','2026-06-27','3','Cancelled','General OPD','22:37:24',NULL,NULL,'1','1','2026-06-27 22:37:24','2026-06-27 22:37:53');
INSERT INTO `queue_entries` VALUES('8','3','2026-08-17','1','Waiting','General OPD','15:25:18',NULL,NULL,'1',NULL,'2026-08-17 15:25:18','2026-08-17 15:25:18');
INSERT INTO `queue_entries` VALUES('9','75','2026-09-02','1','Completed','Prenatal Care','22:35:16','22:35:16','22:35:16','1','1','2026-09-02 22:35:16','2026-09-02 22:35:16');
INSERT INTO `queue_entries` VALUES('10','77','2026-09-02','2','Waiting','Well Baby Immunization','22:35:16',NULL,NULL,'1',NULL,'2026-09-02 22:35:16','2026-09-02 22:35:16');
INSERT INTO `queue_entries` VALUES('11','78','2026-09-02','3','Completed','Prenatal Care','22:35:30','22:35:30','22:35:30','1','1','2026-09-02 22:35:30','2026-09-02 22:35:30');
INSERT INTO `queue_entries` VALUES('12','80','2026-09-02','4','Waiting','Well Baby Immunization','22:35:30',NULL,NULL,'1',NULL,'2026-09-02 22:35:30','2026-09-02 22:35:30');

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_key`),
  KEY `fk_settings_updated_by` (`updated_by`),
  CONSTRAINT `fk_settings_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` VALUES('backup_interval_days','7','Database reminder interval for admins to trigger a SQL backup.','1','2026-06-25 13:24:23');
INSERT INTO `settings` VALUES('clinic_address','Barangay Sinalhan, Santa Rosa City, Laguna, Philippines','Physical address of the clinic used in printed report headers.','1','2026-06-25 13:24:23');
INSERT INTO `settings` VALUES('clinic_name','Barangay Sinalhan Health Center','The name of the healthcare center displayed on the system header.','1','2026-06-25 13:24:23');
INSERT INTO `settings` VALUES('public_queue_display_names','initials','Privacy option for queue display board: initials, none (number only), or full (show names).','1','2026-06-25 13:24:23');
INSERT INTO `settings` VALUES('queue_alert_sound','enabled','Plays a chime sound when a new queue number is called (enabled/disabled).','1','2026-06-25 13:24:23');

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','staff') NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `job_title` varchar(50) DEFAULT NULL,
  `employee_id` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `failed_attempts` int(11) NOT NULL DEFAULT 0,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` datetime DEFAULT NULL,
  `last_failed_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_username` (`username`),
  UNIQUE KEY `idx_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` VALUES('1','admin','$2y$10$6iy1nxX6HRoxGYfxAIO/P.sFNO8vuM6RZB.pgds8vA6D59lrWGP6y','admin','System',NULL,'Administrator',NULL,NULL,'IT Administrator',NULL,NULL,'active','0','0','2026-09-02 21:24:18',NULL,'2026-06-25 13:24:23','2026-09-02 21:24:18',NULL);
INSERT INTO `users` VALUES('2','records_staff','$2y$10$rRo97GBAL0ptGu47ABi1QeU05cBD638u7Cnupl11yxAWLPK9Es3jy','staff','Maria',NULL,'Santos',NULL,NULL,'Barangay Health Worker (BHW)',NULL,NULL,'active','0','0',NULL,NULL,'2026-06-25 13:24:23','2026-08-09 23:31:12',NULL);
INSERT INTO `users` VALUES('3','midwife_user','$2y$10$MQ1JTmlMiH24jODpgfuA5OHI9tH96t1pvwsAXm58YbcGWXOn.T5ba','staff','Juana',NULL,'Dela Cruz',NULL,NULL,'Midwife',NULL,NULL,'inactive','0','1',NULL,NULL,'2026-06-25 13:24:23','2026-08-09 19:56:09',NULL);
INSERT INTO `users` VALUES('5','jdoe_deleted_1782638703','$2y$10$TVabfTk.XCJeCx1fFhT2YOCbFNIjhGQMBqPf7j3AhldnfTnhf4gsq','staff','John',NULL,'Doe',NULL,NULL,'BHW',NULL,NULL,'inactive','1','0','2026-06-27 14:53:56','2026-06-28 11:23:47','2026-06-27 14:53:32','2026-08-10 00:04:18',NULL);
INSERT INTO `users` VALUES('8','jdoe','$2y$10$Ws8hBXGSS.VWNrtEuEMBQucuIFlk0lgo59Mtkdbce4G2AF7LIP7ha','staff','John',NULL,'Doe','jdoe@gmail.com','09123567183','BHW',NULL,NULL,'active','0','0','2026-08-10 10:04:01',NULL,'2026-06-28 11:34:30','2026-08-10 10:04:01',NULL);
INSERT INTO `users` VALUES('11','ralph20','$2y$10$ortPKdPk.qgWBZ6Ly0kLZOXfTKVTsn262FtURSkdECnxZxp1JkDK6','admin','Ralph Lawrence','Basbas','Pore',NULL,NULL,'IT Support',NULL,NULL,'active','0','1','2026-08-08 22:16:56',NULL,'2026-06-28 12:33:27','2026-08-08 22:16:56',NULL);
INSERT INTO `users` VALUES('17','test_nurse_1786199990','$2y$10$GgH0gFLAR/P3.EoIjmjQJORDShu/BKGHYJ0lZVLF7rWVl.cCoIiBS','staff','Elena','M','Reyes','test_nurse_1786199990@sinalhan.gov.ph','09189998877','Staff Nurse','EMP-2026-99','Maternal & Child Health','active','0','1',NULL,NULL,'2026-08-08 22:39:50','2026-08-08 22:39:50',NULL);

DROP TABLE IF EXISTS `vital_signs`;
CREATE TABLE `vital_signs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `bp_systolic` int(11) DEFAULT NULL,
  `bp_diastolic` int(11) DEFAULT NULL,
  `heart_rate` int(11) DEFAULT NULL,
  `respiratory_rate` int(11) DEFAULT NULL,
  `temperature` decimal(4,2) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `bmi` decimal(4,2) DEFAULT NULL,
  `waist_circumference` decimal(5,2) DEFAULT NULL,
  `oxygen_saturation` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_vitals_recorded_by` (`recorded_by`),
  KEY `idx_vitals_patient_date` (`patient_id`,`recorded_at`),
  CONSTRAINT `fk_vitals_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_vitals_recorded_by` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `vital_signs` VALUES('1','1','120','82','72','18','36.50','60.00','165.00','22.04',NULL,'98','None','1','2026-06-25 13:58:04');
INSERT INTO `vital_signs` VALUES('2','1','145','95','72','18','38.40','67.00','167.00','24.02',NULL,'98','High Blood','1','2026-06-25 13:59:48');
INSERT INTO `vital_signs` VALUES('4','32','150','95','82','18','36.60','58.50','160.00',NULL,'78.00','99',NULL,'1','2026-09-02 20:35:44');
INSERT INTO `vital_signs` VALUES('5','38','150','95','82','18','36.60','58.50','160.00',NULL,'78.00','99',NULL,'1','2026-09-02 20:44:02');
INSERT INTO `vital_signs` VALUES('6','39','150','95','82','18','36.60','58.50','160.00',NULL,'78.00','99',NULL,'1','2026-09-02 20:59:43');
INSERT INTO `vital_signs` VALUES('7','1','120','80','72','18','36.70','67.00','170.00','23.18','75.00','98',NULL,'1','2026-09-02 21:32:30');
INSERT INTO `vital_signs` VALUES('8','47','150','95','82','18','36.60','58.50','160.00',NULL,'78.00','99',NULL,'1','2026-09-02 22:06:37');
INSERT INTO `vital_signs` VALUES('9','56','150','95','82','18','36.60','58.50','160.00',NULL,'78.00','99',NULL,'1','2026-09-02 22:20:55');
INSERT INTO `vital_signs` VALUES('10','65','150','95','82','18','36.60','58.50','160.00',NULL,'78.00','99',NULL,'1','2026-09-02 22:25:04');
INSERT INTO `vital_signs` VALUES('11','75',NULL,NULL,'82','18','36.80',NULL,NULL,NULL,NULL,'99',NULL,'1','2026-09-02 22:35:16');
INSERT INTO `vital_signs` VALUES('12','78',NULL,NULL,'82','18','36.80',NULL,NULL,NULL,NULL,'99',NULL,'1','2026-09-02 22:35:30');

DROP TABLE IF EXISTS `wellbaby_records`;
CREATE TABLE `wellbaby_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `mother_patient_id` int(11) DEFAULT NULL,
  `birth_time` time DEFAULT NULL,
  `birth_weight_kg` decimal(4,2) NOT NULL,
  `birth_length_cm` decimal(4,1) NOT NULL,
  `place_of_delivery` enum('Hospital','Lying-in','Home','Others') NOT NULL DEFAULT 'Lying-in',
  `delivery_type` enum('Normal Spontaneous Delivery (NSD)','Caesarean Section (CS)','Others') NOT NULL DEFAULT 'Normal Spontaneous Delivery (NSD)',
  `attended_by` enum('Doctor','Nurse','Midwife','Hilot/TBA','Others') NOT NULL DEFAULT 'Midwife',
  `newborn_screening_done` tinyint(1) NOT NULL DEFAULT 0,
  `newborn_screening_date` date DEFAULT NULL,
  `newborn_screening_result` varchar(100) DEFAULT NULL,
  `mother_cpab_tt` varchar(100) DEFAULT NULL,
  `feeding_method` enum('LAM / Exclusive Breastfeeding','Bottle Feed','Mixed') NOT NULL DEFAULT 'LAM / Exclusive Breastfeeding',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_wellbaby_patient` (`patient_id`),
  KEY `idx_wellbaby_mother` (`mother_patient_id`),
  KEY `fk_wellbaby_created_by` (`created_by`),
  CONSTRAINT `fk_wellbaby_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_wellbaby_mother` FOREIGN KEY (`mother_patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_wellbaby_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `wellbaby_records` VALUES('2','21','20','05:30:00','3.20','50.0','Lying-in','Normal Spontaneous Delivery (NSD)','Midwife','1','2026-07-04','NORMAL','Protected at Birth (TT2 active)','LAM / Exclusive Breastfeeding','1','2026-09-02 16:28:35','2026-09-02 16:28:35');
INSERT INTO `wellbaby_records` VALUES('3','24','23','05:30:00','3.20','50.0','Lying-in','Normal Spontaneous Delivery (NSD)','Midwife','1','2026-07-04','NORMAL','Protected at Birth (TT2 active)','LAM / Exclusive Breastfeeding','1','2026-09-02 16:29:15','2026-09-02 16:29:15');
INSERT INTO `wellbaby_records` VALUES('4','26','25','05:30:00','3.20','50.0','Lying-in','Normal Spontaneous Delivery (NSD)','Midwife','1','2026-07-04','NORMAL','Protected at Birth (TT2 active)','LAM / Exclusive Breastfeeding','1','2026-09-02 16:30:29','2026-09-02 16:30:29');
INSERT INTO `wellbaby_records` VALUES('5','31','30','05:30:00','3.20','50.0','Lying-in','Normal Spontaneous Delivery (NSD)','Midwife','1','2026-07-04','NORMAL','Protected at Birth (TT2 active)','LAM / Exclusive Breastfeeding','1','2026-09-02 16:35:58','2026-09-02 16:35:58');
INSERT INTO `wellbaby_records` VALUES('6','34','33','05:30:00','3.20','50.0','Lying-in','Normal Spontaneous Delivery (NSD)','Midwife','1','2026-07-04','NORMAL','Protected at Birth (TT2 active)','LAM / Exclusive Breastfeeding','1','2026-09-02 20:35:46','2026-09-02 20:35:46');
INSERT INTO `wellbaby_records` VALUES('7','43','42','05:30:00','3.20','50.0','Lying-in','Normal Spontaneous Delivery (NSD)','Midwife','1','2026-07-04','NORMAL','Protected at Birth (TT2 active)','LAM / Exclusive Breastfeeding','1','2026-09-02 22:06:25','2026-09-02 22:06:25');
INSERT INTO `wellbaby_records` VALUES('8','50','49','08:30:00','3.10','49.0','','Normal Spontaneous Delivery (NSD)','','1','2026-07-04','Normal (Cert # NBS-2026-8888)','TT2 Complete in 2026','','1','2026-09-02 22:20:44','2026-09-02 22:20:44');
INSERT INTO `wellbaby_records` VALUES('9','52','51','05:30:00','3.20','50.0','Lying-in','Normal Spontaneous Delivery (NSD)','Midwife','1','2026-07-04','NORMAL','Protected at Birth (TT2 active)','LAM / Exclusive Breastfeeding','1','2026-09-02 22:20:48','2026-09-02 22:20:48');
INSERT INTO `wellbaby_records` VALUES('10','59','58','08:30:00','3.10','49.0','','Normal Spontaneous Delivery (NSD)','','1','2026-07-04','Normal (Cert # NBS-2026-8888)','TT2 Complete in 2026','','1','2026-09-02 22:21:02','2026-09-02 22:21:02');
INSERT INTO `wellbaby_records` VALUES('11','61','60','05:30:00','3.20','50.0','Lying-in','Normal Spontaneous Delivery (NSD)','Midwife','1','2026-07-04','NORMAL','Protected at Birth (TT2 active)','LAM / Exclusive Breastfeeding','1','2026-09-02 22:24:56','2026-09-02 22:24:56');
INSERT INTO `wellbaby_records` VALUES('12','68','67','08:30:00','3.10','49.0','','Normal Spontaneous Delivery (NSD)','','1','2026-07-04','Normal (Cert # NBS-2026-8888)','TT2 Complete in 2026','','1','2026-09-02 22:25:10','2026-09-02 22:25:10');
INSERT INTO `wellbaby_records` VALUES('13','77','75','06:15:00','3.25','50.0','','Normal Spontaneous Delivery (NSD)','','1','2026-06-04','Normal (Cert # NBS-P6-12345)','TT3 Complete','LAM / Exclusive Breastfeeding','1','2026-09-02 22:35:16','2026-09-02 22:35:16');
INSERT INTO `wellbaby_records` VALUES('14','80','78','06:15:00','3.25','50.0','','Normal Spontaneous Delivery (NSD)','','1','2026-06-04','Normal (Cert # NBS-P6-12345)','TT3 Complete','LAM / Exclusive Breastfeeding','1','2026-09-02 22:35:30','2026-09-02 22:35:30');

SET FOREIGN_KEY_CHECKS = 1;

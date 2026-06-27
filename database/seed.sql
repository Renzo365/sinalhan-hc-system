-- -------------------------------------------------------------
-- Barangay Sinalhan Health Center - Default Seeding Script
-- -------------------------------------------------------------

USE `sinalhan_hc_system`;

-- 1. Insert Default Admins and Staff
-- Note: Default passwords are bcrypt hashes of 'admin1234' and 'staff1234'
-- ALWAYS force password changes on first authentication!
INSERT INTO `users` (`username`, `password_hash`, `role`, `first_name`, `last_name`, `job_title`, `status`, `must_change_password`)
VALUES 
(
  'admin', 
  '$2y$10$Mp8OmLNojA8jzqN.PPLyBOUmHAuRNkqD0X6XCmDGB/4LfLJEc75rm', -- bcrypt hash for: admin1234
  'admin', 
  'System', 
  'Administrator', 
  'IT Support / Records Head', 
  'active',
  1
),
(
  'records_staff', 
  '$2y$10$MQ1JTmlMiH24jODpgfuA5OHI9tH96t1pvwsAXm58YbcGWXOn.T5ba', -- bcrypt hash for: staff1234
  'staff', 
  'Maria', 
  'Santos', 
  'Barangay Health Worker (BHW)', 
  'active',
  1
),
(
  'midwife_user', 
  '$2y$10$MQ1JTmlMiH24jODpgfuA5OHI9tH96t1pvwsAXm58YbcGWXOn.T5ba', -- bcrypt hash for: staff1234
  'staff', 
  'Juana', 
  'Dela Cruz', 
  'Midwife', 
  'active',
  1
);

-- 2. Insert Default Application Settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `description`, `updated_by`)
VALUES
('clinic_name', 'Barangay Sinalhan Health Center', 'The name of the healthcare center displayed on the system header.', 1),
('clinic_address', 'Barangay Sinalhan, Santa Rosa City, Laguna, Philippines', 'Physical address of the clinic used in printed report headers.', 1),
('queue_alert_sound', 'enabled', 'Plays a chime sound when a new queue number is called (enabled/disabled).', 1),
('public_queue_display_names', 'initials', 'Privacy option for queue display board: initials, none (number only), or full (show names).', 1),
('backup_interval_days', '7', 'Database reminder interval for admins to trigger a SQL backup.', 1);

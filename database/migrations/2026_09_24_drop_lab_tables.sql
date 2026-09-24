-- Drop unused lab_results and lab_requests tables
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `lab_results`;
DROP TABLE IF EXISTS `lab_requests`;
SET FOREIGN_KEY_CHECKS = 1;

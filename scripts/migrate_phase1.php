<?php

// Check if run from CLI
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

$dbConfig = require dirname(__DIR__) . '/config/database.php';

try {
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "Connected to database: {$dbConfig['dbname']}\n";

    // 1. Add columns to `patients` table if they don't exist
    $patientCols = [
        'family_no' => 'VARCHAR(50) NULL AFTER `patient_no`',
        'suffix' => 'VARCHAR(20) NULL AFTER `last_name`',
        'religion' => 'VARCHAR(100) NULL AFTER `blood_type`',
        'education_attainment' => "ENUM('No Schooling', 'Elementary', 'High School', 'Vocational', 'College / Post-Graduate') NULL AFTER `occupation`",
        'phic_status' => "ENUM('Member', 'Dependent', 'Non-Member') NOT NULL DEFAULT 'Non-Member' AFTER `address`",
        'phic_type' => 'VARCHAR(100) NULL AFTER `phic_status`',
        'father_name' => 'VARCHAR(150) NULL AFTER `philhealth_no`',
        'father_dob' => 'DATE NULL AFTER `father_name`',
        'mother_name' => 'VARCHAR(150) NULL AFTER `father_dob`',
        'mother_dob' => 'DATE NULL AFTER `mother_name`',
        'spouse_name' => 'VARCHAR(150) NULL AFTER `mother_dob`',
        'spouse_dob' => 'DATE NULL AFTER `spouse_name`'
    ];

    echo "Checking columns in `patients` table...\n";
    foreach ($patientCols as $col => $definition) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = 'patients' AND COLUMN_NAME = :col");
        $stmt->execute(['db' => $dbConfig['dbname'], 'col' => $col]);
        if ($stmt->fetchColumn() == 0) {
            $pdo->exec("ALTER TABLE `patients` ADD COLUMN `$col` $definition");
            echo "  [+] Added column `$col` to `patients`\n";
        } else {
            echo "  [.] Column `$col` already exists in `patients`\n";
        }
    }

    // Add index on family_no if not present
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = 'patients' AND INDEX_NAME = 'idx_patients_family_no'");
    $stmt->execute(['db' => $dbConfig['dbname']]);
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("ALTER TABLE `patients` ADD INDEX `idx_patients_family_no` (`family_no`)");
        echo "  [+] Added index `idx_patients_family_no` to `patients`\n";
    }

    // 2. Add columns to `vital_signs` table if they don't exist
    echo "Checking columns in `vital_signs` table...\n";
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = 'vital_signs' AND COLUMN_NAME = 'waist_circumference'");
    $stmt->execute(['db' => $dbConfig['dbname']]);
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("ALTER TABLE `vital_signs` ADD COLUMN `waist_circumference` DECIMAL(5,2) NULL AFTER `bmi`");
        echo "  [+] Added column `waist_circumference` to `vital_signs`\n";
    } else {
        echo "  [.] Column `waist_circumference` already exists in `vital_signs`\n";
    }

    // 3. Create table `patient_medical_histories`
    echo "Creating table `patient_medical_histories`...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `patient_medical_histories` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `patient_id` INT NOT NULL UNIQUE,
        `past_medical_history` TEXT NULL COMMENT 'JSON/Text structured checklist of chronic illnesses',
        `surgical_history` TEXT NULL COMMENT 'JSON/Text structured past operations and dates',
        `family_history` TEXT NULL COMMENT 'JSON/Text structured family hereditary illnesses',
        `smoking_status` ENUM('Never', 'Yes', 'Quit') NOT NULL DEFAULT 'Never',
        `smoking_pack_years` DECIMAL(4,1) NULL,
        `alcohol_status` ENUM('Never', 'Yes', 'Quit') NOT NULL DEFAULT 'Never',
        `alcohol_bottles_per_day` DECIMAL(4,1) NULL,
        `illicit_drugs` TINYINT(1) NOT NULL DEFAULT 0,
        `menarche_age` INT NULL,
        `sexual_onset_age` INT NULL,
        `lmp` DATE NULL,
        `period_duration_days` INT NULL,
        `cycle_interval_days` INT NULL,
        `pads_per_day` INT NULL,
        `is_menopausal` TINYINT(1) NOT NULL DEFAULT 0,
        `menopause_age` INT NULL,
        `birth_control_method` VARCHAR(100) NULL,
        `updated_by` INT NULL,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT `fk_pmh_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_pmh_updater` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
        INDEX `idx_pmh_patient` (`patient_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "  [+] `patient_medical_histories` ready.\n";

    // 4. Create table `prenatal_records`
    echo "Creating table `prenatal_records`...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `prenatal_records` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `patient_id` INT NOT NULL,
        `husband_name` VARCHAR(150) NULL,
        `gravida` INT NOT NULL DEFAULT 1,
        `para` INT NOT NULL DEFAULT 0,
        `term_births` INT NOT NULL DEFAULT 0,
        `preterm_births` INT NOT NULL DEFAULT 0,
        `abortions` INT NOT NULL DEFAULT 0,
        `living_children` INT NOT NULL DEFAULT 0,
        `lmp` DATE NOT NULL,
        `edc` DATE NOT NULL,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `pre_eclampsia` TINYINT(1) NOT NULL DEFAULT 0,
        `fp_counselling` TINYINT(1) NOT NULL DEFAULT 1,
        `delivery_date` DATE NULL,
        `delivery_outcome` ENUM('Live Birth', 'Stillbirth', 'Miscarriage', 'Ectopic', 'Other') NULL,
        `notes` TEXT NULL,
        `created_by` INT NOT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT `fk_pr_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT,
        CONSTRAINT `fk_pr_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
        INDEX `idx_pr_patient` (`patient_id`),
        INDEX `idx_pr_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "  [+] `prenatal_records` ready.\n";

    // 5. Create table `prenatal_visits`
    echo "Creating table `prenatal_visits`...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `prenatal_visits` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `prenatal_id` INT NOT NULL,
        `visit_date` DATE NOT NULL,
        `chief_complaint` VARCHAR(255) NULL,
        `aog_weeks` DECIMAL(4,1) NOT NULL,
        `bp_systolic` INT NULL,
        `bp_diastolic` INT NULL,
        `weight_kg` DECIMAL(5,2) NULL,
        `height_cm` DECIMAL(5,2) NULL,
        `fetal_heart_tone` INT NULL COMMENT 'FHT in bpm',
        `fundal_height_cm` DECIMAL(4,1) NULL COMMENT 'Fundal height in cm',
        `fetal_presentation` ENUM('Cephalic', 'Breech', 'Transverse', 'Undetermined') NOT NULL DEFAULT 'Cephalic',
        `tcb` VARCHAR(100) NULL COMMENT 'Target Client Benefit / Tetanus status',
        `remarks` TEXT NULL,
        `attended_by` INT NOT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT `fk_pv_prenatal` FOREIGN KEY (`prenatal_id`) REFERENCES `prenatal_records` (`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_pv_attendant` FOREIGN KEY (`attended_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
        INDEX `idx_pv_prenatal` (`prenatal_id`),
        INDEX `idx_pv_visit_date` (`visit_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "  [+] `prenatal_visits` ready.\n";

    // 6. Create table `past_obstetric_histories`
    echo "Creating table `past_obstetric_histories`...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `past_obstetric_histories` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `patient_id` INT NOT NULL,
        `gravida_no` INT NOT NULL,
        `delivery_type` ENUM('NSD', 'CS', 'Abortion', 'Other') NOT NULL DEFAULT 'NSD',
        `infant_sex` ENUM('Male', 'Female', 'Unknown') NOT NULL DEFAULT 'Unknown',
        `place_of_delivery` VARCHAR(150) NULL,
        `year_delivered` INT NULL,
        `attended_by` VARCHAR(100) NULL,
        `status` ENUM('Alive', 'Not Alive') NOT NULL DEFAULT 'Alive',
        `birth_date` DATE NULL,
        `tt_status` VARCHAR(100) NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT `fk_poh_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
        INDEX `idx_poh_patient` (`patient_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "  [+] `past_obstetric_histories` ready.\n";

    // 7. Create table `wellbaby_records`
    echo "Creating table `wellbaby_records`...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `wellbaby_records` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `patient_id` INT NOT NULL UNIQUE,
        `mother_patient_id` INT NULL,
        `birth_time` TIME NULL,
        `birth_weight_kg` DECIMAL(4,2) NOT NULL,
        `birth_length_cm` DECIMAL(4,1) NOT NULL,
        `place_of_delivery` ENUM('Hospital', 'Lying-in', 'Home', 'Others') NOT NULL DEFAULT 'Lying-in',
        `delivery_type` ENUM('Normal Spontaneous Delivery (NSD)', 'Caesarean Section (CS)', 'Others') NOT NULL DEFAULT 'Normal Spontaneous Delivery (NSD)',
        `attended_by` ENUM('Doctor', 'Nurse', 'Midwife', 'Hilot/TBA', 'Others') NOT NULL DEFAULT 'Midwife',
        `newborn_screening_done` TINYINT(1) NOT NULL DEFAULT 0,
        `newborn_screening_date` DATE NULL,
        `newborn_screening_result` VARCHAR(100) NULL,
        `mother_cpab_tt` VARCHAR(100) NULL,
        `feeding_method` ENUM('LAM / Exclusive Breastfeeding', 'Bottle Feed', 'Mixed') NOT NULL DEFAULT 'LAM / Exclusive Breastfeeding',
        `created_by` INT NOT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT `fk_wb_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_wb_mother` FOREIGN KEY (`mother_patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL,
        CONSTRAINT `fk_wb_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
        INDEX `idx_wb_patient` (`patient_id`),
        INDEX `idx_wb_mother` (`mother_patient_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "  [+] `wellbaby_records` ready.\n";

    // 8. Create table `child_growth_logs`
    echo "Creating table `child_growth_logs`...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `child_growth_logs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `wellbaby_id` INT NOT NULL,
        `log_date` DATE NOT NULL,
        `age_months` DECIMAL(4,1) NOT NULL,
        `weight_kg` DECIMAL(5,2) NOT NULL,
        `height_cm` DECIMAL(5,2) NOT NULL,
        `head_circumference_cm` DECIMAL(4,1) NULL,
        `chest_circumference_cm` DECIMAL(4,1) NULL,
        `temperature` DECIMAL(4,2) NULL,
        `feeding_method` ENUM('LAM / Exclusive Breastfeeding', 'Bottle Feed', 'Mixed') NOT NULL DEFAULT 'LAM / Exclusive Breastfeeding',
        `vaccines_administered` VARCHAR(255) NULL,
        `vitamin_a_dose` TINYINT(1) NOT NULL DEFAULT 0,
        `deworming_dose` TINYINT(1) NOT NULL DEFAULT 0,
        `tcb_notes` TEXT NULL,
        `recorded_by` INT NOT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT `fk_cgl_wellbaby` FOREIGN KEY (`wellbaby_id`) REFERENCES `wellbaby_records` (`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_cgl_recorder` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
        INDEX `idx_cgl_wellbaby` (`wellbaby_id`),
        INDEX `idx_cgl_log_date` (`log_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "  [+] `child_growth_logs` ready.\n";

    echo "\n=== Migration Phase 1 Completed Successfully! ===\n";

} catch (PDOException $e) {
    die("Migration Error: " . $e->getMessage() . "\n");
}

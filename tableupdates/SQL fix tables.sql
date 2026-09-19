-- ==========================================
-- 1. CREATE MISSING TABLES
-- ==========================================

CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `photo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `client_consents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `p_id` int NOT NULL COMMENT 'ID from the patients table',
  `mat_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `client_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `sex` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `visit_date` date NOT NULL,
  `cso` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `declaration_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `national_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `client_age` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `guardian_accompany` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'If accompanied by guardian/parent',
  `guardian_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `guardian_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mat_facility` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `clinician_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `clinician_org` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `clinician_signature` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `clinician_date` date NOT NULL,
  `counselor_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `counselor_org` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `counselor_signature` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `counselor_date` date NOT NULL,
  `pdf_filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Stored name of the generated PDF file',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `consent_status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'yes',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `clinical_encounters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `facility_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mfl_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `county` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sub_county` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `enrolment_date` date DEFAULT NULL,
  `enrolment_time` time DEFAULT NULL,
  `visit_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `client_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nickname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mat_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sex` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `presenting_complaints` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `injecting_history` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reasons_injecting` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `reasons_injecting_other` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `flash_blood` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `shared_needles` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `injecting_complications` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `drug_overdose` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pulse` int DEFAULT NULL,
  `oxygen_saturation` int DEFAULT NULL,
  `blood_pressure` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `temperature` decimal(4,2) DEFAULT NULL,
  `respiratory_rate` int DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `bmi` decimal(4,2) DEFAULT NULL,
  `bmi_interpretation` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cows_provider` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cows_date` date DEFAULT NULL,
  `cows_scores` json DEFAULT NULL,
  `cows_totals` json DEFAULT NULL,
  `cows_interpretations` json DEFAULT NULL,
  `medical_history` json DEFAULT NULL,
  `medical_medication` json DEFAULT NULL,
  `hiv_diagnosis_date` date DEFAULT NULL,
  `hiv_facility_care` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `other_medical_problems` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `allergies` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `allergies_other` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contraception_use` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contraception_method` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `last_menstrual_period` date DEFAULT NULL,
  `pregnancy_status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pregnancy_weeks` int DEFAULT NULL,
  `breastfeeding` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mental_health_diagnosis` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mental_health_condition` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `mental_health_other` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mental_health_medication` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mental_health_medication_details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `suicidal_thoughts` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `psychiatric_hospitalization` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `family_drug_use` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `family_mental_health` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `family_medical_conditions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `family_medical_other` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `general_appearance` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `skin_examination` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `head_examination` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `eyes_examination` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ears_examination` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `nose_examination` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `mouth_throat_examination` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `neck_examination` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `chest_examination` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `heart_examination` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `abdomen_examination` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `genitalia_examination` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `extremities_examination` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `neurological_examination` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `musculoskeletal_examination` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `diagnosis_opioid_use` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `other_diagnoses` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `treatment_plan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `medication_prescribed` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `medication_other` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `initial_dose` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `next_appointment` date DEFAULT NULL,
  `clinician_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `clinician_signature` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `patient_consent` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('draft','complete') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'draft',
  `current_section` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'facility',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `clinical_encounter_drafts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `form_data` json NOT NULL,
  `current_section` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `clinician_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `countries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `iso2` char(2) NOT NULL DEFAULT '',
  `region` varchar(40) DEFAULT '',
  `sort_order` int DEFAULT '100',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `deleted_prescriptions` (
  `del_id` int NOT NULL AUTO_INCREMENT,
  `disp_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `clientName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mat_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sex` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `drugname` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dosage` int DEFAULT NULL,
  `pharm_officer_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dispDate` date DEFAULT NULL,
  `deletion_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_by` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date_of_deletion` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`del_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `demo_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `first_name` varchar(80) NOT NULL,
  `last_name` varchar(80) NOT NULL,
  `clinic_name` varchar(160) NOT NULL,
  `email` varchar(120) NOT NULL,
  `phone` varchar(40) NOT NULL,
  `country` varchar(80) NOT NULL,
  `plan` varchar(40) DEFAULT 'professional',
  `token` varchar(64) NOT NULL,
  `user_id` int DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `notified_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `dose_schedules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mat_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `drugname` varchar(225) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `dose_mg` decimal(10,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL COMMENT 'NULL = open-ended / no end date',
  `skip_dates` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'JSON array of YYYY-MM-DD dates to skip (Buprenorphine only)',
  `dosing_interval_days` tinyint UNSIGNED DEFAULT '1',
  `comments` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Mandatory clinical comment for this dose period',
  `created_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` enum('active','superseded','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ==========================================
-- 2. APPLY MISSING PRIMARY KEYS & AUTO_INCREMENTS
-- ==========================================

ALTER TABLE `backup_log` 
  ADD PRIMARY KEY (`id`),
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `clinic_visits` 
  ADD PRIMARY KEY (`clinic_id`),
  MODIFY `clinic_id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `daily_report_comments` 
  ADD PRIMARY KEY (`comment_id`),
  MODIFY `comment_id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `deleted_patients` 
  ADD PRIMARY KEY (`p_id`);

ALTER TABLE `dispensing_comments` 
  ADD PRIMARY KEY (`comment_id`),
  MODIFY `comment_id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `facerecognitionnodes` 
  ADD PRIMARY KEY (`Id`),
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;


-- ==========================================
-- 1. CREATE MISSING TABLES
-- ==========================================

CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `photo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `client_consents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `p_id` int NOT NULL COMMENT 'ID from the patients table',
  `mat_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `client_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `sex` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `visit_date` date NOT NULL,
  `cso` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `declaration_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `national_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `client_age` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `guardian_accompany` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'If accompanied by guardian/parent',
  `guardian_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `guardian_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mat_facility` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `clinician_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `clinician_org` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `clinician_signature` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `clinician_date` date NOT NULL,
  `counselor_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `counselor_org` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `counselor_signature` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `counselor_date` date NOT NULL,
  `pdf_filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Stored name of the generated PDF file',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `consent_status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'yes',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `clinical_encounters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `facility_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mfl_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `county` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sub_county` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `enrolment_date` date DEFAULT NULL,
  `enrolment_time` time DEFAULT NULL,
  `visit_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `client_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nickname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mat_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sex` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `presenting_complaints` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `injecting_history` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reasons_injecting` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `reasons_injecting_other` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `flash_blood` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `shared_needles` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `injecting_complications` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `drug_overdose` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pulse` int DEFAULT NULL,
  `oxygen_saturation` int DEFAULT NULL,
  `blood_pressure` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `temperature` decimal(4,2) DEFAULT NULL,
  `respiratory_rate` int DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `bmi` decimal(4,2) DEFAULT NULL,
  `bmi_interpretation` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cows_provider` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cows_date` date DEFAULT NULL,
  `cows_scores` json DEFAULT NULL,
  `cows_totals` json DEFAULT NULL,
  `cows_interpretations` json DEFAULT NULL,
  `medical_history` json DEFAULT NULL,
  `medical_medication` json DEFAULT NULL,
  `hiv_diagnosis_date` date DEFAULT NULL,
  `hiv_facility_care` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `other_medical_problems` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `allergies` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `allergies_other` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contraception_use` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contraception_method` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `last_menstrual_period` date DEFAULT NULL,
  `pregnancy_status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pregnancy_weeks` int DEFAULT NULL,
  `breastfeeding` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mental_health_diagnosis` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mental_health_condition` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `mental_health_other` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mental_health_medication` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mental_health_medication_details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `suicidal_thoughts` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `psychiatric_hospitalization` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `family_drug_use` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `family_mental_health` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `family_medical_conditions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `family_medical_other` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `general_appearance` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `skin_examination` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `head_examination` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `eyes_examination` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ears_examination` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `nose_examination` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `mouth_throat_examination` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `neck_examination` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `chest_examination` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `heart_examination` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `abdomen_examination` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `genitalia_examination` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `extremities_examination` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `neurological_examination` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `musculoskeletal_examination` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `diagnosis_opioid_use` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `other_diagnoses` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `treatment_plan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `medication_prescribed` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `medication_other` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `initial_dose` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `next_appointment` date DEFAULT NULL,
  `clinician_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `clinician_signature` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `patient_consent` enum('yes','no') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('draft','complete') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'draft',
  `current_section` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'facility',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `clinical_encounter_drafts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `form_data` json NOT NULL,
  `current_section` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `clinician_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `countries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `iso2` char(2) NOT NULL DEFAULT '',
  `region` varchar(40) DEFAULT '',
  `sort_order` int DEFAULT '100',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `deleted_prescriptions` (
  `del_id` int NOT NULL AUTO_INCREMENT,
  `disp_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `clientName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mat_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sex` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `drugname` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dosage` int DEFAULT NULL,
  `pharm_officer_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dispDate` date DEFAULT NULL,
  `deletion_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_by` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date_of_deletion` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`del_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `demo_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `first_name` varchar(80) NOT NULL,
  `last_name` varchar(80) NOT NULL,
  `clinic_name` varchar(160) NOT NULL,
  `email` varchar(120) NOT NULL,
  `phone` varchar(40) NOT NULL,
  `country` varchar(80) NOT NULL,
  `plan` varchar(40) DEFAULT 'professional',
  `token` varchar(64) NOT NULL,
  `user_id` int DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `notified_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `dose_schedules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mat_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `drugname` varchar(225) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `dose_mg` decimal(10,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL COMMENT 'NULL = open-ended / no end date',
  `skip_dates` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'JSON array of YYYY-MM-DD dates to skip (Buprenorphine only)',
  `dosing_interval_days` tinyint UNSIGNED DEFAULT '1',
  `comments` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Mandatory clinical comment for this dose period',
  `created_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` enum('active','superseded','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


ALTER TABLE `prescription_drugs`
    ADD COLUMN `is_dot` TINYINT(1) NOT NULL DEFAULT 0
    COMMENT 'Directly Observed Therapy: always co-dispensed with the MAT drug'
    AFTER `prescr_status`;
-- ==========================================
-- 2. ALTER EXISTING TABLES (PRIMARY KEYS & AUTO INCREMENT)
-- ==========================================

ALTER TABLE `backup_log` 
  ADD PRIMARY KEY IF NOT EXISTS (`id`),
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `clinic_visits` 
  ADD PRIMARY KEY IF NOT EXISTS (`clinic_id`),
  MODIFY `clinic_id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `daily_report_comments` 
  ADD PRIMARY KEY IF NOT EXISTS (`comment_id`),
  MODIFY `comment_id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `deleted_patients` 
  ADD PRIMARY KEY IF NOT EXISTS (`p_id`);

ALTER TABLE `dispensing_comments` 
  ADD PRIMARY KEY IF NOT EXISTS (`comment_id`),
  MODIFY `comment_id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `facerecognitionnodes` 
  ADD PRIMARY KEY IF NOT EXISTS (`Id`),
  MODIFY `Id` int NOT NULL AUTO_INCREMENT;
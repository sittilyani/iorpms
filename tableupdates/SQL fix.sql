-- =============================================================
-- fix_pump_reservoir_history_autoincrement.sql
-- =============================================================
-- Symptom:
--   Fatal error: Field 'id' doesn't have a default value
--   in pharmacy/pump_reservoir.php on line 102
--
-- Cause:
--   pump_reservoir_history.id is INT NOT NULL but has no
--   AUTO_INCREMENT, so INSERT statements that omit 'id' fail.
--
-- Fix:
--   Add AUTO_INCREMENT to the id column.
--   Safe to run on a live database — existing rows are untouched.
-- =============================================================

ALTER TABLE `pump_reservoir_history`
    MODIFY `id` INT NOT NULL AUTO_INCREMENT;

SELECT 'pump_reservoir_history AUTO_INCREMENT fix applied.' AS message;

ALTER TABLE `pump_calibration`
    ADD COLUMN  `temperature_celsius`      DECIMAL(5,2)    NOT NULL DEFAULT 20.00
        COMMENT 'Ambient/solution temperature at time of calibration (°C). Used for viscosity correction.',
    ADD COLUMN  `density_g_per_ml`         DECIMAL(6,4)    NOT NULL DEFAULT 1.0200
        COMMENT 'Solution density (g/mL). Methadone 5 mg/mL ≈ 1.02 g/mL. Recorded for traceability.',
    ADD COLUMN  `tube_inner_diameter_mm`   DECIMAL(5,2)    DEFAULT NULL
        COMMENT 'Peristaltic tubing inner diameter in mm (e.g. L/S-14 = 1.60 mm). NULL = not recorded.',
    ADD COLUMN  `tube_type_code`           VARCHAR(20)     DEFAULT NULL
        COMMENT 'Masterflex L/S tubing size code, e.g. LS-14, LS-16, LS-25.',
    ADD COLUMN  `calibration_volume_ml`    DECIMAL(8,4)    NOT NULL DEFAULT 10.0000
        COMMENT 'Target volume used for this calibration run (always 10 mL for this system).',
    ADD COLUMN  `measured_volume_ml`       DECIMAL(8,4)    DEFAULT NULL
        COMMENT 'Actual volume measured in graduated cylinder after calibration dispense.',
    ADD COLUMN  `volume_correction`        DECIMAL(10,6)   DEFAULT NULL
        COMMENT 'Ratio: target_ml / measured_ml. Values > 1 mean pump under-delivered.',
    ADD COLUMN  `temp_correction`          DECIMAL(10,6)   DEFAULT NULL
        COMMENT 'Temperature-viscosity correction multiplier applied to calibration factor.',
    ADD COLUMN  `previous_factor`          DECIMAL(10,4)   DEFAULT NULL
        COMMENT 'Calibration factor that was active before this record was created.';

-- For older MySQL that doesn't support ADD COLUMN ,
-- comment out the block above and run each line individually after
-- checking if the column already exists.

-- Seed a sensible starting calibration if none exists
-- (Safe to skip if pump_calibration already has data)
INSERT IGNORE INTO `pump_calibration`
    (id, pump_id, calibration_factor, concentration_mg_per_ml, tubing_type,
     calibrated_by, notes, is_active,
     temperature_celsius, density_g_per_ml, calibration_volume_ml)
SELECT 1, 1, 400.00, 5.00, NULL, 'System', 'Default calibration — 400 units/mL', 1,
       20.00, 1.0200, 10.0000
WHERE NOT EXISTS (SELECT 1 FROM pump_calibration WHERE id = 1);

SELECT 'pump_calibration_v2 migration complete.' AS message;


-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 28, 2026 at 12:56 PM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `methadone`
--

-- --------------------------------------------------------

--
-- Table structure for table `facerecognitionnodes`
--

DROP TABLE `facerecognitionnodes`;
CREATE TABLE `facerecognitionnodes` (
  `Id` int NOT NULL,
  `PatientId` int DEFAULT NULL,
  `Complexity` double NOT NULL,
  `Data` longblob NOT NULL,
  `L1Norm` double NOT NULL,
  `DistanceToParent` double DEFAULT NULL,
  `LeftId` int DEFAULT NULL,
  `Radius` double NOT NULL,
  `RightId` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gad7_assessments`
--

DROP TABLE `gad7_assessments`;
CREATE TABLE `gad7_assessments` (
  `assessment_id` int UNSIGNED NOT NULL,
  `p_id` int UNSIGNED NOT NULL,
  `mat_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `visitDate` date NOT NULL,
  `therapist_id` int UNSIGNED NOT NULL,
  `q1` tinyint(1) NOT NULL,
  `q2` tinyint(1) NOT NULL,
  `q3` tinyint(1) NOT NULL,
  `q4` tinyint(1) NOT NULL,
  `q5` tinyint(1) NOT NULL,
  `q6` tinyint(1) NOT NULL,
  `q7` tinyint(1) NOT NULL,
  `total_score` tinyint NOT NULL,
  `diagnosis` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `management_plan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gad7_assessments`
--

-- --------------------------------------------------------

--
-- Table structure for table `initial_psychiatric_form`
--

DROP TABLE `initial_psychiatric_form`;
CREATE TABLE `initial_psychiatric_form` (
  `psy_id` int NOT NULL,
  `mat_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `occupation` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `visitDate` date DEFAULT NULL,
  `referral` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rx_supporter` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pre_complaints` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `corr_complaints` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `hx_illness` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `past_psych_hx` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `past_med_hx` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `sub_use_hx` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `fam_hx` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ante_hx` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `dev_hx` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `child_hx` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `edu_hx` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `occup_hx` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `sex_hx` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `premord_hx` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `forens_hx` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `phys_exam` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `mental_exam` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `diagnosis` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `mgt_plan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `involuntary_discontinuation`
--

DROP TABLE `involuntary_discontinuation`;
CREATE TABLE `involuntary_discontinuation` (
  `id` int NOT NULL,
  `visit_date` date NOT NULL,
  `client_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `mat_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `enroll_date` date NOT NULL,
  `discontinue_date` date NOT NULL,
  `reasons` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `discontinuation_plan` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `follow_up_plan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `clinician_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `clinician_org` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `clinician_signature` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `clinician_date` date NOT NULL,
  `counselor_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `counselor_org` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `counselor_signature` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `counselor_date` date NOT NULL,
  `cso_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cso_org` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cso_signature` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cso_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patientfingerprints`
--

DROP TABLE `patientfingerprints`;
CREATE TABLE `patientfingerprints` (
  `Id` int NOT NULL,
  `Template` longblob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patientsignatures`
--

DROP TABLE `patientsignatures`;
CREATE TABLE `patientsignatures` (
  `SessionId` int NOT NULL,
  `ImageData` longblob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_checkins`
--

DROP TABLE `patient_checkins`;
CREATE TABLE `patient_checkins` (
  `checkin_id` int NOT NULL,
  `patient_id` int DEFAULT NULL,
  `mat_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `visit_type` enum('Induction','Re-induction','Revisit') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `checked_in_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `checkin_date` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `phq9_assessments`
--

DROP TABLE `phq9_assessments`;
CREATE TABLE `phq9_assessments` (
  `assessment_id` int UNSIGNED NOT NULL,
  `p_id` int UNSIGNED NOT NULL,
  `mat_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `visitDate` date NOT NULL,
  `therapist_id` int UNSIGNED NOT NULL,
  `q1` tinyint(1) NOT NULL,
  `q2` tinyint(1) NOT NULL,
  `q3` tinyint(1) NOT NULL,
  `q4` tinyint(1) NOT NULL,
  `q5` tinyint(1) NOT NULL,
  `q6` tinyint(1) NOT NULL,
  `q7` tinyint(1) NOT NULL,
  `q8` tinyint(1) NOT NULL,
  `q9` tinyint(1) NOT NULL,
  `total_score` tinyint NOT NULL,
  `diagnosis` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `management_plan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `phq9_assessments`
--

INSERT INTO `phq9_assessments` (`assessment_id`, `p_id`, `mat_id`, `visitDate`, `therapist_id`, `q1`, `q2`, `q3`, `q4`, `q5`, `q6`, `q7`, `q8`, `q9`, `total_score`, `diagnosis`, `management_plan`, `created_at`) VALUES
(1, 1, '11094MAT0001', '2026-04-23', 1, 1, 1, 0, 1, 2, 2, 1, 1, 1, 10, 'Moderate depression*', 'Provide supportive counselling (refer to a psychologist if available).\nRefer to a medical officer, psychiatrist, or mental health team if available.\nIf patient is on ARVs that causes anxiety, substitute with a different ARV after ruling out treatment failure IF APPLICABLE (See \'Managing Single Drug Substitutions for ART\').\n*Symptoms should ideally be present for at least 2 weeks for a diagnosis of depression and before considering treatment with antidepressant medication.', '2026-04-23 12:53:48');

-- --------------------------------------------------------

--
-- Table structure for table `psychosocial_intake_form_1a`
--

DROP TABLE `psychosocial_intake_form_1a`;
CREATE TABLE `psychosocial_intake_form_1a` (
  `id` int NOT NULL,
  `visitDate` date DEFAULT NULL,
  `visit_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `clientName` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mat_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sex` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `other_sex_specify` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pre_complaints` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `corr_complaints` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `hx_illness` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `past_psych_hx` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `past_med_hx` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `sub_use_hx` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `fam_hx` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `intake_date` date DEFAULT NULL,
  `marital_status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `marital_other_specify` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `living_arrangements` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `living_arrangements_detail` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `living_other_specify` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `previous_treatment` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `treatment_specify` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sexually_active` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sexual_partners` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `unprotected_sex` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `education_level` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `education_other_specify` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `has_income` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `income_specify` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `employment_status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `missed_work` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fired_work` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `therapist_initials` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `family_relationship` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `has_dependents` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dependents` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dependent_other_specify` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `has_support` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `support_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ante_hx` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dev_hx` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `child_hx` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gbv_experience` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gbv_description` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gbv_reported` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gbv_medical` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `has_case` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `case_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `case_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `premord_hx` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `forens_hx` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phys_exam` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mental_exam` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `diagnosis` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mgt_plan` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `therapist_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `service_date` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rx_supporter` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `referral` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pump_calibration`
--

DROP TABLE `pump_calibration`;
CREATE TABLE `pump_calibration` (
  `id` int NOT NULL,
  `pump_id` int NOT NULL,
  `calibration_factor` decimal(10,2) NOT NULL,
  `concentration_mg_per_ml` decimal(5,2) NOT NULL DEFAULT '5.00',
  `tubing_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `calibrated_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `calibrated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `temperature_celsius` decimal(5,2) NOT NULL DEFAULT '20.00' COMMENT 'Ambient/solution temperature at time of calibration (°C). Used for viscosity correction.',
  `density_g_per_ml` decimal(6,4) NOT NULL DEFAULT '1.0200' COMMENT 'Solution density (g/mL). Methadone 5 mg/mL ≈ 1.02 g/mL. Recorded for traceability.',
  `tube_inner_diameter_mm` decimal(5,2) DEFAULT NULL COMMENT 'Peristaltic tubing inner diameter in mm (e.g. L/S-14 = 1.60 mm). NULL = not recorded.',
  `tube_type_code` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Masterflex L/S tubing size code, e.g. LS-14, LS-16, LS-25.',
  `calibration_volume_ml` decimal(8,4) NOT NULL DEFAULT '10.0000' COMMENT 'Target volume used for this calibration run (always 10 mL for this system).',
  `measured_volume_ml` decimal(8,4) DEFAULT NULL COMMENT 'Actual volume measured in graduated cylinder after calibration dispense.',
  `volume_correction` decimal(10,6) DEFAULT NULL COMMENT 'Ratio: target_ml / measured_ml. Values > 1 mean pump under-delivered.',
  `temp_correction` decimal(10,6) DEFAULT NULL COMMENT 'Temperature-viscosity correction multiplier applied to calibration factor.',
  `previous_factor` decimal(10,4) DEFAULT NULL COMMENT 'Calibration factor that was active before this record was created.'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pump_calibration`
--

INSERT INTO `pump_calibration` (`id`, `pump_id`, `calibration_factor`, `concentration_mg_per_ml`, `tubing_type`, `calibrated_by`, `calibrated_at`, `notes`, `is_active`, `temperature_celsius`, `density_g_per_ml`, `tube_inner_diameter_mm`, `tube_type_code`, `calibration_volume_ml`, `measured_volume_ml`, `volume_correction`, `temp_correction`, `previous_factor`) VALUES
(1, 1, '500.00', '5.00', NULL, 'System', '2026-01-24 04:25:54', 'Default calibration', 1, '20.00', '1.0200', NULL, NULL, '10.0000', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pump_devices`
--

DROP TABLE `pump_devices`;
CREATE TABLE `pump_devices` (
  `id` int NOT NULL,
  `label` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `port` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `pump_host` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'localhost' COMMENT 'IP/hostname of computer this pump is connected to. Use "localhost" for the server machine.',
  `is_reversed` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = pump motor runs in reverse; command direction will be inverted',
  `api_secret` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'Shared secret for the local pump API on client machines'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pump_devices`
--


-- --------------------------------------------------------

--
-- Table structure for table `pump_reservoir_history`
--

DROP TABLE `pump_reservoir_history`;
CREATE TABLE `pump_reservoir_history` (
  `id` int NOT NULL,
  `pump_id` int NOT NULL,
  `milligrams` decimal(10,2) NOT NULL,
  `new_milligrams` decimal(10,2) NOT NULL,
  `topup_from` datetime NOT NULL,
  `topup_to` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pump_reservoir_history`
--


-- --------------------------------------------------------

--
-- Table structure for table `transfer_forms`
--

DROP TABLE `transfer_forms`;
CREATE TABLE `transfer_forms` (
  `id` int NOT NULL,
  `p_id` int NOT NULL,
  `facilityname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `mflcode` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `county` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `sub_county` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `clientName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `mat_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `sex` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `dob` date NOT NULL,
  `client_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reg_facility` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `reg_date` date NOT NULL,
  `referral_date` date NOT NULL,
  `type_of_movement` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `other_specify` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `from_site` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `to_site` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `reason_transfer` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `clinical_history` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `psychosocial` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `lab_investigations` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `vaccinations` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `diagnosis` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `current_dose` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `date_last_administered` datetime NOT NULL,
  `other_medications` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `clinician_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `clinician_org` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `clinician_signature` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `clinician_date` date NOT NULL,
  `counselor_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `counselor_org` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `counselor_signature` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `counselor_date` date NOT NULL,
  `pdf_filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `json_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'JSON data for future API/development use',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `treatment_stage`
--


--
-- Indexes for dumped tables
--

--
-- Indexes for table `facerecognitionnodes`
--
ALTER TABLE `facerecognitionnodes`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `gad7_assessments`
--
ALTER TABLE `gad7_assessments`
  ADD PRIMARY KEY (`assessment_id`);

--
-- Indexes for table `initial_psychiatric_form`
--
ALTER TABLE `initial_psychiatric_form`
  ADD PRIMARY KEY (`psy_id`),
  ADD KEY `mat_id` (`mat_id`);

--
-- Indexes for table `involuntary_discontinuation`
--
ALTER TABLE `involuntary_discontinuation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mat_id` (`mat_id`),
  ADD KEY `idx_client_name` (`client_name`),
  ADD KEY `idx_discontinue_date` (`discontinue_date`);

--
-- Indexes for table `patientfingerprints`
--
ALTER TABLE `patientfingerprints`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `patient_checkins`
--
ALTER TABLE `patient_checkins`
  ADD PRIMARY KEY (`checkin_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `phq9_assessments`
--
ALTER TABLE `phq9_assessments`
  ADD PRIMARY KEY (`assessment_id`);

--
-- Indexes for table `psychosocial_intake_form_1a`
--
ALTER TABLE `psychosocial_intake_form_1a`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pump_calibration`
--
ALTER TABLE `pump_calibration`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pump_active` (`pump_id`,`is_active`);

--
-- Indexes for table `pump_devices`
--
ALTER TABLE `pump_devices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pump_reservoir_history`
--
ALTER TABLE `pump_reservoir_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pump_id` (`pump_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `gad7_assessments`
--
ALTER TABLE `gad7_assessments`
  MODIFY `assessment_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `phq9_assessments`
--
ALTER TABLE `phq9_assessments`
  MODIFY `assessment_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pump_calibration`
--
ALTER TABLE `pump_calibration`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pump_devices`
--
ALTER TABLE `pump_devices`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `pump_reservoir_history`
--
ALTER TABLE `pump_reservoir_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- =============================================================
-- pump_calibration_v2.sql
-- Adds physical-parameter columns needed for temperature,
-- density, tube-size, and measurement-based auto-recalibration.
-- Safe to run multiple times (uses  / IGNORE).
-- =============================================================

ALTER TABLE `pump_calibration`
    ADD COLUMN  `temperature_celsius`      DECIMAL(5,2)    NOT NULL DEFAULT 20.00
        COMMENT 'Ambient/solution temperature at time of calibration (°C). Used for viscosity correction.',
    ADD COLUMN  `density_g_per_ml`         DECIMAL(6,4)    NOT NULL DEFAULT 1.0200
        COMMENT 'Solution density (g/mL). Methadone 5 mg/mL ≈ 1.02 g/mL. Recorded for traceability.',
    ADD COLUMN  `tube_inner_diameter_mm`   DECIMAL(5,2)    DEFAULT NULL
        COMMENT 'Peristaltic tubing inner diameter in mm (e.g. L/S-14 = 1.60 mm). NULL = not recorded.',
    ADD COLUMN  `tube_type_code`           VARCHAR(20)     DEFAULT NULL
        COMMENT 'Masterflex L/S tubing size code, e.g. LS-14, LS-16, LS-25.',
    ADD COLUMN  `calibration_volume_ml`    DECIMAL(8,4)    NOT NULL DEFAULT 10.0000
        COMMENT 'Target volume used for this calibration run (always 10 mL for this system).',
    ADD COLUMN  `measured_volume_ml`       DECIMAL(8,4)    DEFAULT NULL
        COMMENT 'Actual volume measured in graduated cylinder after calibration dispense.',
    ADD COLUMN  `volume_correction`        DECIMAL(10,6)   DEFAULT NULL
        COMMENT 'Ratio: target_ml / measured_ml. Values > 1 mean pump under-delivered.',
    ADD COLUMN  `temp_correction`          DECIMAL(10,6)   DEFAULT NULL
        COMMENT 'Temperature-viscosity correction multiplier applied to calibration factor.',
    ADD COLUMN  `previous_factor`          DECIMAL(10,4)   DEFAULT NULL
        COMMENT 'Calibration factor that was active before this record was created.';

-- For older MySQL that doesn't support ADD COLUMN ,
-- comment out the block above and run each line individually after
-- checking if the column already exists.

-- Seed a sensible starting calibration if none exists
-- (Safe to skip if pump_calibration already has data)
INSERT IGNORE INTO `pump_calibration`
    (id, pump_id, calibration_factor, concentration_mg_per_ml, tubing_type,
     calibrated_by, notes, is_active,
     temperature_celsius, density_g_per_ml, calibration_volume_ml)
SELECT 1, 1, 400.00, 5.00, NULL, 'System', 'Default calibration — 400 units/mL', 1,
       20.00, 1.0200, 10.0000
WHERE NOT EXISTS (SELECT 1 FROM pump_calibration WHERE id = 1);

SELECT 'pump_calibration_v2 migration complete.' AS message;


CREATE TABLE `countries` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `iso2` char(2) NOT NULL DEFAULT '',
  `region` varchar(40) DEFAULT '',
  `sort_order` int(11) DEFAULT 100
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `countries`
--

INSERT INTO `countries` (`id`, `name`, `iso2`, `region`, `sort_order`) VALUES
(1, 'Kenya', 'KE', 'Africa', 1),
(2, 'Tanzania', 'TZ', 'Africa', 1),
(3, 'Uganda', 'UG', 'Africa', 1),
(4, 'Ethiopia', 'ET', 'Africa', 1),
(5, 'Rwanda', 'RW', 'Africa', 1),
(6, 'Burundi', 'BI', 'Africa', 1),
(7, 'South Sudan', 'SS', 'Africa', 1),
(8, 'Somalia', 'SO', 'Africa', 1),
(9, 'Nigeria', 'NG', 'Africa', 1),
(10, 'Ghana', 'GH', 'Africa', 1),
(11, 'South Africa', 'ZA', 'Africa', 1),
(12, 'Mozambique', 'MZ', 'Africa', 1),
(13, 'Angola', 'AO', 'Africa', 1),
(14, 'Zambia', 'ZM', 'Africa', 1),
(15, 'Zimbabwe', 'ZW', 'Africa', 1),
(16, 'Malawi', 'MW', 'Africa', 1),
(17, 'Botswana', 'BW', 'Africa', 1),
(18, 'Namibia', 'NA', 'Africa', 1),
(19, 'Lesotho', 'LS', 'Africa', 1),
(20, 'Eswatini', 'SZ', 'Africa', 1),
(21, 'Democratic Republic of the Congo', 'CD', 'Africa', 1),
(22, 'Republic of the Congo', 'CG', 'Africa', 1),
(23, 'Cameroon', 'CM', 'Africa', 1),
(24, 'Senegal', 'SN', 'Africa', 1),
(25, 'Ivory Coast', 'CI', 'Africa', 1),
(26, 'Mali', 'ML', 'Africa', 1),
(27, 'Burkina Faso', 'BF', 'Africa', 1),
(28, 'Niger', 'NE', 'Africa', 1),
(29, 'Chad', 'TD', 'Africa', 1),
(30, 'Sudan', 'SD', 'Africa', 1),
(31, 'Egypt', 'EG', 'Africa', 1),
(32, 'Libya', 'LY', 'Africa', 1),
(33, 'Tunisia', 'TN', 'Africa', 1),
(34, 'Algeria', 'DZ', 'Africa', 1),
(35, 'Morocco', 'MA', 'Africa', 1),
(36, 'Mauritania', 'MR', 'Africa', 1),
(37, 'Gambia', 'GM', 'Africa', 1),
(38, 'Guinea', 'GN', 'Africa', 1),
(39, 'Guinea-Bissau', 'GW', 'Africa', 1),
(40, 'Sierra Leone', 'SL', 'Africa', 1),
(41, 'Liberia', 'LR', 'Africa', 1),
(42, 'Togo', 'TG', 'Africa', 1),
(43, 'Benin', 'BJ', 'Africa', 1),
(44, 'Gabon', 'GA', 'Africa', 1),
(45, 'Equatorial Guinea', 'GQ', 'Africa', 1),
(46, 'Central African Republic', 'CF', 'Africa', 1),
(47, 'Eritrea', 'ER', 'Africa', 1),
(48, 'Djibouti', 'DJ', 'Africa', 1),
(49, 'Madagascar', 'MG', 'Africa', 1),
(50, 'Mauritius', 'MU', 'Africa', 1),
(51, 'Seychelles', 'SC', 'Africa', 1),
(52, 'Comoros', 'KM', 'Africa', 1),
(53, 'Cape Verde', 'CV', 'Africa', 1),
(54, 'São Tomé and Príncipe', 'ST', 'Africa', 1),
(55, 'Afghanistan', 'AF', 'World', 50),
(56, 'Albania', 'AL', 'World', 50),
(57, 'Argentina', 'AR', 'World', 50),
(58, 'Armenia', 'AM', 'World', 50),
(59, 'Australia', 'AU', 'World', 50),
(60, 'Austria', 'AT', 'World', 50),
(61, 'Azerbaijan', 'AZ', 'World', 50),
(62, 'Bahrain', 'BH', 'World', 50),
(63, 'Bangladesh', 'BD', 'World', 50),
(64, 'Belarus', 'BY', 'World', 50),
(65, 'Belgium', 'BE', 'World', 50),
(66, 'Bolivia', 'BO', 'World', 50),
(67, 'Bosnia and Herzegovina', 'BA', 'World', 50),
(68, 'Brazil', 'BR', 'World', 50),
(69, 'Bulgaria', 'BG', 'World', 50),
(70, 'Cambodia', 'KH', 'World', 50),
(71, 'Canada', 'CA', 'World', 50),
(72, 'Chile', 'CL', 'World', 50),
(73, 'China', 'CN', 'World', 50),
(74, 'Colombia', 'CO', 'World', 50),
(75, 'Costa Rica', 'CR', 'World', 50),
(76, 'Croatia', 'HR', 'World', 50),
(77, 'Cuba', 'CU', 'World', 50),
(78, 'Cyprus', 'CY', 'World', 50),
(79, 'Czech Republic', 'CZ', 'World', 50),
(80, 'Denmark', 'DK', 'World', 50),
(81, 'Dominican Republic', 'DO', 'World', 50),
(82, 'Ecuador', 'EC', 'World', 50),
(83, 'El Salvador', 'SV', 'World', 50),
(84, 'Estonia', 'EE', 'World', 50),
(85, 'Finland', 'FI', 'World', 50),
(86, 'France', 'FR', 'World', 50),
(87, 'Georgia', 'GE', 'World', 50),
(88, 'Germany', 'DE', 'World', 50),
(89, 'Greece', 'GR', 'World', 50),
(90, 'Guatemala', 'GT', 'World', 50),
(91, 'Haiti', 'HT', 'World', 50),
(92, 'Honduras', 'HN', 'World', 50),
(93, 'Hungary', 'HU', 'World', 50),
(94, 'Iceland', 'IS', 'World', 50),
(95, 'India', 'IN', 'World', 50),
(96, 'Indonesia', 'ID', 'World', 50),
(97, 'Iran', 'IR', 'World', 50),
(98, 'Iraq', 'IQ', 'World', 50),
(99, 'Ireland', 'IE', 'World', 50),
(100, 'Israel', 'IL', 'World', 50),
(101, 'Italy', 'IT', 'World', 50),
(102, 'Jamaica', 'JM', 'World', 50),
(103, 'Japan', 'JP', 'World', 50),
(104, 'Jordan', 'JO', 'World', 50),
(105, 'Kazakhstan', 'KZ', 'World', 50),
(106, 'Kuwait', 'KW', 'World', 50),
(107, 'Kyrgyzstan', 'KG', 'World', 50),
(108, 'Laos', 'LA', 'World', 50),
(109, 'Latvia', 'LV', 'World', 50),
(110, 'Lebanon', 'LB', 'World', 50),
(111, 'Lithuania', 'LT', 'World', 50),
(112, 'Luxembourg', 'LU', 'World', 50),
(113, 'Malaysia', 'MY', 'World', 50),
(114, 'Maldives', 'MV', 'World', 50),
(115, 'Malta', 'MT', 'World', 50),
(116, 'Mexico', 'MX', 'World', 50),
(117, 'Moldova', 'MD', 'World', 50),
(118, 'Mongolia', 'MN', 'World', 50),
(119, 'Montenegro', 'ME', 'World', 50),
(120, 'Myanmar', 'MM', 'World', 50),
(121, 'Nepal', 'NP', 'World', 50),
(122, 'Netherlands', 'NL', 'World', 50),
(123, 'New Zealand', 'NZ', 'World', 50),
(124, 'Nicaragua', 'NI', 'World', 50),
(125, 'North Macedonia', 'MK', 'World', 50),
(126, 'Norway', 'NO', 'World', 50),
(127, 'Oman', 'OM', 'World', 50),
(128, 'Pakistan', 'PK', 'World', 50),
(129, 'Panama', 'PA', 'World', 50),
(130, 'Papua New Guinea', 'PG', 'World', 50),
(131, 'Paraguay', 'PY', 'World', 50),
(132, 'Peru', 'PE', 'World', 50),
(133, 'Philippines', 'PH', 'World', 50),
(134, 'Poland', 'PL', 'World', 50),
(135, 'Portugal', 'PT', 'World', 50),
(136, 'Qatar', 'QA', 'World', 50),
(137, 'Romania', 'RO', 'World', 50),
(138, 'Russia', 'RU', 'World', 50),
(139, 'Saudi Arabia', 'SA', 'World', 50),
(140, 'Serbia', 'RS', 'World', 50),
(141, 'Singapore', 'SG', 'World', 50),
(142, 'Slovakia', 'SK', 'World', 50),
(143, 'Slovenia', 'SI', 'World', 50),
(144, 'South Korea', 'KR', 'World', 50),
(145, 'Spain', 'ES', 'World', 50),
(146, 'Sri Lanka', 'LK', 'World', 50),
(147, 'Sweden', 'SE', 'World', 50),
(148, 'Switzerland', 'CH', 'World', 50),
(149, 'Syria', 'SY', 'World', 50),
(150, 'Taiwan', 'TW', 'World', 50),
(151, 'Tajikistan', 'TJ', 'World', 50),
(152, 'Thailand', 'TH', 'World', 50),
(153, 'Timor-Leste', 'TL', 'World', 50),
(154, 'Turkey', 'TR', 'World', 50),
(155, 'Turkmenistan', 'TM', 'World', 50),
(156, 'Ukraine', 'UA', 'World', 50),
(157, 'United Arab Emirates', 'AE', 'World', 50),
(158, 'United Kingdom', 'GB', 'World', 50),
(159, 'United States', 'US', 'World', 50),
(160, 'Uruguay', 'UY', 'World', 50),
(161, 'Uzbekistan', 'UZ', 'World', 50),
(162, 'Venezuela', 'VE', 'World', 50),
(163, 'Vietnam', 'VN', 'World', 50),
(164, 'Yemen', 'YE', 'World', 50);

-- --------------------------------------------------------

--
-- Table structure for table `demo_requests`
--

CREATE TABLE `demo_requests` (
  `id` int(11) NOT NULL,
  `first_name` varchar(80) NOT NULL,
  `last_name` varchar(80) NOT NULL,
  `clinic_name` varchar(160) NOT NULL,
  `email` varchar(120) NOT NULL,
  `phone` varchar(40) NOT NULL,
  `country` varchar(80) NOT NULL,
  `plan` varchar(40) DEFAULT 'professional',
  `token` varchar(64) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `notified_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_logs`
--

CREATE TABLE `login_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `userrole` varchar(100) DEFAULT NULL,
  `is_demo` tinyint(1) DEFAULT 0,
  `country` varchar(80) DEFAULT '',
  `ip_address` varchar(64) DEFAULT '',
  `user_agent` varchar(255) DEFAULT '',
  `success` tinyint(1) DEFAULT 1,
  `login_time` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `countries`
--
ALTER TABLE `countries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `demo_requests`
--
ALTER TABLE `demo_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_country` (`country`);

--
-- Indexes for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_time` (`login_time`),
  ADD KEY `idx_demo` (`is_demo`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=165;

--
-- AUTO_INCREMENT for table `demo_requests`
--
ALTER TABLE `demo_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

alter table dose_schedules add column dosing_interval_days tinyint;

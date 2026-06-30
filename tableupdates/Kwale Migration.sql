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

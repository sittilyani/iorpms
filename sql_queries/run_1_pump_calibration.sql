-- ============================================================
-- pump_calibration table (full schema)
-- Run this FIRST. Safe to run on an existing install:
--   - CREATE TABLE IF NOT EXISTS skips if already present
--   - ALTER TABLE ADD COLUMN IF NOT EXISTS adds missing cols only
-- ============================================================

CREATE TABLE IF NOT EXISTS `pump_calibration` (
    `id`                      INT            NOT NULL AUTO_INCREMENT,
    `pump_id`                 INT            NOT NULL,
    `calibration_factor`      DECIMAL(10,4)  NOT NULL,
    `concentration_mg_per_ml` DECIMAL(5,2)   NOT NULL DEFAULT 5.00,
    `tubing_type`             VARCHAR(50)    DEFAULT NULL,
    `tube_type_code`          VARCHAR(20)    DEFAULT NULL,
    `tube_inner_diameter_mm`  DECIMAL(6,3)   DEFAULT NULL,
    `calibrated_by`           VARCHAR(100)   DEFAULT NULL,
    `calibrated_at`           TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    `notes`                   TEXT           DEFAULT NULL,
    `is_active`               TINYINT(1)     DEFAULT 1,
    `temperature_celsius`     DECIMAL(5,2)   DEFAULT 20.00,
    `density_g_per_ml`        DECIMAL(6,4)   DEFAULT 1.0200,
    `calibration_volume_ml`   DECIMAL(8,3)   DEFAULT 10.000,
    `measured_volume_ml`      DECIMAL(8,3)   DEFAULT NULL,
    `volume_correction`       DECIMAL(10,6)  DEFAULT NULL,
    `temp_correction`         DECIMAL(10,6)  DEFAULT NULL,
    `previous_factor`         DECIMAL(10,4)  DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_pump_active` (`pump_id`, `is_active`),
    CONSTRAINT `pump_calibration_ibfk_1`
        FOREIGN KEY (`pump_id`) REFERENCES `pump_devices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add any missing columns to an existing table (safe to re-run)
ALTER TABLE `pump_calibration`
    ADD COLUMN IF NOT EXISTS `tube_type_code`          VARCHAR(20)   DEFAULT NULL AFTER `tubing_type`,
    ADD COLUMN IF NOT EXISTS `tube_inner_diameter_mm`  DECIMAL(6,3)  DEFAULT NULL AFTER `tube_type_code`,
    ADD COLUMN IF NOT EXISTS `temperature_celsius`     DECIMAL(5,2)  DEFAULT 20.00 AFTER `is_active`,
    ADD COLUMN IF NOT EXISTS `density_g_per_ml`        DECIMAL(6,4)  DEFAULT 1.0200 AFTER `temperature_celsius`,
    ADD COLUMN IF NOT EXISTS `calibration_volume_ml`   DECIMAL(8,3)  DEFAULT 10.000 AFTER `density_g_per_ml`,
    ADD COLUMN IF NOT EXISTS `measured_volume_ml`      DECIMAL(8,3)  DEFAULT NULL AFTER `calibration_volume_ml`,
    ADD COLUMN IF NOT EXISTS `volume_correction`       DECIMAL(10,6) DEFAULT NULL AFTER `measured_volume_ml`,
    ADD COLUMN IF NOT EXISTS `temp_correction`         DECIMAL(10,6) DEFAULT NULL AFTER `volume_correction`,
    ADD COLUMN IF NOT EXISTS `previous_factor`         DECIMAL(10,4) DEFAULT NULL AFTER `temp_correction`;

-- Seed a default calibration row if none exists
INSERT INTO `pump_calibration`
    (pump_id, calibration_factor, concentration_mg_per_ml, calibrated_by, notes, is_active)
SELECT 1, 500.00, 5.00, 'System', 'Default calibration', 1
WHERE NOT EXISTS (SELECT 1 FROM `pump_calibration` WHERE pump_id = 1);

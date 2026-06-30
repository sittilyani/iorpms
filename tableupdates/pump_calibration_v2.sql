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

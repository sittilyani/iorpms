-- ============================================================================
-- add_is_dot_column.sql
-- ============================================================================
-- Adds the `is_dot` flag to `prescription_drugs`, used by the new DOT
-- (Directly Observed Therapy) checkbox in clinician/new_prescriptions.php.
-- Drugs flagged is_dot = 1 automatically appear in the pharmacy dispensing
-- co-medications panel (pharmacy/dispensingData.php and
-- pharmacy/dispensingData_pump.php), alongside ARV / Anti-TB / TPT, so they
-- get co-dispensed with the client's methadone/buprenorphine dose.
--
-- Run this ONCE against each existing EasyFlow/IORPMS database (via
-- phpMyAdmin's SQL tab, or `mysql -u root -p <database> < add_is_dot_column.sql`).
--
-- NOTE: standard MySQL does not support "ADD COLUMN IF NOT EXISTS" (that's a
-- MariaDB-only extension), so only run this once per database. If you run it
-- twice by mistake, MySQL will error with "Duplicate column name 'is_dot'" —
-- that's harmless and just means the column is already there.
-- ============================================================================

ALTER TABLE `prescription_drugs`
    ADD COLUMN `is_dot` TINYINT(1) NOT NULL DEFAULT 0
    COMMENT 'Directly Observed Therapy: always co-dispensed with the MAT drug'
    AFTER `prescr_status`;

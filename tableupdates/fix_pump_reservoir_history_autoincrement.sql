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

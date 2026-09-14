-- Performance fix: pharmacy table had 177k+ rows with no index on mat_id,
-- pump_id+dispDate, or dispDate alone, forcing full table scans on every
-- per-patient dispensing lookup (dispensingData.php, dispensingData_pump.php,
-- dose_schedule_helpers.php) and on the pump reservoir/summary queries
-- (dispensing-pump.php, pump_reservoir.php, prisons_dispense_pump.php,
-- dispensing-pump-reverse.php). Already applied to the local dev DB on
-- 2026-09-14 — run this against the production database (sitti.site) too.
ALTER TABLE pharmacy ADD INDEX idx_pharmacy_mat_dispdate (mat_id, dispDate);
ALTER TABLE pharmacy ADD INDEX idx_pharmacy_pump_dispdate (pump_id, dispDate);
ALTER TABLE pharmacy ADD INDEX idx_pharmacy_dispdate (dispDate);

-- IMPORTANT: MySQL's optimizer kept choosing the old, less-selective
-- pump_id-only index over the new composite ones until stats were
-- refreshed - without this, the new indexes above silently do nothing.
ANALYZE TABLE pharmacy;

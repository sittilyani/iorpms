-- ============================================================
-- dose_schedules table
-- Stores planned dose periods for active patients.
-- patients.dosage is automatically synced by a MySQL EVENT
-- (dose_sync_event) that fires every minute.
-- ============================================================

CREATE TABLE IF NOT EXISTS `dose_schedules` (
  `id`            INT           NOT NULL AUTO_INCREMENT,
  `mat_id`        VARCHAR(50)   NOT NULL,
  `drugname`      VARCHAR(225)  NOT NULL,
  `dose_mg`       DECIMAL(10,2) NOT NULL,
  `start_date`    DATE          NOT NULL,
  `end_date`      DATE          DEFAULT NULL COMMENT 'NULL = open-ended / no end date',
  `skip_dates`    TEXT          DEFAULT NULL COMMENT 'JSON array of YYYY-MM-DD dates to skip (Buprenorphine only)',
  `comments`      TEXT          NOT NULL    COMMENT 'Mandatory clinical comment for this dose period',
  `created_by`    VARCHAR(100)  NOT NULL,
  `created_at`    DATETIME      DEFAULT CURRENT_TIMESTAMP,
  `status`        ENUM('active','superseded','cancelled') DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `idx_mat_date` (`mat_id`, `start_date`, `end_date`),
  KEY `idx_active`   (`mat_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================================
-- MySQL EVENT: sync patients.dosage every minute
-- This fires for all patients whose current_status = 'Active'
-- and where today falls within a dose_schedules window.
-- ============================================================

SET GLOBAL event_scheduler = ON;

DROP EVENT IF EXISTS `dose_sync_event`;

DELIMITER $$
CREATE EVENT `dose_sync_event`
  ON SCHEDULE EVERY 1 MINUTE
  STARTS CURRENT_TIMESTAMP
  DO
  BEGIN
    -- Update patients.dosage to the dose_mg from the active dose_schedule
    -- whose window covers today.
    UPDATE patients p
    JOIN (
        SELECT mat_id, dose_mg, drugname
        FROM dose_schedules
        WHERE status = 'active'
          AND start_date <= CURDATE()
          AND (end_date IS NULL OR end_date >= CURDATE())
        ORDER BY start_date DESC
    ) ds ON ds.mat_id = p.mat_id
    SET p.dosage   = ds.dose_mg,
        p.drugname = ds.drugname
    WHERE p.current_status = 'Active';
  END$$
DELIMITER ;

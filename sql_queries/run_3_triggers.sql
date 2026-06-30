-- ============================================================
-- Triggers & daily patient-status event
-- Run this THIRD
-- Safe to re-run: uses DROP IF EXISTS before each CREATE
-- ============================================================

-- Enable event scheduler
SET GLOBAL event_scheduler = ON;

-- ── Daily patient status update event ────────────────────────
DROP EVENT IF EXISTS `update_patient_status_event`;

DELIMITER $$
CREATE EVENT `update_patient_status_event`
  ON SCHEDULE EVERY 1 DAY
  STARTS CURRENT_TIMESTAMP
  DO
  BEGIN
    UPDATE patients
    SET current_status = CASE
        WHEN DATEDIFF(CURDATE(), (
                SELECT MAX(visitDate) FROM pharmacy
                WHERE pharmacy.mat_id = patients.mat_id
             )) BETWEEN 6 AND 29 THEN 'Defaulted'
        WHEN DATEDIFF(CURDATE(), (
                SELECT MAX(visitDate) FROM pharmacy
                WHERE pharmacy.mat_id = patients.mat_id
             )) >= 30 THEN 'ltfu'
        WHEN current_status IN ('Weaned','dead','Transfer Out','Stopped') THEN current_status
        ELSE 'Active'
    END;
  END$$
DELIMITER ;

-- ── Trigger: update patient status after each dispense ───────
DROP TRIGGER IF EXISTS `update_patient_status`;

DELIMITER $$
CREATE TRIGGER `update_patient_status`
  AFTER INSERT ON pharmacy
  FOR EACH ROW
BEGIN
    UPDATE patients
    SET current_status = CASE
        WHEN NEW.visitDate IS NOT NULL
             AND DATEDIFF(CURDATE(), NEW.visitDate) BETWEEN 6 AND 29 THEN 'Defaulted'
        WHEN NEW.visitDate IS NOT NULL
             AND DATEDIFF(CURDATE(), NEW.visitDate) >= 30 THEN 'ltfu'
        WHEN current_status IN ('Weaned','dead','Transfer Out','Stopped') THEN current_status
        ELSE 'Active'
    END
    WHERE mat_id = NEW.mat_id;
END$$
DELIMITER ;

-- ── Trigger: backup pharmacy row before delete ───────────────
DROP TRIGGER IF EXISTS `backup_on_delete`;

DELIMITER $$
CREATE TRIGGER `backup_on_delete`
  BEFORE DELETE ON pharmacy
  FOR EACH ROW
BEGIN
    INSERT INTO pharmacy_deleted (
        disp_id, visitDate, mat_id, mat_number, clientName, nickName,
        age, sex, p_address, cso, drugname, dosage, current_status,
        pharm_officer_name, deleted_on
    ) VALUES (
        OLD.disp_id, OLD.visitDate, OLD.mat_id, OLD.mat_number,
        OLD.clientName, OLD.nickName, OLD.age, OLD.sex, OLD.p_address,
        OLD.cso, OLD.drugname, OLD.dosage, OLD.current_status,
        OLD.pharm_officer_name, NOW()
    );
END$$
DELIMITER ;

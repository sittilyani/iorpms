<

DELIMITER //
CREATE TRIGGER backup_before_delete_patients
BEFORE DELETE
ON patients
FOR EACH ROW
BEGIN
    INSERT INTO patients2
    SELECT * FROM patients WHERE mat_id = OLD.mat_id;
END;
//
DELIMITER ;   >

<
DELIMITER //
CREATE TRIGGER backup_before_update_patients
BEFORE UPDATE ON patients
FOR EACH ROW
BEGIN
    INSERT INTO patients2
    SELECT * FROM patients WHERE p_id = OLD.p_id;
END;
//
DELIMITER ;   >



--To generate serial numbers format

DELIMITER //

CREATE TRIGGER before_referral_insert
BEFORE INSERT ON referral
FOR EACH ROW
BEGIN
    DECLARE next_id INT;
    DECLARE formatted_id VARCHAR(12);

    -- Get the next numeric ID from existing rows
    SELECT COALESCE(MAX(CAST(SUBSTRING(referral_id, 4) AS UNSIGNED)), 0) + 1 INTO next_id
    FROM referral;

    -- Format the new ID as "REF00000001"
    SET formatted_id = CONCAT('REF', LPAD(next_id, 8, '0'));

    -- Set the new referral_id
    SET NEW.referral_id = formatted_id;
END;

//
DELIMITER ;

CREATE TABLE referral (
    referral_id VARCHAR(12) NOT NULL PRIMARY KEY,
    mat_id VARCHAR(50),
    clientName VARCHAR(100),
    age INT,
    sex VARCHAR(10),
    referral_notes TEXT,
    refer_to VARCHAR(100),
    refer_from VARCHAR(100),
    referral_name VARCHAR(100),
    referral_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


For updating patient ages

DELIMITER //

CREATE EVENT update_patient_ages
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
BEGIN
    UPDATE patients
    SET age = TIMESTAMPDIFF(YEAR, dob, CURDATE())
    WHERE dob IS NOT NULL AND dob <= CURDATE();
END//

DELIMITER ;




CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(255) NOT NULL,
    unique_id VARCHAR(100) NOT NULL UNIQUE,
    sex ENUM('male', 'female', 'other'),
    other_sex VARCHAR(100),
    visit_type VARCHAR(20),
    date DATE,
    intake_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE social_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT,
    marital_status VARCHAR(50),
    marital_other VARCHAR(100),
    living_arrangements ENUM('stable', 'no_stable'),
    living_arrangements_detail TEXT,
    living_other VARCHAR(100),
    previous_treatment ENUM('yes', 'no'),
    treatment_specify TEXT,
    FOREIGN KEY (client_id) REFERENCES clients(id)
);

CREATE TABLE sexual_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT,
    sexually_active ENUM('yes', 'no'),
    sexual_partners ENUM('single', 'multiple'),
    unprotected_sex ENUM('yes', 'no'),
    FOREIGN KEY (client_id) REFERENCES clients(id)
);

CREATE TABLE education_occupation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT,
    education_level VARCHAR(50),
    education_other VARCHAR(100),
    has_income ENUM('yes', 'no'),
    income_specify TEXT,
    employment_status VARCHAR(100),
    missed_work ENUM('yes', 'no', 'na'),
    fired_work ENUM('yes', 'no', 'na'),
    FOREIGN KEY (client_id) REFERENCES clients(id)
);

CREATE TABLE family_relationships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT,
    family_relationship ENUM('poor', 'fair', 'good'),
    has_dependents ENUM('yes', 'no'),
    dependents TEXT,
    dependent_other VARCHAR(100),
    has_support ENUM('yes', 'no'),
    FOREIGN KEY (client_id) REFERENCES clients(id)
);


ALTER TABLE patients
ADD COLUMN psycho_social_tca DATE DEFAULT NULL,
ADD COLUMN psychiatric_tca DATE DEFAULT NULL,
ADD COLUMN nursing_tca DATE DEFAULT NULL,
ADD COLUMN nutrition_tca DATE DEFAULT NULL,
ADD COLUMN laboratory_tca DATE DEFAULT NULL,
ADD COLUMN records_tca DATE DEFAULT NULL,
ADD COLUMN peer_tca DATE DEFAULT NULL,
ADD COLUMN admin_tca DATE DEFAULT NULL;

-- update the columns
UPDATE patients
SET psycho_social_tca = next_appointment,
    psychiatric_tca = next_appointment,
    nursing_tca = next_appointment,
    nutrition_tca = next_appointment,
    laboratory_tca = next_appointment,
    records_tca = next_appointment,
    peer_tca = next_appointment,
    admin_tca = next_appointment
WHERE next_appointment IS NOT NULL;
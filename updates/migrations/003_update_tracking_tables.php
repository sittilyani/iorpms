<?php
// Update tracking (lives on whichever server the administrator uses to send
// links and collect "update completed" notices, normally the online server).
mig_exec($conn, "CREATE TABLE IF NOT EXISTS update_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    facility_id INT NULL,
    facility_name VARCHAR(255) NOT NULL,
    mflcode VARCHAR(100) NOT NULL,
    contact_name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    last_link_sent DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_facility_email (mflcode, email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mig_exec($conn, "CREATE TABLE IF NOT EXISTS update_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mflcode VARCHAR(100) NOT NULL,
    facility_name VARCHAR(255) NOT NULL,
    commit_sha VARCHAR(40) NOT NULL,
    status VARCHAR(20) NOT NULL,
    files_updated INT NOT NULL DEFAULT 0,
    files_added INT NOT NULL DEFAULT 0,
    migrations_applied INT NOT NULL DEFAULT 0,
    message VARCHAR(500) NULL,
    details MEDIUMTEXT NULL,
    applied_at DATETIME NOT NULL,
    received_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_mfl (mflcode, applied_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

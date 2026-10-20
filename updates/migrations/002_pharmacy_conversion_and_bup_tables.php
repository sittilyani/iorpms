<?php
// pharmacy: conversion of the dispensed dose into what leaves the shelf.
//   Methadone      -> conversion = mL      (dosage mg / 5)
//   Buprenorphine  -> conversion = tablets (dosage mg / tablet strength)
//   everything else-> NULL
// Calculated by the application BEFORE insert (not a generated column).
mig_add_column($conn, 'pharmacy', 'conversion',         'DECIMAL(10,2) NULL DEFAULT NULL');
mig_add_column($conn, 'pharmacy', 'conversion_unit',    'VARCHAR(20) NULL DEFAULT NULL');
mig_add_column($conn, 'pharmacy', 'formulation_detail', 'VARCHAR(100) NULL DEFAULT NULL');
mig_add_column($conn, 'pharmacy', 'dispense_group',     'VARCHAR(40) NULL DEFAULT NULL');

// The tablet strengths chosen for a client persist until the dispenser changes them.
mig_exec($conn, "CREATE TABLE IF NOT EXISTS patient_bup_formulation (
    mat_id VARCHAR(50) NOT NULL PRIMARY KEY,
    components TEXT NOT NULL COMMENT 'JSON {drugname: tablets}',
    dose_mg DECIMAL(10,2) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Bin card ledger: one line per strength per dispensing.
mig_exec($conn, "CREATE TABLE IF NOT EXISTS bin_card_entries (
    entry_id INT AUTO_INCREMENT PRIMARY KEY,
    trans_date DATETIME NOT NULL,
    drugID INT NULL,
    drugname VARCHAR(100) NOT NULL,
    mat_id VARCHAR(50) NULL,
    mat_number VARCHAR(50) NULL,
    batch_number VARCHAR(50) NULL,
    received_from VARCHAR(100) NOT NULL DEFAULT '',
    opening_balance DOUBLE NULL,
    qty_in DOUBLE NULL,
    qty_out_tablets DOUBLE NULL,
    qty_out_mg DOUBLE NULL,
    stock_balance DOUBLE NULL,
    disp_id INT NULL,
    issued_by VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_bin_drug_date (drugname, trans_date),
    KEY idx_bin_disp (disp_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

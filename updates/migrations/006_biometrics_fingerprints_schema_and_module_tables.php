<?php
// 1. `fingerprints` had an old shape (print_id, no template/scanner columns) that the capture/search pages do not match
//    (they use id, mat_number, nickName, dob, template_data, quality_score, fingerprint_type, scanner_type, fingerprint_path).
// 2. New tables for the Reports dashboard (report_submissions) and the Pharmacovigilance dashboard (pv_reports).
// Safe to re-run.
$has = function (string $table, string $col) use ($conn) { $r = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$col'"); return $r && $r->num_rows > 0; };
$t = $conn->query("SHOW TABLES LIKE 'fingerprints'");
if ($t && $t->num_rows) {
    if ($has('fingerprints', 'print_id') && !$has('fingerprints', 'id')) { $conn->query("ALTER TABLE fingerprints CHANGE print_id id INT NOT NULL AUTO_INCREMENT"); }
    $add = ['mat_number' => 'VARCHAR(50) NULL', 'nickName' => 'VARCHAR(100) NULL', 'dob' => 'DATE NULL', 'template_data' => 'LONGTEXT NULL', 'quality_score' => 'INT NULL',
            'fingerprint_type' => 'VARCHAR(30) NULL', 'scanner_type' => 'VARCHAR(30) NULL', 'fingerprint_path' => 'VARCHAR(255) NULL'];
    foreach ($add as $c => $ddl) { if (!$has('fingerprints', $c)) { $conn->query("ALTER TABLE fingerprints ADD COLUMN `$c` $ddl"); } }
}
$conn->query("CREATE TABLE IF NOT EXISTS report_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_code VARCHAR(40) NOT NULL,
    report_name VARCHAR(150) NOT NULL,
    period VARCHAR(7) NOT NULL,
    due_date DATE NOT NULL,
    submitted_at DATETIME DEFAULT NULL,
    submitted_by VARCHAR(150) DEFAULT NULL,
    channel VARCHAR(40) DEFAULT NULL,
    notes VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_report_period (report_code, period)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
$conn->query("CREATE TABLE IF NOT EXISTS pv_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_ref VARCHAR(40) NOT NULL,
    report_type VARCHAR(40) NOT NULL,
    report_date DATE NOT NULL,
    mflcode VARCHAR(50) DEFAULT NULL,
    facility_name VARCHAR(255) DEFAULT NULL,
    county VARCHAR(100) DEFAULT NULL,
    sub_county VARCHAR(100) DEFAULT NULL,
    reporter_cadre VARCHAR(60) DEFAULT NULL,
    patient_age_group VARCHAR(30) DEFAULT NULL,
    patient_sex VARCHAR(10) DEFAULT NULL,
    suspected_product VARCHAR(255) DEFAULT NULL,
    reaction VARCHAR(255) DEFAULT NULL,
    seriousness VARCHAR(30) DEFAULT NULL,
    outcome VARCHAR(40) DEFAULT NULL,
    causality VARCHAR(30) DEFAULT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'Submitted',
    submitted_at DATETIME DEFAULT NULL,
    acknowledged_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (county), INDEX (sub_county), INDEX (report_date), INDEX (report_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
// fingerprint_audit.id had no AUTO_INCREMENT, so audit rows could not be written
$t = $conn->query("SHOW TABLES LIKE 'fingerprint_audit'");
if ($t && $t->num_rows) {
    $c = $conn->query("SHOW COLUMNS FROM fingerprint_audit LIKE 'id'")->fetch_assoc();
    if ($c && stripos((string)$c['Extra'], 'auto_increment') === false) {
        $pk = $conn->query("SHOW KEYS FROM fingerprint_audit WHERE Key_name='PRIMARY'");
        if ($pk && $pk->num_rows === 0) { $conn->query("ALTER TABLE fingerprint_audit ADD PRIMARY KEY (id)"); }
        $conn->query("ALTER TABLE fingerprint_audit MODIFY id INT NOT NULL AUTO_INCREMENT");
    }
}

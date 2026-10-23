<?php
/** Nursing module schema + role setup (idempotent, safe to include on every nursing page). */
function nursing_ensure_schema(mysqli $conn): void
{
    $conn->query("CREATE TABLE IF NOT EXISTS nursing_visits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        mat_id VARCHAR(50) NOT NULL,
        client_name VARCHAR(255) NOT NULL,
        visit_date DATE NOT NULL,
        visit_time TIME NOT NULL,
        service_id INT DEFAULT NULL,
        service_name VARCHAR(255) NOT NULL,
        prescription_id VARCHAR(255) DEFAULT NULL,
        prescribed_by VARCHAR(255) DEFAULT NULL,
        drug_given VARCHAR(255) DEFAULT NULL,
        dose VARCHAR(100) DEFAULT NULL,
        route VARCHAR(30) DEFAULT NULL,
        site VARCHAR(60) DEFAULT NULL,
        bp VARCHAR(20) DEFAULT NULL,
        pulse INT DEFAULT NULL,
        temperature DECIMAL(4,1) DEFAULT NULL,
        resp_rate INT DEFAULT NULL,
        spo2 INT DEFAULT NULL,
        weight DECIMAL(5,1) DEFAULT NULL,
        findings TEXT,
        outcome VARCHAR(100) DEFAULT NULL,
        next_review DATE DEFAULT NULL,
        nurse_name VARCHAR(255) NOT NULL,
        nurse_signature VARCHAR(80) DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX (mat_id), INDEX (visit_date), INDEX (service_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    // The role used by dashboard.php's permission map is the lowercase key 'nursing'
    $conn->query("UPDATE userroles SET role='Nursing', descr='Provides nursing services and injections' WHERE role='Nurse'");
    $conn->query("INSERT INTO userroles (role, descr) SELECT 'Nursing', 'Provides nursing services and injections' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM userroles WHERE role='Nursing')");
    $conn->query("UPDATE tblusers SET userrole='Nursing' WHERE userrole='Nurse'");
    foreach (['Vital signs monitoring', 'Health education', 'IM / IV medication', 'Specimen collection', 'Pregnancy test', 'Other nursing care'] as $s) {
        $e = $conn->real_escape_string($s);
        $conn->query("INSERT INTO nursing_services (nurising_service_name) SELECT '$e' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM nursing_services WHERE nurising_service_name='$e')");
    }
}

/** Columns that let the nurse (Part A / triage) and clinician (Part B) share one encounter. */
function encounter_ensure_schema(mysqli $conn): void
{
    $add = function (string $table, string $col, string $ddl) use ($conn) {
        $r = $conn->query("SHOW COLUMNS FROM `$table` LIKE '" . $conn->real_escape_string($col) . "'");
        if ($r && $r->num_rows === 0) { $conn->query("ALTER TABLE `$table` ADD COLUMN $col $ddl"); }
    };
    $add('triage_services', 'recorded_by_name', 'VARCHAR(255) DEFAULT NULL');
    $add('triage_services', 'recorded_by_role', 'VARCHAR(50) DEFAULT NULL');
    $add('clinical_encounters', 'triage_id', 'INT DEFAULT NULL');
}

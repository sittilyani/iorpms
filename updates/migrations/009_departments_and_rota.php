<?php
// Departments for staff (users) and the weekly rota. Safe to re-run.
$conn->query("CREATE TABLE IF NOT EXISTS departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    department_name VARCHAR(255) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
foreach (['Pharmacy', 'Clinical', 'Nursing', 'Laboratory', 'Psycho-social', 'Psychiatry', 'Health records'] as $d) {
    $e = $conn->real_escape_string($d);
    $conn->query("INSERT IGNORE INTO departments (department_name) VALUES ('$e')");
}
$col = function (string $table, string $c, string $ddl) use ($conn) {
    $r = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$c'");
    if ($r && $r->num_rows === 0) { $conn->query("ALTER TABLE `$table` ADD COLUMN $c $ddl"); return true; }
    return false;
};
// pharmacy works the whole week, every other department Monday to Friday
if ($col('departments', 'works_weekends', 'TINYINT(1) NOT NULL DEFAULT 0')) { $conn->query("UPDATE departments SET works_weekends = 1 WHERE department_name = 'Pharmacy'"); }
// a department for each user; existing users are placed from their role
if ($col('tblusers', 'department_id', 'INT NULL')) {
    $map = ['Pharmacist' => 'Pharmacy', 'Clinician' => 'Clinical', 'Nursing' => 'Nursing', 'Laboratory Scientist' => 'Laboratory', 'Psychologist' => 'Psycho-social', 'Peer Educator' => 'Psycho-social',
            'Psychiatrist' => 'Psychiatry', 'HRIO' => 'Health records', 'Data Manager' => 'Health records', 'Receptionist' => 'Health records'];
    foreach ($map as $role => $dept) {
        $r = $conn->real_escape_string($role); $d = $conn->real_escape_string($dept);
        $conn->query("UPDATE tblusers u JOIN departments d ON d.department_name = '$d' SET u.department_id = d.id WHERE u.userrole = '$r' AND u.department_id IS NULL");
    }
}
$conn->query("CREATE TABLE IF NOT EXISTS rota_weeks (
    week_start DATE PRIMARY KEY,
    status ENUM('draft','final') NOT NULL DEFAULT 'draft',
    weekend_cover TINYINT NOT NULL DEFAULT 1,
    generated_by VARCHAR(150) DEFAULT NULL,
    generated_at DATETIME DEFAULT NULL,
    finalized_by VARCHAR(150) DEFAULT NULL,
    finalized_at DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
$conn->query("CREATE TABLE IF NOT EXISTS rota_shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    week_start DATE NOT NULL,
    user_id INT NOT NULL,
    department_id INT NOT NULL,
    shift_date DATE NOT NULL,
    start_time TIME NOT NULL DEFAULT '06:00:00',
    end_time TIME NOT NULL DEFAULT '12:00:00',
    UNIQUE KEY uq_user_day (user_id, shift_date),
    INDEX (week_start), INDEX (department_id), INDEX (shift_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

<?php
// Several clinical tables were created without an AUTO_INCREMENT primary key, so every INSERT from the
// forms failed with "Field 'id' doesn't have a default value". Give each one a proper auto-increment key.
// Also makes sure the nursing / transfer / pump-compliance tables exist. Safe to re-run.
$fixes = [
    'psychosocial_intake_form_1a' => 'id',
    'phq9_assessments'            => 'assessment_id',
    'gad7_assessments'            => 'assessment_id',
    'psychodar'                   => 'dar_id',
    'psychiatric_encounters'      => 'id',
    'initial_psychiatric_form'    => 'psy_id',
    'followup_psychiatric_form'   => 'visit_id',
    'toxicology_results'          => 'tox_id',
    'viral_load'                  => 'vl_id',
    'laboratory'                  => 'lab_id',
    'medical_history_new'         => 'id',
    'referral'                    => 'referral_id',
];
foreach ($fixes as $table => $col) {
    $t = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($table) . "'");
    if (!$t || !$t->num_rows) continue;
    $c = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
    $row = $c ? $c->fetch_assoc() : null;
    if (!$row || stripos((string)$row['Extra'], 'auto_increment') !== false) continue;
    $pk = $conn->query("SHOW KEYS FROM `$table` WHERE Key_name='PRIMARY'");
    if ($pk && $pk->num_rows === 0) { $conn->query("ALTER TABLE `$table` ADD PRIMARY KEY (`$col`)"); }
    $conn->query("ALTER TABLE `$table` MODIFY `$col` INT NOT NULL AUTO_INCREMENT");
}
// Child tables that carry mat_id and join to patients: keep the same collation as patients
foreach (['nursing_visits', 'transfer_out_requests', 'pump_prime_log'] as $table) {
    $t = $conn->query("SHOW TABLES LIKE '$table'");
    if ($t && $t->num_rows) { $conn->query("ALTER TABLE `$table` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci"); }
}

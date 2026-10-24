<?php
// Speeds up the dispensing pages on clinics with years of dispensing records. Adds the indexes only if they are missing. Safe to re-run.
$t = $conn->query("SHOW TABLES LIKE 'pharmacy'");
if ($t && $t->num_rows) {
    $have = [];
    $r = $conn->query("SHOW INDEX FROM pharmacy");
    foreach ($r ? $r->fetch_all(MYSQLI_ASSOC) : [] as $x) { $have[$x['Key_name']][(int)$x['Seq_in_index']] = $x['Column_name']; }
    foreach (['idx_pharmacy_mat_dispdate' => '(mat_id, dispDate)', 'idx_pharmacy_pump_dispdate' => '(pump_id, dispDate)', 'idx_pharmacy_dispdate' => '(dispDate)'] as $name => $cols) {
        if (!isset($have[$name])) { $conn->query("ALTER TABLE pharmacy ADD INDEX $name $cols"); }
    }
}

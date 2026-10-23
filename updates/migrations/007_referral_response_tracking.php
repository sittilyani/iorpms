<?php
// Referral response tracking: when a referral was acted on, by whom, and the outcome - needed to measure
// how long referrals wait before being acted on. Safe to re-run.
$has = function (string $col) use ($conn) { $r = $conn->query("SHOW COLUMNS FROM referral LIKE '$col'"); return $r && $r->num_rows > 0; };
$t = $conn->query("SHOW TABLES LIKE 'referral'");
if ($t && $t->num_rows) {
    foreach (['acted_at' => 'DATETIME NULL', 'acted_by' => 'VARCHAR(100) NULL', 'outcome' => 'VARCHAR(100) NULL', 'to_department' => 'VARCHAR(80) NULL', 'from_department' => 'VARCHAR(80) NULL'] as $c => $ddl) {
        if (!$has($c)) { $conn->query("ALTER TABLE referral ADD COLUMN `$c` $ddl"); }
    }
    $i = $conn->query("SHOW INDEX FROM referral WHERE Key_name = 'idx_referral_status_date'");
    if ($i && $i->num_rows === 0) { $conn->query("ALTER TABLE referral ADD INDEX idx_referral_status_date (status, referral_date)"); }
}

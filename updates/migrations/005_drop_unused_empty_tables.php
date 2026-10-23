<?php
// Tables that no page, trigger or view uses. Dropped only when they are still empty,
// so a clinic that happens to hold data in one of them keeps it.
foreach (['daily_report_comments', 'dispensing_comments', 'nursing_history', 'patientsignatures', 'temp_updates'] as $t) {
    $e = $conn->query("SHOW TABLES LIKE '$t'");
    if (!$e || !$e->num_rows) continue;
    $n = (int)$conn->query("SELECT COUNT(*) FROM `$t`")->fetch_row()[0];
    if ($n === 0) { $conn->query("DROP TABLE `$t`"); }
}

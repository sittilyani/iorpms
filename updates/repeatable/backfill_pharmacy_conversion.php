<?php
// Runs on EVERY update (repeatable): fills `conversion` for pharmacy rows that have none
// (historic rows, or rows from a screen that does not calculate it yet). Only touches NULLs.
// Only touches rows where conversion IS NULL, so it is safe to re-run.

// Methadone: mg / 5 (mg per mL) -> mL
mig_exec($conn, "UPDATE pharmacy
                 SET conversion = ROUND(dosage / 5, 2), conversion_unit = 'mL'
                 WHERE conversion IS NULL AND drugname LIKE '%methadone%' AND dosage IS NOT NULL");

// Buprenorphine rows whose drug name carries a strength ("Buprenorphine 8mg"): mg / strength -> tablets
$names = $conn->query("SELECT DISTINCT drugname FROM pharmacy
                       WHERE conversion IS NULL AND drugname LIKE '%buprenorphine%'");
while ($names && ($n = $names->fetch_assoc())) {
    if (preg_match('/(\d+(?:\.\d+)?)\s*mg/i', $n['drugname'], $m) && (float)$m[1] > 0) {
        $strength = (float)$m[1];
        $stmt = $conn->prepare("UPDATE pharmacy
                                SET conversion = ROUND(dosage / ?, 2), conversion_unit = 'tablets'
                                WHERE conversion IS NULL AND drugname = ? AND dosage IS NOT NULL");
        $stmt->bind_param('ds', $strength, $n['drugname']);
        $stmt->execute();
        $stmt->close();
    }
}

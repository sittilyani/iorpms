<?php
// Adds the "3 RHZE" TB regimen if it is not already present.
$chk = $conn->query("SELECT 1 FROM tb_regimens WHERE regimen_name = '3 RHZE' LIMIT 1");
if ($chk && $chk->num_rows === 0) {
    mig_exec($conn, "INSERT INTO tb_regimens (regimen_name) VALUES ('3 RHZE')");
}

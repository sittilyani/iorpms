<?php
include '../includes/config.php';

/* ===============================
   DATE FILTER (DEFAULT = LAST MONTH)
================================= */
$start_date = $_GET['start_date'] ?? date('Y-m-01', strtotime('first day of last month'));
$end_date   = $_GET['end_date']   ?? date('Y-m-t', strtotime('last day of last month'));

$sql = "
    SELECT COUNT(*) AS total
    FROM patients
    WHERE current_status = 'dead'
      AND sex = 'female'
      AND drugname = 'methadone'
      AND comp_date BETWEEN ? AND ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

echo $result['total'] ?? 0;

?>
<script>
function updateMonthlyDeadMetFemale() {
    const startDate = document.getElementById('start_date')?.value;
    const endDate   = document.getElementById('end_date')?.value;

    if (!startDate || !endDate) return;

    fetch(`monthlydeadmetfemale.php?start_date=${startDate}&end_date=${endDate}`)
        .then(res => res.text())
        .then(count => {
            const el = document.getElementById('monthlyDeadMetFemale');
            if (el) el.textContent = count;
        })
        .catch(err => console.error(err));
}

/* initial load */
updateMonthlyDeadMetFemale();

/* auto-refresh every 5 minutes */
setInterval(updateMonthlyDeadMetFemale, 300000);
</script>

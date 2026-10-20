<?php
session_start();
include '../includes/config.php';
require_once '../includes/pharmacy_helpers.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../public/signout.php');
    exit;
}

$strengths = bup_strengths($conn);
$drug  = $_GET['drug'] ?? (array_key_first($strengths) ?? '');
$from  = $_GET['from'] ?? date('Y-m-01');
$to    = $_GET['to']   ?? date('Y-m-d');
if (!isset($strengths[$drug])) { $drug = array_key_first($strengths) ?? ''; }

$rows = [];
if ($drug !== '') {
    $stmt = $conn->prepare(
        "SELECT * FROM bin_card_entries
          WHERE drugname = ? AND DATE(trans_date) BETWEEN ? AND ?
          ORDER BY trans_date ASC, entry_id ASC"
    );
    $stmt->bind_param('sss', $drug, $from, $to);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
$fmt = fn($n) => rtrim(rtrim(number_format((float)$n, 2, '.', ''), '0'), '.');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Buprenorphine Bin Card</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="../assets/css/tables.css" type="text/css">
</head>
<body>
<h2 style="color:#2C3162;">Buprenorphine Bin Card</h2>
<form method="GET" class="header" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
    <label>Strength
        <select name="drug">
            <?php foreach ($strengths as $name => $s): ?>
                <option value="<?= htmlspecialchars($name) ?>" <?= $name === $drug ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>From <input type="date" name="from" value="<?= htmlspecialchars($from) ?>"></label>
    <label>To <input type="date" name="to" value="<?= htmlspecialchars($to) ?>"></label>
    <button type="submit" class="search-input">View</button>
    <button type="button" onclick="window.print()">Print</button>
</form>

<?php if (!$rows): ?>
    <p>No bin card entries for this period.</p>
<?php else: ?>
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>MAT ID (Ref No.)</th>
            <th>Batch No.</th>
            <th>Received from</th>
            <th>Opening balance</th>
            <th>Quantity out: tablets (mg)</th>
            <th>Stock balance</th>
            <th>Issued by</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= htmlspecialchars(date('d-m-Y', strtotime($r['trans_date']))) ?></td>
            <td><?= htmlspecialchars($r['mat_id']) ?></td>
            <td><?= htmlspecialchars((string)$r['batch_number']) ?></td>
            <td><?= htmlspecialchars($r['received_from']) ?></td>
            <td><?= $fmt($r['opening_balance']) ?></td>
            <td><?= $r['qty_out_tablets'] !== null ? $fmt($r['qty_out_tablets']) . ' (' . $fmt($r['qty_out_mg']) . 'mg)' : '' ?></td>
            <td><?= $fmt($r['stock_balance']) ?></td>
            <td><?= htmlspecialchars((string)$r['issued_by']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
</body>
</html>

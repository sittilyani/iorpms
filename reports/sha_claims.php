<?php
/**
 * SHA (Social Health Authority) monthly drug claim - DRAFT for printing and attaching.
 * Claims are for drugs only: controlled drugs (methadone, buprenorphine) from the dispensing records plus other
 * prescriptions that were dispensed, priced from the products table. Facility, client and dispensing details are filled
 * automatically; the SHA number is captured here once per client and reused every month.
 * NOTE: this follows the usual claim content (client, SHA no., diagnosis, drug, quantity, price, amount). It is not the
 * official SHA/ClaimsPlus form: compare against the form your county/SHA contact supplies before relying on it.
 */
include "../includes/session_manager.php";
requireRole(['Admin', 'Super Admin', 'HRIO', 'Data Manager', 'Pharmacist', 'Demo']);
include "../includes/config.php";
require_once "../includes/analytics_widgets.php";
require_once "../includes/pharmacy_helpers.php";
require_once "../includes/county_logo.php";

$conn->query("CREATE TABLE IF NOT EXISTS sha_prices (drug_key VARCHAR(100) PRIMARY KEY, unit VARCHAR(20) NOT NULL, unit_price DECIMAL(10,2) NOT NULL DEFAULT 0, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
if (!ph_column_exists($conn, 'patients', 'sha_number')) { $conn->query("ALTER TABLE patients ADD COLUMN sha_number VARCHAR(30) DEFAULT NULL"); }

$fac = dash_facility($conn); $m = $conn->real_escape_string($fac['mfl']); $C = 'COLLATE utf8mb4_general_ci';
$fs = dash_rows($conn, "SELECT subcountyname, facilityincharge FROM facility_settings LIMIT 1")[0] ?? [];
$mo = preg_match('/^\d{4}-\d{2}$/', (string)($_GET['month'] ?? $_POST['month'] ?? '')) ? ($_GET['month'] ?? $_POST['month']) : date('Y-m', strtotime('first day of last month'));
$start = $mo . '-01'; $end = date('Y-m-t', strtotime($start)); $endT = $end . ' 23:59:59';
$canEdit = strtolower((string)($_SESSION['userrole'] ?? '')) !== 'demo';
$note = '';

// ---- save SHA numbers / methadone price ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canEdit) {
    if (isset($_POST['methadone_price']) && $_POST['methadone_price'] !== '') {
        $pr = max(0, (float)$_POST['methadone_price']);
        $st = $conn->prepare("INSERT INTO sha_prices (drug_key, unit, unit_price) VALUES ('methadone', 'mL', ?) ON DUPLICATE KEY UPDATE unit_price = VALUES(unit_price)"); $st->bind_param('d', $pr); $st->execute();
    }
    foreach ((array)($_POST['sha'] ?? []) as $mid => $num) {
        $num = preg_replace('/[^A-Za-z0-9\-\/]/', '', (string)$num);
        $st = $conn->prepare("UPDATE patients SET sha_number = NULLIF(?, '') WHERE mat_id = ? AND mflcode = ?"); $st->bind_param('sss', $num, $mid, $fac['mfl']); $st->execute();
    }
    $note = 'Saved.';
}

// ---- price list ----
$methPrice = (float)dash_val($conn, "SELECT unit_price FROM sha_prices WHERE drug_key='methadone'", 0);
if ($methPrice <= 0) { $methPrice = (float)dash_val($conn, "SELECT COALESCE(NULLIF(unit_price,0), price) FROM products WHERE productname LIKE 'Methadone%' AND currentstatus='active' LIMIT 1", 0); }
$bupPrice = function (string $drug): float {
    global $conn;
    if (!preg_match('/(\d+(?:\.\d+)?)\s*mg/i', $drug, $x)) return 0.0;
    $mg = $conn->real_escape_string(rtrim(rtrim($x[1], '0'), '.') . 'mg');
    return (float)dash_val($conn, "SELECT COALESCE(NULLIF(unit_price,0), price) FROM products WHERE productname LIKE 'Buprenorphine $mg/%' AND currentstatus='active' ORDER BY id LIMIT 1", 0);
};

// ---- claim lines ----
$lines = [];
$q = dash_rows($conn, "SELECT p.mat_id, p.clientName, p.sname, p.sex, p.dob, p.nat_id, p.sha_number, TRIM(x.drugname) drug, COUNT(DISTINCT DATE(x.dispDate)) days, SUM(x.dosage) mg, SUM(CASE WHEN x.conversion_unit='tablets' THEN x.conversion ELSE 0 END) tabs
    FROM pharmacy x JOIN patients p ON p.mat_id = x.mat_id $C WHERE p.mflcode='$m' AND x.dispDate BETWEEN '$start' AND '$endT' AND x.dosage > 0 GROUP BY p.mat_id, TRIM(x.drugname) ORDER BY p.clientName, drug");
foreach ($q as $r) {
    if (!ph_is_controlled($r['drug'])) continue;
    if (ph_is_methadone($r['drug'])) { $qty = round($r['mg'] / METHADONE_MG_PER_ML, 1); $unit = 'mL'; $up = $methPrice; }
    else { $qty = (float)$r['tabs'] > 0 ? (float)$r['tabs'] : round($r['mg'] / max(0.1, (float)(ph_strength_from_name($r['drug']) ?? 1)), 2); $unit = 'tablets'; $up = $bupPrice($r['drug']); }
    $lines[] = $r + ['qty' => $qty, 'unit' => $unit, 'up' => $up, 'amt' => round($qty * $up, 2), 'kind' => 'Controlled'];
}
$op = dash_rows($conn, "SELECT p.mat_id, p.clientName, p.sname, p.sex, p.dob, p.nat_id, p.sha_number, d.drug_name drug, COUNT(DISTINCT o.prescription_date) days, SUM(d.total_dispensed) qty, MAX(pr.unit_price) up1, MAX(pr.price) up2
    FROM prescription_drugs d JOIN other_prescriptions o ON o.prescription_id = d.prescription_id $C JOIN patients p ON p.mat_id = o.mat_id $C
    LEFT JOIN products pr ON LOWER(TRIM(pr.productname)) = LOWER(TRIM(d.drug_name)) $C
    WHERE p.mflcode='$m' AND o.prescription_date BETWEEN '$start' AND '$end' AND d.total_dispensed > 0 GROUP BY p.mat_id, d.drug_name ORDER BY p.clientName");
foreach ($op as $r) {
    $up = (float)($r['up1'] ?: $r['up2']); $qty = (float)$r['qty'];
    $lines[] = $r + ['mg' => null, 'tabs' => null, 'qty' => $qty, 'unit' => 'units', 'up' => $up, 'amt' => round($qty * $up, 2), 'kind' => 'Other'];
}
$total = array_sum(array_column($lines, 'amt'));
$clients = array_unique(array_column($lines, 'mat_id'));
$noSha = count(array_filter(array_unique(array_map(fn($l) => $l['sha_number'] ? '' : $l['mat_id'], $lines))));
$noPrice = array_unique(array_map(fn($l) => $l['up'] > 0 ? '' : $l['drug'], $lines)); $noPrice = array_filter($noPrice);
$byDrug = [];
foreach ($lines as $l) { $k = $l['drug'] . '|' . $l['unit'] . '|' . $l['up']; $byDrug[$k] = ($byDrug[$k] ?? ['drug' => $l['drug'], 'unit' => $l['unit'], 'up' => $l['up'], 'qty' => 0, 'amt' => 0, 'clients' => []]); $byDrug[$k]['qty'] += $l['qty']; $byDrug[$k]['amt'] += $l['amt']; $byDrug[$k]['clients'][$l['mat_id']] = 1; }
$h = fn($v) => htmlspecialchars((string)$v);
$idx = fn($v) => number_format((float)$v, 2);

if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv'); header('Content-Disposition: attachment; filename="SHA_drug_claim_' . $mo . '.csv"');
    $o = fopen('php://output', 'w'); fputcsv($o, ['facility', 'mfl', 'period', 'sha_number', 'client', 'mat_id', 'id_number', 'sex', 'dob', 'diagnosis', 'drug', 'days_dispensed', 'quantity', 'unit', 'unit_price', 'amount']);
    foreach ($lines as $l) { fputcsv($o, [$fac['facilityname'], $fac['mfl'], $mo, $l['sha_number'], trim($l['clientName'] . ' ' . $l['sname']), $l['mat_id'], $l['nat_id'], $l['sex'], $l['dob'], 'F11.2 Opioid dependence', $l['drug'], $l['days'], $l['qty'], $l['unit'], $l['up'], $l['amt']]); }
    exit;
}

dash_head('SHA Monthly Drug Claim', $fac, date('F Y', strtotime($start)) . ' - DRAFT');
$cl = county_logo_path($fac['countyname']);
echo '<style>.tool{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:14px}.tool input,.tool select{padding:7px 9px;border:1px solid #cfc6ea;border-radius:6px}.btn{background:#2D008A;color:#fff;border:0;border-radius:6px;padding:8px 16px;text-decoration:none;cursor:pointer;font-size:13px}.btn.g{background:#04B04B}.btn.o{background:#fff;color:#2D008A;border:1px solid #2D008A}
.warn{background:#fff8e1;border-left:5px solid #FFC12E;border-radius:8px;padding:10px 14px;margin-bottom:12px;font-size:13px}.sha{width:130px;padding:4px 6px;border:1px solid #cfc6ea;border-radius:5px}
.sheet table{font-size:12px}.sheet td.n,.sheet th.n{text-align:right}.letter{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}.sig{display:flex;gap:40px;margin-top:34px}.sig div{flex:1;border-top:1px solid #333;padding-top:3px;font-size:12px}
@media print{.noprint,.top{display:none!important}.wrap{padding:0}.card{border:0}input.sha{border:0;padding:0}}</style>';
echo '<form method="get" class="tool noprint"><label>Claim month <input type="month" name="month" value="' . $h($mo) . '" max="' . date('Y-m') . '"></label><button class="btn">Show</button><a class="btn o" href="#" onclick="window.print();return false">Print</a><a class="btn o" href="?month=' . $h($mo) . '&export=csv">Download CSV</a></form>';
if ($note) echo '<div class="warn noprint" style="background:#e6f8ee;border-color:#04B04B">' . $h($note) . '</div>';
if ($noPrice) echo '<div class="warn noprint"><b>No price set for:</b> ' . $h(implode(', ', $noPrice)) . '. These lines show 0.00 until a price exists in the products list' . (in_array('Methadone', $noPrice, true) ? ' (or enter the methadone price per mL below)' : '') . '.</div>';
if ($noSha) echo '<div class="warn noprint"><b>' . $noSha . ' client(s) have no SHA number yet.</b> Type them in the table below and press Save; they are remembered for next month.</div>';
echo '<div class="card sheet"><div class="b"><div class="letter"><div><b style="font-size:16px;color:#2D008A">SOCIAL HEALTH AUTHORITY - DRUG CLAIM (DRAFT)</b><br>' . $h($fac['facilityname']) . ' &middot; MFL ' . $h($fac['mfl']) . '<br>' . $h($fac['countyname'] . ' County' . (!empty($fs['subcountyname']) ? ', ' . $fs['subcountyname'] . ' Sub County' : '')) . '<br>Claim period: <b>' . date('j M', strtotime($start)) . ' - ' . date('j M Y', strtotime($end)) . '</b> &middot; Service: MAT (opioid dependence, ICD-10 F11.2) &middot; Claim type: drugs only</div>' . ($cl ? '<img src="../' . $h($cl) . '" height="60">' : '') . '</div>';
echo '<h3 style="margin:12px 0 4px;color:#2D008A;font-size:14px">A. Summary</h3><table><tr><th>Drug</th><th class="n">Clients</th><th class="n">Quantity</th><th>Unit</th><th class="n">Unit price (KES)</th><th class="n">Amount (KES)</th></tr>';
foreach ($byDrug as $b) { echo '<tr><td>' . $h($b['drug']) . '</td><td class="n">' . count($b['clients']) . '</td><td class="n">' . $idx($b['qty']) . '</td><td>' . $h($b['unit']) . '</td><td class="n">' . $idx($b['up']) . '</td><td class="n">' . $idx($b['amt']) . '</td></tr>'; }
echo '<tr style="font-weight:700;background:#f3effb"><td>TOTAL CLAIM</td><td class="n">' . count($clients) . ' clients</td><td colspan="3"></td><td class="n">' . $idx($total) . '</td></tr></table>';
echo '<form method="post"><input type="hidden" name="month" value="' . $h($mo) . '"><h3 style="margin:16px 0 4px;color:#2D008A;font-size:14px">B. Claim lines (attach to the summary)</h3><table><tr><th>#</th><th>SHA no.</th><th>Client</th><th>MAT ID</th><th>ID no.</th><th>Sex</th><th>Age</th><th>Diagnosis</th><th>Drug</th><th class="n">Days</th><th class="n">Qty</th><th>Unit</th><th class="n">Price</th><th class="n">Amount</th></tr>';
$i = 0; $seen = [];
foreach ($lines as $l) {
    $i++; $age = $l['dob'] ? (int)date_diff(date_create($l['dob']), date_create($end))->y : '';
    $shaCell = isset($seen[$l['mat_id']]) ? $h($l['sha_number']) : ($canEdit ? '<input class="sha" name="sha[' . $h($l['mat_id']) . ']" value="' . $h($l['sha_number']) . '" placeholder="SHA number">' : $h($l['sha_number']));
    $seen[$l['mat_id']] = 1;
    echo '<tr><td>' . $i . '</td><td>' . $shaCell . '</td><td>' . $h(trim($l['clientName'] . ' ' . $l['sname'])) . '</td><td>' . $h($l['mat_id']) . '</td><td>' . $h($l['nat_id']) . '</td><td>' . $h(substr((string)$l['sex'], 0, 1)) . '</td><td>' . $age . '</td><td>F11.2</td><td>' . $h($l['drug']) . '</td><td class="n">' . $l['days'] . '</td><td class="n">' . $idx($l['qty']) . '</td><td>' . $h($l['unit']) . '</td><td class="n">' . $idx($l['up']) . '</td><td class="n">' . $idx($l['amt']) . '</td></tr>';
}
if (!$lines) echo '<tr><td colspan="14" class="empty">No drugs were dispensed to clients in this month.</td></tr>';
echo '</table>';
if ($canEdit) echo '<div class="noprint" style="margin-top:12px;display:flex;gap:14px;align-items:center;flex-wrap:wrap"><label>Methadone price per mL (KES) <input type="number" step="0.01" min="0" name="methadone_price" value="' . ($methPrice > 0 ? $h($methPrice) : '') . '" style="width:110px;padding:6px;border:1px solid #cfc6ea;border-radius:6px"></label><button class="btn g">Save SHA numbers and price</button></div>';
echo '</form><div class="sig"><div>Prepared by: ' . $h(getUserFullName()) . ' (' . $h($_SESSION['userrole'] ?? '') . ')</div><div>Facility in-charge: ' . $h($fs['facilityincharge'] ?? '') . '</div><div>Date and stamp</div></div>';
echo '<p style="font-size:11px;color:#777;margin-top:12px">Prices: buprenorphine/naloxone and other prescriptions from the products list; methadone from the price entered above. Draft prepared with EasyFlow-L - check against the official SHA claim form before submission.</p></div></div>';
dash_foot();

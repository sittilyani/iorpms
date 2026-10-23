<?php
/**
 * Seeds stock, bin cards, pump calibration and transfers so the pharmacy and transfer pages have history:
 *   - Methadone (mL), Buprenorphine 2/4/8 mg (tablets) and other drugs: monthly stock cards (stock_movements),
 *     stores_inventory, and the `stocks` table for the "other drugs" products
 *   - Buprenorphine bin card: one line per dispensing event + receipts, so daily bin cards work for every strength
 *   - Pump calibration history, priming log and reservoir top-ups
 *   - transfer_forms and transfer_out_requests (sent / accepted / received) for the existing Transout / Transit clients
 *
 *   php sql_queries/seed_stock_and_logistics.php            seed
 *   php sql_queries/seed_stock_and_logistics.php --reseed   remove what this script added, then seed
 *   php sql_queries/seed_stock_and_logistics.php --reset    remove only
 * Requires seed_demo_data.php (clients + dispensing) to have been run.
 */
if (PHP_SAPI !== 'cli') { die('CLI only'); }
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/transfer_helpers.php';
require_once __DIR__ . '/../includes/pump_guard.php';
mysqli_report(MYSQLI_REPORT_OFF);
$conn->set_charset('utf8mb4');
date_default_timezone_set('Africa/Nairobi');
mt_srand(20261023);
transfer_ensure_schema($conn);
pump_guard_schema($conn);

$reset = in_array('--reset', $argv) || in_array('--reseed', $argv);
if ($reset) {
    $conn->query("DELETE FROM stock_movements WHERE batch_number LIKE 'SD-%'");
    $conn->query("DELETE FROM bin_card_entries WHERE batch_number LIKE 'SD-%'");
    $conn->query("DELETE FROM stocks WHERE batch LIKE 'SD-%'");
    $conn->query("DELETE FROM stores_inventory WHERE supplier_name LIKE '%(seed)'");
    $conn->query("DELETE FROM pump_calibration WHERE notes LIKE 'seed:%'");
    $conn->query("DELETE FROM pump_prime_log WHERE user_id IS NULL");
    $conn->query("DELETE FROM pump_reservoir_history WHERE pump_id = 1 AND created_at < '2000-01-01' OR milligrams = 0 AND new_milligrams = 1000000");
    $conn->query("DELETE FROM transfer_forms WHERE json_data LIKE '%\"seed\":true%'");
    $conn->query("DELETE FROM transfer_out_requests WHERE created_by = 'SEED'");
    $conn->query("UPDATE pharmacy SET pump_id = NULL WHERE pump_id = 1 AND pharm_officer_name IN (SELECT full_name FROM tblusers WHERE username LIKE 'seed.%')");
    echo "Removed stock/logistics seed rows (bup strength re-assignment of dispensing rows is not undone).\n";
    if (!in_array('--reseed', $argv)) exit;
}

$q = fn($v) => $v === null ? 'NULL' : (is_int($v) || is_float($v) ? (string)$v : "'" . $conn->real_escape_string((string)$v) . "'");
$pick = fn(array $a) => $a[mt_rand(0, count($a) - 1)];
$bulk = function (string $table, array $cols, array $rows) use ($conn) {
    if (!$rows) { echo str_pad($table, 28) . "0 rows\n"; return; }
    foreach (array_chunk($rows, 500) as $ch) {
        $sql = "INSERT INTO `$table` (" . implode(',', $cols) . ") VALUES " . implode(',', array_map(fn($r) => '(' . implode(',', $r) . ')', $ch));
        if (!$conn->query($sql)) { echo "!! $table failed: " . $conn->error . "\n"; return; }
    }
    echo str_pad($table, 28) . count($rows) . " rows\n";
};
$pharmacists = []; $r = $conn->query("SELECT full_name FROM tblusers WHERE userrole='Pharmacist' AND username LIKE 'seed.%'"); while ($x = $r->fetch_row()) $pharmacists[] = $x[0];
if (!$pharmacists) $pharmacists = ['Pharmacist'];
$today = strtotime(date('Y-m-d'));
$suppliers = ['KEMSA', 'MEDS', 'LVCT Health'];
$firstDisp = $conn->query("SELECT DATE(MIN(dispDate)) FROM pharmacy")->fetch_row()[0];
if (!$firstDisp) die("No dispensing history - run seed_demo_data.php first.\n");

// -------------------------------------------------------------------------------------------------
// 1. Re-assign buprenorphine clients to 2 / 4 / 8 mg tablets so every strength has a bin card
// -------------------------------------------------------------------------------------------------
$bupPts = $conn->query("SELECT mat_id, MAX(dosage) mx FROM pharmacy WHERE drugname LIKE 'Buprenorphine%' GROUP BY mat_id")->fetch_all(MYSQLI_ASSOC);
foreach ($bupPts as $b) {
    $mx = (float)$b['mx'];
    $name = ($mx >= 16) ? 'Buprenorphine 8mg' : (($mx >= 12) ? 'Buprenorphine 4mg' : 'Buprenorphine 2mg');
    $mg = (float)preg_replace('/\D/', '', $name);
    $e = $conn->real_escape_string($b['mat_id']);
    $conn->query("UPDATE pharmacy SET drugname = '$name', conversion = ROUND(dosage / $mg, 2), conversion_unit = 'tablets', formulation_detail = CONCAT('$name x ', ROUND(dosage / $mg, 0)) WHERE mat_id = '$e' AND drugname LIKE 'Buprenorphine%'");
    $conn->query("UPDATE patients SET drugname = '$name' WHERE mat_id = '$e'");
}
echo "Re-assigned " . count($bupPts) . " buprenorphine clients to 2/4/8 mg tablets.\n";

// -------------------------------------------------------------------------------------------------
// helpers: monthly stock-card builder
// -------------------------------------------------------------------------------------------------
$months = [];
for ($t = strtotime(date('Y-m-01', strtotime($firstDisp))); $t <= $today; $t = strtotime('+1 month', $t)) $months[] = date('Y-m', $t);
$smRows = []; $siRows = [];
$smCols = ['transactionType', 'drugID', 'drugname', 'opening_bal', 'qty_in', 'received_from', 'qty_out', 'batch_number', 'expiry_date', 'received_by', 'total_qty', 'trans_date'];
$siCols = ['drugID', 'drugname', 'from_supplier', 'supplier_name', 'to_dispensing', 'stores_balance', 'transaction_date', 'received_by_full_name', 'issued_to_full_name'];
$finalBal = [];

function month_end(string $ym): string { global $today; $e = strtotime(date('Y-m-t', strtotime($ym . '-01')) . ' 17:00:00'); return date('Y-m-d H:i:s', min($e, time())); }

/** $cons: [ym => units consumed]; $step: receipt rounding; $cover: months of cover to hold after a receipt */
function build_monthly(int $drugId, string $name, array $months, array $cons, int $step, float $cover, string $code, array $pharmacists, array $suppliers, callable $q, callable $pick, array &$sm, array &$si, array &$finalBal): void
{
    $open = 0; $n = 0;
    foreach ($months as $ym) {
        $use = (int)round($cons[$ym] ?? 0);
        $recv = 0;
        if ($open < $use * 1.2) { $recv = (int)(ceil(max($step, $use * $cover - $open) / $step) * $step); }
        $close = $open + $recv - $use;
        $by = $pick($pharmacists); $sup = $pick($suppliers); $n++;
        $batch = 'SD-' . $code . '-' . str_replace('-', '', $ym);
        $exp = date('Y-m-d', strtotime($ym . '-01 +20 months'));
        $sm[] = array_map($q, ['Purchase', $drugId, $name, (int)$open, (int)$recv, $recv ? $sup : 'N/A', (int)$use, $batch, $exp, $by, (int)$close, month_end($ym)]);
        if ($recv) $si[] = array_map($q, [$drugId, $name, (int)$recv, $sup . ' (seed)', 0, (int)($open + $recv), $ym . '-02 09:00:00', $by, null]);
        if ($use) $si[] = array_map($q, [$drugId, $name, 0, 'Dispensing (seed)', (int)$use, (int)$close, $ym . '-28 16:00:00', $by, $pick($pharmacists)]);
        $open = $close;
    }
    $finalBal[$name] = $open;
}

// -------------------------------------------------------------------------------------------------
// 2. Methadone (mL, 5 mg/mL)
// -------------------------------------------------------------------------------------------------
$cons = [];
foreach ($conn->query("SELECT DATE_FORMAT(dispDate,'%Y-%m') ym, SUM(dosage)/5 ml FROM pharmacy WHERE drugname LIKE 'Methadone%' GROUP BY ym") as $row) $cons[$row['ym']] = $row['ml'];
build_monthly(2, 'Methadone', $months, $cons, 5000, 1.6, 'MTD', $pharmacists, $suppliers, $q, $pick, $smRows, $siRows, $finalBal);

// -------------------------------------------------------------------------------------------------
// 3. Buprenorphine strengths: event-driven simulation -> bin card lines + monthly stock cards
// -------------------------------------------------------------------------------------------------
$binRows = []; $binCols = ['trans_date', 'drugID', 'drugname', 'mat_id', 'mat_number', 'batch_number', 'received_from', 'opening_balance', 'qty_in', 'qty_out_tablets', 'qty_out_mg', 'stock_balance', 'disp_id', 'issued_by'];
$bupDefs = ['Buprenorphine 2mg' => [6, 2.0, 6000, 'B2'], 'Buprenorphine 4mg' => [7, 4.0, 2500, 'B4'], 'Buprenorphine 8mg' => [8, 8.0, 3000, 'B8']];
foreach ($bupDefs as $name => [$drugId, $mg, $recvSize, $code]) {
    $e = $conn->real_escape_string($name);
    $events = $conn->query("SELECT disp_id, dispDate, mat_id, mat_number, dosage, conversion, pharm_officer_name FROM pharmacy WHERE drugname = '$e' ORDER BY dispDate, disp_id")->fetch_all(MYSQLI_ASSOC);
    $bal = 0; $batchN = 0; $batch = ''; $monthly = [];
    $receive = function (string $ts) use (&$bal, &$batchN, &$batch, $recvSize, $code, $drugId, $name, $suppliers, $pick, $q, &$binRows, &$monthly) {
        $batchN++; $batch = 'SD-' . $code . '-' . str_pad($batchN, 3, '0', STR_PAD_LEFT);
        $from = $pick($suppliers); $open = $bal; $bal += $recvSize;
        $binRows[] = array_map($q, [$ts, $drugId, $name, null, null, $batch, $from, (float)$open, (float)$recvSize, null, null, (float)$bal, null, 'Stores']);
        $ym = substr($ts, 0, 7); $monthly[$ym]['in'] = ($monthly[$ym]['in'] ?? 0) + $recvSize; $monthly[$ym]['from'] = $from; $monthly[$ym]['batch'] = $batch;
    };
    if ($events) $receive(date('Y-m-d 08:00:00', strtotime($events[0]['dispDate'] . ' -1 day')));
    foreach ($events as $ev) {
        $tabs = (float)($ev['conversion'] ?: round($ev['dosage'] / $mg, 2));
        if ($bal < $tabs + 400) $receive(date('Y-m-d 07:30:00', strtotime($ev['dispDate'])));
        $open = $bal; $bal -= $tabs;
        $binRows[] = array_map($q, [$ev['dispDate'], $drugId, $name, $ev['mat_id'], $ev['mat_number'], $batch, '', (float)$open, null, $tabs, (float)$ev['dosage'], (float)$bal, (int)$ev['disp_id'], $ev['pharm_officer_name']]);
        $ym = substr($ev['dispDate'], 0, 7); $monthly[$ym]['out'] = ($monthly[$ym]['out'] ?? 0) + $tabs;
        $monthly[$ym]['batch'] = $monthly[$ym]['batch'] ?? $batch;
        $monthly[$ym]['close'] = $bal;
    }
    // monthly stock card lines from the same simulation (latest line = current balance)
    $open = 0; $lastBatch = 'SD-' . $code . '-001'; $lastFrom = 'KEMSA';
    foreach ($months as $ym) {
        $m = $monthly[$ym] ?? []; $in = (int)($m['in'] ?? 0); $out = (int)round($m['out'] ?? 0);
        $close = $open + $in - $out; if (isset($m['close'])) $close = (int)round($m['close']); // end-of-month balance from the event run
        if (!empty($m['batch'])) $lastBatch = $m['batch']; if (!empty($m['from'])) $lastFrom = $m['from'];
        $by = $pick($pharmacists);
        $smRows[] = array_map($q, ['Purchase', $drugId, $name, (int)$open, $in, $in ? $lastFrom : 'N/A', $out, $lastBatch, date('Y-m-d', strtotime($ym . '-01 +20 months')), $by, $close, month_end($ym)]);
        if ($in) $siRows[] = array_map($q, [$drugId, $name, $in, $lastFrom . ' (seed)', 0, (int)($open + $in), $ym . '-02 09:00:00', $by, null]);
        if ($out) $siRows[] = array_map($q, [$drugId, $name, 0, 'Dispensing (seed)', $out, $close, $ym . '-28 16:00:00', $by, $pick($pharmacists)]);
        $open = $close;
    }
    $finalBal[$name] = $open;
}

// -------------------------------------------------------------------------------------------------
// 4. Other drugs and naltrexone (stock cards), and the `stocks` table for prescribed products
// -------------------------------------------------------------------------------------------------
$others = [850 => ['Ceftriaxone 1g', 90, 500], 828 => ['Diclofenac 75mg Injection', 80, 500], 827 => ['Medroxyprogesterone Injection 150mg/mL', 40, 200], 1032 => ['Tenofovir/lamivudine/dolutegravir', 600, 3000],
    813 => ['Amoxicillin 500mg', 400, 2000], 779 => ['Metronidazole 400mg', 300, 2000], 830 => ['Amitriptyline 25mg', 250, 1500], 832 => ['Diazepam 5mg', 120, 1000], 977 => ['Albendazole 400mg', 60, 500],
    799 => ['Vitamin B Complex', 500, 3000], 852 => ['Zinc Sulfate DT 20mg', 200, 1000], 958 => ['Condoms', 1500, 5000], 9 => ['Naltrexone 50mg', 60, 300]];
foreach ($others as $id => [$name, $avg, $step]) {
    $c = []; foreach ($months as $ym) $c[$ym] = max(0, $avg * mt_rand(70, 130) / 100);
    build_monthly($id, $name, $months, $c, $step, 2.0, 'OD' . $id, $pharmacists, $suppliers, $q, $pick, $smRows, $siRows, $finalBal);
}
$bulk('stock_movements', $smCols, $smRows);
$bulk('stores_inventory', $siCols, $siRows);
$bulk('bin_card_entries', $binCols, $binRows);

// `stocks`: products prescribed through "other drugs" (drugID column holds the product id)
$prod = $conn->query("SELECT MIN(id) id, productname, MIN(brandname) brandname, MIN(reorder_level) reorder_level FROM products WHERE CHAR_LENGTH(productname) <= 100 AND productname REGEXP '^(Paracetamol|Amoxicillin|Ibuprofen|Metronidazole|Cotrimoxazole|Ciprofloxacin|Diclofenac|Vitamin|Zinc|Albendazole|Loratadine|Omeprazole)' GROUP BY productname ORDER BY MIN(id) LIMIT 14");
$prod = $prod ? $prod->fetch_all(MYSQLI_ASSOC) : [];
$stRows = []; $stCols = ['drugID', 'transactionType', 'productname', 'brandname', 'reorderLevel', 'openingBalance', 'quantityIn', 'batch', 'expiryDate', 'receivedFrom', 'quantityOut', 'transDate', 'stockBalance', 'status', 'transBy'];
foreach ($prod as $p) {
    $open = 0; $avg = mt_rand(40, 200);
    foreach ($months as $ym) {
        if ($ym < date('Y-m', strtotime('-18 months'))) continue;
        $use = (int)round($avg * mt_rand(70, 130) / 100); $in = $open < $use * 1.2 ? (int)(ceil(max(100, $use * 2 - $open) / 100) * 100) : 0; $close = $open + $in - $use;
        $stRows[] = array_map($q, [(int)$p['id'], 'Purchase', $p['productname'], $p['brandname'], (int)($p['reorder_level'] ?: 100), (float)$open, $in, 'SD-' . $p['id'] . '-' . str_replace('-', '', $ym), date('Y-m-d', strtotime($ym . '-01 +24 months')), $pick($suppliers), $use, month_end($ym), $close, 'Active', $pick($pharmacists)]);
        $open = $close;
    }
}
$bulk('stocks', $stCols, $stRows);

// -------------------------------------------------------------------------------------------------
// 5. Pump: calibration history, priming log, reservoir top-ups, pump_id on recent methadone dispensing
// -------------------------------------------------------------------------------------------------
$calRows = []; $primeRows = []; $factor = 400.0; $prev = 0.0;
$calCols = ['pump_id', 'calibration_factor', 'concentration_mg_per_ml', 'tubing_type', 'calibrated_by', 'calibrated_at', 'notes', 'is_active', 'temperature_celsius', 'density_g_per_ml', 'tube_inner_diameter_mm', 'tube_type_code', 'calibration_volume_ml', 'measured_volume_ml', 'volume_correction', 'temp_correction', 'previous_factor'];
$tubings = [['Masterflex L/S 15', 'L/S 15', 2.40], ['Masterflex L/S 16', 'L/S 16', 3.10]];
$days = []; for ($t = strtotime('-150 days', $today); $t < $today; $t += 86400) { if (date('N', $t) < 6 || mt_rand(0, 9) === 0) $days[] = $t; }
$tub = $tubings[0];
foreach ($days as $i => $t) {
    if ($i % 40 === 39) $tub = $tubings[$i % 2 ? 1 : 0]; // tube replaced now and then
    $ts = date('Y-m-d', $t) . sprintf(' 07:%02d:00', mt_rand(15, 55));
    $measured = round(10 * (1 + mt_rand(-45, 45) / 1000), 3); $vc = round(10 / $measured, 6); $temp = mt_rand(220, 290) / 10; $tc = round(1 + ($temp - 25) * 0.0004, 6);
    $prev = $factor; $factor = round($factor * $vc * $tc, 2); if ($factor < 350 || $factor > 450) $factor = 400.0;
    $calRows[] = array_map($q, [1, $factor, 5.00, $tub[0], $pick($pharmacists), $ts, 'seed: routine morning calibration, measured ' . $measured . ' mL', ($i === count($days) - 1) ? 1 : 0, $temp, 1.0200, $tub[2], $tub[1], 10.0, $measured, $vc, $tc, $prev]);
    $primeRows[] = array_map($q, [1, 'D', date('Y-m-d', $t) . sprintf(' 07:%02d:00', mt_rand(5, 14)), null]);
    if (mt_rand(0, 5) === 0) $primeRows[] = array_map($q, [1, 'R', date('Y-m-d', $t) . ' 17:45:00', null]); // end-of-day reverse prime
}
$bulk('pump_calibration', $calCols, $calRows);
$bulk('pump_prime_log', ['pump_id', 'direction', 'primed_at', 'user_id'], $primeRows);
$resRows = []; for ($t = strtotime('-120 days', $today); $t < $today; $t += 7 * 86400) { $resRows[] = array_map($q, [1, (float)mt_rand(50000, 200000), 1000000.0, date('Y-m-d 08:00:00', $t), date('Y-m-d 08:00:00', $t + 7 * 86400)]); }
$bulk('pump_reservoir_history', ['pump_id', 'milligrams', 'new_milligrams', 'topup_from', 'topup_to'], $resRows);
$conn->query("UPDATE pharmacy SET pump_id = 1 WHERE drugname LIKE 'Methadone%' AND dispDate >= DATE_SUB(CURDATE(), INTERVAL 120 DAY) AND pump_id IS NULL");
echo "Marked the last 120 days of methadone dispensing as pump dispensing (pump_id 1).\n";

// -------------------------------------------------------------------------------------------------
// 6. Transfer forms and transfer-out requests for the existing Transout / Transit clients
// -------------------------------------------------------------------------------------------------
$fac = $conn->query("SELECT fs.facilityname, COALESCE(NULLIF(fs.mflcode,''), f.mflcode) mfl, fs.countyname, fs.subcountyname FROM facility_settings fs LEFT JOIN facilities f ON f.id = fs.facility_id LIMIT 1")->fetch_assoc();
$targets = $conn->query("SELECT f.facilityname, f.mflcode, (SELECT email FROM update_contacts u WHERE u.facility_id = f.id AND u.email <> '' LIMIT 1) email FROM facilities f WHERE f.mflcode <> '" . $conn->real_escape_string($fac['mfl']) . "' ORDER BY RAND() LIMIT 40")->fetch_all(MYSQLI_ASSOC);
$clins = []; $r = $conn->query("SELECT full_name FROM tblusers WHERE userrole='Clinician' AND username LIKE 'seed.%'"); while ($x = $r->fetch_row()) $clins[] = $x[0];
$psychs = []; $r = $conn->query("SELECT full_name FROM tblusers WHERE userrole='Psychologist' AND username LIKE 'seed.%'"); while ($x = $r->fetch_row()) $psychs[] = $x[0];
$reasons = ['Relocated for work', 'Family relocation', 'Closer to home', 'Incarcerated - transferred to prison clinic', 'Travelling for 2 weeks', 'Seeking services nearer to residence', 'Referred for specialised care'];
$cand = $conn->query("SELECT p.*, (SELECT MAX(dispDate) FROM pharmacy x WHERE x.mat_id = p.mat_id) last_disp FROM patients p WHERE p.hcw_name = 'SEED' AND p.current_status IN ('Transout','Transit') ORDER BY p.p_id")->fetch_all(MYSQLI_ASSOC);
$tfRows = []; $trRows = [];
$tfCols = ['p_id', 'facilityname', 'mflcode', 'county', 'sub_county', 'clientName', 'mat_id', 'sex', 'dob', 'client_phone', 'reg_facility', 'reg_date', 'referral_date', 'type_of_movement', 'other_specify', 'from_site', 'to_site', 'reason_transfer', 'clinical_history', 'psychosocial', 'lab_investigations', 'vaccinations', 'diagnosis', 'current_dose', 'date_last_administered', 'other_medications', 'clinician_name', 'clinician_org', 'clinician_signature', 'clinician_date', 'counselor_name', 'counselor_org', 'counselor_signature', 'counselor_date', 'json_data'];
$trCols = ['token', 'direction', 'mat_id', 'client_name', 'from_facility', 'from_mfl', 'to_facility', 'to_mfl', 'to_email', 'type_of_movement', 'transit_start', 'transit_end', 'status', 'email_sent', 'payload', 'created_by', 'created_at', 'accepted_at'];
foreach ($cand as $i => $p) {
    $isTransit = $p['current_status'] === 'Transit';
    $tgt = $pick($targets); $date = $p['last_disp'] ? substr($p['last_disp'], 0, 10) : substr($p['reg_date'], 0, 10);
    $clin = $pick($clins); $coun = $pick($psychs);
    $hiv = $conn->query("SELECT hiv_status, art_regimen, tb_status FROM medical_history_new WHERE mat_id = '" . $conn->real_escape_string($p['mat_id']) . "' ORDER BY id DESC LIMIT 1")->fetch_assoc() ?: [];
    $history = 'HIV: ' . ($hiv['hiv_status'] ?? 'Unknown') . (!empty($hiv['art_regimen']) && $hiv['art_regimen'] !== 'None' ? ' on ' . $hiv['art_regimen'] : '') . '; TB: ' . ($hiv['tb_status'] ?? 'Negative');
    $tStart = $isTransit ? $date : null; $tEnd = $isTransit ? date('Y-m-d', strtotime($date . ' +' . mt_rand(5, 21) . ' days')) : null;
    $type = $isTransit ? 'Transit' : 'Transfer Out';
    $sig = transfer_signature($clin);
    $tfRows[] = array_map($q, [(int)$p['p_id'], $fac['facilityname'], $fac['mfl'], $fac['countyname'], $fac['subcountyname'], $p['clientName'], $p['mat_id'], $p['sex'], $p['dob'], $p['client_phone'], $p['reg_facility'], substr($p['reg_date'], 0, 10), $date, $type, null, $fac['facilityname'], $tgt['facilityname'], $pick($reasons), $history, 'Stable on treatment; supportive family', 'Toxicology up to date; routine labs normal', 'Hepatitis B vaccine completed', 'Opioid Use Disorder (ICD-10 F11.2)', (string)$p['dosage'], $date . ' 10:00:00', 'None', $clin, $fac['facilityname'], $sig, $date, $coun, $fac['facilityname'], transfer_signature($coun), $date, json_encode(['seed' => true])]);
    $accepted = mt_rand(1, 100) <= 60;
    $payload = json_encode(['seed' => true, 'token' => bin2hex(random_bytes(8)), 'from_mfl' => $fac['mfl'], 'patient' => ['mat_id' => $p['mat_id'], 'clientName' => $p['clientName'], 'dob' => $p['dob'], 'sex' => $p['sex']], 'form' => ['type_of_movement' => $type, 'from_site' => $fac['facilityname'], 'to_site' => $tgt['facilityname'], 'referral_date' => $date, 'current_dose' => (string)$p['dosage'], 'drug' => $p['drugname']]]);
    $trRows[] = array_map($q, [bin2hex(random_bytes(16)), 'OUT', $p['mat_id'], $p['clientName'], $fac['facilityname'], $fac['mfl'], $tgt['facilityname'], $tgt['mflcode'], $tgt['email'] ?: ('clinic' . $tgt['mflcode'] . '@example.org'), $type, $tStart, $tEnd, $accepted ? 'Accepted' : 'Sent', 1, $payload, 'SEED', $date . ' 11:00:00', $accepted ? date('Y-m-d H:i:s', strtotime($date . ' 11:00:00 +' . mt_rand(1, 48) . ' hours')) : null]);
}
// transfers received from other facilities (IN) for clients enrolled as Transfer In
$ins = $conn->query("SELECT mat_id, clientName, referring_facility, reg_date, drugname, dosage FROM patients WHERE hcw_name='SEED' AND TRIM(mat_status)='Transfer In' ORDER BY RAND() LIMIT 20")->fetch_all(MYSQLI_ASSOC);
foreach ($ins as $p) {
    $from = $p['referring_facility'] ?: 'Mathare MAT Clinic'; $d = $p['reg_date'];
    $trRows[] = array_map($q, [bin2hex(random_bytes(16)), 'IN', $p['mat_id'], $p['clientName'], $from, null, $fac['facilityname'], $fac['mfl'], null, 'Transfer Out', null, null, 'Accepted', 1, json_encode(['seed' => true]), 'SEED', $d, $d]);
}
$bulk('transfer_forms', $tfCols, $tfRows);
$bulk('transfer_out_requests', $trCols, $trRows);

echo "\nCurrent balances:\n"; foreach ($finalBal as $n => $b) echo "  " . str_pad($n, 40) . number_format($b) . "\n";

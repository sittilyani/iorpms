<?php
include "../includes/session_manager.php";
requireLogin();
include "../includes/config.php";
require_once "../includes/analytics_widgets.php";
$fac = dash_facility($conn);
$m = $conn->real_escape_string($fac['mfl']);
$mat = trim((string)($_GET['mat_id'] ?? ''));

// ---------- search screen ----------
if ($mat === '') {
    $q = $conn->real_escape_string(trim((string)($_GET['q'] ?? '')));
    dash_head('Dose & Adherence History', $fac, 'Select a client');
    echo '<form style="margin-bottom:14px"><input name="q" value="' . htmlspecialchars((string)($_GET['q'] ?? '')) . '" placeholder="Name, MAT ID or national ID" style="padding:9px 12px;border:1px solid #cfc6ea;border-radius:6px;width:320px"> <button style="background:#2D008A;color:#fff;border:0;border-radius:6px;padding:9px 20px">Search</button></form>';
    $rows = $q === '' ? [] : dash_rows($conn, "SELECT mat_id, clientName, sex, current_status, drugname, dosage FROM patients WHERE mflcode='$m' AND (clientName LIKE '%$q%' OR mat_id LIKE '%$q%' OR nat_id LIKE '%$q%') ORDER BY clientName LIMIT 30");
    dash_table($q === '' ? 'Type a name above' : 'Matching clients', ['Client', 'MAT ID', 'Sex', 'Status', 'Drug / dose', ''],
        array_map(fn($r) => [htmlspecialchars((string)($r['clientName'])), htmlspecialchars((string)($r['mat_id'])), htmlspecialchars((string)($r['sex'])), htmlspecialchars((string)($r['current_status'])), htmlspecialchars((string)(trim((string)($r['drugname'])) . ' ' . $r['dosage'])), '<a href="?mat_id=' . urlencode((string)($r['mat_id'])) . '">View history</a>'], $rows));
    dash_foot(); exit;
}

$e = $conn->real_escape_string($mat);
$p = $conn->query("SELECT * FROM patients WHERE mat_id='$e'")->fetch_assoc();
if (!$p) { dash_head('Dose & Adherence History', $fac); echo '<p>Client not found.</p>'; dash_foot(); exit; }

// One row per dispensing day (a day with several rows keeps its highest dose)
$days = dash_rows($conn, "SELECT DATE(dispDate) d, TRIM(drugname) drug, MAX(dosage) dose FROM pharmacy WHERE mat_id='$e' GROUP BY DATE(dispDate), TRIM(drugname) ORDER BY d");
$ended = in_array($p['current_status'], ['Active', 'Defaulted', 'LTFU', 'Transit'], true) ? date('Y-m-d') : null;

// ---- dose periods ----
$periods = []; $cur = null;
foreach ($days as $r) {
    if ($cur && $cur['drug'] === $r['drug'] && (float)$cur['dose'] === (float)$r['dose']) { $cur['to'] = $r['d']; $cur['dispensed']++; }
    else { if ($cur) $periods[] = $cur; $cur = ['drug' => $r['drug'], 'dose' => $r['dose'], 'from' => $r['d'], 'to' => $r['d'], 'dispensed' => 1]; }
}
if ($cur) $periods[] = $cur;

// ---- gaps / defaulting episodes (5+ consecutive missed days) ----
$gaps = []; $missedTotal = 0;
for ($i = 1; $i < count($days); $i++) {
    $miss = (int)((strtotime((string)($days[$i]['d'])) - strtotime((string)($days[$i - 1]['d']))) / 86400) - 1;
    if ($miss > 0) { $missedTotal += $miss; if ($miss >= 5) $gaps[] = ['from' => date('Y-m-d', strtotime((string)($days[$i - 1]['d'] . ' +1 day'))), 'to' => date('Y-m-d', strtotime((string)($days[$i]['d'] . ' -1 day'))), 'n' => $miss]; }
}
$first = $days[0]['d'] ?? null; $last = $days ? end($days)['d'] : null;
$tailMissed = 0;
if ($last && $ended && $last < $ended) { $tailMissed = (int)((strtotime((string)($ended)) - strtotime((string)($last))) / 86400) - 1; if ($tailMissed >= 5) $gaps[] = ['from' => date('Y-m-d', strtotime((string)("$last +1 day"))), 'to' => date('Y-m-d', strtotime((string)("$ended -1 day"))), 'n' => $tailMissed, 'open' => true]; }
$span = $first ? (int)((strtotime((string)($ended ?: $last)) - strtotime((string)($first))) / 86400) + 1 : 0;
$dispDays = count($days);
$adh = $span ? round($dispDays / $span * 100, 1) : 0;

// ---- monthly adherence ----
$byMonth = [];
foreach ($days as $r) { $k = substr($r['d'], 0, 7); $byMonth[$k] = ($byMonth[$k] ?? 0) + 1; }
$monthRows = [];
foreach ($byMonth as $k => $n) {
    $mStart = max(strtotime((string)($k . '-01')), strtotime((string)($first))); $mEnd = min(strtotime((string)(date('Y-m-t', strtotime((string)($k . '-01'))))), strtotime((string)($ended ?: $last)));
    $dim = max(1, (int)(($mEnd - $mStart) / 86400) + 1);
    $monthRows[] = [date('M y', strtotime((string)($k . '-01'))), min(100, round($n / $dim * 100))];
}
$monthRows = array_slice($monthRows, -24);

dash_head('Dose & Adherence History', $fac, $p['clientName'] . ' (' . $p['mat_id'] . ')');
echo '<p><a href="dose_history.php" style="color:#2D008A">&larr; Search another client</a></p>';
$adhColor = $adh >= 90 ? DASH_GREEN : ($adh >= 75 ? DASH_YELLOW : DASH_RED);
dash_kpis([
    ['Current status', $p['current_status'], trim((string)($p['drugname'])) . ' ' . $p['dosage'] . ' mg now', DASH_PRIMARY],
    ['Cumulative adherence', $adh . '%', $dispDays . ' of ' . $span . ' days dispensed', $adhColor],
    ['Days dispensed', number_format($dispDays), $first ? "$first to $last" : 'No dispensing yet', DASH_GREEN],
    ['Missed days', number_format($missedTotal + max(0, $tailMissed)), '', DASH_YELLOW],
    ['Defaulting episodes', count($gaps), '5+ days missed in a row', DASH_RED],
    ['Dose changes', max(0, count($periods) - 1), count($periods) . ' dose periods', DASH_LILAC],
]);
if (!$days) { echo '<div class="card"><div class="b">This client has no dispensing history yet, so they cannot be counted as Active.</div></div>'; dash_foot(); exit; }

echo '<div class="grid">';
dash_card_open('Dose periods — how the client has taken doses');
echo '<table><tr><th>From</th><th>To</th><th>Drug</th><th>Dose</th><th>Duration</th><th>Dispensed</th></tr>';
foreach ($periods as $pr) {
    $dur = (int)((strtotime((string)($pr['to'])) - strtotime((string)($pr['from']))) / 86400) + 1;
    echo '<tr><td>' . $pr['from'] . '</td><td>' . $pr['to'] . '</td><td>' . htmlspecialchars((string)($pr['drug'])) . '</td><td><b>' . (float)$pr['dose'] . ' mg</b></td><td>' . $dur . ' days</td><td>' . $pr['dispensed'] . ' days</td></tr>';
}
echo '</table>';
dash_card_close();

dash_card_open('Defaulting episodes — click to see the exact dates');
if (!$gaps) echo '<div class="empty">No gaps of 5 or more days. Excellent adherence.</div>';
foreach (array_reverse($gaps) as $g) {
    echo '<details style="margin:6px 0;border:1px solid #eee;border-radius:6px;padding:6px 10px"><summary style="cursor:pointer;font-size:13px"><b>' . $g['from'] . '</b> to <b>' . $g['to'] . '</b> — <span class="pill" style="background:#E41E39">' . $g['n'] . ' days missed</span>' . (!empty($g['open']) ? ' <i>(ongoing)</i>' : '') . '</summary><div style="font-size:12px;color:#555;padding:8px 0;line-height:1.7">';
    $dates = []; for ($t = strtotime((string)($g['from'])); $t <= strtotime((string)($g['to'])); $t += 86400) $dates[] = date('D j M Y', $t);
    echo implode(' &middot; ', $dates) . '</div></details>';
}
dash_card_close();
dash_bars('Monthly adherence % (last 24 months with dispensing)', $monthRows, DASH_PRIMARY);
dash_line('Dose over time (mg)', array_map(fn($r) => [date('M y', strtotime((string)($r['from']))), (float)$r['dose']], $periods), DASH_LILAC);
echo '</div>';
dash_foot();

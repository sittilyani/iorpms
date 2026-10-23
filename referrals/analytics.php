<?php
/**
 * Referrals dashboard: how many referrals, who raised them, where they go, and above all how long they wait before being acted on.
 * Response time = acted_at - referral_date (acted_at is stamped when the status is first changed on the referral list).
 */
include "../includes/session_manager.php";
requireLogin();
include "../includes/config.php";
require_once "../includes/analytics_widgets.php";
require_once "../includes/clinic_helpers.php";
$fac = dash_facility($conn);
$esc = fn($v) => $conn->real_escape_string((string)$v);

$days = $_GET['days'] ?? '365';
if (!in_array($days, ['30', '90', '180', '365', 'all'], true)) $days = '365';
$W = $days === 'all' ? '1=1' : "r.referral_date >= DATE_SUB(CURDATE(), INTERVAL " . (int)$days . " DAY)";
$label = ['30' => 'last 30 days', '90' => 'last 90 days', '180' => 'last 6 months', '365' => 'last 12 months', 'all' => 'all time'][$days];

// one pass over the referrals in the period; every statistic below is derived from this list
$rows = dash_rows($conn, "SELECT r.referral_id, r.mat_id, r.clientName, r.refer_to, r.referral_name, r.referral_date, r.status, r.triage_priority prio, r.chief_complaint, r.outcome, r.acted_at, r.acted_by,
    COALESCE(NULLIF(r.from_department,''), 'Other') fromd, COALESCE(NULLIF(r.to_department,''), 'Other') tod,
    TIMESTAMPDIFF(MINUTE, r.referral_date, r.acted_at) / 60 hrs, TIMESTAMPDIFF(MINUTE, r.referral_date, NOW()) / 60 age_h
    FROM referral r WHERE $W ORDER BY r.referral_date DESC");
$median = function (array $a) { if (!$a) return null; sort($a); $n = count($a); return $n % 2 ? $a[intdiv($n, 2)] : ($a[$n / 2 - 1] + $a[$n / 2]) / 2; };
$fmtH = function ($h) { if ($h === null) return '-'; if ($h < 1) return round($h * 60) . ' min'; if ($h < 48) return round($h, 1) . ' h'; return round($h / 24, 1) . ' days'; };
$isOpen = fn($r) => strtolower((string)$r['status']) !== 'completed';

$total = count($rows); $open = array_values(array_filter($rows, $isOpen)); $done = array_values(array_filter($rows, fn($r) => !$isOpen($r) && $r['hrs'] !== null));
$hours = array_map(fn($r) => (float)$r['hrs'], $done);
$avg = $hours ? array_sum($hours) / count($hours) : null; $med = $median($hours);
$within24 = $hours ? round(count(array_filter($hours, fn($h) => $h <= 24)) / count($hours) * 100) : null;
$oldest = $open ? max(array_map(fn($r) => (float)$r['age_h'], $open)) : 0;
$overdue = count(array_filter($open, fn($r) => $r['age_h'] > 72));
$highOpen = count(array_filter($open, fn($r) => strtolower((string)$r['prio']) === 'high'));

dash_head('Referrals Dashboard', $fac, 'Referral volume and response times - ' . $label);
echo '<style>.span2{grid-column:1/-1}.filters{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:14px}.fb{border-radius:6px;padding:8px 16px;font-weight:600;font-size:13px;text-decoration:none;background:#fff;color:#2D008A;border:1px solid #cfc6ea}.fb.on{background:#2D008A;color:#fff}.fb.g{background:#04B04B;color:#fff;border-color:#04B04B}.rk{display:flex;align-items:center;gap:8px;font-size:12.5px;margin:5px 0}.rk .n{width:34%;text-align:right}.rk .f{height:13px;border-radius:3px;background:#2D008A}.rk .m{color:#777;min-width:150px}</style>';
echo '<div class="filters">';
foreach (['30' => '30 days', '90' => '90 days', '180' => '6 months', '365' => '12 months', 'all' => 'All time'] as $k => $l) echo '<a class="fb' . ($days === (string)$k ? ' on' : '') . '" href="?days=' . $k . '">' . $l . '</a>';
echo '<a class="fb g" style="margin-left:auto" href="referral_dashboard.php">Open referral list &amp; act on referrals</a></div>';
dash_kpis([
    ['Referrals', number_format($total), $label, DASH_PRIMARY],
    ['Waiting for action', count($open), $highOpen . ' high priority', count($open) ? DASH_YELLOW : DASH_GREEN],
    ['Overdue (over 3 days)', $overdue, 'oldest waiting ' . $fmtH($oldest ?: null), $overdue ? DASH_RED : DASH_GREEN],
    ['Typical time to act', $fmtH($med), 'median, ' . count($done) . ' completed referrals', DASH_PRIMARY],
    ['Average time to act', $fmtH($avg), 'mean (pulled up by slow referrals)', DASH_LILAC],
    ['Acted on within 24 hours', $within24 === null ? '-' : $within24 . '%', 'of completed referrals', $within24 !== null && $within24 >= 70 ? DASH_GREEN : DASH_YELLOW],
]);

echo '<div class="grid">';
// ---- trend: referred / completed / still waiting, per month ----
$months = []; for ($i = 11; $i >= 0; $i--) $months[] = date('Y-m', strtotime("first day of -$i month"));
$tr = ['Referred' => array_fill_keys($months, 0), 'Completed' => array_fill_keys($months, 0), 'Still waiting' => array_fill_keys($months, 0)];
foreach (dash_rows($conn, "SELECT DATE_FORMAT(r.referral_date,'%Y-%m') k, COUNT(*) n, SUM(LOWER(r.status) = 'completed') c FROM referral r WHERE r.referral_date >= DATE_SUB(CURDATE(), INTERVAL 13 MONTH) GROUP BY k") as $x) {
    if (isset($tr['Referred'][$x['k']])) { $tr['Referred'][$x['k']] = (int)$x['n']; $tr['Completed'][$x['k']] = (int)$x['c']; $tr['Still waiting'][$x['k']] = (int)$x['n'] - (int)$x['c']; }
}
echo '<div class="span2">';
dash_multiline('Referrals per month - raised, completed and still waiting (last 12 months)', array_map(fn($m) => date('M y', strtotime($m . '-01')), $months), array_map('array_values', $tr), ['Referred' => DASH_PRIMARY, 'Completed' => DASH_GREEN, 'Still waiting' => DASH_RED]);
echo '</div>';

// ---- time-to-act distribution ----
$b = ['Under 1 hour' => 0, '1-6 hours' => 0, '6-24 hours' => 0, '1-3 days' => 0, '3-7 days' => 0, 'Over 7 days' => 0];
foreach ($hours as $h) { if ($h < 1) $b['Under 1 hour']++; elseif ($h < 6) $b['1-6 hours']++; elseif ($h < 24) $b['6-24 hours']++; elseif ($h < 72) $b['1-3 days']++; elseif ($h < 168) $b['3-7 days']++; else $b['Over 7 days']++; }
dash_vbars('How long referrals took to be acted on', array_map(null, array_keys($b), array_values($b)), ['Under 1 hour' => DASH_GREEN, '1-6 hours' => DASH_GREEN, '6-24 hours' => '#7BD9A3', '1-3 days' => DASH_YELLOW, '3-7 days' => '#F17A8A', 'Over 7 days' => DASH_RED]);

// ---- helper: group rows into [label => [count, completed, hours[]]] ----
$group = function (callable $key, ?array $src = null) use ($rows, $isOpen) {
    $g = []; foreach ($src ?? $rows as $r) { $k = $key($r); if ($k === null || $k === '') $k = 'Not recorded'; $g[$k]['n'] = ($g[$k]['n'] ?? 0) + 1; if (!$isOpen($r)) { $g[$k]['c'] = ($g[$k]['c'] ?? 0) + 1; if ($r['hrs'] !== null) $g[$k]['h'][] = (float)$r['hrs']; } else { $g[$k]['o'] = ($g[$k]['o'] ?? 0) + 1; } }
    uasort($g, fn($a, $c) => $c['n'] <=> $a['n']); return $g;
};
$rankCard = function (string $title, array $g, int $limit = 10) use ($median, $fmtH) {
    dash_card_open($title);
    if (!$g) { echo '<div class="empty">No referrals.</div>'; dash_card_close(); return; }
    $mx = max(array_map(fn($x) => $x['n'], $g));
    foreach (array_slice($g, 0, $limit, true) as $name => $x) {
        $m = $median($x['h'] ?? []);
        echo '<div class="rk"><div class="n" title="' . htmlspecialchars((string)$name) . '">' . htmlspecialchars(mb_strimwidth((string)$name, 0, 34, '...')) . '</div><div style="flex:1"><div class="f" style="width:' . round($x['n'] / $mx * 100) . '%"></div></div><b>' . $x['n'] . '</b><span class="m">median ' . $fmtH($m) . (!empty($x['o']) ? ' &middot; <span style="color:#E41E39">' . $x['o'] . ' waiting</span>' : '') . '</span></div>';
    }
    dash_card_close();
};
$rankCard('Referrals by destination service (with median time to act)', $group(fn($r) => $r['tod']));
$rankCard('Referrals by sending department', $group(fn($r) => $r['fromd']));
$rankCard('Referrals by staff member who raised them', $group(fn($r) => $r['referral_name']));
$acted = array_values(array_filter($rows, fn($r) => !$isOpen($r)));
$rankCard('Who acted on referrals (completed by)', $group(fn($r) => $r['acted_by'], $acted));
$rankCard('Top reasons for referral', $group(fn($r) => $r['chief_complaint']), 8);

// ---- priority ----
$pg = $group(fn($r) => ucfirst(strtolower((string)$r['prio'])));
dash_card_open('By priority - and how fast each was acted on');
echo '<table><tr><th>Priority</th><th>Referrals</th><th>Waiting</th><th>Median time to act</th><th>Within 24h</th></tr>';
foreach (['High', 'Medium', 'Normal'] as $p) { if (!isset($pg[$p])) continue; $x = $pg[$p]; $h = $x['h'] ?? [];
    echo '<tr><td><span class="pill" style="background:' . ($p === 'High' ? '#E41E39' : ($p === 'Medium' ? '#FFC12E;color:#4a3500' : '#04B04B')) . '">' . $p . '</span></td><td>' . $x['n'] . '</td><td>' . ($x['o'] ?? 0) . '</td><td>' . $fmtH($median($h)) . '</td><td>' . ($h ? round(count(array_filter($h, fn($v) => $v <= 24)) / count($h) * 100) . '%' : '-') . '</td></tr>'; }
echo '</table>';
dash_card_close();
// ---- internal vs external ----
$internal = ['Clinical', 'Psychosocial', 'Laboratory', 'Nursing', 'Pharmacy'];
$ig = $group(fn($r) => in_array($r['tod'], $internal, true) ? 'Within the clinic' : 'Outside the clinic');
dash_card_open('Within the clinic vs outside the clinic');
echo '<table><tr><th>Destination</th><th>Referrals</th><th>Waiting</th><th>Median time to act</th></tr>';
foreach ($ig as $k => $x) echo '<tr><td>' . $k . '</td><td>' . $x['n'] . '</td><td>' . ($x['o'] ?? 0) . '</td><td>' . $fmtH($median($x['h'] ?? [])) . '</td></tr>';
echo '</table>';
dash_card_close();
$oc = []; foreach ($acted as $r) { $k = $r['outcome'] ?: 'Not recorded'; $oc[$k] = ($oc[$k] ?? 0) + 1; } arsort($oc);
dash_donut('Outcome of completed referrals', array_map(null, array_keys($oc), array_values($oc)));

// ---- when referrals are raised ----
$wd = array_fill_keys(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], 0); $hr = [];
foreach ($rows as $r) { $t = strtotime($r['referral_date']); $wd[date('D', $t)]++; $hr[(int)date('G', $t)] = ($hr[(int)date('G', $t)] ?? 0) + 1; }
dash_bars('Referrals raised by weekday', array_map(null, array_keys($wd), array_values($wd)), DASH_LILAC);
$hb = []; for ($h = 7; $h <= 18; $h++) $hb[] = [sprintf('%02d:00', $h), $hr[$h] ?? 0];
dash_bars('Referrals raised by hour of day', $hb, DASH_PRIMARY);

// ---- flows ----
$flows = []; foreach ($rows as $r) { $k = $r['fromd'] . '  ->  ' . $r['tod']; $flows[$k] = ($flows[$k] ?? 0) + 1; } arsort($flows);
dash_bars('Busiest referral routes (from -> to)', array_map(null, array_keys(array_slice($flows, 0, 8, true)), array_values(array_slice($flows, 0, 8, true))), 'multi');

// ---- the waiting queue ----
dash_card_open('Waiting for action - oldest first (' . count($open) . ')');
if (!$open) { echo '<div class="empty">Nothing is waiting. Every referral has been acted on.</div>'; }
else {
    usort($open, fn($a, $c) => $c['age_h'] <=> $a['age_h']);
    echo '<table><tr><th>Waiting</th><th>Client</th><th>Priority</th><th>Going to</th><th>Raised by</th><th>Date</th><th></th></tr>';
    foreach (array_slice($open, 0, 12) as $r) {
        $col = $r['age_h'] > 72 ? '#E41E39' : ($r['age_h'] > 24 ? '#FFC12E;color:#4a3500' : '#04B04B');
        echo '<tr><td><span class="pill" style="background:' . $col . '">' . $fmtH((float)$r['age_h']) . '</span></td><td>' . htmlspecialchars((string)$r['clientName']) . '<br><small style="color:#888">' . htmlspecialchars((string)$r['mat_id']) . '</small></td><td>' . htmlspecialchars(ucfirst((string)$r['prio'])) . '</td><td>' . htmlspecialchars((string)$r['refer_to']) . '</td><td>' . htmlspecialchars((string)$r['referral_name']) . '</td><td>' . substr($r['referral_date'], 0, 10) . '</td><td><a href="edit_referral.php?mat_id=' . urlencode((string)$r['mat_id']) . '">Act</a></td></tr>';
    }
    echo '</table>';
}
dash_card_close();
$slow = $done; usort($slow, fn($a, $c) => $c['hrs'] <=> $a['hrs']);
dash_table('Slowest completed referrals', ['Took', 'Client', 'Going to', 'Raised by', 'Acted by'],
    array_map(fn($r) => ['<span class="pill" style="background:#E41E39">' . $fmtH((float)$r['hrs']) . '</span>', htmlspecialchars((string)$r['clientName']), htmlspecialchars((string)$r['refer_to']), htmlspecialchars((string)$r['referral_name']), htmlspecialchars((string)$r['acted_by'])], array_slice($slow, 0, 8)));
echo '</div>';
dash_foot();

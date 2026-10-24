<?php
/**
 * Duty rota by department. Everyone logged in can read a final rota; Admin, Super Admin, HRIO and Data Manager can generate,
 * edit the draft and make it final. Clinic hours 06:00 - 12:00.
 */
include "../includes/session_manager.php";
requireLogin();
include "../includes/config.php";
require_once "../includes/analytics_widgets.php";
require_once "../includes/rota_helpers.php";
rota_ensure($conn);

$fac = dash_facility($conn);
$me = (int)getUserId(); $who = getUserFullName();
$canEdit = in_array(strtolower((string)($_SESSION['userrole'] ?? '')), ['admin', 'super admin', 'hrio', 'data manager'], true);
$monday = rota_monday((string)($_GET['week'] ?? $_POST['week'] ?? date('Y-m-d')));
$days = rota_days($monday);
$msg = ''; $notes = [];

// ---- actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canEdit) {
    $act = (string)($_POST['act'] ?? '');
    $wk = dash_rows($conn, "SELECT status FROM rota_weeks WHERE week_start = '$monday'")[0]['status'] ?? null;
    if ($act === 'generate' && $wk !== 'final') { $notes = rota_generate($conn, $monday, (int)($_POST['cover'] ?? 1), $who); $msg = 'Draft rota generated. Edit it below, then make it final.'; }
    elseif ($act === 'save' && $wk === 'draft') { rota_save($conn, $monday, (array)($_POST['s'] ?? [])); $msg = 'Changes saved to the draft.'; }
    elseif ($act === 'final' && $wk === 'draft') {
        if (isset($_POST['s'])) { rota_save($conn, $monday, (array)$_POST['s']); }
        $b = $conn->real_escape_string($who); $conn->query("UPDATE rota_weeks SET status = 'final', finalized_by = '$b', finalized_at = NOW() WHERE week_start = '$monday'"); $msg = 'Rota is final and visible to all staff.';
    }
    elseif ($act === 'reopen' && $wk === 'final') { $conn->query("UPDATE rota_weeks SET status = 'draft', finalized_by = NULL, finalized_at = NULL WHERE week_start = '$monday'"); $msg = 'Rota reopened as a draft.'; }
}

$week = dash_rows($conn, "SELECT * FROM rota_weeks WHERE week_start = '$monday'")[0] ?? null;
$status = $week['status'] ?? 'none';
$visible = $status === 'final' || ($canEdit && $status !== 'none');
$editing = $canEdit && $status === 'draft';
$depts = dash_rows($conn, "SELECT id, department_name, works_weekends FROM departments ORDER BY id");
$staff = rota_staff($conn);
$on = [];   // user|date => true
foreach (dash_rows($conn, "SELECT user_id, shift_date FROM rota_shifts WHERE week_start = '$monday'") as $r) { $on[$r['user_id'] . '|' . $r['shift_date']] = true; }
$palette = ['#2D008A', '#04B04B', '#6B3FD1', '#E0A100', '#AC80EE', '#0B8F6B', '#8B2FC9'];
$h = fn($v) => htmlspecialchars((string)$v);
$prevW = date('Y-m-d', strtotime("$monday -7 days")); $nextW = date('Y-m-d', strtotime("$monday +7 days"));
$today = date('Y-m-d');
$myDays = array_values(array_filter($days, fn($d) => isset($on[$me . '|' . $d])));
$weekLabel = date('j M', strtotime($monday)) . ' - ' . date('j M Y', strtotime($days[6]));

echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Duty Rota</title><link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.css"><style>
:root{--pri:#2D008A;--ok:#04B04B;--warn:#FFC12E;--bg:#FDFCF9}*{box-sizing:border-box}body{margin:0;background:var(--bg);font-family:Segoe UI,Arial,sans-serif;color:#222}
.top{background:linear-gradient(135deg,#2D008A,#4a14b8);color:#fff;padding:18px 28px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px}.top h1{margin:0;font-size:21px}.top small{opacity:.85}
.top a.back{color:#fff;text-decoration:none;border:1px solid rgba(255,255,255,.6);border-radius:6px;padding:6px 14px;font-size:13px}
.wrap{padding:18px 24px;max-width:1400px;margin:auto}.bar{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:14px}
.btn{background:var(--pri);color:#fff;border:0;border-radius:7px;padding:8px 18px;text-decoration:none;font-size:13px;cursor:pointer;font-weight:600}.btn.g{background:var(--ok)}.btn.y{background:var(--warn);color:#3a2a00}.btn.o{background:#fff;color:var(--pri);border:1px solid var(--pri)}
.week{font-size:17px;font-weight:700;color:var(--pri);margin:0 6px}.badge{padding:3px 12px;border-radius:999px;font-size:12px;font-weight:700;color:#fff}.b-draft{background:#b8860b}.b-final{background:var(--ok)}.b-none{background:#999}
.info{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:14px}.info div{background:#fff;border:1px solid #e5e0f2;border-left:5px solid var(--pri);border-radius:8px;padding:9px 14px;font-size:13px}
.msg{background:#e6f8ee;border-left:5px solid var(--ok);border-radius:8px;padding:10px 14px;margin-bottom:12px}.note{background:#fff8e1;border-left:5px solid var(--warn);border-radius:8px;padding:8px 14px;margin-bottom:8px;font-size:13px}
.dept{background:#fff;border:1px solid #e5e0f2;border-radius:12px;margin-bottom:18px;overflow:hidden;box-shadow:0 2px 8px rgba(45,0,138,.07)}
.dept h2{margin:0;padding:10px 16px;color:#fff;font-size:15px;display:flex;justify-content:space-between;align-items:center}.dept h2 small{font-weight:400;opacity:.9}
table{border-collapse:collapse;width:100%;font-size:13px}th{background:#f3effb;color:var(--pri);padding:8px 6px;text-align:center;border-bottom:2px solid #d9cdf5}th:first-child,td:first-child{text-align:left;padding-left:14px;min-width:170px}
th.we{background:#ece4fb}th.today{background:var(--warn);color:#3a2a00}td{padding:6px;border-bottom:1px solid #eee;text-align:center}tr.me td:first-child{font-weight:700;color:var(--pri)}tr.me{background:#fbf9ff}
.chip{display:inline-block;padding:4px 8px;border-radius:6px;background:#e6f8ee;color:#04703a;font-weight:600;font-size:12px;min-width:92px}.chip.we{background:#ece4fb;color:var(--pri)}.off{color:#bbb}
label.cell{display:block;cursor:pointer}label.cell input{display:none}label.cell span{display:inline-block;padding:4px 8px;border-radius:6px;min-width:92px;font-size:12px;border:1px dashed #cfc6ea;color:#999}label.cell input:checked+span{background:#e6f8ee;color:#04703a;font-weight:600;border:1px solid #04B04B}label.cell.we input:checked+span{background:#ece4fb;color:var(--pri);border-color:var(--pri)}
tfoot td{background:#faf8ff;font-weight:700;color:var(--pri)}.empty{padding:40px;text-align:center;color:#777;background:#fff;border:1px dashed #cfc6ea;border-radius:12px}
@media print{.top,.bar,.noprint{display:none!important}.wrap{padding:0}.dept{break-inside:avoid;box-shadow:none}}
@media(max-width:700px){.wrap{padding:10px}table{font-size:11px}.chip,label.cell span{min-width:0;padding:3px 4px}}
</style></head><body>';
echo '<div class="top"><div><h1><i class="fa fa-calendar"></i> Duty Rota</h1><small>' . $h($fac['facilityname']) . ' &middot; MFL ' . $h($fac['mfl']) . ' &middot; opening hours 6:00 am - 12:00 noon</small></div><a class="back noprint" id="backLink" href="../dashboard/dashboard.php"><i class="fa fa-home"></i> Back to dashboard</a></div>';
echo '<script>if(window.self!==window.top){document.getElementById("backLink").style.display="none";}</script><div class="wrap">';

echo '<div class="bar"><a class="btn o" href="?week=' . $prevW . '">&laquo; Previous</a><span class="week">Week of ' . $h($weekLabel) . '</span><a class="btn o" href="?week=' . $nextW . '">Next &raquo;</a><a class="btn o" href="?week=' . date('Y-m-d') . '">This week</a>'
   . '<span class="badge b-' . $status . '">' . ($status === 'none' ? 'NOT GENERATED' : strtoupper($status)) . '</span>'
   . ($visible ? '<a class="btn o noprint" href="#" onclick="window.print();return false"><i class="fa fa-print"></i> Print</a>' : '') . '</div>';
if ($msg) echo '<div class="msg">' . $h($msg) . '</div>';
foreach ($notes as $n) echo '<div class="note">' . $h($n) . '</div>';

echo '<div class="info"><div><b>Hours:</b> 6:00 am - 12:00 noon</div><div><b>Pharmacy:</b> all week (Mon - Sun)</div><div><b>Other departments:</b> Monday to Friday</div><div><b>Weekend rule:</b> anyone who works a weekend works only two days the following week</div>'
   . ($myDays && $visible ? '<div style="border-left-color:var(--ok)"><b>Your shifts:</b> ' . $h(implode(', ', array_map(fn($d) => date('D j M', strtotime($d)), $myDays))) . '</div>' : '') . '</div>';

if ($canEdit && $status !== 'final') {
    echo '<form method="post" class="bar noprint" onsubmit="return confirm(\'' . ($status === 'none' ? 'Generate the draft rota for this week?' : 'Generating again replaces the draft for this week, including your edits. Continue?') . '\')"><input type="hidden" name="week" value="' . $monday . '"><input type="hidden" name="act" value="generate">'
       . '<label style="font-size:13px">Weekend cover per department <select name="cover" style="padding:6px;border:1px solid #cfc6ea;border-radius:6px">' . implode('', array_map(fn($i) => '<option' . ((int)($week['weekend_cover'] ?? 1) === $i ? ' selected' : '') . '>' . $i . '</option>', [1, 2, 3])) . '</select></label>'
       . '<button class="btn g"><i class="fa fa-magic"></i> ' . ($status === 'none' ? 'Generate rota' : 'Regenerate draft') . '</button></form>';
}
if (!$visible) {
    echo '<div class="empty"><i class="fa fa-calendar-o" style="font-size:34px;color:#AC80EE"></i><p>' . ($status === 'draft' ? 'The rota for this week has not been published yet.' : 'No rota has been made for this week.') . '</p></div></div></body></html>'; exit;
}

if ($editing) echo '<form method="post" id="rotaForm"><input type="hidden" name="week" value="' . $monday . '">';
foreach ($depts as $i => $d) {
    $team = array_filter($staff, fn($s) => $s['dept'] === (int)$d['id']);
    $col = $palette[$i % count($palette)];
    echo '<div class="dept"><h2 style="background:' . $col . '">' . $h($d['department_name']) . '<small>' . count($team) . ' staff &middot; ' . ($d['works_weekends'] ? 'Mon - Sun' : 'Mon - Fri') . '</small></h2>';
    if (!$team) { echo '<p style="padding:14px;color:#888">No staff assigned to this department yet (set it on the user\'s profile).</p></div>'; continue; }
    echo '<table><thead><tr><th>Staff</th>';
    foreach ($days as $k => $day) { echo '<th class="' . ($k >= 5 ? 'we ' : '') . ($day === $today ? 'today' : '') . '">' . date('D', strtotime($day)) . '<br><small>' . date('j M', strtotime($day)) . '</small></th>'; }
    echo '<th>Days</th></tr></thead><tbody>';
    $tot = array_fill(0, 7, 0);
    foreach ($team as $uid => $s) {
        $n = 0; echo '<tr class="' . ($uid === $me ? 'me' : '') . '"><td>' . $h($s['name']) . '</td>';
        foreach ($days as $k => $day) {
            $w = isset($on[$uid . '|' . $day]); if ($w) { $n++; $tot[$k]++; }
            $we = $k >= 5;
            if ($editing) { echo '<td><label class="cell ' . ($we ? 'we' : '') . '"><input type="checkbox" name="s[' . $uid . '][' . $day . ']" value="1"' . ($w ? ' checked' : '') . '><span>6:00 - 12:00</span></label></td>'; }
            else { echo '<td>' . ($w ? '<span class="chip ' . ($we ? 'we' : '') . '">6:00 - 12:00</span>' : '<span class="off">&ndash;</span>') . '</td>'; }
        }
        echo '<td><b>' . $n . '</b></td></tr>';
    }
    echo '</tbody><tfoot><tr><td>On duty</td>' . implode('', array_map(fn($t) => '<td>' . $t . '</td>', $tot)) . '<td></td></tr></tfoot></table></div>';
}
if ($editing) {
    echo '<div class="bar noprint"><button class="btn" name="act" value="save"><i class="fa fa-save"></i> Save changes</button><button class="btn g" name="act" value="final" onclick="return confirm(\'Make this rota final? Staff will see it.\')"><i class="fa fa-check"></i> Make final</button><span style="font-size:12px;color:#666">Click a cell to switch a shift on or off.</span></div></form>';
} elseif ($canEdit && $status === 'final') {
    echo '<form method="post" class="bar noprint"><input type="hidden" name="week" value="' . $monday . '"><button class="btn y" name="act" value="reopen" onclick="return confirm(\'Reopen this rota for editing?\')"><i class="fa fa-unlock"></i> Reopen as draft</button><span style="font-size:12px;color:#666">Finalised by ' . $h($week['finalized_by'] ?? '') . ' on ' . $h(date('j M Y H:i', strtotime((string)($week['finalized_at'] ?? 'now')))) . '</span></form>';
}
echo '</div></body></html>';

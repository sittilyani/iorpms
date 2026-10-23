<?php
include "../includes/session_manager.php";
requireLogin();
include "../includes/config.php";
require_once "../includes/analytics_widgets.php";
$fac = dash_facility($conn);
$m = $conn->real_escape_string($fac['mfl']);

// Treatment progress and outcomes as tabs: each tab opens the existing list page inside this frame.
$tabs = [
    'active'    => ['Active',                'view_active_patients.php',    ['Active']],
    'defaulted' => ['Defaulters',            'view_defaulted_patients.php', ['Defaulted']],
    'ltfu'      => ['LTFU',                  'view_ltfu_patients.php',      ['LTFU']],
    'weaned'    => ['Weaned off',            'view_weaned_patients.php',    ['Weaned']],
    'transin'   => ['Transfer in',           'view_transin_patients.php',   null],
    'transout'  => ['Transfer out',          'view_transout_patients.php',  ['Transout']],
    'transit'   => ['Transit',               'view_transit_patients.php',   ['Transit', 'Transit-End']],
    'voluntary' => ['Voluntary disc.',       'view_voluntary_patients.php', ['Voluntary Discontinuation']],
    'stopped'   => ['Stopped',               'view_stopped_patients.php',   ['Stopped']],
    'inmates'   => ['Inmates',               'view_inmates_patients.php',   'inmate'],
    'dead'      => ['Died',                  'view_dead_patients.php',      ['Dead']],
    'mortality' => ['Mortality causes',      'mortality_chart.php',         false],
];
$total = max(1, (int)dash_val($conn, "SELECT COUNT(*) FROM patients p WHERE p.mflcode='$m'"));
$counts = [];
foreach ($tabs as $k => [$label, $page, $statuses]) {
    if ($statuses === false) { $counts[$k] = null; continue; }
    if ($statuses === null) { $counts[$k] = (int)dash_val($conn, "SELECT COUNT(*) FROM patients p WHERE p.mflcode='$m' AND TRIM(p.mat_status)='Transfer In'"); continue; }
    if ($statuses === 'inmate') { $counts[$k] = (int)dash_val($conn, "SELECT COUNT(*) FROM patients p WHERE p.mflcode='$m' AND p.p_address='inmate'"); continue; }
    $in = "'" . implode("','", array_map([$conn, 'real_escape_string'], $statuses)) . "'";
    $counts[$k] = (int)dash_val($conn, "SELECT COUNT(*) FROM patients p WHERE p.mflcode='$m' AND p.current_status IN ($in)");
}
$first = array_key_exists($_GET['tab'] ?? '', $tabs) ? $_GET['tab'] : 'active';
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Treatment Progress and Outcomes</title>
<style>
:root{--pri:#2D008A;--bg:#FDFCF9;--ok:#04B04B;--warn:#FFC12E;--bad:#E41E39;--soft:#AC80EE}
*{box-sizing:border-box}html,body{height:100%}body{margin:0;font-family:Segoe UI,Arial,sans-serif;background:var(--bg);display:flex;flex-direction:column}
.top{background:var(--pri);color:#fff;padding:14px 24px}.top h1{margin:0;font-size:19px}.top small{opacity:.8}
.tabs{display:flex;gap:4px;flex-wrap:wrap;padding:10px 16px 0;background:#f3effb;border-bottom:3px solid var(--pri)}
.tab{border:0;background:#fff;color:var(--pri);padding:9px 14px;border-radius:8px 8px 0 0;font-weight:600;font-size:13px;cursor:pointer;border:1px solid #e5e0f2;border-bottom:0}
.tab .n{display:inline-block;margin-left:6px;background:var(--soft);color:#fff;border-radius:10px;padding:0 8px;font-size:11px}
.tab.on{background:var(--pri);color:#fff}.tab.on .n{background:var(--warn);color:#000}
.tab.bad .n{background:var(--bad)}.tab.good .n{background:var(--ok)}
iframe{flex:1;width:100%;border:0;min-height:600px;background:#fff}
</style></head><body>
<div class="top"><h1><i></i>Treatment Progress &amp; Outcomes</h1><small><?= htmlspecialchars((string)($fac['facilityname'])) ?> &middot; counts show the share of all <?= number_format($total) ?> clients ever enrolled</small></div>
<div class="tabs" id="tabs">
<?php foreach ($tabs as $k => [$label, $page, $st]):
    $cls = in_array($k, ['ltfu', 'dead', 'stopped', 'mortality']) ? 'bad' : (in_array($k, ['active', 'weaned']) ? 'good' : ''); ?>
    <button class="tab <?= $cls ?><?= $k === $first ? ' on' : '' ?>" data-page="<?= htmlspecialchars((string)($page)) ?>" data-key="<?= $k ?>"><?= htmlspecialchars((string)($label)) ?><?php if ($counts[$k] !== null): ?><span class="n"><?= number_format($counts[$k]) ?> (<?= round($counts[$k] / $total * 100, 1) ?>%)</span><?php endif; ?></button>
<?php endforeach; ?>
</div>
<iframe id="frame" src="<?= htmlspecialchars((string)($tabs[$first][1])) ?>"></iframe>
<script>
document.querySelectorAll('.tab').forEach(function (t) {
    t.onclick = function () {
        document.querySelectorAll('.tab').forEach(function (x) { x.classList.remove('on'); });
        t.classList.add('on'); document.getElementById('frame').src = t.dataset.page;
    };
});
</script></body></html>

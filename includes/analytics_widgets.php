<?php
/**
 * Dependency-free (works offline in clinics) dashboard widgets in Lvcthealth colours:
 * KPI cards, bar charts, doughnut, line chart and tables.
 */
const DASH_PRIMARY = '#2D008A', DASH_BG = '#FDFCF9', DASH_GREEN = '#04B04B', DASH_YELLOW = '#FFC12E', DASH_RED = '#E41E39', DASH_LILAC = '#AC80EE';
const DASH_PALETTE = ['#2D008A', '#04B04B', '#FFC12E', '#E41E39', '#AC80EE', '#6B3FD1', '#7BD9A3', '#FFE08A', '#F17A8A'];

function dash_facility(mysqli $conn): array
{
    $r = $conn->query("SELECT fs.facilityname, fs.countyname, COALESCE(NULLIF(fs.mflcode,''), f.mflcode) AS mfl FROM facility_settings fs LEFT JOIN facilities f ON f.id = fs.facility_id LIMIT 1");
    return ($r ? $r->fetch_assoc() : null) ?: ['facilityname' => 'No facility set', 'countyname' => '', 'mfl' => ''];
}

/** Run a query and return all rows (empty array on error). */
function dash_rows(mysqli $conn, string $sql): array
{
    $r = $conn->query($sql);
    return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}
function dash_val(mysqli $conn, string $sql, $default = 0)
{
    $r = $conn->query($sql);
    $row = $r ? $r->fetch_row() : null;
    return $row && $row[0] !== null ? $row[0] : $default;
}

function dash_head(string $title, array $fac, string $subtitle = ''): void
{
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' . htmlspecialchars((string)($title)) . '</title>
<link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.css"><style>
:root{--pri:#2D008A;--bg:#FDFCF9;--ok:#04B04B;--warn:#FFC12E;--bad:#E41E39;--soft:#AC80EE}
*{box-sizing:border-box}body{margin:0;font-family:Segoe UI,Arial,sans-serif;background:var(--bg);color:#222}
.top{background:var(--pri);color:#fff;padding:16px 28px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px}
.top h1{margin:0;font-size:20px}.top small{opacity:.8}.wrap{padding:20px 24px;max-width:1400px;margin:auto}
.kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px;margin-bottom:20px}
.kpi{background:#fff;border-radius:10px;padding:16px 18px;border:1px solid #e5e0f2;border-top:5px solid var(--c,#2D008A);box-shadow:0 1px 3px rgba(45,0,138,.06)}
.kpi .n{font-size:30px;font-weight:700;color:var(--c,#2D008A)}.kpi .l{font-size:12px;color:#555;margin-top:2px}.kpi .h{font-size:11px;color:#888;margin-top:6px}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(380px,1fr));gap:16px}
.card{background:#fff;border:1px solid #e5e0f2;border-radius:10px;overflow:hidden}.card h2{margin:0;padding:11px 16px;font-size:14px;color:var(--pri);border-bottom:3px solid var(--soft)}
.card .b{padding:14px 16px}.bar{display:flex;align-items:center;gap:8px;margin:6px 0;font-size:12px}.bar .t{width:34%;text-align:right;color:#444}
.bar .f{height:16px;border-radius:3px;min-width:2px}.bar .v{font-weight:600;color:#333}
table{border-collapse:collapse;width:100%;font-size:12.5px}th{background:#f3effb;color:var(--pri);text-align:left;padding:7px 12px}td{padding:7px 12px;border-bottom:1px solid #eee}
.donutwrap{display:flex;align-items:center;gap:18px;flex-wrap:wrap}.donut{width:140px;height:140px;border-radius:50%;position:relative;flex:none}
.donut:after{content:"";position:absolute;inset:32px;background:#fff;border-radius:50%}.leg div{font-size:12px;margin:3px 0}.leg i{display:inline-block;width:11px;height:11px;border-radius:2px;margin-right:6px}
.empty{color:#999;font-size:13px;padding:8px 0}.pill{display:inline-block;padding:1px 9px;border-radius:10px;font-size:11px;color:#fff}
@media(max-width:600px){.grid{grid-template-columns:1fr}.wrap{padding:12px}}
</style></head><body><div class="top"><div><h1>' . htmlspecialchars((string)($title)) . '</h1><small>' . htmlspecialchars((string)($fac['facilityname'])) . ' &middot; MFL ' . htmlspecialchars((string)$fac['mfl']) . ($subtitle ? ' &middot; ' . htmlspecialchars((string)($subtitle)) : '') . '</small></div><div><small>' . date('l, j F Y') . '</small></div></div><div class="wrap">';
}
function dash_foot(): void { echo '</div></body></html>'; }

function dash_kpis(array $items): void
{
    echo '<div class="kpis">';
    foreach ($items as $k) {
        echo '<div class="kpi" style="--c:' . ($k[3] ?? DASH_PRIMARY) . '"><div class="n">' . htmlspecialchars((string)$k[1]) . '</div><div class="l">' . htmlspecialchars((string)($k[0])) . '</div>' . (!empty($k[2]) ? '<div class="h">' . htmlspecialchars((string)($k[2])) . '</div>' : '') . '</div>';
    }
    echo '</div>';
}

function dash_card_open(string $title): void { echo '<div class="card"><h2>' . htmlspecialchars((string)($title)) . '</h2><div class="b">'; }
function dash_card_close(): void { echo '</div></div>'; }

/** rows: [[label, value], ...] */
function dash_bars(string $title, array $rows, string $color = DASH_PRIMARY): void
{
    dash_card_open($title);
    if (!$rows) { echo '<div class="empty">No data yet.</div>'; }
    $max = max(1, ...array_map(fn($r) => (float)$r[1], $rows ?: [[0, 0]]));
    foreach ($rows as $i => $r) {
        $w = round(((float)$r[1] / $max) * 100);
        echo '<div class="bar"><div class="t">' . htmlspecialchars((string)$r[0]) . '</div><div class="f" style="width:calc(' . $w . '% * .55);background:' . ($color === 'multi' ? DASH_PALETTE[$i % 9] : $color) . '"></div><div class="v">' . number_format((float)$r[1]) . '</div></div>';
    }
    dash_card_close();
}

function dash_donut(string $title, array $rows, array $colorMap = []): void
{
    dash_card_open($title);
    $total = array_sum(array_map(fn($r) => (float)$r[1], $rows));
    if ($total <= 0) { echo '<div class="empty">No data yet.</div>'; dash_card_close(); return; }
    $acc = 0; $stops = []; $leg = '';
    foreach ($rows as $i => $r) {
        $c = $colorMap[$r[0]] ?? DASH_PALETTE[$i % 9]; $p = $r[1] / $total * 100;
        $stops[] = "$c {$acc}% " . ($acc + $p) . '%'; $acc += $p;
        $leg .= '<div><i style="background:' . $c . '"></i>' . htmlspecialchars((string)$r[0]) . ' &mdash; <b>' . number_format((float)$r[1]) . '</b> (' . round($p, 1) . '%)</div>';
    }
    echo '<div class="donutwrap"><div class="donut" style="background:conic-gradient(' . implode(',', $stops) . ')"></div><div class="leg">' . $leg . '</div></div>';
    dash_card_close();
}

/** rows: [[label, value], ...] in x order */
function dash_line(string $title, array $rows, string $color = DASH_PRIMARY): void
{
    dash_card_open($title);
    $n = count($rows);
    if ($n < 2) { echo '<div class="empty">Not enough data yet.</div>'; dash_card_close(); return; }
    $W = 520; $H = 170; $pad = 26; $max = max(1, ...array_map(fn($r) => (float)$r[1], $rows));
    $pts = []; $dots = ''; $lab = '';
    foreach ($rows as $i => $r) {
        $x = $pad + ($W - 2 * $pad) * $i / ($n - 1); $y = $H - $pad - ($H - 2 * $pad) * ((float)$r[1] / $max);
        $pts[] = round($x, 1) . ',' . round($y, 1);
        $dots .= '<circle cx="' . round($x, 1) . '" cy="' . round($y, 1) . '" r="3" fill="' . $color . '"><title>' . htmlspecialchars((string)$r[0]) . ': ' . $r[1] . '</title></circle>';
        if ($i % max(1, (int)ceil($n / 8)) === 0) $lab .= '<text x="' . round($x, 1) . '" y="' . ($H - 8) . '" font-size="10" fill="#777" text-anchor="middle">' . htmlspecialchars((string)$r[0]) . '</text>';
    }
    echo '<svg viewBox="0 0 ' . $W . ' ' . $H . '" style="width:100%;height:auto"><line x1="' . $pad . '" y1="' . ($H - $pad) . '" x2="' . ($W - $pad) . '" y2="' . ($H - $pad) . '" stroke="#ddd"/>'
        . '<text x="' . $pad . '" y="14" font-size="10" fill="#777">max ' . number_format($max) . '</text><polyline fill="none" stroke="' . $color . '" stroke-width="2.5" points="' . implode(' ', $pts) . '"/>' . $dots . $lab . '</svg>';
    dash_card_close();
}

/** $cols: [header, ...]; $rows: arrays of cell html-safe strings */
function dash_table(string $title, array $cols, array $rows): void
{
    dash_card_open($title);
    if (!$rows) { echo '<div class="empty">Nothing to show.</div>'; dash_card_close(); return; }
    echo '<table><tr>' . implode('', array_map(fn($c) => '<th>' . htmlspecialchars((string)($c)) . '</th>', $cols)) . '</tr>';
    foreach ($rows as $r) echo '<tr>' . implode('', array_map(fn($c) => '<td>' . $c . '</td>', $r)) . '</tr>';
    echo '</table>';
    dash_card_close();
}

/** Fill gaps so a line chart has one point per day. $data: [Y-m-d => value] */
function dash_days(array $data, int $days, string $fmt = 'd M'): array
{
    $out = [];
    for ($i = $days - 1; $i >= 0; $i--) { $d = date('Y-m-d', strtotime((string)("-$i day"))); $out[] = [date($fmt, strtotime((string)($d))), (int)($data[$d] ?? 0)]; }
    return $out;
}
function dash_months(array $data, int $months): array
{
    $out = [];
    for ($i = $months - 1; $i >= 0; $i--) { $m = date('Y-m', strtotime((string)("first day of -$i month"))); $out[] = [date('M y', strtotime((string)($m . '-01'))), (int)($data[$m] ?? 0)]; }
    return $out;
}
function dash_assoc(array $rows, string $k, string $v): array { $o = []; foreach ($rows as $r) $o[$r[$k]] = $r[$v]; return $o; }
function dash_pairs(array $rows, string $k, string $v): array { return array_map(fn($r) => [$r[$k], $r[$v]], $rows); }
const DASH_STATUS_COLORS = ['Active' => DASH_GREEN, 'Defaulted' => DASH_YELLOW, 'LTFU' => DASH_RED, 'Weaned' => DASH_LILAC, 'Transout' => '#6B3FD1', 'Transit' => '#7BD9A3', 'Dead' => '#444', 'Stopped' => '#999', 'Voluntary Discontinuation' => '#F17A8A'];

/**
 * Multi-series line chart. $labels: x labels; $series: [name => [values...]]; $colors: [name => colour];
 * $tips: optional full-date tooltips per x position.
 */
function dash_multiline(string $title, array $labels, array $series, array $colors = [], array $tips = []): void
{
    dash_card_open($title);
    $n = count($labels);
    if ($n < 2) { echo '<div class="empty">Not enough data yet.</div>'; dash_card_close(); return; }
    $W = 900; $H = 280; $pl = 40; $pr = 14; $pt = 14; $pb = 36;
    $max = 1; foreach ($series as $vals) { $max = max($max, ...array_map('floatval', $vals ?: [0])); }
    $step = max(1, (int)ceil($max / 5)); $max = $step * ceil($max / $step);
    $x = fn($i) => $pl + ($W - $pl - $pr) * $i / ($n - 1);
    $y = fn($v) => $H - $pb - ($H - $pt - $pb) * ($v / $max);
    $svg = '<svg viewBox="0 0 ' . $W . ' ' . $H . '" style="width:100%;height:auto">';
    for ($g = 0; $g <= $max; $g += $step) { $gy = round($y($g), 1); $svg .= '<line x1="' . $pl . '" y1="' . $gy . '" x2="' . ($W - $pr) . '" y2="' . $gy . '" stroke="#eee"/><text x="' . ($pl - 6) . '" y="' . ($gy + 4) . '" font-size="11" fill="#777" text-anchor="end">' . $g . '</text>'; }
    $every = max(1, (int)ceil($n / 14));
    foreach ($labels as $i => $lb) { if ($i % $every === 0) $svg .= '<text x="' . round($x($i), 1) . '" y="' . ($H - 12) . '" font-size="11" fill="#666" text-anchor="middle">' . htmlspecialchars((string)$lb) . '</text>'; }
    $legend = ''; $k = 0;
    foreach ($series as $name => $vals) {
        $c = $colors[$name] ?? DASH_PALETTE[$k++ % 9]; $pts = []; $dots = '';
        foreach ($vals as $i => $v) {
            $px = round($x($i), 1); $py = round($y((float)$v), 1); $pts[] = "$px,$py";
            $dots .= '<circle cx="' . $px . '" cy="' . $py . '" r="3.5" fill="' . $c . '"><title>' . htmlspecialchars((string)($name . ' - ' . ($tips[$i] ?? $labels[$i]) . ': ' . $v)) . '</title></circle>';
        }
        $svg .= '<polyline fill="none" stroke="' . $c . '" stroke-width="2.6" stroke-linejoin="round" points="' . implode(' ', $pts) . '"/>' . $dots;
        $legend .= '<span style="margin-right:18px;font-size:12.5px"><i style="display:inline-block;width:14px;height:4px;background:' . $c . ';margin-right:6px;vertical-align:middle"></i>' . htmlspecialchars((string)($name)) . ' <b>(' . array_sum($vals) . ')</b></span>';
    }
    echo '<div style="margin-bottom:6px">' . $legend . '</div>' . $svg . '</svg>';
    dash_card_close();
}

/** Vertical column chart with value labels. $rows: [[label, value], ...] */
function dash_vbars(string $title, array $rows, array $colorMap = [], string $default = DASH_PRIMARY): void
{
    dash_card_open($title);
    if (!$rows) { echo '<div class="empty">No data yet.</div>'; dash_card_close(); return; }
    $total = array_sum(array_map(fn($r) => (float)$r[1], $rows)) ?: 1;
    $max = max(1, ...array_map(fn($r) => (float)$r[1], $rows));
    $n = count($rows); $W = max(520, $n * 74); $H = 250; $pb = 52; $pt = 24; $bw = 40; $gap = ($W - $n * $bw) / ($n + 1);
    $svg = '<svg viewBox="0 0 ' . $W . ' ' . $H . '" style="width:100%;height:auto"><line x1="0" y1="' . ($H - $pb) . '" x2="' . $W . '" y2="' . ($H - $pb) . '" stroke="#ddd"/>';
    foreach ($rows as $i => $r) {
        $h = ($H - $pb - $pt) * ((float)$r[1] / $max); $bx = $gap + $i * ($bw + $gap); $by = $H - $pb - $h;
        $c = $colorMap[$r[0]] ?? $default;
        $svg .= '<rect x="' . round($bx, 1) . '" y="' . round($by, 1) . '" width="' . $bw . '" height="' . round($h, 1) . '" rx="4" fill="' . $c . '"><title>' . htmlspecialchars((string)($r[0] . ': ' . $r[1] . ' (' . round($r[1] / $total * 100, 1) . '%)')) . '</title></rect>'
              . '<text x="' . round($bx + $bw / 2, 1) . '" y="' . round($by - 6, 1) . '" font-size="12" font-weight="700" fill="#333" text-anchor="middle">' . number_format((float)$r[1]) . '</text>'
              . '<text x="' . round($bx + $bw / 2, 1) . '" y="' . ($H - $pb + 16) . '" font-size="11" fill="#555" text-anchor="middle">' . htmlspecialchars((string)(mb_strimwidth($r[0], 0, 11, '.'))) . '</text>'
              . '<text x="' . round($bx + $bw / 2, 1) . '" y="' . ($H - $pb + 30) . '" font-size="10" fill="#999" text-anchor="middle">' . round($r[1] / $total * 100, 1) . '%</text>';
    }
    echo $svg . '</svg>';
    dash_card_close();
}

<?php
/**
 * create_ai_generated_report.php
 * ------------------------------------------------------------------
 * EasyFlow-L / IORPMS — AI-Assisted Analytics & Risk Report
 *
 * Summarises clinic-wide trends (inductions, status changes, LTFU)
 * and offers a statistical "risk estimator" that uses historical
 * cohort data already in this facility's database to estimate, for
 * a new or existing induction:
 *   - Likelihood of defaulting
 *   - Likelihood of becoming Lost To Follow Up (LTFU)
 *   - Expected time to being weaned off treatment
 *
 * NOTE ON METHODOLOGY: This is a transparent, rule-based statistical
 * model (cohort matching + Bayesian shrinkage toward the facility
 * average), computed live from this facility's own records. It is a
 * decision-support aid, not a clinical diagnosis. It works fully
 * offline (no external services/CDNs) since MAT sites may have
 * limited connectivity.
 * ------------------------------------------------------------------
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
include '../includes/config.php';

// ---------------------------------------------------------------
// 1. Pull raw data once, do all banding / matching in PHP.
// ---------------------------------------------------------------

$patients = [];
$sql = "SELECT p_id, mat_id, reg_date, dob, age, sex, dosage, drugname, current_status, cso, county, comp_date
        FROM patients";
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $patients[$row['mat_id']] = $row;
    }
}

// All status history, grouped by mat_id, in chronological order
$historyByMat = [];
$sql = "SELECT mat_id, new_status, status_change_date FROM statushistory ORDER BY mat_id, status_change_date ASC";
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $historyByMat[$row['mat_id']][] = $row;
    }
}

// ---------------------------------------------------------------
// 2. Helper functions
// ---------------------------------------------------------------

function computeAge($dob, $fallbackAge) {
    if (!empty($dob) && $dob !== '0000-00-00') {
        try {
            $d = new DateTime($dob);
            $now = new DateTime();
            return (int)$d->diff($now)->y;
        } catch (Exception $e) { /* fall through */ }
    }
    $a = (int)preg_replace('/[^0-9]/', '', (string)$fallbackAge);
    return $a > 0 ? $a : null;
}

function ageBand($age) {
    if ($age === null) return 'Unknown';
    if ($age < 20) return '<20';
    if ($age <= 24) return '20-24';
    if ($age <= 29) return '25-29';
    if ($age <= 34) return '30-34';
    if ($age <= 39) return '35-39';
    return '40+';
}

function dosageBand($dose) {
    $dose = (float)$dose;
    if ($dose <= 0) return 'Unknown';
    if ($dose < 20) return '0-19 mg';
    if ($dose < 40) return '20-39 mg';
    if ($dose < 60) return '40-59 mg';
    if ($dose < 80) return '60-79 mg';
    if ($dose < 100) return '80-99 mg';
    return '100+ mg';
}

function tenureBand($days) {
    if ($days === null) return 'Unknown';
    if ($days <= 30) return '0-30 days';
    if ($days <= 90) return '31-90 days';
    if ($days <= 180) return '91-180 days';
    if ($days <= 365) return '181-365 days';
    if ($days <= 730) return '1-2 years';
    return '2+ years';
}

function isLtfuLabel($s) { return stripos((string)$s, 'ltfu') !== false; }
function isDefaultLabel($s) { return stripos((string)$s, 'default') !== false; }
function isWeanedLabel($s) { return stripos((string)$s, 'wean') !== false; }

function daysBetween($d1, $d2) {
    if (empty($d1) || empty($d2)) return null;
    try {
        $a = new DateTime($d1);
        $b = new DateTime($d2);
        return (int)$a->diff($b)->days;
    } catch (Exception $e) { return null; }
}

function riskBand($rate) {
    if ($rate < 0.15) return ['Low', '#2e7d32'];
    if ($rate < 0.35) return ['Moderate', '#e65100'];
    return ['High', '#c62828'];
}

// ---------------------------------------------------------------
// 3. Enrich each patient with derived outcome flags
// ---------------------------------------------------------------

$today = date('Y-m-d');
$cohort = [];

foreach ($patients as $matId => $p) {
    $regDate = $p['reg_date'] ? date('Y-m-d', strtotime($p['reg_date'])) : null;
    $age = computeAge($p['dob'], $p['age']);

    $everLtfu = isLtfuLabel($p['current_status']);
    $everDefault = isDefaultLabel($p['current_status']);
    $firstLtfuDate = null;
    $firstWeanedDate = null;

    if (!empty($historyByMat[$matId])) {
        foreach ($historyByMat[$matId] as $h) {
            if (isLtfuLabel($h['new_status'])) {
                $everLtfu = true;
                if ($firstLtfuDate === null) $firstLtfuDate = $h['status_change_date'];
            }
            if (isDefaultLabel($h['new_status'])) {
                $everDefault = true;
            }
            if (isWeanedLabel($h['new_status']) && $firstWeanedDate === null) {
                $firstWeanedDate = $h['status_change_date'];
            }
        }
    }
    if (isWeanedLabel($p['current_status']) && $firstWeanedDate === null) {
        $firstWeanedDate = $p['comp_date'] ?? $today;
    }

    $tenureDays = $regDate ? daysBetween($regDate, $today) : null;
    $daysToLtfu = ($regDate && $firstLtfuDate) ? daysBetween($regDate, $firstLtfuDate) : null;
    $daysToWean = ($regDate && $firstWeanedDate) ? daysBetween($regDate, $firstWeanedDate) : null;

    $cohort[] = [
        'mat_id' => $matId,
        'age' => $age,
        'age_band' => ageBand($age),
        'sex' => trim((string)$p['sex']),
        'dosage' => (float)$p['dosage'],
        'dosage_band' => dosageBand($p['dosage']),
        'drugname' => trim((string)$p['drugname']),
        'current_status' => trim((string)$p['current_status']),
        'reg_date' => $regDate,
        'tenure_days' => $tenureDays,
        'tenure_band' => tenureBand($tenureDays),
        'ever_ltfu' => $everLtfu,
        'ever_default' => $everDefault,
        'days_to_ltfu' => $daysToLtfu,
        'ltfu_tenure_band' => $daysToLtfu !== null ? tenureBand($daysToLtfu) : null,
        'days_to_wean' => $daysToWean,
    ];
}

$totalClients = count($cohort);

// ---------------------------------------------------------------
// 4. Aggregate: induction trend by month (last 12 months)
// ---------------------------------------------------------------

$monthLabels = [];
for ($i = 11; $i >= 0; $i--) {
    $monthLabels[] = date('Y-m', strtotime("-$i months"));
}
$inductionsByMonth = array_fill_keys($monthLabels, ['M' => 0, 'F' => 0, 'Other' => 0]);
foreach ($cohort as $c) {
    if (!$c['reg_date']) continue;
    $m = date('Y-m', strtotime($c['reg_date']));
    if (!isset($inductionsByMonth[$m])) continue;
    $sx = strtoupper(substr($c['sex'], 0, 1));
    if ($sx === 'M') $inductionsByMonth[$m]['M']++;
    elseif ($sx === 'F') $inductionsByMonth[$m]['F']++;
    else $inductionsByMonth[$m]['Other']++;
}

// ---------------------------------------------------------------
// 5. Aggregate: status-change trend by month (last 12 months)
// ---------------------------------------------------------------

$statusesTracked = ['LTFU', 'Defaulted', 'Weaned', 'Stopped', 'Transout', 'Dead'];
$statusByMonth = [];
foreach ($monthLabels as $m) {
    $statusByMonth[$m] = array_fill_keys($statusesTracked, 0);
}
foreach ($historyByMat as $matId => $rows) {
    foreach ($rows as $h) {
        $m = date('Y-m', strtotime($h['status_change_date']));
        if (!isset($statusByMonth[$m])) continue;
        foreach ($statusesTracked as $label) {
            if (stripos($h['new_status'], $label) !== false) {
                $statusByMonth[$m][$label]++;
                break;
            }
        }
    }
}

// ---------------------------------------------------------------
// 6. LTFU analysis: by age band, dosage band, sex, tenure-at-LTFU
// ---------------------------------------------------------------

function bandBreakdown($cohort, $bandKey) {
    $out = [];
    foreach ($cohort as $c) {
        $b = $c[$bandKey];
        if (!isset($out[$b])) $out[$b] = ['n' => 0, 'ltfu' => 0, 'default' => 0];
        $out[$b]['n']++;
        if ($c['ever_ltfu']) $out[$b]['ltfu']++;
        if ($c['ever_default']) $out[$b]['default']++;
    }
    return $out;
}

$byAge = bandBreakdown($cohort, 'age_band');
$byDosage = bandBreakdown($cohort, 'dosage_band');
$bySex = bandBreakdown($cohort, 'sex');

$ltfuByTenure = array_fill_keys(['0-30 days', '31-90 days', '91-180 days', '181-365 days', '1-2 years', '2+ years'], 0);
foreach ($cohort as $c) {
    if ($c['ever_ltfu'] && $c['ltfu_tenure_band'] && isset($ltfuByTenure[$c['ltfu_tenure_band']])) {
        $ltfuByTenure[$c['ltfu_tenure_band']]++;
    }
}
$totalLtfuCount = array_sum($ltfuByTenure);

// Overall base rates (facility-wide priors used for shrinkage)
$overallLtfuCount = 0; $overallDefaultCount = 0; $weanDaysSum = 0; $weanDaysN = 0;
foreach ($cohort as $c) {
    if ($c['ever_ltfu']) $overallLtfuCount++;
    if ($c['ever_default']) $overallDefaultCount++;
    if ($c['days_to_wean'] !== null && $c['days_to_wean'] >= 0) { $weanDaysSum += $c['days_to_wean']; $weanDaysN++; }
}
$overallLtfuRate = $totalClients ? $overallLtfuCount / $totalClients : 0;
$overallDefaultRate = $totalClients ? $overallDefaultCount / $totalClients : 0;
$overallWeanDaysAvg = $weanDaysN ? $weanDaysSum / $weanDaysN : null;

// ---------------------------------------------------------------
// 7. Prediction tool: cohort match + Bayesian shrinkage
// ---------------------------------------------------------------

$prediction = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['predict'])) {
    $inAge = (int)($_POST['p_age'] ?? 0);
    $inSex = trim($_POST['p_sex'] ?? '');
    $inDosage = (float)($_POST['p_dosage'] ?? 0);
    $inDrug = trim($_POST['p_drug'] ?? '');

    $inAgeBand = ageBand($inAge);
    $inDosageBand = dosageBand($inDosage);

    $prior = 10; // strength of the facility-wide prior in the shrinkage formula

    // Backoff levels: most specific -> least specific
    $levels = [
        ['label' => 'age band + sex + dosage band + drug', 'f' => function ($c) use ($inAgeBand, $inSex, $inDosageBand, $inDrug) {
            return $c['age_band'] === $inAgeBand && $c['sex'] === $inSex && $c['dosage_band'] === $inDosageBand && strcasecmp($c['drugname'], $inDrug) === 0;
        }],
        ['label' => 'age band + sex + dosage band', 'f' => function ($c) use ($inAgeBand, $inSex, $inDosageBand) {
            return $c['age_band'] === $inAgeBand && $c['sex'] === $inSex && $c['dosage_band'] === $inDosageBand;
        }],
        ['label' => 'age band + sex', 'f' => function ($c) use ($inAgeBand, $inSex) {
            return $c['age_band'] === $inAgeBand && $c['sex'] === $inSex;
        }],
        ['label' => 'age band only', 'f' => function ($c) use ($inAgeBand) {
            return $c['age_band'] === $inAgeBand;
        }],
    ];

    $matchLabel = 'facility-wide average (no closer match found)';
    $matchN = 0; $matchLtfu = 0; $matchDefault = 0; $matchWeanSum = 0; $matchWeanN = 0;

    foreach ($levels as $level) {
        $n = 0; $ltfu = 0; $def = 0; $weanSum = 0; $weanN = 0;
        foreach ($cohort as $c) {
            if ($level['f']($c)) {
                $n++;
                if ($c['ever_ltfu']) $ltfu++;
                if ($c['ever_default']) $def++;
                if ($c['days_to_wean'] !== null && $c['days_to_wean'] >= 0) { $weanSum += $c['days_to_wean']; $weanN++; }
            }
        }
        if ($n >= 5) {
            $matchLabel = $level['label']; $matchN = $n; $matchLtfu = $ltfu; $matchDefault = $def;
            $matchWeanSum = $weanSum; $matchWeanN = $weanN;
            break;
        }
    }

    $cohortLtfuRate = $matchN ? $matchLtfu / $matchN : $overallLtfuRate;
    $cohortDefaultRate = $matchN ? $matchDefault / $matchN : $overallDefaultRate;

    // Bayesian shrinkage toward facility-wide rate
    $adjLtfuRate = (($matchN * $cohortLtfuRate) + ($prior * $overallLtfuRate)) / ($matchN + $prior);
    $adjDefaultRate = (($matchN * $cohortDefaultRate) + ($prior * $overallDefaultRate)) / ($matchN + $prior);

    $expWeanDays = $matchWeanN >= 3 ? ($matchWeanSum / $matchWeanN) : $overallWeanDaysAvg;

    [$ltfuBandLabel, $ltfuBandColor] = riskBand($adjLtfuRate);
    [$defaultBandLabel, $defaultBandColor] = riskBand($adjDefaultRate);

    $prediction = [
        'input' => compact('inAge', 'inSex', 'inDosage', 'inDrug'),
        'matchLabel' => $matchLabel,
        'matchN' => $matchN,
        'ltfuRate' => $adjLtfuRate,
        'defaultRate' => $adjDefaultRate,
        'ltfuBand' => [$ltfuBandLabel, $ltfuBandColor],
        'defaultBand' => [$defaultBandLabel, $defaultBandColor],
        'expWeanDays' => $expWeanDays,
        'weanSampleN' => $matchWeanN,
    ];
}

function pct($x) { return $x === null ? 'n/a' : round($x * 100, 1) . '%'; }

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>AI-Assisted Analytics &amp; Risk Report</title>
<style>
    body { font-family: Arial, Helvetica, sans-serif; font-size: 15px; color: #222; margin: 10px 20px; }
    h2 { color: #2C3162; margin-bottom: 4px; }
    h3 { color: #0A1172; border-bottom: 2px solid #0A1172; padding-bottom: 4px; margin-top: 30px; }
    .subtitle { color: #555; margin-top: 0; margin-bottom: 20px; }
    .kpi-row { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 10px; }
    .kpi-card { background: #0A1172; color: #fff; border-radius: 8px; padding: 12px 18px; min-width: 140px; text-align: center; }
    .kpi-card .num { font-size: 26px; font-weight: bold; display: block; }
    .kpi-card .label { font-size: 12px; opacity: 0.85; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
    th, td { border: 1px solid #ccc; padding: 6px 10px; text-align: left; font-size: 14px; }
    th { background: #0A1172; color: #fff; }
    tr:nth-child(even) { background: #f5f7fb; }
    .bar-wrap { background: #e8eaf2; border-radius: 4px; overflow: hidden; width: 220px; height: 16px; display: inline-block; vertical-align: middle; }
    .bar-fill { background: #3949ab; height: 16px; }
    .bar-fill.ltfu { background: #c62828; }
    .month-chart { display: flex; align-items: flex-end; gap: 6px; height: 160px; border-left: 2px solid #ccc; border-bottom: 2px solid #ccc; padding: 10px 5px 0 5px; }
    .month-bar { flex: 1; text-align: center; display: flex; flex-direction: column-reverse; }
    .month-bar .seg { width: 100%; }
    .month-bar .seg.m { background: #3949ab; }
    .month-bar .seg.f { background: #d81b60; }
    .month-bar .lab { font-size: 10px; transform: rotate(-45deg); margin-top: 6px; white-space: nowrap; }
    .toolbar { margin: 14px 0 22px 0; }
    .toolbar button { background: grey; color: #fff; border: none; border-radius: 5px; padding: 8px 16px; margin-right: 10px; cursor: pointer; }
    .toolbar button.export { background: green; }
    .predict-form { background: #f5f7fb; border: 1px solid #d0d5e5; border-radius: 8px; padding: 18px; max-width: 700px; }
    .predict-form label { display: inline-block; width: 130px; font-weight: bold; }
    .predict-form input, .predict-form select { padding: 6px; margin-bottom: 10px; width: 180px; }
    .predict-form button { background: #0A1172; color: #fff; border: none; padding: 10px 24px; border-radius: 5px; cursor: pointer; }
    .result-card { border-radius: 8px; padding: 16px; color: #fff; margin-bottom: 12px; }
    .caveat { font-size: 12px; color: #666; font-style: italic; margin-top: 10px; max-width: 700px; }
    @media print { .toolbar, .predict-form button { display: none; } }
</style>
</head>
<body>

<h2>AI-Assisted Analytics &amp; Risk Report</h2>
<p class="subtitle">Client induction trends, status-change trends, LTFU analysis, and a historical-cohort risk estimator for new inductions. Generated <?php echo date('d M Y H:i'); ?>.</p>

<div class="toolbar">
    <button onclick="window.print()">Print / Save PDF</button>
    <button class="export" onclick="exportTablesToExcel()">Export Tables to Excel</button>
</div>

<div class="kpi-row">
    <div class="kpi-card"><span class="num"><?php echo $totalClients; ?></span><span class="label">Ever Enrolled</span></div>
    <div class="kpi-card"><span class="num"><?php echo $overallLtfuCount; ?></span><span class="label">Ever LTFU (<?php echo pct($overallLtfuRate); ?>)</span></div>
    <div class="kpi-card"><span class="num"><?php echo $overallDefaultCount; ?></span><span class="label">Ever Defaulted (<?php echo pct($overallDefaultRate); ?>)</span></div>
    <div class="kpi-card"><span class="num"><?php echo $weanDaysN; ?></span><span class="label">Ever Weaned Off</span></div>
    <div class="kpi-card"><span class="num"><?php echo $overallWeanDaysAvg ? round($overallWeanDaysAvg / 30.44, 1) : 'n/a'; ?></span><span class="label">Avg Months to Wean</span></div>
</div>

<h3>1. Client Induction Trend (last 12 months)</h3>
<div class="month-chart" id="inductionChart">
<?php
$maxInd = 1;
foreach ($inductionsByMonth as $v) { $maxInd = max($maxInd, $v['M'] + $v['F'] + $v['Other']); }
foreach ($inductionsByMonth as $m => $v) {
    $total = $v['M'] + $v['F'] + $v['Other'];
    $hM = $maxInd ? round(($v['M'] / $maxInd) * 130) : 0;
    $hF = $maxInd ? round(($v['F'] / $maxInd) * 130) : 0;
    echo "<div class='month-bar' title='" . htmlspecialchars($m) . ": $total inductions (M:{$v['M']} F:{$v['F']})'>";
    echo "<div class='lab'>" . htmlspecialchars($m) . " ($total)</div>";
    echo "<div class='seg f' style='height:{$hF}px'></div>";
    echo "<div class='seg m' style='height:{$hM}px'></div>";
    echo "</div>";
}
?>
</div>
<p style="font-size:12px;color:#555;"><span style="color:#3949ab;">■</span> Male &nbsp; <span style="color:#d81b60;">■</span> Female</p>

<table id="tbl-inductions">
<tr><th>Month</th><th>Male</th><th>Female</th><th>Other/Unspecified</th><th>Total</th></tr>
<?php foreach ($inductionsByMonth as $m => $v): $t = $v['M'] + $v['F'] + $v['Other']; ?>
<tr><td><?php echo $m; ?></td><td><?php echo $v['M']; ?></td><td><?php echo $v['F']; ?></td><td><?php echo $v['Other']; ?></td><td><b><?php echo $t; ?></b></td></tr>
<?php endforeach; ?>
</table>

<h3>2. Status Change Trend (last 12 months)</h3>
<table id="tbl-status">
<tr><th>Month</th><?php foreach ($statusesTracked as $s) echo "<th>$s</th>"; ?></tr>
<?php foreach ($statusByMonth as $m => $counts): ?>
<tr><td><?php echo $m; ?></td><?php foreach ($statusesTracked as $s) echo "<td>" . $counts[$s] . "</td>"; ?></tr>
<?php endforeach; ?>
</table>

<h3>3. LTFU Analysis by Age Group</h3>
<table id="tbl-age">
<tr><th>Age Group</th><th>Clients</th><th>Ever LTFU</th><th>LTFU Rate</th><th></th></tr>
<?php foreach ($byAge as $band => $d): $rate = $d['n'] ? $d['ltfu'] / $d['n'] : 0; ?>
<tr>
    <td><?php echo htmlspecialchars($band); ?></td>
    <td><?php echo $d['n']; ?></td>
    <td><?php echo $d['ltfu']; ?></td>
    <td><?php echo pct($rate); ?></td>
    <td><div class="bar-wrap"><div class="bar-fill ltfu" style="width:<?php echo round($rate * 220); ?>px"></div></div></td>
</tr>
<?php endforeach; ?>
</table>

<h3>4. LTFU Analysis by Dosage Band</h3>
<table id="tbl-dosage">
<tr><th>Dosage Band</th><th>Clients</th><th>Ever LTFU</th><th>LTFU Rate</th><th></th></tr>
<?php foreach ($byDosage as $band => $d): $rate = $d['n'] ? $d['ltfu'] / $d['n'] : 0; ?>
<tr>
    <td><?php echo htmlspecialchars($band); ?></td>
    <td><?php echo $d['n']; ?></td>
    <td><?php echo $d['ltfu']; ?></td>
    <td><?php echo pct($rate); ?></td>
    <td><div class="bar-wrap"><div class="bar-fill ltfu" style="width:<?php echo round($rate * 220); ?>px"></div></div></td>
</tr>
<?php endforeach; ?>
</table>

<h3>5. LTFU Analysis by Sex</h3>
<table id="tbl-sex">
<tr><th>Sex</th><th>Clients</th><th>Ever LTFU</th><th>LTFU Rate</th><th></th></tr>
<?php foreach ($bySex as $band => $d): $rate = $d['n'] ? $d['ltfu'] / $d['n'] : 0; ?>
<tr>
    <td><?php echo htmlspecialchars($band); ?></td>
    <td><?php echo $d['n']; ?></td>
    <td><?php echo $d['ltfu']; ?></td>
    <td><?php echo pct($rate); ?></td>
    <td><div class="bar-wrap"><div class="bar-fill ltfu" style="width:<?php echo round($rate * 220); ?>px"></div></div></td>
</tr>
<?php endforeach; ?>
</table>

<h3>6. LTFU Analysis by Time in Program (time elapsed before going LTFU)</h3>
<table id="tbl-tenure">
<tr><th>Time in Program at LTFU</th><th>Clients who went LTFU</th><th>Share of all LTFU cases</th><th></th></tr>
<?php foreach ($ltfuByTenure as $band => $n): $share = $totalLtfuCount ? $n / $totalLtfuCount : 0; ?>
<tr>
    <td><?php echo htmlspecialchars($band); ?></td>
    <td><?php echo $n; ?></td>
    <td><?php echo pct($share); ?></td>
    <td><div class="bar-wrap"><div class="bar-fill" style="width:<?php echo round($share * 220); ?>px"></div></div></td>
</tr>
<?php endforeach; ?>
</table>
<p style="font-size:13px;color:#444;">This shows <b>when</b> in a client's treatment journey LTFU most commonly occurs at this facility — useful for timing retention interventions (e.g. reinforced peer follow-up around the highest-risk window).</p>

<h3>7. New Induction Risk Estimator</h3>
<p style="font-size:13px;color:#444;max-width:700px;">
Enter a client's induction profile (or an existing MAT client's details) to get a statistical estimate of default risk,
LTFU risk, and expected time to being weaned off — based on outcomes of similar clients already treated at this facility.
The model automatically falls back to broader groupings (and finally to the facility-wide average) when there isn't
enough historical data for a very specific match, and blends the two so estimates stay stable with small sample sizes.
</p>

<form method="post" class="predict-form">
    <div>
        <label for="p_age">Age</label>
        <input type="number" min="10" max="100" name="p_age" id="p_age" required value="<?php echo htmlspecialchars($prediction['input']['inAge'] ?? ''); ?>">
    </div>
    <div>
        <label for="p_sex">Sex</label>
        <select name="p_sex" id="p_sex" required>
            <option value="Male" <?php echo (($prediction['input']['inSex'] ?? '') === 'Male') ? 'selected' : ''; ?>>Male</option>
            <option value="Female" <?php echo (($prediction['input']['inSex'] ?? '') === 'Female') ? 'selected' : ''; ?>>Female</option>
        </select>
    </div>
    <div>
        <label for="p_dosage">Induction Dosage (mg)</label>
        <input type="number" min="0" max="300" step="0.5" name="p_dosage" id="p_dosage" required value="<?php echo htmlspecialchars($prediction['input']['inDosage'] ?? ''); ?>">
    </div>
    <div>
        <label for="p_drug">Drug</label>
        <select name="p_drug" id="p_drug">
            <option value="Methadone" <?php echo (($prediction['input']['inDrug'] ?? '') === 'Methadone') ? 'selected' : ''; ?>>Methadone</option>
            <option value="Buprenorphine" <?php echo (($prediction['input']['inDrug'] ?? '') === 'Buprenorphine') ? 'selected' : ''; ?>>Buprenorphine</option>
        </select>
    </div>
    <button type="submit" name="predict" value="1">Estimate Risk</button>
</form>

<?php if ($prediction): ?>
<div style="max-width:700px;margin-top:18px;">
    <div class="result-card" style="background:<?php echo $prediction['ltfuBand'][1]; ?>;">
        <b>LTFU Risk: <?php echo $prediction['ltfuBand'][0]; ?></b> — estimated <?php echo pct($prediction['ltfuRate']); ?> likelihood of becoming lost-to-follow-up
    </div>
    <div class="result-card" style="background:<?php echo $prediction['defaultBand'][1]; ?>;">
        <b>Defaulting Risk: <?php echo $prediction['defaultBand'][0]; ?></b> — estimated <?php echo pct($prediction['defaultRate']); ?> likelihood of defaulting (5+ consecutive missed doses)
    </div>
    <div class="result-card" style="background:#0A1172;">
        <b>Expected Time to Weaned Off:</b>
        <?php echo $prediction['expWeanDays'] ? round($prediction['expWeanDays'] / 30.44, 1) . ' months' : 'insufficient historical data at this facility'; ?>
        <?php if ($prediction['weanSampleN'] < 3): ?> (facility-wide average used — few comparable weaned cases so far)<?php endif; ?>
    </div>
    <p style="font-size:12px;color:#555;">
        Estimate based on <b><?php echo $prediction['matchN']; ?></b> historical client(s) matched on:
        <b><?php echo htmlspecialchars($prediction['matchLabel']); ?></b>, blended with the facility-wide average
        (<?php echo $totalClients; ?> ever-enrolled clients) for stability.
    </p>
    <p class="caveat">
        This is a statistical decision-support estimate derived from this facility's own historical outcomes — it is
        NOT a clinical diagnosis and must not replace clinical judgement, MAT team review, or NASCOP/facility protocols.
        Always document the actual clinical assessment in the client's file.
    </p>
</div>
<?php endif; ?>

<script>
function exportTablesToExcel() {
    var ids = ['tbl-inductions','tbl-status','tbl-age','tbl-dosage','tbl-sex','tbl-tenure'];
    var html = '';
    ids.forEach(function(id){
        var t = document.getElementById(id);
        if (t) { html += t.outerHTML + '<br>'; }
    });
    var uri = 'data:application/vnd.ms-excel;charset=utf-8,' + encodeURIComponent(
        '<html><head><meta charset="UTF-8"><style>table,th,td{border:1px solid black;border-collapse:collapse;}th,td{padding:5px;}</style></head><body>' + html + '</body></html>'
    );
    var link = document.createElement('a');
    link.href = uri;
    link.download = 'ai_generated_report.xls';
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

</body>
</html>

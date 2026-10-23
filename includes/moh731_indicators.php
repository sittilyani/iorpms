<?php
/**
 * MOH 731 Plus (MAT section) indicators computed from live clinic data for one reporting period.
 * Used by the Reports dashboard, the CSV/JSON export and the KHIS (DHIS2) push.
 * Each indicator: id => [label, bands(bool), [Male=>[band=>n], Female=>[band=>n]]]
 */
const M731_BANDS = ['15-19', '20-24', '25-29', '30+'];

function m731_band_sql(string $dobExpr, string $end): string
{
    $a = "TIMESTAMPDIFF(YEAR, $dobExpr, '$end')";
    return "CASE WHEN $a BETWEEN 15 AND 19 THEN '15-19' WHEN $a BETWEEN 20 AND 24 THEN '20-24' WHEN $a BETWEEN 25 AND 29 THEN '25-29' WHEN $a >= 30 THEN '30+' ELSE 'under15' END";
}

/** Count distinct clients grouped by sex and age band. $from/$where reference patients as p. */
function m731_grid(mysqli $conn, string $from, string $where, string $end, bool $bands = true): array
{
    $out = ['Male' => [], 'Female' => []];
    foreach ($out as $s => $_) { foreach ($bands ? M731_BANDS : ['all'] as $b) { $out[$s][$b] = 0; } }
    $band = $bands ? m731_band_sql('p.dob', $end) : "'all'";
    $r = $conn->query("SELECT LOWER(TRIM(p.sex)) sx, $band bd, COUNT(DISTINCT p.mat_id) n FROM $from WHERE $where GROUP BY sx, bd");
    if ($r) {
        foreach ($r->fetch_all(MYSQLI_ASSOC) as $x) {
            $s = $x['sx'] === 'male' ? 'Male' : ($x['sx'] === 'female' ? 'Female' : null);
            if ($s && isset($out[$s][$x['bd']])) { $out[$s][$x['bd']] = (int)$x['n']; }
        }
    }
    return $out;
}

function moh731_indicators(mysqli $conn, string $mfl, string $period): array
{
    $start = $period . '-01'; $end = date('Y-m-t', strtotime($start)); $endT = $end . ' 23:59:59';
    $m = $conn->real_escape_string($mfl); $C = 'COLLATE utf8mb4_general_ci';
    $P = "patients p"; $PM = "p.mflcode='$m'";
    $disp = fn(string $drug) => m731_grid($conn, "patients p JOIN pharmacy x ON x.mat_id = p.mat_id $C", "$PM AND x.dispDate BETWEEN '$start' AND '$endT' AND x.drugname LIKE '$drug%'", $end);
    $hist = fn(string $st) => "p.mat_id IN (SELECT h.mat_id $C FROM statushistory h WHERE LOWER(TRIM(h.new_status)) = '$st' AND h.status_change_date BETWEEN '$start' AND '$endT')";
    $vl12 = "STR_TO_DATE(v.last_vlDate, '%Y-%m-%d') BETWEEN DATE_SUB('$end', INTERVAL 12 MONTH) AND '$end'";
    $num = "CAST(TRIM(SUBSTRING_INDEX(v.results, ' ', 1)) AS UNSIGNED)";
    $vlFrom = "patients p JOIN viral_load v ON v.mat_id = p.mat_id $C";
    $od = fn(string $extra) => m731_grid($conn, "patients p JOIN clinical_tool_entries o ON o.mat_id = p.mat_id $C AND o.form_key = 'overdose'", "$PM AND o.form_date BETWEEN '$start' AND '$end' $extra", $end, false);
    $I = [];
    $I['ever_inducted'] = ['Number ever inducted on MAT (cumulative)', false, m731_grid($conn, $P, "$PM AND p.reg_date <= '$endT' AND TRIM(p.mat_status) <> 'Transit'", $end, false)];
    $I['ever_weaned'] = ['Total clients ever weaned off MAT', false, m731_grid($conn, $P, "$PM AND LOWER(TRIM(p.current_status)) = 'weaned'", $end, false)];
    $I['newly_inducted'] = ['Newly inducted on MAT in the reporting period', true, m731_grid($conn, $P, "$PM AND TRIM(p.mat_status) = 'New' AND p.reg_date BETWEEN '$start' AND '$endT'", $end)];
    $I['on_methadone'] = ['Currently on methadone (dispensed in the period)', true, $disp('Methadone')];
    $I['on_bup'] = ['Currently on buprenorphine (dispensed in the period)', true, $disp('Buprenorphine')];
    $I['transit_methadone'] = ['Transit clients who received methadone', false, m731_grid($conn, "patients p JOIN pharmacy x ON x.mat_id = p.mat_id $C", "$PM AND LOWER(TRIM(p.mat_status)) = 'transit' AND x.dispDate BETWEEN '$start' AND '$endT' AND x.drugname LIKE 'Methadone%'", $end, false)];
    $I['transit_bup'] = ['Transit clients who received buprenorphine', false, m731_grid($conn, "patients p JOIN pharmacy x ON x.mat_id = p.mat_id $C", "$PM AND LOWER(TRIM(p.mat_status)) = 'transit' AND x.dispDate BETWEEN '$start' AND '$endT' AND x.drugname LIKE 'Buprenorphine%'", $end, false)];
    $I['weaned_bup'] = ['Weaned off buprenorphine in the period', false, m731_grid($conn, $P, "$PM AND p.drugname LIKE '%Buprenorphine%' AND " . $hist('weaned'), $end, false)];
    $I['weaned_methadone'] = ['Weaned off methadone in the period', false, m731_grid($conn, $P, "$PM AND p.drugname LIKE 'Methadone%' AND " . $hist('weaned'), $end, false)];
    $I['ltfu'] = ['Clients lost to follow up in the period', false, m731_grid($conn, $P, "$PM AND " . $hist('ltfu'), $end, false)];
    $I['hiv_tested'] = ['Clients tested for HIV in the period', true, m731_grid($conn, "patients p JOIN laboratory l ON l.mat_id = p.mat_id $C", "$PM AND l.visitDate BETWEEN '$start' AND '$end' AND l.hiv_status IN ('Positive','Negative')", $end)];
    $I['hiv_positive'] = ['Clients newly HIV positive in the period', true, m731_grid($conn, "patients p JOIN laboratory l ON l.mat_id = p.mat_id $C", "$PM AND l.visitDate BETWEEN '$start' AND '$end' AND l.hiv_status = 'Positive'", $end)];
    $I['hiv_known_pos'] = ['Active clients with known HIV positive status', true, m731_grid($conn, "patients p JOIN viral_load v ON v.mat_id = p.mat_id $C", "$PM AND LOWER(TRIM(p.current_status)) = 'active' AND v.hiv_status = 'Positive'", $end)];
    $I['vl_12mo'] = ['Viral load result in the last 12 months', false, m731_grid($conn, $vlFrom, "$PM AND $vl12", $end, false)];
    $I['vl_200'] = ['Suppressed - below 200 copies', false, m731_grid($conn, $vlFrom, "$PM AND $vl12 AND (v.results = 'LDL' OR v.results LIKE '<%' OR (v.results LIKE '%copies%' AND $num < 200))", $end, false)];
    $I['vl_50'] = ['Suppressed - below 50 copies', false, m731_grid($conn, $vlFrom, "$PM AND $vl12 AND (v.results = 'LDL' OR v.results LIKE '< 50%' OR (v.results LIKE '%copies%' AND $num < 50))", $end, false)];
    $I['overdose'] = ['Experienced opioid overdose (Overdose Encounter Form)', false, $od('')];
    $I['naloxone'] = ['Received naloxone', false, $od("AND o.data LIKE '%\"naloxone\":\"Yes\"%'")];
    $I['overdose_deaths'] = ['Deaths due to overdose', false, $od("AND o.data LIKE '%\"outcome\":\"Died\"%'")];
    $I['psychosocial'] = ['Received psychosocial interventions', false, m731_grid($conn, "patients p JOIN psychodar d ON d.mat_id = p.mat_id $C", "$PM AND d.visitDate BETWEEN '$start' AND '$endT'", $end, false)];
    return $I;
}

/** Flatten to key => value, e.g. newly_inducted.Male.15-19 => 4 (used for CSV and KHIS mapping). */
function moh731_flat(array $ind): array
{
    $o = [];
    foreach ($ind as $id => [$label, $bands, $g]) {
        foreach ($g as $sex => $cells) { foreach ($cells as $b => $n) { $o["$id.$sex.$b"] = $n; } }
    }
    return $o;
}

/** Push the mapped values for one period to KHIS (DHIS2). Returns [ok, message]. Needs config/khis.php (not committed). */
function moh731_push_khis(array $flat, string $period): array
{
    $cfgFile = __DIR__ . '/../config/khis.php';
    if (!is_file($cfgFile)) { return [false, 'KHIS is not configured: create config/khis.php (see config/khis.sample.php).']; }
    $cfg = require $cfgFile; $map = require __DIR__ . '/../config/khis_mapping.php';
    foreach (['url', 'username', 'password', 'orgUnit', 'dataSet'] as $k) { if (empty($cfg[$k])) { return [false, "config/khis.php is missing '$k'."]; } }
    $values = [];
    foreach ($flat as $key => $n) {
        if (!empty($map[$key]['de'])) { $values[] = ['dataElement' => $map[$key]['de'], 'categoryOptionCombo' => $map[$key]['coc'] ?? '', 'value' => (string)$n]; }
    }
    if (!$values) { return [false, 'No indicators are mapped yet: fill config/khis_mapping.php with the data element and category option combo UIDs from KHIS.']; }
    $body = json_encode(['dataSet' => $cfg['dataSet'], 'period' => str_replace('-', '', $period), 'orgUnit' => $cfg['orgUnit'], 'completeDate' => date('Y-m-d'), 'dataValues' => $values]);
    $ch = curl_init(rtrim($cfg['url'], '/') . '/api/dataValueSets?importStrategy=CREATE_AND_UPDATE');
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'], CURLOPT_USERPWD => $cfg['username'] . ':' . $cfg['password']]);
    $resp = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
    if ($resp === false) { return [false, 'Could not reach KHIS: ' . $err]; }
    $j = json_decode((string)$resp, true);
    $st = $j['response']['status'] ?? $j['status'] ?? '';
    if ($code >= 200 && $code < 300 && in_array($st, ['SUCCESS', 'OK', 'WARNING'], true)) {
        $c = $j['response']['importCount'] ?? [];
        return [true, "KHIS accepted the report ($st): imported " . (int)($c['imported'] ?? 0) . ', updated ' . (int)($c['updated'] ?? 0) . ', ignored ' . (int)($c['ignored'] ?? 0) . '.'];
    }
    return [false, "KHIS rejected the report (HTTP $code): " . substr((string)($j['message'] ?? $resp), 0, 300)];
}

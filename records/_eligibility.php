<?php
/**
 * Records-officer sections of the Health Records dashboard (included by records/dashboard.php):
 *   1. Check a prospective client (duplicate / previous enrolment / age) before registering
 *   2. Eligibility check of recently registered clients (required documents + recommended items)
 *   3. Registration data quality, enrolment cohorts, referral source, residence, time to first dispense
 * Needs: $conn, $m (escaped MFL), $C (collation clause), $fromYm/$toYm (period filter, carried through the forms).
 */
$keep = ($fromYm ? '<input type="hidden" name="from" value="' . htmlspecialchars((string)($fromYm)) . '"><input type="hidden" name="to" value="' . htmlspecialchars((string)($toYm)) . '">' : '');
$esc = fn($v) => $conn->real_escape_string((string)$v);

echo '<style>.sec{color:#2D008A;margin:26px 0 8px;font-size:18px}.chk{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:10px;align-items:end}.chk label{font-size:12px;font-weight:600;color:#4a3a7a;display:block}.chk input,.chk select{width:100%;padding:8px 10px;border:1px solid #cfc6ea;border-radius:6px}.ok{background:#e6f8ee;border:1px solid #04B04B}.warn{background:#fff6dc;border:1px solid #FFC12E}.bad{background:#fdeaec;border:1px solid #E41E39}.box{border-radius:8px;padding:10px 14px;margin:8px 0;font-size:13.5px}.tick{color:#04B04B;font-weight:700}.cross{color:#E41E39;font-weight:700}.dash{color:#aaa}.badge{display:inline-block;border-radius:10px;padding:1px 9px;font-size:11px;color:#fff;margin:1px 2px}</style>';

// =================================================================================================
// 1. Check a prospective client before registering
// =================================================================================================
$cid = trim((string)($_GET['chk_id'] ?? '')); $cname = trim((string)($_GET['chk_name'] ?? '')); $cdob = trim((string)($_GET['chk_dob'] ?? '')); $cphone = trim((string)($_GET['chk_phone'] ?? ''));
$asked = ($cid !== '' || $cname !== '' || $cphone !== '' || $cdob !== '');
echo '<h2 class="sec" id="eligibility">Check a new client before registering</h2>';
echo '<div class="card"><div class="b"><form method="get" action="#eligibility" class="chk">' . $keep
   . '<div><label>National ID / passport</label><input name="chk_id" value="' . htmlspecialchars((string)($cid)) . '" placeholder="e.g. 23456789"></div>'
   . '<div><label>Client name</label><input name="chk_name" value="' . htmlspecialchars((string)($cname)) . '" placeholder="First and/or last name"></div>'
   . '<div><label>Date of birth</label><input type="date" name="chk_dob" value="' . htmlspecialchars((string)($cdob)) . '" max="' . date('Y-m-d') . '"></div>'
   . '<div><label>Phone</label><input name="chk_phone" value="' . htmlspecialchars((string)($cphone)) . '" placeholder="07..."></div>'
   . '<div><button class="fb g" style="width:100%">Check eligibility</button></div></form>';
if ($asked) {
    // age rule
    if ($cdob !== '' && preg_match('/^\d{4}-\d\d-\d\d$/', $cdob)) {
        $ageNow = (int)date_diff(date_create($cdob), date_create('today'))->y;
        if ($ageNow < 15) echo '<div class="box bad"><b>Not eligible - age ' . $ageNow . '.</b> The client must be at least 15 years old. Refer to a child and adolescent service.</div>';
        elseif ($ageNow < 18) echo '<div class="box warn"><b>Age ' . $ageNow . ' - eligible with a guardian.</b> A parent/guardian must give consent and sign Form 3E, and their ID/name must be recorded.</div>';
        else echo '<div class="box ok"><b>Age ' . $ageNow . ' - meets the age requirement</b> (15 years or older).</div>';
    }
    $conds = [];
    if ($cid !== '') $conds[] = "p.nat_id = '" . $esc($cid) . "'";
    if ($cphone !== '') $conds[] = "p.client_phone = '" . $esc($cphone) . "'";
    if ($cname !== '') {
        $parts = array_filter(preg_split('/\s+/', $cname)); $nc = [];
        foreach ($parts as $pt) $nc[] = "(p.clientName LIKE '%" . $esc($pt) . "%' OR p.sname LIKE '%" . $esc($pt) . "%')";
        $conds[] = '(' . implode(' AND ', $nc) . ($cdob !== '' ? " AND p.dob = '" . $esc($cdob) . "'" : '') . ')';
    } elseif ($cdob !== '' && $cid === '' && $cphone === '') { $conds[] = "p.dob = '" . $esc($cdob) . "'"; }   // a date of birth alone is only a weak lead
    $hits = dash_rows($conn, "SELECT p.mat_id, p.clientName, p.sex, p.dob, p.current_status, p.reg_date, p.mat_status, p.reg_facility, p.nat_id, p.client_phone, (SELECT DATE(MAX(x.dispDate)) FROM pharmacy x WHERE x.mat_id = p.mat_id) last_disp FROM patients p WHERE " . implode(' OR ', $conds) . " ORDER BY p.reg_date DESC LIMIT 10");
    if (!$hits) {
        echo '<div class="box ok"><b>No existing record found.</b> Register as <b>New</b> if the client meets the checklist below (consent, initial clinical encounter, baseline toxicology and labs). <a href="../patients/add_clients.php">Open registration</a></div>';
    } else {
        echo '<div class="box warn"><b>' . count($hits) . ' existing record(s) found - do not register again as New.</b></div>';
        $advice = ['Active' => 'Already in care here. Open the record instead.', 'Defaulted' => 'Defaulted - follow up the client; if they return, update the record (not a new registration).', 'LTFU' => 'Lost to follow up - if they return use Update Patient and set the status to Re-induction.', 'Stopped' => 'Treatment was stopped - clinician review needed before re-induction.', 'Voluntary Discontinuation' => 'Discontinued voluntarily - clinician review, then Re-induction if eligible.', 'Weaned' => 'Previously weaned off. A clinician should assess before restarting treatment.', 'Transout' => 'Transferred out earlier - register as Transfer In with the MAT ID from the parent facility.', 'Transit' => 'On transit - continue under the transit record.', 'Dead' => 'Recorded as deceased - verify the identity before doing anything else.'];
        $rows = [];
        foreach ($hits as $h) {
            $rows[] = [htmlspecialchars((string)($h['mat_id'])), htmlspecialchars((string)($h['clientName'])), htmlspecialchars((string)($h['sex'] . ' / ' . $h['dob'])), htmlspecialchars((string)($h['reg_facility'])), htmlspecialchars((string)(trim((string)($h['mat_status'])))) . ' ' . htmlspecialchars((string)(substr($h['reg_date'], 0, 10))),
                '<b>' . htmlspecialchars((string)($h['current_status'])) . '</b>' . ($h['last_disp'] ? '<br><small>last dispensed ' . $h['last_disp'] . '</small>' : ''), htmlspecialchars((string)($advice[$h['current_status']] ?? 'Review the record.')) . ' <a href="../patients/view_patient.php?mat_id=' . urlencode((string)($h['mat_id'])) . '">Open</a>'];
        }
        echo '<table><tr><th>MAT ID</th><th>Client</th><th>Sex / DOB</th><th>Facility</th><th>Enrolled</th><th>Status</th><th>What to do</th></tr>';
        foreach ($rows as $r) echo '<tr>' . implode('', array_map(fn($c) => '<td>' . $c . '</td>', $r)) . '</tr>';
        echo '</table>';
    }
}
echo '</div></div>';

// =================================================================================================
// 2. Eligibility check of recently registered clients
// =================================================================================================
$days = (int)($_GET['elig_days'] ?? 90);
if (!in_array($days, [30, 90, 180, 365], true)) $days = 90;
$since = date('Y-m-d', strtotime((string)("-$days days")));
$rows = dash_rows($conn, "SELECT p.p_id, p.mat_id, p.clientName, p.sex, p.dob, p.reg_date, TRIM(p.mat_status) enrol, p.nat_id, p.client_phone, p.residence_scounty, p.p_address, p.rx_supporter_name, p.transfer_id, p.referring_facility,
    TIMESTAMPDIFF(YEAR, p.dob, p.reg_date) age_at_reg,
    EXISTS(SELECT 1 FROM client_consents c WHERE c.mat_id = p.mat_id $C AND c.consent_status NOT IN ('Declined','Withdrawn')) OR EXISTS(SELECT 1 FROM consents c2 WHERE c2.mat_id = p.mat_id $C AND c2.consent_status NOT IN ('Declined','Withdrawn')) has_consent,
    EXISTS(SELECT 1 FROM clinical_encounters e WHERE e.patient_id = p.p_id AND e.status = 'complete') has_encounter,
    EXISTS(SELECT 1 FROM toxicology_results t WHERE t.mat_id = p.mat_id $C AND (t.opiates = 'Positive' OR t.morphine = 'Positive') AND t.date_of_test <= DATE_ADD(p.reg_date, INTERVAL 45 DAY)) has_opioid_test,
    EXISTS(SELECT 1 FROM laboratory l WHERE l.mat_id = p.mat_id $C) has_labs,
    EXISTS(SELECT 1 FROM patients q WHERE q.p_id <> p.p_id AND ((p.nat_id IS NOT NULL AND p.nat_id <> '' AND q.nat_id = p.nat_id) OR (q.clientName = p.clientName AND q.dob = p.dob))) is_dup,
    EXISTS(SELECT 1 FROM photos ph WHERE ph.mat_id = p.mat_id $C) has_photo,
    EXISTS(SELECT 1 FROM fingerprints f WHERE f.mat_id = p.mat_id $C) has_fp,
    EXISTS(SELECT 1 FROM psychosocial_intake_form_1a s WHERE s.mat_id = p.mat_id $C) has_psy
    FROM patients p WHERE p.mflcode = '$m' AND p.reg_date >= '$since' AND TRIM(p.mat_status) IN ('New','Transfer In') ORDER BY p.reg_date DESC");

$req = ['age' => 'Age 15+', 'id' => 'ID / guardian', 'consent' => 'Consent (3E)', 'enc' => 'Initial encounter (3A)', 'src' => 'Opioid use / transfer doc', 'labs' => 'Baseline labs', 'dup' => 'Not a duplicate'];
$stats = array_fill_keys(array_keys($req), 0); $verdicts = ['Eligible' => 0, 'Incomplete' => 0, 'Review' => 0, 'Not eligible' => 0]; $attention = [];
$rec = ['phone' => 0, 'residence' => 0, 'photo' => 0, 'fp' => 0, 'psy' => 0];
foreach ($rows as $r) {
    $age = (int)$r['age_at_reg']; $isNew = $r['enrol'] === 'New';
    $ok = [
        'age' => $age >= 15,
        'id' => $r['nat_id'] !== null && $r['nat_id'] !== '' || ($age < 18 && trim((string)$r['rx_supporter_name']) !== ''),
        'consent' => (bool)$r['has_consent'],
        'enc' => (bool)$r['has_encounter'],
        'src' => $isNew ? (bool)$r['has_opioid_test'] : (trim((string)$r['transfer_id']) !== '' && trim((string)$r['referring_facility']) !== ''),
        'labs' => (bool)$r['has_labs'],
        'dup' => !$r['is_dup'],
    ];
    foreach ($ok as $k => $v) if ($v) $stats[$k]++;
    $missing = array_keys(array_filter($ok, fn($v) => !$v));
    $verdict = !$ok['age'] ? 'Not eligible' : (!$ok['dup'] ? 'Review' : (!$missing ? 'Eligible' : 'Incomplete'));
    $verdicts[$verdict]++;
    $rec['phone'] += trim((string)$r['client_phone']) !== ''; $rec['residence'] += (trim((string)$r['residence_scounty']) !== '' || trim((string)$r['p_address']) !== ''); $rec['photo'] += (bool)$r['has_photo']; $rec['fp'] += (bool)$r['has_fp']; $rec['psy'] += (bool)$r['has_psy'];
    if ($verdict !== 'Eligible') $attention[] = [$r, $ok, $verdict, $missing];
}
$n = count($rows);
echo '<h2 class="sec">Eligibility check of newly registered clients</h2>';
echo '<form method="get" action="#eligibility" class="filters" style="margin-bottom:12px">' . $keep . '<div><label>Registered in the last</label><select name="elig_days" style="padding:8px;border:1px solid #cfc6ea;border-radius:6px">';
foreach ([30 => '30 days', 90 => '90 days', 180 => '6 months', 365 => '12 months'] as $d => $lb) echo '<option value="' . $d . '"' . ($d === $days ? ' selected' : '') . '>' . $lb . '</option>';
echo '</select></div><button class="fb g">Apply</button><span style="font-size:12.5px;color:#666">Clients enrolled as <b>New</b> or <b>Transfer In</b> since ' . $since . '. Required: age 15+, ID (or guardian if under 18), signed consent, initial encounter, opioid-use evidence (positive baseline toxicology) or transfer documents, baseline labs, and no duplicate record.</span></form>';
dash_kpis([
    ['Registered (' . $days . ' days)', $n, 'New + Transfer In', DASH_PRIMARY],
    ['Eligible - all required items', $verdicts['Eligible'], $n ? round($verdicts['Eligible'] / $n * 100) . '%' : '', DASH_GREEN],
    ['Incomplete paperwork', $verdicts['Incomplete'], 'missing a required item', DASH_YELLOW],
    ['Needs review', $verdicts['Review'], 'possible duplicate record', DASH_RED],
    ['Not eligible', $verdicts['Not eligible'], 'under 15 years', DASH_RED],
]);
echo '<div class="grid">';
dash_bars('Required items on file (% of registered clients)', array_map(fn($k) => [$req[$k], $n ? round($stats[$k] / $n * 100, 1) : 0], array_keys($req)), DASH_GREEN);
dash_bars('Recommended items on file (% of registered clients)', [['Phone number', $n ? round($rec['phone'] / $n * 100, 1) : 0], ['Residence', $n ? round($rec['residence'] / $n * 100, 1) : 0], ['Psychosocial intake', $n ? round($rec['psy'] / $n * 100, 1) : 0], ['Photo captured', $n ? round($rec['photo'] / $n * 100, 1) : 0], ['Fingerprint captured', $n ? round($rec['fp'] / $n * 100, 1) : 0]], DASH_LILAC);
echo '</div>';
dash_card_open('Clients that need attention (' . count($attention) . ')');
if (!$attention) { echo '<div class="empty">Every client registered in this period has all required items.</div>'; }
else {
    echo '<table><tr><th>MAT ID</th><th>Client</th><th>Enrolment</th><th>Registered</th><th>Verdict</th><th>Missing / problem</th><th></th></tr>';
    $vc = ['Incomplete' => '#FFC12E', 'Review' => '#E41E39', 'Not eligible' => '#E41E39'];
    foreach (array_slice($attention, 0, 40) as [$r, $ok, $verdict, $missing]) {
        $badges = ''; foreach ($missing as $k) { $badges .= '<span class="badge" style="background:' . ($k === 'dup' || $k === 'age' ? '#E41E39' : '#AC80EE') . '">' . htmlspecialchars((string)($req[$k])) . '</span>'; }
        echo '<tr><td>' . htmlspecialchars((string)($r['mat_id'])) . '</td><td>' . htmlspecialchars((string)($r['clientName'])) . '</td><td>' . htmlspecialchars((string)($r['enrol'])) . '</td><td>' . substr($r['reg_date'], 0, 10) . '</td><td><span class="badge" style="background:' . $vc[$verdict] . ';color:' . ($verdict === 'Incomplete' ? '#4a3500' : '#fff') . '">' . $verdict . '</span></td><td>' . $badges . '</td><td><a href="../patients/view_patient.php?p_id=' . (int)$r['p_id'] . '">Open</a></td></tr>';
    }
    echo '</table>' . (count($attention) > 40 ? '<div class="empty">Showing the first 40 of ' . count($attention) . '.</div>' : '');
}
dash_card_close();
dash_card_open('Checklist for the most recent registrations');
if (!$rows) { echo '<div class="empty">No new registrations in this period.</div>'; }
else {
    echo '<table><tr><th>Client</th><th>Registered</th>' . implode('', array_map(fn($l) => '<th style="text-align:center;font-size:11px">' . htmlspecialchars((string)($l)) . '</th>', $req)) . '</tr>';
    foreach (array_slice($rows, 0, 12) as $r) {
        $age = (int)$r['age_at_reg']; $isNew = $r['enrol'] === 'New';
        $ok = ['age' => $age >= 15, 'id' => ($r['nat_id'] !== null && $r['nat_id'] !== '') || ($age < 18 && trim((string)$r['rx_supporter_name']) !== ''), 'consent' => (bool)$r['has_consent'], 'enc' => (bool)$r['has_encounter'],
               'src' => $isNew ? (bool)$r['has_opioid_test'] : (trim((string)$r['transfer_id']) !== '' && trim((string)$r['referring_facility']) !== ''), 'labs' => (bool)$r['has_labs'], 'dup' => !$r['is_dup']];
        echo '<tr><td>' . htmlspecialchars((string)($r['clientName'])) . '<br><small style="color:#888">' . htmlspecialchars((string)($r['mat_id'])) . ' &middot; ' . htmlspecialchars((string)($r['enrol'])) . '</small></td><td>' . substr($r['reg_date'], 0, 10) . '</td>';
        foreach ($ok as $v) echo '<td style="text-align:center">' . ($v ? '<span class="tick">&#10003;</span>' : '<span class="cross">&#10007;</span>') . '</td>';
        echo '</tr>';
    }
    echo '</table>';
}
dash_card_close();

// =================================================================================================
// 3. Registration data quality, cohorts, referral source, residence, time to first dispense
// =================================================================================================
echo '<h2 class="sec">Registration data quality &amp; enrolment analytics</h2><div class="grid">';
$tot = max(1, (int)dash_val($conn, "SELECT COUNT(*) FROM patients p WHERE p.mflcode='$m'"));
$pct = fn(string $sql) => round((int)dash_val($conn, $sql) / $tot * 100, 1);
$base = "FROM patients p WHERE p.mflcode='$m'";
dash_bars('Record completeness (% of all clients)', [
    ['National ID', $pct("SELECT COUNT(*) $base AND p.nat_id IS NOT NULL AND p.nat_id <> ''")],
    ['Phone number', $pct("SELECT COUNT(*) $base AND p.client_phone IS NOT NULL AND p.client_phone <> ''")],
    ['Residence', $pct("SELECT COUNT(*) $base AND (p.residence_scounty <> '' OR p.p_address <> '')")],
    ['Referral source', $pct("SELECT COUNT(*) $base AND p.referral_type IS NOT NULL AND p.referral_type <> ''")],
    ['Treatment supporter', $pct("SELECT COUNT(*) $base AND p.rx_supporter_name IS NOT NULL AND p.rx_supporter_name <> ''")],
    ['Signed consent', $pct("SELECT COUNT(*) $base AND EXISTS(SELECT 1 FROM client_consents c WHERE c.mat_id = p.mat_id $C)")],
    ['Initial encounter', $pct("SELECT COUNT(*) $base AND EXISTS(SELECT 1 FROM clinical_encounters e WHERE e.patient_id = p.p_id AND e.status='complete')")],
    ['Photo', $pct("SELECT COUNT(*) $base AND EXISTS(SELECT 1 FROM photos ph WHERE ph.mat_id = p.mat_id $C)")],
    ['Fingerprint', $pct("SELECT COUNT(*) $base AND EXISTS(SELECT 1 FROM fingerprints f WHERE f.mat_id = p.mat_id $C)")],
], DASH_PRIMARY);

$dups = dash_rows($conn, "SELECT p.nat_id, GROUP_CONCAT(CONCAT(p.clientName, ' (', p.mat_id, ')') SEPARATOR '<br>') who, COUNT(*) n FROM patients p WHERE p.mflcode='$m' AND p.nat_id IS NOT NULL AND p.nat_id <> '' GROUP BY p.nat_id HAVING n > 1 LIMIT 8");
$dupName = dash_rows($conn, "SELECT p.clientName, p.dob, GROUP_CONCAT(p.mat_id SEPARATOR ', ') ids, COUNT(*) n FROM patients p WHERE p.mflcode='$m' GROUP BY p.clientName, p.dob HAVING n > 1 LIMIT 8");
dash_table('Possible duplicate records', ['Match on', 'Records'], array_merge(
    array_map(fn($d) => ['National ID ' . htmlspecialchars((string)($d['nat_id'])), $d['who']], $dups),
    array_map(fn($d) => ['Name + DOB (' . htmlspecialchars((string)($d['dob'])) . ')', htmlspecialchars((string)($d['clientName'])) . ': ' . htmlspecialchars((string)($d['ids']))], $dupName)));

$coh = dash_rows($conn, "SELECT YEAR(p.reg_date) y, COUNT(*) n, SUM(p.current_status='Active') act, SUM(p.current_status IN ('Defaulted','LTFU')) lost, SUM(p.current_status='Weaned') wean, SUM(p.current_status='Transout') tout, SUM(p.current_status IN ('Dead','Stopped','Voluntary Discontinuation')) other FROM patients p WHERE p.mflcode='$m' GROUP BY y ORDER BY y DESC");
dash_table('Enrolment cohorts - where each year\'s clients are now', ['Year', 'Enrolled', 'Active', 'Defaulted / LTFU', 'Weaned', 'Transferred out', 'Dead / stopped / disc.', 'In care %'],
    array_map(fn($c) => [$c['y'], $c['n'], $c['act'], $c['lost'], $c['wean'], $c['tout'], $c['other'], '<b>' . round(($c['act'] + $c['wean']) / max(1, $c['n']) * 100) . '%</b>'], $coh));

dash_bars('Referral source of clients (top)', dash_pairs(dash_rows($conn, "SELECT COALESCE(NULLIF(TRIM(p.referral_type),''),'Not recorded') s, COUNT(*) n $base GROUP BY 1 ORDER BY n DESC LIMIT 8"), 's', 'n'), 'multi');
dash_bars('Residence - sub-county (top)', dash_pairs(dash_rows($conn, "SELECT COALESCE(NULLIF(TRIM(p.residence_scounty),''),'Not recorded') s, COUNT(*) n $base GROUP BY 1 ORDER BY n DESC LIMIT 8"), 's', 'n'), DASH_LILAC);
$tt = dash_rows($conn, "SELECT DATEDIFF((SELECT MIN(x.dispDate) FROM pharmacy x WHERE x.mat_id = p.mat_id), p.reg_date) d FROM patients p WHERE p.mflcode='$m' AND TRIM(p.mat_status)='New' AND p.reg_date >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)");
$buckets = ['Same day' => 0, '1-3 days' => 0, '4-7 days' => 0, '8-14 days' => 0, 'Over 14 days' => 0, 'Never dispensed' => 0];
foreach ($tt as $r) { $d = $r['d']; if ($d === null) $buckets['Never dispensed']++; elseif ($d <= 0) $buckets['Same day']++; elseif ($d <= 3) $buckets['1-3 days']++; elseif ($d <= 7) $buckets['4-7 days']++; elseif ($d <= 14) $buckets['8-14 days']++; else $buckets['Over 14 days']++; }
dash_bars('Time from registration to first dispensing (New clients, last 12 months)', array_map(null, array_keys($buckets), array_values($buckets)), DASH_GREEN);
dash_donut('Age at registration (all clients)', dash_pairs(dash_rows($conn, "SELECT CASE WHEN a < 18 THEN '15-17' WHEN a < 25 THEN '18-24' WHEN a < 35 THEN '25-34' WHEN a < 45 THEN '35-44' ELSE '45+' END b, COUNT(*) n FROM (SELECT TIMESTAMPDIFF(YEAR, p.dob, p.reg_date) a FROM patients p WHERE p.mflcode='$m') z GROUP BY b ORDER BY MIN(a)"), 'b', 'n'));
echo '</div>';

<?php
/**
 * Part B of the Initial Clinical Encounter: collecting posted answers, pre-filling a saved encounter or draft,
 * section drafts, facility lookup and the controlled-drug hand-over to patients.drugname / dosage.
 */
require_once __DIR__ . '/pharmacy_helpers.php';

const PARTB_TEXT = ['abdomen_examination','allergies_other','chest_examination','ears_examination','extremities_examination','eyes_examination','family_medical_other','general_appearance','genitalia_examination','head_examination','heart_examination','hiv_facility_care','initial_dose','medication_other','mental_health_medication_details','mental_health_other','mouth_throat_examination','musculoskeletal_examination','neck_examination','neurological_examination','nose_examination','other_diagnoses','other_medical_problems','pregnancy_status','skin_examination','treatment_plan','diagnosis_opioid_use','pregnancy_weeks','medication_prescribed'];
const PARTB_YN = ['breastfeeding','contraception_use','family_drug_use','family_mental_health','mental_health_diagnosis','mental_health_medication','psychiatric_hospitalization','suicidal_thoughts','patient_consent'];
const PARTB_DATE = ['hiv_diagnosis_date','last_menstrual_period','next_appointment'];
const PARTB_LISTS = ['allergies','contraception_method','mental_health_condition','family_medical_conditions'];   // stored as comma separated text
const PARTB_MAPS = ['medical_history','medical_medication'];                                                   // stored as JSON {key: value}

const CONTRACEPTION_MALE = ['male_condom', 'withdrawal', 'male_sterilization'];

/** Controlled-drug choices on the treatment plan (single choice). */
function partb_medication_choices(): array
{
    return ['methadone' => 'Methadone', 'buprenorphine' => 'Buprenorphine', 'bup_naloxone' => 'Buprenorphine/Naloxone', 'naltrexone' => 'Naltrexone', 'other' => 'Other (choose from products)'];
}

/** Doses a clinician can pick for each choice: [value => label]. */
function partb_dose_options(mysqli $conn): array
{
    $meth = []; for ($d = 5; $d <= 150; $d += 5) { $meth[(string)$d] = "$d mg (" . rtrim(rtrim(number_format($d / METHADONE_MG_PER_ML, 2, '.', ''), '0'), '.') . " mL oral solution)"; }
    $strengths = bup_strengths($conn); $bup = [];
    for ($d = 2; $d <= 32; $d += 2) {
        $split = bup_default_split((float)$d, $strengths);
        $bup[(string)$d] = "$d mg" . ($split ? ' (' . bup_describe($split, $strengths) . ')' : '');
    }
    $nal = [];
    foreach (dash_rows_pb($conn, "SELECT drugName FROM drug WHERE drugName LIKE 'Naltrexone%' ORDER BY drugName") as $r) { $nal[$r['drugName']] = $r['drugName']; }
    return ['methadone' => $meth, 'buprenorphine' => $bup, 'bup_naloxone' => $bup, 'naltrexone' => $nal];
}
function dash_rows_pb(mysqli $conn, string $sql): array { $r = $conn->query($sql); return $r ? $r->fetch_all(MYSQLI_ASSOC) : []; }

/** Drugs usable as "Other" (single select), from the drug table. */
function partb_products(mysqli $conn): array
{
    return array_column(dash_rows_pb($conn, "SELECT DISTINCT TRIM(drugName) n FROM drug WHERE drugName <> '' ORDER BY n"), 'n');
}
function partb_bup_naloxone_products(mysqli $conn): array
{
    return array_column(dash_rows_pb($conn, "SELECT TRIM(drugName) n FROM drug WHERE drugName LIKE 'Buprenorphine%/Naloxone%' ORDER BY n"), 'n');
}

/** Facility lookup for "HIV facility of care": returns ["Name (MFL)", ...]. */
function partb_facility_search(mysqli $conn, string $q): array
{
    $like = '%' . $conn->real_escape_string($q) . '%';
    return array_map(fn($r) => $r['facilityname'] . ' (' . $r['mflcode'] . ')', dash_rows_pb($conn, "SELECT facilityname, mflcode FROM facilities WHERE facilityname LIKE '$like' OR mflcode LIKE '$like' ORDER BY facilityname LIMIT 25"));
}
/** Accepts "Name (MFL)" or a plain name that exists in the facilities table; returns the canonical text or null. */
function partb_facility_canonical(mysqli $conn, string $v): ?string
{
    $v = trim($v); if ($v === '') return null;
    $mfl = preg_match('/\((\d+)\)\s*$/', $v, $m) ? $m[1] : null;
    $st = $conn->prepare($mfl ? "SELECT facilityname, mflcode FROM facilities WHERE mflcode = ? LIMIT 1" : "SELECT facilityname, mflcode FROM facilities WHERE facilityname = ? LIMIT 1");
    $key = $mfl ?: $v; $st->bind_param('s', $key); $st->execute();
    $r = $st->get_result()->fetch_assoc(); $st->close();
    return $r ? $r['facilityname'] . ' (' . $r['mflcode'] . ')' : null;
}

/** Collect posted Part B answers into [set[], vals[]] for an UPDATE of clinical_encounters. */
function partb_collect(mysqli $conn, array $post, string $sex, string $clinician_name): array
{
    $isMale = strtolower(trim($sex)) === 'male';
    $set = []; $vals = [];
    foreach (PARTB_TEXT as $c) {
        $v = trim((string)($post[$c] ?? ''));
        if ($c === 'hiv_facility_care') { $v = (string)partb_facility_canonical($conn, $v); }
        $set[] = "$c = ?"; $vals[] = $v === '' ? null : $v;
    }
    foreach (PARTB_YN as $c) { $v = strtolower(trim((string)($post[$c] ?? ''))); $set[] = "$c = ?"; $vals[] = in_array($v, ['yes', 'no'], true) ? $v : null; }
    foreach (PARTB_DATE as $c) { $v = trim((string)($post[$c] ?? '')); $set[] = "$c = ?"; $vals[] = preg_match('/^\d{4}-\d\d-\d\d$/', $v) ? $v : null; }
    foreach (PARTB_LISTS as $c) {
        $a = array_values(array_filter(array_map('strval', (array)($post[$c] ?? [])), 'strlen'));
        if ($c === 'contraception_method') {
            if (($post['contraception_use'] ?? '') !== 'yes') { $a = []; }
            elseif ($isMale) { $a = array_values(array_intersect($a, CONTRACEPTION_MALE)); }
            else { $a = array_values(array_diff($a, CONTRACEPTION_MALE)); }
        }
        $set[] = "$c = ?"; $vals[] = $a ? implode(',', $a) : null;
    }
    foreach (PARTB_MAPS as $c) {
        $a = array_filter(array_map(fn($x) => in_array($x, ['yes', 'no'], true) || $c === 'medical_medication' ? trim((string)$x) : '', (array)($post[$c] ?? [])), 'strlen');
        $set[] = "$c = ?"; $vals[] = $a ? json_encode($a) : null;
    }
    $set[] = "clinician_name = ?"; $vals[] = $clinician_name;
    $set[] = "clinician_signature = ?"; $vals[] = function_exists('transfer_signature') ? transfer_signature($clinician_name) : $clinician_name;
    return [$set, $vals];
}

/** Flat name => value|list map used to fill the form from a saved encounter row. */
function partb_prefill(array $row): array
{
    $o = [];
    foreach (array_merge(PARTB_TEXT, PARTB_YN, PARTB_DATE) as $c) { if (isset($row[$c]) && $row[$c] !== '') { $o[$c] = (string)$row[$c]; } }
    foreach (PARTB_LISTS as $c) { if (!empty($row[$c])) { $o[$c . '[]'] = array_values(array_filter(array_map('trim', explode(',', (string)$row[$c])), 'strlen')); } }
    foreach (PARTB_MAPS as $c) { foreach ((array)json_decode((string)($row[$c] ?? ''), true) as $k => $v) { $o[$c . '[' . $k . ']'] = (string)$v; } }
    return $o;
}

/** Controlled choice -> patients.drugname + dosage. Returns [drugname, dose] or null when nothing should change. */
function partb_patient_drug(mysqli $conn, array $post): ?array
{
    $choice = (string)($post['medication_prescribed'] ?? '');
    $dose = is_numeric($post['initial_dose'] ?? '') ? (float)$post['initial_dose'] : null;
    if ($choice === 'methadone') { return ['Methadone', $dose]; }
    if ($choice === 'buprenorphine' && $dose) {
        $strengths = bup_strengths($conn);
        $split = bup_stock_split($dose, $strengths, bup_stock_available($conn, $strengths)) ?: bup_default_split($dose, $strengths);
        $best = null;
        foreach ($split as $name => $n) { if ($best === null || $n > $split[$best] || ($n == $split[$best] && ($strengths[$name]['mg'] ?? 0) > ($strengths[$best]['mg'] ?? 0))) { $best = $name; } }
        return [$best ?? 'Buprenorphine', $dose];
    }
    if ($choice === 'bup_naloxone') {
        $p = trim((string)($post['medication_other'] ?? ''));
        if ($p !== '' && in_array($p, partb_bup_naloxone_products($conn), true)) { return [$p, $dose]; }
    }
    return null;
}

function partb_draft_ensure(mysqli $conn): void
{
    $r = $conn->query("SHOW COLUMNS FROM clinical_encounter_drafts LIKE 'triage_id'");
    if ($r && $r->num_rows === 0) { $conn->query("ALTER TABLE clinical_encounter_drafts ADD COLUMN triage_id INT DEFAULT NULL, ADD INDEX (triage_id)"); }
}
function partb_draft_load(mysqli $conn, int $patientId, int $triageId): array
{
    $st = $conn->prepare("SELECT form_data, current_section FROM clinical_encounter_drafts WHERE patient_id = ? AND triage_id = ? ORDER BY id DESC LIMIT 1");
    $st->bind_param('ii', $patientId, $triageId); $st->execute();
    $r = $st->get_result()->fetch_assoc(); $st->close();
    return $r ? ['data' => json_decode((string)$r['form_data'], true) ?: [], 'sections' => array_values(array_filter(explode(',', (string)$r['current_section'])))] : ['data' => [], 'sections' => []];
}
function partb_draft_save(mysqli $conn, int $patientId, int $triageId, int $clinicianId, array $data, string $section): array
{
    $cur = partb_draft_load($conn, $patientId, $triageId);
    $secs = array_values(array_unique(array_merge($cur['sections'], [$section])));
    $json = json_encode($data, JSON_UNESCAPED_UNICODE); $cs = implode(',', $secs);
    $st = $conn->prepare("SELECT id FROM clinical_encounter_drafts WHERE patient_id = ? AND triage_id = ? LIMIT 1"); $st->bind_param('ii', $patientId, $triageId); $st->execute();
    $id = $st->get_result()->fetch_row()[0] ?? null; $st->close();
    if ($id) { $u = $conn->prepare("UPDATE clinical_encounter_drafts SET form_data = ?, current_section = ?, clinician_id = ? WHERE id = ?"); $u->bind_param('ssii', $json, $cs, $clinicianId, $id); }
    else { $u = $conn->prepare("INSERT INTO clinical_encounter_drafts (patient_id, triage_id, form_data, current_section, clinician_id) VALUES (?,?,?,?,?)"); $u->bind_param('iissi', $patientId, $triageId, $json, $cs, $clinicianId); }
    $u->execute(); $u->close();
    return $secs;
}

/** Put the chosen controlled drug and dose on the client record (patients.drugname / dosage). */
function partb_apply_patient_drug(mysqli $conn, int $patientId, array $post): void
{
    $pd = partb_patient_drug($conn, $post);
    if ($pd) {
        $name = $pd[0]; $dose = $pd[1];
        $u = $conn->prepare("UPDATE patients SET drugname = ?, dosage = COALESCE(?, dosage) WHERE p_id = ?");
        $u->bind_param('sdi', $name, $dose, $patientId); $u->execute(); $u->close();
    } elseif (!empty($post['initial_dose']) && is_numeric($post['initial_dose'])) {
        $d = (float)$post['initial_dose'];
        $u = $conn->prepare("UPDATE patients SET dosage = ? WHERE p_id = ?"); $u->bind_param('di', $d, $patientId); $u->execute(); $u->close();
    }
}

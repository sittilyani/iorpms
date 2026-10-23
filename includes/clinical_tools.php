<?php
/**
 * MOH clinical tools for the clinician: Alcohol (AUDIT) screening, Opioid overdose encounter, STI treatment,
 * PLHIV link-facility tracking and the gender dysphoria screen. One generic table keeps every form's answers as JSON.
 * Also holds the lab request (Form 3D) schema so clinician and laboratory pages share it.
 */
function ct_ensure_schema(mysqli $conn): void
{
    $conn->query("CREATE TABLE IF NOT EXISTS clinical_tool_entries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        form_key VARCHAR(30) NOT NULL,
        patient_id INT NOT NULL,
        mat_id VARCHAR(50) NOT NULL,
        client_name VARCHAR(255) NOT NULL,
        form_date DATE NOT NULL,
        data LONGTEXT NOT NULL,
        score INT DEFAULT NULL,
        summary VARCHAR(255) DEFAULT NULL,
        created_by VARCHAR(255) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX (form_key), INDEX (patient_id), INDEX (mat_id), INDEX (form_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
}

function lab_ensure_schema(mysqli $conn): void
{
    $conn->query("CREATE TABLE IF NOT EXISTS lab_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        mat_id VARCHAR(50) NOT NULL,
        client_name VARCHAR(255) NOT NULL,
        sex VARCHAR(20) DEFAULT NULL,
        enrol_date DATE DEFAULT NULL,
        visit_type VARCHAR(30) NOT NULL,
        tests TEXT NOT NULL,
        extra_tests VARCHAR(255) DEFAULT NULL,
        notes VARCHAR(255) DEFAULT NULL,
        status ENUM('pending','done') NOT NULL DEFAULT 'pending',
        requested_by VARCHAR(255) NOT NULL,
        requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        results TEXT,
        completed_by VARCHAR(255) DEFAULT NULL,
        completed_at DATETIME DEFAULT NULL,
        INDEX (status), INDEX (mat_id), INDEX (requested_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
}

/** Tests on Form 3D: key => label. */
function lab_tests(): array
{
    return [
        'Urine toxicology' => ['COC' => 'Cocaine (COC)', 'AMP' => 'Amphetamine (AMP)', 'MET' => 'Methamphetamine (MET)', 'THC' => 'Marijuana (THC)', 'MTD' => 'Methadone (MTD)', 'BUP' => 'Buprenorphine (BUP)', 'MOP' => 'Morphine (MOP)', 'OPI' => 'Opiates (OPI)', 'PCP' => 'Phencyclidine (PCP)', 'BAR' => 'Barbiturates (BAR)', 'BZO' => 'Benzodiazepine (BZO)', 'OTH' => 'Other drugs'],
        'Other tests' => ['HIV' => 'HIV', 'HBV' => 'Hepatitis B', 'HCV' => 'Hepatitis C', 'VDRL' => 'VDRL', 'PREG' => 'Pregnancy', 'VL' => 'Viral load'],
    ];
}
function lab_test_label(string $k): string
{
    foreach (lab_tests() as $g) { if (isset($g[$k])) return $g[$k]; }
    return $k;
}

const CT_KP = ['FSW' => 'FSW', 'MSM' => 'MSM', 'MSW' => 'MSW', 'PWID' => 'PWID', 'PWUD' => 'PWUD', 'TG MAN' => 'TG MAN', 'TG WOMAN' => 'TG WOMAN'];
const CT_VP = ['Fisherfolks' => 'Fisherfolks', 'Truckers' => 'Truckers', 'Discordant Couples' => 'Discordant Couples', 'Persons in Prison' => 'Persons in Prison'];
const CT_YN = ['Yes' => 'Yes', 'No' => 'No'];

function ct_freq(): array { return ['0' => '(0) Never', '1' => '(1) Less than monthly', '2' => '(2) Monthly', '3' => '(3) Weekly', '4' => '(4) Daily or almost daily']; }

/** Form definitions: sections => fields [key, label, type, options, full-width]. */
function ct_forms(): array
{
    $yn = CT_YN; $f = [];
    $f['audit'] = ['title' => 'Alcohol Abuse Screening Tool (AUDIT)', 'icon' => 'fa-glass', 'color' => '#2D008A', 'blurb' => 'Ten-question AUDIT; a total over 8 means alcohol use disorder is likely.', 'sections' => [
        'Key population details' => [['kp_type', 'KP type', 'checks', CT_KP, 1], ['vp_type', 'VP type', 'checks', CT_VP, 1], ['hotspot', 'KP hotspot / injecting site', 'text'], ['alt_phone', 'Alternate phone', 'text']],
        'AUDIT questions (code in standard drinks)' => [
            ['q1', '1. How often do you have a drink containing alcohol?', 'radio', ['0' => '(0) Never', '1' => '(1) Monthly or less', '2' => '(2) 2 to 4 times a month', '3' => '(3) 2 to 3 times a week', '4' => '(4) 4 or more times a week'], 1],
            ['q2', '2. How many drinks containing alcohol do you have on a typical day when you are drinking?', 'radio', ['0' => '(0) 1 or 2', '1' => '(1) 3 or 4', '2' => '(2) 5 or 6', '3' => '(3) 7, 8 or 9', '4' => '(4) 10 or more'], 1],
            ['q3', '3. How often do you have six or more drinks on one occasion?', 'radio', ct_freq(), 1],
            ['q4', '4. How often during the last year have you found that you were not able to stop drinking once you had started?', 'radio', ct_freq(), 1],
            ['q5', '5. How often during the last year have you failed to do what was normally expected from you because of drinking?', 'radio', ct_freq(), 1],
            ['q6', '6. How often during the last year have you needed a first drink in the morning to get yourself going after a heavy drinking session?', 'radio', ct_freq(), 1],
            ['q7', '7. How often during the last year have you had a feeling of guilt or remorse after drinking?', 'radio', ct_freq(), 1],
            ['q8', '8. How often during the last year have you been unable to remember what happened the night before because you had been drinking?', 'radio', ct_freq(), 1],
            ['q9', '9. Have you or someone else been injured as a result of your drinking?', 'radio', ['0' => '(0) No', '2' => '(2) Yes, but not in the last year', '4' => '(4) Yes, during the last year'], 1],
            ['q10', '10. Has a relative, friend, doctor or other health worker been concerned about your drinking or suggested you cut down?', 'radio', ['0' => '(0) No', '2' => '(2) Yes, but not in the last year', '4' => '(4) Yes, during the last year'], 1]],
        'Remarks' => [['remarks', 'Remarks', 'textarea', null, 1]]]];
    $f['overdose'] = ['title' => 'Opioid Overdose Encounter Form', 'icon' => 'fa-medkit', 'color' => '#E41E39', 'blurb' => 'Record an overdose incident: site, substance, naloxone, risk factors and outcome.', 'sections' => [
        'Incident' => [['alt_phone', 'Alternate phone', 'text'], ['incident_type', 'Incident type', 'radio', ['New' => 'New', 'Recurrent' => 'Recurrent']], ['site_name', 'Name of the incident site (hotspot)', 'text'],
            ['site_type', 'Type of site', 'select', ['Street', 'Injecting den', 'Uninhabitable building', 'Parks', 'Homes', 'Beach', 'Casino', 'Bar with lodging', 'Bar without lodging', 'Sex den', 'Strip club', 'Highways', 'Brothel', 'Guest house/hotel and lodging', 'Massage parlor', 'Changaa den', 'Barber shop/salon', 'Others']],
            ['site_other', 'Other site (specify)', 'text'],
            ['opioid', 'Suspected opioid substance used', 'checks', ['Heroine' => 'Heroine', 'Pethidine' => 'Pethidine', 'Morphine' => 'Morphine', 'Unknown' => 'Unknown'], 1],
            ['naloxone', 'Naloxone provided', 'radio', $yn],
            ['other_drugs', 'Any other drug used other than opioid', 'checks', ['Methadone' => 'Methadone', 'Cannabis' => 'Cannabis', 'Flunitrazepam' => 'Flunitrazepam (Tap tap, Bugizi)', 'Alcohol' => 'Alcohol', 'Cocaine' => 'Cocaine', 'Others' => 'Others'], 1],
            ['other_drug_specify', 'Other drug (specify)', 'text'],
            ['risk', 'Risk factors', 'checks', ['Age' => 'Age', 'Comorbidity' => 'Comorbidity', 'Abstinence' => 'Abstinence from opioid use', 'Mixing' => 'Mixing', 'MAT induction' => 'MAT induction / re-induction', 'Others' => 'Others'], 1],
            ['risk_other', 'Other risk factor (specify)', 'text']],
        'Outcome' => [['outcome', 'Outcome', 'radio', ['Recovered' => 'Recovered', 'Died' => 'Died']], ['referred_facility', 'Referred facility', 'text'], ['remarks', 'Remarks', 'textarea', null, 1], ['attendee', 'Attendee name', 'text']]]];
    $f['sti'] = ['title' => 'STI Treatment Data Collection Form', 'icon' => 'fa-stethoscope', 'color' => '#04B04B', 'blurb' => 'Syndromes seen, drugs prescribed, condoms/lubricant and partner referral.', 'sections' => [
        'Client' => [['hotspot', 'KP/VP hotspot / injecting / operating site', 'text'], ['new_client', 'New client in programme?', 'radio', $yn], ['kp_type', 'KP type', 'checks', CT_KP, 1], ['vp_type', 'Vulnerable population type', 'checks', CT_VP, 1]],
        'Type of syndrome / infection' => [['syndromes', 'Tick as appropriate', 'checks', array_combine($a = ['Genital ulcer disease', 'Pelvic inflammatory disease', 'Urethral discharge / urethritis', 'Genital warts', 'Cervicitis', 'Syphilis', 'Herpes genitalia', 'Pharyngeal discharge', 'Vaginitis', 'Anal warts', 'Anal discharge', 'Anal rectal ulcer', 'Pharyngeal ulcer', 'Orchitis'], $a), 1], ['syndrome_other', 'Other syndrome (specify)', 'text']],
        'Drug prescription' => [['drugs', 'Tick as appropriate', 'checks', array_combine($b = ['Erythromycin', 'Inj. Ceftriaxone', 'Podophyllin', 'Ciprofloxacin', 'Acyclovir tablets', 'Paracetamol', 'Inj. Benzathine penicillin', 'Ibuprofen', 'Cefixime', 'Inj. Gentamycin', 'Amoxycillin', 'Doxycycline', 'Clotrimazole pessaries', 'Azithromycin', 'Suppositories', 'Metronidazole', 'Fluconazole', 'Acyclovir cream', 'Clotrimazole cream'], $b), 1], ['drug_other', 'Other drug (specify)', 'text']],
        'Services' => [['ref_lab', 'Client referred for lab investigation', 'radio', $yn], ['ref_facility', 'Client referred to other health facilities', 'radio', $yn], ['condom', 'Condom given', 'radio', ['No' => 'No', 'Yes' => 'Yes', 'N/A' => 'N/A']], ['condom_num', 'Number of condoms', 'number'], ['lube', 'Lubricant given', 'radio', ['No' => 'No', 'Yes' => 'Yes', 'N/A' => 'N/A']], ['lube_num', 'Number of lubricants', 'number'], ['partner_ref', 'Partner referral done', 'radio', $yn], ['genital_exam', 'Was genital examination done', 'radio', $yn], ['next_visit', 'Date of next visit', 'date'], ['comments', "Clinical provider's comments", 'textarea', null, 1], ['provider', 'Clinical service provider name', 'text']]]];
    $f['linkfac'] = ['title' => 'PLHIV Link Facility Documentation Tracking Form', 'icon' => 'fa-link', 'color' => '#6B3FD1', 'blurb' => 'Track ART, viral load and services for clients linked to a CCC / link facility.', 'sections' => [
        'Link facility details' => [['lf_county', 'County', 'text'], ['lf_subcounty', 'Sub county', 'text'], ['lf_ward', 'Ward', 'text'], ['lf_name', 'Facility / DIC name', 'text'], ['lf_mfl', 'MFL code', 'text'], ['ccc', 'CCC number', 'text'], ['kp_type', 'KP type', 'checks', ['FSW' => 'FSW', 'MSM' => 'MSM', 'MSW' => 'MSW', 'PWID' => 'PWID', 'TG' => 'TG', 'Other' => 'Other'], 1], ['kp_other', 'Other KP (specify)', 'text'], ['vp_type', 'VP type', 'checks', ['FFX' => 'FFX', 'Trackers' => 'Trackers', 'Prison' => 'People in prison setting', 'DC' => 'DC'], 1]],
        'HIV care' => [['date_diagnosed', '1. Date diagnosed', 'date'], ['date_art', '2. Date initiated ART', 'date'], ['orig_regimen', '2a. Original regimen (in full)', 'text'], ['cur_regimen', '3. Current ART regimen (in full)', 'text'], ['date_switch', '3a. Date switched (blank if not switched)', 'date'], ['switch_reason', '4. Reason for switch (or N/A)', 'text'], ['last_visit', '5. Date of latest visit', 'date'], ['next_visit', 'Date of next visit', 'date'], ['vl_collected', 'Date VL sample collected', 'date'], ['vl_received', 'Date VL results received', 'date'], ['vl_copies', 'Viral copies', 'text']],
        'Other services' => [['pssg_enrolled', '6. Enrolled in PSSG', 'radio', $yn], ['pssg_attended', 'Attended PSSG', 'radio', ['Yes' => 'Yes', 'No' => 'No', 'N/A' => 'N/A']], ['pmtct', '7. On PMTCT', 'radio', ['Yes' => 'Yes', 'No' => 'No', 'N/A' => 'N/A']], ['edd', 'Date of delivery / EDD', 'date'],
            ['tb_screen', 'TB screening', 'radio', $yn], ['cervical', 'Cervical cancer screening', 'radio', ['Yes' => 'Yes', 'No' => 'No', 'N/A' => 'N/A']], ['sti_tx', 'STI treatment', 'radio', $yn], ['trauma', 'Trauma counselling', 'radio', ['Yes' => 'Yes', 'No' => 'No', 'N/A' => 'N/A']], ['fp', 'Family planning', 'radio', ['Yes' => 'Yes', 'No' => 'No', 'N/A' => 'N/A']],
            ['on_tb', '9. Currently on TB treatment', 'radio', $yn], ['tb_date', 'Date initiated TB treatment', 'date'], ['tpt', '10. TPT status', 'radio', ['On TPT' => 'On TPT', 'Completed' => 'Completed', 'Declined' => 'Declined', 'Never initiated' => 'Never initiated']], ['tpt_date', 'TPT date initiated', 'date'],
            ['collected_via', '11. Data collected through', 'radio', ['Visiting facility' => 'Visiting facility', 'Calling facility' => 'Calling facility', 'Self-reported' => 'Self-reported', 'Other' => 'Other']], ['collected_other', 'Other (specify)', 'text'], ['completed_by', 'Completed by', 'text'], ['designation', 'Designation', 'text']]]];
    $gd = [];
    foreach (['g1' => 'Do you feel your gender identity is at odds with your biological sex?', 'g2' => 'Do you dislike your genitals and secondary sex characteristics (such as facial hair or breasts) of your assigned gender?', 'g3' => 'Would you be happier if you had the primary or secondary sex characteristics of the opposite gender? For example, have you considered procedures to physically alter your sexual characteristics?', 'g4' => 'Do you have a desire to be of the other gender or some alternative gender different from your assigned gender?', 'g5' => 'Do you wish that the people in your life would treat you the same way they treat males (if your assigned gender is female) or females (if your assigned gender is male)?', 'g6' => 'Are you experiencing distress or difficulties in social, occupational or other important areas of functioning as a result of the disconnect between your natural gender and your expressed/preferred gender?'] as $k => $l) { $gd[] = [$k, $l, 'radio', $yn, 1]; }
    $f['gender'] = ['title' => 'Gender Dysphoria Screening Tool', 'icon' => 'fa-venus-mars', 'color' => '#AC80EE', 'blurb' => 'Six questions; two or more YES answers (present for at least six months) is a positive screen.', 'sections' => ['Questions' => $gd, 'Notes' => [['remarks', 'Remarks / action taken', 'textarea', null, 1]]]];
    return $f;
}

/** Score + one-line summary for a submitted form. */
function ct_score(string $key, array $d): array
{
    if ($key === 'audit') {
        $t = 0; for ($i = 1; $i <= 10; $i++) { $t += (int)($d["q$i"] ?? 0); }
        return [$t, $t > 8 ? "AUDIT $t: alcohol use disorder likely - refer" : "AUDIT $t: low risk / not likely"];
    }
    if ($key === 'gender') {
        $y = 0; for ($i = 1; $i <= 6; $i++) { if (($d["g$i"] ?? '') === 'Yes') $y++; }
        return [$y, $y >= 2 ? "Positive screen ($y of 6 Yes) - refer for assessment" : "Negative screen ($y of 6 Yes)"];
    }
    if ($key === 'overdose') { return [null, 'Outcome: ' . ($d['outcome'] ?? 'not recorded') . '; naloxone ' . ($d['naloxone'] ?? 'n/a')]; }
    if ($key === 'sti') { return [null, count((array)($d['syndromes'] ?? [])) . ' syndrome(s), ' . count((array)($d['drugs'] ?? [])) . ' drug(s)']; }
    if ($key === 'linkfac') { return [null, 'ART: ' . ($d['cur_regimen'] ?? 'n/a') . '; VL ' . ($d['vl_copies'] ?? 'n/a')]; }
    return [null, ''];
}

function ct_h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES); }

/** Render one field as an input (editable) or as a value (view). */
function ct_field(array $fd, array $vals, bool $edit): string
{
    [$k, $l, $t] = $fd; $o = $fd[3] ?? null; $full = !empty($fd[4]);
    $v = $vals[$k] ?? ($t === 'checks' ? [] : '');
    $cls = $full ? 'ctf full' : 'ctf';
    $out = '<div class="' . $cls . '"><label>' . ct_h($l) . '</label>';
    if (!$edit) {
        $show = is_array($v) ? implode(', ', $v) : (string)$v;
        if ($t === 'radio' && $o && isset($o[$show])) $show = $o[$show];
        return $out . '<div class="val">' . ($show === '' ? '<span class="na">-</span>' : nl2br(ct_h($show))) . '</div></div>';
    }
    if ($t === 'text' || $t === 'date' || $t === 'number') {
        $out .= '<input type="' . $t . '" name="' . $k . '" value="' . ct_h($v) . '"' . ($t === 'number' ? ' min="0"' : '') . '>';
    } elseif ($t === 'textarea') {
        $out .= '<textarea name="' . $k . '" rows="3">' . ct_h($v) . '</textarea>';
    } elseif ($t === 'select') {
        $out .= '<select name="' . $k . '"><option value="">-- select --</option>';
        foreach ($o as $x) { $out .= '<option' . ($v === $x ? ' selected' : '') . '>' . ct_h($x) . '</option>'; }
        $out .= '</select>';
    } else {
        $out .= '<div class="opts">';
        foreach ($o as $ov => $ol) {
            $ov = (string)$ov;
            if ($t === 'radio') { $out .= '<label class="opt"><input type="radio" name="' . $k . '" value="' . ct_h($ov) . '"' . ((string)$v === $ov ? ' checked' : '') . '> ' . ct_h($ol) . '</label>'; }
            else { $out .= '<label class="opt"><input type="checkbox" name="' . $k . '[]" value="' . ct_h($ov) . '"' . (in_array($ov, (array)$v, true) ? ' checked' : '') . '> ' . ct_h($ol) . '</label>'; }
        }
        $out .= '</div>';
    }
    return $out . '</div>';
}

/** Keep only the posted answers that belong to the form. */
function ct_collect(array $form, array $post): array
{
    $d = [];
    foreach ($form['sections'] as $fields) {
        foreach ($fields as $fd) {
            $k = $fd[0]; $t = $fd[2];
            if ($t === 'checks') { $d[$k] = array_values(array_map('strval', (array)($post[$k] ?? []))); }
            else { $d[$k] = trim((string)($post[$k] ?? '')); }
        }
    }
    return $d;
}

function ct_css(): string
{
    return '<style>
.ctbar{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px}.ctbtn{background:#2D008A;color:#fff;border:0;border-radius:8px;padding:9px 20px;text-decoration:none;cursor:pointer;font-size:14px}.ctbtn.g{background:#04B04B}.ctbtn.o{background:#fff;color:#2D008A;border:1px solid #2D008A}
.ctsec{background:#fff;border:1px solid #e5e0f2;border-radius:10px;margin-bottom:16px;overflow:hidden}.ctsec h3{margin:0;padding:10px 16px;background:#f3effb;color:#2D008A;font-size:14px;border-bottom:3px solid #AC80EE}
.ctgrid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px 18px;padding:14px 16px}.ctf.full{grid-column:1/-1}.ctf label{font-weight:600;font-size:12.5px;color:#2D008A;display:block;margin-bottom:4px}
.ctf input[type=text],.ctf input[type=date],.ctf input[type=number],.ctf select,.ctf textarea{width:100%;padding:8px 10px;border:1px solid #cfc6ea;border-radius:6px;font-size:14px}.ctf input:focus,.ctf select:focus,.ctf textarea:focus{outline:none;border-color:#2D008A;box-shadow:0 0 0 3px rgba(45,0,138,.15)}
.opts{display:flex;flex-wrap:wrap;gap:6px 14px}.opt{font-weight:400!important;color:#222!important;font-size:13.5px!important;display:inline-flex!important;align-items:center;gap:5px}.val{padding:6px 0;border-bottom:1px dotted #cfc6ea;min-height:28px}.na{color:#aaa}
.ctscore{background:#fff;border:2px solid #04B04B;border-radius:10px;padding:14px 18px;margin-bottom:16px;font-size:15px}.ctscore b{color:#2D008A}
.ctmenu{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px}.ctcard{background:#fff;border:1px solid #e5e0f2;border-top:5px solid var(--c);border-radius:12px;padding:18px;text-decoration:none;color:#222;display:block;box-shadow:0 2px 8px rgba(45,0,138,.06)}.ctcard:hover{box-shadow:0 6px 18px rgba(45,0,138,.18)}.ctcard i{font-size:28px;color:var(--c)}.ctcard h4{margin:8px 0 4px;color:#2D008A;font-size:16px}.ctcard p{margin:0;font-size:13px;color:#555}
.ctmsg{background:#e6f8ee;border:1px solid #04B04B;border-radius:8px;padding:12px 16px;margin-bottom:14px}
@media print{.top,.ctbar,.noprint{display:none!important}.wrap{padding:0}.ctsec{break-inside:avoid}}
</style>';
}

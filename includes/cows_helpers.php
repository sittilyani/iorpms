<?php
/** BMI interpretation and the COWS (Clinical Opiate Withdrawal Scale) with four timed assessments. */

/** BMI category from a BMI value (WHO adult cut-offs). */
function bmi_interpret($bmi): string
{
    if ($bmi === null || $bmi === '' || (float)$bmi <= 0) return '';
    $b = (float)$bmi;
    return $b < 18.5 ? 'underweight' : ($b < 25 ? 'normal' : ($b < 30 ? 'overweight' : 'obesity'));
}
function bmi_interpret_label(string $k): string
{
    return ['underweight' => 'Underweight (below 18.5)', 'normal' => 'Normal (18.5 - 24.9)', 'overweight' => 'Overweight (25 - 29.9)', 'obesity' => 'Obesity (30 and above)'][$k] ?? '';
}

/** COWS items: label => [score => description]. */
function cows_symptoms(): array
{
    return [
        'Resting Pulse Rate' => ['0' => 'pulse rate 80 or below', '1' => 'pulse rate 81-100', '2' => 'pulse rate 101-120', '4' => 'pulse rate greater than 120'],
        'Sweating' => ['0' => 'no report of chills or flushing', '1' => 'subjective report of chills or flushing', '2' => 'flushed or observable moistness on face', '3' => 'beads of sweat on brow or face', '4' => 'sweat streaming off face'],
        'Restlessness' => ['0' => 'able to sit still', '1' => 'reports difficulty sitting still, but is able to do so', '3' => 'frequent shifting or extraneous movements of legs/arms', '5' => 'unable to sit still for more than a few seconds'],
        'Pupil size' => ['0' => 'pupils pinned or normal size for room light', '1' => 'pupils possibly larger than normal for room light', '2' => 'pupils moderately dilated', '5' => 'pupils so dilated that only the rim of the iris is visible'],
        'Bone or Joint aches' => ['0' => 'not present', '1' => 'mild diffuse discomfort', '2' => 'patient reports severe diffuse aching of joints/muscles', '4' => 'patient is rubbing joints or muscles and is unable to sit still because of discomfort'],
        'Runny nose or tearing' => ['0' => 'not present', '1' => 'nasal stuffiness or unusually moist eyes', '2' => 'nose running or tearing', '4' => 'nose constantly running or tears streaming down cheeks'],
        'GI Upset' => ['0' => 'no GI symptoms', '1' => 'stomach cramps', '2' => 'nausea or loose stool', '3' => 'vomiting or diarrhea', '5' => 'multiple episodes of diarrhea or vomiting'],
        'Tremor' => ['0' => 'no tremor', '1' => 'tremor can be felt, but not observed', '2' => 'slight tremor observable', '4' => 'gross tremor or muscle twitching'],
        'Yawning' => ['0' => 'no yawning', '1' => 'yawning once or twice during assessment', '2' => 'yawning three or more times during assessment', '4' => 'yawning several times/minute'],
        'Anxiety or Irritability' => ['0' => 'none', '1' => 'patient reports increasing irritability or anxiousness', '2' => 'patient obviously irritable anxious', '4' => 'patient so irritable or anxious that participation in the assessment is difficult'],
        'Gooseflesh skin' => ['0' => 'skin is smooth', '3' => 'piloerrection of skin can be felt or hairs standing up on arms', '5' => 'prominent piloerrection'],
    ];
}
function cows_interpret(int $total): string
{
    return $total > 36 ? 'severe' : ($total >= 25 ? 'moderately_severe' : ($total >= 13 ? 'moderate' : ($total >= 5 ? 'mild' : 'none')));
}
function cows_interpret_label(string $k): string
{
    return ['none' => 'No active withdrawal (below 5)', 'mild' => 'Mild (5-12)', 'moderate' => 'Moderate (13-24)', 'moderately_severe' => 'Moderately severe (25-36)', 'severe' => 'Severe (above 36)'][$k] ?? '';
}

/** Saved COWS state of a triage row: [time => ['scores'=>[idx=>score], 'total'=>n, 'interp'=>k, 'at'=>, 'by'=>]] for the completed times only. */
function cows_state(?array $triage): array
{
    $j = $triage && !empty($triage['cows_scores']) ? json_decode((string)$triage['cows_scores'], true) : [];
    $out = [];
    foreach ((array)$j as $t => $x) { if (isset($x['scores']) && count($x['scores']) === count(cows_symptoms())) { $out[(int)$t] = $x; } }
    return $out;
}

/**
 * Merge newly posted scores (cows[time][index]) into the saved state. Completed times are never overwritten;
 * a time is only completed when all 11 items are chosen. Returns [state, warnings].
 */
function cows_collect(array $post, array $state, string $by): array
{
    $warn = []; $n = count(cows_symptoms());
    for ($t = 1; $t <= 4; $t++) {
        if (isset($state[$t])) continue;
        $raw = (array)($post['cows'][$t] ?? []); $sc = [];
        for ($i = 0; $i < $n; $i++) {
            $v = $raw[$i] ?? '';
            if ($v !== '' && is_numeric($v)) { $sc[$i] = (int)$v; }
        }
        if (count($sc) === $n) {
            $tot = array_sum($sc);
            $state[$t] = ['scores' => $sc, 'total' => $tot, 'interp' => cows_interpret($tot), 'at' => date('Y-m-d H:i'), 'by' => $by];
        } elseif ($sc) { $warn[] = "Assessment $t was not saved: only " . count($sc) . " of $n items were scored. Score every item to complete it."; }
    }
    ksort($state);
    return [$state, $warn];
}

function cows_persist(mysqli $conn, int $triageId, array $state, string $provider): void
{
    $sc = []; $tot = []; $int = [];
    foreach ($state as $t => $x) { $sc[$t] = $x; $tot[$t] = $x['total']; $int[$t] = $x['interp']; }
    $a = json_encode((object)$sc); $b = json_encode((object)$tot); $c = json_encode((object)$int);
    $st = $conn->prepare("UPDATE triage_services SET cows_scores = ?, cows_totals = ?, cows_interpretations = ?, cows_provider = ?, cows_date = CURDATE() WHERE id = ?");
    $st->bind_param('ssssi', $a, $b, $c, $provider, $triageId); $st->execute(); $st->close();
}

/** The COWS table: completed times show their fixed score, remaining times are filled in and score themselves live. */
function cows_render(array $state): void
{
    $sym = cows_symptoms(); $done = count($state);
    echo '<div style="background:#f3effb;border-left:5px solid #2D008A;border-radius:8px;padding:10px 14px;margin-bottom:12px;font-size:14px"><b>' . $done . ' of 4 assessments completed</b>' . ($done < 4 ? ' &mdash; ' . (4 - $done) . ' remaining. Completed assessments are locked; fill the remaining columns whenever each one is done.' : ' &mdash; all four are complete.') . '</div>';
    echo '<table class="cows-table"><thead><tr><th>Symptom</th><th>Score guide</th>';
    for ($t = 1; $t <= 4; $t++) {
        echo '<th>Time ' . $t . (isset($state[$t]) ? '<br><small style="font-weight:400">' . htmlspecialchars((string)($state[$t]['at'] ?? '')) . '<br>' . htmlspecialchars((string)($state[$t]['by'] ?? '')) . '</small>' : '<br><small style="font-weight:400;color:#a87500">pending</small>') . '</th>';
    }
    echo '</tr></thead><tbody>';
    $i = 0;
    foreach ($sym as $name => $opts) {
        echo '<tr><td class="symptom">' . htmlspecialchars($name) . '</td><td style="font-size:12px">';
        foreach ($opts as $s => $d) { echo '<div><b>' . $s . '</b>: ' . htmlspecialchars($d) . '</div>'; }
        echo '</td>';
        for ($t = 1; $t <= 4; $t++) {
            if (isset($state[$t])) {
                $v = $state[$t]['scores'][$i] ?? '';
                echo '<td style="background:#e6f8ee;text-align:center;font-weight:700;color:#04803a" title="' . htmlspecialchars((string)($opts[(string)$v] ?? '')) . '">' . $v . '</td>';
            } else {
                echo '<td><select name="cows[' . $t . '][' . $i . ']" class="cows-sel" data-t="' . $t . '" style="width:64px"><option value="">-</option>';
                foreach ($opts as $s => $d) { echo '<option value="' . $s . '">' . $s . '</option>'; }
                echo '</select></td>';
            }
        }
        echo '</tr>'; $i++;
    }
    echo '<tr style="background:#f3effb"><td colspan="2"><strong>Total score</strong></td>';
    for ($t = 1; $t <= 4; $t++) {
        echo isset($state[$t]) ? '<td style="text-align:center;font-weight:700">' . $state[$t]['total'] . '</td>' : '<td style="text-align:center;font-weight:700" id="cowsTot' . $t . '">0</td>';
    }
    echo '</tr><tr style="background:#f3effb"><td colspan="2"><strong>Scale interpretation</strong></td>';
    $col = ['none' => '#555', 'mild' => '#04803a', 'moderate' => '#a87500', 'moderately_severe' => '#c0580a', 'severe' => '#E41E39'];
    for ($t = 1; $t <= 4; $t++) {
        if (isset($state[$t])) { $k = $state[$t]['interp']; echo '<td style="font-weight:700;font-size:12px;color:' . ($col[$k] ?? '#222') . '">' . htmlspecialchars(cows_interpret_label($k)) . '</td>'; }
        else { echo '<td style="font-weight:700;font-size:12px" id="cowsInt' . $t . '">-</td>'; }
    }
    echo '</tr></tbody></table>';
    echo '<script>(function(){
  function lab(n){return n>36?["Severe (above 36)","#E41E39"]:n>=25?["Moderately severe (25-36)","#c0580a"]:n>=13?["Moderate (13-24)","#a87500"]:n>=5?["Mild (5-12)","#04803a"]:["No active withdrawal (below 5)","#555"];}
  function upd(){for(var t=1;t<=4;t++){var s=document.querySelectorAll(".cows-sel[data-t=\'"+t+"\']");if(!s.length)continue;var n=0,f=0;s.forEach(function(e){if(e.value!==""){n+=parseInt(e.value,10);f++;}});var a=document.getElementById("cowsTot"+t),b=document.getElementById("cowsInt"+t);if(a)a.textContent=n;if(b){if(f===0){b.textContent="-";b.style.color="";}else{var l=lab(n);b.textContent=l[0]+(f<s.length?" (so far)":"");b.style.color=l[1];}}}}
  document.addEventListener("change",function(e){if(e.target.classList&&e.target.classList.contains("cows-sel"))upd();});upd();
})();</script>';
}

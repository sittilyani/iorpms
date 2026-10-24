<?php
/**
 * KHIS (DHIS2) submission for MOH 731 Plus, Form P7 and Form P8.
 * Nothing is sent until someone logs in to KHIS with an account that has data-entry rights: that login is typed in the
 * submit window, used for this one submission and never stored. The server only keeps the facility's KHIS address,
 * organisation unit and data set UIDs (config/khis.php) and the indicator mapping (config/khis_mapping.php).
 */
require_once __DIR__ . '/moh731_indicators.php';

function khis_config(): array
{
    $f = __DIR__ . '/../config/khis.php';
    return is_file($f) ? (array)(require $f) : [];
}

/** Basic-auth JSON call to KHIS. Returns [http code, decoded body, curl error]. */
function khis_call(string $method, string $url, string $user, string $pass, ?array $body = null): array
{
    $ch = curl_init($url);
    $opts = [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60, CURLOPT_CUSTOMREQUEST => $method, CURLOPT_USERPWD => $user . ':' . $pass, CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json']];
    if ($body !== null) { $opts[CURLOPT_POSTFIELDS] = json_encode($body); }
    curl_setopt_array($ch, $opts);
    $resp = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
    return [$code, $resp === false ? null : json_decode((string)$resp, true), $err];
}

/** Confirm the KHIS login works, has data-entry rights and covers this facility. Returns null when fine, otherwise the reason. */
function khis_check_login(array $cfg, string $user, string $pass): ?string
{
    $base = rtrim((string)$cfg['url'], '/');
    [$code, $me, $err] = khis_call('GET', $base . '/api/me?fields=username,authorities,organisationUnits[id,name]', $user, $pass);
    if ($code === 0) return 'Could not reach KHIS (' . ($err ?: 'no connection') . '). Check the internet connection and try again.';
    if ($code === 401 || $code === 403) return 'KHIS did not accept that username and password.';
    if ($code !== 200 || !is_array($me)) return 'KHIS returned an unexpected answer (HTTP ' . $code . ').';
    $auth = (array)($me['authorities'] ?? []);
    if (!in_array('ALL', $auth, true) && !in_array('F_DATAVALUE_ADD', $auth, true)) return 'This KHIS account (' . ($me['username'] ?? $user) . ') does not have data-entry rights, so it cannot submit reports.';
    if (!in_array('ALL', $auth, true)) {
        $mine = array_column((array)($me['organisationUnits'] ?? []), 'id');
        $ok = in_array($cfg['orgUnit'], $mine, true);
        if (!$ok) {   // a county or sub-county account covers the facilities below it
            [$c2, $ou] = khis_call('GET', $base . '/api/organisationUnits/' . rawurlencode((string)$cfg['orgUnit']) . '?fields=ancestors[id]', $user, $pass);
            $ok = $c2 === 200 && array_intersect($mine, array_column((array)($ou['ancestors'] ?? []), 'id'));
        }
        if (!$ok) return 'This KHIS account is not assigned to this facility, so it cannot submit its report.';
    }
    return null;
}

/** Map the report's values to KHIS data elements and send them. $values is key => number (see config/khis_mapping.php). */
function khis_submit(string $report, string $period, array $values, string $user, string $pass): array
{
    $cfg = khis_config();
    foreach (['url', 'orgUnit'] as $k) { if (empty($cfg[$k])) return [false, "KHIS is not set up on this computer: config/khis.php is missing '$k'."]; }
    $dataSet = $cfg['dataSets'][$report] ?? ($cfg['dataSet'] ?? '');
    if ($dataSet === '') return [false, "KHIS is not set up for this report: add its data set UID to config/khis.php ('dataSets' => ['$report' => '...'])."];
    $map = (array)(require __DIR__ . '/../config/khis_mapping.php');
    $dv = [];
    foreach ($values as $key => $v) {
        if (!empty($map[$key]['de']) && $v !== '' && $v !== null) { $dv[] = ['dataElement' => $map[$key]['de'], 'categoryOptionCombo' => $map[$key]['coc'] ?? '', 'value' => (string)$v]; }
    }
    if (!$dv) return [false, 'Nothing to send yet: the figures on this report are not linked to KHIS data elements. Fill config/khis_mapping.php first.'];
    if ($why = khis_check_login($cfg, $user, $pass)) return [false, $why];
    [$code, $j, $err] = khis_call('POST', rtrim((string)$cfg['url'], '/') . '/api/dataValueSets?importStrategy=CREATE_AND_UPDATE', $user, $pass,
        ['dataSet' => $dataSet, 'period' => str_replace('-', '', $period), 'orgUnit' => $cfg['orgUnit'], 'completeDate' => date('Y-m-d'), 'dataValues' => $dv]);
    if ($code === 0) return [false, 'Could not reach KHIS (' . ($err ?: 'no connection') . ').'];
    $st = $j['response']['status'] ?? $j['status'] ?? '';
    if ($code >= 200 && $code < 300 && in_array($st, ['SUCCESS', 'OK', 'WARNING'], true)) {
        $c = $j['response']['importCount'] ?? [];
        return [true, "KHIS accepted the report ($st): " . (int)($c['imported'] ?? 0) . ' new, ' . (int)($c['updated'] ?? 0) . ' updated, ' . (int)($c['ignored'] ?? 0) . ' ignored of ' . count($dv) . ' values sent.'];
    }
    return [false, "KHIS rejected the report (HTTP $code): " . substr((string)($j['message'] ?? json_encode($j)), 0, 300)];
}

function khis_log_schema(mysqli $conn): void
{
    $conn->query("CREATE TABLE IF NOT EXISTS khis_submissions (id INT AUTO_INCREMENT PRIMARY KEY, report VARCHAR(20) NOT NULL, period VARCHAR(7) NOT NULL, app_user VARCHAR(150), khis_user VARCHAR(150), ok TINYINT(1) NOT NULL, message VARCHAR(400), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
}

/**
 * Button + login window for a report page. $report: moh731 | p7 | p8. $period: YYYY-MM.
 * For p7 and p8 the figures are read from the named fields on the page; for moh731 the server calculates them.
 */
function khis_submit_ui(string $report, string $period, string $endpoint = 'khis_submit.php'): void
{
    $names = ['moh731' => 'MOH 731 Plus', 'p7' => 'Form P7', 'p8' => 'Form P8'];
    ?>
    <style>
    .khis-btn{background:#04B04B;color:#fff;border:0;border-radius:6px;padding:9px 18px;font-weight:600;cursor:pointer;margin:20px 0 0 10px}
    #khisBack{display:none;position:fixed;inset:0;background:rgba(20,0,60,.55);z-index:99999;align-items:flex-start;justify-content:center;overflow:auto}
    #khisBox{background:#fff;border-radius:14px;max-width:460px;width:92%;margin:60px auto;padding:0;box-shadow:0 12px 40px rgba(0,0,0,.35);font-family:Segoe UI,Arial,sans-serif;overflow:hidden}
    #khisBox h3{margin:0;background:linear-gradient(135deg,#2D008A,#4a14b8);color:#fff;padding:14px 20px;font-size:17px}
    #khisBox .kb{padding:18px 20px}#khisBox label{display:block;font-weight:600;color:#2D008A;font-size:13px;margin:10px 0 4px}
    #khisBox input{width:100%;padding:9px 10px;border:1px solid #cfc6ea;border-radius:6px;font-size:14px;box-sizing:border-box}
    #khisBox .note{background:#f3effb;border-left:4px solid #2D008A;border-radius:6px;padding:8px 12px;font-size:12.5px;color:#333}
    #khisMsg{margin-top:10px;font-size:13.5px;font-weight:600}
    @media print{.khis-btn,#khisBack{display:none!important}}
    </style>
    <button type="button" class="khis-btn" id="khisOpen"><i class="fa fa-cloud-upload"></i> Submit to KHIS</button>
    <div id="khisBack"><div id="khisBox">
        <h3>Submit <?php echo htmlspecialchars($names[$report] ?? $report); ?> to KHIS</h3>
        <div class="kb">
            <div class="note">Log in with <b>your own KHIS account</b>. It must have <b>data-entry rights</b> for this facility. The login is used for this submission only and is not saved.</div>
            <p style="margin:10px 0 0;font-size:13px">Reporting period: <b><?php echo htmlspecialchars(date('F Y', strtotime($period . '-01'))); ?></b></p>
            <label for="khisUser">KHIS username</label><input id="khisUser" type="text" autocomplete="off">
            <label for="khisPass">KHIS password</label><input id="khisPass" type="password" autocomplete="off">
            <div style="display:flex;gap:10px;margin-top:14px"><button type="button" class="khis-btn" style="margin:0" id="khisSend">Log in and submit</button><button type="button" id="khisCancel" style="background:#fff;color:#2D008A;border:1px solid #2D008A;border-radius:6px;padding:9px 18px;cursor:pointer">Close</button></div>
            <div id="khisMsg"></div>
        </div>
    </div></div>
    <script>
    (function () {
        var back = document.getElementById('khisBack'), msg = document.getElementById('khisMsg'), send = document.getElementById('khisSend');
        function vals() {
            var o = {}; document.querySelectorAll('input[name], textarea[name], select[name]').forEach(function (e) {
                if (e.closest('#khisBack') || e.type === 'password' || e.type === 'hidden' || e.type === 'submit' || e.type === 'button') return;
                if (e.value !== '' && !isNaN(parseFloat(e.value)) && isFinite(e.value)) o[<?php echo json_encode($report); ?> + '.' + e.name] = e.value;
            }); return o;
        }
        document.getElementById('khisOpen').onclick = function () { msg.textContent = ''; back.style.display = 'flex'; document.getElementById('khisUser').focus(); };
        document.getElementById('khisCancel').onclick = function () { back.style.display = 'none'; document.getElementById('khisPass').value = ''; };
        send.onclick = function () {
            var u = document.getElementById('khisUser').value.trim(), p = document.getElementById('khisPass').value;
            if (!u || !p) { msg.style.color = '#E41E39'; msg.textContent = 'Enter your KHIS username and password.'; return; }
            send.disabled = true; msg.style.color = '#555'; msg.textContent = 'Checking your KHIS login and sending...';
            fetch(<?php echo json_encode($endpoint); ?>, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ report: <?php echo json_encode($report); ?>, period: <?php echo json_encode($period); ?>, username: u, password: p, values: vals() }) })
                .then(function (r) { return r.json(); })
                .then(function (j) { msg.style.color = j.ok ? '#04803a' : '#E41E39'; msg.textContent = j.message; if (j.ok) { document.getElementById('khisPass').value = ''; } })
                .catch(function () { msg.style.color = '#E41E39'; msg.textContent = 'The submission could not be completed. Try again.'; })
                .finally(function () { send.disabled = false; });
        };
    })();
    </script>
    <?php
}

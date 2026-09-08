<?php
/**
 * secugen_api.php — Native PHP Bridge for SecuGen WebAPI & Scanner
 * =================================================================
 * Runs directly inside Laragon on Port 80. Proxies requests to local SecuGen
 * WebAPI service (https://127.0.0.1:8443/SGIFPCapture), bypassing CORS and SSL
 * certificate errors in the browser.
 */
session_start();
set_time_limit(30); // A real capture round-trip can take up to ~20s on some scanners/drivers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    exit(0);
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'health';
$port = isset($_GET['port']) ? intval($_GET['port']) : (isset($_POST['port']) ? intval($_POST['port']) : 8443);

$possible_ports = array_unique([$port, 8443, 8000, 8080, 8001, 3002]);

/**
 * Cheap TCP connect check so we don't waste seconds sending a real
 * SGIFPCapture request (which now engages the sensor) to ports nothing is
 * listening on.
 */
function isPortOpen($port, $timeoutSec = 0.3) {
    $fp = @fsockopen('127.0.0.1', $port, $errno, $errstr, $timeoutSec);
    if ($fp) {
        fclose($fp);
        return true;
    }
    return false;
}

// SecuGen WebAPI rejects requests with ErrorCode 10004 ("WebAPI may not have
// received the origin header from the browser") unless an Origin header is
// present, but this is a server-side PHP proxy so browsers don't send one.
$webApiOrigin = (($_SERVER['HTTPS'] ?? '') === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

/**
 * Send HTTP/HTTPS cURL request to local SecuGen WebAPI service.
 *
 * $paramsInBody: SGIFPCapture's params are small scalars and work fine as a
 * URL query string, but SGIMatchScore's Template1/Template2 are large
 * base64 blobs - passed as query-string params the WebAPI fails to parse
 * them (returns a generic ErrorCode 100) even though the same values work
 * when sent as a POST body. Pass true for endpoints that take template data.
 */
function callSecuGenWebAPI($endpoint, $params = [], $targetPort = 8443, $method = 'POST', $timeout = 12, $paramsInBody = false) {
    global $webApiOrigin;
    $protocols = ['https', 'http'];

    foreach ($protocols as $proto) {
        $queryString = (!empty($params) && !$paramsInBody) ? '?' . http_build_query($params) : '';
        $url = "{$proto}://127.0.0.1:{$targetPort}/{$endpoint}{$queryString}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Origin: {$webApiOrigin}"]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $paramsInBody ? http_build_query($params) : '');
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($response !== false && ($httpCode == 200 || $httpCode == 400)) {
            $json = json_decode($response, true);
            if ($json !== null) {
                return ['success' => true, 'data' => $json, 'port' => $targetPort, 'proto' => $proto];
            }
        }
    }

    return ['success' => false, 'error' => $curlErr ?: "HTTP Code $httpCode"];
}

// Minimum SGIMatchScore (0-199) accepted as a genuine match. This is a
// commonly used default for 1:1/1:N verification with SecuGen readers.
define('SECUGEN_MATCH_THRESHOLD', 40);

/**
 * Compare two fingerprint templates using the WebAPI's own minutiae-based
 * matcher (SGIMatchScore), not a naive byte comparison - two captures of the
 * same finger are never byte-identical, so byte comparison always fails.
 * Returns the 0-199 matching score, or null if the WebAPI could not be
 * reached or returned an error.
 */
function secuGenMatchScore($tmpl1B64, $tmpl2B64, $possible_ports) {
    if ($tmpl1B64 === $tmpl2B64) {
        return 199; // identical template, no need to ask the WebAPI
    }

    foreach ($possible_ports as $p) {
        $res = callSecuGenWebAPI('SGIMatchScore', [
            'Template1' => $tmpl1B64,
            'Template2' => $tmpl2B64,
            // This device/driver always outputs ISO-format templates
            // regardless of what TemplateFormat is requested at capture
            // time (see the 'capture' action below) - telling SGIMatchScore
            // it's ANSI makes it reject a valid, self-matching template with
            // ErrorCode 103 "Invalid template".
            'TemplateFormat' => 'ISO'
        ], $p, 'POST', 10, true);

        if ($res['success']) {
            $data = $res['data'];
            if (($data['ErrorCode'] ?? -1) === 0) {
                return (int)($data['MatchingScore'] ?? 0);
            }
            return null; // WebAPI responded but rejected the templates
        }
    }

    return null; // could not reach the WebAPI at all
}

/**
 * Ensure the SecuGen WebAPI client service (SgiBioSrv / sgibiosrv.exe) is
 * running on Windows. The installer names/locations vary by SDK version, so
 * check the known candidates.
 */
function ensureSecuGenServiceRunning() {
    if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
        return;
    }

    $candidates = [
        'sgibiosrv.exe' => 'C:\\Program Files\\SecuGen\\SgiBioSrv\\sgibiosrv.exe',
        'SecuGenWebAPI.exe' => 'C:\\Program Files\\SecuGen\\SecuGen WebAPI\\SecuGenWebAPI.exe',
    ];

    foreach ($candidates as $processName => $secugenExe) {
        if (!file_exists($secugenExe)) {
            continue;
        }

        $output = @shell_exec('tasklist /FI "IMAGENAME eq ' . $processName . '" 2>nul');
        if (strpos($output, $processName) === false) {
            // Launch the WebAPI client in background
            @pclose(@popen('start /B "" "' . $secugenExe . '"', "r"));
            usleep(1500000); // 1.5s delay for service initialization
        }
        return; // Found and handled a valid install; no need to check others
    }
}

// Ensure background service is running
ensureSecuGenServiceRunning();

// Only probe ports something is actually listening on
$possible_ports = array_values(array_filter($possible_ports, 'isPortOpen'));

// Action Handlers
switch ($action) {
    case 'health':
    case 'test':
        // A real SGIFPCapture round-trip physically engages the sensor and
        // can take 10+ seconds on some devices/drivers, which is far too
        // slow for a connectivity check. A TCP connect is enough to confirm
        // the WebAPI client service is up and listening.
        if (!empty($possible_ports)) {
            $connectedPort = $possible_ports[0];
            $connectedProto = ($connectedPort === 8443) ? 'https' : 'http';
            echo json_encode([
                'success' => true,
                'status' => 'online',
                'service' => 'SecuGen WebAPI (PHP Bridge)',
                'port' => $connectedPort,
                'protocol' => $connectedProto,
                'message' => "SecuGen WebAPI active on port {$connectedPort}"
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'status' => 'offline',
                'message' => 'SecuGen WebAPI service not responding. Please connect SecuGen USB scanner.'
            ]);
        }
        break;

    case 'capture':
        $res = null;
        foreach ($possible_ports as $p) {
            $res = callSecuGenWebAPI('SGIFPCapture', [
                'Timeout' => '10000',
                'Quality' => '50',
                // This device/driver always outputs ISO-format templates
                // regardless of what's requested here, but declare it
                // explicitly so SGIMatchScore is told the truth later.
                'TemplateFormat' => 'ISO',
                'ImageWidth' => '260',
                'ImageHeight' => '300',
                'ImageDPI' => '500'
            ], $p, 'POST', 20);

            if ($res['success']) break;
        }

        if ($res && $res['success']) {
            $data = $res['data'];
            $errorCode = $data['ErrorCode'] ?? -1;

            if ($errorCode === 0) {
                echo json_encode([
                    'success' => true,
                    'fingerprint_data_base64' => $data['BMPBase64'] ?? '',
                    'fingerprint_template' => $data['TemplateBase64'] ?? '',
                    'quality_score' => $data['ImageQuality'] ?? 85,
                    'image_width' => $data['ImageWidth'] ?? 260,
                    'image_height' => $data['ImageHeight'] ?? 300,
                    'message' => 'SecuGen fingerprint captured successfully via PHP'
                ]);
            } else {
                // Per SecuGen WebAPI Programmer's Manual (SG1-0121A-008)
                $errMsgs = [
                    1 => "Fingerprint reader not correctly installed or driver files error",
                    2 => "Wrong type of fingerprint reader or not correctly installed",
                    3 => "Invalid parameters to sensor API",
                    5 => "SecuGen DLL load failed",
                    6 => "SecuGen driver DLL load failed",
                    7 => "SecuGen algorithm DLL load failed",
                    51 => "System file load failure",
                    52 => "Sensor chip initialization failed - No SecuGen scanner device attached",
                    53 => "Sensor line dropped - Failed to initialize SecuGen scanner device",
                    54 => "Timeout - No finger detected on SecuGen scanner",
                    55 => "SecuGen device not found",
                    56 => "SecuGen driver load failed",
                    57 => "Wrong image captured, please try again",
                    58 => "Lack of USB bandwidth",
                    59 => "SecuGen device busy",
                    60 => "Cannot get serial number of the device",
                    61 => "Unsupported SecuGen device",
                    101 => "Very low minutiae count, please try again",
                    102 => "Wrong template type",
                    103 => "Invalid template",
                    104 => "Invalid template",
                    105 => "Could not extract fingerprint features",
                    106 => "Match failed",
                    1000 => "SecuGen WebAPI: no memory",
                    2000 => "SecuGen WebAPI internal error",
                    3000 => "SecuGen WebAPI internal error (extended)",
                    4000 => "Invalid parameter passed to SecuGen WebAPI",
                    6000 => "SecuGen WebAPI certificate error - cannot decode",
                    10001 => "SecuGen WebAPI license error",
                    10002 => "SecuGen WebAPI invalid domain for license",
                    10003 => "SecuGen WebAPI license expired",
                    10004 => "SecuGen WebAPI did not receive an Origin header from the request",
                ];
                $msg = $errMsgs[$errorCode] ?? "SecuGen Error Code: {$errorCode}";
                echo json_encode([
                    'success' => false,
                    'message' => $msg,
                    'error_code' => $errorCode
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Could not connect to SecuGen WebAPI service on ports: ' . implode(', ', $possible_ports)
            ]);
        }
        break;

    case 'match':
        $rawInput = file_get_contents('php://input');
        $body = json_decode($rawInput, true) ?? $_POST;

        $tmpl1 = $body['template1'] ?? '';
        $tmpl2 = $body['template2'] ?? '';

        if (empty($tmpl1) || empty($tmpl2)) {
            echo json_encode(['success' => false, 'message' => 'Templates required for matching']);
            exit;
        }

        $score = secuGenMatchScore($tmpl1, $tmpl2, $possible_ports);

        if ($score === null) {
            echo json_encode(['success' => false, 'message' => 'SecuGen WebAPI could not compute a match score (unreachable or rejected the templates)']);
        } else {
            echo json_encode(['success' => true, 'match' => ($score >= SECUGEN_MATCH_THRESHOLD), 'score' => $score]);
        }
        break;

    case 'identify':
        $rawInput = file_get_contents('php://input');
        $body = json_decode($rawInput, true) ?? $_POST;

        $captured = $body['captured_template'] ?? '';
        $candidates = $body['candidates'] ?? [];

        if (empty($captured) || empty($candidates)) {
            echo json_encode(['success' => false, 'message' => 'Captured template and candidates required']);
            exit;
        }

        $bestMatchId = null;
        $bestScore = -1;

        foreach ($candidates as $cand) {
            // get_all_fingerprints.php sends {id, template}; accept the
            // legacy/alternate key names too so any other caller keeps working.
            $candTmpl = $cand['template'] ?? $cand['template_data'] ?? $cand['fingerprint_template'] ?? '';
            $matId = $cand['mat_id'] ?? $cand['id'] ?? null;

            if (empty($candTmpl)) continue;

            $score = secuGenMatchScore($captured, $candTmpl, $possible_ports);
            if ($score !== null && $score > $bestScore) {
                $bestScore = $score;
                $bestMatchId = $matId;
            }
        }

        if ($bestScore >= SECUGEN_MATCH_THRESHOLD && $bestMatchId) {
            echo json_encode(['success' => true, 'matched' => true, 'match_id' => $bestMatchId, 'score' => $bestScore]);
        } else {
            echo json_encode(['success' => true, 'matched' => false, 'message' => 'No matching patient found', 'best_score' => max($bestScore, 0)]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
        break;
}

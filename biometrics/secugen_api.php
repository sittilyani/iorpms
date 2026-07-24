<?php
/**
 * secugen_api.php — Native PHP Bridge for SecuGen WebAPI & Scanner
 * =================================================================
 * Runs directly inside Laragon on Port 80. Proxies requests to local SecuGen
 * WebAPI service (https://127.0.0.1:8443/SGIFPCapture), bypassing CORS and SSL
 * certificate errors in the browser.
 */
session_start();
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
 * Send HTTP/HTTPS cURL request to local SecuGen WebAPI service
 */
function callSecuGenWebAPI($endpoint, $params = [], $targetPort = 8443, $method = 'POST', $timeout = 12) {
    $protocols = ['https', 'http'];
    
    foreach ($protocols as $proto) {
        $queryString = !empty($params) ? '?' . http_build_query($params) : '';
        $url = "{$proto}://127.0.0.1:{$targetPort}/{$endpoint}{$queryString}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, '');
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

/**
 * Ensure SecuGenWebAPI.exe process is running on Windows
 */
function ensureSecuGenServiceRunning() {
    $secugenExe = 'C:\\Program Files\\SecuGen\\SecuGen WebAPI\\SecuGenWebAPI.exe';
    if (file_exists($secugenExe) && strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Check if process is already running
        $output = @shell_exec('tasklist /FI "IMAGENAME eq SecuGenWebAPI.exe" 2>nul');
        if (strpos($output, 'SecuGenWebAPI.exe') === false) {
            // Launch SecuGenWebAPI in background
            @pclose(@popen('start /B "" "' . $secugenExe . '"', "r"));
            usleep(1500000); // 1.5s delay for service initialization
        }
    }
}

// Ensure background service is running
ensureSecuGenServiceRunning();

// Action Handlers
switch ($action) {
    case 'health':
    case 'test':
        $connectedPort = null;
        $connectedProto = null;
        $webData = null;

        foreach ($possible_ports as $p) {
            $res = callSecuGenWebAPI('SGIFPCapture', [
                'Timeout' => '500',
                'Quality' => '50',
                'TemplateFormat' => 'ANSI'
            ], $p, 'POST', 2);

            if ($res['success']) {
                $connectedPort = $res['port'];
                $connectedProto = $res['proto'];
                $webData = $res['data'];
                break;
            }
        }

        if ($connectedPort) {
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
                'TemplateFormat' => 'ANSI',
                'ImageWidth' => '260',
                'ImageHeight' => '300',
                'ImageDPI' => '500'
            ], $p, 'POST', 12);

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
                    'quality_score' => $data['Quality'] ?? 85,
                    'image_width' => $data['ImageWidth'] ?? 260,
                    'image_height' => $data['ImageHeight'] ?? 300,
                    'message' => 'SecuGen fingerprint captured successfully via PHP'
                ]);
            } else {
                $errMsgs = [
                    54 => "Timeout - No finger detected on SecuGen scanner",
                    52 => "No SecuGen fingerprint scanner device attached",
                    53 => "Failed to initialize SecuGen scanner device",
                    51 => "System memory error"
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

        if ($tmpl1 === $tmpl2) {
            echo json_encode(['success' => true, 'match' => true, 'score' => 100]);
            exit;
        }

        $b1 = base64_decode($tmpl1);
        $b2 = base64_decode($tmpl2);

        if ($b1 !== false && $b2 !== false && strlen($b1) > 0 && strlen($b2) > 0) {
            $minLen = min(strlen($b1), strlen($b2));
            $matches = 0;
            for ($i = 0; $i < $minLen; $i++) {
                if ($b1[$i] === $b2[$i]) $matches++;
            }
            $similarity = ($matches / $minLen) * 100;
            echo json_encode([
                'success' => true,
                'match' => ($similarity >= 65.0),
                'score' => round($similarity, 2)
            ]);
            exit;
        }

        echo json_encode(['success' => true, 'match' => false, 'score' => 0]);
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

        $capBytes = base64_decode($captured);
        $bestMatchId = null;
        $bestScore = 0;

        foreach ($candidates as $cand) {
            $candTmpl = $cand['template_data'] ?? $cand['fingerprint_template'] ?? '';
            $matId = $cand['mat_id'] ?? $cand['id'] ?? null;

            if (empty($candTmpl)) continue;

            if ($candTmpl === $captured) {
                echo json_encode(['success' => true, 'matched' => true, 'match_id' => $matId, 'score' => 100]);
                exit;
            }

            $candBytes = base64_decode($candTmpl);
            if ($capBytes !== false && $candBytes !== false && strlen($capBytes) > 0 && strlen($candBytes) > 0) {
                $minLen = min(strlen($capBytes), strlen($candBytes));
                $matches = 0;
                for ($i = 0; $i < $minLen; $i++) {
                    if ($capBytes[$i] === $candBytes[$i]) $matches++;
                }
                $similarity = ($matches / $minLen) * 100;
                if ($similarity > $bestScore) {
                    $bestScore = $similarity;
                    $bestMatchId = $matId;
                }
            }
        }

        if ($bestScore >= 65.0 && $bestMatchId) {
            echo json_encode(['success' => true, 'matched' => true, 'match_id' => $bestMatchId, 'score' => round($bestScore, 2)]);
        } else {
            echo json_encode(['success' => true, 'matched' => false, 'message' => 'No matching patient found', 'best_score' => round($bestScore, 2)]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
        break;
}

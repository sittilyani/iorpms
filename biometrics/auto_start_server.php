<?php
/**
 * auto_start_server.php — Background Fingerprint Server Auto-Launcher
 * =================================================================
 * Triggered by frontend if local fingerprint server is detected offline.
 * Detects available runtime (Python vs Node.js) and executes in background.
 */
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// 1. Check if server is already running on port 3000 (ZKTeco Python), 3001 (ZKTeco Node), 8443 (SecuGen WebAPI), or 8000 (SecuGen Python)
$ports_to_check = [3000, 3001, 8443, 8000, 8080];
$running_ports = [];

foreach ($ports_to_check as $p) {
    $conn = @fsockopen('127.0.0.1', $p, $errno, $errstr, 0.2);
    if (is_resource($conn)) {
        $running_ports[] = $p;
        fclose($conn);
    }
}

$scanner = $_GET['scanner'] ?? 'all';

if (!empty($running_ports)) {
    echo json_encode(['success' => true, 'message' => 'Server is already running on port(s): ' . implode(', ', $running_ports)]);
    exit;
}

// 2. Launch server in background (Windows OS support)
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    // Check if SecuGen WebAPI executable exists
    $secugenWebAPI = 'C:\\Program Files\\SecuGen\\SecuGen WebAPI\\SecuGenWebAPI.exe';
    if (file_exists($secugenWebAPI) && ($scanner === 'secugen' || $scanner === 'all')) {
        @pclose(@popen('start /B "" "' . $secugenWebAPI . '"', "r"));
    }

    // Find Python executable
    $pythonCandidates = [
        'C:\\laragon\\bin\\python\\python-3.10-32\\python.exe',
        'C:\\Python314\\python.exe',
        'C:\\laragon\\bin\\python\\python-3.10\\python.exe',
        'C:\\laragon\\bin\\python\\python-3.13\\python.exe',
        'python'
    ];
    
    $pythonCmd = null;
    foreach ($pythonCandidates as $py) {
        if ($py === 'python' || file_exists($py)) {
            $pythonCmd = ($py === 'python') ? 'python' : '"' . $py . '"';
            break;
        }
    }

    // Find Node executable
    $nodeCandidates = [
        'C:\\Program Files\\nodejs\\node.exe',
        'C:\\laragon\\bin\\nodejs\\node-v24.18.0-win-x64\\node.exe',
        'C:\\laragon\\bin\\nodejs\\node-v18\\node.exe',
        'node'
    ];

    $nodeCmd = null;
    foreach ($nodeCandidates as $nd) {
        if ($nd === 'node' || file_exists($nd)) {
            $nodeCmd = ($nd === 'node') ? 'node' : '"' . $nd . '"';
            break;
        }
    }

    if ($pythonCmd) {
        @shell_exec($pythonCmd . " -c \"import flask\" 2>nul || " . $pythonCmd . " -m pip install flask flask-cors");
        
        $zkScript = __DIR__ . '\\zkteco_python_server.py';
        $sgScript = __DIR__ . '\\secugen_python_server.py';

        if ($scanner === 'secugen') {
            @pclose(@popen('start /B "" ' . $pythonCmd . ' "' . $sgScript . '"', "r"));
        } else {
            @pclose(@popen('start /B "" ' . $pythonCmd . ' "' . $zkScript . '"', "r"));
            @pclose(@popen('start /B "" ' . $pythonCmd . ' "' . $sgScript . '"', "r"));
        }
        echo json_encode(['success' => true, 'message' => 'Launching fingerprint server(s) in background...']);
        exit;
    } elseif ($nodeCmd) {
        $scriptPath = __DIR__ . '\\zkteco_server.js';
        if (!is_dir(__DIR__ . '/node_modules')) {
            @shell_exec("cd /d \"" . __DIR__ . "\" && npm.cmd install --silent");
        }
        @pclose(@popen('start /B "" ' . $nodeCmd . ' "' . $scriptPath . '"', "r"));
        echo json_encode(['success' => true, 'message' => 'Launching Node fingerprint server in background...']);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Failed to start server automatically.']);
exit;


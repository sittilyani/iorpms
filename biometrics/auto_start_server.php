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

// 1. Check if server is already running on port 3000 (Python) or 3001 (Node)
$port3000_running = false;
$port3001_running = false;

$connection3000 = @fsockopen('localhost', 3000, $errno, $errstr, 0.2);
if (is_resource($connection3000)) {
    $port3000_running = true;
    fclose($connection3000);
}

$connection3001 = @fsockopen('localhost', 3001, $errno, $errstr, 0.2);
if (is_resource($connection3001)) {
    $port3001_running = true;
    fclose($connection3001);
}

if ($port3000_running || $port3001_running) {
    echo json_encode(['success' => true, 'message' => 'Server is already running.']);
    exit;
}

// 2. Launch server in background (Windows OS support)
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    // Find Python executable (prioritize 32-bit Python for ZKTeco 32-bit DLL compatibility)
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

    $cmd = '';
    if ($pythonCmd) {
        $scriptPath = __DIR__ . '\\zkteco_python_server.py';
        @shell_exec($pythonCmd . " -c \"import flask\" 2>nul || " . $pythonCmd . " -m pip install flask flask-cors");
        $cmd = 'start /B "" ' . $pythonCmd . ' "' . $scriptPath . '"';
    } elseif ($nodeCmd) {
        $scriptPath = __DIR__ . '\\zkteco_server.js';
        if (!is_dir(__DIR__ . '/node_modules')) {
            @shell_exec("cd /d \"" . __DIR__ . "\" && npm.cmd install --silent");
        }
        $cmd = 'start /B "" ' . $nodeCmd . ' "' . $scriptPath . '"';
    }

    if ($cmd) {
        $handle = popen($cmd, "r");
        if ($handle !== false) {
            pclose($handle);
            echo json_encode(['success' => true, 'message' => 'Launching server in background...']);
            exit;
        }
    }
}

echo json_encode(['success' => false, 'message' => 'Failed to start server automatically.']);
exit;


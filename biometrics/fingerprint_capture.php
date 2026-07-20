<?php
session_start();
include "../includes/config.php";

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

$userId = isset($_GET['p_id']) ? intval($_GET['p_id']) : null;
$fingerprintId = isset($_GET['id']) ? intval($_GET['id']) : null;
$action = isset($_GET['action']) ? $_GET['action'] : 'capture';
$performedBy = $_SESSION['username'];

// Fetch latest scanner type for default persistence
$defaultScanner = 'zkteco';
$latestScannerQ = $conn->query("SELECT scanner_type FROM fingerprints ORDER BY capture_date DESC LIMIT 1");
if ($latestScannerQ && $row = $latestScannerQ->fetch_assoc()) {
    if (!empty($row['scanner_type'])) {
        $defaultScanner = $row['scanner_type'];
    }
}

// Handle delete action
if ($action === 'delete' && $fingerprintId) {
    if (deleteFingerprint($conn, $fingerprintId, $performedBy)) {
        header("Location: fingerprint_search.php?message=" . urlencode("Fingerprint deleted successfully."));
        exit();
    } else {
        die("Error deleting fingerprint.");
    }
}

// Fetch patient details
$currentSettings = [];
if ($userId) {
    $query = "SELECT * FROM patients WHERE p_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentSettings = $result->fetch_assoc();
}

// Fetch existing fingerprint if editing
$existingPrint = null;
if ($fingerprintId) {
    $printQuery = "SELECT * FROM fingerprints WHERE id = ?";
    $printStmt = $conn->prepare($printQuery);
    $printStmt->bind_param('i', $fingerprintId);
    $printStmt->execute();
    $printResult = $printStmt->get_result();
    $existingPrint = $printResult->fetch_assoc();
} elseif ($userId && $currentSettings) {
    // Get latest fingerprint for this patient
    $printQuery = "SELECT * FROM fingerprints WHERE mat_id = ? ORDER BY capture_date DESC LIMIT 1";
    $printStmt = $conn->prepare($printQuery);
    $printStmt->bind_param('s', $currentSettings['mat_id']);
    $printStmt->execute();
    $printResult = $printStmt->get_result();
    $existingPrint = $printResult->fetch_assoc();
}

// Function to delete fingerprint
function deleteFingerprint($conn, $fingerprintId, $performedBy) {
    try {
        $conn->begin_transaction();

        // Get fingerprint details for audit log
        $query = "SELECT mat_id FROM fingerprints WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $fingerprintId);
        $stmt->execute();
        $result = $stmt->get_result();
        $fingerprint = $result->fetch_assoc();

        // Create audit log
        $auditQuery = "INSERT INTO fingerprint_audit (fingerprint_id, mat_id, operation, performed_by, details)
                       VALUES (?, ?, 'DELETE', ?, 'Fingerprint permanently deleted from system')";
        $auditStmt = $conn->prepare($auditQuery);
        $auditStmt->bind_param('iss', $fingerprintId, $fingerprint['mat_id'], $performedBy);
        $auditStmt->execute();
        $auditStmt->close();

        // Delete the fingerprint
        $deleteQuery = "DELETE FROM fingerprints WHERE id = ?";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bind_param('i', $fingerprintId);
        $deleteStmt->execute();
        $deleteStmt->close();

        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Delete fingerprint error: " . $e->getMessage());
        return false;
    }
}

// Function to save fingerprint
function saveFingerprint($conn, $data, $action, $performedBy) {
    try {
        $conn->begin_transaction();

        // Convert base64 to binary
        $fingerprint_data = null;
        $template_data = null;

        if (!empty($data['fingerprint_data_base64'])) {
            $fingerprint_data = base64_decode($data['fingerprint_data_base64']);
            if ($fingerprint_data === false) {
                throw new Exception("Invalid base64 fingerprint data");
            }
        }

        if (!empty($data['fingerprint_template'])) {
            $template_data = base64_decode($data['fingerprint_template']);
            if ($template_data === false) {
                throw new Exception("Invalid base64 template data");
            }
        }

        // Validate required data
        if (empty($fingerprint_data) && empty($template_data)) {
            throw new Exception("No valid fingerprint data received");
        }

        $quality_score = isset($data['quality_score']) ? intval($data['quality_score']) : 0;

        // Save raw binary files to disk
        $fingerprint_path = '';
        if ($template_data !== null) {
            $file_dir = dirname(__DIR__) . '/biometrics/fingerprints';
            if (!is_dir($file_dir)) {
                @mkdir($file_dir, 0777, true);
            }
            $filename = $data['mat_id'] . '_template.dat';
            $full_path = $file_dir . '/' . $filename;
            if (file_put_contents($full_path, $template_data) !== false) {
                $fingerprint_path = 'biometrics/fingerprints/' . $filename;
            }
        }

        if ($fingerprint_data !== null) {
            $file_dir = dirname(__DIR__) . '/biometrics/fingerprints';
            if (!is_dir($file_dir)) {
                @mkdir($file_dir, 0777, true);
            }
            $img_filename = $data['mat_id'] . '_image.png';
            $img_full_path = $file_dir . '/' . $img_filename;
            @file_put_contents($img_full_path, $fingerprint_data);
        }

        if ($action === 'update' && !empty($data['fingerprint_id'])) {
            // Update existing record
            $sql = "UPDATE fingerprints SET
                    visitDate = ?,
                    mat_number = ?,
                    nickName = ?,
                    dob = ?,
                    current_status = ?,
                    fingerprint_data = ?,
                    template_data = ?,
                    quality_score = ?,
                    fingerprint_type = ?,
                    scanner_type = ?,
                    fingerprint_path = ?,
                    capture_date = NOW()
                    WHERE id = ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssssssssi",
                $data['visitDate'],
                $data['mat_number'],
                $data['nickName'],
                $data['dob'],
                $data['current_status'],
                $fingerprint_data,
                $template_data,
                $quality_score,
                $data['fingerprint_type'],
                $data['scanner_type'],
                $fingerprint_path,
                $data['fingerprint_id']
            );

            $fingerprintId = $data['fingerprint_id'];
            $message = "Fingerprint updated successfully.";
            $operation = 'UPDATE';
        } else {
            // Insert new record
            $sql = "INSERT INTO fingerprints
                    (visitDate, mat_id, mat_number, clientName, nickName, dob, sex, current_status,
                     fingerprint_data, template_data, quality_score, fingerprint_type, scanner_type, fingerprint_path)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssssssisss",
                $data['visitDate'],
                $data['mat_id'],
                $data['mat_number'],
                $data['clientName'],
                $data['nickName'],
                $data['dob'],
                $data['sex'],
                $data['current_status'],
                $fingerprint_data,
                $template_data,
                $quality_score,
                $data['fingerprint_type'],
                $data['scanner_type'],
                $fingerprint_path
            );

            $message = "Fingerprint registered successfully.";
            $operation = 'INSERT';
        }

        if ($stmt->execute()) {
            if ($operation === 'INSERT') {
                $fingerprintId = $conn->insert_id;
            }

            // Create audit log
            $auditQuery = "INSERT INTO fingerprint_audit (fingerprint_id, mat_id, operation, performed_by, details)
                           VALUES (?, ?, ?, ?, ?)";
            $auditStmt = $conn->prepare($auditQuery);
            $details = "Fingerprint " . ($operation === 'UPDATE' ? 'updated' : 'captured') .
                      " for " . $data['clientName'] . " (MAT ID: " . $data['mat_id'] . ")";
            $auditStmt->bind_param('issss', $fingerprintId, $data['mat_id'], $operation, $performedBy, $details);
            $auditStmt->execute();
            $auditStmt->close();

            $conn->commit();

            return [
                'success' => true,
                'message' => $message,
                'fingerprint_id' => $fingerprintId,
                'redirect' => 'fingerprint_search.php?message=' . urlencode($message)
            ];
        } else {
            throw new Exception('Execute failed: ' . $stmt->error);
        }
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    } finally {
        if (isset($stmt)) $stmt->close();
    }
}

// Handle POST request for saving fingerprint
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;
    $data['visitDate'] = date('Y-m-d');
    $data['mat_id'] = $currentSettings['mat_id'];
    $data['clientName'] = $currentSettings['clientName'];
    $data['sex'] = $currentSettings['sex'];

    $result = saveFingerprint($conn, $data, $action, $performedBy);
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

// Helper function to format file size
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        return $bytes . ' bytes';
    } elseif ($bytes == 1) {
        return '1 byte';
    } else {
        return '0 bytes';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Fingerprint Management</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: none;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #2C3162 0%, #1a1f4b 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header-actions {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            gap: 10px;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            padding: 40px;
        }

        @media (max-width: 768px) {
            .content {
                grid-template-columns: 1fr;
            }
        }

        .patient-info, .scanner-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .patient-info h3, .scanner-section h3 {
            color: #2C3162;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .existing-info {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }

        .existing-info h4 {
            color: #0056b3;
            margin-bottom: 10px;
        }

        .file-info {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 14px;
            margin-top: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #495057;
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .readonly-input {
            background-color: #f1f3f4;
            cursor: not-allowed;
        }

        .scanner-container {
            text-align: center;
            padding: 30px;
            background: white;
            border-radius: 10px;
            border: 2px dashed #dee2e6;
            margin: 20px 0;
        }

        .scanner-controls {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
            margin: 20px 0;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 14px rgba(102, 126, 234, 0.2);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }

        .status-container {
            background: #e9ecef;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            min-height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
        }

        .status-success {
            background: #d4edda;
            color: #155724;
        }

        .status-error {
            background: #f8d7da;
            color: #721c24;
        }

        .status-info {
            background: #e9ecef;
            color: #495057;
        }

        .scanner-selector {
            margin-bottom: 20px;
        }

        .scanner-selector select {
            width: 100%;
            padding: 12px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            font-size: 16px;
            background: white;
        }

        .fingerprint-preview {
            width: 200px;
            height: 200px;
            margin: 20px auto;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            overflow: hidden;
            display: none;
        }

        .fingerprint-preview canvas {
            width: 100%;
            height: 100%;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 500px;
            width: 90%;
            text-align: center;
        }

        .modal-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Fingerprint <?php echo ucfirst($action); ?></h1>
            <h2><?php echo htmlspecialchars($currentSettings['clientName'] ?? ''); ?>
                <?php if(isset($currentSettings['mat_id'])): ?>
                    (MAT ID: <?php echo htmlspecialchars($currentSettings['mat_id']); ?>)
                <?php endif; ?>
            </h2>

            <?php if ($existingPrint && $action === 'update'): ?>
            <div class="header-actions">
                <button type="button" class="btn-delete" onclick="confirmDelete()">
                    Delete Fingerprint
                </button>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($existingPrint): ?>
        <div class="existing-info">
            <h4>? Existing Fingerprint Details</h4>
            <p><strong>Capture Date:</strong> <?php echo date('Y-m-d H:i', strtotime($existingPrint['capture_date'])); ?></p>
            <p><strong>Finger Type:</strong> <?php echo htmlspecialchars($existingPrint['fingerprint_type']); ?></p>
            <p><strong>Scanner:</strong> <?php echo htmlspecialchars($existingPrint['scanner_type']); ?></p>
            <p><strong>Quality Score:</strong> <?php echo $existingPrint['quality_score']; ?>/100</p>
            <?php if ($existingPrint['fingerprint_data']): ?>
            <div class="file-info">
                <strong>Fingerprint Data Size:</strong>
                <?php echo formatFileSize(strlen($existingPrint['fingerprint_data'])); ?>
            </div>
            <?php endif; ?>
            <?php if ($existingPrint['template_data']): ?>
            <div class="file-info">
                <strong>Template Data Size:</strong>
                <?php echo formatFileSize(strlen($existingPrint['template_data'])); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="content">
            <div class="patient-info">
                <h3>Patient Information</h3>

                <div class="form-group">
                    <label>Visit Date:</label>
                    <input type="text" name="visitDate" class="form-control readonly-input"
                           value="<?php echo date('Y-m-d H:i'); ?>" readonly>
                </div>

                <div class="form-group">
                    <label>MAT ID:</label>
                    <input type="text" name="mat_id" class="form-control readonly-input"
                           value="<?php echo htmlspecialchars($currentSettings['mat_id'] ?? ''); ?>" readonly>
                </div>

                <div class="form-group">
                    <label>MAT Number:</label>
                    <input type="text" name="mat_number" class="form-control readonly-input"
                           value="<?php echo htmlspecialchars($currentSettings['mat_number'] ?? ''); ?>" <?php echo $action === 'update' ? '' : 'readonly'; ?>>
                </div>

                <div class="form-group">
                    <label>Client Name:</label>
                    <input type="text" name="clientName" class="form-control readonly-input"
                           value="<?php echo htmlspecialchars($currentSettings['clientName'] ?? ''); ?>" readonly>
                </div>

                <div class="form-group">
                    <label>Nick Name:</label>
                    <input type="text" name="nickName" class="form-control"
                           value="<?php echo htmlspecialchars($currentSettings['nickName'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Date of Birth:</label>
                    <input type="date" name="dob" class="form-control"
                           value="<?php echo htmlspecialchars($currentSettings['dob'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Sex:</label>
                    <input type="text" name="sex" class="form-control readonly-input"
                           value="<?php echo htmlspecialchars($currentSettings['sex'] ?? ''); ?>" readonly>
                </div>

                <div class="form-group">
                    <label>Current Status:</label>
                    <select name="current_status" class="form-control">
                        <option value="Active" <?php echo ($currentSettings['current_status'] ?? '') === 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Inactive" <?php echo ($currentSettings['current_status'] ?? '') === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="Transferred" <?php echo ($currentSettings['current_status'] ?? '') === 'Transferred' ? 'selected' : ''; ?>>Transferred</option>
                        <option value="Deceased" <?php echo ($currentSettings['current_status'] ?? '') === 'Deceased' ? 'selected' : ''; ?>>Deceased</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Fingerprint Type:</label>
                    <select name="fingerprint_type" class="form-control">
                        <option value="Thumb" <?php echo ($existingPrint['fingerprint_type'] ?? '') === 'Thumb' ? 'selected' : ''; ?>>Thumb</option>
                        <option value="Index" <?php echo ($existingPrint['fingerprint_type'] ?? 'Index') === 'Index' ? 'selected' : ''; ?>>Index Finger</option>
                        <option value="Middle" <?php echo ($existingPrint['fingerprint_type'] ?? '') === 'Middle' ? 'selected' : ''; ?>>Middle Finger</option>
                        <option value="Ring" <?php echo ($existingPrint['fingerprint_type'] ?? '') === 'Ring' ? 'selected' : ''; ?>>Ring Finger</option>
                        <option value="Little" <?php echo ($existingPrint['fingerprint_type'] ?? '') === 'Little' ? 'selected' : ''; ?>>Little Finger</option>
                    </select>
                </div>
            </div>

            <div class="scanner-section">
                <h3>Fingerprint Scanner</h3>

                <div class="scanner-selector">
                    <label>Select Scanner Type:</label>
                    <select id="scanner-type" class="form-control">
                        <option value="zkteco" <?php echo $defaultScanner === 'zkteco' ? 'selected' : ''; ?>>ZKTeco Scanner</option>
                        <option value="digitalpersona" <?php echo $defaultScanner === 'digitalpersona' ? 'selected' : ''; ?>>Digital Persona Scanner</option>
                    </select>
                </div>

                <div class="scanner-container">
                    <h4 id="scanner-title">ZKTeco Fingerprint Scanner</h4>
                    <p id="scanner-instructions">Place your finger on the scanner and click "Capture Fingerprint"</p>

                    <div class="scanner-controls">
                        <button type="button" id="init-scanner" class="btn btn-primary" onclick="initializeScanner()">
                            <span class="btn-text">Initialize Scanner</span>
                            <span class="loading" style="display:none;"></span>
                        </button>
                        <button type="button" id="capture-fingerprint" class="btn btn-success" onclick="captureFingerprint()" disabled>
                            Capture Fingerprint
                        </button>
                    </div>

                    <div id="scanner-status" class="status-container status-info">
                        Scanner not initialized
                    </div>

                    <div class="fingerprint-preview" id="fingerprint-preview">
                        <img id="fingerprint-image" alt="Fingerprint Preview" style="width: 100%; height: 100%;">
                    </div>

                    <div id="quality-indicator" style="margin: 20px 0; display: none;">
                        <label>Quality Score: <span id="quality-score">0</span>/100</label>
                        <div style="background: #e9ecef; height: 10px; border-radius: 5px; overflow: hidden;">
                            <div id="quality-bar" style="background: #28a745; height: 100%; width: 0%;"></div>
                        </div>
                    </div>
                </div>

                <button type="button" id="btn-submit" class="btn btn-primary"
                        onclick="submitFingerprint()" style="width: 100%; padding: 15px;" disabled>
                    <span id="submit-text"><?php echo $action === 'update' ? 'Update Fingerprint' : 'Save Fingerprint'; ?></span>
                    <span class="loading" style="display:none;"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="delete-modal" class="modal">
        <div class="modal-content">
            <h3>Confirm Delete</h3>
            <p>Are you sure you want to delete this fingerprint? This action cannot be undone.</p>
            <div class="modal-actions">
                <button type="button" class="btn btn-danger" onclick="performDelete()">Yes, Delete</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Hidden form for submission -->
    <form id="fingerprint-form" style="display: none;">
        <input type="hidden" name="action" value="<?php echo $action; ?>">
        <input type="hidden" name="visitDate" value="<?php echo date('Y-m-d H:i'); ?>">
        <input type="hidden" name="mat_id" value="<?php echo htmlspecialchars($currentSettings['mat_id'] ?? ''); ?>">
        <input type="hidden" name="mat_number" id="form-mat-number" value="<?php echo htmlspecialchars($currentSettings['mat_number'] ?? ''); ?>">
        <input type="hidden" name="clientName" value="<?php echo htmlspecialchars($currentSettings['clientName'] ?? ''); ?>">
        <input type="hidden" name="nickName" id="form-nickname" value="<?php echo htmlspecialchars($currentSettings['nickName'] ?? ''); ?>">
        <input type="hidden" name="dob" id="form-dob" value="<?php echo htmlspecialchars($currentSettings['dob'] ?? ''); ?>">
        <input type="hidden" name="sex" value="<?php echo htmlspecialchars($currentSettings['sex'] ?? ''); ?>">
        <input type="hidden" name="current_status" id="form-current-status" value="<?php echo htmlspecialchars($currentSettings['current_status'] ?? 'Active'); ?>">
        <input type="hidden" name="fingerprint_type" id="form-fingerprint-type" value="<?php echo $existingPrint['fingerprint_type'] ?? 'Index'; ?>">
        <input type="hidden" name="scanner_type" id="form-scanner-type" value="<?php echo htmlspecialchars($defaultScanner); ?>">
        <?php if ($existingPrint && $action === 'update'): ?>
        <input type="hidden" name="fingerprint_id" value="<?php echo $existingPrint['id']; ?>">
        <?php endif; ?>
        <input type="hidden" name="fingerprint_data_base64" id="fingerprint-data-base64">
        <input type="hidden" name="fingerprint_template" id="fingerprint-template">
        <input type="hidden" name="quality_score" id="quality-score-input" value="0">
    </form>

    <script>
        // Global variables
        let isScannerInitialized = false;
        let fingerprintData = null;
        let templateData = null;
        let qualityScore = 0;
        let activeZktecoPort = 3001; // default fallback
        let dpSocket = null;

        // Auto-detect ZKTeco port (3000 or 3001)
        async function detectZktecoPort() {
            const ports = [3000, 3001];
            for (let port of ports) {
                try {
                    const response = await fetch(`http://localhost:${port}/health`, { mode: 'cors' });
                    if (response.ok) {
                        const data = await response.json();
                        if (data.success || data.status === 'online' || data.service) {
                            activeZktecoPort = port;
                            console.log("Detected ZKTeco server on port " + port);
                            return true;
                        }
                    }
                } catch(e) {
                    // Try next port
                }
            }
            return false;
        }

        async function initializeScanner() {
            const scannerType = document.getElementById('scanner-type').value;

            if (scannerType === 'digitalpersona') {
                initDigitalPersona();
                return;
            }

            const btn = document.getElementById('init-scanner');
            const btnText = btn.querySelector('.btn-text');
            const loading = btn.querySelector('.loading');

            btnText.style.display = 'none';
            loading.style.display = 'inline-block';
            btn.disabled = true;

            try {
                updateStatus('Initializing ZKTeco scanner...', 'info');

                // Re-detect active port
                await detectZktecoPort();

                const response = await fetch(`http://localhost:${activeZktecoPort}/test`, { mode: 'cors' });

                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        isScannerInitialized = true;
                        document.getElementById('capture-fingerprint').disabled = false;
                        updateStatus(`? ZKTeco Scanner initialized. ${data.message}`, 'success');
                    } else {
                        throw new Error(data.message || 'Scanner test failed');
                    }
                } else {
                    throw new Error(`Server responded with status: ${response.status}`);
                }
            } catch (error) {
                updateStatus(`? Scanner initialization failed: ${error.message}`, 'error');
                console.error('Scanner init error:', error);
            } finally {
                btnText.style.display = 'inline-block';
                loading.style.display = 'none';
                btn.disabled = false;
            }
        }

        async function captureFingerprint() {
            const scannerType = document.getElementById('scanner-type').value;
            if (scannerType === 'digitalpersona') {
                updateStatus('Place finger on the DigitalPersona scanner to capture...', 'info');
                return;
            }

            if (!isScannerInitialized) {
                updateStatus('Please initialize the scanner first', 'error');
                return;
            }

            const btn = document.getElementById('capture-fingerprint');
            btn.disabled = true;
            updateStatus('Capturing fingerprint... Place finger on scanner', 'info');

            try {
                // Ensure active port is fresh
                await detectZktecoPort();

                // Show countdown or waiting message
                let countdown = 10;
                const interval = setInterval(() => {
                    updateStatus(`Capturing... Place finger on scanner (${countdown}s)`, 'info');
                    countdown--;
                    if (countdown < 0) clearInterval(interval);
                }, 1000);

                const response = await fetch(`http://localhost:${activeZktecoPort}/capture`, { mode: 'cors' });

                clearInterval(interval);

                if (!response.ok) {
                    throw new Error(`Server error: ${response.status}`);
                }

                const data = await response.json();

                if (data.success) {
                    fingerprintData = data.fingerprint_data_base64;
                    templateData = data.fingerprint_template;
                    qualityScore = data.quality_score;

                    // Update form fields
                    document.getElementById('fingerprint-data-base64').value = fingerprintData;
                    document.getElementById('fingerprint-template').value = templateData;
                    document.getElementById('quality-score-input').value = qualityScore;

                    // Show preview if we have image data
                    if (fingerprintData && fingerprintData.length > 100) {
                        document.getElementById('fingerprint-image').src = 'data:image/png;base64,' + fingerprintData;
                        document.getElementById('fingerprint-preview').style.display = 'block';
                    }

                    // Update quality indicator
                    document.getElementById('quality-indicator').style.display = 'block';
                    document.getElementById('quality-score').textContent = qualityScore;
                    document.getElementById('quality-bar').style.width = qualityScore + '%';

                    // Enable submit button
                    document.getElementById('btn-submit').disabled = false;

                    updateStatus(`? Fingerprint captured successfully! Quality: ${qualityScore}/100`, 'success');
                } else {
                    throw new Error(data.message || 'Capture failed');
                }
            } catch (error) {
                updateStatus(`? Error: ${error.message}`, 'error');
                console.error('Capture error:', error);

                // Handle specific errors
                if (error.message.includes('No finger detected')) {
                    updateStatus('? No finger detected. Please place finger firmly on scanner', 'error');
                } else if (error.message.includes('quality')) {
                    updateStatus('? Fingerprint quality too low. Please clean finger and try again', 'error');
                }
            } finally {
                btn.disabled = false;
            }
        }

        function initDigitalPersona() {
            if (dpSocket && dpSocket.readyState === WebSocket.OPEN) {
                updateStatus('DigitalPersona scanner is already connected', 'success');
                return;
            }
            updateStatus('Connecting to DigitalPersona service...', 'info');
            dpSocket = new WebSocket('ws://localhost:9001');
            
            dpSocket.onopen = function() {
                isScannerInitialized = true;
                document.getElementById('capture-fingerprint').disabled = false;
                updateStatus('DigitalPersona connected! Place finger on scanner to capture.', 'success');
            };
            
            dpSocket.onerror = function() {
                updateStatus('Cannot connect to DigitalPersona WebSocket. Make sure DigitalPersona Agent is running on port 9001.', 'error');
            };
            
            dpSocket.onmessage = function(event) {
                try {
                    const data = JSON.parse(event.data);
                    if (data.type === 'sample' && data.image) {
                        fingerprintData = data.image; // base64
                        templateData = data.template; // base64
                        qualityScore = data.quality || 100;
                        
                        document.getElementById('fingerprint-data-base64').value = fingerprintData;
                        document.getElementById('fingerprint-template').value = templateData;
                        document.getElementById('quality-score-input').value = qualityScore;
                        
                        document.getElementById('fingerprint-image').src = 'data:image/png;base64,' + fingerprintData;
                        document.getElementById('fingerprint-preview').style.display = 'block';
                        
                        document.getElementById('quality-indicator').style.display = 'block';
                        document.getElementById('quality-score').textContent = qualityScore;
                        document.getElementById('quality-bar').style.width = qualityScore + '%';
                        
                        document.getElementById('btn-submit').disabled = false;
                        updateStatus('Fingerprint captured successfully from DigitalPersona!', 'success');
                    }
                } catch(e) {
                    console.error('DigitalPersona message parse error:', e);
                }
            };
            
            dpSocket.onclose = function() {
                isScannerInitialized = false;
                document.getElementById('capture-fingerprint').disabled = true;
                updateStatus('DigitalPersona connection closed.', 'info');
            };
        }

        function updateFormData() {
            document.getElementById('form-mat-number').value = document.querySelector('input[name="mat_number"]').value;
            document.getElementById('form-nickname').value = document.querySelector('input[name="nickName"]').value;
            document.getElementById('form-dob').value = document.querySelector('input[name="dob"]').value;
            document.getElementById('form-current-status').value = document.querySelector('select[name="current_status"]').value;
            document.getElementById('form-fingerprint-type').value = document.querySelector('select[name="fingerprint_type"]').value;
            document.getElementById('form-scanner-type').value = document.getElementById('scanner-type').value;
        }

        async function submitFingerprint() {
            const btn = document.getElementById('btn-submit');
            const btnText = document.getElementById('submit-text');
            const loading = btn.querySelector('.loading');

            // Update form data before submission
            updateFormData();

            // Validate we have fingerprint data
            if (!fingerprintData && !templateData) {
                updateStatus('? No fingerprint data to save. Please capture fingerprint first.', 'error');
                return;
            }

            btnText.style.display = 'none';
            loading.style.display = 'inline-block';
            btn.disabled = true;

            try {
                updateStatus('Saving fingerprint data to database...', 'info');

                const form = document.getElementById('fingerprint-form');
                const formData = new FormData(form);

                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    updateStatus(`? ${result.message}`, 'success');
                    setTimeout(() => {
                        if (result.redirect) {
                            window.location.href = result.redirect;
                        }
                    }, 2000);
                } else {
                    updateStatus(`? ${result.message}`, 'error');
                    btnText.style.display = 'inline-block';
                    loading.style.display = 'none';
                    btn.disabled = false;
                }

            } catch (error) {
                updateStatus(`? Error: ${error.message}`, 'error');
                console.error('Submit error:', error);
                btnText.style.display = 'inline-block';
                loading.style.display = 'none';
                btn.disabled = false;
            }
        }

        function updateStatus(message, type = 'info') {
            const statusElement = document.getElementById('scanner-status');
            statusElement.textContent = message;
            statusElement.className = 'status-container status-' + type;

            // Add emoji based on type
            if (type === 'success') {
                statusElement.innerHTML = '? ' + message;
            } else if (type === 'error') {
                statusElement.innerHTML = '? ' + message;
            } else {
                statusElement.innerHTML = message;
            }
        }

        // Delete functions
        function confirmDelete() {
            document.getElementById('delete-modal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('delete-modal').style.display = 'none';
        }

        function performDelete() {
            window.location.href = '?action=delete&id=<?php echo $existingPrint['id'] ?? ''; ?>';
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Restore scanner preference from localStorage if available
            const savedScanner = localStorage.getItem('preferred_scanner');
            if (savedScanner) {
                const scannerSelect = document.getElementById('scanner-type');
                if (scannerSelect) {
                    scannerSelect.value = savedScanner;
                }
            }
            
            // Listen for scanner type changes
            document.getElementById('scanner-type').addEventListener('change', function() {
                localStorage.setItem('preferred_scanner', this.value);
                updateScannerUIElements();
            });

            updateScannerUIElements();

            // If editing, enable submit button if we already have data
            if (<?php echo $existingPrint ? 'true' : 'false'; ?> && <?php echo $action === 'update' ? 'true' : 'false'; ?>) {
                document.getElementById('btn-submit').disabled = false;
                updateStatus('Ready to update fingerprint', 'info');
            }
        });

        function updateScannerUIElements() {
            const scannerType = document.getElementById('scanner-type').value;
            const title = document.getElementById('scanner-title');
            const instructions = document.getElementById('scanner-instructions');
            
            if (scannerType === 'digitalpersona') {
                title.textContent = 'Digital Persona Fingerprint Scanner';
                instructions.textContent = 'Ensure Digital Persona Agent is running. Place finger on scanner.';
                document.getElementById('init-scanner').querySelector('.btn-text').textContent = 'Connect Scanner';
                isScannerInitialized = false;
                document.getElementById('capture-fingerprint').disabled = true;
                updateStatus('DigitalPersona scanner not connected', 'info');
            } else {
                title.textContent = 'ZKTeco Fingerprint Scanner';
                instructions.textContent = 'Place your finger on the scanner and click "Capture Fingerprint"';
                document.getElementById('init-scanner').querySelector('.btn-text').textContent = 'Initialize Scanner';
                isScannerInitialized = false;
                document.getElementById('capture-fingerprint').disabled = true;
                updateStatus('ZKTeco scanner not initialized', 'info');
                checkServerStatus(); // Auto check ZKTeco server status
            }
        }

        async function checkServerStatus() {
            const scannerType = document.getElementById('scanner-type').value;
            if (scannerType !== 'zkteco') return;

            const detected = await detectZktecoPort();
            if (detected) {
                updateStatus('ZKTeco fingerprint server is running', 'success');
                document.getElementById('init-scanner').disabled = false;
            } else {
                updateStatus('Fingerprint server offline. Attempting to start automatically...', 'info');
                try {
                    await fetch('auto_start_server.php');
                    setTimeout(async () => {
                        const retried = await detectZktecoPort();
                        if (retried) {
                            updateStatus('ZKTeco fingerprint server started automatically', 'success');
                            document.getElementById('init-scanner').disabled = false;
                        } else {
                            updateStatus('ZKTeco server not running. Make sure Python (port 3000) or Node.js (port 3001) is started.', 'info');
                        }
                    }, 2500);
                } catch(e) {
                    updateStatus('ZKTeco server not running. Make sure Python (port 3000) or Node.js (port 3001) is started.', 'info');
                }
            }
        }
    </script>
</body>
</html>
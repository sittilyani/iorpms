<?php
session_start();
include "../includes/config.php";

$userId = isset($_GET['p_id']) ? $_GET['p_id'] : null;
$action = isset($_GET['action']) ? $_GET['action'] : 'capture';

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

// Check if fingerprint exists
$existingPrint = null;
if ($userId && $currentSettings) {
    $printQuery = "SELECT * FROM fingerprints WHERE mat_id = ? ORDER BY capture_date DESC LIMIT 1";
    $printStmt = $conn->prepare($printQuery);
    $printStmt->bind_param('s', $currentSettings['mat_id']);
    $printStmt->execute();
    $printResult = $printStmt->get_result();
    $existingPrint = $printResult->fetch_assoc();
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $visitDate = $_POST['visitDate'];
    $mat_id = $_POST['mat_id'];
    $clientName = $_POST['clientName'];
    $sex = $_POST['sex'];
    $current_status = $_POST['current_status'];
    $formAction = $_POST['action'];

    // Get fingerprint data from SecuGen scanner
    $fingerprint_data = $_POST['fingerprint_data'] ?? '';

    if (empty($fingerprint_data)) {
        die("No fingerprint data received.");
    }

    // Create fingerprints directory if it doesn't exist
    if (!is_dir('../fingerprints/clientsphotos/')) {
        mkdir('../fingerprints/clientsphotos/', 0755, true);
    }

    // Decode base64 fingerprint data
    $fingerprint_binary = base64_decode($fingerprint_data);
    
    // Save fingerprint data to file
    $filename = $mat_id . '_' . time() . '.fpt';
    $filepath = '../fingerprints/clientsphotos/' . $filename;

    if (file_put_contents($filepath, $fingerprint_binary)) {
        if ($formAction === 'update' && $existingPrint) {
            // Delete old fingerprint file
            $oldFile = '../fingerprints/clientsphotos/' . $existingPrint['fingerprint_data'];
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }

            // Update record
            $sql = "UPDATE fingerprints SET visitDate = ?, fingerprint_data = ?, capture_date = NOW() WHERE mat_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $visitDate, $filename, $mat_id);
            $successMessage = "Fingerprint updated successfully.";
        } else {
            // Insert new record
            $sql = "INSERT INTO fingerprints (visitDate, mat_id, clientName, sex, current_status, fingerprint_data) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssss", $visitDate, $mat_id, $clientName, $sex, $current_status, $filename);
            $successMessage = "Fingerprint registered successfully.";
        }

        if ($stmt->execute()) {
            header("refresh:2; url=fingerprint_search.php?message=" . urlencode($successMessage));
            exit();
        } else {
            echo "Error: " . $conn->error;
        }
    } else {
        echo "Error saving fingerprint data.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Fingerprint <?php echo ucfirst($action); ?></title>
    <style>
        .content-main {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin: 20px;
            padding: 20px;
            background-color: #f5f5f5;
        }

        .patient-info, .fingerprint-section {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        input, select {
            width: 100%;
            padding: 8px;
            margin: 5px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .readonly-input {
            background-color: #f0f0f0;
            cursor: not-allowed;
        }

        #btn-submit {
            background-color: #2C3162;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 15px;
        }

        .scanner-container {
            text-align: center;
            padding: 20px;
            border: 2px dashed #ccc;
            border-radius: 5px;
            margin-top: 15px;
        }

        .existing-print {
            background-color: #e7f3ff;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }

        .scanner-controls {
            margin: 15px 0;
        }

        .scanner-controls button {
            margin: 5px;
            padding: 8px 15px;
            background: #2C3162;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        #fingerprint-image {
            max-width: 200px;
            max-height: 200px;
            border: 1px solid #ddd;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <h2 style="color: #2C3162; margin: 20px;">Fingerprint <?php echo ucfirst($action); ?> for <?php echo htmlspecialchars($client['clientName']); ?></h2>

    <?php if ($action === 'update' && $existingPrint): ?>
    <div class="existing-print" style="margin: 20px;">
        <strong>Existing Fingerprint Registered:</strong>
        <?php echo date('Y-m-d H:i', strtotime($existingPrint['capture_date'])); ?>
    </div>
    <?php endif; ?>

    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?p_id=" . $userId . "&action=" . $action; ?>">
        <input type="hidden" name="action" value="<?php echo $action; ?>">

        <div class="content-main">
            <div class="patient-info">
                <h3>Patient Information</h3>

                <label>Visit Date:</label>
                <input type="text" name="visitDate" class="readonly-input" readonly value="<?php echo date('Y-m-d H:i'); ?>">

                <label>MAT ID:</label>
                <input type="text" name="mat_id" class="readonly-input" readonly value="<?php echo $currentSettings['mat_id']; ?>">

                <label>Client Name:</label>
                <input type="text" name="clientName" class="readonly-input" readonly value="<?php echo $currentSettings['clientName']; ?>">

                <label>Sex:</label>
                <input type="text" name="sex" class="readonly-input" readonly value="<?php echo $currentSettings['sex']; ?>">

                <label>Current Status:</label>
                <input type="text" name="current_status" class="readonly-input" readonly value="<?php echo $currentSettings['current_status']; ?>">
            </div>

            <div class="fingerprint-section">
                <h3>SecuGen Fingerprint Capture</h3>

                <div class="scanner-container">
                    <h4>SecuGen Scanner</h4>
                    <p>Please place finger on the scanner</p>

                    <div class="scanner-controls">
                        <button type="button" id="init-scanner">Initialize Scanner</button>
                        <button type="button" id="start-capture">Start Capture</button>
                        <button type="button" id="stop-capture">Stop Capture</button>
                    </div>

                    <div id="scanner-status" style="padding: 10px; background: #e9ecef; border-radius: 4px;">
                        Scanner not initialized
                    </div>

                    <!-- Fingerprint image preview -->
                    <canvas id="fingerprint-image" width="200" height="200" style="display: none;"></canvas>

                    <!-- Hidden field to store fingerprint data -->
                    <input type="hidden" id="fingerprint_data" name="fingerprint_data">
                </div>

                <input type="submit" id="btn-submit" value="<?php echo $action === 'update' ? 'Update Fingerprint' : 'Save Fingerprint'; ?>" disabled>
            </div>
        </div>
    </form>

    <!-- Include SecuGen SDK -->
    <script src="js/secugensdk.js"></script>
    <script>
        class SecuGenIntegration {
            constructor() {
                this.scanner = null;
                this.isInitialized = false;
                this.isCapturing = false;
                this.init();
            }

            async init() {
                try {
                    // Initialize SecuGen SDK
                    if (typeof SGFingerprintEnrollment !== 'undefined') {
                        this.scanner = new SGFingerprintEnrollment();
                        await this.initializeScanner();
                    } else {
                        console.error('SecuGen SDK not loaded');
                        this.updateStatus('SecuGen SDK not available', 'error');
                    }
                } catch (error) {
                    console.error('Initialization error:', error);
                    this.updateStatus('Initialization failed: ' + error.message, 'error');
                }
            }

            async initializeScanner() {
                try {
                    // Initialize the fingerprint device
                    const result = await this.scanner.initialize();
                    if (result === 0) {
                        this.isInitialized = true;
                        this.updateStatus('Scanner initialized and ready', 'success');
                        document.getElementById('start-capture').disabled = false;
                    } else {
                        throw new Error('Failed to initialize scanner. Error code: ' + result);
                    }
                } catch (error) {
                    this.updateStatus('Scanner initialization failed: ' + error.message, 'error');
                }
            }

            async startCapture() {
                if (!this.isInitialized) {
                    this.updateStatus('Scanner not initialized', 'error');
                    return;
                }

                try {
                    this.isCapturing = true;
                    this.updateStatus('Capture started - Place finger on scanner', 'info');

                    // Start fingerprint capture
                    const captureResult = await this.scanner.captureFingerprint();
                    
                    if (captureResult.code === 0) {
                        await this.handleFingerprintData(captureResult.data);
                    } else {
                        throw new Error('Capture failed with code: ' + captureResult.code);
                    }
                } catch (error) {
                    this.updateStatus('Capture error: ' + error.message, 'error');
                } finally {
                    this.isCapturing = false;
                }
            }

            stopCapture() {
                this.isCapturing = false;
                this.updateStatus('Capture stopped', 'info');
            }

            async handleFingerprintData(fingerprintData) {
                try {
                    // Convert fingerprint data to base64 for storage
                    const base64Data = this.arrayBufferToBase64(fingerprintData);
                    
                    // Store in hidden field
                    document.getElementById('fingerprint_data').value = base64Data;
                    
                    // Enable submit button
                    document.getElementById('btn-submit').disabled = false;
                    
                    // Display preview (if image data is available)
                    await this.displayFingerprintPreview(fingerprintData);
                    
                    this.updateStatus('✓ Fingerprint captured successfully!', 'success');
                } catch (error) {
                    console.error('Error processing fingerprint data:', error);
                    this.updateStatus('Error processing fingerprint data', 'error');
                }
            }

            arrayBufferToBase64(buffer) {
                let binary = '';
                const bytes = new Uint8Array(buffer);
                for (let i = 0; i < bytes.byteLength; i++) {
                    binary += String.fromCharCode(bytes[i]);
                }
                return btoa(binary);
            }

            async displayFingerprintPreview(fingerprintData) {
                try {
                    const canvas = document.getElementById('fingerprint-image');
                    const ctx = canvas.getContext('2d');
                    
                    // Create image from fingerprint data (simplified)
                    // In practice, you might need to convert WSQ or other formats
                    const imageData = new ImageData(200, 200);
                    
                    // Simple visualization - replace with actual image processing
                    for (let i = 0; i < imageData.data.length; i += 4) {
                        const intensity = Math.random() * 255;
                        imageData.data[i] = intensity;     // R
                        imageData.data[i + 1] = intensity; // G
                        imageData.data[i + 2] = intensity; // B
                        imageData.data[i + 3] = 255;       // A
                    }
                    
                    ctx.putImageData(imageData, 0, 0);
                    canvas.style.display = 'block';
                } catch (error) {
                    console.warn('Could not display fingerprint preview:', error);
                }
            }

            updateStatus(message, type = 'info') {
                const statusElement = document.getElementById('scanner-status');
                const colors = {
                    success: '#d4edda',
                    error: '#f8d7da',
                    info: '#e9ecef'
                };
                
                statusElement.innerHTML = message;
                statusElement.style.backgroundColor = colors[type] || colors.info;
                statusElement.style.color = type === 'error' ? '#721c24' : '#155724';
            }
        }

        // Initialize SecuGen integration when page loads
        let secuGen;

        document.addEventListener('DOMContentLoaded', function() {
            secuGen = new SecuGenIntegration();

            // Event listeners
            document.getElementById('init-scanner').addEventListener('click', function() {
                secuGen.initializeScanner();
            });

            document.getElementById('start-capture').addEventListener('click', function() {
                secuGen.startCapture();
            });

            document.getElementById('stop-capture').addEventListener('click', function() {
                secuGen.stopCapture();
            });
        });
    </script>
</body>
</html>
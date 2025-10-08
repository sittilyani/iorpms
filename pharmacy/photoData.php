<?php
session_start();
include '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['userrole'])) {
    header('Location: ../public/signout.php');
    exit;
}

// Get parameters from URL with proper null handling
$p_id = isset($_GET['p_id']) ? $_GET['p_id'] : '';
$mat_id = isset($_GET['mat_id']) ? $_GET['mat_id'] : '';
$mat_number = isset($_GET['mat_number']) ? $_GET['mat_number'] : '';
$clientName = isset($_GET['clientName']) ? $_GET['clientName'] : '';
$nickName = isset($_GET['nickName']) ? $_GET['nickName'] : '';
$dob = isset($_GET['dob']) ? $_GET['dob'] : '';
$sex = isset($_GET['sex']) ? $_GET['sex'] : '';
$current_status = isset($_GET['current_status']) ? $_GET['current_status'] : '';

// If no p_id provided, redirect back
if (!$p_id) {
    header('Location: dispensingData.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = '';
    $error = '';

    // Handle file upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['photo'];

        // Check for errors
        if ($file['error'] === UPLOAD_ERR_OK) {
            // Validate file type
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = mime_content_type($file['tmp_name']);

            if (in_array($file_type, $allowed_types)) {
                // Read file content
                $image_data = file_get_contents($file['tmp_name']);
                $success = savePhoto($conn, $p_id, $mat_id, $mat_number, $clientName, $nickName, $dob, $sex, $current_status, $image_data);

                if ($success) {
                    $message = "Photo uploaded successfully!";
                } else {
                    $error = "Error saving photo to database.";
                }
            } else {
                $error = "Invalid file type. Only JPEG, PNG, and GIF are allowed.";
            }
        } else {
            $error = "Error uploading file. Please try again.";
        }
    }
    // Handle camera capture
    else if (isset($_POST['cameraImage'])) {
        $image_data = $_POST['cameraImage'];
        // Remove data URL prefix
        $image_data = str_replace('data:image/png;base64,', '', $image_data);
        $image_data = str_replace(' ', '+', $image_data);
        $image_data = base64_decode($image_data);

        if ($image_data) {
            $success = savePhoto($conn, $p_id, $mat_id, $mat_number, $clientName, $nickName, $dob, $sex, $current_status, $image_data);

            if ($success) {
                $message = "Photo captured successfully!";
            } else {
                $error = "Error saving captured photo to database.";
            }
        } else {
            $error = "Invalid image data. Please try again.";
        }
    }
}

// Function to save photo to database
function savePhoto($conn, $p_id, $mat_id, $mat_number, $clientName, $nickName, $dob, $sex, $current_status, $image_data) {
    // Check if photo already exists for this patient
    $checkQuery = "SELECT * FROM photos WHERE p_id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param('i', $p_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        // Update existing photo
        $updateQuery = "UPDATE photos SET visitDate = NOW(), mat_id = ?, mat_number = ?, clientName = ?, nickName = ?, dob = ?, sex = ?, current_status = ?, Image = ? WHERE p_id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $null = null;
        $updateStmt->bind_param('ssssssbsi', $mat_id, $mat_number, $clientName, $nickName, $dob, $sex, $current_status, $null, $p_id);
        $updateStmt->send_long_data(7, $image_data);
        $result = $updateStmt->execute();
        $updateStmt->close();
    } else {
        // Insert new photo
        $insertQuery = "INSERT INTO photos (p_id, visitDate, mat_id, mat_number, clientName, nickName, dob, sex, current_status, Image) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        $null = null;
        $insertStmt->bind_param('isssssssb', $p_id, $mat_id, $mat_number, $clientName, $nickName, $dob, $sex, $current_status, $null);
        $insertStmt->send_long_data(8, $image_data);
        $result = $insertStmt->execute();
        $insertStmt->close();
    }

    $checkStmt->close();
    return $result;
}

// Fetch current photo if exists
$currentPhoto = null;
$photoQuery = "SELECT Image FROM photos WHERE p_id = ?";
$photoStmt = $conn->prepare($photoQuery);
$photoStmt->bind_param('i', $p_id);
$photoStmt->execute();
$photoResult = $photoStmt->get_result();

if ($photoResult->num_rows > 0) {
    $photoRow = $photoResult->fetch_assoc();
    $currentPhoto = $photoRow['Image'];
}
$photoStmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Patient Photo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container {
            max-width: 800px;
            margin-top: 30px;
        }
        .photo-preview {
            max-width: 200px;
            max-height: 200px;
            margin: 20px auto;
            display: block;
            border: 2px solid #ddd;
            border-radius: 5px;
        }
        .camera-container {
            margin-bottom: 20px;
            text-align: center;
        }
        #cameraPreview {
            width: 100%;
            max-width: 400px;
            height: 300px;
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            margin-bottom: 10px;
        }
        .btn-camera {
            margin: 5px;
        }
        .nav-tabs {
            margin-bottom: 20px;
        }
        .camera-error {
            color: #dc3545;
            margin-top: 10px;
        }
        .permission-help {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2 class="text-center">Upload Patient Photo</h2>
                <p class="text-center text-muted">Patient: <?php echo htmlspecialchars($clientName ?? ''); ?> (<?php echo htmlspecialchars($mat_id ?? ''); ?>)</p>
            </div>
            <div class="card-body">
                <?php if (isset($message)): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($currentPhoto): ?>
                    <div class="text-center">
                        <h4>Current Photo</h4>
                        <img src="data:image/jpeg;base64,<?php echo base64_encode($currentPhoto); ?>"
                             alt="Current Photo" class="photo-preview">
                    </div>
                <?php else: ?>
                    <p class="text-center">No photo available</p>
                <?php endif; ?>

                <ul class="nav nav-tabs" id="photoTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="upload-tab" data-bs-toggle="tab" data-bs-target="#upload" type="button" role="tab">Upload Photo</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="camera-tab" data-bs-toggle="tab" data-bs-target="#camera" type="button" role="tab">Take Photo</button>
                    </li>
                </ul>

                <div class="tab-content" id="photoTabsContent">
                    <!-- Upload Tab -->
                    <div class="tab-pane fade show active" id="upload" role="tabpanel">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="photo" class="form-label">Select Photo</label>
                                <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Upload Photo</button>
                        </form>
                    </div>

                    <!-- Camera Tab -->
                    <div class="tab-pane fade" id="camera" role="tabpanel">
                        <div class="camera-container">
                            <video id="cameraPreview" autoplay playsinline></video>
                            <canvas id="photoCanvas" style="display: none;"></canvas>

                            <div id="cameraError" class="camera-error" style="display: none;">
                                Unable to access camera. Please check permissions.
                            </div>

                            <div class="permission-help" style="display: none;" id="permissionHelp">
                                <h5>Camera Permission Help</h5>
                                <p>To enable camera access:</p>
                                <ol>
                                    <li>Make sure you're using <strong>HTTPS</strong> or <strong>localhost</strong></li>
                                    <li>Check your browser's permission settings for this site</li>
                                    <li>Ensure no other application is using your camera</li>
                                    <li>Try refreshing the page and allowing camera access when prompted</li>
                                </ol>
                            </div>

                            <div class="d-flex justify-content-center">
                                <button id="captureBtn" class="btn btn-primary btn-camera" style="display: none;">Capture Photo</button>
                                <button id="retakeBtn" class="btn btn-secondary btn-camera" style="display: none;">Retake</button>
                                <button id="startCameraBtn" class="btn btn-info btn-camera">Start Camera</button>
                            </div>

                            <div id="capturedPhotoContainer" style="display: none; text-align: center; margin-top: 20px;">
                                <h4>Captured Photo</h4>
                                <img id="capturedPhoto" class="photo-preview">
                            </div>

                            <form method="POST" id="cameraForm" style="display: none; margin-top: 20px;">
                                <input type="hidden" name="cameraImage" id="cameraImage">
                                <button type="submit" class="btn btn-success w-100">Save Photo</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="mt-3 text-center">
                    <a href="dispensingData.php?mat_id=<?php echo htmlspecialchars($mat_id ?? ''); ?>"
                       class="btn btn-secondary">Back to Dispensing</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Camera functionality
        const video = document.getElementById('cameraPreview');
        const canvas = document.getElementById('photoCanvas');
        const captureBtn = document.getElementById('captureBtn');
        const retakeBtn = document.getElementById('retakeBtn');
        const startCameraBtn = document.getElementById('startCameraBtn');
        const capturedPhoto = document.getElementById('capturedPhoto');
        const capturedPhotoContainer = document.getElementById('capturedPhotoContainer');
        const cameraForm = document.getElementById('cameraForm');
        const cameraImage = document.getElementById('cameraImage');
        const cameraError = document.getElementById('cameraError');
        const permissionHelp = document.getElementById('permissionHelp');
        let stream = null;

        // Start camera when camera tab is shown
        document.getElementById('camera-tab').addEventListener('shown.bs.tab', function() {
            // Don't auto-start, wait for user to click the button
            cameraError.style.display = 'none';
            permissionHelp.style.display = 'block';
        });

        // Stop camera when leaving camera tab
        document.getElementById('upload-tab').addEventListener('shown.bs.tab', function() {
            stopCamera();
            resetCameraUI();
        });

        // Manual camera start button
        startCameraBtn.addEventListener('click', function() {
            startCamera();
        });

        async function startCamera() {
            try {
                // Reset UI
                cameraError.style.display = 'none';
                startCameraBtn.style.display = 'none';
                captureBtn.style.display = 'inline-block';

                // Try to get camera access
                stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: 'environment',
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    },
                    audio: false
                });

                video.srcObject = stream;
                permissionHelp.style.display = 'none';
            } catch (err) {
                console.error("Error accessing camera: ", err);
                cameraError.style.display = 'block';
                permissionHelp.style.display = 'block';
                startCameraBtn.style.display = 'inline-block';
                captureBtn.style.display = 'none';
            }
        }

        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
        }

        function resetCameraUI() {
            video.style.display = 'block';
            captureBtn.style.display = 'none';
            retakeBtn.style.display = 'none';
            startCameraBtn.style.display = 'inline-block';
            capturedPhotoContainer.style.display = 'none';
            cameraForm.style.display = 'none';
            permissionHelp.style.display = 'none';
        }

        captureBtn.addEventListener('click', function() {
            // Set canvas size to match video
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;

            // Draw current video frame to canvas
            const context = canvas.getContext('2d');
            context.drawImage(video, 0, 0, canvas.width, canvas.height);

            // Convert canvas to data URL
            const imageData = canvas.toDataURL('image/png');
            capturedPhoto.src = imageData;
            cameraImage.value = imageData;

            // Show captured photo and hide video
            capturedPhotoContainer.style.display = 'block';
            video.style.display = 'none';
            captureBtn.style.display = 'none';
            retakeBtn.style.display = 'inline-block';
            cameraForm.style.display = 'block';

            // Stop camera
            stopCamera();
        });

        retakeBtn.addEventListener('click', function() {
            // Hide captured photo and show video
            capturedPhotoContainer.style.display = 'none';
            video.style.display = 'block';
            captureBtn.style.display = 'inline-block';
            retakeBtn.style.display = 'none';
            cameraForm.style.display = 'none';
            startCameraBtn.style.display = 'none';

            // Restart camera
            startCamera();
        });

        // Clean up when leaving page
        window.addEventListener('beforeunload', function() {
            stopCamera();
        });
    </script>
</body>
</html>
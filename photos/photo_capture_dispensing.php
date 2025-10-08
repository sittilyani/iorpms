<?php
session_start();
include "../includes/config.php";

// Get the user_id from the query parameter and action type
$userId = isset($_GET['p_id']) ? $_GET['p_id'] : null;
$action = isset($_GET['action']) ? $_GET['action'] : 'capture';

// Fetch the current settings for the user
$currentSettings = [];
if ($userId) {
    $query = "SELECT * FROM patients WHERE p_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentSettings = $result->fetch_assoc();
}

// Check if photo already exists for this patient
$existingPhoto = null;
if ($userId && $currentSettings) {
    $photoQuery = "SELECT * FROM photos WHERE mat_id = ? ORDER BY visitDate DESC LIMIT 1";
    $photoStmt = $conn->prepare($photoQuery);
    $photoStmt->bind_param('s', $currentSettings['mat_id']);
    $photoStmt->execute();
    $photoResult = $photoStmt->get_result();
    $existingPhoto = $photoResult->fetch_assoc();
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $visitDate = $_POST['visitDate'];
    $mat_id = $_POST['mat_id'];
    $clientName = $_POST['clientName'];
    $sex = $_POST['sex'];
    $formAction = $_POST['action'];

    // Get image data from base64 format
    $encoded_image = $_POST['webcam'];
    if (empty($_POST['webcam'])) {
        echo "No image data received.";
        exit();
    }

    // Decode base64 string to binary
    $encoded_image = str_replace('data:image/jpeg;base64,', '', $encoded_image);
    $decoded_image = base64_decode($encoded_image);

    if ($decoded_image === false) {
        die("Failed to decode image.");
    }

    // Define the path to save the image
    $image_filename = $mat_id . '_' . time() . '.jpeg';
    $image_path = '../clientPhotos/' . $image_filename;

    // Create the clientPhotos directory if it doesn't exist
    if (!is_dir('../clientPhotos/')) {
        mkdir('../clientPhotos/', 0755, true);
    }

    // Save the image to the file system
    if (file_put_contents($image_path, $decoded_image)) {
        if ($formAction === 'update' && $existingPhoto) {
            // Delete old photo file if it exists
            $oldPhotoPath = '../clientPhotos/' . $existingPhoto['image'];
            if (file_exists($oldPhotoPath)) {
                unlink($oldPhotoPath);
            }

            // Update existing photo record
            $sql = "UPDATE photos SET visitDate = ?, image = ? WHERE mat_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $visitDate, $image_filename, $mat_id);
            $successMessage = "Photo updated successfully.";
        } else {
            // Insert new photo record
            $sql = "INSERT INTO photos (visitDate, mat_id, clientName, sex, image) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $visitDate, $mat_id, $clientName, $sex, $image_filename);
            $successMessage = "Photo captured and saved successfully.";
        }

        if ($stmt->execute()) {
            // Redirect after a delay
            header("refresh:2; url=client_search.php?message=" . urlencode($successMessage));
            exit();
        } else {
            echo "Error " . ($formAction === 'update' ? "updating" : "inserting") . " photo details: " . $conn->error;
        }
    } else {
        echo "Error saving image to file system.";
    }

    $stmt->close();
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Photo <?php echo ucfirst($action); ?></title>
    <style>
        .content-main{
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            background-color: #ccccff;
            margin: 0 50px;
            padding: 20px;
        }
        input, select{
            width: 250px;
            height: 30px;
            margin-bottom: 15px;
            margin-top: 10px;
        }
        label{
            font-weight: bold;
        }
        h2{
            color: #2C3162;
            margin-top: 20px;
            margin-left: 50px;
        }
        #btn-submit{
            width: 250px;
            color: white;
            background-color: #2C3162;
            height: 35px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            margin-top: 10px;
        }
        .readonly-input{
            background-color: #E8E8E8;
            cursor: not-allowed;
        }

        /* Style the webcam video and canvas */
        .camera-container {
            position: relative;
            width: 320px;
            height: 240px;
            border: 2px solid #2C3162;
            border-radius: 5px;
            background-color: black;
        }
        #video, #canvas, #photo-preview {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        #capture-button {
            position: absolute;
            bottom: 10px;
            left: calc(50% - 50px);
            z-index: 10;
            width: 100px;
            height: 30px;
            background-color: #2C3162;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        #retake-button {
            position: absolute;
            bottom: 10px;
            left: calc(50% - 50px);
            z-index: 10;
            width: 100px;
            height: 30px;
            background-color: #ff4444;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: none;
        }
        #error-message {
            color: red;
            margin: 10px 50px;
            padding: 10px;
            border: 1px solid red;
            border-radius: 5px;
            display: none;
        }
        .webcam-status {
            margin: 10px 50px;
            padding: 10px;
            border-radius: 5px;
        }
        .status-ok {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .preview-title {
            text-align: center;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .action-info {
            margin: 10px 50px;
            padding: 10px;
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            color: #856404;
        }
        .existing-photo {
            margin: 10px 50px;
            padding: 10px;
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 5px;
            color: #0c5460;
        }
        .existing-photo img {
            max-width: 150px;
            max-height: 150px;
            border: 2px solid #2C3162;
            border-radius: 5px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
<h2>Client Photo <?php echo ucfirst($action); ?> Form</h2>

<div id="error-message"></div>
<div id="webcam-status" class="webcam-status"></div>

<?php if ($action === 'update' && $existingPhoto): ?>
<div class="existing-photo">
    <strong>Existing Photo:</strong>
    <?php
    $existingPhotoPath = '../clientPhotos/' . $existingPhoto['image'];
    if (file_exists($existingPhotoPath)):
    ?>
        <br><img src="<?php echo $existingPhotoPath; ?>" alt="Current Photo">
        <br><small>This photo will be replaced when you capture a new one.</small>
    <?php else: ?>
        <br><em>Photo file not found. Please capture a new photo.</em>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="action-info">
    <?php
    $clientName = !empty($currentSettings['clientName']) ? htmlspecialchars($currentSettings['clientName']) : 'Unknown';
    if ($action === 'update') {
        echo "You are updating the photo for $clientName. The existing photo will be replaced.";
    } else {
        echo "You are capturing a new photo for $clientName.";
    }
    ?>
</div>

<form id="photo-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?p_id=" . $userId . "&action=" . $action; ?>" method="post" enctype="multipart/form-data">
    <input type="hidden" name="action" value="<?php echo $action; ?>">

    <div class="content-main">
        <div class="grid-item">
            <label for="visitDate">Photo Capture Date</label><br>
            <input type="text" name="visitDate" class="readonly-input" readonly value="<?php echo date('Y-m-d'); ?>"><br>

            <label for="mat_id">MAT ID</label><br>
            <input type="text" name="mat_id" class="readonly-input" readonly value="<?php echo !empty($currentSettings['mat_id']) ? htmlspecialchars($currentSettings['mat_id']) : ''; ?>"><br>

            <label for="clientName">Client Name</label><br>
            <input type="text" name="clientName" class="readonly-input" readonly value="<?php echo !empty($currentSettings['clientName']) ? htmlspecialchars($currentSettings['clientName']) : ''; ?>"><br>

            <label for="sex">Gender</label><br>
            <input type="text" name="sex" class="readonly-input" readonly value="<?php echo !empty($currentSettings['sex']) ? htmlspecialchars($currentSettings['sex']) : ''; ?>"><br>
        </div>

        <div class="grid-item">
            <div class="preview-title">Live Camera</div>
            <div class="camera-container">
                <video id="video" width="320" height="240" autoplay playsinline></video>
                <button type="button" id="capture-button">Capture</button>
                <button type="button" id="retake-button">Retake</button>
            </div>
        </div>

        <div class="grid-item">
            <div class="preview-title">Captured Photo</div>
            <div class="camera-container">
                <canvas id="canvas" width="320" height="240" style="display: none;"></canvas>
                <img id="photo-preview" src="" alt="Captured Photo" style="display: none;">
                <input type="hidden" id="webcam" name="webcam">
                <input type="submit" id="btn-submit" value="<?php echo $action === 'update' ? 'Update Photo' : 'Save Photo'; ?>" disabled style="position: absolute; bottom: 10px; left: calc(50% - 60px); width: 120px;">
            </div>
        </div>
    </div>
</form>

<!-- Script to handle webcam capture -->
<script>
    var video = document.getElementById('video');
    var canvas = document.getElementById('canvas');
    var photoPreview = document.getElementById('photo-preview');
    var captureButton = document.getElementById('capture-button');
    var retakeButton = document.getElementById('retake-button');
    var webcamInput = document.getElementById('webcam');
    var submitButton = document.getElementById('btn-submit');
    var form = document.getElementById('photo-form');
    var errorMessage = document.getElementById('error-message');
    var webcamStatus = document.getElementById('webcam-status');

    // Function to update status message
    function updateStatus(message, isError) {
        webcamStatus.textContent = message;
        webcamStatus.className = 'webcam-status ' + (isError ? 'status-error' : 'status-ok');
        webcamStatus.style.display = 'block';
    }

    // Function to show error message
    function showError(message) {
        errorMessage.textContent = message;
        errorMessage.style.display = 'block';
    }

    // Function to hide error message
    function hideError() {
        errorMessage.style.display = 'none';
    }

    // Initialize webcam
    function initWebcam() {
        // Check if browser supports mediaDevices
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showError('Your browser does not support webcam access. Please try using Chrome, Firefox, or Edge.');
            updateStatus('Browser not supported', true);
            return false;
        }

        // Get webcam stream with better error handling
        navigator.mediaDevices.getUserMedia({
            video: {
                width: { ideal: 320 },
                height: { ideal: 240 },
                facingMode: 'user' // Use front camera
            },
            audio: false
        })
        .then(function(stream) {
            video.srcObject = stream;
            captureButton.disabled = false;
            updateStatus('Webcam connected successfully. You can now capture a photo.', false);

            // Handle when video starts playing
            video.addEventListener('loadeddata', function() {
                console.log('Video is playing');
                video.style.display = 'block';
            });

            // Handle video errors
            video.addEventListener('error', function(e) {
                showError('Video error: ' + e.message);
                updateStatus('Video error occurred', true);
            });
        })
        .catch(function(error) {
            console.error('Error accessing webcam:', error);
            var errorMsg = 'Error accessing webcam: ';

            if (error.name === 'PermissionDeniedError' || error.name === 'NotAllowedError') {
                errorMsg += 'Permission denied. Please allow camera access and reload the page.';
            } else if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
                errorMsg += 'No camera found. Please check if your camera is connected.';
            } else if (error.name === 'NotSupportedError') {
                errorMsg += 'Camera not supported. Please try a different browser.';
            } else if (error.name === 'NotReadableError' || error.name === 'TrackStartError') {
                errorMsg += 'Camera is already in use by another application. Please close other applications using the camera.';
            } else {
                errorMsg += error.message;
            }

            showError(errorMsg);
            updateStatus('Webcam access failed', true);
        });

        return true;
    }

    // Capture photo from webcam
    captureButton.addEventListener('click', function() {
        try {
            var context = canvas.getContext('2d');

            // Draw current video frame to canvas
            context.drawImage(video, 0, 0, canvas.width, canvas.height);

            // Convert canvas to data URL
            var imageDataUrl = canvas.toDataURL('image/jpeg', 0.8);

            // Set the hidden input value
            webcamInput.value = imageDataUrl;

            // Display the captured photo
            photoPreview.src = imageDataUrl;
            photoPreview.style.display = 'block';
            canvas.style.display = 'none';

            // Hide video and show retake button
            video.style.display = 'none';
            captureButton.style.display = 'none';
            retakeButton.style.display = 'block';

            // Enable submit button
            submitButton.disabled = false;

            updateStatus('Photo captured! You can save it or retake if needed.', false);
        } catch (error) {
            showError('Error capturing photo: ' + error.message);
            updateStatus('Photo capture failed', true);
        }
    });

    // Retake photo
    retakeButton.addEventListener('click', function() {
        // Show video again
        video.style.display = 'block';
        captureButton.style.display = 'block';
        retakeButton.style.display = 'none';

        // Hide photo preview
        photoPreview.style.display = 'none';
        canvas.style.display = 'none';

        // Clear the captured image
        webcamInput.value = '';
        submitButton.disabled = true;

        updateStatus('Ready to capture a new photo.', false);
    });

    // Form submission validation
    form.addEventListener('submit', function(event) {
        if (!webcamInput.value) {
            event.preventDefault();
            showError('Please capture a photo before submitting.');
            updateStatus('No photo captured', true);
        }
    });

    // Try alternative approach if initial method fails
    function tryAlternativeWebcamAccess() {
        // Try with simpler constraints
        var constraints = { video: true };

        navigator.mediaDevices.getUserMedia(constraints)
        .then(function(stream) {
            video.srcObject = stream;
            captureButton.disabled = false;
            updateStatus('Webcam connected using alternative method.', false);
        })
        .catch(function(error) {
            console.error('Alternative method also failed:', error);
            showError('All webcam access methods failed. Please check your camera permissions and try refreshing the page.');
        });
    }

    // Add a retry button for webcam access
    var retryButton = document.createElement('button');
    retryButton.textContent = 'Retry Webcam Access';
    retryButton.style.margin = '10px 50px';
    retryButton.style.padding = '10px';
    retryButton.style.backgroundColor = '#2C3162';
    retryButton.style.color = 'white';
    retryButton.style.border = 'none';
    retryButton.style.borderRadius = '5px';
    retryButton.style.cursor = 'pointer';
    retryButton.addEventListener('click', function() {
        hideError();
        updateStatus('Attempting to access webcam...', false);
        tryAlternativeWebcamAccess();
    });

    document.body.appendChild(retryButton);

    // Initialize webcam when page loads
    window.addEventListener('load', function() {
        initWebcam();
    });
</script>
</body>
</html>
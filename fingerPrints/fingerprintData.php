<?php
session_start();
include "../includes/config.php";


// Get the user_id from the query parameter (if applicable)
$userId = isset($_GET['p_id']) ? $_GET['p_id'] : null;

// Fetch the current settings for the user (if applicable)
$currentSettings = [];
if ($userId) {
    $query = "SELECT * FROM patients WHERE p_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentSettings = $result->fetch_assoc();
}

?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Finger Print Capture</title>
    <link rel="stylesheet" href="../assets/css/forms.css" type="text/css">

    <style>
        .form{
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            margin: 0 50px;
            padding: 20px;
        }

        /* Style the webcam video and canvas */
        #webcam-container {
            position: relative;
            width: 320px;
            height: 240px;
        }
        #video, #canvas {
            position: absolute;
            top: 0;
            left: 0;
        }
        #capture-button {
            position: absolute;
            bottom: 10px;
            left: calc(50% - 50px); /* Center the button horizontally */
            z-index: 1; /* Ensure the button is above the canvas */
        }
    </style>
</head>
<body>
    <div class="content-main">
        <h2>Client FingerPrint Capture Form</h2>

    <form id="fingerprint-form" action="fingerprintData_process.php" method="post" class="post" enctype="multipart/form-data">

        <div class="form-group">
            <label for="visitDate">Finger Print Capture Date</label><br>
            <input type="text" name="visitDate" class="readonly-input" readonly value="<?php echo date('Y-m-d'); ?>"><br>
        </div>
        <div class="form-group">
            <label for="mat_id">MAT ID</label><br>
            <input type="text" name="mat_id" class="readonly-input" readonly value="<?php echo isset($currentSettings['mat_id']) ? $currentSettings['mat_id'] : ''; ?>"><br>
        </div>
        <div class="form-group">
            <label for="mat_number">MAT Number</label><br>
            <input type="text" name="mat_number" class="readonly-input" readonly value="<?php echo isset($currentSettings['mat_number']) ? $currentSettings['mat_number'] : ''; ?>"><br>

            <label for="clientName">Client Name</label><br>
            <input type="text" name="clientName" class="readonly-input" readonly value="<?php echo isset($currentSettings['clientName']) ? $currentSettings['clientName'] : ''; ?>"><br>
        </div>
        <div class="form-group">
            <label for="nickName">Nick Name</label><br>
            <input type="text" name="nickName" class="readonly-input" readonly value="<?php echo isset($currentSettings['nickName']) ? $currentSettings['nickName'] : ''; ?>"><br>
        </div>
        <div class="form-group">
            <label for="dob">Date of Birth</label><br>
            <input type="text" name="dob" value="<?php echo isset($currentSettings['dob']) ? $currentSettings['dob'] : ''; ?>"> <br>
        </div>
        <div class="form-group">
            <label for="sex">Gender</label><br>
            <input type="text" name="sex" class="readonly-input" readonly value="<?php echo isset($currentSettings['sex']) ? $currentSettings['sex'] : ''; ?>"><br>
        </div>
        <div class="form-group">
            <label for="current_status">Current Status</label><br>
            <input type="text" name="current_status" class="readonly-input" readonly value="<?php echo isset($currentSettings['current_status']) ? $currentSettings['current_status'] : ''; ?>"><br>
        </div>
        <div class="form-group">
                <div id="fingerprint-capture">

                    <label for="fingerprint_data">Place your finger on the scanner.</label><br>
                    <button id="capture-button">Finger Print Capture</button>
                </div>
        </div>
        <div class="form-group">
                <input type="blob" name="fingerprint_data" id="fingerprintdata" value="Fingerprint Data (Simulated)">

                <input type="submit" id="btn-submit" value="Submit">
            </div>
        </form>
    </div>
    <!-- Script to handle fingerprint capture -->
            <script>
                // Get the capture button and fingerprint data input
                const captureButton = document.getElementById('capture-button');
                const fingerprintDataInput = document.getElementById('fingerprint-data');

                // Simulate fingerprint capture on button click
                captureButton.addEventListener('click', () => {
                    fingerprintDataInput.value = "Fingerprint Captured (Simulated)";
                    captureButton.disabled = true; // Disable button after simulation
                });
            </script>
        </body>
    </html>


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
    <title>Photo Capture</title>
    <style>
        .grid-container{
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            background-color: #99FFBB;
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
            margin-top: 10px; /* Add margin to separate from other elements */
        }
        .readonly-input{
            background-color: #E8E8E8;
            cursor: not-allowed;
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
<h2>Client Photo Capture Form</h2>
<form id="photo-form" action="photoData_process.php" method="post" class="post" enctype="multipart/form-data">

    <div class="grid-container">
        <div class="grid-item">
            <label for="visitDate">Photo Capture Date</label><br>
            <input type="text" name="visitDate" class="readonly-input" readonly value="<?php echo date('Y-m-d'); ?>"><br>

            <label for="mat_id">MAT ID</label><br>
            <input type="text" name="mat_id" class="readonly-input" readonly value="<?php echo isset($currentSettings['mat_id']) ? $currentSettings['mat_id'] : ''; ?>"><br>

            <label for="mat_number">MAT Number</label><br>
            <input type="text" name="mat_number" class="readonly-input" readonly value="<?php echo isset($currentSettings['mat_number']) ? $currentSettings['mat_number'] : ''; ?>"><br>

            <label for="clientName">Client Name</label><br>
            <input type="text" name="clientName" class="readonly-input" readonly value="<?php echo isset($currentSettings['clientName']) ? $currentSettings['clientName'] : ''; ?>"><br>
        </div>
        <div class="grid-item">
            <label for="nickName">Nick Name</label><br>
            <input type="text" name="nickName" class="readonly-input" readonly value="<?php echo isset($currentSettings['nickName']) ? $currentSettings['nickName'] : ''; ?>"><br>

            <label for="dob">Date of Birth</label><br>
            <input type="text" name="dob" value="<?php echo isset($currentSettings['dob']) ? $currentSettings['dob'] : ''; ?>"><br>

            <label for="sex">Gender</label><br>
            <input type="text" name="sex" class="readonly-input" readonly value="<?php echo isset($currentSettings['sex']) ? $currentSettings['sex'] : ''; ?>"><br>

            <label for="current_status">Current Status</label><br>
            <input type="text" name="current_status" class="readonly-input" readonly value="<?php echo isset($currentSettings['current_status']) ? $currentSettings['current_status'] : ''; ?>"><br>
        </div>

        <div class="grid-item">
            <div id="webcam-container">
                <video id="video" width="320" height="240" autoplay></video>
                <button type="button" id="capture-button">Capture</button> <!-- Make it type="button" to prevent form submission -->
            </div>
        </div>
        <div class="grid-item">
            <canvas id="canvas" width="320" height="240"></canvas>
            <input type="hidden" id="webcam" name="webcam">
            <input type="submit" id="btn-submit" value="Submit">
        </div>
    </div>
</form>

<script>
// Capture photo from webcam
captureButton.addEventListener('click', function(event) {
    event.preventDefault(); // Prevent form from submitting immediately
    var context = canvas.getContext('2d');
    context.drawImage(video, 0, 0, canvas.width, canvas.height); // Capture image on canvas
    var image = canvas.toDataURL('image/jpeg'); // Convert to base64
    webcamInput.value = image; // Set the hidden input field's value to the base64 image
});

</script>

<!-- Script to handle webcam capture -->
<script>
    var video = document.getElementById('video');
    var canvas = document.getElementById('canvas');
    var captureButton = document.getElementById('capture-button');
    var webcamInput = document.getElementById('webcam');
    var form = document.getElementById('photo-form');

    // Get webcam stream
    navigator.mediaDevices.getUserMedia({ video: true })
        .then(function(stream) {
            video.srcObject = stream;
        })
        .catch(function(error) {
            console.log('Error accessing webcam:', error);
        });

    // Capture photo from webcam
    captureButton.addEventListener('click', function() {
        var context = canvas.getContext('2d');
        context.drawImage(video, 0, 0, canvas.width, canvas.height); // Use canvas dimensions
        var image = canvas.toDataURL('image/jpeg');
        webcamInput.value = image;
    });
</script>
</body>
</html>



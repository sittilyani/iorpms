<?php
session_start();
include('../includes/config.php');

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $visitDate = $_POST['visitDate'];
    $mat_id = $_POST['mat_id'];
    $mat_number = $_POST['mat_number'];
    $clientName = $_POST['clientName'];
    $nickName = $_POST['nickName'];
    $dob = $_POST['dob'];
    $sex = $_POST['sex'];
    $current_status = $_POST['current_status'];

    // Get image data from base64 format
    $encoded_image = $_POST['webcam'];

    // Check if encoded image is received
    if (empty($encoded_image)) {
        die("No image data received.");
    }

    // Decode base64 string to binary
    $encoded_image = str_replace('data:image/jpeg;base64,', '', $encoded_image);
    $decoded_image = base64_decode($encoded_image);

    if ($decoded_image === false) {
        die("Failed to decode image.");
    }

    // Prepare SQL statement to insert data into the photos table
    $sql = "INSERT INTO photos (visitDate, mat_id, mat_number, clientName, nickName, dob, sex, current_status, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssss", $visitDate, $mat_id, $mat_number, $clientName, $nickName, $dob, $sex, $current_status, $decoded_image);

    if ($stmt->execute()) {
        echo "Photo captured and details inserted successfully.";
        // Redirect after a delay
        header("refresh:3; url=index.php");
    } else {
        echo "Error capturing photo and inserting details: " . $conn->error;
    }

    $stmt->close();
}
?>


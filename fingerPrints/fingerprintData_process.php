

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

    // Debugging: Output received form data
    echo "Visit Date: $visitDate<br>";
    echo "MAT ID: $mat_id<br>";
    echo "MAT Number: $mat_number<br>";
    echo "Client Name: $clientName<br>";
    echo "Nick Name: $nickName<br>";
    echo "Date of Birth: $dob<br>";
    echo "Sex: $sex<br>";
    echo "Current Status: $current_status<br>";

    // Get fingerprint_data based on scanner format (modify as needed)
    $encoded_fingerprint_data = isset($_POST['fingerprint_data']) ? $_POST['fingerprint_data'] : null;

    // Decode fingerprint_data based on format (modify as needed)
    // Assuming base64 encoding, remove data URI prefix if present
    if ($encoded_fingerprint_data) {
        $encoded_fingerprint_data = str_replace('data:fingerprint_data/jpeg;base64,', '', $encoded_fingerprint_data);
        $decoded_fingerprint_data = base64_decode($encoded_fingerprint_data);
    } else {
        // Handle case where fingerprint_data is missing
        echo "Error: Fingerprint data not received from scanner.<br>";
    }

    // Define the path to save the fingerprint (if applicable)
    if (isset($decoded_fingerprint_data)) {
        $fingerprint_data_path = '../fingerPrintFolder/' . $mat_id . '_' . time() . '.jpeg';

        // Debugging: Output fingerprint_data path
        echo "fingerprint_data Path: $fingerprint_data_path<br>";

        try {
            // Save the fingerprint_data to the server with error handling
            $save_result = file_put_contents($fingerprint_data_path, $decoded_fingerprint_data);
            if ($save_result === false) {
                throw new Exception("Failed to save fingerprint data.");
            }
        } catch (Exception $e) {
            echo "Error saving fingerprint_data: " . $e->getMessage() . "<br>";
        }
    }

    // Insert data into the photos table
    $sql = "INSERT INTO fingerprints (visitDate, mat_id, mat_number, clientName, nickName, dob, sex, current_status, fingerprint_data) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    // Consider using a parameterized query to prevent SQL injection vulnerabilities
    $fingerprint_data_path = isset($fingerprint_data_path) ? $fingerprint_data_path : null;
    $stmt->bind_param("sssssssss", $visitDate, $mat_id, $mat_number, $clientName, $nickName, $dob, $sex, $current_status, $fingerprint_data_path);
    if ($stmt->execute()) {
        $message = "Finger Print captured and details inserted successfully.";
    } else {
        $message = "Error capturing finger print and inserting details: " . $conn->error;
    }
    $stmt->close();

    // Debugging: Output success message
    echo $message;
}
?>


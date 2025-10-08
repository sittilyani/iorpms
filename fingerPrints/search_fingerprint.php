<?php
header('Content-Type: application/json');

include "../includes/config.php";

$host = 'localhost';
$db = 'methadone';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_error) {
    echo json_encode(['found' => false, 'message' => 'Database connection failed']);
    exit;
}

if (isset($_POST['fingerprint_template'])) {
    $captured_template = base64_decode($_POST['fingerprint_template']);

    // Query all fingerprint templates
    $query = "SELECT f.id, f.p_id, f.fingerprint_template, p.clientName FROM fingerprints f JOIN patients p ON f.p_id = p.p_id";
    $result = $mysqli->query($query);

    $match_found = false;
    $patient_name = '';

    while ($row = $result->fetch_assoc()) {
        // Call external API or SDK to compare templates
        // This is a placeholder; replace with actual comparison logic
        $match_score = callComparisonAPI($captured_template, $row['fingerprint_template']);

        if ($match_score > 50) { // Adjust threshold based on SDK documentation
            $match_found = true;
            $patient_name = $row['clientName'];
            break;
        }
    }

    echo json_encode([
        'found' => $match_found,
        'patient_name' => $patient_name,
        'message' => $match_found ? 'Match found' : 'No match found'
    ]);
} else {
    echo json_encode(['found' => false, 'message' => 'No fingerprint data provided']);
}

function callComparisonAPI($template1, $template2) {
    // Placeholder: Implement call to ZKFinger SDK or Cams Biometrics Compare API
    // Example: Send templates to a local Python/C# server that uses ZKFinger SDK
    return 0; // Replace with actual comparison logic
}
?>
<?php
// Enable error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connect to database
$mysqli = new mysqli("localhost", "root", "", "methadone");

// Check connection
if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed: " . $mysqli->connect_error]);
    exit;
}

// Get search term from the request
$term = $_GET['term'] ?? '';

// Prepare results array
$results = [];

if (!empty($term)) {
    // Use prepared statement to prevent SQL injection
    $stmt = $mysqli->prepare("SELECT p_id, clientName FROM patients WHERE clientName LIKE CONCAT('%', ?, '%') OR p_id LIKE CONCAT('%', ?, '%') LIMIT 10");
    $stmt->bind_param("ss", $term, $term);
    $stmt->execute();
    $res = $stmt->get_result();

    // Build result array
    while ($row = $res->fetch_assoc()) {
        $results[] = [
            'label' => $row['p_id'] . ' - ' . $row['clientName'], // What appears in the dropdown
            'value' => $row['p_id'] . ' - ' . $row['clientName'], // What goes into the input field
            'name' => $row['clientName'],        // To populate patient name
            'id' => $row['p_id']     // To populate patient ID
        ];
    }

    $stmt->close();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($results);
?>

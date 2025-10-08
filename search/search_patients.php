<?php
// api/search_patients.php
require_once('../includes/config.php');

header('Content-Type: application/json');

if (!isset($_GET['term']) || strlen($_GET['term']) < 2) {
        echo json_encode([]);
        exit;
}

$searchTerm = '%' . $_GET['term'] . '%';

$query = "SELECT mat_id, clientName, p_address, current_status, dosage
                    FROM patients
                    WHERE mat_id LIKE ? OR clientName LIKE ?
                    ORDER BY clientName ASC
                    LIMIT 20";

$stmt = $mysqli->prepare($query);
$stmt->bind_param('ss', $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

$patients = [];
while ($row = $result->fetch_assoc()) {
        $patients[] = $row;
}

echo json_encode($patients);
?>
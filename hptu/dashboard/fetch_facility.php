<?php
// dashboard/fetch_facility.php
session_start();
include '../includes/config.php';

header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$facilityname = trim($_POST['facilityname'] ?? '');

if (empty($facilityname)) {
    echo json_encode([]);
    exit;
}

// Use prepared statement to prevent SQL injection
$stmt = $conn->prepare("SELECT level_of_care, mflcode, countyname, subcountyname FROM facilities WHERE facilityname = ?");
$stmt->bind_param("s", $facilityname);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        'level_of_care' => $row['level_of_care'] ?? '',
        'mflcode'       => $row['mflcode'] ?? '',
        'countyname'    => $row['countyname'] ?? '',
        'subcountyname' => $row['subcountyname'] ?? ''
    ]);
} else {
    echo json_encode([
        'level_of_care' => '',
        'mflcode'       => '',
        'countyname'    => '',
        'subcountyname' => ''
    ]);
}

$stmt->close();
exit;
?>
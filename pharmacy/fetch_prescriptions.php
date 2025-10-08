<?php
include '../includes/config.php';

$limit = 15;
$page = isset($_POST['page']) ? intval($_POST['page']) : 1;
$start = ($page - 1) * $limit;

// Build the base query
$sql = "SELECT * FROM other_prescriptions WHERE prescr_status != 'submitted'";
$params = [];
$types = '';

// Add filters to the query
if (!empty($_POST['client_name'])) {
    $sql .= " AND clientName LIKE ?";
    $params[] = '%' . $_POST['client_name'] . '%';
    $types .= 's';
}
if (!empty($_POST['mat_id'])) {
    $sql .= " AND mat_id LIKE ?";
    $params[] = '%' . $_POST['mat_id'] . '%';
    $types .= 's';
}
if (!empty($_POST['prescriber_name'])) {
    $sql .= " AND prescriber_name LIKE ?";
    $params[] = '%' . $_POST['prescriber_name'] . '%';
    $types .= 's';
}
if (!empty($_POST['prescr_status'])) {
    $sql .= " AND prescr_status LIKE ?";
    $params[] = '%' . $_POST['prescr_status'] . '%';
    $types .= 's';
}
if (!empty($_POST['min_date']) && !empty($_POST['max_date'])) {
    $sql .= " AND prescription_date BETWEEN ? AND ?";
    $params[] = $_POST['min_date'];
    $params[] = $_POST['max_date'];
    $types .= 'ss';
}

$sql .= " ORDER BY prescription_date DESC LIMIT ?, ?";
$params[] = $start;
$params[] = $limit;
$types .= 'ii';

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $prescriptions = [];
    while ($row = $result->fetch_assoc()) {
        $prescriptions[] = $row;
    }

    // Get total number of records for pagination
    $total_sql = "SELECT COUNT(id) AS count FROM other_prescriptions WHERE prescr_status != 'submitted'";
    $total_stmt = $conn->prepare($total_sql);
    $total_stmt->execute();
    $total_result = $total_stmt->get_result();
    $total_count = $total_result->fetch_assoc()['count'];

    echo json_encode(['data' => $prescriptions, 'total_count' => $total_count]);
}
?>
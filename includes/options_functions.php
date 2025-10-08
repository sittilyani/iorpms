<?php
ob_start();
include '../includes/config.php';

// Fetch the logged-in user's name from tblusers
$service_provider = 'Unknown';
if (isset($_SESSION['user_id'])) {
    $loggedInUserId = $_SESSION['user_id'];
    $userQuery = "SELECT first_name, last_name FROM tblusers WHERE user_id = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param('i', $loggedInUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $service_provider = $user['first_name'] . ' ' . $user['last_name'];
    }
    $stmt->close();
}

// Fetch status names from the status table for the dropdown
$statusOptions = '';
$statusQuery = "SELECT status_id, status_name FROM status";
$statusResult = $conn->query($statusQuery);

if ($statusResult->num_rows > 0) {
    while ($statusRow = $statusResult->fetch_assoc()) {
        $statusName = $statusRow['status_name'];
        $selected = (isset($currentSettings['current_status']) && $statusName == $currentSettings['current_status']) ? 'selected' : '';
        $statusOptions .= "<option value='" . htmlspecialchars($statusName) . "' $selected>" . htmlspecialchars($statusName) . "</option>";
    }
} else {
    $statusOptions = "<option value=''>No status found</option>";
}
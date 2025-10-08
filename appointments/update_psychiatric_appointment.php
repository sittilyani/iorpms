<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$next_appointment = isset($_POST['next_appointment']) ? $_POST['next_appointment'] : '';
$appointment_status = isset($_POST['appointment_status']) ? $_POST['appointment_status'] : '';
$clinical_notes = isset($_POST['clinical_notes']) ? $_POST['clinical_notes'] : '';

try {
    $sql = "UPDATE medical_story SET next_appointment = ?, appointment_status = ?, clinical_notes = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssi', $next_appointment, $appointment_status, $clinical_notes, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update appointment']);
    }

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
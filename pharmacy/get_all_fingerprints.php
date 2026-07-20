<?php
// pharmacy/get_all_fingerprints.php
session_start();
include '../includes/config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$query = "SELECT mat_id, template_data, fingerprint_path FROM fingerprints";
$result = $conn->query($query);
$data = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $template = '';
        if (!empty($row['template_data'])) {
            $template = base64_encode($row['template_data']);
        } elseif (!empty($row['fingerprint_path'])) {
            // Fall back to reading from local file path
            $fullPath = dirname(__DIR__) . '/' . $row['fingerprint_path'];
            if (file_exists($fullPath)) {
                $fileBytes = file_get_contents($fullPath);
                if ($fileBytes !== false) {
                    $template = base64_encode($fileBytes);
                }
            }
        }

        if (!empty($template)) {
            $data[] = [
                'id' => $row['mat_id'],
                'template' => $template
            ];
        }
    }
}

echo json_encode([
    'success' => true,
    'candidates' => $data
]);
exit;

<?php
session_start();
include "../includes/config.php";

if (isset($_GET['p_id'])) {
    $patientId = $_GET['p_id'];

    // Get patient details
    $patientQuery = "SELECT mat_id FROM patients WHERE p_id = ?";
    $stmt = $conn->prepare($patientQuery);
    $stmt->bind_param('i', $patientId);
    $stmt->execute();
    $result = $stmt->get_result();
    $patient = $result->fetch_assoc();

    if ($patient) {
        // Get fingerprint record
        $printQuery = "SELECT fingerprint_data FROM fingerprints WHERE mat_id = ?";
        $printStmt = $conn->prepare($printQuery);
        $printStmt->bind_param('s', $patient['mat_id']);
        $printStmt->execute();
        $printResult = $printStmt->get_result();
        $fingerprint = $printResult->fetch_assoc();

        if ($fingerprint) {
            // Delete fingerprint file
            $filepath = '../fingerprints/clientsphotos/' . $fingerprint['fingerprint_data'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }

            // Delete database record
            $deleteQuery = "DELETE FROM fingerprints WHERE mat_id = ?";
            $deleteStmt = $conn->prepare($deleteQuery);
            $deleteStmt->bind_param('s', $patient['mat_id']);

            if ($deleteStmt->execute()) {
                header("Location: fingerprint_search.php?message=" . urlencode("Fingerprint deleted successfully."));
            } else {
                header("Location: fingerprint_search.php?message=" . urlencode("Error deleting fingerprint."));
            }
        }
    }
}

header("Location: fingerprint_search.php");
exit();
?>
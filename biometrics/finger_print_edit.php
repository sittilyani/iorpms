<?php
/**
 * finger_print_edit.php — Fingerprint Edit Wrapper
 * ===============================================
 * Resolves the patient's latest fingerprint record and delegates
 * directly to fingerprint_capture.php in update mode.
 */
session_start();
include '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

$p_id = isset($_GET['p_id']) ? intval($_GET['p_id']) : null;
if (!$p_id) {
    die("Patient ID required.");
}

// Find patient's mat_id
$patientQ = $conn->prepare("SELECT mat_id FROM patients WHERE p_id = ?");
if ($patientQ) {
    $patientQ->bind_param("i", $p_id);
    $patientQ->execute();
    $pat = $patientQ->get_result()->fetch_assoc();
    $patientQ->close();
}

if (!isset($pat) || !$pat) {
    die("Patient not found.");
}

// Find latest fingerprint record id
$printQ = $conn->prepare("SELECT id FROM fingerprints WHERE mat_id = ? ORDER BY capture_date DESC LIMIT 1");
if ($printQ) {
    $printQ->bind_param("s", $pat['mat_id']);
    $printQ->execute();
    $print = $printQ->get_result()->fetch_assoc();
    $printQ->close();
}

if (isset($print) && $print) {
    // Programmatically set update parameters and include capture script
    $_GET['p_id'] = $p_id;
    $_GET['id'] = $print['id'];
    $_GET['action'] = 'update';
    include 'fingerprint_capture.php';
} else {
    // Fall back to capture if no fingerprint exists
    header("Location: fingerprint_capture.php?p_id=" . $p_id);
    exit();
}
?>

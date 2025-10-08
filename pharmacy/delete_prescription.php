<?php
session_start();
include '../includes/config.php';

// Check if a user is logged in and has the required role
if (!isset($_SESSION['user_id']) || ($_SESSION['userrole'] !== 'Admin' && $_SESSION['userrole'] !== 'Pharmacist')) {
    header("Location: ../auth/login.php");
    exit;
}

$prescription_id = $_GET['id'] ?? null;
$deleted_by_name = $_SESSION['full_name'] ?? 'System';

if (!$prescription_id) {
    header("Location: view_prescriptions.php?error=no_id");
    exit;
}

// Start a transaction
$conn->begin_transaction();

try {
    // 1. Fetch the prescription data to be deleted
    $stmt_select = $conn->prepare("SELECT * FROM other_prescriptions WHERE prescription_id = ?");
    $stmt_select->bind_param("s", $prescription_id);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    $prescription_data = $result->fetch_assoc();
    $stmt_select->close();

    if (!$prescription_data) {
        throw new Exception("Prescription not found.");
    }

    // 2. Insert the record into 'deleted_prescriptions' table
    // Removed 'date_of_deletion' from bind_param as it's handled by NOW()
    $stmt_insert = $conn->prepare("INSERT INTO deleted_prescriptions (
        prescription_id, clientName, mat_id, prescription_date, prescriber_name, prescr_status,
        deleted_by, date_of_deletion
    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");

    $stmt_insert->bind_param("sssssss",
        $prescription_data['prescription_id'],
        $prescription_data['clientName'],
        $prescription_data['mat_id'],
        $prescription_data['prescription_date'],
        $prescription_data['prescriber_name'],
        $prescription_data['prescr_status'],
        $deleted_by_name
    );
    $stmt_insert->execute();
    $stmt_insert->close();

    // 3. Delete related drugs from 'prescription_drugs' table
    $stmt_delete_drugs = $conn->prepare("DELETE FROM prescription_drugs WHERE prescription_id = ?");
    $stmt_delete_drugs->bind_param("s", $prescription_id);
    $stmt_delete_drugs->execute();
    $stmt_delete_drugs->close();

    // 4. Delete the record from 'other_prescriptions' table
    $stmt_delete_prescr = $conn->prepare("DELETE FROM other_prescriptions WHERE prescription_id = ?");
    $stmt_delete_prescr->bind_param("s", $prescription_id);
    $stmt_delete_prescr->execute();
    $stmt_delete_prescr->close();

    // If all queries were successful, commit the transaction
    $conn->commit();
    header("Location: view_prescriptions.php?success=2"); // Redirect with a success message
    exit;

} catch (Exception $e) {
    // If any query failed, rollback the transaction
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}

$conn->close();
?>
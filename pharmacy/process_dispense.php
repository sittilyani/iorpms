<?php
session_start();
include '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prescription_id = $_POST['prescription_id'];
    $dispensed_amounts = $_POST['total_dispensed'];
    $drug_ids = $_POST['drug_id'];
    $total_dosages = $_POST['total_dosage'];

    $conn->begin_transaction();

    try {
        // Prepare the statement for updating prescription_drugs
        $stmt_drugs = $conn->prepare("UPDATE prescription_drugs SET total_dispensed = ?, remaining_balance = ?, prescr_status = ? WHERE id = ?");

        $all_dispensed = true;

        for ($i = 0; $i < count($drug_ids); $i++) {
            $drug_id = $drug_ids[$i];
            $dispensed = $dispensed_amounts[$i];
            $total_dosage = $total_dosages[$i];
            $remaining_balance = $total_dosage - $dispensed;

            if ($remaining_balance < 0) {
                throw new Exception("Dispensed amount cannot be greater than total dosage.");
            }

            $status = ($remaining_balance == 0) ? 'dispensed and closed' : 'partially dispensed';

            $stmt_drugs->bind_param("diis", $dispensed, $remaining_balance, $status, $drug_id);
            $stmt_drugs->execute();

            if ($remaining_balance > 0) {
                $all_dispensed = false;
            }
        }
        $stmt_drugs->close();

        // Update other_prescriptions table
        $new_prescr_status = $all_dispensed ? 'dispensed' : 'partially dispensed';

        // Corrected SQL query with IF statement for mark_symbol
        $stmt_prescr = $conn->prepare("UPDATE other_prescriptions SET prescr_status = ? WHERE prescription_id = ?");
        $stmt_prescr->bind_param("ss", $new_prescr_status, $prescription_id);
        $stmt_prescr->execute();
        $stmt_prescr->close();

        $conn->commit();

        header("Location: view_prescriptions.php?success=1");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }
}
?>
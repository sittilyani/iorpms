<?php
session_start();
include '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $prescription_id = $_POST['prescription_id'];
    $clientName = $_POST['clientName'];
    $mat_id = $_POST['mat_id'];
    $sex = $_POST['sex'];
    $age = $_POST['age'];
    $prescriber_name = $_POST['prescriber_name'];
    $prescription_date = $_POST['prescription_date'];

    // Start a transaction
    $conn->begin_transaction();

    try {
        // Insert into other_prescriptions table
        $stmt = $conn->prepare("INSERT INTO other_prescriptions (prescription_id, clientName, mat_id, sex, age, prescriber_name, prescription_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssiss", $prescription_id, $clientName, $mat_id, $sex, $age, $prescriber_name, $prescription_date);
        $stmt->execute();
        $stmt->close();

        // Insert prescription drug details
        $drug = $_POST['drug'];
        $dosings = $_POST['dosings'];
        // Corrected line: Retrieve frequencies from the POST data
        $frequencies = $_POST['frequencies'];
        $days = $_POST['days'];
        $total_dosages = $_POST['total_dosages'];

        $stmt = $conn->prepare("INSERT INTO prescription_drugs (prescription_id, drug_name, dosing, frequency, days, total_dosage) VALUES (?, ?, ?, ?, ?, ?)");

        for ($i = 0; $i < count($drug); $i++) {
            // Corrected line: Add the frequency variable to the bind_param
            $stmt->bind_param("sssiis", $prescription_id, $drug[$i], $dosings[$i], $frequencies[$i], $days[$i], $total_dosages[$i]);
            $stmt->execute();
        }

        $stmt->close();

        $conn->commit();

        echo "Prescription saved successfully!";
        header("Refresh: 2; URL=other_prescriptions.php?success=1");
        exit;


    } catch (Exception $e) {
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }
}
?>
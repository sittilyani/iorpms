<?php
require_once 'includes/config.php';

function describeTable($conn, $table) {
    $res = $conn->query("DESCRIBE $table");
    if ($res) {
        echo "TABLE: $table\n";
        while ($row = $res->fetch_assoc()) {
            echo "  Field: {$row['Field']} | Type: {$row['Type']} | Null: {$row['Null']}\n";
        }
    } else {
        echo "Error describing $table: " . $conn->error . "\n";
    }
    
    $countRes = $conn->query("SELECT COUNT(*) as count FROM $table");
    if ($countRes) {
        $row = $countRes->fetch_assoc();
        echo "Total rows: {$row['count']}\n\n";
    }
}

describeTable($conn, 'fingerprints');
describeTable($conn, 'patientfingerprints');

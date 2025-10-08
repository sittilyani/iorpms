<?php
session_start();
include '../includes/config.php';

try {
    if (!$conn) {
        throw new Exception('Database connection is not established.');
    }

    // Update age for all patients
    $age_update_query = "UPDATE patients SET age = TIMESTAMPDIFF(YEAR, dob, CURDATE()) WHERE dob IS NOT NULL";
    $conn->query($age_update_query);

    // Main query to fetch patient data including age
    $query = "
    SELECT
        p.mat_id,
        MAX(DATE(ph.visitDate)) AS latest_visit_date,
        p.clientName,
        p.sex,
        p.dob,
        p.age,
        LOWER(p.current_status) AS current_status
    FROM
        patients p
    JOIN
        pharmacy ph
    ON
        p.mat_id = ph.mat_id
    GROUP BY
        p.mat_id";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();

    $today = new DateTime();

    while ($row = $result->fetch_assoc()) {
        $mat_id = $row['mat_id'];
        $latest_visit_date = $row['latest_visit_date'];
        $clientName = $row['clientName'];
        $sex = $row['sex'];
        $age = $row['age']; // Now fetched from the updated patients table
        $current_status = $row['current_status'];

        $visit_date = new DateTime($latest_visit_date);
        $date_diff = $today->diff($visit_date)->days;

        $new_status = $current_status;

        // Status update logic (unchanged)
        if ($current_status === 'active' || $current_status === 'defaulted' || $current_status === 'ltfu') {
            if ($current_status === 'active' && $date_diff <= 5) {
                $new_status = $current_status;
            } else {
                if ($date_diff <= 1) {
                    $new_status = 'active';
                } elseif ($date_diff > 1 && $date_diff <= 5) {
                    $new_status = 'active';
                } elseif ($date_diff > 5 && $date_diff <= 30) {
                    $new_status = 'defaulted';
                } elseif ($date_diff > 30) {
                    $new_status = 'ltfu';
                }
            }
        }

        // Update patients table with new status and log history if status changed
        if ($new_status !== $current_status) {
            $update_query = "UPDATE patients SET current_status = ? WHERE mat_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param('ss', $new_status, $mat_id);
            $update_stmt->execute();

            // History query including age
            $history_query = "
                INSERT INTO statushistory (clientName, sex, mat_id, current_status, new_status, visitDate, status_change_date, age)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)";
            $history_stmt = $conn->prepare($history_query);
            $history_stmt->bind_param('ssssssi', $clientName, $sex, $mat_id, $current_status, $new_status, $latest_visit_date, $age);
            $history_stmt->execute();

            echo "Updated mat_id: $mat_id from $current_status to $new_status with age: $age based on visitDate: $latest_visit_date. Status history logged.<br>";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status and Age Update</title>
    <style>
        h3 {
            margin-left: 0;
            padding-left: 40px;
            padding-top: 10px;
        }
    </style>
    <script>
        function showLoadingMessage() {
            document.getElementById('message').textContent = "Running the patient status and age update! Please be patient until complete...";
            setTimeout(() => {
                showSuccessMessage();
            }, 3000);
        }

        function showSuccessMessage() {
            document.getElementById('message').textContent = "Status and age updates with history logging completed successfully. Redirecting...";
            setTimeout(() => {
                window.location.href = '../dashboard/dashboard.php';
            }, 4000);
        }
    </script>
</head>
<body onload="showLoadingMessage()">
    <h3 style="width: 100%; height: 40px; background-color: #FFE3C7; color: black; font-family: monospace;" id="message">Initializing...</h3>
</body>
</html>
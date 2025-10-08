<?php

function displayMissingDates($conn, $p_id) {
        // Fetch all visit dates for the patient within the current month up to yesterday
        $sub_query = "SELECT visitDate
                                    FROM pharmacy
                                    WHERE mat_id = 'C28293MAT0003'
                                    AND visitDate BETWEEN DATE_FORMAT(NOW(), '%Y-%m-01') AND DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        $stmt = $conn->prepare($sub_query);
        $stmt->bind_param("s", $p_id);
        $stmt->execute();
        $result = $stmt->get_result();

        // Extract dates into an array
        $visit_dates = [];
        while ($row = $result->fetch_assoc()) {
                $visit_dates[] = $row['visitDate'];
        }
        $stmt->close();

        // Calculate missing dates
        $startDate = date('Y-m-01'); // Start from the first day of the current month
        $endDate = date('Y-m-d', strtotime('-1 day')); // Up to the previous day
        $missing_dates = [];
        $currentDate = $startDate;

        // Iterate through each day in the range
        while ($currentDate <= $endDate) {
                if (!in_array($currentDate, $visit_dates)) {
                        $missing_dates[] = $currentDate;
                }
                $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
        }

        // Output missing dates
        if (empty($missing_dates)) {
                echo "<p><strong>List of missed Dates this month:</strong><span style='color: red;'> No missing dates</span></p>";
        } else {
                $missing_dates_string = implode(", ", $missing_dates);
                echo "<p><strong>List of missed Dates this month:</strong><span style='color: red;'> $missing_dates_string </span></p>";
        }

        // Output missed appointments
        if (empty($missing_dates)) {
                echo "<p><strong>Number of missed Appointments this month:</strong> No missing appointments</p>";
        } else {
                echo "<p><strong>Number of missed Appointments this month:</strong> <span style='color: red;'> " . count($missing_dates) . " </span>Days</p>";
        }
}

// Call the function with connection and patient ID
displayMissingDates($conn, $p_id);
?>
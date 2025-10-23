<?php
include "../includes/config.php";

if (isset($_GET['mat_id'])) {
    $mat_id = $_GET['mat_id'];

    // Fetch patient's details from the database based on the ID
    $query_patient = "SELECT * FROM patients WHERE mat_id = ?";
    $stmt_patient = $conn->prepare($query_patient);
    $stmt_patient->bind_param("s", $mat_id);
    $stmt_patient->execute();
    $result_patient = $stmt_patient->get_result();

    if ($result_patient->num_rows === 0) {
        die("<p style='margin-left: 20px; color: red;'>Patient not found.</p>");
    }

    $row_patient = $result_patient->fetch_assoc();

    // Get and normalize the patient's current status
    $current_status = strtolower(trim($row_patient['current_status']));

    // Fetch the last visitDate for the patient from the pharmacy table
    $query_last_visit = "SELECT MAX(visitDate) AS last_comp_date FROM pharmacy WHERE mat_id = ?";
    $stmt_last_visit = $conn->prepare($query_last_visit);
    $stmt_last_visit->bind_param("s", $mat_id);
    $stmt_last_visit->execute();
    $result_last_visit = $stmt_last_visit->get_result();
    $row_last_visit = $result_last_visit->fetch_assoc();

    // Check and handle the last_comp_date value
    if ($row_last_visit && !empty($row_last_visit['last_comp_date'])) {
        $last_comp_date = $row_last_visit['last_comp_date'];
        echo "<p style='margin-left: 20px; background-color: yellow; padding: 10px; width: 400px; color: red;'><strong>Last Visit Date was on " . htmlspecialchars($last_comp_date, ENT_QUOTES, 'UTF-8') . "</strong></p>";
    } else {
        $last_comp_date = "No dispensing record in pharmacy";
        echo "<p style='margin-left: 20px; background-color: yellow; padding: 10px; width: 400px; color: red;'><strong>" . htmlspecialchars($last_comp_date, ENT_QUOTES, 'UTF-8') . "</strong></p>";
    }

    // Display patient's details
    echo "<h2 style='margin-left: 20px;'>Patient Details</h2>";
    echo "<p style='margin-left: 20px;'><strong>Name:</strong> " . htmlspecialchars($row_patient['clientName']) . "</p>";
    echo "<p style='margin-left: 20px;'><strong>Gender:</strong> " . htmlspecialchars($row_patient['sex']) . "</p>";
    echo "<p style='margin-left: 20px;'><strong>Drug:</strong> " . htmlspecialchars($row_patient['drugname']) . "</p>";
    echo "<p style='margin-left: 20px;'><strong>MAT Number:</strong> " . htmlspecialchars($row_patient['mat_number']) . "</p>";
    echo "<p style='margin-left: 20px;'><strong>MAT ID:</strong> " . htmlspecialchars($row_patient['mat_id']) . "</p>";

    // Check current status
    if (in_array($current_status, ["active", "defaulted"])) {
        // Display missing dates for active or defaulted patients
        displayMissingDates($conn, $mat_id);
    } else {
        // Display status message for other statuses
        $status_message = getStatusMessage($current_status);
        echo "<p style='margin-left: 20px; color: red;'><strong>Status:</strong> " . htmlspecialchars($status_message) . "</p>";
    }
} else {
    echo "<p style='margin-left: 20px; color: red;'>Patient ID not provided.</p>";
}

function getStatusMessage($status) {
    switch ($status) {
        case 'ltfu':
            return "This client is lost to follow up.";
        case 'dead':
            return "This client died.";
        case 'transout':
            return "This client has been transferred out.";
        case 'stopped':
            return "This client was discontinued.";
        case 'weaned':
            return "This client was weaned off.";
        case 'transit':
            return "This was a transit client.";
        case 'other status':
            return "This client has an unknown status.";
        default:
            return "Unknown current status.";
    }
}

function displayMissingDates($conn, $mat_id) {
    // Generate calendar dates for the current month and find missing pharmacy visit dates
    $first_day_of_month = date('Y-m-01');
    $today = date('Y-m-d');

    // Get the actual days in month
    $start_date = new DateTime($first_day_of_month);
    $end_date = new DateTime($today);
    $interval = new DateInterval('P1D');
    $date_range = new DatePeriod($start_date, $interval, $end_date->modify('+1 day'));

    $all_dates = [];
    foreach ($date_range as $date) {
        $all_dates[] = $date->format('Y-m-d');
    }

    // Fetch dates when medication was dispensed (with dosage > 0)
    $dispensed_query = "
        SELECT DISTINCT visitDate
        FROM pharmacy
        WHERE mat_id = ?
        AND visitDate BETWEEN ? AND ?
        AND dosage > 0";

    $stmt_dispensed = $conn->prepare($dispensed_query);
    $stmt_dispensed->bind_param("sss", $mat_id, $first_day_of_month, $today);
    $stmt_dispensed->execute();
    $result_dispensed = $stmt_dispensed->get_result();

    $dispensed_dates = [];
    while ($row = $result_dispensed->fetch_assoc()) {
        // Extract just the date part (remove time)
        $date_only = date('Y-m-d', strtotime($row['visitDate']));
        $dispensed_dates[] = $date_only;
    }
    $stmt_dispensed->close();

    // Find missing dates (dates in month range but not in dispensed dates)
    $missing_dates = array_diff($all_dates, $dispensed_dates);

    // Display missing dates
    if (empty($missing_dates)) {
        echo "<p style='margin-left: 20px;'><strong>List of missed Dates this month:</strong><span style='color: green;'> No missing dates - Excellent adherence!</span></p>";
    } else {
        $missed_count = count($missing_dates);
        echo "<p style='margin-left: 20px;'><strong>List of missed Dates this month: ($missed_count days missed)</strong></p>";
        echo "<div style='background-color: #fff3cd; color: #856404; padding: 10px; margin-left: 20px; margin-bottom: 20px; line-height: 2; width: 80%; border: 1px solid #ffeaa7; border-radius: 5px;'>";

        // Loop through the dates and display them
        $counter = 0;
        foreach ($missing_dates as $date) {
            echo htmlspecialchars($date);
            $counter++;

            // Add comma if not the last date
            if ($counter < count($missing_dates)) {
                echo ", ";
            }

            // Break line every 5 dates for better readability
            if ($counter % 5 === 0) {
                echo "<br>";
            }
        }

        echo "</div>";

        // Calculate adherence percentage
        $total_days = count($all_dates);
        $adherence_rate = $total_days > 0 ? round((($total_days - $missed_count) / $total_days) * 100, 1) : 0;

        echo "<p style='margin-left: 20px;'><strong>Adherence Rate:</strong> $adherence_rate% ($total_days total days, $missed_count missed)</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Missed Dates - <?php echo htmlspecialchars($row_patient['clientName'] ?? 'Patient'); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f8f9fa; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .back-btn { display: inline-block; padding: 10px 20px; background: #454AB7; color: white; text-align: center; font-weight: bold; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        .back-btn:hover { background: #3a3f9e; }
    </style>
</head>
<body>
    <div class="container">
        <a href="javascript:history.go(-1);" class="back-btn">Back to Dispensing</a>
    </div>
</body>
</html>
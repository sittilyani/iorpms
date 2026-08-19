<?php
include "../includes/config.php";
require_once "../includes/dose_schedule_helpers.php";

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

    // Fetch the last visitDate/ dispensing date (dispDate) for the patient from the pharmacy table
    $query_last_visit = "SELECT MAX(dispDate) AS last_comp_date FROM pharmacy WHERE mat_id = ?";
    $stmt_last_visit = $conn->prepare($query_last_visit);
    $stmt_last_visit->bind_param("s", $mat_id);
    $stmt_last_visit->execute();
    $result_last_visit = $stmt_last_visit->get_result();
    $row_last_visit = $result_last_visit->fetch_assoc();

    // Check and handle the last_comp_date value
    if ($row_last_visit && !empty($row_last_visit['last_comp_date'])) {
        $last_comp_date = $row_last_visit['last_comp_date'];
        echo "<p style='margin-left: 20px; background-color: yellow; padding: 10px; width: 40%; color: red;'><strong>Last visit/dispensed date was on " . htmlspecialchars($last_comp_date, ENT_QUOTES, 'UTF-8') . "</strong></p>";
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

    // Schedule-aware adherence: a day only counts as "missed" if a dose was
    // actually due that day per the client's dose schedule/pattern. Days
    // that fall on an alternate-dosing "off" day (e.g. Buprenorphine every
    // 2nd/3rd day) are shown separately and are NOT counted as missed.
    $adherence = computeDoseAdherence($conn, $mat_id, $first_day_of_month, $today);

    $missing_dates = $adherence['missed_dates'];
    $off_pattern_dates = array_keys(array_filter($adherence['days'], fn($status) => $status === 'off_pattern'));

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

        // Adherence percentage is calculated over DUE days only, so an
        // alternate-dosing patient isn't penalised for planned off-days.
        $due_days = $adherence['due_count'];
        $adherence_rate = $due_days > 0 ? round((($due_days - $missed_count) / $due_days) * 100, 1) : 0;

        echo "<p style='margin-left: 20px;'><strong>Adherence Rate:</strong> <span style='color: red; font-size: 20px; font-weight: bold;'> $adherence_rate% </span> ($due_days dose days due this month, $missed_count missed)</p>";
    }

    // Show alternate-dosing off-pattern days separately, for clinician context.
    if (!empty($off_pattern_dates)) {
        echo "<p style='margin-left: 20px;'><strong>Alternate Dosing — Not Due (" . count($off_pattern_dates) . " days):</strong></p>";
        echo "<div style='background-color: #e7f3ff; color: #31708f; padding: 10px; margin-left: 20px; margin-bottom: 20px; line-height: 2; width: 80%; border: 1px solid #bcdff1; border-radius: 5px;'>";
        echo htmlspecialchars(implode(', ', $off_pattern_dates));
        echo "</div>";
        echo "<p style='margin-left: 20px; color: #555; font-size: 13px;'>These days fall on the client's alternate dosing pattern (or an explicit skip date) and are not counted as missed.</p>";
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
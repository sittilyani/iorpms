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
        echo "<p style='margin-left: 20px; background-color: yellow;  font-weight: normal; padding: 10px; width: 90%; color: red;'>Last visit/dispensed date was on " . htmlspecialchars($last_comp_date, ENT_QUOTES, 'UTF-8') . "</p>";
    } else {
        $last_comp_date = "No dispensing record in pharmacy";
        echo "<p style='margin-left: 20px; background-color: yellow; padding: 10px; width: 400px; color: red;'>" . htmlspecialchars($last_comp_date, ENT_QUOTES, 'UTF-8') . "</p>";
    }

    // Check current status
    if (in_array($current_status, ["active", "defaulted"])) {
        // Display missing dates for active or defaulted patients
        displayMissingDates($conn, $mat_id);
    } else {
        // Display status message for other statuses
        $status_message = getStatusMessage($current_status);
        echo "<p style='margin-left: 20px; color: red;'>Status:" . htmlspecialchars($status_message) . "</p>";
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

        echo "<p style='margin-left:20px;'>Dispensing Status - Last 5 Days</p>";

        // Define date range (Last 5 days including today)
        $today = new DateTime();
        $start_date = (clone $today)->modify('-4 days'); // today + previous 4 days

        $interval = new DateInterval('P1D');
        $date_range = new DatePeriod($start_date, $interval, (clone $today)->modify('+1 day'));

        $last_five_days = [];
        foreach ($date_range as $date) {
                $last_five_days[] = $date->format('Y-m-d');
        }

        // Fetch dispensed dates for last 5 days
        $dispensed_query = "
                SELECT DISTINCT DATE(dispDate) AS disp_date
                FROM pharmacy
                WHERE mat_id = ?
                AND DATE(dispDate) BETWEEN ? AND ?
                AND dosage > 0
        ";

        $start_str = $start_date->format('Y-m-d');
        $end_str = $today->format('Y-m-d');

        $stmt_dispensed = $conn->prepare($dispensed_query);
        $stmt_dispensed->bind_param("sss", $mat_id, $start_str, $end_str);
        $stmt_dispensed->execute();
        $result_dispensed = $stmt_dispensed->get_result();

        $dispensed_dates = [];
        while ($row = $result_dispensed->fetch_assoc()) {
                $dispensed_dates[] = $row['disp_date'];
        }
        $stmt_dispensed->close();

        // Display results in table format
        echo "<table style='margin-left:20px; width:90%; border-collapse: collapse; font-weight: normal;'>";
        echo "<tr>
                        <th style='border:1px solid #ddd; padding:8px; font-weight: normal;'>Date</th>
                        <th style='border:1px solid #ddd; padding:8px; font-weight: normal;'>Status</th>
                    </tr>";

        foreach ($last_five_days as $date) {

                if (in_array($date, $dispensed_dates)) {
                        echo "<tr>
                                        <td style='border:1px solid #ddd; padding:8px;'>$date</td>
                                        <td style='border:1px solid #ddd; padding:8px; color:green; font-weight: normal;'>Dispensed</td>
                                    </tr>";
                } else {
                        echo "<tr style='background-color:#ffe6e6;'>
                                        <td style='border:1px solid #ddd; padding:8px;'>$date</td>
                                        <td style='border:1px solid #ddd; padding:8px; color:red;  font-weight: normal;'>Missed</td>
                                    </tr>";
                }
        }

        echo "</table>";
}

?>


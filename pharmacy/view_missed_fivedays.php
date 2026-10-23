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
    $current_status = strtolower((string)(trim((string)($row_patient['current_status']))));

    // last visit and patient status are shown in the grid above the form; only the missed dates are listed here
    // Check current status
    if (in_array($current_status, ["active", "defaulted"])) {
        // Display missing dates for active or defaulted patients
        displayMissingDates($conn, $mat_id);
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

        $start_str = $start_date->format('Y-m-d');
        $end_str = $today->format('Y-m-d');

        // Schedule-aware: classifies each day as dispensed / missed / an
        // alternate-dosing off-pattern day (not counted as missed) / no
        // active schedule that day.
        $adherence = computeDoseAdherence($conn, $mat_id, $start_str, $end_str);

        // Display results in table format
        echo "<table style='margin-left:20px; width:90%; border-collapse: collapse; font-weight: normal;'>";
        echo "<tr>
                        <th style='border:1px solid #ddd; padding:8px; font-weight: normal;'>Date</th>
                        <th style='border:1px solid #ddd; padding:8px; font-weight: normal;'>Status</th>
                    </tr>";

        foreach ($adherence['days'] as $date => $status) {

                switch ($status) {
                        case 'dispensed':
                                echo "<tr>
                                                <td style='border:1px solid #ddd; padding:8px;'>$date</td>
                                                <td style='border:1px solid #ddd; padding:8px; color:green; font-weight: normal;'>Dispensed</td>
                                            </tr>";
                                break;
                        case 'off_pattern':
                                echo "<tr style='background-color:#e7f3ff;'>
                                                <td style='border:1px solid #ddd; padding:8px;'>$date</td>
                                                <td style='border:1px solid #ddd; padding:8px; color:#31708f; font-weight: normal;'>Not Due (Alternate Dosing)</td>
                                            </tr>";
                                break;
                        case 'missed':
                                echo "<tr style='background-color:#ffe6e6;'>
                                                <td style='border:1px solid #ddd; padding:8px;'>$date</td>
                                                <td style='border:1px solid #ddd; padding:8px; color:red;  font-weight: normal;'>Missed</td>
                                            </tr>";
                                break;
                        default: // no_schedule
                                echo "<tr>
                                                <td style='border:1px solid #ddd; padding:8px;'>$date</td>
                                                <td style='border:1px solid #ddd; padding:8px; color:#888; font-weight: normal;'>No Active Dose Schedule</td>
                                            </tr>";
                }
        }

        echo "</table>";
}

?>


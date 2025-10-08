<?php
include "../includes/config.php";
include "../includes/footer.php";
include "../includes/header.php";

if (isset($_GET['p_id'])) {
    $p_id = $_GET['p_id'];

    // Fetch patient's details from the database based on the ID
    $query_patient = "SELECT * FROM patients WHERE p_id = ?";
    $stmt_patient = $conn->prepare($query_patient);
    $stmt_patient->bind_param("s", $param_p_id);
    $param_p_id = $p_id;
    $stmt_patient->execute();
    $result_patient = $stmt_patient->get_result();
    $row_patient = $result_patient->fetch_assoc();

    // Display patient's details
    echo "<h2 style='margin-left: 20px;'>Patient Details</h2>";
    echo "<p style='margin-left: 20px;'><strong>Name:</strong> " . htmlspecialchars($row_patient['clientName']) . "</p>";
    echo "<p style='margin-left: 20px;'><strong>Gender:</strong> " . htmlspecialchars($row_patient['sex']) . "</p>";
    echo "<p style='margin-left: 20px;'><strong>Drug:</strong> " . htmlspecialchars($row_patient['drugname']) . "</p>";
    echo "<p style='margin-left: 20px;'><strong>MAT Number:</strong> " . htmlspecialchars($row_patient['mat_number']) . "</p>";

    // Display missing dates and missed appointments
    displayMissingDates($conn, $row_patient['mat_id']);
} else {
    echo "Patient ID not provided.";
}

function displayMissingDates($conn, $mat_id) {
    // Subquery to find dates where mat_id has no entries in pharmacy
    $sub_query = "SELECT calendar_date
                            FROM (
                                    SELECT DATE_FORMAT(NOW(), '%Y-%m-01') + INTERVAL seq DAY AS calendar_date
                                    FROM seq_0_to_30
                            ) AS dates_in_month
                            LEFT JOIN pharmacy
                            ON dates_in_month.calendar_date = pharmacy.visitDate AND pharmacy.mat_id = ?
                            WHERE pharmacy.visitDate IS NULL
                            AND dates_in_month.calendar_date <= DATE_SUB(CURDATE(), INTERVAL 1 DAY)";

    $stmt = $conn->prepare($sub_query);
    $stmt->bind_param("s", $mat_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Extract missing dates
    $missing_dates = [];
    while ($row = $result->fetch_assoc()) {
        $missing_dates[] = $row['calendar_date'];
    }
    $stmt->close();

    // Display missing dates
    if (empty($missing_dates)) {
        echo "<p style='margin-left: 20px;'><strong>List of missed Dates this month:</strong><span style='color: red;'> No missing dates</span></p>";
    } else {
        echo "<p style='margin-left: 20px;'><strong>List of missed Dates this month:</strong></p>";
        echo "<div style='background-color: yellow; padding: 10px; margin-left: 20px; margin-bottom: 20px; line-height: 2; width: 50%; border: none;'>";

        // Loop through the dates and display them in rows of 10
        foreach ($missing_dates as $index => $date) {
            echo $date;

            // Add a comma after each date except the last one in a row
            if (($index + 1) % 10 !== 0 && $index !== count($missing_dates) - 1) {
                echo ", ";
            }

            // Start a new row after every 10 dates
            if (($index + 1) % 10 === 0) {
                echo "<br>";
            }
        }

        echo "</div>";
    }
}
?>

<a href="javascript:history.go(-1);" style="display: inline-block; width: 100px; margin-left: 20px; height: 40px; background: #454AB7; color: white; text-align: center; line-height: 40px; font-weight: bold; text-decoration: none;">Back</a>

</body>
</html>

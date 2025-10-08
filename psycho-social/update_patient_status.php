<?php
// Database connection
include "../includes/config.php"; // Adjust the path as needed to include your database connection

// Get distinct mat_ids where current_status is Active
$query = "SELECT p.mat_id
          FROM patients AS p
          WHERE p.current_status = 'Active'";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $mat_id = $row['mat_id'];

        // Query to check the last 5 and 15 days for visitDate records in pharmacy for each mat_id
        $query_check_visits = "SELECT visitDate
                               FROM pharmacy
                               WHERE mat_id = ?
                               AND visitDate >= DATE_SUB(CURDATE(), INTERVAL 15 DAY)
                               ORDER BY visitDate DESC";

        $stmt = $conn->prepare($query_check_visits);
        $stmt->bind_param("s", $mat_id);
        $stmt->execute();
        $result_visits = $stmt->get_result();

        // Collect visit dates
        $visit_dates = [];
        while ($visit_row = $result_visits->fetch_assoc()) {
            $visit_dates[] = $visit_row['visitDate'];
        }

        // Calculate missing days
        $today = date('Y-m-d');
        $missing_days_count = 0;
        $is_defaulted = false;
        $is_ltfu = false;

        for ($i = 1; $i <= 15; $i++) {
            $check_date = date('Y-m-d', strtotime("$today -$i day"));
            if (!in_array($check_date, $visit_dates)) {
                $missing_days_count++;

                // If 5 consecutive missing days within the last 15 days, mark as Defaulted
                if ($i <= 5 && $missing_days_count >= 5) {
                    $is_defaulted = true;
                    break;
                }

                // If 15 consecutive missing days, mark as LTFU
                if ($missing_days_count >= 15) {
                    $is_ltfu = true;
                    break;
                }
            } else {
                $missing_days_count = 0; // Reset if there's a visit
            }
        }

        // Update current_status if needed
        if ($is_defaulted) {
            $update_status = "UPDATE patients SET current_status = 'Defaulted' WHERE mat_id = ?";
        } elseif ($is_ltfu) {
            $update_status = "UPDATE patients SET current_status = 'LTFU' WHERE mat_id = ?";
        } else {
            continue; // No status change needed
        }

        $stmt_update = $conn->prepare($update_status);
        $stmt_update->bind_param("s", $mat_id);
        $stmt_update->execute();
        $stmt_update->close();
    }
}
$conn->close();
?>

<?php
// Include the database connection file
require_once '../includes/config.php';

// Check if mat_id is set in the URL parameters
if (isset($_GET['mat_id'])) {
    // Sanitize and retrieve the mat_id from the URL parameters
    $mat_id = $_GET['mat_id'];

    // Construct the SQL query with a placeholder for the mat_id parameter
    $sql = "SELECT
                COUNT(*) AS num_rows,
                DATEDIFF(CURDATE(), STR_TO_DATE(CONCAT(YEAR(CURDATE()), '-', MONTH(CURDATE()), '-01'), '%Y-%m-%d')) - COUNT(*) AS new_num_rows
            FROM
                patient p
            JOIN
                dispence d ON p.mat_id = d.mat_id
            WHERE
                p.mat_id = ?
                AND d.dosage > 0";

    // Prepare the SQL statement
    $stmt = mysqli_prepare($conn, $sql);

    // Bind the mat_id parameter to the prepared statement
    mysqli_stmt_bind_param($stmt, "s", $mat_id);

    // Execute the prepared statement
    mysqli_stmt_execute($stmt);

    // Bind the result variables
    mysqli_stmt_bind_result($stmt, $num_rows, $new_num_rows);

    // Fetch the result
    mysqli_stmt_fetch($stmt);

    // Echo the values
    echo "num_rows: $num_rows\n";
    echo "new_num_rows: $new_num_rows\n";

    // Close the prepared statement
    mysqli_stmt_close($stmt);
} else {
    // Mat_id parameter is not provided in the URL
    echo "Please provide a mat_id parameter in the URL.";
}

// Close the database connection
mysqli_close($conn);
?>

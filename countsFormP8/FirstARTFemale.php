<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT (SELECT COUNT(*)  FROM medical_history WHERE regimen_type = 'First Line' AND sex = 'Female') AS first_lineCount,

        (SELECT COUNT(*)
                FROM patients
                where current_status = 'Active') AS total_count";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$first_lineCount = $result['first_lineCount'];

// Output the count as plain text
echo $first_lineCount;
?>



    <script>
        // Function to update the count of first_line users
        function updatefirst_lineCount() {
            $.ajax({
                url: 'first_line_count.php',
                type: 'GET',
                success: function (data) {
                    $('#first_linesCount').text('first_lines: ' + data);
                },
                error: function (error) {
                    console.error('Error fetching first_line count:', error);
                }
            });
        }

        // Call the function initially
        updatefirst_lineCount();

        // Set an interval to update the count every 5 minutes (300,000 milliseconds)
        setInterval(updatefirst_lineCount, 300000);
    </script>
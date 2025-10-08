<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as first_lineCount FROM medical_history WHERE art_regimen = 'first_line' AND sex NOT IN ('male', 'female')";
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

        // Set an interval to update the count every 5 minutes (300,000 millifirsts)
        setInterval(updatefirst_lineCount, 300000);
    </script>
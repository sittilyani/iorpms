<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as second_lineCount FROM medical_history WHERE regimen_type = 'Second Line' AND sex = 'male' ";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$second_lineCount = $result['second_lineCount'];

// Output the count as plain text
echo $second_lineCount;
?>



    <script>
        // Function to update the count of second_line users
        function updatesecond_lineCount() {
            $.ajax({
                url: 'second_line_count.php',
                type: 'GET',
                success: function (data) {
                    $('#second_linesCount').text('second_lines: ' + data);
                },
                error: function (error) {
                    console.error('Error fetching second_line count:', error);
                }
            });
        }

        // Call the function initially
        updatesecond_lineCount();

        // Set an interval to update the count every 5 minutes (300,000 milliseconds)
        setInterval(updatesecond_lineCount, 300000);
    </script>
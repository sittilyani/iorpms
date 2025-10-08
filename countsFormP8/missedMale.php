<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as missed_lineCount FROM patients WHERE current_status = 'defaulted' AND sex = 'male' AND drugname ='methadone'";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$missed_lineCount = $result['missed_lineCount'];

// Output the count as plain text
echo $missed_lineCount;
?>



    <script>
        // Function to update the count of missed_line users
        function updatemissed_lineCount() {
            $.ajax({
                url: 'missed_line_count.php',
                type: 'GET',
                success: function (data) {
                    $('#missed_linesCount').text('missed_lines: ' + data);
                },
                error: function (error) {
                    console.error('Error fetching missed_line count:', error);
                }
            });
        }

        // Call the function initially
        updatemissed_lineCount();

        // Set an interval to update the count every 5 minutes (300,000 millimisseds)
        setInterval(updatemissed_lineCount, 300000);
    </script>
<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as stoppedCount FROM patients WHERE current_status = 'stopped'";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$stoppedCount = $result['stoppedCount'];

// Output the count as plain text
echo $stoppedCount;
?>


    <script>
        // Function to update the count of weaned users
        function updatestoppedCount() {
            $.ajax({
                url: 'stopped_count.php',
                type: 'GET',
                success: function (data) {
                    $('#stoppedsCount').text('stoppeds: ' + data);
                },
                error: function (error) {
                    console.error('Error fetching stopped count:', error);
                }
            });
        }

        // Call the function initially
        updateweanedCount();

        // Set an interval to update the count every 5 minutes (300,000 milliseconds)
        setInterval(updateweanedCount, 300000);
    </script>




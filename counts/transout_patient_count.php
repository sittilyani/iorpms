<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as transoutCount FROM patients WHERE current_status = 'transout'";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$transoutCount = $result['transoutCount'];

// Output the count as plain text
echo $transoutCount;
?>


    <script>
        // Function to update the count of transout users
        function updatetransoutCount() {
            $.ajax({
                url: 'transout_count.php',
                type: 'GET',
                success: function (data) {
                    $('#transoutsCount').text('transouts: ' + data);
                },
                error: function (error) {
                    console.error('Error fetching transout count:', error);
                }
            });
        }

        // Call the function initially
        updatetransoutCount();

        // Set an interval to update the count every 5 minutes (300,000 milliseconds)
        setInterval(updatetransoutCount, 300000);
    </script>




<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as transitCount FROM patients WHERE mat_status = 'transit' AND current_status = 'active' ";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$transitCount = $result['transitCount'];

// Output the count as plain text
echo $transitCount;
?>


    <script>
        // Function to update the count of transit users
        function updatetransitCount() {
            $.ajax({
                url: 'transit_count.php',
                type: 'GET',
                success: function (data) {
                    $('#transitsCount').text('transits: ' + data);
                },
                error: function (error) {
                    console.error('Error fetching transit count:', error);
                }
            });
        }

        // Call the function initially
        updatetransitCount();

        // Set an interval to update the count every 5 minutes (300,000 milliseconds)
        setInterval(updatetransitCount, 300000);
    </script>




<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as deadCount FROM patients WHERE current_status = 'dead' AND sex = 'male' AND drugname LIKE 'buprenorphine%'";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$deadCount = $result['deadCount'];

// Output the count as plain text
echo $deadCount;
?>



    <script>
        // Function to update the count of dead users
        function updatedeadCount() {
            $.ajax({
                url: 'dead_count.php',
                type: 'GET',
                success: function (data) {
                    $('#deadsCount').text('deads: ' + data);
                },
                error: function (error) {
                    console.error('Error fetching dead count:', error);
                }
            });
        }

        // Call the function initially
        updatedeadCount();

        // Set an interval to update the count every 5 minutes (300,000 milliseconds)
        setInterval(updatedeadCount, 300000);
    </script>
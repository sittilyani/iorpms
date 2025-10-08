<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as ltfuCount FROM patients WHERE current_status = 'ltfu' AND sex = 'female' AND drugname ='methadone'";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$activeCount = $result['ltfuCount'];

// Output the count as plain text
echo $activeCount;
?>



    <script>
        // Function to update the count of active users
        function updateactiveCount() {
            $.ajax({
                url: 'ltfu_count.php',
                type: 'GET',
                success: function (data) {
                    $('#ltfusCount').text('actives: ' + data);
                },
                error: function (error) {
                    console.error('Error fetching active count:', error);
                }
            });
        }

        // Call the function initially
        updateactiveCount();

        // Set an interval to update the count every 5 minutes (300,000 milliseconds)
        setInterval(updateactiveCount, 300000);
    </script>
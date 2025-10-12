<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as everweanedallCount FROM patients WHERE current_status = 'weaned' AND sex IN ('male', 'female')";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$everweanedallCount = $result['everweanedallCount'];

// Output the count as plain text
echo $everweanedallCount;
?>



    <script>
        // Function to update the count of everweanedall users
        function updateeverweanedallCount() {
            $.ajax({
                url: 'everweanedall_count.php',
                type: 'GET',
                success: function (data) {
                    $('#everweanedallsCount').text('everweanedalls: ' + data);
                },
                error: function (error) {
                    console.error('Error fetching everweanedall count:', error);
                }
            });
        }

        // Call the function initially
        updateeverweanedallCount();

        // Set an interval to update the count every 5 minutes (300,000 milliseconds)
        setInterval(updateeverweanedallCount, 300000);
    </script>
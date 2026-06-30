<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as everweanedmaleCount FROM patients WHERE current_status = 'weaned' AND sex = 'male'";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$everweanedmaleCount = $result['everweanedmaleCount'];

// Output the count as plain text
echo $everweanedmaleCount;
?>



    <script>
        // Function to update the count of everweanedmale users
        function updateeverweanedmaleCount() {
            $.ajax({
                url: 'everweanedmale_count.php',
                type: 'GET',
                success: function (data) {
                    $('#everweanedmalesCount').text('everweanedmales: ' + data);
                },
                error: function (error) {
                    console.error('Error fetching everweanedmale count:', error);
                }
            });
        }

        // Call the function initially
        updateeverweanedmaleCount();

        // Set an interval to update the count every 5 minutes (300,000 milliseconds)
        setInterval(updateeverweanedmaleCount, 300000);
    </script>
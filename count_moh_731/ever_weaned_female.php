<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as everweanedfemaleCount FROM patients WHERE current_status = 'weaned' AND sex = 'female'";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$everweanedfemaleCount = $result['everweanedfemaleCount'];

// Output the count as plain text
echo $everweanedfemaleCount;
?>



    <script>
        // Function to update the count of everweanedfemale users
        function updateeverweanedfemaleCount() {
            $.ajax({
                url: 'everweanedfemale_count.php',
                type: 'GET',
                success: function (data) {
                    $('#everweanedfemalesCount').text('everweanedfemales: ' + data);
                },
                error: function (error) {
                    console.error('Error fetching everweanedfemale count:', error);
                }
            });
        }

        // Call the function initially
        updateeverweanedfemaleCount();

        // Set an interval to update the count every 5 minutes (300,000 milliseconds)
        setInterval(updateeverweanedfemaleCount, 300000);
    </script>
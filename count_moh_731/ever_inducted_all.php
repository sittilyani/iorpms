<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as everinductedallCount FROM patients WHERE mat_status = 'new' AND sex IN ('male', 'female')";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$everinductedallCount = $result['everinductedallCount'];

// Output the count as plain text
echo $everinductedallCount;
?>



    <script>
        // Function to update the count of everinductedall users
        function updateeverinductedallCount() {
            $.ajax({
                url: 'everinductedall_count.php',
                type: 'GET',
                success: function (data) {
                    $('#everinductedallsCount').text('everinductedalls: ' + data);
                },
                error: function (error) {
                    console.error('Error fetching everinductedall count:', error);
                }
            });
        }

        // Call the function initially
        updateeverinductedallCount();

        // Set an interval to update the count every 5 minutes (300,000 milliseconds)
        setInterval(updateeverinductedallCount, 300000);
    </script>
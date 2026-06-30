<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as everinductedmaleCount FROM patients WHERE mat_status = 'new' AND sex = 'male'";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$everinductedmaleCount = $result['everinductedmaleCount'];

// Output the count as plain text
echo $everinductedmaleCount;
?>



    <script>
        // Function to update the count of everinductedmale users
        function updateeverinductedmaleCount() {
            $.ajax({
                url: 'everinductedmale_count.php',
                type: 'GET',
                success: function (data) {
                    $('#everinductedmalesCount').text('everinductedmales: ' + data);
                },
                error: function (error) {
                    console.error('Error fetching everinductedmale count:', error);
                }
            });
        }

        // Call the function initially
        updateeverinductedmaleCount();

        // Set an interval to update the count every 5 minutes (300,000 milliseconds)
        setInterval(updateeverinductedmaleCount, 300000);
    </script>
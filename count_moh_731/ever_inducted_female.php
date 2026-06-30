<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as everinductedfemaleCount FROM patients WHERE mat_status = 'new' AND sex = 'female'";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$everinductedfemaleCount = $result['everinductedfemaleCount'];

// Output the count as plain text
echo $everinductedfemaleCount;
?>



    <script>
        // Function to update the count of everinductedfemale users
        function updateeverinductedfemaleCount() {
            $.ajax({
                url: 'everinductedfemale_count.php',
                type: 'GET',
                success: function (data) {
                    $('#everinductedfemalesCount').text('everinductedfemales: ' + data);
                },
                error: function (error) {
                    console.error('Error fetching everinductedfemale count:', error);
                }
            });
        }

        // Call the function initially
        updateeverinductedfemaleCount();

        // Set an interval to update the count every 5 minutes (300,000 milliseconds)
        setInterval(updateeverinductedfemaleCount, 300000);
    </script>
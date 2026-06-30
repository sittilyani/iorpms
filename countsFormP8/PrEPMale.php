<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as prepCount FROM medical_history WHERE other_status = 'PrEP' AND sex = 'male' ";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$prepCount = $result['prepCount'];

// Output the count as plain text
echo $prepCount;
?>



    <script>
        // Function to update the count of prep users
        function updateprepCount() {
            $.ajax({
                url: 'prep_count.php',
                type: 'GET',
                success: function (data) {
                    $('#prepsCount').text('preps: ' + data);
                },
                error: function (error) {
                    console.error('Error fetching prep count:', error);
                }
            });
        }

        // Call the function initially
        updateprepCount();

        // Set an interval to update the count every 5 minutes (300,000 milliseconds)
        setInterval(updateprepCount, 300000);
    </script>
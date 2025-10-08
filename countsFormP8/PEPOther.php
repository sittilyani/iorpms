<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as pepCount FROM medical_history WHERE other_status = 'PEP' AND sex NOT IN ('male', 'female') ";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$pepCount = $result['pepCount'];

// Output the count as plain text
echo $pepCount;
?>



    <script>
        // Function to update the count of pep users
        function updatepepCount() {
            $.ajax({
                url: 'pep_count.php',
                type: 'GET',
                success: function (data) {
                    $('#pepsCount').text('peps: ' + data);
                },
                error: function (error) {
                    console.error('Error fetching pep count:', error);
                }
            });
        }

        // Call the function initially
        updatepepCount();

        // Set an interval to update the count every 5 minutes (300,000 milliseconds)
        setInterval(updatepepCount, 300000);
    </script>
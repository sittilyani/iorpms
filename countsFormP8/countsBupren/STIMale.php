<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as stiCount FROM medical_history WHERE other_status = 'STI' AND sex = 'male' ";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$stiCount = $result['stiCount'];

// Output the count as plain text
echo $stiCount;
?>



    <script>
        // Function to update the count of sti users
        function updatestiCount() {
            $.ajax({
                url: 'sti_count.php',
                type: 'GET',
                success: function (data) {
                    $('#stisCount').text('stis: ' + data);
                },
                error: function (error) {
                    console.error('Error fetching sti count:', error);
                }
            });
        }

        // Call the function initially
        updatestiCount();

        // Set an interval to update the count every 5 minutes (300,000 milliseconds)
        setInterval(updatestiCount, 300000);
    </script>
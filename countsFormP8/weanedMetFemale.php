<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Get the current month and year
$currentMonth = date('m');
$currentYear = date('Y');

// Fetch the count of "weaned" female patients for the current month
$sql = "SELECT COUNT(*) as weanedCount
                FROM patients
                WHERE current_status = 'weaned'
                    AND sex = 'female'
                     AND drugname ='methadone'
                    AND MONTH(comp_date) = ?
                    AND YEAR(comp_date) = ?";

$stmt = $conn->prepare($sql); // Use prepared statements for better security
$stmt->bind_param('ii', $currentMonth, $currentYear); // Bind the month and year as parameters
$stmt->execute();
$result = $stmt->get_result(); // Execute and get the result
$row = $result->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$weanedCount = $row['weanedCount'];

// Output the count as plain text
echo $weanedCount;
?>




    <script>
        // Function to update the count of weaned users
        function updateweanedCount() {
            $.ajax({
                url: 'weaned_count.php',
                type: 'GET',
                success: function (data) {
                    $('#weanedsCount').text('weaneds: ' + data);
                },
                error: function (error) {
                    console.error('Error fetching weaned count:', error);
                }
            });
        }

        // Call the function initially
        updateweanedCount();

        // Set an interval to update the count every 5 minutes (300,000 milliseconds)
        setInterval(updateweanedCount, 300000);
    </script>
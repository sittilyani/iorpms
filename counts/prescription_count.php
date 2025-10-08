<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as prescriptionCount FROM other_prescriptions WHERE prescr_status = 'submitted'";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$prescriptionCount = $result['prescriptionCount'];

// Output the count as plain text
echo $prescriptionCount;
?>


    <script>
        // Function to update the count of prescription users
        function updateprescriptionCount() {
            $.ajax({
                url: '../counts/prescription_count.php',
                type: 'GET',
                success: function (data) {
                    $('#prescriptionsCount').text('prescriptions: ' + data);
                },
                error: function (error) {
                    console.error('Error fetching prescription count:', error);
                }
            });
        }

        // Call the function initially
        updateprescriptionCount();

        // Set an interval to update the count every 50 seconds (30,000 milliseconds)
        setInterval(updateprescriptionCount, 30000);
    </script>




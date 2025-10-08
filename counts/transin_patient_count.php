<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as transinCount FROM patients WHERE mat_status = 'transfer in' ";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$transinCount = $result['transinCount'];

// Output the count as plain text
echo $transinCount;
?>


    <script>
        // Function to update the count of transin users
        function updatetransinCount() {
            $.ajax({
                url: 'transin_count.php',
                type: 'GET',
                success: function (data) {
                    $('#transinsCount').text('transins: ' + data);
                },
                error: function (error) {
                    console.error('Error fetching transin count:', error);
                }
            });
        }

        // Call the function initially
        updatetransinCount();

        // Set an interval to update the count every 5 minutes (300,000 milliseconds)
        setInterval(updatetransinCount, 300000);
    </script>




<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as adminCount FROM tblusers WHERE userrole = 'hrio'";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$adminCount = $result['adminCount'];

// Output the count as plain text
echo $adminCount;
?>



    <script>
        // Function to update the count of hrio users
        function updatehrioCount() {
            $.ajax({
                url: 'hrio_count.php',
                type: 'GET',
                success: function (data) {
                    $('#hriosCount').text('hrios: ' + data);
                },
                error: function (error) {
                    console.error('Error fetching hrio count:', error);
                }
            });
        }

        // Call the function initially
        updatehrioCount();

        // Set an interval to update the count every 5 minutes (300,000 milliseconds)
        setInterval(updatehrioCount, 300000);
    </script>






<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as ActiveCount FROM patients WHERE current_status = 'Active'";

$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$ActiveCount = $result['ActiveCount'];

// Output the count as plain text
echo $ActiveCount;
?>



    <script>
        // Function to update the count of Active users
        function updateActiveCount() {
            $.ajax({
                url: 'Active_count.php',
                type: 'GET',
                success: function (data) {
                    $('#ActivesCount').text('Actives: ' + data);
                },
                error: function (error) {
                    console.error('Error fetching Active count:', error);
                }
            });
        }

        // Call the function initially
        updateActiveCount();

        // Set an interval to update the count every 5 minutes (300,000 milliseconds)
        setInterval(updateActiveCount, 300000);
    </script>




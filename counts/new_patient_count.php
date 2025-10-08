

<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as NewCount FROM patients WHERE mat_status NOT IN ('transit')";

$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$NewCount = $result['NewCount'];

// Output the count as plain text
echo $NewCount;
?>



    <script>
        // Function to update the count of Enrolled users
        function updateNewCount() {
            $.ajax({
                url: 'New_count.php',
                type: 'GET',
                success: function (data) {
                    $('#NewsCount').text('News: ' + data);
                },
                error: function (error) {
                    console.error('Error fetching New count:', error);
                }
            });
        }

        // Call the function initially
        updateNewCount();

        // Set an interval to update the count every 5 minutes (300,000 milliseconds)
        setInterval(updateNewCount, 300000);
    </script>




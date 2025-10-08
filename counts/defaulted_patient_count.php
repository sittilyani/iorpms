
<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as defaultedCount FROM patients WHERE current_status = 'defaulted'";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$defaultedCount = $result['defaultedCount'];

// Output the count as plain text
echo $defaultedCount;
?>


    <script>
        // Function to update the count of defaulted users
        function updatedefaultedCount() {
            $.ajax({
                url: 'defaulted_count.php',
                type: 'GET',
                success: function (data) {
                    $('#defaultedsCount').text('defaulteds: ' + data);
                },
                error: function (error) {
                    console.error('Error fetching defaulted count:', error);
                }
            });
        }

        // Call the function initially
        updatedefaultedCount();

        // Set an interval to update the count every 5 minutes (300,000 milliseconds)
        setInterval(updatedefaultedCount, 300000);
    </script>




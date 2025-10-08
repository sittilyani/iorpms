
<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as ltfuCount FROM patients WHERE current_status = 'ltfu'";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$ltfuCount = $result['ltfuCount'];

// Output the count as plain text
echo $ltfuCount;
?>


    <script>
        // Function to update the count of lost-to-follow-up users
        function updatelost-to-follow-upCount() {
            $.ajax({
                url: 'lost_patient_count.php',
                type: 'GET',
                success: function (data) {
                    $('#lost-to-follow-upsCount').text('lost-to-follow-ups: ' + data);
                },
                error: function (error) {
                    console.error('Error fetching lost-to-follow-up count:', error);
                }
            });
        }

        // Call the function initially
        updatelost-to-follow-upCount();

        // Set an interval to update the count every 5 minutes (300,000 milliseconds)
        setInterval(updatelost-to-follow-upCount, 300000);
    </script>




<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as receptionistCount FROM tblusers WHERE userrole = 'receptionist'";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$receptionistCount = $result['receptionistCount'];

// Output the count as plain text
echo $receptionistCount;
?>


    <script>
        // Function to update the count of receptionist users
        function updatereceptionistCount() {
            $.ajax({
                url: 'receptionist_count.php',
                type: 'GET',
                success: function (data) {
                    $('#receptionistsCount').text('receptionists: ' + data);
                },
                error: function (error) {
                    console.error('Error fetching receptionist count:', error);
                }
            });
        }

        // Call the function initially
        updatereceptionistCount();

        // Set an interval to update the count every 5 minutes (300,000 milliseconds)
        setInterval(updatereceptionistCount, 300000);
    </script>




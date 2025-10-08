<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of users users from the database
$sql = "SELECT COUNT(*) as usersCount FROM tblusers";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$usersCount = $result['usersCount'];

// Output the count as plain text
echo $usersCount;
?>



    <script>
        // Function to update the count of users users
        function updateusersCount() {
            $.ajax({
                url: 'users_count.php',
                type: 'GET',
                success: function (data) {
                    $('#userssCount').text('userss: ' + data);
                },
                error: function (error) {
                    console.error('Error fetching users count:', error);
                }
            });
        }

        // Call the function initially
        updateusersCount();

        // Set an interval to update the count every 5 minutes (300,000 milliseconds)
        setInterval(updateusersCount, 300000);
    </script>




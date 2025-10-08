<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as peerCount FROM tblusers WHERE userrole = 'Peer Educator'";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$peerCount = $result['peerCount'];

// Output the count as plain text
echo $peerCount;
?>


    <script>
        // Function to update the count of peer users
        function updatepeerCount() {
            $.ajax({
                url: 'peer_count.php',
                type: 'GET',
                success: function (data) {
                    $('#peersCount').text('peers: ' + data);
                },
                error: function (error) {
                    console.error('Error fetching peer count:', error);
                }
            });
        }

        // Call the function initially
        updatepeerCount();

        // Set an interval to update the count every 5 minutes (300,000 milliseconds)
        setInterval(updatepeerCount, 300000);
    </script>




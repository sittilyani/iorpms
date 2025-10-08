<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as adminCount FROM tblusers WHERE userrole = 'pharmacist'";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$adminCount = $result['adminCount'];

// Output the count as plain text
echo $adminCount;
?>



    <script>
        // Function to update the count of pharmacist users
        function updatepharmacistCount() {
            $.ajax({
                url: 'pharmacist_count.php',
                type: 'GET',
                success: function (data) {
                    $('#pharmacistsCount').text('pharmacists: ' + data);
                },
                error: function (error) {
                    console.error('Error fetching pharmacist count:', error);
                }
            });
        }

        // Call the function initially
        updatepharmacistCount();

        // Set an interval to update the count every 5 minutes (300,000 milliseconds)
        setInterval(updatepharmacistCount, 300000);
    </script>




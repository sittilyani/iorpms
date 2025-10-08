
<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as voluntaryCount FROM patients WHERE current_status = 'Voluntary Discontinuation'";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$voluntaryCount = $result['voluntaryCount'];

// Output the count as plain text
echo $voluntaryCount;
?>


    <script>
        // Function to update the count of voluntary users
        function updatevoluntaryCount() {
            $.ajax({
                url: 'voluntary_count.php',
                type: 'GET',
                success: function (data) {
                    $('#voluntarysCount').text('voluntarys: ' + data);
                },
                error: function (error) {
                    console.error('Error fetching voluntary count:', error);
                }
            });
        }

        // Call the function initially
        updatevoluntaryCount();

        // Set an interval to update the count every 5 minutes (300,000 milliseconds)
        setInterval(updatevoluntaryCount, 300000);
    </script>




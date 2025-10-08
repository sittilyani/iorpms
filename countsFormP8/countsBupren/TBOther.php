<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as positiveCount FROM medical_history WHERE tb_status = 'Positive' AND sex NOT IN ('male', 'female') ";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$positiveCount = $result['positiveCount'];

// Output the count as plain text
echo $positiveCount;
?>



    <script>
        // Function to update the count of positive users
        function updatepositiveCount() {
            $.ajax({
                url: 'positive_count.php',
                type: 'GET',
                success: function (data) {
                    $('#positivesCount').text('positives: ' + data);
                },
                error: function (error) {
                    console.error('Error fetching positive count:', error);
                }
            });
        }

        // Call the function initially
        updatepositiveCount();

        // Set an interval to update the count every 5 minutes (300,000 milliseconds)
        setInterval(updatepositiveCount, 300000);
    </script>
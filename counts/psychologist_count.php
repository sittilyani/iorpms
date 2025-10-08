<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as psychologistCount FROM tblusers WHERE userrole = 'psychologist'";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$psychologistCount = $result['psychologistCount'];

// Output the count as plain text
echo $psychologistCount;
?>


    <script>
        // Function to update the count of psychologist users
        function updatepsychologistCount() {
            $.ajax({
                url: 'psychologist_count.php',
                type: 'GET',
                success: function (data) {
                    $('#psychologistsCount').text('psychologists: ' + data);
                },
                error: function (error) {
                    console.error('Error fetching psychologist count:', error);
                }
            });
        }

        // Call the function initially
        updatepsychologistCount();

        // Set an interval to update the count every 5 minutes (300,000 milliseconds)
        setInterval(updatepsychologistCount, 300000);
    </script>




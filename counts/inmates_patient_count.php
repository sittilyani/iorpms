

<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as InmatesCount FROM patients WHERE current_status = 'Active' AND p_address = 'Inmate'";

$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$InmatesCount = $result['InmatesCount'];

// Output the count as plain text
echo $InmatesCount;
?>



    <script>
        // Function to update the count of Inmates users
        function updateInmatesCount() {
            $.ajax({
                url: 'Inmates_count.php',
                type: 'GET',
                success: function (data) {
                    $('#InmatessCount').text('Inmatess: ' + data);
                },
                error: function (error) {
                    console.error('Error fetching Inmates count:', error);
                }
            });
        }

        // Call the function initially
        updateInmatesCount();

        // Set an interval to update the count every 5 minutes (300,000 milliseconds)
        setInterval(updateInmatesCount, 300000);
    </script>




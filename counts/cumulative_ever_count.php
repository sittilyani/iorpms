<?php
// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of patients with 'Transit', 'New', or 'Re-induction' status
$sql = "SELECT COUNT(*) as everCount FROM patients WHERE mat_status NOT IN ('Re-induction')";
$stmt = $conn->query($sql);
$result = $stmt->fetch_assoc();

// Output the numeric count value
echo $result['everCount'];
?>

    <script>
        // Function to update the count of ever users
        function updateeverCount() {
            $.ajax({
                url: 'cumulative_ever_count.php',
                type: 'GET',
                success: function (data) {
                    $('#eversCount').text('evers: ' + data);
                },
                error: function (error) {
                    console.error('Error fetching ever count:', error);
                }
            });
        }

        // Call the function initially
        updateeverCount();                                                                           5

        // Set an interval to update the count every 5 minutes (300,000 milliseconds)
        setInterval(updateeverCount, 300000);
    </script>




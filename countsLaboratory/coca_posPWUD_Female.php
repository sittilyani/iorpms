<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database for the current month
$currentMonth = date('Y-m');
$sql = "SELECT COUNT(*) as positiveCount FROM laboratory WHERE cocaine = 'yes' AND sex = 'female' AND mode_drug_use ='pwud' AND DATE_FORMAT(visitDate, '%Y-%m') = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $currentMonth);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $positiveCount = $row['positiveCount'];

    // Output the count as plain text
    echo $positiveCount;
} else {
    // No records found for the current month
    echo "0";
}

// Close the statement and connection
$stmt->close();
$conn->close();

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

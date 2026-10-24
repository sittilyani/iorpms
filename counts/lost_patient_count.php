
<?php

// Include the config file to access the $conn variable

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as ltfuCount FROM patients WHERE current_status = 'ltfu'";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$ltfuCount = $result['ltfuCount'];

// Output the count as plain text
echo $ltfuCount;
?>

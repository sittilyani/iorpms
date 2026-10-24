
<?php

// Include the config file to access the $conn variable

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as voluntaryCount FROM patients WHERE current_status = 'Voluntary Discontinuation'";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$voluntaryCount = $result['voluntaryCount'];

// Output the count as plain text
echo $voluntaryCount;
?>

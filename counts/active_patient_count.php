

<?php

// Include the config file to access the $conn variable

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as ActiveCount FROM patients WHERE current_status = 'Active' AND (drugname LIKE '%Methadone%' OR drugname LIKE '%Buprenorphine%')";

$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$ActiveCount = $result['ActiveCount'];

// Output the count as plain text
echo $ActiveCount;
?>

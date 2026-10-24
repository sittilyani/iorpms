<?php

// Include the config file to access the $conn variable

// Fetch the count of users users from the database
$sql = "SELECT COUNT(*) as usersCount FROM tblusers";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$usersCount = $result['usersCount'];

// Output the count as plain text
echo $usersCount;
?>

<?php

// Include the config file to access the $conn variable

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as peerCount FROM tblusers WHERE userrole = 'Peer Educator'";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$peerCount = $result['peerCount'];

// Output the count as plain text
echo $peerCount;
?>

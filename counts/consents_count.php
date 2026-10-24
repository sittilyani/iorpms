<?php

// Include the config file to access the $conn variable

// Fetch the count of consents users from the database
$sql = "SELECT COUNT(*) as consentsCount FROM client_consents WHERE consent_status = 'yes'";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$consentsCount = $result['consentsCount'];

// Output the count as plain text
echo $consentsCount;
?>

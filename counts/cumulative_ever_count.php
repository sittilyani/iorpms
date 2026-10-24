<?php
// Include the config file to access the $conn variable

// Fetch the count of patients with 'Transit', 'New', or 'Re-induction' status
$sql = "SELECT COUNT(*) as everCount FROM patients WHERE mat_status NOT IN ('Re-induction')";
$stmt = $conn->query($sql);
$result = $stmt->fetch_assoc();

// Output the numeric count value
echo $result['everCount'];
?>

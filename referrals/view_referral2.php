<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('../includes/config.php');
include('../includes/header.php');
include('../includes/footer.php');

// Redirect if not logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['userrole'])) {
    header("Location: ../login.php");
    exit();
}

// Check if MAT ID is provided
if (!isset($_GET['mat_id'])) {
    echo "<p>Error: No MAT ID provided.</p>";
    echo '<a href="dashboard.php">Return to Dashboard</a>';
    exit();
}

$mat_id = $_GET['mat_id'];

// Fetch referral details by MAT ID
$sql = "SELECT * FROM referral WHERE mat_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $mat_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p>Error: No referral found for the provided MAT ID.</p>";
    echo '<a href="dashboard.php">Return to Dashboard</a>';
    exit();
}

$referral = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Referral Details</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
</head>
<body>
<div class="container">
    <h2>Referral Details</h2>
    <p><strong>MAT ID:</strong> <?php echo htmlspecialchars($referral['mat_id']); ?></p>
    <p><strong>Client Name:</strong> <?php echo htmlspecialchars($referral['clientName']); ?></p>
    <p><strong>Age:</strong> <?php echo htmlspecialchars($referral['age']); ?></p>
    <p><strong>Sex:</strong> <?php echo htmlspecialchars($referral['sex']); ?></p>
    <p><strong>Referral Notes:</strong> <?php echo htmlspecialchars($referral['referral_notes']); ?></p>
    <p><strong>Refer From:</strong> <?php echo htmlspecialchars($referral['refer_from']); ?></p>
    <p><strong>Refer To:</strong> <?php echo htmlspecialchars($referral['refer_to']); ?></p>
    <p><strong>Referring Officer:</strong> <?php echo htmlspecialchars($referral['referral_name']); ?></p>
    <p><strong>Referral Date:</strong> <?php echo htmlspecialchars($referral['referral_date']); ?></p>
    <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
</div>
</body>
</html>

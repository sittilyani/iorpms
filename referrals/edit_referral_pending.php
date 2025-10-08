<?php
session_start();
include('../includes/config.php');

// Check if `mat_id` is passed in the URL
if (!isset($_GET['referral_id'])) {
        die("No referral ID provided.");
}

$referral_id = $_GET['referral_id'];

// Fetch referral details (not patient details) since we're updating a referral
$sql = "SELECT * FROM referral WHERE referral_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $referral_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
        die("No referral found with the provided MAT ID.");
}

$referral = $result->fetch_assoc();
$stmt->close();

// Initialize messages
$successMessage = "";
$errorMessage = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $referral_id = $_POST['referral_id'];
        $refer_to = $_POST['refer_to'];
        $referral_notes = $_POST['referral_notes'];

        // Update only the refer_to and referral_notes fields
        $query = "UPDATE referral SET refer_to = ?, referral_notes = ? WHERE referral_id = ?";
        $stmt = $conn->prepare($query);

        if ($stmt) {
                $stmt->bind_param('ssi', $refer_to, $referral_notes, $referral_id);

                if ($stmt->execute()) {
                        $successMessage = "Referral redirected successfully! Redirecting to dashboard...";
                        // Set a delay before redirect
                        header("Refresh: 2; url=referral_dashboard.php");
                } else {
                        $errorMessage = "Error updating referral: " . $stmt->error;
                }
                $stmt->close();
        } else {
                $errorMessage = "Error preparing statement: " . $conn->error;
        }
}

// Check if the user is logged in and fetch their user_id
if (!isset($_SESSION['user_id'])) {
        die("You must be logged in to access this page.");
}
$loggedInUserId = $_SESSION['user_id'];

// Fetch the logged-in user's name from tblusers
$referral_name = 'Unknown';
$userQuery = "SELECT first_name, last_name, userrole, mobile FROM tblusers WHERE user_id = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param('i', $loggedInUserId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $referral_name = $user['first_name'] . ' ' . $user['last_name'];
        $user_role = $user['userrole'];
        $user_mobile = $user['mobile'];
} else {
        $user_role = "Role not found";
        $user_mobile = "Mobile not found";
}
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
        <title>Redirect Referral</title>
        <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
        <link rel="stylesheet" href="../assets/css/forms.css" type="text/css">
        <style>
                .form{
                        display: grid;
                        grid-template-columns: repeat(3, 1fr);
                    }
                .main-content {
                        padding: 20px;
                        width: 70%;
                        margin: 20px auto;
                        background-color: var(--card-background);
                        border-radius: 8px;
                        box-shadow: 0 4px 10px var(--shadow-light);
                }
                .alert {
                        padding: 15px;
                        margin-bottom: 20px;
                        border: 1px solid transparent;
                        border-radius: 4px;
                }
                .alert-success {
                        color: #3c763d;
                        background-color: #dff0d8;
                        border-color: #d6e9c6;
                }
                .alert-error {
                        color: #a94442;
                        background-color: #f2dede;
                        border-color: #ebccd1;
                }
        </style>
</head>
<body>

<div class="main-content">
        <div class="form-group"><h2>Redirect Referral</h2></div>

        <!-- Display success or error messages -->
        <?php if ($successMessage): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
                <!-- Hidden field for referral_id -->
                <input type="text" name="referral_id" value="<?php echo htmlspecialchars($referral['referral_id']); ?>">

                <div class="form-group">
                        <label for="mat_id">MAT ID:</label>
                        <input type="text" name="mat_id" value="<?php echo htmlspecialchars($referral['mat_id']); ?>" id="readonly-input" readonly>
                </div>
                <div class="form-group">
                        <label for="clientName">Client Name:</label>
                        <input type="text" name="clientName" value="<?php echo htmlspecialchars($referral['clientName']); ?>" id="readonly-input" readonly>
                </div>
                <div class="form-group">
                        <label for="age">Age:</label>
                        <input type="text" name="age" value="<?php echo htmlspecialchars($referral['age']); ?>" id="readonly-input" readonly>
                </div>
                <div class="form-group">
                        <label for="sex">Sex:</label>
                        <input type="text" id="readonly-input" readonly name="sex" value="<?php echo htmlspecialchars($referral['sex']); ?>">
                </div>
                <div class="form-group">
                        <label for="refer_from">Refer from Department</label>
                        <input type="text" id="refer_from" name="refer_from" class="form-control" readonly value="<?php echo htmlspecialchars($referral['refer_from']); ?>">
                </div>
                <div class="form-group">
                        <label for="mobile">Mobile Number</label>
                        <input type="text" id="mobile" name="mobile" class="form-control" readonly value="<?php echo htmlspecialchars($user_mobile); ?>">
                </div>
                <div class="form-group">
                        <label for="refer_to">Refer to Department</label>
                        <select id="select_dept" name="refer_to" class="form-control" required>
                                <?php
                                // Fetch status names from the "userroles" table
                                $statusQuery = "SELECT id, role FROM userroles";
                                $statusResult = $conn->query($statusQuery);

                                if ($statusResult->num_rows > 0) {
                                        while ($statusRow = $statusResult->fetch_assoc()) {
                                                $statusName = $statusRow['role'];
                                                $selected = ($statusName == $referral['refer_to']) ? 'selected' : '';
                                                echo "<option value='$statusName' $selected>$statusName</option>";
                                        }
                                } else {
                                        echo "<option value=''>No departments found</option>";
                                }
                                ?>
                        </select>
                </div>
                <div class="form-group">
                        <label for="referral_notes">Referral Notes:</label>
                        <textarea id="referral_notes" name="referral_notes" rows="4" required><?php echo htmlspecialchars($referral['referral_notes']); ?></textarea>
                </div>
                <div class="form-group">
                        <label for="referral_name">Referring Officer Name</label>
                        <input type="text" name="referral_name" id="readonly-input" readonly value="<?php echo htmlspecialchars($referral['referral_name']); ?>">
                </div>

                <?php if (!$successMessage): ?>
                        <button type="submit" class="custom-submit-btn">Redirect Referral</button>
                <?php endif; ?>
        </form>
</div>

</body>
</html>
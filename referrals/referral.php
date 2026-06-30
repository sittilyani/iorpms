<?php
session_start();
include('../includes/config.php');

// Check if `mat_id` is passed in the URL
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['mat_id'])) {
    die("No MAT ID provided.");
}

$mat_id = $_GET['mat_id'] ?? null;

// Fetch patient details
if ($mat_id) {
    $sql = "SELECT mat_id, clientName, age, sex FROM patients WHERE mat_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $mat_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("No patient found with the provided MAT ID.");
    }

    $patient = $result->fetch_assoc();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mat_id = $_POST['mat_id'];
    $clientName = $_POST['clientName'];
    $age = $_POST['age'];
    $sex = $_POST['sex'];
    $refer_from = $_POST['refer_from'];
    $refer_to = $_POST['refer_to'];
    $referral_notes = $_POST['referral_notes'];
    $referral_name = $_POST['referral_name'];

    $insert_sql = "INSERT INTO referral (mat_id, clientName, age, sex, referral_notes, refer_to, refer_from, referral_name)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param(
        "ssssssss",
        $mat_id,
        $clientName,
        $age,
        $sex,
        $referral_notes,
        $refer_to,
        $refer_from,
        $referral_name
    );

    if ($insert_stmt->execute()) {
    echo "<script>
            alert('Client Referred Successfully');
            setTimeout(function(){
                window.history.go(-2);
            }, 3000);
        </script>";
    exit();
} else {
    echo "Error: " . $conn->error;
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
    <title>Referral Form</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="../assets/css/forms.css" type="text/css">
    <style>
        .form{
            display: grid;
            grid-template-columns: repeat(3, 1fr);
          }
        .main-content {
            padding: 20px;
            max-width: 50%;
            margin: 20px auto; /* Center the main content */
            background-color: var(--card-background);
            border-radius: 8px;
            box-shadow: 0 4px 10px var(--shadow-light);
        }
    </style>
</head>
<body>

<div class="main-content">
        <div class="form-group"><h2>Referral Form</h2></div>

    <form method="POST" action="">
        <div class="form-group">
            <label for="mat_id">MAT ID:</label>  
            <input type="text"  name="mat_id" value="<?php echo htmlspecialchars($patient['mat_id']); ?>" id="readonly-input" readonly>
        </div>
        <div class="form-group">
            <label for="clientName">Client Name:</label> 
            <input type="text"  name="clientName" value="<?php echo htmlspecialchars($patient['clientName']); ?>" id="readonly-input" readonly>
        </div>
        <div class="form-group">
            <label for="age">Age:</label>  
            <input type="text"  name="age" value="<?php echo htmlspecialchars($patient['age']); ?>" id="readonly-input" readonly>
        </div>
        <div class="form-group">
            <label for="sex">Sex:</label>  
            <input type="text"  id="readonly-input" readonly name="sex" value="<?php echo htmlspecialchars($patient['sex']); ?>" >
        </div>
        <div class="form-group">
            <label for="refer_from">Refer from Department</label>
            <input type="text" id="refer_from" name="refer_from" class="form-control" readonly value="<?php echo htmlspecialchars($user_role); ?>">
        </div>
        <div class="form-group">
            <label for="mobile">Mobile Number</label>
            <input type="text" id="mobile" name="mobile" class="form-control" readonly value="<?php echo htmlspecialchars($user_mobile); ?>">
        </div>
        <div class="form-group">
            <label for="refer_to">Refer to Department</label>
            <select id="select_dept" name="refer_to" class="form-control">
                <?php
                // Fetch status names from the "userroles" table
                $statusQuery = "SELECT id, role FROM userroles";
                $statusResult = $conn->query($statusQuery);

                if ($statusResult->num_rows > 0) {
                    while ($statusRow = $statusResult->fetch_assoc()) {
                        $statusName = $statusRow['role'];
                        echo "<option value='$statusName'>$statusName</option>";
                    }
                } else {
                    echo "<option value=''>No departments found</option>";
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="referral_notes">Referral Notes:</label>  
            <textarea id="referral_notes" name="referral_notes" rows="4" required></textarea>
        </div>
        <div class="form-group">
            <label for="referral_name">Referring Officer Name</label>
            <input type="text" name="referral_name" id="readonly-input" readonly value="<?php echo htmlspecialchars($referral_name); ?>" >
        </div>
        <button type="submit" class="custom-submit-btn">Submit for review</button>
        </form>
    </div>

</body>
</html>
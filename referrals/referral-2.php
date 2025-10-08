<?php
ob_start();
include('../includes/config.php');
include("../includes/footer.php");
include('../includes/header.php');

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
    $referral_id = $_POST['referral_id'];
    $mat_id = $_POST['mat_id'];
    $clientName = $_POST['clientName'];
    $age = $_POST['age'];
    $sex = $_POST['sex'];
    $refer_from = $_POST['refer_from'];
    $refer_to = $_POST['refer_to'];
    $referral_notes = $_POST['referral_notes'];
    $referral_name = $_POST['referral_name'];
    $referral_date = $_POST['referral_date'] ?? date("Y-m-d H:i:s");

    $insert_sql = "INSERT INTO referral (referral_id, mat_id, clientName, age, sex, referral_notes, refer_to, refer_from, referral_name, referral_date)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param(
        "ssssssssss",
        $referral_id,
        $mat_id,
        $clientName,
        $age,
        $sex,
        $referral_notes,
        $refer_to,
        $refer_from,
        $referral_name,
        $referral_date
    );

    if ($insert_stmt->execute()) {
        echo "<script>
                alert('Client Referred Successfully');
                setTimeout(function(){
                    window.location.href = 'referral.php?message=" . urlencode("Referral successfully made!") . "&mat_id=" . urlencode($mat_id) . "';
                }, 3000);
              </script>";
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}
?>

<!-- This selects the logged in User Name -->

<?php
// Existing includes and configurations
include "../includes/config.php";

ob_start(); // Ensure sessions are started if not started already
$host = 'localhost';
$db = 'methadone';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Check if the user is logged in and fetch their user_id
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to access this page.");
}
$loggedInUserId = $_SESSION['user_id'];

// Fetch the logged-in user's name from tblusers
$referral_name = 'Unknown';
$userQuery = "SELECT first_name, last_name FROM tblusers WHERE user_id = ?";
$stmt = $mysqli->prepare($userQuery);
$stmt->bind_param('i', $loggedInUserId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $referral_name = $user['first_name'] . ' ' . $user['last_name'];
}
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Referral Form</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <style>
        .container-form{
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 50px;
            background-color: #FFFFDB;
            margin-left: 60px;
            width: 40%;
            border: dashed 1px;
            padding: 10px;
            font-family: "Times New Roman", Times, serif;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 15px;
        }
        .btn-submit {
            background-color: green;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-submit:hover {
            background-color: darkgreen;
        }
        #mobile, #refer_from, #readonly-input{
            background-color: #B0B0B0;
            cursor: not-allowed;
        }
        input, textarea{
             width: 100%;
         }
         textarea{
             height: 150px;
         }
    </style>
</head>
<body>

<div class="container-form">
    <div class="grid-item">
    <h3>Referral Form</h3>
    <form method="POST" action="">
        <div class="form-group">

            <label for="mat_id">MAT ID:</label>  <br>
            <input type="text"  name="mat_id" value="<?php echo $patient['mat_id']; ?>" id="readonly-input" readonly>
        </div>
        <div class="form-group">
            <label for="clientName">Client Name:</label> <br>
            <input type="text"  name="clientName" value="<?php echo $patient['clientName']; ?>" id="readonly-input" readonly>
        </div>
        <div class="form-group">
            <label for="age">Age:</label>  <br>
            <input type="text"  name="age" value="<?php echo $patient['age']; ?>" id="readonly-input" readonly>
        </div>
        <div class="form-group">
            <label for="sex">Sex:</label>  <br>
            <input type="text"  id="readonly-input" readonly name="sex" value="<?php echo $patient['sex']; ?>" >
        </div>

        <label for="refer_from">Refer from Department</label><br>
            <input type="text" id="refer_from" name="refer_from" class="form-control" readonly value="<?php
            // Ensure the session is active and `user_id` exists
            if (!isset($_SESSION['user_id'])) {
                die("You must be logged in to access this page.");
            }

            // Fetch the logged-in user's details
            $loggedInUserId = $_SESSION['user_id'];
            $userQuery = "SELECT userrole, mobile FROM tblusers WHERE user_id = ?";
            $stmt = $mysqli->prepare($userQuery);
            $stmt->bind_param('i', $loggedInUserId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                echo htmlspecialchars($user['userrole']); // Echo the user's role
            } else {
                echo "Role not found";
            }
            $stmt->close();
            ?>">
            <br>

        <label for="mobile">Mobile Number</label><br>
        <input type="text" id="mobile" name="mobile" class="form-control" readonly value="<?php
        // Reuse the `$user` data fetched above
        echo htmlspecialchars($user['mobile'] ?? 'Mobile not found');
        ?>">
    </div>
        <div class="grid-item">

        <label for="refer_to">Refer to Department</label><br>
        <select id="select_dept" name="refer_to" class="form-control">
            <?php
            // Fetch status names from the "status" table
            $statusQuery = "SELECT id, role FROM userroles";
            $statusResult = $mysqli->query($statusQuery);

            if ($statusResult->num_rows > 0) {
                while ($statusRow = $statusResult->fetch_assoc()) {
                    $statusName = $statusRow['role'];
                    $selected = ($statusName == $currentSettings['role']) ? 'selected' : '';
                    echo "<option value='$statusName' $selected>$statusName</option>";
                }
            } else {
                echo "<option value=''>No status found</option>";
            }
            ?>
        </select><br>

        <div class="form-group">
            <label for="referral_notes">Referral Notes:</label>  <br>
            <textarea id="referral_notes" name="referral_notes" rows="4" required></textarea>
        </div>

        <div class="form-group">
            <label for="referral_name">Referring Officer Name</label><br>
            <input type="text" name="referral_name" id="readonly-input" readonly value="<?php echo htmlspecialchars($referral_name); ?>" ><br>
        </div>
        <button type="submit" class="btn-submit">Submit Referral</button>


    </form>
    </div>
</div>

</body>
</html>

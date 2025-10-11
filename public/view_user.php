<?php
ob_start(); // Start output buffering
session_start(); // Assuming session is needed for user authentication

include '../includes/config.php'; // Database configuration

// --- PHP Logic for Fetching and Updating User Data ---
// --------------------------------------------------------

$user = []; // Initialize $user as an empty array

// Check if a user ID is provided in the URL
if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];

    // Prepare and execute the SQL query to fetch user data
    $sql = "SELECT * FROM tblusers WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if a user was found
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    } else {
        // Redirect to a list page with an error if the user is not found
        header("Location: userslist.php?error=user_not_found");
        exit();
    }
    $stmt->close(); // Close the statement
} else {
    // Redirect if no user ID is provided in the URL
    header("Location: userslist.php?error=user_id_missing");
    exit();
}

// Handle form submission for updating user details
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    // Sanitize and retrieve form data
    $user_id    = $_POST['user_id'];
    $username   = $_POST['username'];
    $first_name = $_POST['first_name'];
    $last_name  = $_POST['last_name'];
    $email      = $_POST['email'];
    $gender     = $_POST['gender'];
    $mobile     = $_POST['mobile'];
    $userrole   = $_POST['userrole'];

    // Prepare the SQL UPDATE statement
    $sql = "UPDATE tblusers SET username = ?, first_name = ?, last_name = ?, email = ?, gender = ?, mobile = ?, userrole = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);

    // Bind parameters and execute the statement
    $stmt->bind_param('sssssssi', $username, $first_name, $last_name, $email, $gender, $mobile, $userrole, $user_id);
    $stmt->execute();
    $stmt->close();

    // Redirect with a success message after a successful update
    header("Location: userslist.php?success=user_updated");
    exit();
}
ob_end_flush(); // End output buffering and send output to the browser
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View User Details</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            margin-top: 50px;
            max-width: 800px;
        }
        .card-header h2 {
            margin-bottom: 0;
        }
        .card-body p {
            font-size: 1.1em;
            margin-bottom: 0.5rem;
        }
        .card-body p strong {
            display: inline-block;
            width: 150px;
        }
        .btn-container {
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h2 class="text-center">View User Details</h2>
        </div>
        <div class="card-body">
            <?php if (!empty($user)): ?>
                <p><strong>User ID:</strong> <?php echo htmlspecialchars($user['user_id']); ?></p>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                <p><strong>First Name:</strong> <?php echo htmlspecialchars($user['first_name']); ?></p>
                <p><strong>Last Name:</strong> <?php echo htmlspecialchars($user['last_name']); ?></p>
                <p><strong>Full Name:</strong> <?php echo htmlspecialchars($user['full_name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>Gender:</strong> <?php echo htmlspecialchars($user['gender']); ?></p>
                <p><strong>Mobile:</strong> <?php echo htmlspecialchars($user['mobile']); ?></p>
                <p><strong>User Role:</strong> <?php echo htmlspecialchars($user['userrole']); ?></p>
                <p><strong>Date Created:</strong> <?php echo htmlspecialchars($user['date_created']); ?></p>
            <?php else: ?>
                <div class="alert alert-danger text-center" role="alert">
                    User not found.
                </div>
            <?php endif; ?>
        </div>
        <div class="card-footer text-center btn-container">
            <a href="userslist.php" class="btn btn-secondary">Back to User List</a>
        </div>
    </div>
</div>

</body>
</html>
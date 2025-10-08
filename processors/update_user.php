<?php
include '../includes/config.php';
include '../includes/footer.php';
include '../includes/header.php';

// Retrieve user details for the specified user_id
if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    $sql = "SELECT * FROM tblusers WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();


}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $user_id = $_POST['user_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $gender = $_POST['gender'];
    $mobile = $_POST['mobile'];
    $userrole = $_POST['userrole'];

    $sql = "UPDATE tblusers SET first_name = ?, last_name = ?, email = ?, gender = ?, mobile = ?, userrole = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssssi', $first_name,  $last_name, $email, $gender, $mobile, $userrole, $user_id);

    $stmt->execute();
    
    exit();
}


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update User</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <style>
        .container{
             background-color: none;
             width: 300px;
        }

    form{
            background-color: none;
        }

         input[type="submit"] {
                background-color: blue;
                color: white;
                padding: 10px 15px;
                margin-top: 15px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
            }
         h2{
             color: #000099;
             font-size: 32px;
         }

    </style>
</head>
<body>

<div class="container">

<h2>Update User</h2>

    <form action="update_user.php" method="post">
        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
        First Name:<br> <input type="text" name="first_name" value="<?php echo $user['first_name']; ?>"><br>
        Last Name:<br> <input type="text" name="last_name" value="<?php echo $user['last_name']; ?>"><br>
        Email:<br> <input type="text" name="email" value="<?php echo $user['email']; ?>"><br>
        Gender:<br> <input type="text" name="gender" value="<?php echo $user['gender']; ?>"><br>
        Mobile:<br> <input type="text" name="mobile" value="<?php echo $user['mobile']; ?>"><br>
        <label for="userrole">User Role:</label>
        <select name="userrole" required>
            <?php
            // Fetch genders from tblgender and populate the dropdown
            $result = $conn->query("SELECT id, role FROM userroles");
            while ($row = $result->fetch_assoc()) {
                echo "<option value='{$row['role']}'>{$row['role']}</option>";
            }
            ?>
        </select>  <br>
        <input type="submit" name="submit" value="Update">
    </form>
</div>
</body>
</html>

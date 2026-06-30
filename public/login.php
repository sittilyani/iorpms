<?php
session_start();

include '../includes/config.php';
include '../includes/languages.php';

$error_message = ''; // Initialize error message variable

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate and collect form data
    if (empty($_POST['username'])) {
        $error_message = "Username is required";
    } elseif (empty($_POST['password'])) {
        $error_message = "Password is required";
    } else {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);

        // Prepare and execute the SQL query to check user credentials
        $sql = "SELECT user_id, username, first_name, last_name, email, password, gender, mobile, userrole, date_created
                FROM tblusers
                WHERE username = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            die("Error preparing statement: " . $conn->error);
        }

        $stmt->bind_param("s", $username);
        $stmt->execute();

        if ($stmt->errno) {
            die("Error executing query: " . $stmt->error);
        }

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // User found, fetch the result
            $user = $result->fetch_assoc();

            // Verify password
            if (password_verify($password, $user['password'])) {
                // Password is correct, store user details in session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['userrole'] = $user['userrole'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['gender'] = $user['gender'];
                $_SESSION['mobile'] = $user['mobile'];
                $_SESSION['last_activity'] = time(); // Set last activity time

                // Regenerate session ID for security
                session_regenerate_id(true);

                // Redirect to dashboard (no role-based redirection)
                header("Location: ../dashboard/dashboard.php");
                exit();
            } else {
                // Invalid password
                $error_message = "Invalid credentials. Please try again.";
            }
        } else {
            // User not found
            $error_message = "Invalid credentials. Please try again.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EasyFlow-L</title>
    <link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.css" type="text/css">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/favicon_io/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicon_io/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicon_io/favicon-16x16.png">
    <link rel="manifest" href="../assets/favicon_io/site.webmanifest">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="../assets/css/header-dash.css" type="text/css">


    <style>
        .container{
          margin-top: 2%;

        }

       .grid-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr); /* Three equal columns */
            grid-gap: 20px; /* Gap between grid items */
        }

        .container-item {
            display: flex;
            flex-direction: row;
            justify-content: center; /* Align items vertically center */
            align-items: center; /* Align items horizontally center */
            width: 100%;
            margin-bottom: 20px;
            text-align: center; /* Align text content center */

        }

        #errorMessage {
            color: red;
        }
        h2{
            color: #2D008A;
        }
        label{
            color: #2D008A;
            font-weight: bold;
            font-size: 22px;
            margin: 10px;
            color: #2D008A;
            font-family: Arial, Helvetica, sans-serif;
        }
         input{
             width: 300px;
             height: 50px;
             font-size: 22px;
             border-radius: 5px;
             text-align: center;
         }
         .btn-submit{
             background-color: #2D008A;
             color: #FFFFFF;
             font-size: 22px;
             font-weight: bold;
             width: 100%;
             height: 50px;
             border: none;
             border-radius: 5px;

         }

         .btn-submit:hover{
            cursor:pointer;
        }
    </style>
</head>
<body>
        <div class="container">

                <!--Error Message Div-->
                    <center>
                        <div id="errorMessage" style="color: red;">
                            <?php echo $error_message; ?>
                        </div>
                    </center>

            <div class="container-item">
                <div class="logo">
                    <img src="../assets/images/easyflow_logonew.png" width="225" height="101" alt="">

                </div>
            </div>
            <div class="container-item">

                    <!-- Your login form goes here -->
                    <form action="login.php" method="post">
                        <!-- Your form fields go here -->
                        <label for="username"><?php echo $text['usernname']; ?></label> <br><br>
                        <input type="text" id="username" name="username" required>
                        <br><br>
                        <label for="password"><?php echo $text['password']; ?></label> <br><br>
                        <input type="password" id="password" name="password" required>
                        <br><br>
                        <button type="submit" class="btn-submit"><?php echo $text['login']; ?></button>
                    </form>
            </div>
            <div class="container-item">
                <table style="margin: 20px auto; border-collapse: collapse; background: #f8f9fa; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                    <tr style="background: #2D008A; color: white;">
                        <th style="padding: 10px 20px;">Select Language / Choisir la langue</th>
                    </tr>
                    <tr>
                        <td style="padding: 15px; text-align: center;">
                            <a href="login.php?lang=en" style="text-decoration: none; color: #2D008A; font-weight: bold; margin-right: 20px;">
                               <img src="../assets/images/en_flag.png" width="20" style="vertical-align: middle;">&nbsp;&nbsp;English
                            </a>
                            <a href="login.php?lang=fr" style="text-decoration: none; color: #2D008A; font-weight: bold;">
                               <img src="../assets/images/fr_flag.png" width="20" style="vertical-align: middle;"> &nbsp;&nbsp;Français
                            </a>
                        </td>
                    </tr>
                </table>
            </div>

        </div>

<?php include '../includes/footer.php'; ?>

</body>
</html>
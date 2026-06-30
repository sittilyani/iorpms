<?php
 include '../includes/footer.php';
 include '../includes/header.php';

?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Error</title>
    <link rel="stylesheet" href="assets/fontawesome/css/fontawesome.min.css" type="text/css">
    <link rel="icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <style>
           .error{
               align-content: center;
               margin-top: 100px;
               align-items: center;
               font-size: 20px;
           }

    </style>
</head>
<body>
      <div class="error">
           <center>

               <img src="../assets/images/danger-4148.svg " width="80" height="60" alt="">


               <p>
                <span style="color:red;">Oooops!</span> You Are Not Allowed to edit patient   <br><br>
                  Please contact your administrator or <a href="prescribe.php">Go back</a>
                </p>
            </center>
      </div>
</body>
</html>
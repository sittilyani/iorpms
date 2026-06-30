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
               font-size: 18px;
           }
           p{
               color:red;
           }

    </style>
</head>
<body>
      <div class="error">
           <center>

               <img src="../assets/images/danger-4148.svg" width="80" height="60" alt="">



                  <p>Sorry! You Are Not Allowed to perform this operation </p>
                  Please contact your administrator

            </center>
      </div>

<script>
    // Redirect to dispense.php after 2 seconds
    setTimeout(function() {
        window.location.href = "dispense.php";
    }, 2000);
</script>

</body>
</html>

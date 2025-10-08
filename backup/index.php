<?php

 include ("../includes/footer.php");
 include ("../includes/header.php");

?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Backup Methadone DB</title>

    <style>
            body{
             font-family: "Times New Roman", Times, serif;
            }

            h3{
                margin-bottom: 10px;
                color:: #FF3333;
                margin-left: 80px;
                font-size: 24px;

            }
             .link-btn{
               cursor: pointer;
               color: white;
               margin-left: 80px;
               width: 100px;

             }
             .link-btn:hover{
                 background-color: #CC0000;
             }

    </style>
</head>

<body>


        <h3>Please Click to Backup MAT Database</h3>

      <div class='link-btn'>
         <a href="backup.php" class="btn btn-success">Backup MAT Database</a>
      </div>


    <script src="assets/js/jquery.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="../assets/js/bootstrap.min.js"></script>
</body>

</html>
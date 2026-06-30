<?php
include '../includes/config.php';
include '../includes/footer.php';
include ("../includes/header.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Methadone Balance</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <style>
          #methadoneBalance{
              color: #000099;
              font-size: 24px;
              font-weight: bold;
          }
           h2{
               color: red;
           }
          .image{
              margin-top: 30px;
              margin-left: 120px;
              margin-right: auto;
          }

    </style>
</head>
<body>

<div class="image">

<img src="../assets/images/Methadone.jpg" width="200" height="200" alt="">

</div>

<div class="container mt-5">
    <h2>Methadone Balance</h2>

    <!-- Display Total Stock Quantity -->
    <div id="methadoneBalance"></div>

    <!-- JavaScript for auto-updating -->
    <script>
        // Function to update Methadone balance
        function updateMethadoneBalance() {
            // Fetch total_stockqty using AJAX
            var xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    // Update the content of the div
                    document.getElementById("methadoneBalance").innerHTML = "Methadone 5mg/mL Balance: " + xhr.responseText;
                }
            };
            xhr.open("GET", "get_methadone_balance.php", true);
            xhr.send();
        }

        // Update Methadone balance initially
        updateMethadoneBalance();

        // Set interval for auto-updating every 60 seconds (adjust as needed)
        setInterval(updateMethadoneBalance, 60000);
    </script>
</div>

</body>
</html>

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
    <title>Buprenorphine 2mg Balance</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
</head>
<body>

<div class="container mt-5">
    <h2>Buprenorphine 2mg Balance</h2>

    <!-- Display Total Stock Quantity -->
    <div id="buprenorphineBalance"></div>

    <!-- JavaScript for auto-updating -->
    <script>
        // Function to update Methadone balance
        function updateBuprenorphineBalance() {
            // Fetch total_stockqty using AJAX
            var xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    // Update the content of the div
                    document.getElementById("buprenorphineBalance").innerHTML = "Buprenorphine: " + xhr.responseText;
                }
            };
            xhr.open("GET", "get_bupronorphine2_balance.php", true);
            xhr.send();
        }

        // Update Buprenorphine balance initially
        updateBuprenorphineBalance();

        // Set interval for auto-updating every 60 seconds (adjust as needed)
        setInterval(updateNaloxoneBalance, 60000);
    </script>
</div>

</body>
</html>

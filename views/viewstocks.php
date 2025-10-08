<?php
  include '../includes/config.php';
  include '../includes/footer.php';
include '../includes/header.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Stocks Balances</title>
    <!-- Add your CSS and other header content here -->
    <style>
          body{
              font-family: "Times New Roman", Times, serif;
          }

          .container{
              background-color: none;
              padding: 40px;
          }

    </style>
</head>
<body>
    <div class="container">
    <h3>Other Current Stocks' Balances</h3>
    <!-- Include the content of the other file -->
    <?php include '../OtherDrugsBalances/buprenorphine2mg_balance.php'; ?>
    <?php include '../OtherDrugsBalances/buprenorphine4mg_balance.php'; ?>
    <?php include '../OtherDrugsBalances/buprenorphine8mg_balance.php'; ?>
    <?php include '../OtherDrugsBalances/Naloxone_balance.php'; ?>
    <?php include '../OtherDrugsBalances/Naltrexone50mg_balance.php'; ?>
    <?php include '../OtherDrugsBalances/Naltrexone100mg_balance.php'; ?>
    <?php include '../OtherDrugsBalances/Naltrexone150mg_balance.php'; ?>
    <?php include '../OtherDrugsBalances/NaltrexoneImplant_balance.php'; ?> 

    <!-- Add any additional HTML content here -->
    </div>
</body>
</html>

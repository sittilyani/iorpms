<?php
include '../includes/config.php';
  include "../includes/header.php";

?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <style>
        .grid-container{
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            margin-top: 80px;
            grid-gap: 10px;
            margin-left: 40px;
            padding: 20px;
        }
         h4{
             margin-left: 30px;
             font-size: 18px;
             color: red;
             margin-left: 40px;

         }
          a{
              font-size: 16px;
              font-weight: bold;
              color: #000099;
          }

    </style>
</head>
<body>
     <div class="grid-container">
        <div class="grid-item">
             <h4>Excel Printable</h4>
             <ul><a href="Excel FormsPrintable.xlsx">Excel Forms Download</a></ul>
        </div>
         <div class="grid-item">
             <h4>Pharmacy Forms</h4>
             <ul><a href="FormP1a.pdf">Form 1A</a></ul>
             <ul><a href="Form P1b.pdf">Form 1B</a></ul>
             <ul><a href="FormP2.pdf">Form P2</a></ul>
             <ul><a href="FORMP3.pdf">Form P3</a></ul>
             <ul><a href="FORM P4.pdf">Form P4</a></ul>
             <ul><a href="FORMP5.pdf">Form P5</a></ul>
             <ul><a href="FORM P6.pdf">Form P6</a></ul>
             <ul><a href="Form P7.pdf">Form P7</a></ul>
             <ul><a href="FORMP8.pdf">Form P8</a></ul>
             <ul><a href="FORMP9.pdf">Form P9</a></ul>

         </div>
         <div class="grid-item">
             <h4>Guidelines and Policies</h4>
             <ul><a href="Operational Manual - IORPMS2.pdf">Standard Operating Procedures Manual</a></ul>
             <ul><a href="methadone_policies-e.pdf">Methadone Policies</a> </ul>
             <ul><a href="Kenya-ARV-Guidelines-2022.pdf">ART Guidelines 2022</a></ul>
         </div>
         <div class="grid-item">
             <h4>Mothly Reports</h4>
             <ul>
                <a href="../Monthly Reports/view_reports.php" target="_blank">Monthly Reports</a>
            </ul>

         </div>
         <div class="grid-item">
             <h4>Other Forms</h4>
             <ul><a href="">Others</a></ul>
         </div>

      </div>

  <?php include "../includes/footer.php"; ?>
</body>
</html>
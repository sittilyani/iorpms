<?php
    include "../includes/config.php"; 

    // Get current month and year for default selection (optional)
    $currentMonth = date('m');
    $currentYear = date('Y');
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PsychoSocioReports</title>
    
    <style>
        .grid-container{
            display: grid;
            grid-template-columns: repeat(6, 8fr);
            padding: 20px 40px;
            grid-gap: 20px;
            height: 75vh;

        }
        .grid-item{
            border: solid thin;
            align-items: center;
            align-content: center;
            padding: 10px 20px;

        }
          #header{
            font-weight: bold;
          }
          h3{
            margin-left: 40px;
          }

    </style>
</head>
<body>
    <h3>Psychosocial Outcomes Report</h3>
    
    <div class="grid-container">
            <div class="grid-item" id="header">ATTENDANCE</div>

            <div class="grid-item" id="header">15-20 years</div>
            <div class="grid-item" id="header">18-24 years</div>
            <div class="grid-item" id="header">25-35</div>
            <div class="grid-item" id="header">36 + years</div>
            <div class="grid-item" id="header">Total</div>
            <div class="grid-item" id="header">MALE</div>
            <div class="grid-item"><?php include '../counts/psycho_male15_20.php'; ?></div>
            <div class="grid-item"><?php include '../counts/psycho_male21_24.php'; ?></div>
            <div class="grid-item"><?php include '../counts/psycho_male25_35.php'; ?></div>
            <div class="grid-item"><?php include '../counts/psycho_male36_above.php'; ?></div>
            <div class="grid-item"><?php include '../counts/psycho_male_total.php'; ?></div>
            <div class="grid-item" id="header">FEMALE</div>
            <div class="grid-item"><?php include '../counts/psycho_female15_20.php'; ?></div>
            <div class="grid-item"><?php include '../counts/psycho_female21_24.php'; ?></div>
            <div class="grid-item"><?php include '../counts/psycho_female25_35.php'; ?></div>
            <div class="grid-item"><?php include '../counts/psycho_female36_above.php'; ?></div>
            <div class="grid-item"><?php include '../counts/psycho_female_total.php'; ?></div>
            <div class="grid-item" id="header">OTHERS</div>
            <div class="grid-item"><?php include '../counts/psycho_others15_20.php'; ?></div>
            <div class="grid-item"><?php include '../counts/psycho_others21_24.php'; ?></div>
            <div class="grid-item"><?php include '../counts/psycho_others25_35.php'; ?></div>
            <div class="grid-item"><?php include '../counts/psycho_others36_above.php'; ?></div>
            <div class="grid-item"><?php include '../counts/psycho_others_total.php'; ?></div>
            <div class="grid-item" id="header">PSYCHO-SOCIO OUTCOMES</div>
            <div class="grid-item" id="header">Re-integration</div>
            <div class="grid-item" id="header">Employment</div>
            <div class="grid-item" id="header">Stable accomodation</div>
            <div class="grid-item" id="header">GBV support</div>
            <div class="grid-item" id="header">Total</div>
            <div class="grid-item" id="header">MALE</div>
            <div class="grid-item"><?php include '../counts/reintegration_maleCount.php'; ?></div>
            <div class="grid-item"><?php include '../counts/employment_maleCount.php'; ?></div>
            <div class="grid-item"><?php include '../counts/accomodation_maleCount.php'; ?></div>
            <div class="grid-item"><?php include '../counts/gbvsupport_maleCount.php'; ?></div>
            <div class="grid-item"></div>
            <div class="grid-item" id="header">FEMALE</div>
            <div class="grid-item"><?php include '../counts/reintegration_femaleCount.php'; ?></div>
            <div class="grid-item"><?php include '../counts/employment_femaleCount.php'; ?></div>
            <div class="grid-item"><?php include '../counts/accomodation_femaleCount.php'; ?></div>
            <div class="grid-item"><?php include '../counts/gbvsupport_femaleCount.php'; ?></div>
            <div class="grid-item"></div>
            <div class="grid-item" id="header">OTHERS</div>
            <div class="grid-item"><?php include '../counts/reintegration_othersCount.php'; ?></div>
            <div class="grid-item"><?php include '../counts/employment_othersCount.php'; ?></div>
            <div class="grid-item"><?php include '../counts/accomodation_othersCount.php'; ?></div>
            <div class="grid-item"><?php include '../counts/gbvsupport_othersCount.php'; ?></div>
            <div class="grid-item"></div>
    </div>

</body>
</html>
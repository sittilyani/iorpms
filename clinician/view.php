<?php
session_start();
include('../includes/config.php');

if (isset($_GET['mat_id'])) {
    $viral_loadId = $_GET['mat_id'];

    // Fetch viral_load details from the database based on the ID
    $sql = "SELECT * FROM viral_load WHERE mat_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $viral_loadId);
    $stmt->execute();
    $result = $stmt->get_result();

    $viral_load = $result->fetch_assoc();

    if (!$viral_load) {
        die("viral_load not found");
    }
} else {
    die("Invalid request. Please provide a viral_load ID.");
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Client</title>
    <link rel="icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <style>
        /* Add any additional styling as needed */
        .grid-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            grid-gap: 30px;
            padding-left: 10px;
            padding-right: 10px;
            margin-left: 30px;
        }
          h3{
              margin-top: 5px;
              margin-left: 30px;
              margin-bottom: 20px;
          }

        .grid-item {
            grid-column: span 1;
            border: none;
            padding: 10px;

        }
        label {
            font-weight: bold;
            margin-right: 10px;

        }
        span {
            color: blue;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        .grid-item-photo{
            border: solid thin;
            padding: 30px;
            margin-right: 20px;
        }
        .head-buttons{
            display: in-line block;
            align-content: center;
            align-items: center;
            margin-left: 40px;
            margin-bottom: 20px;
            background-color: yellow;
            height: 60px;

        }
        #psycho{
            background-color: green;
            color: white;
            padding: 5px;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            width: 200px;
            margin-left: 30px
        }
         #exit{
            background-color: red;
            color: white;
            padding: 5px;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            width: 160px;
            margin-left: 40px;
        }
    </style>
</head>
<body>
      <h3>Client Details</h3>

    <div class="grid-container">
        <div class="grid-item">
        <!-- Display viral_load details as needed -->
        
        <div>
            <label for="mat_id">MAT ID:</label>
            <span><?php echo $viral_load['mat_id']; ?></span>
        </div>


        <div>
            <label for="clientName">Client Name:</label>
            <span><?php echo $viral_load['clientName']; ?></span>
        </div>


        <div class="grid-item">

        <div>
            <label for="dob">Date of Birth:</label>
            <span><?php echo $viral_load['dob']; ?></span>
        </div>

        <div>
            <label for="reg_date">Date of Registration:</label>
            <span><?php echo $viral_load['reg_date']; ?></span>
        </div>

        <div>
            <label for="sex">Sex:</label>
            <span><?php echo $viral_load['sex']; ?></span>
        </div>

        </div>

    <div class="grid-item">
        <div>
            <label for="hiv_status">HIV Status:</label>
            <span><?php echo $viral_load['hiv_status']; ?></span>
        </div>
        <div>
            <label for="art_regimen">ART Regimen:</label>
            <span><?php echo $viral_load['art_regimen']; ?></span>
        </div>
        <div>
            <label for="regimen_type">Regimen Type:</label>
            <span><?php echo $viral_load['regimen_type']; ?></span>
        </div>
        <div>
            <label for="clinical_notes">Clinical Notes:</label>
            <span><?php echo $viral_load['clinical_notes']; ?></span>
        </div>
        <div>
            <label for="last_vlDate">Last Visit Date:</label>
            <span><?php echo $viral_load['last_vlDate']; ?></span>
        </div>
        <div>
            <label for="results">Results:</label>
            <span><?php echo $viral_load['results']; ?></span>
        </div>

        <div>
            <label for="next_appointment">Next Appointmnet:</label>
            <span><?php echo $viral_load['next_appointment']; ?></span>
        </div>


    </div>
</body>
</html>

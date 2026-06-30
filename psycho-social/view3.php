<?php
session_start();
include('../includes/config.php');
include('../includes/footer.php');
include('../includes/header.php');

if (isset($_GET['p_id'])) {
    $patientsId = $_GET['p_id'];

    // Fetch patients details from the database based on the ID
    $sql = "SELECT * FROM patients WHERE p_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $patientsId);
    $stmt->execute();
    $result = $stmt->get_result();

    $patients = $result->fetch_assoc();

    if (!$patients) {
        die("patients not found");
    }
} else {
    die("Invalid request. Please provide a patients ID.");
}

// Fetch photo details from the database based on the MAT ID
$sql_photo = "SELECT * FROM photos WHERE mat_id = ?";
$stmt_photo = $conn->prepare($sql_photo);
$stmt_photo->bind_param('s', $patients['mat_id']); // Assuming 'mat_id' is the column in the 'patients' table
$stmt_photo->execute();
$result_photo = $stmt_photo->get_result();
$photo = $result_photo->fetch_assoc();
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
        <!-- Display patients details as needed -->

        <div>
            <label for="reg_facility">Facility:</label>
            <span><?php echo $patients['reg_facility']; ?></span>
        </div>
        <div>
            <label for="mflcode">MFL Code:</label>
            <span><?php echo $patients['mflcode']; ?></span>
        </div>
        <div>
            <label for="county">County:</label>
            <span><?php echo $patients['county']; ?></span>
        </div>
        <div>
            <label for="scounty">Sub County:</label>
            <span><?php echo $patients['scounty']; ?></span>
        </div>
        <div>
            <label for="reg_date">Enrolment Date:</label>
            <span><?php echo $patients['reg_date']; ?></span>
        </div>
        <div>
            <label for="mat_id">MAT ID:</label>
            <span><?php echo $patients['mat_id']; ?></span>
        </div>
        <div>
            <label for="mat_number">MAT Number:</label>
            <span><?php echo $patients['mat_number']; ?></span>
        </div>
        <div>
            <label for="clientName">Client Name:</label>
            <span><?php echo $patients['clientName']; ?></span>
        </div>

        <div>
            <label for="sname">SurName:</label>
            <span><?php echo $patients['sname']; ?></span>
        </div>
        <div>
            <label for="nickName">Nick Name:</label>
            <span><?php echo $patients['nickName']; ?></span>
        </div>
        <div>
            <label for="nat_id">National ID:</label>
            <span><?php echo $patients['nat_id']; ?></span>
        </div>

        </div>

        <div class="grid-item">

        <div>
            <label for="dob">Date of Birth:</label>
            <span><?php echo $patients['dob']; ?></span>
        </div>
        <div>
            <label for="age">Age:</label>
            <span><?php echo $patients['age']; ?></span>
        </div>
        <div>
            <label for="sex">Sex:</label>
            <span><?php echo $patients['sex']; ?></span>
        </div>
        <div>
            <label for="residence_scounty">Residence Sub County:</label>
            <span><?php echo $patients['residence_scounty']; ?></span>
        </div>
        <div>
            <label for="p_address">Residence:</label>
            <span><?php echo $patients['p_address']; ?></span>
        </div>
        <div>
            <label for="client_phone">Client Phone:</label>
            <span><?php echo $patients['client_phone']; ?></span>
        </div>
        <div>
            <label for="mat_status">MAT services before?</label>
            <span><?php echo $patients['mat_status']; ?></span>
        </div>
        <div>
            <label for="transfer_id">Transfer ID:</label>
            <span><?php echo $patients['transfer_id']; ?></span>
        </div>
        <div>
            <label for="referral_type">Referral Type:</label>
            <span><?php echo $patients['referral_type']; ?></span>
        </div>
        <div>
            <label for="referring_facility">Referring Facility:</label>
            <span><?php echo $patients['referring_facility']; ?></span>
        </div>
        <div>
            <label for="reffering_fac_client_number">Referring Facility Client Number:</label>
            <span><?php echo $patients['reffering_fac_client_number']; ?></span>
        </div>


        </div>

    <div class="grid-item">
        <div>
            <label for="accompanment_type">Accompanment Type:</label>
            <span><?php echo $patients['accompanment_type']; ?></span>
        </div>
        <div>
            <label for="peer_edu_name">Peer Educator Name:</label>
            <span><?php echo $patients['peer_edu_name']; ?></span>
        </div>
        <div>
            <label for="peer_edu_phone">Peer Educator Phone:</label>
            <span><?php echo $patients['peer_edu_phone']; ?></span>
        </div>
        <div>
            <label for="rx_supporter_name">Treatment Supporter Name:</label>
            <span><?php echo $patients['rx_supporter_name']; ?></span>
        </div>
        <div>
            <label for="drugname">Drug:</label>
            <span><?php echo $patients['drugname']; ?></span>
        </div>
        <div>
            <label for="dosage">Start Dosage:</label>
            <span><?php echo $patients['dosage']; ?></span>
        </div>
        <div>
            <label for="current_status">Current Status:</label>
            <span><?php echo $patients['current_status']; ?></span>
        </div>
        <div>
            <label for="hcw_name">Registering Healthcare Worker Name:</label>
            <span><?php echo $patients['hcw_name']; ?></span>
        </div>

    </div>
    <div class="grid-item-photo">
        <!-- Display photo based on mat_id -->
        <!--<img src="<?php echo $photo['Image']; ?>" alt="Patient Photo">  -->
        <img src="data:image/jpeg;base64,<?php echo base64_encode($photo['Image']); ?>" alt="Patient Photo">

    </div>
    </div>
</body>
</html>

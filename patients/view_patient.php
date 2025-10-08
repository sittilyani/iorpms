<?php
session_start();
include('../includes/config.php');

if (isset($_GET['p_id'])) {
    $patientsId = $_GET['p_id'];

    // Fetch patient details from the database based on the ID
    $sql = "SELECT * FROM patients WHERE p_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $patientsId);
    $stmt->execute();
    $result = $stmt->get_result();

    $patients = $result->fetch_assoc();

    if (!$patients) {
        die("Patient not found");
    }
} else {
    die("Invalid request. Please provide a patient ID.");
}

// Fetch photo details from the database based on the MAT ID
$sql_photo = "SELECT image FROM photos WHERE mat_id = ? ORDER BY visitDate DESC LIMIT 1";
$stmt_photo = $conn->prepare($sql_photo);
$stmt_photo->bind_param('s', $patients['mat_id']);
$stmt_photo->execute();
$result_photo = $stmt_photo->get_result();
$photo = $result_photo->fetch_assoc();

// Check if photo exists in the file system
$photoPath = '';
if ($photo && !empty($photo['image'])) {
    $photoPath = '../clientPhotos/' . $photo['image'];

    // Check if the file actually exists
    if (!file_exists($photoPath)) {
        $photoPath = ''; // Reset if file doesn't exist
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View client details</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="../assets/css/view.css" type="text/css">
    <style>
        .grid-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
        .grid-item-photo {
            grid-column: 1 / -1;
            text-align: center;
            margin-top: 20px;
        }
        .grid-item-photo img {
            max-width: 200px;
            max-height: 200px;
            border: 2px solid #2C3162;
            border-radius: 5px;
        }
        .photo-placeholder {
            width: 200px;
            height: 200px;
            border: 2px dashed #ccc;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="content-main">
        <h3>Client Details</h3>
        <div class="grid-container">
            <div class="grid-item">
                <div><label>Enrolment Date:</label><span><?php echo htmlspecialchars($patients['reg_date']); ?></span></div>
                <div><label>MAT ID:</label><span><?php echo htmlspecialchars($patients['mat_id']); ?></span></div>
                <div><label>MAT Number:</label><span><?php echo htmlspecialchars($patients['mat_number']); ?></span></div>
                <div><label>Client Name:</label><span><?php echo htmlspecialchars($patients['clientName']); ?></span></div>
                <div><label>Surname:</label><span><?php echo htmlspecialchars($patients['sname']); ?></span></div>
                <div><label>Nickname:</label><span><?php echo htmlspecialchars($patients['nickName']); ?></span></div>
                <div><label>National ID:</label><span><?php echo htmlspecialchars($patients['nat_id']); ?></span></div>
            </div>

            <div class="grid-item">
                <div><label>Date of Birth:</label><span><?php echo htmlspecialchars($patients['dob']); ?></span></div>
                <div><label>Age:</label><span><?php echo htmlspecialchars($patients['age']); ?></span></div>
                <div><label>Sex:</label><span><?php echo htmlspecialchars($patients['sex']); ?></span></div>
                <div><label>Residence Sub County:</label><span><?php echo htmlspecialchars($patients['residence_scounty']); ?></span></div>
                <div><label>Residence:</label><span><?php echo htmlspecialchars($patients['p_address']); ?></span></div>
                <div><label>Client Phone:</label><span><?php echo htmlspecialchars($patients['client_phone']); ?></span></div>
                <div><label>MAT services before?</label><span><?php echo htmlspecialchars($patients['mat_status']); ?></span></div>
                <div><label>Referring Facility:</label><span><?php echo htmlspecialchars($patients['referring_facility']); ?></span></div>
                <div><label>Referring Facility Client No:</label><span><?php echo htmlspecialchars($patients['reffering_fac_client_number']); ?></span></div>
            </div>

            <div class="grid-item">
                <div><label>Peer Educator Name:</label><span><?php echo htmlspecialchars($patients['peer_edu_name']); ?></span></div>
                <div><label>Peer Educator Phone:</label><span><?php echo htmlspecialchars($patients['peer_edu_phone']); ?></span></div>
                <div><label>Treatment Supporter Name:</label><span><?php echo htmlspecialchars($patients['rx_supporter_name']); ?></span></div>
                <div><label>Drug:</label><span><?php echo htmlspecialchars($patients['drugname']); ?></span></div>
                <div><label>Start Dosage:</label><span><?php echo htmlspecialchars($patients['dosage']); ?></span></div>
                <div><label>Current Status:</label><span><?php echo htmlspecialchars($patients['current_status']); ?></span></div>
                <div><label>Registering HCW Name:</label><span><?php echo htmlspecialchars($patients['hcw_name']); ?></span></div>
            </div>

            <div class="grid-item">
                <?php if ($photoPath && file_exists($photoPath)): ?>
                    <img src="<?php echo $photoPath; ?>" alt="Patient Photo">
                <?php else: ?>
                    <div class="photo-placeholder">
                        <span>No photo available</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
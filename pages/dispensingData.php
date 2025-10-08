<?php
include "../includes/config.php";
include "../includes/footer.php";
include "../includes/header.php";

$host = 'localhost';
$db = 'methadone';
$user = 'root';
$pass = 'sittilyani';
$charset = 'utf8mb4';

$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Get the user_id from the query parameter (if applicable)
$userId = isset($_GET['p_id']) ? $_GET['p_id'] : null;

// Fetch the current settings for the user (if applicable)
$currentSettings = [];
if ($userId) {
    $query = "SELECT * FROM patients WHERE p_id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentSettings = $result->fetch_assoc();
}

?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LabDAR</title>

    <style>
        .grid-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            background-color: #99CCFF;
            margin: 0 50px;
            padding: 20px;
        }

        input, select {
            width: 250px;
            height: 30px;
            margin-bottom: 15px;
            margin-top: 10px;
        }

        label {
            font-weight: bold;
        }

        h2 {
            color: #2C3162;
            margin-top: 80px;
            margin-left: 50px;
        }

        #btn-submit {
            width: 250px;
            color: white;
            background-color: #2C3162;
            height: 35px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
        }
        .readonly-input{
            background-color: #FFFF94;
            cursor: not-allowed;
        }
         .custom-btn-submit{
             width: 250px;
             height: 35px;
             margin-bottom: 20px;
             background-color: red;
             color: white;
             cursor: pointer;
             border: none;
            border-radius: 5px;
            font-size: 18px;
            font-weight: bold;
         }
         .custom-btn-cancel,
        .custom-btn-home {
            display: inline-block;
            width: 250px;
            height: 40px;
            text-align: center;
            line-height: 40px; /* Center the text vertically */
            background-color: #2C3162; /* Example background color */
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 5px;
            margin-bottom: 10px; /* Add spacing between the buttons */
        }

        .custom-btn-cancel:hover,
        .custom-btn-home:hover {
            background-color: #1E2255; /* Example background color on hover */
        }
         .custom-btn-submit:hover {
            background-color: orange;
            color: black;
        }

    </style>
</head>
<body>
    <h2>Laboratory Daily Activity Register</h2>

    <form action="labdar_process.php" method="post" class="post">
        <div class="grid-container">
            <div class="grid-item">
                <label for="visitDate">Visit Date</label><br>
                <input type="text" name="visitDate" class="readonly-input readonly" value="<?php echo date('Y-m-d'); ?>"><br>

                <label for="mat_id">MAT ID</label><br>
                <input type="text" name="mat_id" class="readonly-input readonly" value="<?php echo isset($currentSettings['mat_id']) ? $currentSettings['mat_id'] : ''; ?>"><br>

                <label for="mat_number">MAT Number</label><br>
                <input type="text" name="mat_number" class="readonly-input readonly" value="<?php echo isset($currentSettings['mat_number']) ? $currentSettings['mat_number'] : ''; ?>"><br>

                <label for="clientName">Client Name</label><br>
                <input type="text" name="clientName" class="readonly-input readonly" value="<?php echo isset($currentSettings['clientName']) ? $currentSettings['clientName'] : ''; ?>"><br>

                </div>
                <div class="grid-item">
                <label for="nickName">Nick Name</label><br>
                <input type="text" name="nickName" value="<?php echo isset($currentSettings['nickName']) ? $currentSettings['nickName'] : ''; ?>"><br>

                <label for="dob">Date of Birth</label><br>
                <input type="date" name="dob" class="readonly-input readonly" value="<?php echo isset($currentSettings['dob']) ? $currentSettings['dob'] : ''; ?>"> <br>

                <label for="age">Age</label><br>
                <input type="text" name="age" class="readonly-input readonly" value="<?php echo isset($currentSettings['age']) ? $currentSettings['age'] : ''; ?>"> <br>

                <label for="sex">Gender</label><br>
                <input type="text" name="sex" class="readonly-input readonly" value="<?php echo isset($currentSettings['sex']) ? $currentSettings['sex'] : ''; ?>"> <br>

            </div>
            <div class="grid-item">

                <label for="p_address">Residence</label><br>
                <input type="text" name="p_address" value="<?php echo isset($currentSettings['p_address']) ? $currentSettings['p_address'] : ''; ?>"> <br>

                <label for="cso">CSO</label><br>
                <input type="text" name="cso" value="<?php echo isset($currentSettings['cso']) ? $currentSettings['cso'] : ''; ?>"> <br>

                <label>Drug</label><br>
                    <select name="drugname" class="form-control">
                        <?php
                        // Fetch drug names from the database
                        $sql = "SELECT drugname FROM drug";
                        $result = $mysqli->query($sql);

                        // Check if any rows were returned
                        if ($result->num_rows > 0) {
                            // Loop through the rows and display each drug name as an option
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='" . $row['drugname'] . "'>" . $row['drugname'] . "</option>";
                            }
                        } else {
                            // If no drugs are found in the database, display a default option
                            echo "<option value=''>No drugs found</option>";
                        }
                        ?>
                    </select> <br>


                <label for="dosage">Dosage</label><br>
                <input type="text" name="dosage" value="<?php echo isset($currentSettings['dosage']) ? $currentSettings['dosage'] : ''; ?>"> <br>

                <label for="current_status">Current Status</label><br>
                <input type="text" name="current_status" value="<?php echo isset($currentSettings['current_status']) ? $currentSettings['current_status'] : ''; ?>"> <br>

            </div>
            <div class="grid-item">
                <label for="pharm_officer_name">Dispensing Officer Name</label> <br>
                <input type="text" name="pharm_officer_name" value="Pharmacist" class="readonly-input readonly"> <br>

                <button type="submit" class="custom-btn-submit">Dispense</button> <br>
                <a href="dispensing.php" class="custom-btn-cancel">&lt;&lt;GoBack</a>  <br>
                <a href="../dashboard/admin_dashboard.php" class="custom-btn-home">Home</a>  <br>
            </div>
        </div>
    </form>
</body>
</html>
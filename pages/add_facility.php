<?php
// Include the database configuration file at the top
include '../includes/config.php';
include '../includes/header.php';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $facilityname = trim($_POST["facilityname"]);
        $mflcode = trim($_POST["mflcode"]);
        $countyname = trim($_POST["countyname"]);
        $subcountyname = trim($_POST["subcountyname"]);
        $owner = trim($_POST["owner"]);
        $sdp = trim($_POST["sdp"]);
        $agency = trim($_POST["agency"]);
        $emr = trim($_POST["emr"]);
        $emrstatus = trim($_POST["emrstatus"]);
        $infrastructuretype = trim($_POST["infrastructuretype"]);
        $latitude = trim($_POST["latitude"]);
        $longitude = trim($_POST["longitude"]);

        // Prepare an insert statement
        $sql = "INSERT INTO facilities (facilityname, mflcode, countyname, subcountyname, owner, sdp, emr, emrstatus, infrastructuretype, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        if ($stmt = $conn->prepare($sql)) {
                // Bind variables to the prepared statement as parameters
                $stmt->bind_param("sssssssssss", $facilityname, $mflcode, $countyname, $subcountyname, $owner, $sdp, $emr, $emrstatus, $infrastructuretype, $latitude, $longitude);

                // Execute the prepared statement
                if ($stmt->execute()) {
                        // Success message and redirect
                        echo '<div style="color: green; background-color: #DAF7A6; height: 50px; padding: 15px; margin-left: 40px; margin-top: 30px; font-size: 18px;">facility added successfully</div>';
                        echo '<script>
                                        setTimeout(function(){
                                                window.location.href = "add_facility.php";
                                        }, 3000);
                                    </script>';
                        exit();
                } else {
                        echo "Something went wrong. Please try again later.";
                }

                // Close statement
                $stmt->close();
        }

        // Close connection
        $conn->close();
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Add New Facility</title>
    <link rel="icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <style>
        /* CSS Variables for easy theme changes */
        :root {
            --primary-color: #0056b3; /* Darker blue for primary actions */
            --secondary-color: #6c757d; /* Grey for secondary elements */
            --background-light: #f8f9fa; /* Light background for overall page */
            --card-background: #ffffff; /* White for form background */
            --border-color: #dee2e6;
            --success-color: #28a745;
            --success-bg-color: #d4edda;
            --text-color: #343a40;
            --input-border: #ced4da;
            --input-focus-border: #80bdff;
            --shadow-light: rgba(0, 0, 0, 0.1);
            --font-family: 'Arial', sans-serif; /* Changed from Times New Roman for a modern look */
        }



        .main-content {
            padding: 20px;
            max-width: 1200px;
            margin: 20px auto; /* Center the main content */
            background-color: var(--card-background);
            border-radius: 8px;
            box-shadow: 0 4px 10px var(--shadow-light);
        }

        h3 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 30px;
            font-size: 1.8em;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
        }

        #success-message {
            background-color: var(--success-bg-color);
            color: var(--success-color);
            padding: 15px 20px;
            margin-bottom: 20px;
            border: 1px solid var(--success-color);
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        #success-message .fas {
            font-size: 1.2em;
        }


        form {
            display: grid;
            grid-template-columns: repeat(3, 1fr); /* Three equal columns */
            gap: 25px; /* Spacing between columns and rows */
            padding: 20px;
            background-color: #FFFFE0; /* Original light yellow background */
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 0 2px 5px var(--shadow-light);
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        label {
            margin-bottom: 8px;
            font-weight: bold;
            color: var(--text-color);
        }

        input[type="text"],
        input[type="number"],
        input[type="date"],
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--input-border);
            border-radius: 5px;
            font-size: 1em;
            box-sizing: border-box; /* Include padding in width */
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        input[type="date"]:focus,
        select:focus {
            border-color: var(--input-focus-border);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
            outline: none;
        }

        .readonly-input {
            background-color: #e9ecef; /* Light gray for readonly fields */
            cursor: not-allowed;
        }

        .custom-submit-btn {
            grid-column: 1 / -1; /* Make the button span all three columns */
            padding: 15px 25px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: bold;
            transition: background-color 0.3s ease, transform 0.2s ease;
            margin-top: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .custom-submit-btn:hover {
            background-color: #004085; /* Darker shade on hover */
            transform: translateY(-2px); /* Slight lift effect */
        }

        .custom-submit-btn:active {
            transform: translateY(0);
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            form {
                grid-template-columns: repeat(2, 1fr); /* Two columns on medium screens */
            }
            .custom-submit-btn {
                grid-column: 1 / -1; /* Still span full width */
            }
        }

        @media (max-width: 768px) {
            form {
                grid-template-columns: 1fr; /* Single column on small screens */
            }
        }
    </style>
</head>
<body>
    <div class="main-content">
        
        <h3>Add New Facility</h3>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">

                <div class="form-group">
                <label for="facilityname">Facility Name</label>
                <input type="text" name="facilityname" id="facilityname" >
                </div>
            <div class="form-group">
                <label for="mflcode">MFL Code</label>
                <input type="number" name="mflcode" id="mflcode" >
            </div>
            <div class="form-group">
                <label>County</label>
                <select name="countyname" class="form-control" required>
                    <option value="">Select County</option>
                    <?php
                    // Fetch sub-counties from the database
                    include '../includes/config.php';
                    $sql = "SELECT county_name FROM counties";
                    $result = $conn->query($sql);
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($row['county_name']) . "'>" . htmlspecialchars($row['county_name']) . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label>Sub-County</label>
                <select name="subcountyname" class="form-control" required>
                    <option value="">Select Sub-County</option>
                    <?php
                    // Fetch sub-counties from the database
                    include '../includes/config.php';
                    $sql = "SELECT sub_county_name FROM sub_counties";
                    $result = $conn->query($sql);
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($row['sub_county_name']) . "'>" . htmlspecialchars($row['sub_county_name']) . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="owner">Ownership</label>
                <input type="text" name="owner" id="owner" placeholder = "e.g. MOH, FBO etc">
            </div>

            <div class="form-group">
                <label for="sdp">Service Delivery Partner</label>
                <input type="text" name="sdp" id="sdp" placeholder = "e.g. Stawisha Pwani, ICRH etc" >
            </div>
            <div class="form-group">
                <label for="agency">Agency</label>
                <input type="text" name="agency" id="agency" placeholder = "e.g. CDC, USAID etc" >
            </div>

            <div class="form-group">
                <label for="emr">TaifaCare Type</label>
                <input type="text" name="emr" id="emr" placeholder = "e.g. KenyaEMR, AfyaKE, Tiberbu etc" >
            </div>

            <div class="form-group">
                <label for="emrstatus">TaifaCare Status</label>
                <input type="text" name="emrstatus" id="emrstatus" placeholder = "e.g. Active, standalone, Inactive etc" >
            </div>

            <div class="form-group">
                <label for="infrastructuretype">Infrastructure Type</label>
                <input type="text" name="infrastructuretype" id="infrastructuretype" placeholder = "e.g. Local, Cloud-based etc" >
            </div>

            <div class="form-group">
                <label for="latitude">Latitude</label>
                <input type="text" name="latitude">
            </div>

            <div class="form-group">
                <label for="longitude">Longitude</label>
                <input type="text" name="longitude" required>
            </div>

            <div class="form-group">

            <input type="submit" class='custom-submit-btn' name="submit" value="Add Facility">
            </div>
        </form>
    </div>


</body>
</html>


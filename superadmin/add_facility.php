<?php
// Include the database configuration file at the top
include '../includes/config.php';


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

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/edit_dispense.css" type="text/css">
    <link rel="stylesheet" href="../assets/css/forms.css" type="text/css">
    <style>
        /* CSS Variables for easy theme changes */

        .content-main {
            padding: 20px;
            width: 60%;
            margin: 20px auto; /* Center the main content */
            background-color: var(--card-background);
            border-radius: 8px;
            box-shadow: 0 4px 10px var(--shadow-light);
        }

        form {
            display: grid;
            grid-template-columns: repeat(3, 1fr); /* Three equal columns */
            gap: 25px; /* Spacing between columns and rows */
            padding: 20px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 0 2px 5px var(--shadow-light);
        }


    </style>
</head>
<body>
    <div class="content-main">

        <h2>Add New Facility</h2>
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


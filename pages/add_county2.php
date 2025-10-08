<?php
// Include the database configuration file
include '../includes/config.php';
include('../includes/footer.php');
include('../includes/header.php');

// Define variables and initialize with empty values
$countyCode = $countyName = $region = "";
$countyCode_err = $countyName_err = $region_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Validate county code
        $input_countyCode = trim($_POST["county_code"]);
        if (empty($input_countyCode)) {
                $countyCode_err = "Please enter the county code.";
        } else {
                $countyCode = $input_countyCode;
        }

        // Validate county name
        $input_countyName = trim($_POST["county_name"]);
        if (empty($input_countyName)) {
                $countyName_err = "Please enter the county name.";
        } else {
                $countyName = $input_countyName;
        }

        // Validate region
        $input_region = trim($_POST["region"]);
        if (empty($input_region)) {
                $region_err = "Please enter the region.";
        } else {
                $region = $input_region;
        }

        // Check input errors before inserting into database
        if (empty($countyCode_err) && empty($countyName_err) && empty($region_err)) {
                // Prepare an insert statement
                $sql = "INSERT INTO counties (county_code, county_name, region) VALUES (?, ?, ?)";

                if ($stmt = $conn->prepare($sql)) {
                        // Bind variables to the prepared statement as parameters
                        $stmt->bind_param("sss", $param_countyCode, $param_countyName, $param_region);

                        // Set parameters
                        $param_countyCode = $countyCode;
                        $param_countyName = $countyName;
                        $param_region = $region;

                        // Attempt to execute the prepared statement
                        if ($stmt->execute()) {
                                // Success message

                                echo '<div style="color: green; background-color:  #DAF7A6; height: 50px; padding: 15px; margin-left: 40px; margin-top: 30px; font-size: 18px;">County added successfully</div>';
                                // Redirect after 3 seconds
                                echo '<script>
                                                setTimeout(function(){
                                                        window.location.href = "add_county.php";
                                                }, 3000);
                                            </script>';
                        } else {
                                echo "Something went wrong. Please try again later.";
                        }

                        // Close statement
                        $stmt->close();
                }
        }

        // Close connection
        $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Add County</title>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
        <link rel="stylesheet" href="../includes/style.css" type="text/css">
</head>

<body>
        <div class="wrapper">
                <h2>Add New County</h2>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="form-group">
                                <label>County Code</label>
                                <input type="text" name="county_code" class="form-control <?php echo (!empty($countyCode_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $countyCode; ?>">
                                <span class="invalid-feedback"><?php echo $countyCode_err; ?></span>
                        </div>
                        <div class="form-group">
                                <label>County Name</label>
                                <input type="text" name="county_name" class="form-control <?php echo (!empty($countyName_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $countyName; ?>">
                                <span class="invalid-feedback"><?php echo $countyName_err; ?></span>
                        </div>
                        <div class="form-group">
                                <label>Region</label>
                                <input type="text" name="region" class="form-control <?php echo (!empty($region_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $region; ?>">
                                <span class="invalid-feedback"><?php echo $region_err; ?></span>
                        </div>
                        <input type="submit" class="btn btn-primary" value="Submit">
                        <a href="index.php" class="btn btn-secondary ml-2">Cancel</a>
                </form>
        </div>
</body>

</html>

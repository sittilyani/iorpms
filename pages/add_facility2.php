<?php
// Include the database configuration file at the top
include '../includes/config.php';
include '../includes/header.php';
include '../includes/footer.php';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $facility_name = trim($_POST["facility_name"]);
        $mfl_code = trim($_POST["mfl_code"]);
        $county_name = trim($_POST["county_name"]);
        $sub_county_name = trim($_POST["sub_county_name"]);

        // Prepare an insert statement
        $sql = "INSERT INTO counties (facility_name, mfl_code, county_name, sub_county_name) VALUES (?, ?, ?, ?)";

        if ($stmt = $conn->prepare($sql)) {
                // Bind variables to the prepared statement as parameters
                $stmt->bind_param("ssss", $facility_name, $mfl_code, $county_name, $sub_county_name);

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
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add facility</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../includes/style.css" type="text/css">
</head>

<body>
    <div class="wrapper">
        <h2>Add New facility</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Facility Name</label>
                <input type="text" name="facility_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>MFL Code</label>
                <input type="text" name="mfl_code" class="form-control" required>
            </div>
            <div class="form-group">
                <label>County Name</label>
                <input type="text" name="county_name" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Sub County Name</label>
                <input type="text" name="sub_county_name" class="form-control" required>
            </div>

            <input type="submit" class="btn btn-primary" value="Submit">
            <a href="index.php" class="btn btn-secondary ml-2">Cancel</a>
        </form>
    </div>
</body>

</html>


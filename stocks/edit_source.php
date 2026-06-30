<?php
// Include config file
require_once '../includes/config.php';
require_once '../includes/footer.php'; 

// Define variables and initialize with empty values
$source_name = "";
$source_name_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate source name
    $input_source_name = trim($_POST["source_name"]);
    if (empty($input_source_name)) {
        $source_name_err = "Please enter a source name.";
    } else {
        $source_name = $input_source_name;
    }

    // Check input errors before inserting into database
    if (empty($source_name_err)) {
        // Prepare an insert statement
        $sql = "INSERT INTO drug_sources (source_name) VALUES (?)";

        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("s", $param_source_name);

            // Set parameters
            $param_source_name = $source_name;

            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Redirect to source list page
                header("location: sources.php");
                exit();
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

// Define variables for edit and delete actions
$edit_link = "<a href='edit_source.php?id=%d' class='btn btn-primary'>Edit</a>";
$delete_link = "<a href='delete_source.php?id=%d' class='btn btn-danger'>Delete</a>";

// Fetch all sources from the database
$sql = "SELECT * FROM drug_sources";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drug Sources</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h2>Drug Sources</h2>
            <a href="add_source.php" class="btn btn-success mb-3">Add New Source</a>
            <table class="table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Source Name</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['source_id'] . "</td>";
                        echo "<td>" . $row['source_name'] . "</td>";
                        echo "<td>";
                        printf($edit_link, $row['source_id']);
                        echo "&nbsp;";
                        printf($delete_link, $row['source_id']);
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='3'>No sources found</td></tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>

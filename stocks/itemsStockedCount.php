<?php
// Establish connection to the MySQL database
include '../includes/footer.php'; 

// Execute the MySQL query to get the count
$sql = "SELECT
            COUNT(DISTINCT id) AS distinct_categoryIds
        FROM
            stocks
        WHERE id <>0 AND stockBalance > 0";
$result = $conn->query($sql);

// Fetch the count
$row = $result->fetch_assoc();
$distinct_categoryIds = $row['distinct_categoryIds'];


// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Items Stocked</title>
</head>
<body>
    <p><?php echo $distinct_categoryIds; ?></p>
</body>
</html>
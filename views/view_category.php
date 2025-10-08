<?php
include '../includes/config.php';
include '../includes/footer.php';
include '../includes/header.php';

// Retrieve category details for the specified category_id
if (isset($_GET['id'])) {
    $categoryId = $_GET['id'];

    $sql = "SELECT * FROM drugcategory WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $categoryId);
    $stmt->execute();
    $category = $stmt->get_result()->fetch_assoc();

    if (!$category) {
        header("Location: category_list.php");
        exit();
    }
} else {
    header("Location: category_list.php");
    exit();
}
?>

<!-- HTML structure to display category details -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Category</title>
    <link rel="icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <!-- Add your CSS styles or include Bootstrap here -->
</head>
<body>
    <h2>Category Details</h2>
    <div>
        <p><strong>Category ID:</strong> <?php echo $category['id']; ?></p>
        <p><strong>Category Name:</strong> <?php echo $category['catname']; ?></p>
        <p><strong>Description:</strong> <?php echo $category['description']; ?></p>
    </div>
    <!-- Add your HTML structure or additional elements as needed -->
</body>
</html>

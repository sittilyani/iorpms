<?php
include '../includes/config.php';
include ("../includes/header.php");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $categoryId = $_POST['category_id'];
    $newCategoryName = $_POST['new_category_name'];
    $newDescription = $_POST['new_description'];

    // Update category details in the database
    $sqlUpdateCategory = "UPDATE drugcategory SET catname = ?, description = ? WHERE id = ?";
    $stmtUpdateCategory = $conn->prepare($sqlUpdateCategory);
    $stmtUpdateCategory->bind_param('ssi', $newCategoryName, $newDescription, $categoryId);

    if ($stmtUpdateCategory->execute()) {
        echo "Category updated successfully.";
    } else {
        echo "Error updating category.";
    }
}

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

<!-- HTML form for updating category details -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Category</title>
    <link rel="icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <!-- Add your CSS styles or include Bootstrap here -->
</head>
<body>
    <h2>Update Category Details</h2>
    <form action="update_category.php?id=<?php echo $categoryId; ?>" method="post">
        <!-- Display existing category details in the form -->
        <div>
            <label for="new_category_name">Category Name:</label>
            <input type="text" id="new_category_name" name="new_category_name" value="<?php echo $category['catname']; ?>" required>
        </div>

        <div>
            <label for="new_description">Description:</label>
            <textarea id="new_description" name="new_description"><?php echo $category['description']; ?></textarea>
        </div>

        <input type="hidden" name="category_id" value="<?php echo $categoryId; ?>">

        <button type="submit" name="submit">Update Category</button>
    </form>
    <!-- Add your HTML structure or additional elements as needed -->
</body>
</html>

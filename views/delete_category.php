<?php
include '../includes/config.php';
include ("../includes/header.php");

if (isset($_GET['id'])) {
    $categoryId = $_GET['id'];

    // Delete category from the database
    $sqlDeleteCategory = "DELETE FROM drugcategory WHERE id = ?";
    $stmtDeleteCategory = $conn->prepare($sqlDeleteCategory);
    $stmtDeleteCategory->bind_param('i', $categoryId);

    if ($stmtDeleteCategory->execute()) {
        echo "Category deleted successfully.";
    } else {
        echo "Error deleting category.";
    }
} else {
    echo "Invalid request. Please provide a category ID.";
}
?>

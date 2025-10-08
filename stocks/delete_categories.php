<?php
include '../includes/config.php';
include '../includes/header.php';

if (isset($_GET['id'])) {
    $supplierId = intval($_GET['id']); // Correct variable and force it to an integer for safety

    // Delete supplier from the database
    $sqlDeleteCategory = "DELETE FROM categories WHERE id = ?";
    $stmtDeleteCategory = $conn->prepare($sqlDeleteCategory);

    if ($stmtDeleteCategory) {
        $stmtDeleteCategory->bind_param('i', $CategoryId);

        if ($stmtDeleteCategory->execute()) {
            $_SESSION['success_message'] = "Scategory deleted successfully!";
            echo '<script>
                setTimeout(function() {
                    window.location.href = "../views/view_categories.php";
                }, 3000);
            </script>';
            exit;
        } else {
            $_SESSION['error_message'] = "Error deleting Category: " . $stmtDeleteCategory->error;
        }
    } else {
        $_SESSION['error_message'] = "Error preparing delete statement: " . $conn->error;
    }
} else {
    $_SESSION['error_message'] = "No Category ID specified.";
    header("Location: ../views/view_categories.php");
    exit;
}
?>

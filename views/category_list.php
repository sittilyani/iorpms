<?php
include '../includes/config.php';
include '../includes/header.php';

// Read
$sql = "SELECT * FROM drugcategory";
$result = $conn->query($sql);

// Check for query success
if ($result) {
    $categories = $result->fetch_all(MYSQLI_ASSOC);
} else {
    // Handle query failure
    echo "Error fetching categories: " . $conn->error;
    $categories = array(); // Set empty array to avoid issues in the loop
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient List</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <style>
        button {
        background-color: green;
        color: white;
        border: none;
        padding: 10px;
        cursor: pointer;
        transition: background-color 0.3s; /* Add a smooth transition effect */
        border-radius: 5px;
        margin-bottom: 10px;
        }

        button:hover {
            background-color: blue;
        }
        .container {
            margin-left: 30px;

        }

        .patient-table th, .patient-table td {
            width: 100px;
            padding: none;
        }

        .patient-table th:last-child, .patient-table td:last-child {
            width: 200px;
        }

    </style>
</head>
<body>

<div class="container mt-5">
    <h2>Category List</h2>


    <a href="../processors/addcategory.php">
        <button>Add New Category</button>
    </a>

    <!-- Patient Table -->
    <table class="table table-bordered">
        <thead>
        <tr>

            <th>Cat ID</th>
            <th>Category Name</th>
            <th>Description</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($categories as $category): ?>
            <tr>
                <td><?= $category['id']; ?></td>
                <td><?= $category['catname']; ?></td>
                <td><?= $category['description']; ?></td>
                <td>
                    <!-- Add buttons for update, view, and delete with appropriate links -->
                    <a href="update_category.php?id=<?= $category['id']; ?>" class="btn btn-primary btn-sm">Update</a>
                    <a href="view_category.php?id=<?= $category['id']; ?>" class="btn btn-info btn-sm">View</a>
                    <a href="delete_category.php?id=<?= $category['id']; ?>" class="btn btn-danger btn-sm">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
 <script>
    $(document).ready(function () {
        $('#search').on('input', function () {
            var searchTerm = $(this).val();
            $.ajax({
                url: 'search_category.php', // Change this to your search script
                type: 'GET',
                data: {search: searchTerm},
                success: function (response) {
                    $('.category-table tbody').html(response);
                },
                error: function () {
                    console.log('Error during AJAX request');
                }
            });
        });
    });
</script>
</body>
</html>

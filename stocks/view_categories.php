<?php
include '../includes/config.php';
include '../includes/header.php';
// Move footer.php include to the end to avoid premature output

$sql = "SELECT * FROM categories";
$result = $conn->query($sql);
$categories = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View categories</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/DataTables/datatables.min.css">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <style>

        button {
            background-color: #920000;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 14px;
            font-size: 14px;
            cursor: pointer;
            margin-bottom: 15px;
        }
        button a {
            color: white;
            text-decoration: none;
        }
        table {
            width: 50%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #dee2e6;
            padding: 6px 10px;
            font-size: 14px;
            text-align: left;
        }
        th {
            background-color: #920000;
            color: white;
            height: 50px;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        .action-buttons button {
            padding: 5px 10px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .btn-update {
            background-color: #17a2b8;
        }
        .btn-delete {
            background-color: #dc3545;
        }
        .btn-view {
            background-color: #28a745;
        }
        .error, .success {
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        @media screen and (max-width: 768px) {
            table, thead, tbody, th, td, tr {
                display: block;
            }
            thead {
                display: none;
            }
            tr {
                margin-bottom: 10px;
                background-color: #fff;
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 10px;
            }
            td {
                border: none;
                padding: 6px 10px;
                position: relative;
            }
            td::before {
                content: attr(data-label);
                font-weight: bold;
                display: block;
                color: #6c757d;
            }
            .action-buttons {
                flex-direction: column;
                gap: 8px;
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>
<div class="main-content">
    <div class="categories">

        <button><a href="../stocks/categories.php">+ Add New Category</a></button>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Category Name</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $user) : ?>
                    <tr>
                        <td data-label="ID"><?= htmlspecialchars($user['id']) ?></td>
                        <td data-label="Category Name"><?= htmlspecialchars($user['name']) ?></td>
                        <td data-label="Description"><?= htmlspecialchars($user['description']) ?></td>
                        <td data-label="Actions">
                            <div class="action-buttons">
                                <button class="btn-update" onclick="location.href='../stocks/update_categories.php?id=<?= $user['id'] ?>'">
                                    Update
                                </button>
                                <button class="btn-delete" onclick="if(confirm('Are you sure you want to delete this user?')) location.href='../stocks/delete_categories.php?id=<?= $user['id'] ?>'">
                                    Delete
                                </button>
                                <button class="btn-view" onclick="location.href='../stocks/view_categories.php?id=<?= $user['id'] ?>'">
                                    View
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>

</body>
</html>
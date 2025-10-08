<?php
include '../includes/config.php';
include '../includes/header.php';
// Move footer.php include to the end to avoid premature output

$sql = "SELECT * FROM stocks";
$result = $conn->query($sql);
$users = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users List</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/DataTables/datatables.min.css">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            background-image: url('../assets/images/background.jpg'); /* Replace with your image path */
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
        }
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5); /* Semi-transparent overlay */
            z-index: 1;
        }
        .main-content {
            position: relative;
            z-index: 2;
        }
        .users {
            width: 90%;
            margin: auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.05);
        }
        button {
            background-color: #00246B;
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
            width: 100%;
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
            background-color: #00246B;
            color: white;
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
    <div class="users">
        <?php if (isset($_GET['error']) && $_GET['error'] == 'user_id_missing'): ?>
            <div class="error">Error: User ID is missing. Please select a user to edit.</div>
        <?php elseif (isset($_GET['success']) && $_GET['success'] == 'user_updated'): ?>
            <div class="success">User updated successfully.</div>
        <?php endif; ?>

        <button><a href="../Login/user_registration.php">+ Add New User</a></button>
        <table>
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>User Name</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>Sex</th>
                    <th>Mobile</th>
                    <th>User Role</th>
                    <th>Date Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user) : ?>
                    <tr>
                        <td data-label="User ID"><?= htmlspecialchars($user['user_id']) ?></td>
                        <td data-label="User Name"><?= htmlspecialchars($user['username']) ?></td>
                        <td data-label="First Name"><?= htmlspecialchars($user['first_name']) ?></td>
                        <td data-label="Last Name"><?= htmlspecialchars($user['last_name']) ?></td>
                        <td data-label="Email"><?= htmlspecialchars($user['email']) ?></td>
                        <td data-label="Sex"><?= htmlspecialchars($user['sex']) ?></td>
                        <td data-label="Mobile"><?= htmlspecialchars($user['mobile']) ?></td>
                        <td data-label="User Role"><?= htmlspecialchars($user['userrole']) ?></td>
                        <td data-label="Date Created"><?= date('M d, Y', strtotime($user['date_created'])) ?></td>
                        <td data-label="Actions">
                            <div class="action-buttons">
                                <button class="btn-update" onclick="location.href='update_user.php?user_id=<?= $user['user_id'] ?>'">
                                    Update
                                </button>
                                <button class="btn-delete" onclick="if(confirm('Are you sure you want to delete this user?')) location.href='delete_user.php?user_id=<?= $user['user_id'] ?>'">
                                    Delete
                                </button>
                                <button class="btn-view" onclick="location.href='view_user.php?user_id=<?= $user['user_id'] ?>'">
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
<?php include '../includes/footer.php'; ?>
</body>
</html>
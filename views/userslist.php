<?php
 session_start();

    // Check if the user is not logged in, redirect to login page
    if (!isset($_SESSION['user_id'])) {
        header("location: ../public/index.php");
        exit;
    }
include '../includes/config.php';
include '../includes/footer.php';
include '../includes/header.php';

// Fetch user data from the database
$sql = "SELECT * FROM tblusers";
$result = $conn->query($sql);
$users = $result->fetch_all(MYSQLI_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users List</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <script src="../assets/js/bootstrap.bundle.min.js"></script>

    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --danger-color: #e74c3c;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --border-color: #dee2e6;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #495057;
            padding: 20px;
        }

        .users {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
            padding: 30px;
            overflow: hidden;
        }

        .users h2 {
            color: var(--dark-color);
            font-weight: 700;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
        }

        .users h2 i {
            margin-right: 10px;
            color: var(--primary-color);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 0.9rem;
        }

        thead {
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }

        .btn {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-right: 5px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn i {
            margin-right: 5px;
            font-size: 0.8rem;
        }

        .btn-update {
            background-color: var(--warning-color);
            color: white;
            border: none;
        }

        .btn-update:hover {
            background-color: #e67e22;
            transform: translateY(-1px);
        }

        .btn-delete {
            background-color: var(--danger-color);
            color: white;
            border: none;
        }

        .btn-delete:hover {
            background-color: #c0392b;
            transform: translateY(-1px);
        }

        .btn-view {
            background-color: var(--success-color);
            color: white;
            border: none;
        }

        .btn-view:hover {
            background-color: #27ae60;
            transform: translateY(-1px);
        }

        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        /* Responsive table */
        @media (max-width: 768px) {
            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }

            .btn {
                margin-bottom: 5px;
            }

            .action-buttons {
                flex-direction: column;
            }
        }

        /* Status indicators */
        .status-active {
            color: var(--success-color);
            font-weight: 600;
        }

        .status-inactive {
            color: var(--danger-color);
            font-weight: 600;
        }

        /* Add user button */
        .add-user-btn {
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            margin-bottom: 20px;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .add-user-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
            color: white;
        }

        .add-user-btn i {
            margin-right: 8px;
        }

        /* Table row animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        tbody tr {
            animation: fadeIn 0.3s ease forwards;
        }

        tbody tr:nth-child(odd) {
            background-color: rgba(0, 0, 0, 0.01);
        }
    </style>
</head>
<body>
    <div class="users">
        <h2><i class="fas fa-users"></i> User List</h2>

        <a href="../public/user_registration.php" class="add-user-btn">
            <i class="fas fa-user-plus"></i> Add New User
        </a>

        <table class="table-responsive">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>Gender</th>
                    <th>Mobile</th>
                    <th>User Role</th>
                    <th>Date Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                        <td><?php echo htmlspecialchars($user['first_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['gender']); ?></td>
                        <td><?php echo htmlspecialchars($user['mobile']); ?></td>
                        <td><?php echo htmlspecialchars($user['userrole']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($user['date_created'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-update" onclick="location.href='../processors/update_user.php?id=<?php echo $user['user_id']; ?>'">
                                    <i class="fas fa-edit"></i> Update
                                </button>
                                <button class="btn btn-delete" onclick="if(confirm('Are you sure you want to delete this user?')) location.href='../processors/delete_user.php?id=<?php echo $user['user_id']; ?>'">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </button>
                                <button class="btn btn-view" onclick="location.href='view_user.php?id=<?php echo $user['user_id']; ?>'">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>


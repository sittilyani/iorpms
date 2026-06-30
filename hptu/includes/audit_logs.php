<?php
session_start();
include '../includes/config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['userrole'] !== 'Admin') {
    header("Location: ../public/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include '../includes/header.php'; ?>

<div class="container mt-5">
    <div class="card">
        <div class="card-header bg-danger text-white">
            <h5><i class="fas fa-history"></i> System Audit Logs</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Details</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT al.*, u.full_name FROM audit_logs al
                                JOIN tblusers u ON al.user_id = u.user_id
                                ORDER BY al.created_at DESC LIMIT 100";
                        $result = $conn->query($sql);
                        while ($log = $result->fetch_assoc()): ?>
                        <tr>
                            <td><small><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></small></td>
                            <td><strong><?= htmlspecialchars($log['full_name']) ?></strong></td>
                            <td><span class="badge bg-primary"><?= $log['action'] ?></span></td>
                            <td><small><?= htmlspecialchars($log['details']) ?></small></td>
                            <td><code><?= $log['ip_address'] ?></code></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
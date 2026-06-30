<?php
session_start();
include '../includes/config.php';
// ... existing code ...

// Check the logged-in user's role
$loggedInUserRole = $_SESSION['userrole'] ?? '';

// Check if a user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit;
}

$message = '';
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $message = "Prescription saved successfully!";
}

// Fetch prescriptions from the 'other_prescriptions' table
$sql = "SELECT * FROM other_prescriptions WHERE prescr_status = 'submitted' ORDER BY prescription_date DESC";
$result = $conn->query($sql);

$prescriptions = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $prescriptions[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Document</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="../assets/css/tables.css" type="text/css">
    <style>
          .close-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 4px;
            margin: 10px;
        }

        .close-btn:hover {
            background-color: #c82333;
        }

        .close-btn:active {
            transform: scale(0.98);
        }


    </style>
</head>
<body>
    <div class="content-main">
        <button
            onclick="window.history.back()"
            style="background: red; border: none; color: #ffffff; text-decoration: none; cursor: pointer; font-size: 16px; padding: 5px;
            "> ← Go Back
        </button>
        <table class="table">
            <thead>
                <tr>
                    <th>Prescription ID</th>
                    <th>Client Name</th>
                    <th>MAT ID</th>
                    <th>Date</th>
                    <th>Prescriber</th>
                    <th>Status</th>
                    <th style='text-wrap: nowrap;'>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($prescriptions)): ?>
                    <?php foreach ($prescriptions as $prescription): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($prescription['prescription_id']); ?></td>
                            <td><?php echo htmlspecialchars($prescription['clientName']); ?></td>
                            <td><?php echo htmlspecialchars($prescription['mat_id']); ?></td>
                            <td><?php echo htmlspecialchars($prescription['prescription_date']); ?></td>
                            <td><?php echo htmlspecialchars($prescription['prescriber_name']); ?></td>
                            <td><?php echo htmlspecialchars($prescription['prescr_status']); ?></td>
                            <td>
                                <a href="view_prescription_details.php?id=<?php echo htmlspecialchars($prescription['prescription_id']); ?>" class="btn btn-info btn-sm">View details</a>
                                <a href="dispense_prescription.php?id=<?php echo htmlspecialchars($prescription['prescription_id']); ?>" class="btn btn-primary btn-sm">&#62;&#62;Next</a>

                                <?php if ($loggedInUserRole === 'Admin' || $loggedInUserRole === 'Pharmacist'): ?>
                                    <a href="delete_prescription.php?id=<?php echo htmlspecialchars($prescription['prescription_id']); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this prescription? This action cannot be undone.');">Delete</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">No prescriptions found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <!-- Close button -->
    <button class="close-btn" onclick="closePage()">Close Page</button>

    </div>
    <script>
       function closePage() {
            // Try to close the window/tab
            window.close();

            // Fallback: If window.close() doesn't work (security restrictions),
            // redirect to a blank page or show a message
            setTimeout(function() {
                if (!window.closed) {
                    // Option 1: Redirect to blank page
                    window.location.href = 'about:blank';

                    // Option 2: Alternative - redirect to another page
                    // window.location.href = 'your-homepage.php';

                    // Option 3: Show message if can't close
                    // alert('Please close this tab manually');
                }
            }, 100);
        }
    </script>
</body>
</html>
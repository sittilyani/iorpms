<?php
session_start();
include '../includes/config.php';

// Check if file parameter is provided
if (!isset($_GET['file']) || empty($_GET['file'])) {
    header("Location: view_backups.php?error=" . urlencode("No file specified for deletion."));
    exit();
}

$fileToDelete = $_GET['file'];

// Define backups directory
$backupsDir = realpath(dirname(__DIR__) . '/backup/database');

if (!$backupsDir || !is_dir($backupsDir)) {
    header("Location: view_backups.php?error=" . urlencode("Backups directory not found."));
    exit();
}

// Validate and sanitize the filename
$filePath = realpath($backupsDir . '/' . basename($fileToDelete));

// Security checks
if (!$filePath || strpos($filePath, $backupsDir) !== 0) {
    header("Location: view_backups.php?error=" . urlencode("Invalid file path."));
    exit();
}

// Check if file exists
if (!file_exists($filePath)) {
    header("Location: view_backups.php?error=" . urlencode("File does not exist."));
    exit();
}

// Check if it's actually a SQL file
if (pathinfo($filePath, PATHINFO_EXTENSION) !== 'sql') {
    header("Location: view_backups.php?error=" . urlencode("Invalid file type. Only SQL backups can be deleted."));
    exit();
}

// Confirmation step - check if user confirmed deletion
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    // Show confirmation page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Confirm Deletion</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
        <style>
            .confirmation-box {
                max-width: 600px;
                margin: 100px auto;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                background-color: #fff;
            }
            .warning-icon {
                font-size: 60px;
                color: #dc3545;
                margin-bottom: 20px;
            }
        </style>
    </head>
    <body class="bg-light">
        <div class="confirmation-box">
            <div class="text-center">
                <i class="bi bi-exclamation-triangle-fill warning-icon"></i>
                <h3>Confirm Deletion</h3>
                <p class="mt-3">Are you sure you want to delete this backup?</p>
                <p class="text-muted"><strong><?php echo htmlspecialchars(basename($fileToDelete)); ?></strong></p>
                <p class="text-danger"><small><i class="bi bi-info-circle"></i> This action cannot be undone!</small></p>
            </div>
            <div class="d-flex justify-content-center gap-3 mt-4">
                <a href="delete_backup.php?file=<?php echo urlencode($fileToDelete); ?>&confirm=yes"
                   class="btn btn-danger">
                    <i class="bi bi-trash"></i> Yes, Delete
                </a>
                <a href="view_backups.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// User confirmed deletion - proceed to delete the file
if (unlink($filePath)) {
    header("Location: view_backups.php?message=" . urlencode("Backup deleted successfully: " . basename($fileToDelete)));
} else {
    header("Location: view_backups.php?error=" . urlencode("Failed to delete backup file."));
}
exit();
?>
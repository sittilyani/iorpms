<?php
ob_start(); // Add this at the VERY TOP - before ANYTHING else

session_start();
include '../includes/config.php';

// Define backups directory
$backupsDir = realpath(dirname(__DIR__) . '/backup/database');
if (!$backupsDir || !is_dir($backupsDir)) {
    $error = "Backups directory not found. Please create the directory: " . dirname(__DIR__) . '/backup/database';
}

// Process date filter parameters
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$filterApplied = !empty($startDate) || !empty($endDate);

// Collect SQL backups
$backupFiles = [];
$totalSize = 0;
if (!isset($error)) {
    $files = scandir($backupsDir);
    foreach ($files as $file) {
        // Filter for SQL backups and prevent directory traversal
        if (pathinfo($file, PATHINFO_EXTENSION) === 'sql' && $file !== '.' && $file !== '..') {
            $filePath = $backupsDir . '/' . $file;
            $fileSize = filesize($filePath);
            $totalSize += $fileSize;

            // Extract the filename without extension
            $filename = pathinfo($file, PATHINFO_FILENAME);

            // Expected format: databasename_dd-mm-YYYY-HH-ii-ss
            $lastUnderscorePos = strrpos($filename, '_');

            if ($lastUnderscorePos !== false) {
                // Split into database name and datetime string
                $dbName = substr($filename, 0, $lastUnderscorePos);
                $dateTimeString = substr($filename, $lastUnderscorePos + 1);

                // Parse timestamp from the datetime string (dd-mm-YYYY-HH-ii-ss)
                $timestamp = DateTime::createFromFormat('d-m-Y-H-i-s', $dateTimeString);

                if ($timestamp !== false) {
                    $timestamp = $timestamp->getTimestamp();
                    $backupDate = date('d-m-Y', $timestamp);
                    $backupTime = date('H:i:s', $timestamp);
                    $backupDateTime = date('d-m-Y H:i:s', $timestamp);

                    // Apply date filter if set
                    $includeBackup = true;
                    if ($filterApplied) {
                        $backupDateForFilter = date('Y-m-d', $timestamp);

                        if (!empty($startDate) && $backupDateForFilter < $startDate) {
                            $includeBackup = false;
                        }
                        if (!empty($endDate) && $backupDateForFilter > $endDate) {
                            $includeBackup = false;
                        }
                    }

                    if ($includeBackup) {
                        $backupFiles[] = [
                            'filename' => $file,
                            'dbname' => $dbName,
                            'timestamp' => $timestamp,
                            'date' => $backupDate,
                            'time' => $backupTime,
                            'datetime' => $backupDateTime,
                            'size' => $fileSize,
                            'size_formatted' => formatFileSize($fileSize),
                            'path' => '../backup/database/' . rawurlencode($file)
                        ];
                    }
                }
            }
        }
    }

    // Sort backups by timestamp (newest first)
    usort($backupFiles, function($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });
}

// Handle delete action
if (isset($_GET['delete']) && !empty($_GET['file'])) {
    $fileToDelete = basename($_GET['file']);
    $filePath = $backupsDir . '/' . $fileToDelete;

    if (file_exists($filePath) && strpos(realpath($filePath), $backupsDir) === 0) {
        if (unlink($filePath)) {
            $message = "Backup file deleted successfully.";
        } else {
            $error = "Failed to delete backup file.";
        }
    } else {
        $error = "Backup file not found.";
    }

    // Clean output buffer before redirect
    ob_end_clean();
    header("Location: view_backups.php" . ($filterApplied ? "?start_date=$startDate&end_date=$endDate" : ""));
    exit();
}

// Handle email action
if (isset($_GET['email']) && isset($_GET['file'])) {
    $fileToEmail = basename($_GET['file']);
    $email = 'sittilyani@gmail.com';

    $filePath = $backupsDir . '/' . $fileToEmail;
    if (file_exists($filePath) && strpos(realpath($filePath), $backupsDir) === 0) {
        $message = "Backup file '" . htmlspecialchars($fileToEmail) . "' has been sent to " . $email;
    } else {
        $error = "Invalid file specified for email.";
    }

    // Clean output buffer before redirect
    ob_end_clean();
    header("Location: view_backups.php" . ($filterApplied ? "?start_date=$startDate&end_date=$endDate" : "") . (isset($message) ? "&message=" . urlencode($message) : ""));
    exit();
}

// Helper function to format file size
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        return $bytes . ' bytes';
    } elseif ($bytes == 1) {
        return '1 byte';
    } else {
        return '0 bytes';
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Backups</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* Your existing styles */
        body {
            background: #f4f7fc;
            font-family: 'Inter', -apple-system, sans-serif;
        }
        .main-content {
            padding: 20px 25px;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .page-header h2 {
            font-size: 28px;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
        }
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: #e6f0ff;
            color: #4361ee;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .stat-content h3 {
            font-size: 14px;
            font-weight: 500;
            color: #64748b;
            margin: 0 0 5px;
        }
        .stat-content .value {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
        }
        .filter-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }
        .filter-title {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 15px;
        }
        .backups-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            overflow-x: auto;
        }
        .backup-table {
            width: 100%;
            border-collapse: collapse;
        }
        .backup-table th {
            text-align: left;
            padding: 15px 10px;
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            border-bottom: 2px solid #e2e8f0;
            background: #f8fafc;
        }
        .backup-table td {
            padding: 12px 10px;
            font-size: 13px;
            color: #1e293b;
            border-bottom: 1px solid #f1f5f9;
        }
        .backup-table tr:hover td {
            background: #f8fafc;
        }
        .db-badge {
            background: #e6f0ff;
            color: #4361ee;
            padding: 5px 10px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .file-size {
            font-family: monospace;
            color: #475569;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        .btn-action {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }
        .btn-download {
            background: #e6f0ff;
            color: #4361ee;
            border: 1px solid #4361ee20;
        }
        .btn-download:hover {
            background: #4361ee;
            color: white;
        }
        .btn-email {
            background: #fff4e6;
            color: #f39c12;
            border: 1px solid #f39c1220;
        }
        .btn-email:hover {
            background: #f39c12;
            color: white;
        }
        .btn-delete {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #b91c1c20;
        }
        .btn-delete:hover {
            background: #b91c1c;
            color: white;
        }
        .alert {
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 25px;
            border: none;
        }
        .alert-success {
            background: #d1fae5;
            color: #059669;
        }
        .alert-danger {
            background: #fee2e2;
            color: #b91c1c;
        }
        .alert-info {
            background: #e6f0ff;
            color: #4361ee;
        }
        .btn-primary {
            background: #4361ee;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 500;
        }
        .btn-primary:hover {
            background: #3f37c9;
        }
        .btn-secondary {
            background: #f1f5f9;
            border: none;
            color: #475569;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 500;
        }
        .btn-secondary:hover {
            background: #e2e8f0;
            color: #1e293b;
        }
        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }
            .filter-container .row > div {
                margin-bottom: 10px;
            }
            .action-buttons {
                flex-direction: column;
            }
            .btn-action {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
<div class="main-content">
    <div class="page-header">
        <h2><i class="bi bi-database" style="margin-right: 10px; color: #4361ee;"></i> Database Backups</h2>
        <a href="backup.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Create New Backup
        </a>
    </div>

    <!-- Stats Cards -->
    <?php if (!isset($error) && !empty($backupFiles)): ?>
    <div class="stats-cards">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="bi bi-files"></i>
            </div>
            <div class="stat-content">
                <h3>Total Backups</h3>
                <div class="value"><?php echo count($backupFiles); ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="bi bi-hdd-stack"></i>
            </div>
            <div class="stat-content">
                <h3>Total Size</h3>
                <div class="value"><?php echo formatFileSize($totalSize); ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="bi bi-calendar-check"></i>
            </div>
            <div class="stat-content">
                <h3>Latest Backup</h3>
                <div class="value"><?php echo !empty($backupFiles) ? date('d M', $backupFiles[0]['timestamp']) : 'N/A'; ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="bi bi-database"></i>
            </div>
            <div class="stat-content">
                <h3>Databases</h3>
                <div class="value"><?php echo count(array_unique(array_column($backupFiles, 'dbname'))); ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Messages -->
    <?php if (isset($_GET['message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?php echo htmlspecialchars(urldecode($_GET['message'])); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Date Range Filter -->
    <div class="filter-container">
        <div class="filter-title">
            <i class="bi bi-funnel me-2"></i> Filter Backups by Date Range
        </div>
        <form method="GET" action="">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="start_date" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date"
                           value="<?php echo htmlspecialchars($startDate); ?>">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date"
                           value="<?php echo htmlspecialchars($endDate); ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div>
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-filter"></i> Apply Filter
                        </button>
                        <a href="?" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Reset
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Backups Table -->
    <div class="backups-container">
        <?php if (isset($error)): ?>
            <div class="text-center py-5">
                <i class="bi bi-exclamation-circle" style="font-size: 48px; color: #b91c1c;"></i>
                <p class="mt-3 text-danger"><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php elseif (empty($backupFiles)): ?>
            <div class="text-center py-5">
                <i class="bi bi-archive" style="font-size: 48px; color: #94a3b8;"></i>
                <p class="mt-3 text-muted">
                    <?php if ($filterApplied): ?>
                        No backups found for the selected date range.
                    <?php else: ?>
                        No backups found. Click "Create New Backup" to get started.
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <table class="backup-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Database Name</th>
                        <th>Backup Date & Time</th>
                        <th>File Size</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backupFiles as $index => $backup): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td>
                                <span class="db-badge">
                                    <i class="bi bi-database"></i>
                                    <?php echo htmlspecialchars($backup['dbname']); ?>
                                </span>
                            </td>
                            <td>
                                <i class="bi bi-calendar3 me-1"></i> <?php echo $backup['date']; ?>
                                <i class="bi bi-clock ms-2 me-1"></i> <?php echo $backup['time']; ?>
                            </td>
                            <td>
                                <span class="file-size">
                                    <i class="bi bi-file-earmark"></i>
                                    <?php echo $backup['size_formatted']; ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="<?php echo $backup['path']; ?>" class="btn-action btn-download" download>
                                        <i class="bi bi-download"></i> Download
                                    </a>
                                    <a href="?email=1&file=<?php echo urlencode($backup['filename']); ?><?php echo $filterApplied ? "&start_date=$startDate&end_date=$endDate" : ""; ?>"
                                       class="btn-action btn-email"
                                       onclick="return confirm('Send this backup to sittilyani@gmail.com?')">
                                        <i class="bi bi-envelope"></i> Email
                                    </a>
                                    <a href="?delete=1&file=<?php echo urlencode($backup['filename']); ?><?php echo $filterApplied ? "&start_date=$startDate&end_date=$endDate" : ""; ?>"
                                       class="btn-action btn-delete"
                                       onclick="return confirm('Are you sure you want to delete this backup?')">
                                        <i class="bi bi-trash"></i> Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const startDate = document.getElementById('start_date');
        const endDate = document.getElementById('end_date');

        function validateDates() {
            if (startDate.value && endDate.value) {
                if (startDate.value > endDate.value) {
                    endDate.value = startDate.value;
                }
            }
        }

        startDate.addEventListener('change', function() {
            endDate.min = this.value;
            validateDates();
        });

        endDate.addEventListener('change', validateDates);
    });
</script>
</body>
</html>
<?php ob_end_flush(); // Flush the output buffer at the end ?>
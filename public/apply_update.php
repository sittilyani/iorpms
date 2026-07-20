<?php
/**
 * apply_update.php — Web-based Offline Update Processor
 * ====================================================
 * Reads and applies updates from the updates/ directory.
 * Returns JSON response.
 */
session_start();
header('Content-Type: application/json');

// Check if user is logged in (optional restriction, but since it's local development/admin, allow it)
include_once '../includes/config.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit;
}

$sqlFile = '../updates/update.sql';
$filesDir = '../updates/files';

$dbUpdated = false;
$filesUpdated = false;
$messages = [];

// 1. Process Database SQL Updates
if (file_exists($sqlFile)) {
    $sqlContent = file_get_contents($sqlFile);
    
    // Split SQL content by semicolon, filter out empty queries
    // A regex is used to avoid splitting semicolons inside string literals
    $queries = preg_split("/;(?=(?:[^']*'[^']*')*[^']*$)/", $sqlContent);
    
    $conn->begin_transaction();
    $queriesRun = 0;
    $success = true;
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (empty($query)) continue;
        
        if (!$conn->query($query)) {
            $success = false;
            $messages[] = "SQL error on query: " . substr($query, 0, 100) . "... Error: " . $conn->error;
            break;
        }
        $queriesRun++;
    }
    
    if ($success) {
        $conn->commit();
        $dbUpdated = true;
        @unlink($sqlFile);
        $messages[] = "Database updated successfully ($queriesRun queries executed).";
    } else {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Database update failed: ' . implode(" | ", $messages)]);
        exit;
    }
}

// Helper functions for file replacement
function copyFolderRecursive($src, $dst) {
    if (!is_dir($src)) return false;
    if (!is_dir($dst)) {
        @mkdir($dst, 0777, true);
    }
    $dir = opendir($src);
    while (false !== ($file = readdir($dir))) {
        if ($file != '.' && $file != '..') {
            $srcPath = $src . '/' . $file;
            $dstPath = $dst . '/' . $file;
            if (is_dir($srcPath)) {
                copyFolderRecursive($srcPath, $dstPath);
            } else {
                @copy($srcPath, $dstPath);
            }
        }
    }
    closedir($dir);
    return true;
}

function deleteFolderRecursive($dir) {
    if (!is_dir($dir)) return false;
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            deleteFolderRecursive($path);
        } else {
            @unlink($path);
        }
    }
    return @rmdir($dir);
}

// 2. Process File System Updates
if (is_dir($filesDir)) {
    if (copyFolderRecursive($filesDir, '..')) {
        $filesUpdated = true;
        deleteFolderRecursive($filesDir);
        $messages[] = "File updates applied successfully.";
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to copy updated files. Check directory permissions.']);
        exit;
    }
}

if ($dbUpdated || $filesUpdated) {
    echo json_encode([
        'status' => 'success',
        'message' => implode("\n", $messages)
    ]);
} else {
    echo json_encode([
        'status' => 'info',
        'message' => 'No updates were found to apply.'
    ]);
}

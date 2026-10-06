<?php
/** Send-to-Drive endpoint for this project. Shared logic: _shared/gdrive_backup/lib.php */
$GDRIVE_PROJECT = 'iorpms';

ob_start();
require_once __DIR__ . '/../includes/config.php';

$gdriveLib = null;
foreach ([
    __DIR__ . '/../../_shared/gdrive_backup/lib.php',
    __DIR__ . '/../../../_shared/gdrive_backup/lib.php',
    ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/../_shared/gdrive_backup/lib.php',
] as $candidate) {
    if (is_file($candidate)) { $gdriveLib = $candidate; break; }
}
if (!$gdriveLib) {
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json');
    http_response_code(500);
    exit(json_encode(['status' => 'error', 'message' => 'Shared Google Drive library (_shared/gdrive_backup) not found.']));
}
require_once $gdriveLib;

gdrive_handle_request([
    'project'    => $GDRIVE_PROJECT,
    'conn'       => $conn,
    'backup_dir' => __DIR__ . '/database',
]);

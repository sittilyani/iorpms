<?php
/**
 * pharmacy/pump_devices.php
 * =========================
 * Manage pump devices: add, edit host IP, direction (forward/reverse), port,
 * and API secret for dual-dispensing client machines.
 *
 * Requires the pump_devices_migration.sql to have been applied first.
 */
session_start();
include '../includes/config.php';
include '../includes/languages.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../public/login.php');
    exit;
}

$success = '';
$error   = '';

// ── Handle form submissions ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $id          = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $label       = trim($_POST['label']       ?? '');
        $port        = strtoupper(trim($_POST['port'] ?? ''));
        $pump_host   = trim($_POST['pump_host']   ?? 'localhost') ?: 'localhost';
        $is_reversed = isset($_POST['is_reversed']) ? 1 : 0;
        $api_secret  = trim($_POST['api_secret']  ?? '');

        if (!$label || !$port) {
            $error = 'Label and Port are required.';
        } elseif (!preg_match('/^COM\d{1,3}$/i', $port)) {
            $error = 'Port must be in format COM3, COM6, etc.';
        } else {
            if ($action === 'add') {
                $stmt = $conn->prepare(
                    "INSERT INTO pump_devices (label, port, pump_host, is_reversed, api_secret)
                     VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->bind_param('sssss', $label, $port, $pump_host, $is_reversed, $api_secret);
                // Note: is_reversed is int but using 's' bind still works in MySQL
            } else {
                $stmt = $conn->prepare(
                    "UPDATE pump_devices SET label=?, port=?, pump_host=?, is_reversed=?, api_secret=?
                     WHERE id=?"
                );
                $stmt->bind_param('sssssi', $label, $port, $pump_host, $is_reversed, $api_secret, $id);
            }
            if ($stmt->execute()) {
                $success = ($action === 'add') ? 'Pump device added successfully.' : 'Pump device updated successfully.';
            } else {
                $error = 'Database error: ' . $stmt->error . '. (Run pump_devices_migration.sql if columns are missing.)';
            }
            $stmt->close();
        }
    }

    if ($action === 'delete') {
        $id   = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM pump_devices WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        $success = 'Pump device deleted.';
    }
}

// ── Fetch all devices ──────────────────────────────────────────────────────
// Try the extended columns; fall back gracefully if migration not yet run
$devices = [];
$res = $conn->query(
    "SELECT id, label, port,
            COALESCE(pump_host,  'localhost') AS pump_host,
            COALESCE(is_reversed, 0)          AS is_reversed,
            COALESCE(api_secret,  '')         AS api_secret,
            created_at
     FROM pump_devices ORDER BY id"
);
if (!$res) {
    // Columns may not exist yet — fall back to basic columns
    $res = $conn->query("SELECT id, label, port, created_at FROM pump_devices ORDER BY id");
    $error = '⚠️ Extended pump columns not found. Please run <code>blankDB/pump_devices_migration.sql</code> on your database.';
}
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $row['pump_host']   = $row['pump_host']   ?? 'localhost';
        $row['is_reversed'] = $row['is_reversed'] ?? 0;
        $row['api_secret']  = $row['api_secret']  ?? '';
        $devices[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pump Devices – EasyFlow-L</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.min.css">
    <style>
        body { font-family: Arial, Helvetica, sans-serif; background: #f4f6fb; }
        .page-header { background: #2D008A; color: white; padding: 18px 24px; margin-bottom: 24px; }
        .page-header h2 { margin: 0; font-size: 22px; }
        .container-main { max-width: 1000px; margin: 0 auto; padding: 0 16px 80px; }
        .card { background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,.1); padding: 24px; margin-bottom: 24px; }
        .card h3 { color: #2D008A; border-bottom: 2px solid #2D008A; padding-bottom: 8px; margin-bottom: 18px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #2D008A; color: white; padding: 10px 12px; text-align: left; }
        td { padding: 9px 12px; border-bottom: 1px solid #e9ecef; }
        tr:hover td { background: #f0eeff; }
        .badge-local    { background: #28a745; color: white; padding: 3px 8px; border-radius: 12px; font-size: 12px; }
        .badge-remote   { background: #fd7e14; color: white; padding: 3px 8px; border-radius: 12px; font-size: 12px; }
        .badge-reversed { background: #dc3545; color: white; padding: 3px 8px; border-radius: 12px; font-size: 12px; }
        .badge-normal   { background: #17a2b8; color: white; padding: 3px 8px; border-radius: 12px; font-size: 12px; }
        .btn-edit   { background: #2D008A; color: white; border: none; padding: 5px 12px; border-radius: 5px; cursor: pointer; }
        .btn-del    { background: #dc3545; color: white; border: none; padding: 5px 12px; border-radius: 5px; cursor: pointer; }
        .form-group { margin-bottom: 14px; }
        label { font-weight: bold; color: #333; display: block; margin-bottom: 4px; }
        input[type=text], select { width: 100%; padding: 8px 10px; border: 1px solid #ced4da; border-radius: 5px; font-size: 15px; }
        .btn-submit { background: #2D008A; color: white; border: none; padding: 10px 24px; border-radius: 5px; font-size: 16px; cursor: pointer; }
        .alert-success { background: #d4edda; color: #155724; padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; }
        .alert-error   { background: #f8d7da; color: #721c24; padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; }
        .hint { font-size: 12px; color: #6c757d; margin-top: 3px; }
        .secret-field { font-family: monospace; letter-spacing: 1px; }
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:1000; align-items:center; justify-content:center; }
        .modal-overlay.active { display:flex; }
        .modal-box { background:white; border-radius:10px; padding:28px; width:500px; max-width:95vw; }
        .modal-box h4 { color:#2D008A; margin-top:0; }
    </style>
</head>
<body>

<div class="page-header">
    <h2><i class="fa fa-cog"></i> <?php echo t('pump_device'); ?> – <?php echo t('settings'); ?></h2>
</div>

<div class="container-main">

    <?php if ($success): ?>
        <div class="alert-success"><i class="fa fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert-error"><i class="fa fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Info box -->
    <div class="card" style="background:#e8f4fd; border-left:5px solid #2D008A;">
        <strong>ℹ️ Dual Dispensing Setup</strong><br>
        <ul style="margin:8px 0 0 16px; line-height:1.8;">
            <li>Set <strong>Pump Host</strong> to <code>localhost</code> if the pump is connected to <em>this</em> server machine.</li>
            <li>Set it to the client machine's <strong>IP address</strong> (e.g. <code>192.168.1.101</code>) for a pharmacy-station PC.</li>
            <li>Each client machine must have Laragon/XAMPP running and <code>pump\local_pump_api.php</code> deployed.</li>
            <li>Set a strong random <strong>API Secret</strong> and use the same value in <code>local_pump_api.php → PUMP_API_SECRET</code> on that client.</li>
            <li>For a <strong>reversed pump</strong>, tick the "Reversed" checkbox — the system will send a direction-invert command first.</li>
        </ul>
        <br>
        <strong>⚠️ First-time setup:</strong> Run <code>blankDB/pump_devices_migration.sql</code> on your database if you haven't already.
    </div>

    <!-- Devices Table -->
    <div class="card">
        <h3><i class="fa fa-list"></i> Configured Pumps</h3>
        <?php if (empty($devices)): ?>
            <p style="color:#888;">No pump devices found. Add one below.</p>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Label</th>
                    <th>Port</th>
                    <th>Host / IP</th>
                    <th>Direction</th>
                    <th>API Secret</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($devices as $d): ?>
                <tr>
                    <td><?php echo (int)$d['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($d['label']); ?></strong></td>
                    <td><code><?php echo htmlspecialchars($d['port']); ?></code></td>
                    <td>
                        <?php
                        $h = $d['pump_host'];
                        $is_local = in_array(strtolower($h), ['localhost','127.0.0.1','::1']);
                        ?>
                        <?php if ($is_local): ?>
                            <span class="badge-local">Server (local)</span>
                        <?php else: ?>
                            <span class="badge-remote"><?php echo htmlspecialchars($h); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($d['is_reversed']): ?>
                            <span class="badge-reversed">↩ Reversed</span>
                        <?php else: ?>
                            <span class="badge-normal">→ Normal</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $s = $d['api_secret'];
                        echo $s ? '<code style="font-size:11px;">' . htmlspecialchars(substr($s,0,8)) . '…</code>' : '<span style="color:#aaa;">not set</span>';
                        ?>
                    </td>
                    <td>
                        <button class="btn-edit" onclick="openEdit(<?php echo htmlspecialchars(json_encode($d)); ?>)">
                            <i class="fa fa-edit"></i> Edit
                        </button>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this pump device?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo (int)$d['id']; ?>">
                            <button type="submit" class="btn-del"><i class="fa fa-trash"></i> Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Add Pump Form -->
    <div class="card">
        <h3><i class="fa fa-plus-circle"></i> Add New Pump Device</h3>
        <form method="POST" id="addForm">
            <input type="hidden" name="action" value="add">
            <?php include '_pump_device_fields.php'; ?>
            <button type="submit" class="btn-submit"><i class="fa fa-save"></i> Add Pump</button>
        </form>
    </div>

</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal-box">
        <h4><i class="fa fa-edit"></i> Edit Pump Device</h4>
        <form method="POST" id="editForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id"     id="edit_id">
            <?php include '_pump_device_fields.php'; ?>
            <button type="submit" class="btn-submit"><i class="fa fa-save"></i> Save Changes</button>
            <button type="button" class="btn-del" style="margin-left:8px;" onclick="closeEdit()">Cancel</button>
        </form>
    </div>
</div>

<script>
function openEdit(d) {
    const m = document.getElementById('editModal');
    const f = document.getElementById('editForm');
    m.querySelector('#edit_id').value        = d.id;
    f.querySelector('[name=label]').value    = d.label;
    f.querySelector('[name=port]').value     = d.port;
    f.querySelector('[name=pump_host]').value = d.pump_host;
    f.querySelector('[name=is_reversed]').checked = (d.is_reversed == 1);
    f.querySelector('[name=api_secret]').value = d.api_secret;
    m.classList.add('active');
}
function closeEdit() {
    document.getElementById('editModal').classList.remove('active');
}
</script>
</body>
</html>

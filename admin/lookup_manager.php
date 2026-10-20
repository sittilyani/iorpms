<?php
/**
 * admin/lookup_manager.php
 *
 * Generic add / edit / delete screen for the system's reference ("lookup")
 * tables - categories, drugs, regimens, statuses, etc. Only tables listed in
 * admin/lookup_tables.php can be reached; column names always come from the
 * database schema, never from the request, and all values are bound.
 */
session_start();
include '../includes/config.php';
require_once __DIR__ . '/lookup_tables.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$role = $_SESSION['userrole'] ?? '';
if (!isset($_SESSION['user_id']) || !in_array(strtolower($role), ['admin', 'super admin', 'demo'], true)) {
    http_response_code(403);
    exit('<div style="font-family:sans-serif;padding:50px;text-align:center;color:#c53030">Access denied - administrators only.</div>');
}
$canWrite = strtolower($role) !== 'demo';   // demo accounts may look but not change

$tables = lookup_tables();
$key = $_GET['t'] ?? $_POST['t'] ?? '';
if (!isset($tables[$key])) {
    header('Location: system_settings.php');
    exit;
}
$meta  = $tables[$key];
$table = $meta['table'];

// ── Schema introspection ─────────────────────────────────────────────────────
$cols = [];       // name => ['type'=>, 'auto'=>bool, 'nullable'=>bool, 'editable'=>bool]
$res = $conn->query("SHOW COLUMNS FROM `$table`");
if (!$res) {
    exit('Table "' . htmlspecialchars($table) . '" is not available in this database yet.');
}
while ($c = $res->fetch_assoc()) {
    $isAuto = stripos($c['Extra'], 'auto_increment') !== false;
    $isStamp = (stripos($c['Default'] ?? '', 'CURRENT_TIMESTAMP') !== false)
            || in_array($c['Field'], ['date_created', 'comp_date', 'created_at', 'updated_at'], true);
    $cols[$c['Field']] = [
        'type'     => strtolower($c['Type']),
        'pk'       => $c['Key'] === 'PRI',
        'auto'     => $isAuto,
        'nullable' => $c['Null'] === 'YES',
        'editable' => !$isAuto && !$isStamp,
    ];
}
$pk = null;
foreach ($cols as $n => $c) { if ($c['pk']) { $pk = $n; break; } }
if ($pk === null) { $pk = array_key_first($cols); }
$pkAuto = $cols[$pk]['auto'];
$editable = array_keys(array_filter($cols, fn($c) => $c['editable']));
// A non-auto-increment key is assigned automatically (MAX+1), never typed in
$editable = array_values(array_filter($editable, fn($n) => $n !== $pk));

function lm_bind_type(string $sqlType): string {
    if (preg_match('/int|bit|year/', $sqlType)) return 'i';
    if (preg_match('/decimal|double|float|numeric/', $sqlType)) return 'd';
    return 's';
}

$flash = null; $flashOk = true;
$editRow = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canWrite) {
    try {
        $action = $_POST['action'] ?? '';
        if ($action === 'add' || $action === 'edit') {
            $vals = []; $types = '';
            foreach ($editable as $n) {
                $v = trim((string)($_POST['f'][$n] ?? ''));
                if ($v === '' && $cols[$n]['nullable']) { $v = null; }
                $vals[$n] = $v;
                $types .= lm_bind_type($cols[$n]['type']);
            }
            // first editable required text column must not be blank
            foreach ($editable as $n) {
                if (!$cols[$n]['nullable'] && ($vals[$n] === '' || $vals[$n] === null) && lm_bind_type($cols[$n]['type']) === 's') {
                    throw new Exception("'" . $n . "' is required.");
                }
            }
            if ($action === 'add') {
                $names = $editable; $params = array_values($vals);
                if (!$pkAuto) {
                    $next = (int)$conn->query("SELECT COALESCE(MAX(`$pk`),0)+1 AS n FROM `$table`")->fetch_assoc()['n'];
                    array_unshift($names, $pk); array_unshift($params, $next); $types = 'i' . $types;
                }
                $sql = "INSERT INTO `$table` (`" . implode('`,`', $names) . "`) VALUES (" . implode(',', array_fill(0, count($names), '?')) . ")";
                $st = $conn->prepare($sql);
                $st->bind_param($types, ...$params);
                $st->execute();
                $flash = 'Added successfully.';
            } else {
                $id = $_POST['id'] ?? '';
                $sets = implode(',', array_map(fn($n) => "`$n` = ?", $editable));
                $st = $conn->prepare("UPDATE `$table` SET $sets WHERE `$pk` = ?");
                $params = array_values($vals); $params[] = $id;
                $st->bind_param($types . lm_bind_type($cols[$pk]['type']), ...$params);
                $st->execute();
                $flash = 'Updated successfully.';
            }
        } elseif ($action === 'delete') {
            $st = $conn->prepare("DELETE FROM `$table` WHERE `$pk` = ?");
            $id = $_POST['id'] ?? '';
            $st->bind_param(lm_bind_type($cols[$pk]['type']), $id);
            $st->execute();
            $flash = 'Deleted.';
        }
    } catch (mysqli_sql_exception $e) {
        $flashOk = false;
        $flash = ($e->getCode() == 1451)
            ? 'This entry is in use elsewhere and cannot be deleted.'
            : 'Database error: ' . $e->getMessage();
    } catch (Exception $e) {
        $flashOk = false; $flash = $e->getMessage();
    }
}

if (isset($_GET['edit']) && $canWrite) {
    $st = $conn->prepare("SELECT * FROM `$table` WHERE `$pk` = ?");
    $eid = $_GET['edit'];
    $st->bind_param(lm_bind_type($cols[$pk]['type']), $eid);
    $st->execute();
    $editRow = $st->get_result()->fetch_assoc();
}

$q = trim($_GET['q'] ?? '');
$sql = "SELECT * FROM `$table`";
$params = []; $ptypes = '';
if ($q !== '') {
    $likes = [];
    foreach ($cols as $n => $c) {
        if (lm_bind_type($c['type']) === 's' && !in_array($n, ['date_created', 'comp_date'], true)) {
            $likes[] = "`$n` LIKE ?"; $params[] = "%$q%"; $ptypes .= 's';
        }
    }
    if ($likes) { $sql .= " WHERE " . implode(' OR ', $likes); }
}
$sql .= " ORDER BY `$pk` DESC LIMIT 500";
$st = $conn->prepare($sql);
if ($params) { $st->bind_param($ptypes, ...$params); }
$st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);

$label = fn($n) => ucwords(str_replace('_', ' ', preg_replace('/([a-z])([A-Z])/', '$1 $2', $n)));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($meta['label']) ?> - Settings</title>
<link rel="stylesheet" href="../assets/css/bootstrap.min.css">
<style>
 body{background:#f5f7fa;font-family:'Segoe UI',Tahoma,sans-serif;padding:20px;}
 .card-box{background:#fff;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,.08);padding:20px;margin-bottom:20px;}
 h2{color:#2C3162;font-size:1.4rem;margin-bottom:4px;} .sub{color:#666;margin-bottom:16px;}
 th{background:#2C3162;color:#fff;white-space:nowrap;} td{vertical-align:middle;}
 .btn-main{background:#2C3162;color:#fff;} .btn-main:hover{background:#82b543;color:#fff;}
</style>
</head>
<body>
<a href="system_settings.php" style="text-decoration:none;">&larr; All settings</a>
<h2><?= htmlspecialchars($meta['label']) ?></h2>
<div class="sub"><?= htmlspecialchars($meta['help'] ?? '') ?></div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flashOk ? 'success' : 'danger' ?>"><?= htmlspecialchars($flash) ?></div>
<?php endif; ?>
<?php if (!$canWrite): ?>
<div class="alert alert-info">Demo accounts can view settings but not change them.</div>
<?php endif; ?>

<?php if ($canWrite): ?>
<div class="card-box">
 <h5><?= $editRow ? 'Edit entry' : 'Add new' ?></h5>
 <form method="POST">
  <input type="hidden" name="t" value="<?= htmlspecialchars($key) ?>">
  <input type="hidden" name="action" value="<?= $editRow ? 'edit' : 'add' ?>">
  <?php if ($editRow): ?><input type="hidden" name="id" value="<?= htmlspecialchars($editRow[$pk]) ?>"><?php endif; ?>
  <div class="row">
  <?php foreach ($editable as $n): $isText = stripos($cols[$n]['type'], 'text') !== false; ?>
   <div class="col-md-4 mb-2">
    <label><?= htmlspecialchars($label($n)) ?><?= $cols[$n]['nullable'] ? '' : ' *' ?></label>
    <?php if ($isText): ?>
     <textarea class="form-control" name="f[<?= htmlspecialchars($n) ?>]" rows="2"><?= htmlspecialchars($editRow[$n] ?? '') ?></textarea>
    <?php else: ?>
     <input class="form-control" name="f[<?= htmlspecialchars($n) ?>]" value="<?= htmlspecialchars($editRow[$n] ?? '') ?>"
            <?= lm_bind_type($cols[$n]['type']) !== 's' ? 'type="number" step="any"' : 'type="text"' ?>>
    <?php endif; ?>
   </div>
  <?php endforeach; ?>
  </div>
  <button class="btn btn-main" type="submit"><?= $editRow ? 'Save changes' : 'Add' ?></button>
  <?php if ($editRow): ?><a class="btn btn-light" href="lookup_manager.php?t=<?= urlencode($key) ?>">Cancel</a><?php endif; ?>
 </form>
</div>
<?php endif; ?>

<div class="card-box">
 <form method="GET" class="form-inline mb-3">
  <input type="hidden" name="t" value="<?= htmlspecialchars($key) ?>">
  <input class="form-control mr-2" name="q" placeholder="Search..." value="<?= htmlspecialchars($q) ?>">
  <button class="btn btn-main" type="submit">Search</button>
  <span class="ml-3 text-muted"><?= count($rows) ?> record(s)</span>
 </form>
 <div class="table-responsive">
 <table class="table table-bordered table-sm">
  <thead><tr>
   <?php foreach (array_keys($cols) as $n): ?><th><?= htmlspecialchars($label($n)) ?></th><?php endforeach; ?>
   <?php if ($canWrite): ?><th>Action</th><?php endif; ?>
  </tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
   <tr>
    <?php foreach (array_keys($cols) as $n): ?><td><?= htmlspecialchars((string)$r[$n]) ?></td><?php endforeach; ?>
    <?php if ($canWrite): ?>
    <td style="white-space:nowrap;">
     <a class="btn btn-sm btn-outline-primary" href="lookup_manager.php?t=<?= urlencode($key) ?>&edit=<?= urlencode($r[$pk]) ?>">Edit</a>
     <form method="POST" style="display:inline" onsubmit="return confirm('Delete this entry?');">
      <input type="hidden" name="t" value="<?= htmlspecialchars($key) ?>">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="id" value="<?= htmlspecialchars($r[$pk]) ?>">
      <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
     </form>
    </td>
    <?php endif; ?>
   </tr>
  <?php endforeach; ?>
  </tbody>
 </table>
 </div>
</div>
</body>
</html>

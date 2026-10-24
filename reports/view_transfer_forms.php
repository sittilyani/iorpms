<?php
// Client transfer forms: search by name or MAT ID, view/download the PDF, and send (or resend) the acceptance link to the receiving facility.
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }
include '../includes/config.php';
require_once '../includes/transfer_helpers.php';
require_once '../includes/transfer_send_ui.php';
transfer_ensure_schema($conn);

$q = trim((string)($_GET['q'] ?? ''));
$transfer_forms = []; $error = '';
try {
    $like = '%' . $q . '%';
    if ($q !== '') {
        $stmt = $conn->prepare("SELECT id, facilityname, clientName, mat_id, referral_date, type_of_movement, from_site, to_site, pdf_filename, created_at FROM transfer_forms WHERE clientName LIKE ? OR mat_id LIKE ? ORDER BY created_at DESC LIMIT 200");
        $stmt->bind_param('ss', $like, $like);
    } else {
        $stmt = $conn->prepare("SELECT id, facilityname, clientName, mat_id, referral_date, type_of_movement, from_site, to_site, pdf_filename, created_at FROM transfer_forms ORDER BY created_at DESC LIMIT 200");
    }
    $stmt->execute();
    $transfer_forms = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (\Exception $e) { $error = "Database Error: " . $e->getMessage(); }
$h = fn($v) => htmlspecialchars((string)$v);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Transfer Forms</title>
    <link href="../assets/css/bootstrap-5.3.0.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.css">
    <style>
        body{background:#FDFCF9}
        .table thead th{background-color:#2D008A;color:#fff}
        .action-btns .btn{margin:0 4px 4px 0}
        .st-sent{background:#e6f8ee;color:#04703a;border-radius:999px;padding:2px 10px;font-size:12px;font-weight:600;display:inline-block}
        .st-link{background:#fff4d6;color:#7a5600;border-radius:999px;padding:2px 10px;font-size:12px;font-weight:600;display:inline-block}
        .st-none{background:#eee;color:#666;border-radius:999px;padding:2px 10px;font-size:12px;display:inline-block}
        .btn-send{background:#04B04B;border-color:#04B04B;color:#fff}.btn-resend{background:#FFC12E;border-color:#FFC12E;color:#3a2a00}
    </style>
</head>
<body>
<div class="container-fluid py-4">
    <h2 class="mb-3" style="color:#2D008A">Client Transfer Forms</h2>

    <form method="get" class="d-flex flex-wrap gap-2 mb-3">
        <input type="text" name="q" value="<?= $h($q) ?>" class="form-control" style="max-width:360px" placeholder="Search by client name or MAT ID">
        <button class="btn" style="background:#2D008A;color:#fff">Search</button>
        <?php if ($q !== ''): ?><a href="view_transfer_forms.php" class="btn btn-outline-secondary">Clear</a><?php endif; ?>
    </form>

    <?php if ($error): ?><div class="alert alert-danger"><?= $h($error) ?></div><?php endif; ?>

    <div class="card">
        <div class="card-header text-white" style="background:#2D008A"><h5 class="mb-0"><?= count($transfer_forms) ?> transfer form<?= count($transfer_forms) === 1 ? '' : 's' ?><?= $q !== '' ? ' matching "' . $h($q) . '"' : '' ?></h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead><tr><th>#</th><th>Client Name</th><th>MAT ID</th><th>Transfer Type</th><th>From Site</th><th>To Site</th><th>Referral Date</th><th>Receiving facility</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php if (empty($transfer_forms)): ?>
                        <tr><td colspan="9" class="text-center">No transfer forms found.</td></tr>
                    <?php else: foreach ($transfer_forms as $i => $form):
                        $pdf = $form['pdf_filename'] ?? '';
                        if (empty($pdf) && !empty($form['mat_id']) && !empty($form['created_at'])) { $pdf = $form['mat_id'] . '_' . date('Y-m-d', strtotime((string)$form['created_at'])) . '.pdf'; }
                        elseif (!empty($pdf) && pathinfo($pdf, PATHINFO_EXTENSION) !== 'pdf') { $pdf .= '.pdf'; }
                        $state = transfer_form_state($conn, (int)$form['id']);
                        $sent = $state && (int)$state['email_sent'] === 1;
                        $known = $state['to_email'] ?? '' ?: transfer_known_email($conn, (string)$form['to_site']);
                        $stateTxt = $sent ? 'Already sent to ' . $state['to_email'] . ' on ' . date('j M Y H:i', strtotime((string)$state['created_at'])) : ($state ? 'A link was created on ' . date('j M Y H:i', strtotime((string)$state['created_at'])) . ' but not emailed' : '');
                    ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= $h($form['clientName']) ?></td>
                            <td><?= $h($form['mat_id']) ?></td>
                            <td><?= $h($form['type_of_movement']) ?></td>
                            <td><?= $h($form['from_site']) ?></td>
                            <td><?= $h($form['to_site']) ?></td>
                            <td><?= $h($form['referral_date']) ?></td>
                            <td>
                                <?php if ($sent): ?><span class="st-sent"><i class="fa fa-check"></i> Already sent</span><br><small><?= $h(date('j M Y', strtotime((string)$state['created_at']))) ?></small>
                                <?php elseif ($state): ?><span class="st-link"><i class="fa fa-link"></i> Link created, not emailed</span>
                                <?php else: ?><span class="st-none">Not sent</span><?php endif; ?>
                                <?php if ($state && !empty($state['accepted_at'])): ?><br><span class="st-sent">Accepted <?= $h(date('j M Y', strtotime((string)$state['accepted_at']))) ?></span><?php endif; ?>
                            </td>
                            <td class="action-btns">
                                <button type="button" class="btn btn-sm <?= $sent ? 'btn-resend' : 'btn-send' ?>"
                                    data-id="<?= (int)$form['id'] ?>" data-client="<?= $h($form['clientName']) ?>" data-site="<?= $h($form['to_site']) ?>" data-email="<?= $h($known) ?>" data-state="<?= $h($stateTxt) ?>"
                                    onclick="openTransferSend({id:+this.dataset.id, client:this.dataset.client, toSite:this.dataset.site, email:this.dataset.email, state:this.dataset.state, done:function(){setTimeout(function(){location.reload()},1500)}})">
                                    <i class="fa fa-paper-plane"></i> <?= $sent ? 'Resend' : 'Send' ?>
                                </button>
                                <?php if (!empty($pdf)): ?>
                                    <a href="../transferforms/<?= urlencode($pdf) ?>" target="_blank" class="btn btn-sm btn-info" title="View PDF"><i class="fa fa-eye"></i> View</a>
                                    <a href="download_handler.php?file=<?= urlencode($pdf) ?>" class="btn btn-sm btn-success" title="Download PDF"><i class="fa fa-download"></i> Download</a>
                                <?php else: ?><span class="text-danger">File missing</span><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php transfer_send_modal('transfer_send.php'); ?>
<script src="../assets/js/bootstrap-5.3.0.bundle.min.js"></script>
</body>
</html>
<?php if (isset($conn)) { mysqli_close($conn); }

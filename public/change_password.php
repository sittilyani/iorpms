<?php
/**
 * public/change_password.php
 * ===========================
 * Forced password change on FIRST LOGIN for demo (and any flagged) accounts.
 * The user lands here from login.php when tblusers.must_change_password = 1.
 * After a successful change the flag is cleared and they are sent back to
 * login with their new password. The password never expires after this.
 */
session_start();
include '../includes/config.php';

// Must have come from a successful credential check in login.php
if (empty($_SESSION['pw_change_user_id'])) {
    header("Location: login.php");
    exit();
}
$userId   = (int)$_SESSION['pw_change_user_id'];
$username = $_SESSION['pw_change_username'] ?? '';

$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = trim($_POST['current_password'] ?? '');
    $new1    = trim($_POST['new_password'] ?? '');
    $new2    = trim($_POST['confirm_password'] ?? '');

    if ($current === '' || $new1 === '' || $new2 === '') {
        $error = 'All fields are required.';
    } elseif ($new1 !== $new2) {
        $error = 'New passwords do not match.';
    } elseif (strlen($new1) < 8) {
        $error = 'New password must be at least 8 characters.';
    } elseif ($new1 === $current) {
        $error = 'New password must be different from the temporary password.';
    } else {
        $stmt = $conn->prepare("SELECT password FROM tblusers WHERE user_id = ? LIMIT 1");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row || !password_verify($current, $row['password'])) {
            $error = 'The temporary password you entered is incorrect.';
        } else {
            $hash = password_hash($new1, PASSWORD_DEFAULT);
            $upd  = $conn->prepare("UPDATE tblusers SET password = ?, must_change_password = 0 WHERE user_id = ?");
            $upd->bind_param('si', $hash, $userId);
            if ($upd->execute()) {
                $success = true;
                unset($_SESSION['pw_change_user_id'], $_SESSION['pw_change_username']);
            } else {
                $error = 'Could not update the password. Please try again.';
            }
            $upd->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EasyFlow-L — Change Password</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicon_io/favicon-32x32.png">
    <style>
        body{background:#f4f7fb;font-family:Arial,Helvetica,sans-serif;}
        .card-box{max-width:460px;margin:6% auto;background:#fff;border-radius:12px;
                  box-shadow:0 4px 20px rgba(0,0,0,.1);padding:36px;}
        h2{color:#2D008A;font-size:1.4rem;margin-bottom:6px;}
        p.sub{color:#666;font-size:.92rem;margin-bottom:22px;}
        label{color:#2D008A;font-weight:bold;margin-top:12px;}
        input{width:100%;height:46px;font-size:1rem;border:1px solid #cdd5e0;
              border-radius:6px;padding:0 12px;margin-top:4px;}
        .btn-submit{background:#2D008A;color:#fff;font-size:1.05rem;font-weight:bold;
                    width:100%;height:48px;border:none;border-radius:6px;margin-top:22px;cursor:pointer;}
        .btn-submit:hover{background:#40109f;}
        .error{color:#c53030;background:#fdecec;border:1px solid #f5c2c2;border-radius:6px;
               padding:10px 14px;font-size:.9rem;margin-bottom:10px;}
        .ok{color:#276749;background:#e6f4ea;border:1px solid #b7e1c1;border-radius:6px;
            padding:14px;font-size:.95rem;text-align:center;}
        .logo{text-align:center;margin-bottom:18px;}
    </style>
</head>
<body>
<div class="card-box">
    <div class="logo"><img src="../assets/images/easyflow_logonew.png" width="180" alt="EasyFlow-L"></div>

    <?php if ($success): ?>
        <div class="ok">
            <strong>Password changed successfully!</strong><br><br>
            Your new password is now active and will not expire.<br>
            <a href="login.php" style="display:inline-block;margin-top:14px;background:#82b543;color:#fff;
               padding:10px 26px;border-radius:6px;text-decoration:none;font-weight:bold;">Log in now</a>
        </div>
    <?php else: ?>
        <h2>Set Your New Password</h2>
        <p class="sub">Hi <strong><?php echo htmlspecialchars($username); ?></strong> — for security you must
           change your temporary password before exploring the system. This only happens once.</p>

        <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <form method="post" action="change_password.php" autocomplete="off">
            <label for="current_password">Temporary password (from your email/WhatsApp)</label>
            <input type="password" id="current_password" name="current_password" required>

            <label for="new_password">New password (min 8 characters)</label>
            <input type="password" id="new_password" name="new_password" minlength="8" required>

            <label for="confirm_password">Confirm new password</label>
            <input type="password" id="confirm_password" name="confirm_password" minlength="8" required>

            <button type="submit" class="btn-submit">Change Password &amp; Continue</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>

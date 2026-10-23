<?php
/**
 * public/reset_password_confirm.php
 * ─────────────────────────────────────────────────────────────────
 * Second half of the "Forgot Password" flow. Validates the single-use
 * token from the emailed link (see forgot_password.php) and lets the user
 * set a new password. Online-only, same reasoning as forgot_password.php.
 * ─────────────────────────────────────────────────────────────────
 */
session_start();
include '../includes/config.php';

if (APP_ENV !== 'online') {
    http_response_code(403);
    echo '<p style="font-family:Arial,Helvetica,sans-serif;text-align:center;margin-top:80px;">
            Password reset by email is only available on the online version of EasyFlow-L.</p>';
    exit();
}

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$error   = '';
$success = false;
$tokenRow = null;

if (empty($token)) {
    $error = 'Missing reset token.';
} else {
    $tokenHash = hash('sha256', $token);
    $stmt = $conn->prepare(
        "SELECT pr.id, pr.user_id, pr.expires_at, pr.used_at, u.first_name, u.email
         FROM password_resets pr
         JOIN tblusers u ON u.user_id = pr.user_id
         WHERE pr.token_hash = ? LIMIT 1"
    );
    $stmt->bind_param('s', $tokenHash);
    $stmt->execute();
    $tokenRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$tokenRow) {
        $error = 'This password reset link is invalid.';
    } elseif ($tokenRow['used_at'] !== null) {
        $error = 'This password reset link has already been used.';
    } elseif (strtotime((string)($tokenRow['expires_at'])) < time()) {
        $error = 'This password reset link has expired. Please request a new one.';
    }
}

if (!$error && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'])) {
    $newPassword     = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (strlen((string)($newPassword)) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);

        $upd = $conn->prepare("UPDATE tblusers SET password = ?, must_change_password = 0 WHERE user_id = ?");
        $upd->bind_param('si', $hashed, $tokenRow['user_id']);
        $upd->execute();
        $upd->close();

        // Mark this token used and invalidate any other outstanding tokens for the user.
        $mark = $conn->prepare("UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL");
        $mark->bind_param('i', $tokenRow['user_id']);
        $mark->execute();
        $mark->close();

        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EasyFlow-L</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <style>
        body { background:#f5f7fa; }
        .container { max-width: 460px; margin-top: 8%; }
        h2 { color: #2D008A; text-align:center; }
        label { color: #2D008A; font-weight: bold; }
        input[type=password] { width:100%; height:46px; font-size:16px; border-radius:5px; text-align:center;
            border:1px solid #ccc; margin: 10px 0 18px; }
        .btn-submit { background:#2D008A; color:#fff; font-size:16px; font-weight:bold; width:100%; height:46px;
            border:none; border-radius:5px; }
        .btn-submit:hover { cursor:pointer; background:#1a0060; }
        .msg { padding:14px 16px; border-radius:6px; margin-bottom:16px; font-size:14px; }
        .msg-ok  { background:#e6ffed; color:#1a7f37; border:1px solid #a3d9b1; }
        .msg-err { background:#fdeaea; color:#c53030; border:1px solid #f0b0b0; }
        .back-link { display:block; text-align:center; margin-top:16px; color:#2D008A; font-weight:bold; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Set New Password</h2>

        <?php if ($success): ?>
            <div class="msg msg-ok">Your password has been reset successfully. You can now log in with your new password.</div>
            <a href="login.php" class="back-link">&larr; Go to Login</a>

        <?php elseif ($error): ?>
            <div class="msg msg-err"><?= htmlspecialchars((string)($error)) ?></div>
            <a href="forgot_password.php" class="back-link">Request a new reset link &rarr;</a>

        <?php else: ?>
            <p style="text-align:center;color:#555;">
                Hi <?= htmlspecialchars((string)($tokenRow['first_name'] ?: '')) ?>, enter your new password below.
            </p>
            <form method="post" action="reset_password_confirm.php">
                <input type="hidden" name="token" value="<?= htmlspecialchars((string)($token)) ?>">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required minlength="6">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                <button type="submit" class="btn-submit">Set New Password</button>
            </form>
            <a href="login.php" class="back-link">&larr; Back to Login</a>
        <?php endif; ?>
    </div>
</body>
</html>

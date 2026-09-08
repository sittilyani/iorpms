<?php
/**
 * public/forgot_password.php
 * ─────────────────────────────────────────────────────────────────
 * Self-service "Forgot Password" — pre-login. Available on the ONLINE
 * deployment only (APP_ENV === 'online'); local/offline dev relies on an
 * admin doing a manual reset via admin/sidenav.php instead, since there is
 * no guarantee of working outbound mail on a dev machine.
 *
 * Flow: user enters their registered email → if it matches a tblusers row,
 * a single-use, time-limited token is emailed as a reset link → they land
 * on reset_password_confirm.php to set a new password.
 *
 * The confirmation message is identical whether or not the email is
 * registered, so this page can't be used to enumerate valid accounts.
 * ─────────────────────────────────────────────────────────────────
 */
session_start();
include '../includes/config.php';
include '../includes/languages.php';
include '../includes/mail_helper.php';

if (APP_ENV !== 'online') {
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>EasyFlow-L</title>
        <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    </head>
    <body>
        <div style="max-width:520px;margin:80px auto;text-align:center;font-family:Arial,Helvetica,sans-serif;">
            <h2 style="color:#2D008A;">Not available offline</h2>
            <p style="color:#555;">Password reset by email is only available on the online (production) version of EasyFlow-L.
               On this local/offline installation, please ask a system administrator to reset your password from
               <strong>Admin &rarr; View users</strong>.</p>
            <p><a href="login.php" style="color:#2D008A;font-weight:bold;">&larr; Back to Login</a></p>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// ── Ensure the password_resets table exists ───────────────────────────────────
$conn->query("
    CREATE TABLE IF NOT EXISTS password_resets (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        user_id    INT NOT NULL,
        email      VARCHAR(120) NOT NULL,
        token_hash VARCHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        used_at    DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_token_hash (token_hash),
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$message   = '';
$messageOk = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
    } else {
        // Look up the account, but never reveal whether it exists.
        $stmt = $conn->prepare("SELECT user_id, first_name, email FROM tblusers WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user) {
            // Invalidate any earlier unused tokens for this user before issuing a new one.
            $inv = $conn->prepare("UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL");
            $inv->bind_param('i', $user['user_id']);
            $inv->execute();
            $inv->close();

            $rawToken  = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $rawToken);
            $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour

            $ins = $conn->prepare(
                "INSERT INTO password_resets (user_id, email, token_hash, expires_at) VALUES (?, ?, ?, ?)"
            );
            $ins->bind_param('isss', $user['user_id'], $user['email'], $tokenHash, $expiresAt);
            $ins->execute();
            $ins->close();

            $resetLink = rtrim(APP_URL, '/') . '/public/reset_password_confirm.php?token=' . $rawToken;
            $firstName = htmlspecialchars($user['first_name'] ?: 'there');

            $body = "
                <p>Hi {$firstName},</p>
                <p>We received a request to reset your EasyFlow-L password. Click the button below to set a new one.
                   This link expires in <strong>1 hour</strong> and can only be used once.</p>
                <p style='margin:24px 0;'>
                    <a href='{$resetLink}' style='background:#2D008A;color:#fff;padding:12px 24px;border-radius:6px;
                       text-decoration:none;font-weight:bold;'>Reset My Password</a>
                </p>
                <p>If the button doesn't work, copy and paste this link into your browser:<br>
                   <a href='{$resetLink}'>{$resetLink}</a></p>
                <p style='color:#888;font-size:13px;'>If you didn't request this, you can safely ignore this email —
                   your password will not be changed.</p>
            ";
            sendAppMail($user['email'], $user['first_name'] ?: 'EasyFlow-L User', 'Reset your EasyFlow-L password', $body);
        }

        // Identical message regardless of whether the account exists.
        $messageOk = true;
        $message   = "If {$email} is a registered EasyFlow-L account, a password reset link has been sent to it. "
                   . "The link expires in 1 hour.";
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
        input[type=email] { width:100%; height:46px; font-size:16px; border-radius:5px; text-align:center;
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
        <h2>Forgot Password</h2>
        <p style="text-align:center;color:#555;">Enter the email address on your account and we'll send you a link to reset your password.</p>

        <?php if ($message): ?>
            <div class="msg <?= $messageOk ? 'msg-ok' : 'msg-err' ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if (!$messageOk): ?>
        <form method="post" action="forgot_password.php">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            <button type="submit" class="btn-submit">Send Reset Link</button>
        </form>
        <?php endif; ?>

        <a href="login.php" class="back-link">&larr; Back to Login</a>
    </div>
</body>
</html>

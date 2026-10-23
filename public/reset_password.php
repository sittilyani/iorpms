<?php
session_start();
include('../includes/config.php');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.top.location.href = '../index.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($new_password !== $confirm_password) {
        $error_message = "New passwords do not match. Please try again.";
    } else {
        $stmt = $conn->prepare("SELECT password FROM tblusers WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($current_password_hash);
        $stmt->fetch();
        $stmt->close();

        if (password_verify($old_password, (string)$current_password_hash)) {
            $hashed_new_password = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt_update = $conn->prepare("UPDATE tblusers SET password = ? WHERE user_id = ?");
            $stmt_update->bind_param("si", $hashed_new_password, $user_id);

            if ($stmt_update->execute()) {
                $stmt_update->close();
                $conn->close();
                // sign out and send the whole window (not just this frame) to the login page
                $_SESSION = [];
                session_destroy();
                echo '<!DOCTYPE html><html><body style="font-family:Segoe UI,Arial;text-align:center;padding:60px;color:#2D008A"><h2>Password updated</h2><p>Signing you out so you can log in with the new password...</p><script>setTimeout(function(){ window.top.location.href = "../index.php"; }, 1200);</script></body></html>';
                exit;
            }
            $error_message = "Failed to update password. Please try again.";
        } else {
            $error_message = "Old password is incorrect.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.css" type="text/css">
    <style>
        *{box-sizing:border-box}
        body{margin:0;background:#FDFCF9;font-family:Segoe UI,Arial,sans-serif;color:#222}
        .pw-wrap{max-width:520px;margin:32px auto;padding:0 16px}
        .pw-card{background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 8px 24px rgba(45,0,138,.15);border:1px solid #e5e0f2}
        .pw-head{background:linear-gradient(135deg,#2D008A,#4a14b8);color:#fff;padding:26px 28px;display:flex;align-items:center;gap:16px}
        .pw-head i{font-size:30px;background:rgba(255,255,255,.15);border-radius:50%;width:64px;height:64px;display:flex;align-items:center;justify-content:center}
        .pw-head h2{margin:0;font-size:1.4rem}.pw-head p{margin:4px 0 0;opacity:.9;font-size:.9rem}
        .pw-body{padding:26px 28px}
        label{display:block;font-weight:600;color:#2D008A;margin:14px 0 6px;font-size:.9rem}
        .pw-in{position:relative}
        .pw-in input{width:100%;padding:11px 42px 11px 12px;border:1px solid #cfc6ea;border-radius:8px;font-size:15px}
        .pw-in input:focus{outline:none;border-color:#2D008A;box-shadow:0 0 0 3px rgba(45,0,138,.15)}
        .pw-in span{position:absolute;right:12px;top:50%;transform:translateY(-50%);cursor:pointer;color:#6B3FD1}
        .pw-btn{margin-top:22px;width:100%;background:#04B04B;color:#fff;border:0;border-radius:8px;padding:12px;font-size:16px;font-weight:600;cursor:pointer}
        .pw-btn:hover{background:#039a40}
        .pw-tip{background:#f3effb;border-left:4px solid #2D008A;border-radius:8px;padding:10px 14px;font-size:.85rem;color:#333;margin-top:18px}
        .pw-err{background:#fdecee;border-left:4px solid #E41E39;color:#8f0f20;border-radius:8px;padding:10px 14px;margin-bottom:6px}
    </style>
</head>
<body>
<div class="pw-wrap"><div class="pw-card">
    <div class="pw-head"><i class="fa fa-key"></i><div><h2>Change your password</h2><p>You will be signed out afterwards and asked to log in again.</p></div></div>
    <div class="pw-body">
        <?php if (isset($error_message)) { echo '<div class="pw-err">' . htmlspecialchars((string)$error_message) . '</div>'; } ?>
        <form method="post" action="reset_password.php">
            <label for="old_password">Old password</label>
            <div class="pw-in"><input type="password" id="old_password" name="old_password" required><span onclick="tg('old_password')"><i class="fa fa-eye"></i></span></div>
            <label for="new_password">New password</label>
            <div class="pw-in"><input type="password" id="new_password" name="new_password" minlength="6" required><span onclick="tg('new_password')"><i class="fa fa-eye"></i></span></div>
            <label for="confirm_password">Confirm new password</label>
            <div class="pw-in"><input type="password" id="confirm_password" name="confirm_password" minlength="6" required><span onclick="tg('confirm_password')"><i class="fa fa-eye"></i></span></div>
            <button class="pw-btn" type="submit"><i class="fa fa-lock"></i> Update password</button>
        </form>
        <div class="pw-tip">Use at least 6 characters and avoid the default password (123456).</div>
    </div>
</div></div>
<script>function tg(id){var e=document.getElementById(id);e.type=e.type==='password'?'text':'password';}</script>
</body>
</html>

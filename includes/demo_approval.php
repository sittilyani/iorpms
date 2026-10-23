<?php
/**
 * includes/demo_approval.php
 * Shared by demo_request.php (new request) and superadmin/demo_action.php (admin clicks Approve).
 *
 * A demo request waits as 'pending'. When an admin approves it:
 *   - a demo user account is created with a generated temporary password
 *   - the credentials are EMAILED to the requester
 *   - a prefilled WhatsApp (wa.me) link is returned, which the admin's browser opens
 *   - the demo runs for DEMO_DAYS days, then is marked 'expired' and can no longer log in
 */
require_once __DIR__ . '/mail_helper.php';

const DEMO_DAYS = 30;

function demoEmailWrap(string $title, string $body): string
{
    $year = date('Y');
    return <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>
  body{font-family:Arial,sans-serif;background:#f4f7fb;margin:0;padding:20px;}
  .box{max-width:560px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;
       box-shadow:0 4px 20px rgba(0,0,0,.1);}
  .header{background:#2D008A;color:#fff;padding:28px 32px;}
  .header h1{margin:0;font-size:1.3rem;}
  .body{padding:28px 32px;color:#263238;font-size:.95rem;line-height:1.7;}
  .btn{display:inline-block;background:#82b543;color:#fff !important;padding:12px 28px;
       border-radius:8px;text-decoration:none;font-weight:700;margin:16px 0;}
  .cred{background:#f4f7fb;border:1px dashed #2D008A;border-radius:8px;padding:14px 18px;
        font-family:Consolas,monospace;font-size:1.05rem;margin:14px 0;}
  .footer{background:#f4f7fb;padding:16px 32px;font-size:.78rem;color:#888;text-align:center;}
  table{width:100%;border-collapse:collapse;margin:12px 0;}
  td{padding:8px 10px;border:1px solid #dde3f0;font-size:.88rem;}
  td:first-child{font-weight:700;background:#f9f9f9;width:35%;}
</style>
</head>
<body><div class="box">
  <div class="header"><h1>EasyFlow-L — $title</h1></div>
  <div class="body">$body</div>
  <div class="footer">&copy; $year EasyFlow-L · support@sitti.site · WhatsApp +254 722 427 721</div>
</div></body></html>
HTML;
}

/** Readable temporary password, e.g. "Simba-7K2M-94" */
function generateTempPassword(): string
{
    $words = ['Simba','Twiga','Chui','Nyati','Tembo','Kifaru','Duma','Pundamilia','Korongo','Mamba'];
    $word  = $words[random_int(0, count($words) - 1)];
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no confusing 0/O/1/I
    $rand  = '';
    for ($i = 0; $i < 4; $i++) $rand .= $chars[random_int(0, strlen((string)($chars)) - 1)];
    return $word . '-' . $rand . '-' . random_int(10, 99);
}

/**
 * $mode 'approve'    : pending request -> create the account, 30 days
 * $mode 'resend'     : active demo -> fresh temporary password, same expiry
 * $mode 'reactivate' : expired demo -> fresh password and a new 30 days
 * @return array success, error, email_sent, email_error, wa_link, username, expires_at
 */
function approveDemoRequest(mysqli $conn, int $id, string $mode = 'approve'): array
{
    expireDemoAccounts($conn);
    $st = $conn->prepare("SELECT * FROM demo_requests WHERE id = ?");
    $st->bind_param('i', $id);
    $st->execute();
    $r = $st->get_result()->fetch_assoc();
    $st->close();
    if (!$r) return ['success' => false, 'error' => 'Request not found.'];
    $need = ['approve' => 'pending', 'resend' => 'approved', 'reactivate' => 'expired'][$mode] ?? 'pending';
    if ($r['status'] !== $need) {
        return ['success' => false, 'error' => 'This request is ' . $r['status'] . ', so it cannot be ' . ($mode === 'approve' ? 'approved' : ($mode === 'resend' ? 'sent again' : 'reactivated')) . '.'];
    }

    $tempPassword = generateTempPassword();
    $hash = password_hash($tempPassword, PASSWORD_DEFAULT);
    $userId = (int)$r['user_id'];

    if ($userId) {                                    // re-send: reset the password
        $u = $conn->prepare("UPDATE tblusers SET password = ?, must_change_password = 1 WHERE user_id = ?");
        $u->bind_param('si', $hash, $userId);
        $u->execute();
        $uq = $conn->prepare("SELECT username FROM tblusers WHERE user_id = ?");
        $uq->bind_param('i', $userId);
        $uq->execute();
        $username = $uq->get_result()->fetch_assoc()['username'] ?? '';
    } else {
        $base = strtolower((string)(preg_replace('/[^a-z0-9]/i', '', explode('@', $r['email'])[0])));
        if ($base === '') $base = 'demo';
        $username = $base; $n = 0;
        while (true) {
            $c = $conn->prepare("SELECT user_id FROM tblusers WHERE username = ? LIMIT 1");
            $c->bind_param('s', $username);
            $c->execute();
            $taken = $c->get_result()->fetch_assoc();
            $c->close();
            if (!$taken) break;
            $username = $base . (++$n);
        }
        $gender = 'Not specified'; $role = 'Demo';
        $ins = $conn->prepare("INSERT INTO tblusers (username, first_name, last_name, email, password, gender, mobile, userrole, must_change_password, is_demo)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 1)");
        $ins->bind_param('ssssssss', $username, $r['first_name'], $r['last_name'], $r['email'], $hash, $gender, $r['phone'], $role);
        if (!$ins->execute()) return ['success' => false, 'error' => 'Could not create the demo account: ' . $ins->error];
        $userId = $conn->insert_id;
    }

    if ($mode === 'resend') {
        $up = $conn->prepare("UPDATE demo_requests SET user_id = ? WHERE id = ?");
    } else {
        $up = $conn->prepare("UPDATE demo_requests SET status = 'approved', user_id = ?, approved_at = NOW(), expires_at = NOW() + INTERVAL " . DEMO_DAYS . " DAY, reactivation_requested_at = NULL WHERE id = ?");
    }
    $up->bind_param('ii', $userId, $id);
    $up->execute();
    $ex = $conn->prepare("SELECT expires_at FROM demo_requests WHERE id = ?");
    $ex->bind_param('i', $id);
    $ex->execute();
    $expires = $ex->get_result()->fetch_assoc()['expires_at'];
    $expLabel = date('j M Y', strtotime((string)($expires)));

    $lang = in_array($r['lang'] ?? 'en', ['en', 'fr', 'pt'], true) ? $r['lang'] : 'en';
    $first = $r['first_name']; $clinic = $r['clinic_name'];
    $loginLink = APP_URL . '/public/login.php';

    $subjects = [
        'en' => 'Your EasyFlow-L Demo Account — Login Credentials',
        'fr' => 'Votre compte démo EasyFlow-L — Identifiants de connexion',
        'pt' => 'A sua conta demo EasyFlow-L — Credenciais de acesso',
    ];
    $intros = [
        'en' => "Hi $first,<br><br>Welcome to <strong>EasyFlow-L</strong>! Your demo request for <strong>$clinic</strong> has been <strong>approved</strong>. Use these credentials to log in:",
        'fr' => "Bonjour $first,<br><br>Bienvenue sur <strong>EasyFlow-L</strong> ! Votre demande de démo pour <strong>$clinic</strong> a été <strong>approuvée</strong>. Utilisez ces identifiants pour vous connecter :",
        'pt' => "Olá $first,<br><br>Bem-vindo ao <strong>EasyFlow-L</strong>! O seu pedido de demo para <strong>$clinic</strong> foi <strong>aprovado</strong>. Use estas credenciais para iniciar sessão:",
    ];
    $notes = [
        'en' => "For security you will be asked to <strong>change this password the first time you log in</strong>. This demo is valid for <strong>" . DEMO_DAYS . " days (until $expLabel)</strong>, after which it expires. You will also receive these details on WhatsApp from <strong>+254 722 427 721</strong>.",
        'fr' => "Pour votre sécurité, vous devrez <strong>changer ce mot de passe lors de votre première connexion</strong>. Cette démo est valable <strong>" . DEMO_DAYS . " jours (jusqu'au $expLabel)</strong>, puis elle expire. Vous recevrez aussi ces informations sur WhatsApp du <strong>+254 722 427 721</strong>.",
        'pt' => "Por segurança, ser-lhe-á pedido para <strong>alterar esta palavra-passe no primeiro início de sessão</strong>. Esta demo é válida por <strong>" . DEMO_DAYS . " dias (até $expLabel)</strong>, depois expira. Também receberá estes dados por WhatsApp do <strong>+254 722 427 721</strong>.",
    ];
    $btn = ['en' => 'Log In Now', 'fr' => 'Se connecter', 'pt' => 'Iniciar sessão'][$lang];
    $trainingLink = APP_URL . '/trainings/index.php';
    $body = demoEmailWrap('Demo approved', "
{$intros[$lang]}
<div class='cred'>
  Username: <strong>$username</strong><br>
  Temporary password: <strong>$tempPassword</strong>
</div>
<a href='$loginLink' class='btn'>$btn</a>
<p style='font-size:.86rem;'>{$notes[$lang]}</p>
<p style='font-size:.86rem;'>Self-learning: <a href='https://www.youtube.com/@EasyFlow-LTutorialsc'>youtube.com/@EasyFlow-LTutorialsc</a>
   or the <a href='$trainingLink'>Training Videos</a> page.</p>
");
    $mail = sendAppMail($r['email'], $first . ' ' . $r['last_name'], $subjects[$lang], $body);

    $dec = fn($s) => html_entity_decode((string)$s, ENT_QUOTES, 'UTF-8');
    $waText = "Hello " . $dec($first) . ", welcome to EasyFlow-L!\n\n"
            . "Your demo request has been approved:\n"
            . "Login: $loginLink\n"
            . "Username: $username\n"
            . "Temporary password: $tempPassword\n\n"
            . "You'll be asked to change the password on first login. The demo is valid for " . DEMO_DAYS . " days (until $expLabel).\n"
            . "Training videos: https://www.youtube.com/@EasyFlow-LTutorialsc";
    $waPhone = preg_replace('/\D/', '', $r['phone']);

    return [
        'success' => true, 'username' => $username, 'expires_at' => $expires,
        'email_sent' => (bool)$mail['success'], 'email_error' => $mail['success'] ? '' : ($mail['error'] ?? 'unknown error'),
        'wa_link' => $waPhone ? 'https://wa.me/' . $waPhone . '?text=' . rawurlencode((string)($waText)) : '',
    ];
}

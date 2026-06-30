<?php
/**
 * demo_request.php
 * =================
 * Handles the demo/access request form submitted from index.php.
 *
 * Flow:
 *  1. Validate + sanitise POST data
 *  2. Create the `demo_requests` table if it doesn't exist
 *  3. Check for duplicate email
 *  4. Insert the request with a unique setup token
 *  5. Email the ADMIN about the new request
 *  6. Email the USER with a password-setup link
 *  7. Return JSON {success, message}
 *
 * Password setup link: public/reset_password.php?token=<token>
 * (uses the existing reset_password.php already in the project)
 */

header('Content-Type: application/json');
session_start();
include 'includes/config.php';

// ── Config — update these ────────────────────────────────────────────────────
define('ADMIN_EMAIL',  'admin@iorpms.health');   // where admin notifications go
define('FROM_EMAIL',   'noreply@iorpms.health');  // sender address
define('FROM_NAME',    'IORPMS Platform');
define('APP_URL',      'https://iorpms.health');  // production URL (no trailing slash)

// ── Only accept POST ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// ── Helper: safe string ───────────────────────────────────────────────────────
function clean(string $v): string {
    return htmlspecialchars(strip_tags(trim($v)), ENT_QUOTES, 'UTF-8');
}

// ── Collect & validate ────────────────────────────────────────────────────────
$first_name  = clean($_POST['first_name']  ?? '');
$last_name   = clean($_POST['last_name']   ?? '');
$clinic_name = clean($_POST['clinic_name'] ?? '');
$email       = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$phone       = clean($_POST['phone']       ?? '');
$country     = clean($_POST['country']     ?? '');
$plan        = clean($_POST['plan']        ?? 'professional');
$lang        = in_array($_POST['lang'] ?? 'en', ['en','fr','pt']) ? $_POST['lang'] : 'en';

$errors = [];
if (!$first_name)                        $errors[] = 'First name is required.';
if (!$last_name)                         $errors[] = 'Last name is required.';
if (!$clinic_name)                       $errors[] = 'Clinic/Organisation is required.';
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
if (!$phone)                             $errors[] = 'Phone number is required.';
if (!$country)                           $errors[] = 'Country is required.';

if ($errors) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// ── Ensure demo_requests table exists ────────────────────────────────────────
$conn->query("
    CREATE TABLE IF NOT EXISTS demo_requests (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        first_name   VARCHAR(80)  NOT NULL,
        last_name    VARCHAR(80)  NOT NULL,
        clinic_name  VARCHAR(160) NOT NULL,
        email        VARCHAR(120) NOT NULL,
        phone        VARCHAR(40)  NOT NULL,
        country      VARCHAR(80)  NOT NULL,
        plan         VARCHAR(40)  DEFAULT 'professional',
        token        VARCHAR(64)  NOT NULL,
        status       ENUM('pending','approved','rejected') DEFAULT 'pending',
        created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
        notified_at  TIMESTAMP    NULL,
        INDEX idx_email (email),
        INDEX idx_token (token)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// ── Duplicate check ───────────────────────────────────────────────────────────
$dupStmt = $conn->prepare("SELECT id, status FROM demo_requests WHERE email = ? LIMIT 1");
$dupStmt->bind_param('s', $email);
$dupStmt->execute();
$dup = $dupStmt->get_result()->fetch_assoc();
$dupStmt->close();

if ($dup) {
    $msgs = [
        'en' => 'This email address already has a pending request. We\'ll be in touch soon!',
        'fr' => 'Cette adresse email a déjà une demande en cours. Nous vous contacterons bientôt !',
        'pt' => 'Este endereço de email já tem um pedido pendente. Entraremos em contato em breve!',
    ];
    echo json_encode(['success' => false, 'message' => $msgs[$lang] ?? $msgs['en']]);
    exit;
}

// ── Generate a secure token ───────────────────────────────────────────────────
$token = bin2hex(random_bytes(32));   // 64-char hex token

// ── Insert the request ────────────────────────────────────────────────────────
$ins = $conn->prepare(
    "INSERT INTO demo_requests (first_name, last_name, clinic_name, email, phone, country, plan, token)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);
$ins->bind_param('ssssssss', $first_name, $last_name, $clinic_name, $email, $phone, $country, $plan, $token);
if (!$ins->execute()) {
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
    exit;
}
$ins->close();

$fullName     = "$first_name $last_name";
$setupLink    = APP_URL . "/public/reset_password.php?token=" . urlencode($token);
$adminLink    = APP_URL . "/admin/demo_requests.php";   // future admin page

// ── Email helpers ─────────────────────────────────────────────────────────────
function sendMail(string $to, string $toName, string $subject, string $htmlBody): bool {
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . FROM_NAME . " <" . FROM_EMAIL . ">\r\n";
    $headers .= "Reply-To: " . FROM_EMAIL . "\r\n";
    $headers .= "X-Mailer: PHP/" . PHP_VERSION;
    return mail($to, $subject, $htmlBody, $headers);
}

function emailWrap(string $title, string $body): string {
    return <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>
  body{font-family:Arial,sans-serif;background:#f4f7fb;margin:0;padding:20px;}
  .box{max-width:560px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;
       box-shadow:0 4px 20px rgba(0,0,0,.1);}
  .header{background:#2C3162;color:#fff;padding:28px 32px;}
  .header h1{margin:0;font-size:1.3rem;}
  .body{padding:28px 32px;color:#263238;font-size:.95rem;line-height:1.7;}
  .btn{display:inline-block;background:#82b543;color:#fff !important;padding:12px 28px;
       border-radius:8px;text-decoration:none;font-weight:700;margin:16px 0;}
  .footer{background:#f4f7fb;padding:16px 32px;font-size:.78rem;color:#888;text-align:center;}
  table{width:100%;border-collapse:collapse;margin:12px 0;}
  td{padding:8px 10px;border:1px solid #dde3f0;font-size:.88rem;}
  td:first-child{font-weight:700;background:#f9f9f9;width:35%;}
</style>
</head>
<body><div class="box">
  <div class="header"><h1>IORPMS — $title</h1></div>
  <div class="body">$body</div>
  <div class="footer">© 2025 IORPMS · support@iorpms.health</div>
</div></body></html>
HTML;
}

// ── 1. Email to ADMIN ─────────────────────────────────────────────────────────
$planLabel = ['starter'=>'Clinic Basic','professional'=>'Clinic Pro','enterprise'=>'Network / Government'][$plan] ?? $plan;
$adminBody = emailWrap('New Demo Request', "
<p>A new demo / access request has been submitted:</p>
<table>
  <tr><td>Name</td><td>$fullName</td></tr>
  <tr><td>Clinic</td><td>$clinic_name</td></tr>
  <tr><td>Email</td><td>$email</td></tr>
  <tr><td>Phone</td><td>$phone</td></tr>
  <tr><td>Country</td><td>$country</td></tr>
  <tr><td>Plan</td><td>$planLabel</td></tr>
</table>
<p>The user has been sent an automatic password-setup link. You can review all requests in the admin panel:</p>
<a href='$adminLink' class='btn'>View Demo Requests</a>
<p style='font-size:.85rem;color:#888;'>Setup link sent to user: <a href='$setupLink'>$setupLink</a></p>
");
sendMail(ADMIN_EMAIL, 'IORPMS Admin', "New Demo Request — $fullName ($clinic_name)", $adminBody);

// ── 2. Email to USER ──────────────────────────────────────────────────────────
$userSubjects = [
    'en' => 'Your IORPMS Account — Set Up Your Password',
    'fr' => 'Votre compte IORPMS — Créez votre mot de passe',
    'pt' => 'A sua conta IORPMS — Crie a sua palavra-passe',
];
$userIntros = [
    'en' => "Hi $first_name,<br><br>Thank you for requesting access to <strong>IORPMS</strong>. Your account has been created for <strong>$clinic_name</strong>.<br><br>Click the button below to set your password and log in:",
    'fr' => "Bonjour $first_name,<br><br>Merci d'avoir demandé l'accès à <strong>IORPMS</strong>. Votre compte a été créé pour <strong>$clinic_name</strong>.<br><br>Cliquez sur le bouton ci-dessous pour définir votre mot de passe et vous connecter :",
    'pt' => "Olá $first_name,<br><br>Obrigado por solicitar acesso ao <strong>IORPMS</strong>. A sua conta foi criada para <strong>$clinic_name</strong>.<br><br>Clique no botão abaixo para definir a sua palavra-passe e iniciar sessão:",
];
$btnLabels = ['en'=>'Set Up My Password','fr'=>'Définir mon mot de passe','pt'=>'Definir a minha palavra-passe'];
$noteTexts = [
    'en' => 'This link expires in 48 hours. If you did not request this, please ignore this email.',
    'fr' => 'Ce lien expire dans 48 heures. Si vous n\'avez pas fait cette demande, ignorez cet email.',
    'pt' => 'Este link expira em 48 horas. Se não fez este pedido, ignore este email.',
];

$intro   = $userIntros[$lang]   ?? $userIntros['en'];
$btnLbl  = $btnLabels[$lang]    ?? $btnLabels['en'];
$noteText= $noteTexts[$lang]    ?? $noteTexts['en'];
$subject = $userSubjects[$lang] ?? $userSubjects['en'];

$userBody = emailWrap('Welcome to IORPMS', "
$intro
<br>
<a href='$setupLink' class='btn'>$btnLbl</a>
<br>
<p style='font-size:.83rem;color:#888;'>$noteText</p>
<p style='font-size:.83rem;color:#888;'>Or copy this link: <a href='$setupLink'>$setupLink</a></p>
");
sendMail($email, $fullName, $subject, $userBody);

// ── Done ──────────────────────────────────────────────────────────────────────
$successMsgs = [
    'en' => 'Request submitted! Check your email for your password setup link.',
    'fr' => 'Demande soumise ! Vérifiez votre email pour créer votre mot de passe.',
    'pt' => 'Pedido enviado! Verifique o seu email para criar a sua palavra-passe.',
];
echo json_encode([
    'success' => true,
    'message' => $successMsgs[$lang] ?? $successMsgs['en'],
]);

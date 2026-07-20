<?php
/**
 * demo_request.php
 * =================
 * Handles the demo/access request form submitted from index.php.
 *
 * Flow:
 *  1. Validate + sanitise POST data (country comes from the `countries` table)
 *  2. Ensure demo tables exist (countries, demo_requests, login_logs, flags)
 *  3. Check for duplicate email
 *  4. Create a REAL user account immediately with an AUTO-GENERATED password
 *     - must_change_password = 1  → forced to change it on first login
 *     - password never expires, account stays in the database
 *  5. Email the USER their username + temporary password
 *     (they are also told they may get a WhatsApp message from +254722427721)
 *  6. Notify BOTH admins (admin@sitti.site + sittilyani@gmail.com) with a
 *     one-click "Send credentials via WhatsApp" wa.me link
 *  7. Return JSON {success, message}
 */

header('Content-Type: application/json');
session_start();
include 'includes/config.php';
include 'includes/demo_schema.php';
include 'includes/mail_helper.php';

// ── Only accept POST ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

ensureDemoSchema($conn);

// ── Helper: safe string ──────────────────────────────────────────────────────
function clean(string $v): string {
    return htmlspecialchars(strip_tags(trim($v)), ENT_QUOTES, 'UTF-8');
}

// ── Collect & validate ───────────────────────────────────────────────────────
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

// Country must exist in the countries table
if ($country) {
    $cs = $conn->prepare("SELECT id FROM countries WHERE name = ? LIMIT 1");
    $cs->bind_param('s', $country);
    $cs->execute();
    if (!$cs->get_result()->fetch_assoc()) $errors[] = 'Please choose a country from the list.';
    $cs->close();
}

if ($errors) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// ── Duplicate check (demo_requests + tblusers) ───────────────────────────────
$dupStmt = $conn->prepare("SELECT id FROM demo_requests WHERE email = ? LIMIT 1");
$dupStmt->bind_param('s', $email);
$dupStmt->execute();
$dup = $dupStmt->get_result()->fetch_assoc();
$dupStmt->close();

if (!$dup) {
    $dupStmt = $conn->prepare("SELECT user_id FROM tblusers WHERE email = ? LIMIT 1");
    $dupStmt->bind_param('s', $email);
    $dupStmt->execute();
    $dup = $dupStmt->get_result()->fetch_assoc();
    $dupStmt->close();
}

if ($dup) {
    $msgs = [
        'en' => 'This email address already has an account or pending request. Try logging in, or contact support@sitti.site.',
        'fr' => 'Cette adresse email a déjà un compte ou une demande en cours. Essayez de vous connecter ou contactez support@sitti.site.',
        'pt' => 'Este endereço de email já tem uma conta ou pedido pendente. Tente iniciar sessão ou contacte support@sitti.site.',
    ];
    echo json_encode(['success' => false, 'message' => $msgs[$lang] ?? $msgs['en']]);
    exit;
}

// ── Generate credentials ─────────────────────────────────────────────────────
$token = bin2hex(random_bytes(32));  // audit token kept in demo_requests

// Readable temporary password, e.g. "Nairobi-7K2M-94"
function generateTempPassword(): string {
    $words = ['Simba','Twiga','Chui','Nyati','Tembo','Kifaru','Duma','Pundamilia','Korongo','Mamba'];
    $word  = $words[random_int(0, count($words)-1)];
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no confusing 0/O/1/I
    $rand  = '';
    for ($i = 0; $i < 4; $i++) $rand .= $chars[random_int(0, strlen($chars)-1)];
    return $word . '-' . $rand . '-' . random_int(10, 99);
}
$tempPassword = generateTempPassword();
$passwordHash = password_hash($tempPassword, PASSWORD_DEFAULT);

// Unique username from the email prefix
$base = strtolower(preg_replace('/[^a-z0-9]/i', '', explode('@', $email)[0]));
if ($base === '') $base = 'demo';
$username = $base; $suffix = 0;
while (true) {
    $u = $conn->prepare("SELECT user_id FROM tblusers WHERE username = ? LIMIT 1");
    $u->bind_param('s', $username);
    $u->execute();
    $exists = $u->get_result()->fetch_assoc();
    $u->close();
    if (!$exists) break;
    $suffix++;
    $username = $base . $suffix;
}

// ── Create the demo user account ─────────────────────────────────────────────
// must_change_password = 1  → forced change on first login
// Password does NOT expire and the account remains in the database.
$gender = 'Not specified';
$role   = 'Demo';
$ins = $conn->prepare(
    "INSERT INTO tblusers (username, first_name, last_name, email, password, gender, mobile, userrole, must_change_password, is_demo)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 1)"
);
$ins->bind_param('ssssssss', $username, $first_name, $last_name, $email, $passwordHash, $gender, $phone, $role);
if (!$ins->execute()) {
    echo json_encode(['success' => false, 'message' => 'Could not create your demo account. Please try again.']);
    exit;
}
$newUserId = $conn->insert_id;
$ins->close();

// ── Record the demo request ──────────────────────────────────────────────────
$req = $conn->prepare(
    "INSERT INTO demo_requests (first_name, last_name, clinic_name, email, phone, country, plan, token, user_id, status)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved')"
);
$req->bind_param('ssssssssi', $first_name, $last_name, $clinic_name, $email, $phone, $country, $plan, $token, $newUserId);
$req->execute();
$req->close();

$fullName  = "$first_name $last_name";
$loginLink = APP_URL . "/public/login.php";

// ── Email sending is handled by includes/mail_helper.php ────────────────────
// sendAppMail() tries PHPMailer/SMTP first, then falls back to PHP mail().

function emailWrap(string $title, string $body): string {
    $year = date('Y');
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
  .cred{background:#f4f7fb;border:1px dashed #2C3162;border-radius:8px;padding:14px 18px;
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

// ── 1. Email to USER (credentials) ───────────────────────────────────────────
$userSubjects = [
    'en' => 'Your EasyFlow-L Demo Account — Login Credentials',
    'fr' => 'Votre compte démo EasyFlow-L — Identifiants de connexion',
    'pt' => 'A sua conta demo EasyFlow-L — Credenciais de acesso',
];
$userIntros = [
    'en' => "Hi $first_name,<br><br>Welcome to <strong>EasyFlow-L</strong>! Your demo account for <strong>$clinic_name</strong> is ready. Use these credentials to log in:",
    'fr' => "Bonjour $first_name,<br><br>Bienvenue sur <strong>EasyFlow-L</strong> ! Votre compte démo pour <strong>$clinic_name</strong> est prêt. Utilisez ces identifiants pour vous connecter :",
    'pt' => "Olá $first_name,<br><br>Bem-vindo ao <strong>EasyFlow-L</strong>! A sua conta demo para <strong>$clinic_name</strong> está pronta. Use estas credenciais para iniciar sessão:",
];
$changeNotes = [
    'en' => "For security, you will be asked to <strong>change this password the first time you log in</strong>. After that you can explore the full system. You may also receive a WhatsApp message from <strong>+254 722 427 721</strong> with these credentials and training links.",
    'fr' => "Pour votre sécurité, vous devrez <strong>changer ce mot de passe lors de votre première connexion</strong>. Vous pourrez ensuite explorer tout le système. Vous pouvez aussi recevoir un message WhatsApp du <strong>+254 722 427 721</strong>.",
    'pt' => "Por segurança, ser-lhe-á pedido para <strong>alterar esta palavra-passe no primeiro início de sessão</strong>. Depois disso pode explorar todo o sistema. Também pode receber uma mensagem WhatsApp do <strong>+254 722 427 721</strong>.",
];
$btnLabels = ['en'=>'Log In Now','fr'=>'Se connecter','pt'=>'Iniciar sessão'];

$intro   = $userIntros[$lang]   ?? $userIntros['en'];
$note    = $changeNotes[$lang]  ?? $changeNotes['en'];
$btnLbl  = $btnLabels[$lang]    ?? $btnLabels['en'];
$subject = $userSubjects[$lang] ?? $userSubjects['en'];

$trainingLink = APP_URL . "/trainings/index.php";
$userBody = emailWrap('Welcome!', "
$intro
<div class='cred'>
  Username: <strong>$username</strong><br>
  Temporary password: <strong>$tempPassword</strong>
</div>
<a href='$loginLink' class='btn'>$btnLbl</a>
<p style='font-size:.86rem;'>$note</p>
<p style='font-size:.86rem;'>Self-learning: watch our training tutorials on YouTube —
   <a href='https://www.youtube.com/@EasyFlow-LTutorialsc'>youtube.com/@EasyFlow-LTutorialsc</a>
   or visit the <a href='$trainingLink'>Training Videos</a> page.</p>
");
$userMailResult = sendAppMail($email, $fullName, $subject, $userBody);

// ── 2. Notify BOTH admins ────────────────────────────────────────────────────
// One-click WhatsApp link so the admin (+254722427721) can forward credentials
$waText = rawurlencode(
    "Hello $first_name, welcome to EasyFlow-L!\n\n" .
    "Your demo account is ready:\n" .
    "Login: $loginLink\n" .
    "Username: $username\n" .
    "Temporary password: $tempPassword\n\n" .
    "You'll be asked to change the password on first login.\n" .
    "Training videos: https://www.youtube.com/@EasyFlow-LTutorialsc"
);
$waPhone = preg_replace('/\D/', '', $phone);
$waLink  = "https://wa.me/$waPhone?text=$waText";

// ── One-click "notify our team" WhatsApp links ───────────────────────────────
// Addressed to each internal support number (ADMIN_WHATSAPP) so whoever opens
// the admin email can forward the new-lead summary with a single click.
$notifyText = rawurlencode(
    "New EasyFlow-L demo request:\n" .
    "Name: $fullName\n" .
    "Clinic: $clinic_name\n" .
    "Email: $email\n" .
    "Phone: $phone\n" .
    "Country: $country\n" .
    "Username: $username"
);
$adminWaButtons = '';
foreach ((array)ADMIN_WHATSAPP as $adminNumber) {
    $num = preg_replace('/\D/', '', $adminNumber);
    if (!$num) continue;
    $adminWaButtons .= "<a href='https://wa.me/$num?text=$notifyText' class='btn' style='margin-right:8px;'>Notify +$num</a> ";
}

$planLabel = ['starter'=>'Clinic Basic','professional'=>'Clinic Pro','enterprise'=>'Network / Government'][$plan] ?? $plan;
$adminBody = emailWrap('New Demo Request', "
<p>A new demo account has been created automatically:</p>
<table>
  <tr><td>Name</td><td>$fullName</td></tr>
  <tr><td>Clinic</td><td>$clinic_name</td></tr>
  <tr><td>Email</td><td>$email</td></tr>
  <tr><td>Phone</td><td>$phone</td></tr>
  <tr><td>Country</td><td>$country</td></tr>
  <tr><td>Plan</td><td>$planLabel</td></tr>
  <tr><td>Username</td><td>$username</td></tr>
</table>
<p>User email delivery: <strong>" . ($userMailResult['success'] ? 'Sent (' . $userMailResult['method'] . ')' : 'FAILED — ' . htmlspecialchars($userMailResult['error'] ?? 'unknown error')) . "</strong></p>
<a href='$waLink' class='btn'>Send credentials via WhatsApp</a><br><br>
$adminWaButtons
<p style='font-size:.85rem;color:#888;margin-top:14px;'>Analytics:
   <a href='" . APP_URL . "/superadmin/demo_analytics.php'>Demo Analytics Dashboard</a></p>
");
$adminMailResults = [];
foreach (ADMIN_EMAILS as $adminEmail) {
    $adminMailResults[$adminEmail] = sendAppMail($adminEmail, 'EasyFlow-L Admin', "New Demo Request — $fullName ($clinic_name, $country)", $adminBody);
}

// ── Done ─────────────────────────────────────────────────────────────────────
if ($userMailResult['success']) {
    $successMsgs = [
        'en' => '✅ Email sent! Check your inbox for your username and temporary password. You may also get a WhatsApp message from +254 722 427 721.',
        'fr' => '✅ Email envoyé ! Vérifiez votre boîte de réception pour votre nom d\'utilisateur et mot de passe temporaire. Vous pouvez aussi recevoir un message WhatsApp du +254 722 427 721.',
        'pt' => '✅ Email enviado! Verifique a sua caixa de entrada para o nome de utilizador e a palavra-passe temporária. Também pode receber uma mensagem WhatsApp do +254 722 427 721.',
    ];
} else {
    // Email could not be delivered — credentials will arrive via WhatsApp instead
    $successMsgs = [
        'en' => '⚠️ Account created, but the confirmation email could not be delivered. You will receive your login credentials shortly via WhatsApp from +254 722 427 721 instead.',
        'fr' => '⚠️ Compte créé, mais l\'email de confirmation n\'a pas pu être envoyé. Vous recevrez vos identifiants via WhatsApp du +254 722 427 721.',
        'pt' => '⚠️ Conta criada, mas o email de confirmação não pôde ser entregue. Receberá as suas credenciais via WhatsApp do +254 722 427 721.',
    ];
}
echo json_encode([
    'success'    => true,
    'message'    => $successMsgs[$lang] ?? $successMsgs['en'],
    'email_sent' => (bool)$userMailResult['success'],
    'email_method' => $userMailResult['success'] ? ($userMailResult['method'] ?? null) : null,
    'email_error'  => $userMailResult['success'] ? null : ($userMailResult['error'] ?? 'unknown error'),
]);

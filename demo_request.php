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
 *  4. Save the request as PENDING (an admin approves it; see includes/demo_approval.php)
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
include 'includes/demo_approval.php';

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

// ── Duplicate check: one email = one demo request, ever ──────────────────────
// An EXPIRED demo cannot make a new request, but its owner can ask the developer for reactivation
// (submitting the form again records that request and alerts the admins).
expireDemoAccounts($conn);
$dupStmt = $conn->prepare("SELECT id, status, first_name, last_name, clinic_name, phone, country, reactivation_requested_at FROM demo_requests WHERE email = ? LIMIT 1");
$dupStmt->bind_param('s', $email);
$dupStmt->execute();
$dup = $dupStmt->get_result()->fetch_assoc();
$dupStmt->close();

if ($dup && $dup['status'] === 'expired') {
    if (empty($dup['reactivation_requested_at'])) {
        $conn->query("UPDATE demo_requests SET reactivation_requested_at = NOW() WHERE id = " . (int)$dup['id']);
        $adminBody = demoEmailWrap('Demo reactivation requested', "
<p>The demo below has <strong>expired</strong> and its owner has asked for it to be reactivated:</p>
<table>
  <tr><td>Name</td><td>{$dup['first_name']} {$dup['last_name']}</td></tr>
  <tr><td>Clinic</td><td>{$dup['clinic_name']}</td></tr>
  <tr><td>Email</td><td>$email</td></tr>
  <tr><td>Phone</td><td>{$dup['phone']}</td></tr>
  <tr><td>Country</td><td>{$dup['country']}</td></tr>
</table>
<p>Clicking <strong>Reactivate</strong> gives a new " . DEMO_DAYS . " days and sends the credentials by email and WhatsApp.</p>
<a href='" . APP_URL . "/superadmin/demo_analytics.php' class='btn'>Review &amp; reactivate</a>
");
        foreach (ADMIN_EMAILS as $adminEmail) {
            sendAppMail($adminEmail, 'EasyFlow-L Admin', "Demo reactivation requested — {$dup['first_name']} {$dup['last_name']} ({$dup['clinic_name']})", $adminBody);
        }
        $msgs = [
            'en' => 'Your 30-day demo has expired. A reactivation request has been sent to the developer; you will receive new credentials by email and WhatsApp once it is approved.',
            'fr' => 'Votre démo de 30 jours a expiré. Une demande de réactivation a été envoyée au développeur ; vous recevrez de nouveaux identifiants par email et WhatsApp une fois approuvée.',
            'pt' => 'A sua demo de 30 dias expirou. Foi enviado um pedido de reativação ao programador; receberá novas credenciais por email e WhatsApp após aprovação.',
        ];
    } else {
        $msgs = [
            'en' => 'Your demo has expired and a reactivation request is already with the developer. Please wait for approval, or contact support@sitti.site.',
            'fr' => 'Votre démo a expiré et une demande de réactivation est déjà chez le développeur. Veuillez patienter ou contacter support@sitti.site.',
            'pt' => 'A sua demo expirou e já existe um pedido de reativação com o programador. Aguarde a aprovação ou contacte support@sitti.site.',
        ];
    }
    echo json_encode(['success' => false, 'message' => $msgs[$lang] ?? $msgs['en']]);
    exit;
}

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

// ── Record the request (pending - an admin approves it) ─────────────────────
// No account or password exists yet. When an admin clicks Approve in
// superadmin/demo_analytics.php the account is created and the credentials are
// sent by email and WhatsApp (see includes/demo_approval.php). The demo then
// runs for 30 days and is marked 'expired'.
$token = bin2hex(random_bytes(32));
$req = $conn->prepare(
    "INSERT INTO demo_requests (first_name, last_name, clinic_name, email, phone, country, plan, token, lang, status)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
);
$req->bind_param('sssssssss', $first_name, $last_name, $clinic_name, $email, $phone, $country, $plan, $token, $lang);
if (!$req->execute()) {
    echo json_encode(['success' => false, 'message' => 'Could not save your request. Please try again.']);
    exit;
}
$req->close();

$fullName = "$first_name $last_name";

// ── 1. Acknowledge the requester ─────────────────────────────────────────────
$ackSubjects = [
    'en' => 'We received your EasyFlow-L demo request',
    'fr' => 'Nous avons reçu votre demande de démo EasyFlow-L',
    'pt' => 'Recebemos o seu pedido de demo EasyFlow-L',
];
$ackBodies = [
    'en' => "Hi $first_name,<br><br>Thank you for requesting an <strong>EasyFlow-L</strong> demo for <strong>$clinic_name</strong>. Our team will review it shortly. Once approved, your login credentials will be sent to you by <strong>email and WhatsApp</strong>. The demo is valid for 30 days.",
    'fr' => "Bonjour $first_name,<br><br>Merci d'avoir demandé une démo <strong>EasyFlow-L</strong> pour <strong>$clinic_name</strong>. Notre équipe l'examinera sous peu. Une fois approuvée, vos identifiants vous seront envoyés par <strong>email et WhatsApp</strong>. La démo est valable 30 jours.",
    'pt' => "Olá $first_name,<br><br>Obrigado por pedir uma demo do <strong>EasyFlow-L</strong> para <strong>$clinic_name</strong>. A nossa equipa irá analisá-lo em breve. Depois de aprovado, as suas credenciais serão enviadas por <strong>email e WhatsApp</strong>. A demo é válida por 30 dias.",
];
$ackResult = sendAppMail($email, $fullName, $ackSubjects[$lang], demoEmailWrap('Request received', $ackBodies[$lang]));

// ── 2. Notify the admins with a link to review and approve ──────────────────
$planLabel = ['starter'=>'Clinic Basic','professional'=>'Clinic Pro','enterprise'=>'Network / Government'][$plan] ?? $plan;
$adminBody = demoEmailWrap('New Demo Request — approval needed', "
<p>A new demo request is waiting for approval:</p>
<table>
  <tr><td>Name</td><td>$fullName</td></tr>
  <tr><td>Clinic</td><td>$clinic_name</td></tr>
  <tr><td>Email</td><td>$email</td></tr>
  <tr><td>Phone</td><td>$phone</td></tr>
  <tr><td>Country</td><td>$country</td></tr>
  <tr><td>Plan</td><td>$planLabel</td></tr>
</table>
<p>Clicking <strong>Approve</strong> creates the account and sends the credentials to the requester by email and WhatsApp.</p>
<a href='" . APP_URL . "/superadmin/demo_analytics.php' class='btn'>Review &amp; approve</a>
");
foreach (ADMIN_EMAILS as $adminEmail) {
    sendAppMail($adminEmail, 'EasyFlow-L Admin', "Demo request awaiting approval — $fullName ($clinic_name, $country)", $adminBody);
}

// ── Done ─────────────────────────────────────────────────────────────────────
$successMsgs = [
    'en' => '✅ Request received! Once our team approves it, your login credentials will be sent to you by email and WhatsApp (+254 722 427 721).',
    'fr' => '✅ Demande reçue ! Dès son approbation par notre équipe, vos identifiants vous seront envoyés par email et WhatsApp (+254 722 427 721).',
    'pt' => '✅ Pedido recebido! Assim que a nossa equipa o aprovar, as suas credenciais serão enviadas por email e WhatsApp (+254 722 427 721).',
];
echo json_encode([
    'success'    => true,
    'message'    => $successMsgs[$lang] ?? $successMsgs['en'],
    'email_sent' => (bool)$ackResult['success'],
]);

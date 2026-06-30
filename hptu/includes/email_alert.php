<?php
// includes/email_alert.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

require '../vendor/autoload.php'; // PHPMailer

function send_email($to, $subject, $body, $is_html = true) {
    $mail = new PHPMailer(true);
    try {
        // Server settings (SMTP)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';  // Change to your SMTP
        $mail->SMTPAuth   = true;
        $mail->Username   = 'your-email@gmail.com';     // CHANGE
        $mail->Password   = 'your-app-password';         // CHANGE
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('no-reply@hptu.go.ke', 'HPTU LMIS');
        $mail->addAddress($to);

        // Content
        $mail->isHTML($is_html);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email failed: {$mail->ErrorInfo}");
        return false;
    }
}
?>
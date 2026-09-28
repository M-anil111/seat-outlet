<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../../phpmailer/src/SMTP.php';
require_once __DIR__ . '/../../phpmailer/src/Exception.php';

function admin_send_password_reset_email(string $toEmail, string $toName, string $resetLink): bool {
    $smtpUser = getenv('SMTP_USER');
    $smtpPass = getenv('SMTP_PASS');
    if ($smtpUser === false || $smtpUser === '' || $smtpPass === false || $smtpPass === '') {
        error_log('admin_send_password_reset_email: SMTP_USER/SMTP_PASS not set.');
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp-relay.brevo.com';
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUser;
        $mail->Password = $smtpPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        $mail->Timeout = 30;

        $mail->setFrom('support@seatoutlet.com', 'Seat Outlet Admin');
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = 'Reset your Seat Outlet Admin password';
        $safeLink = htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8');
        $mail->Body = <<<EOD
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Reset your password</title></head>
<body style="margin:0; padding:20px 0; font-family: Arial, sans-serif; background:#f6f6f6;">
  <table align="center" border="0" cellpadding="0" cellspacing="0" width="600"
         style="background:#ffffff; border-radius:8px; overflow:hidden;">
    <tr>
      <td align="center" bgcolor="#ffffff" style="padding:40px 20px 20px; border-bottom:1px #e1e1e1 solid;">
        <img src="https://beta.seatoutlet.com/images/seatoutlet.png" alt="Seat Outlet" style="display:block; max-width:220px;">
      </td>
    </tr>
    <tr>
      <td style="padding:30px; font-size:15px; color:#333; line-height:1.6;">
        Hello {$toName},<br><br>
        We received a request to reset the password for your Seat Outlet Admin account.
        This link is valid for 30 minutes and can only be used once.
      </td>
    </tr>
    <tr>
      <td align="center" style="padding:0 30px 30px;">
        <a href="{$safeLink}" target="_blank"
           style="background:#2556e0; color:#ffffff; text-decoration:none; font-size:16px;
                  padding:12px 24px; border-radius:6px; display:inline-block;">
           Reset Password
        </a>
      </td>
    </tr>
    <tr>
      <td style="padding:0 30px 30px; font-size:13px; color:#777;">
        If you didn't request this, you can safely ignore this email.
      </td>
    </tr>
    <tr>
      <td bgcolor="#2556e0" style="padding:20px; font-size:13px; color:#fff; text-align:center;">
        Seat Outlet Admin
      </td>
    </tr>
  </table>
</body>
</html>
EOD;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('admin_send_password_reset_email failed: ' . $e->getMessage());
        return false;
    }
}

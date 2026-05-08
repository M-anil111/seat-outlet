<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';
require 'phpmailer/src/Exception.php';
include 'db/config.php';


/* =========================
   BASIC REQUEST CHECK
========================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    exit;
}

/* =========================
   HONEYPOT
========================= */
if (!empty($_POST['company'])) {
    http_response_code(204);
    exit;
}

/* =========================
   HELPERS
========================= */
function clean($v) {
    return htmlspecialchars(trim((string)$v), ENT_QUOTES, 'UTF-8');
}
function required($v) {
    return isset($v) && trim($v) !== '';
}


// ========== GET FORM DATA ==========
$fname = $_POST['fname'] ?? '';
$lname = $_POST['lname'] ?? '';
$email = $_POST['email'] ?? '';

/* =========================
   TRACKING INFORMATION
========================= */
$page_url   = $_SERVER['HTTP_REFERER'] ?? 'Unknown';
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$browser    = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

$country = 'Unknown';
if (!empty($ip_address) && $ip_address !== '127.0.0.1') {
    $geo = @unserialize(@file_get_contents("http://ip-api.com/php/" . urlencode($ip_address)));
    if (is_array($geo) && ($geo['status'] ?? '') === 'success') {
        $country = $geo['country'] ?? 'Unknown';
    }
}

/* =========================
   MAILER
========================= */
$mail = new PHPMailer(true);

$sent = false;

try {

    $mail->isSMTP();
    $mail->Host       = 'smtp-relay.brevo.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'a9aa64001@smtp-brevo.com';
    $mail->Password   = 'xsmtpsib-41690a322b8ba6220e63d2fea8b972c356987134281a173e9cb28e57652d9ed5-H7xKvwOtfy0izOuH';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->CharSet = 'UTF-8';
    $mail->Timeout = 30;

    /* =========================
       ADMIN EMAIL
    ========================= */
    $mail->setFrom('support@seatoutlet.com', 'Seat Outlet');
    //$mail->addAddress('jay@jaymehta.co');
    $mail->addAddress('tarun@mindshare.consulting');
    $mail->addReplyTo($email, $fname . ' ' . $lname);

    $mail->isHTML(true);
    $mail->Subject = 'New Newsletter Subscription Received';
    
    $mail->Body = <<<EOD
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>New Newsletter Subscription</title>
</head>
<body style="margin:0; padding:20px 0; font-family: Arial, sans-serif; background:#f6f6f6;">
  <table align="center" border="0" cellpadding="0" cellspacing="0" width="600" 
         style="background:#ffffff; border-radius:8px; overflow:hidden;">
    
    <!-- Logo -->
    <tr>
      <td align="center" bgcolor="#ffffff" style="padding:40px 20px 20px; border-bottom:1px #e1e1e1 solid;">
        <a href="https://beta.seatoutlet.com" title="Seat Outlet">
          <img src="https://beta.seatoutlet.com/images/seatoutlet.png" 
               alt="Seat Outlet" style="display:block; max-width:220px;">
        </a>
      </td>
    </tr>
    
    <!-- Greeting -->
    <tr>
      <td align="center" style="padding:40px 30px 10px 30px; font-size:20px; font-weight:bold; color:#333;">
         Hello Admin, 
      </td>
    </tr>
    
    <!-- Intro -->
    <tr>
      <td align="center" style="padding:0 30px 20px 30px; font-size:15px; color:#555;">
        A new user has subscribed to the newsletter on your website.
      </td>
    </tr>
    
    <!-- Inquiry Details -->
    <tr>
      <td style="padding:0 30px 20px 30px;">
      <h4 style="font-size:16px; color:#333; margin-bottom:10px;">Lead Information:</h4>
        <table width="100%" cellpadding="6" cellspacing="0" style="font-size:14px; color:#333;">
          <tr><td style="font-weight:bold;" width="35%">First Name:</td><td>{$fname}</td></tr>
          <tr><td style="font-weight:bold;" width="35%">Last Name:</td><td>{$lname}</td></tr>
          <tr><td style="font-weight:bold;">Email Address:</td><td><a href="mailto:{$email}" style="color:#2556e0;">{$email}</a></td></tr>
        </table>
      </td>
    </tr>

    <!-- Tracking Info -->
    <tr>
      <td style="padding:0 30px 20px 30px;">
        <h4 style="margin:20px 0 10px 0; font-size:16px; color:#2c463a;">Tracking Information:</h4>
        <table width="100%" cellpadding="6" cellspacing="0" style="font-size:14px; color:#333;">
          <tr><td style="font-weight:bold;" width="23%">Page URL:</td><td><a href="{$page_url}" style="color:#0066cc;">{$page_url}</a></td></tr>
          <tr><td style="font-weight:bold;" width="23%">IP Address:</td><td>{$ip_address}</td></tr>
          <tr><td style="font-weight:bold;">Browser:</td><td>{$browser}</td></tr>
          <tr><td style="font-weight:bold;">Country:</td><td>{$country}</td></tr>
        </table>
      </td>
    </tr>
    
   <!-- Footer -->
    <tr>
      <td align="center" bgcolor="#000" style="padding:15px 10px; border-top:1px solid #444;">
        <table align="center" border="0" cellpadding="0" cellspacing="0" style="margin:auto;">
          <tr>
            <td style="font-size:13px; color:#fff; white-space:nowrap;">
              Website Designed by
            </td>
            <td style="padding:0 3px;">
              <a href="https://www.jaymehta.co/" target="_blank" style="text-decoration:none;">
                <img src="https://beta.seatoutlet.com/images/jm.png"
                    alt="Jay Mehta Digital"
                    style="display:inline-block; vertical-align:middle; max-width:90px;">
              </a>
            </td>
            <td style="font-size:13px; color:#fff; white-space:nowrap;">
              | Developed & Maintained by
            </td>
            <td style="padding-left:5px;">
              <a href="https://www.mindshare.consulting/" target="_blank" style="text-decoration:none;">
                <img src="https://beta.seatoutlet.com/images/mindshare-logo.webp"
                    alt="Mindshare Consulting Inc"
                    style="display:inline-block; vertical-align:middle; max-width:90px; margin-top:-2px;">
              </a>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  
  </table>
</body>
</html>
EOD;

    $mail->send();
    $sent = true;

    /* =========================
       AUTO REPLY TO USER
    ========================= */
    $mail->clearAddresses();
    $mail->clearReplyTos();

    $mail->setFrom('no-reply@seatoutlet.com', 'Seat Outlet');
    $mail->addAddress($email, $fname . ' ' . $lname);

    $mail->Subject = 'Your request has been successfully submitted.';
    $mail->isHTML(true);

    $mail->Body = <<<EOD
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Thank you for subscribing to the Seat Outlet newsletter.</title>
</head>
<body style="margin:0; padding:20px 0; font-family: Arial, sans-serif; background:#f6f6f6;">
  <table align="center" border="0" cellpadding="0" cellspacing="0" width="600" 
         style="background:#ffffff; border-radius:8px; overflow:hidden;">
    
    <!-- Header -->
    <tr>
      <td align="center" bgcolor="#ffffff" style="padding:40px 20px 20px; border-bottom:1px #e1e1e1 solid;">
        <a href="https://beta.seatoutlet.com/" title="Seat Outlet">
            <img src="https://beta.seatoutlet.com/images/seatoutlet.png" 
                alt="Seat Outlet" style="display:block; max-width:220px;">
            </a>
      </td>
    </tr>

    <!-- Greeting -->
    <tr>
      <td style="padding:20px 30px; font-size:16px; color:#333;">
        Hello <strong>{$fname} {$lname}</strong>,
      </td>
    </tr>

    <!-- Message -->
    <tr>
      <td style="padding:0 30px 20px 30px; font-size:15px; color:#555; line-height:1.6;">
        Our team will review your message and get back to you shortly. <br><br>
        Meanwhile, explore exciting upcoming events happening near you. <br><br>
      </td>
    </tr>

    <!-- Call to Action -->
      <tr>
      <td align="center" style="padding:20px;">
        <a href="https://beta.seatoutlet.com/" target="_blank" 
           style="background:#1b3bb0; color:#ffffff; text-decoration:none; font-size:16px; 
                  padding:12px 24px; border-radius:5px; display:inline-block;">
           Visit Our Website
        </a>
      </td>
    </tr>

    <!-- Footer -->
    <tr>
      <td bgcolor="#2556e0" style="padding:20px; font-size:13px; color:#fff; text-align:center;">
        Best regards, <br>
        <strong>Seat Outlet</strong><br>
        Phone: <a href="tel:8505299455" style="color:#b5d3fb; text-decoration:none;">850-529-9455</a><br>
        Website: <a href="https://beta.seatoutlet.com/" style="color:#b5d3fb; text-decoration:none;">
          beta.seatoutlet.com/
        </a>
      </td>
    </tr>
  </table>
</body>
</html>
EOD;

    $mail->send();

} catch (Exception $e) {
    error_log($mail->ErrorInfo);
    http_response_code(500);
    exit;
}

/* =========================
   SEND TO Database
========================= */

$data = [  
  'first_name'                    => $_POST['fname'] ?? '',
  'last_name'                    => $_POST['lname'] ?? '',
  'email'                         => $_POST['email'] ?? '',
  'created_at'                     => date('Y-m-d H:i:s'),
  'page_url'                      => $page_url ?? '',
  'ip_address'                    => $ip_address ?? '',
  'country'                       => $country ?? '',
  'browser'                       => $browser ?? ''
];

$mysqli = MYSQLI;

$stmt = $mysqli->prepare("
  INSERT INTO newsletter_leads (
      first_name,
      last_name,
      email,
      created_at,
      page_url,
      ip_address,
      country,
      browser
  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
  "ssssssss",
  $data['first_name'],
  $data['last_name'],
  $data['email'],
  $data['created_at'],
  $data['page_url'],
  $data['ip_address'],
  $data['country'],
  $data['browser']
);

$success = $stmt->execute();

$stmt->close();



/* =========================
   REDIRECT
========================= */
if ($sent) {
    header('Location: https://beta.seatoutlet.com/thank-you');
    exit;
}

http_response_code(500);
exit;

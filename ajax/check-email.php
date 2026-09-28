<?php
include '../db/config.php';
include '../inc/constants.php';

header('Content-Type: application/json');

$email = trim((string) ($_POST['email'] ?? ''));
$recaptchaSecret  = RECAPTCHA_SECRET_KEY;
$recaptchaResponse = $_POST['token'] ?? $_POST['g-recaptcha-response'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["status" => "invalid"]);
    exit;
}

// Was file_get_contents() - relies on allow_url_fopen being enabled, which
// many managed/shared PHP hosts disable by default, and has no timeout, so
// a slow reCAPTCHA response would hang this request indefinitely. curl is
// what the rest of this app already uses for every other outbound call.
$ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query([
        'secret'   => $recaptchaSecret,
        'response' => $recaptchaResponse,
    ]),
    CURLOPT_TIMEOUT        => 10,
]);
$verify = curl_exec($ch);
curl_close($ch);

$captcha = $verify !== false ? json_decode($verify, true) : null;

if (empty($captcha['success'])) {
    echo json_encode(["status" => "recaptcha"]);
    exit;
}

$mysqli = MYSQLI;
$stmt = $mysqli->prepare("SELECT ID FROM newsletter_leads WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
$isDuplicate = $stmt->num_rows > 0;
$stmt->close();

echo json_encode(["status" => $isDuplicate ? "duplicate" : "success"]);
exit;

<?php 
include '../db/config.php';

header('Content-Type: application/json');

$email = $_POST['email'] ?? '';
$recaptchaSecret  = '6Lfnn9AsAAAAAONimemstyyc7lGqK_RuopuRitDf';
$recaptchaResponse = $_POST['token'] ?? $_POST['g-recaptcha-response'] ?? '';

$verify = file_get_contents(
    'https://www.google.com/recaptcha/api/siteverify?secret=' .
    urlencode($recaptchaSecret) .
    '&response=' . urlencode($recaptchaResponse)
);

$captcha = json_decode($verify, true);

if (empty($captcha['success'])) {
    echo json_encode(["status" => "recaptcha"]);
    exit;
}

$mysqli = MYSQLI;
$stmt = $mysqli->prepare("SELECT ID FROM newsletter_leads WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(["status" => "duplicate"]);
    exit;
}
$stmt->close();

echo json_encode(["status" => "success"]);
exit;
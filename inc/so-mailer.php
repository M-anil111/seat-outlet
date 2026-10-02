<?php
// Include-only file: answer 404 if it is requested directly over the web.
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
require_once __DIR__ . '/../phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/src/SMTP.php';
require_once __DIR__ . '/../phpmailer/src/Exception.php';

/**
 * PHPMailer that leaves the List-Unsubscribe value readable. PHPMailer would Q-encode a header value longer than about
 * 60 characters, and mailbox providers do not decode that form in List-Unsubscribe, so the one-click link would not work.
 */
class SoMailer extends \PHPMailer\PHPMailer\PHPMailer
{
    public function encodeHeader($str, $position = 'text')
    {
        if (preg_match('#^<https?://[\x21-\x7E]+>$#', (string) $str)) {
            return $str;
        }
        return parent::encodeHeader($str, $position);
    }
}

<?php
// Include-only file: answer 404 if it is requested directly over the web.
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/**
 * Small helpers shared by the public endpoints: who is calling, how often, and safe reads of query/form values.
 *
 *   soClientIp()                      the visitor's address (Cloudflare header only when the request really came from Cloudflare)
 *   soIpHash($ip)                     salted SHA-256, what we store instead of an address
 *   soRateHit($kind, $subject, $limit, $window)   true while under the limit; counts in the rate_limits table
 *   soQs($name, $default)             a string from $_GET (an array-typed parameter such as ?q[]=a yields the default, never a TypeError)
 *   soQsInt($name, $default, $min, $max)
 */

/** Cloudflare's published ranges (https://www.cloudflare.com/ips/). Extend with SO_TRUSTED_PROXIES="cidr,cidr" for another proxy. */
function soTrustedProxyRanges() {
    $ranges = ['173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22', '141.101.64.0/18', '108.162.192.0/18',
        '190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
        '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22', '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32',
        '2405:b500::/32', '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32'];
    $extra = (string) getenv('SO_TRUSTED_PROXIES');
    if ($extra !== '') {
        foreach (explode(',', $extra) as $c) { $c = trim($c); if ($c !== '') $ranges[] = $c; }
    }
    return $ranges;
}

function soIpInCidr($ip, $cidr) {
    if (strpos($cidr, '/') === false) $cidr .= (strpos($cidr, ':') === false ? '/32' : '/128');
    [$net, $bits] = explode('/', $cidr, 2);
    $a = @inet_pton($ip);
    $b = @inet_pton($net);
    if ($a === false || $b === false || strlen($a) !== strlen($b)) return false;
    $bits = (int) $bits;
    $bytes = intdiv($bits, 8);
    if ($bytes > 0 && substr($a, 0, $bytes) !== substr($b, 0, $bytes)) return false;
    $rem = $bits % 8;
    if ($rem === 0) return true;
    $mask = (0xFF << (8 - $rem)) & 0xFF;
    return (ord($a[$bytes]) & $mask) === (ord($b[$bytes]) & $mask);
}

/** The visitor's address. CF-Connecting-IP is believed only when the TCP peer is a trusted proxy (otherwise anyone could forge it). */
function soClientIp() {
    static $ip = null;
    if ($ip !== null) return $ip;
    $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $ip = $remote;
    $cf = (string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? '');
    if ($cf !== '' && filter_var($cf, FILTER_VALIDATE_IP) && filter_var($remote, FILTER_VALIDATE_IP)) {
        foreach (soTrustedProxyRanges() as $cidr) {
            if (soIpInCidr($remote, $cidr)) { $ip = $cf; break; }
        }
    }
    if (!filter_var($ip, FILTER_VALIDATE_IP)) $ip = '0.0.0.0';
    return $ip;
}

/** Secret salt for hashes of personal data. DB name + consumer secret keep it stable per installation without a new setting. */
function soHashSalt() {
    return 'so-v1|' . (defined('DB_NAME') ? DB_NAME : '') . '|' . (string) getenv('CONSUMER_SECRET');
}

function soIpHash($ip) {
    return hash('sha256', soHashSalt() . '|ip|' . $ip);
}

/**
 * Count one hit and say whether the caller is still within $limit hits per $window seconds (fixed window).
 * Fails open (true) when the table is missing or the database errors, so a broken limiter never blocks real visitors.
 * Expired rows are swept on about 1 call in 50, so the table stays small.
 */
function soRateHit($kind, $subject, $limit, $window) {
    try {
        $db = MYSQLI;
        $window = max(1, (int) $window);
        $slot = intdiv(time(), $window);
        $k = sha1($kind . '|' . $subject . '|' . $slot);
        $exp = ($slot + 1) * $window + 60;
        $stmt = $db->prepare('INSERT INTO rate_limits (k, hits, expires_at) VALUES (?, 1, ?) ON DUPLICATE KEY UPDATE hits = hits + 1');
        $stmt->bind_param('si', $k, $exp);
        $stmt->execute();
        $stmt->close();
        $stmt = $db->prepare('SELECT hits FROM rate_limits WHERE k = ?');
        $stmt->bind_param('s', $k);
        $stmt->execute();
        $stmt->bind_result($hits);
        $stmt->fetch();
        $stmt->close();
        if (mt_rand(1, 50) === 1) {
            $now = time();
            $db->query('DELETE FROM rate_limits WHERE expires_at < ' . (int) $now . ' LIMIT 500');
        }
        return (int) $hits <= (int) $limit;
    } catch (\Throwable $e) {
        error_log('rate limit unavailable: ' . $e->getMessage());
        return true;
    }
}

/** Current hits for a key without counting (used for lockout checks). */
function soRateCount($kind, $subject, $window) {
    try {
        $window = max(1, (int) $window);
        $k = sha1($kind . '|' . $subject . '|' . intdiv(time(), $window));
        $stmt = MYSQLI->prepare('SELECT hits FROM rate_limits WHERE k = ?');
        $stmt->bind_param('s', $k);
        $stmt->execute();
        $stmt->bind_result($hits);
        $has = $stmt->fetch();
        $stmt->close();
        return $has ? (int) $hits : 0;
    } catch (\Throwable $e) {
        return 0;
    }
}

/** A query-string value as a trimmed string; arrays and missing values give $default. */
function soQs($name, $default = '') {
    $v = $_GET[$name] ?? null;
    return is_string($v) ? trim($v) : $default;
}

function soQsInt($name, $default = 0, $min = 0, $max = PHP_INT_MAX) {
    $v = $_GET[$name] ?? null;
    if (!is_string($v) || !preg_match('/^-?\d{1,12}$/', trim($v))) return $default;
    return max($min, min($max, (int) $v));
}

/** Same as soQs for POST bodies. */
function soPost($name, $default = '') {
    $v = $_POST[$name] ?? null;
    return is_string($v) ? trim($v) : $default;
}

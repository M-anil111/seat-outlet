<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/mailer.php';
admin_require_guest();

$genericMessage = "If that email address is registered, we've sent password reset instructions to it.";
$emailValue = '';
$submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted = true;
    if (!admin_csrf_verify($_POST['csrf_token'] ?? null)) {
        admin_flash_set('error', 'Your session expired. Please try again.');
    } else {
        $emailValue = trim((string)($_POST['email'] ?? ''));

        // A reset mail goes to the account owner, so the form is limited per address and per email (the page answers the same either way).
        $allowed = soRateHit('admin-reset-ip', soIpHash(soClientIp()), 6, 3600) && soRateHit('admin-reset-email', strtolower($emailValue), 3, 3600);
        if ($allowed && $emailValue !== '' && filter_var($emailValue, FILTER_VALIDATE_EMAIL)) {
            $admin = admin_find_by_email($emailValue);

            if ($admin) {
                // Older unused links for this account stop working as soon as a new one is requested.
                $stmt = MYSQLI->prepare('UPDATE admin_password_resets SET used_at = NOW() WHERE admin_id = ? AND used_at IS NULL');
                $oldAdminId = (int)$admin['ID'];
                $stmt->bind_param('i', $oldAdminId);
                $stmt->execute();
                $stmt->close();

                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $expiresAt = date('Y-m-d H:i:s', time() + 30 * 60);

                $stmt = MYSQLI->prepare(
                    'INSERT INTO admin_password_resets (admin_id, token_hash, expires_at, created_at) VALUES (?, ?, ?, NOW())'
                );
                $adminId = (int)$admin['ID'];
                $stmt->bind_param('iss', $adminId, $tokenHash, $expiresAt);
                $stmt->execute();
                $stmt->close();

                // Built from the configured site address, never from the Host header (a forged Host would mail the admin a link to another site).
                $resetLink = rtrim(HOME_URL, '/') . '/admin/reset-password?token=' . $token;

                admin_send_password_reset_email($admin['email'], $admin['name'], $resetLink);
            }
        }

        admin_flash_set('success', $genericMessage);
        header('Location: forgot-password');
        exit;
    }
}

$pageTitle = 'Forgot Password — Seat Outlet Admin';
include __DIR__ . '/includes/header.php';
?>
        <h1 class="admin-title">Forgot Password</h1>
        <p class="admin-subtitle">Enter your email address and we'll send you a link to reset your password.</p>

        <form method="post" action="forgot-password" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo admin_csrf_token(); ?>">

            <div class="admin-form-group">
                <label for="email">Email Address</label>
                <div class="admin-input-wrap">
                    <i class="bi bi-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="Enter your email address"
                        value="<?php echo htmlspecialchars($emailValue, ENT_QUOTES, 'UTF-8'); ?>" required autofocus>
                </div>
            </div>

            <button type="submit" class="admin-btn">Send Reset Link <i class="bi bi-arrow-right"></i></button>
        </form>

        <div class="admin-divider">OR</div>

        <p class="admin-footer-link">Remembered your password? <a href="login">Sign In</a></p>
<?php include __DIR__ . '/includes/footer.php'; ?>

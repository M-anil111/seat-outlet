<?php
require_once __DIR__ . '/includes/auth.php';
admin_require_guest();

$token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
$reset = $token !== '' ? admin_find_valid_reset($token) : null;
$errors = [];

if ($token !== '' && $reset && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $password = (string)($_POST['password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        if (!admin_password_meets_policy($password)) {
            $errors[] = 'Password must be at least 8 characters and include letters, numbers and a special character.';
        } elseif ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);

            $stmt = MYSQLI->prepare('UPDATE admin_users SET password = ?, updated_at = NOW() WHERE ID = ?');
            $stmt->bind_param('si', $hashed, $reset['admin_id']);
            $stmt->execute();
            $stmt->close();

            $stmt = MYSQLI->prepare('UPDATE admin_password_resets SET used_at = NOW() WHERE ID = ?');
            $stmt->bind_param('i', $reset['reset_id']);
            $stmt->execute();
            $stmt->close();

            admin_flash_set('success', 'Your password has been updated. Please sign in.');
            header('Location: login.php');
            exit;
        }
    }
}

if (!empty($errors)) {
    admin_flash_set('error', implode(' ', $errors));
}

$pageTitle = 'Reset Password — Seat Outlet Admin';
include __DIR__ . '/includes/header.php';
?>
<?php if (!$reset): ?>
        <h1 class="admin-title">Link Expired</h1>
        <p class="admin-subtitle">This password reset link is invalid or has expired. Please request a new one.</p>
        <a href="forgot-password.php" class="admin-btn">Request New Link <i class="bi bi-arrow-right"></i></a>
<?php else: ?>
        <h1 class="admin-title">Reset Password</h1>
        <p class="admin-subtitle">Choose a new password for <?php echo htmlspecialchars($reset['email'], ENT_QUOTES, 'UTF-8'); ?>.</p>

        <form method="post" action="reset-password.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo admin_csrf_token(); ?>">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="admin-form-group">
                <label for="password">New Password</label>
                <div class="admin-input-wrap has-toggle">
                    <i class="bi bi-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Create a new password" required autofocus>
                    <button type="button" class="admin-toggle-visibility" data-target="password" aria-label="Show password">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <div class="admin-form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="admin-input-wrap has-toggle">
                    <i class="bi bi-lock"></i>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your new password" required>
                    <button type="button" class="admin-toggle-visibility" data-target="confirm_password" aria-label="Show password">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <div class="admin-hint">
                <i class="bi bi-info-circle"></i>
                <span>Password must be at least 8 characters and include letters, numbers and a special character.</span>
            </div>

            <button type="submit" class="admin-btn">Update Password <i class="bi bi-arrow-right"></i></button>
        </form>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>

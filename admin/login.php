<?php
require_once __DIR__ . '/includes/auth.php';
admin_require_guest();

$errors = [];
$emailValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $emailValue = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $keepSignedIn = !empty($_POST['keep_signed_in']);

        if ($emailValue === '' || $password === '') {
            $errors[] = 'Please enter both your email address and password.';
        } elseif (!filter_var($emailValue, FILTER_VALIDATE_EMAIL)) {
            admin_login_failed($emailValue);
            $errors[] = 'Invalid email or password.';
        } elseif (admin_login_locked($emailValue)) {
            $errors[] = 'Too many sign-in attempts. Please wait 15 minutes and try again.';
        } else {
            $admin = admin_find_by_email($emailValue);

            if (!$admin || !password_verify($password, $admin['password'])) {
                admin_login_failed($emailValue);
                $errors[] = 'Invalid email or password.';
            } else {
                admin_sign_in($admin, $keepSignedIn);
                header('Location: dashboard');
                exit;
            }
        }
    }
}

if (!empty($errors)) {
    admin_flash_set('error', implode(' ', $errors));
}

$pageTitle = 'Sign In — Seat Outlet Admin';
include __DIR__ . '/includes/header.php';
?>
        <h1 class="admin-title">Welcome Back</h1>
        <p class="admin-subtitle">Sign in to access your Seat Outlet admin panel.</p>

        <form method="post" action="login" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo admin_csrf_token(); ?>">

            <div class="admin-form-group">
                <label for="email">Email Address</label>
                <div class="admin-input-wrap">
                    <i class="bi bi-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="Enter your email address"
                        value="<?php echo htmlspecialchars($emailValue, ENT_QUOTES, 'UTF-8'); ?>" required autofocus>
                </div>
            </div>

            <div class="admin-form-group">
                <label for="password">Password</label>
                <div class="admin-input-wrap has-toggle">
                    <i class="bi bi-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    <button type="button" class="admin-toggle-visibility" data-target="password" aria-label="Show password">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <div class="admin-row-between">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="keep_signed_in" id="keep_signed_in">
                    <label class="form-check-label" for="keep_signed_in">Keep me signed in</label>
                </div>
                <a href="forgot-password">Forgot Password?</a>
            </div>

            <button type="submit" class="admin-btn">Sign In <i class="bi bi-arrow-right"></i></button>
        </form>

        <div class="admin-divider">OR</div>

        <p class="admin-footer-link">Don't have an account? <a href="register">Register Now</a></p>
<?php include __DIR__ . '/includes/footer.php'; ?>

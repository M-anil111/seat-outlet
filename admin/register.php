<?php
require_once __DIR__ . '/includes/auth.php';
admin_require_guest();

if (!admin_registration_is_open()) {
    admin_flash_set('error', 'Registration is closed. Contact an existing admin for access.');
    header('Location: login');
    exit;
}

$errors = [];
$nameValue = '';
$emailValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $nameValue = trim((string)($_POST['name'] ?? ''));
        $emailValue = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($nameValue === '' || $emailValue === '' || $password === '') {
            $errors[] = 'Please fill in all fields.';
        }
        if ($emailValue !== '' && !filter_var($emailValue, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if ($password !== '' && !admin_password_meets_policy($password)) {
            $errors[] = 'Password must be at least 8 characters and include letters, numbers and a special character.';
        }

        if (empty($errors)) {
            if (admin_find_by_email($emailValue)) {
                $errors[] = 'An account with this email address already exists.';
            } else {
                $stmt = MYSQLI->prepare(
                    'INSERT INTO admin_users (name, email, password, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())'
                );
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $stmt->bind_param('sss', $nameValue, $emailValue, $hashed);

                if ($stmt->execute()) {
                    $stmt->close();
                    admin_flash_set('success', 'Account created successfully. Please sign in.');
                    header('Location: login');
                    exit;
                }

                $stmt->close();
                $errors[] = 'Something went wrong while creating your account. Please try again.';
            }
        }
    }
}

if (!empty($errors)) {
    admin_flash_set('error', implode(' ', $errors));
}

$pageTitle = 'Create Admin Account — Seat Outlet Admin';
include __DIR__ . '/includes/header.php';
?>
        <h1 class="admin-title">Create Admin Account</h1>
        <p class="admin-subtitle">Set up your account to access the Seat Outlet admin panel.</p>

        <form method="post" action="register" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo admin_csrf_token(); ?>">

            <div class="admin-form-group">
                <label for="name">Full Name</label>
                <div class="admin-input-wrap">
                    <i class="bi bi-person"></i>
                    <input type="text" id="name" name="name" placeholder="Enter your full name"
                        value="<?php echo htmlspecialchars($nameValue, ENT_QUOTES, 'UTF-8'); ?>" required autofocus>
                </div>
            </div>

            <div class="admin-form-group">
                <label for="email">Email Address</label>
                <div class="admin-input-wrap">
                    <i class="bi bi-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="Enter your email address"
                        value="<?php echo htmlspecialchars($emailValue, ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
            </div>

            <div class="admin-form-group">
                <label for="password">Password</label>
                <div class="admin-input-wrap has-toggle">
                    <i class="bi bi-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Create a password" required>
                    <button type="button" class="admin-toggle-visibility" data-target="password" aria-label="Show password">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <div class="admin-hint">
                <i class="bi bi-info-circle"></i>
                <span>Password must be at least 8 characters and include letters, numbers and a special character.</span>
            </div>

            <button type="submit" class="admin-btn">Create Account <i class="bi bi-arrow-right"></i></button>
        </form>

        <div class="admin-divider">OR</div>

        <p class="admin-footer-link">Already have an account? <a href="login">Sign In</a></p>
<?php include __DIR__ . '/includes/footer.php'; ?>

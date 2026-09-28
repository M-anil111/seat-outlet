<?php
require_once __DIR__ . '/includes/auth.php';
admin_require_login();

$pageTitle = 'Dashboard — Seat Outlet Admin';
include __DIR__ . '/includes/header.php';
?>
        <h1 class="admin-title">Welcome, <?php echo htmlspecialchars($_SESSION['admin_name'], ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="admin-subtitle">You're signed in to the Seat Outlet admin panel.</p>

        <a href="logout.php" class="admin-btn"><i class="bi bi-box-arrow-right"></i> Sign Out</a>
<?php include __DIR__ . '/includes/footer.php'; ?>

<?php
// Shared shell for every screen *inside* the admin panel (post-login), built
// on Tabler (https://tabler.io - MIT licensed), matching the Bootstrap 5
// stack already used on the public site. The stand-alone auth screens
// (login/register/forgot/reset password) keep their own existing
// includes/header.php + admin.css and are not touched by this file.
if (!isset($pageTitle)) {
    $pageTitle = 'Seat Outlet Admin';
}
$flash = admin_flash_get();
$currentPage = $currentPage ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="icon" type="image/png" href="/images/favicon-new.webp">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.6.0/dist/css/tabler.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.48.0/dist/tabler-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/app.css?v=<?php echo filemtime(__DIR__ . '/../assets/app.css'); ?>">
</head>

<body class="app-admin-body">
    <div class="page">
        <aside class="navbar navbar-vertical navbar-expand-lg app-sidebar">
            <div class="container-fluid">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <h1 class="navbar-brand navbar-brand-autodark">
                    <a href="dashboard" class="app-sidebar-brand">
                        <img src="/images/admin-logo.png" alt="Seat Outlet Admin" height="28">
                    </a>
                </h1>
                <div class="collapse navbar-collapse" id="sidebar-menu">
                    <ul class="navbar-nav pt-lg-3">
                        <li class="nav-item <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                            <a class="nav-link" href="dashboard">
                                <span class="nav-link-icon"><i class="ti ti-layout-dashboard"></i></span>
                                <span class="nav-link-title">Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item <?php echo $currentPage === 'page-rules' ? 'active' : ''; ?>">
                            <a class="nav-link" href="page-rules">
                                <span class="nav-link-icon"><i class="ti ti-file-search"></i></span>
                                <span class="nav-link-title">Page SEO &amp; Redirects</span>
                            </a>
                        </li>
                        <li class="nav-item <?php echo $currentPage === 'blog-posts' ? 'active' : ''; ?>">
                            <a class="nav-link" href="blog-posts">
                                <span class="nav-link-icon"><i class="ti ti-notes"></i></span>
                                <span class="nav-link-title">Blog Posts</span>
                            </a>
                        </li>
                        <li class="nav-item <?php echo $currentPage === 'page-content' ? 'active' : ''; ?>">
                            <a class="nav-link" href="page-content">
                                <span class="nav-link-icon"><i class="ti ti-edit"></i></span>
                                <span class="nav-link-title">Page Content</span>
                            </a>
                        </li>
                        <li class="nav-item <?php echo $currentPage === 'seo-scores' ? 'active' : ''; ?>">
                            <a class="nav-link" href="seo-scores">
                                <span class="nav-link-icon"><i class="ti ti-gauge"></i></span>
                                <span class="nav-link-title">SEO Scores</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </aside>

        <header class="navbar navbar-expand-md navbar-light d-print-none app-topbar">
            <div class="container-fluid">
                <div class="navbar-nav flex-row order-md-last ms-auto">
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown">
                            <span class="avatar avatar-sm app-avatar">
                                <?php echo htmlspecialchars(strtoupper(substr($_SESSION['admin_name'] ?? '?', 0, 1)), ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                            <div class="d-none d-xl-block ps-2">
                                <div><?php echo htmlspecialchars($_SESSION['admin_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="mt-1 small text-secondary"><?php echo htmlspecialchars($_SESSION['admin_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                            <a href="logout" class="dropdown-item"><i class="ti ti-logout me-2"></i>Sign out</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="page-wrapper">
            <div class="page-body">
                <div class="container-xl">
                    <?php if ($flash): ?>
                        <div class="alert <?php echo $flash['type'] === 'success' ? 'alert-success' : 'alert-danger'; ?> alert-dismissible" role="alert">
                            <?php echo htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8'); ?>
                            <a class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss"></a>
                        </div>
                    <?php endif; ?>

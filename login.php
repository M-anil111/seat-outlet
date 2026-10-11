<?php
// /login: sign in or create an account (the same emailed link does both).
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/inc/account.php';
require_once __DIR__ . '/inc/account-pages.php';
if (soAcctUser() !== null) { header('Location: /account', true, 302); exit; }
$pageNoCache = true; $pageRobots = 'noindex, nofollow';
[$pageMetaTitle, $pageMetaDescription] = soAcctMeta('Sign in', 'Sign in or create a Seat Outlet account with an emailed link. No password.');
include 'header.php';
echo soAcctStyle();
soAcctSignInBody('login');
include 'footer.php';

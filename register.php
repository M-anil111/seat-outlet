<?php
// /register: the same passwordless form as /login, worded for a new account.
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/inc/account.php';
require_once __DIR__ . '/inc/account-pages.php';
if (soAcctUser() !== null) { header('Location: /account', true, 302); exit; }
$pageNoCache = true; $pageRobots = 'noindex, nofollow';
[$pageMetaTitle, $pageMetaDescription] = soAcctMeta('Create account', 'Create a Seat Outlet account with an emailed link. No password.');
include 'header.php';
echo soAcctStyle();
soAcctSignInBody('register');
include 'footer.php';

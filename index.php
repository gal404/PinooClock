<?php
require_once __DIR__ . '/includes/auth.php';
$user = currentUser();
if (!$user) {
    header('Location: /login.php');
    exit;
}
header('Location: /clock.php');
exit;

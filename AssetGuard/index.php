<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirectByRole();
} else {
    header('Location: ' . APP_URL . '/auth/login.php');
}
exit;

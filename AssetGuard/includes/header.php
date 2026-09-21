<?php
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?><?= isset($pageTitle) ? ' — ' . htmlspecialchars($pageTitle) : '' ?></title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom Styles -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-glass sticky-top">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= APP_URL ?>/index.php">
            <i class="bi bi-shield-check brand-icon"></i>
            <span class="brand-text"><?= APP_NAME ?></span>
        </a>

        <?php if ($currentUser): ?>
        <div class="d-flex align-items-center gap-3 ms-auto">
            <span class="user-chip">
                <i class="bi bi-person-circle"></i>
                <?= htmlspecialchars($currentUser['name']) ?>
                <span class="role-tag"><?= htmlspecialchars(ucfirst($currentUser['role'])) ?></span>
            </span>
            <a href="<?= APP_URL ?>/auth/logout.php" class="btn btn-outline-warning btn-sm">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
        <?php endif; ?>
    </div>
</nav>

<div class="app-layout">
    <?php if ($currentUser): include __DIR__ . '/sidebar.php'; endif; ?>
    <main class="app-content">
        <?php displayFlash(); ?>

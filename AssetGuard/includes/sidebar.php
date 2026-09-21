<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="app-sidebar glass-panel">
    <nav class="nav flex-column gap-1">
        <?php if (isAdmin()): ?>
            <a class="sidebar-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a class="sidebar-link <?= $currentPage === 'manage_equipment.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/manage_equipment.php">
                <i class="bi bi-gear-wide-connected"></i> Equipment
            </a>
            <a class="sidebar-link <?= $currentPage === 'manage_requests.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/manage_requests.php">
                <i class="bi bi-clipboard2-pulse"></i> Maintenance Requests
            </a>
            <a class="sidebar-link <?= $currentPage === 'manage_schedule.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/manage_schedule.php">
                <i class="bi bi-calendar3"></i> Schedule
            </a>
            <a class="sidebar-link <?= $currentPage === 'spare_parts_alerts.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/spare_parts_alerts.php">
                <i class="bi bi-exclamation-triangle"></i> Spare Parts Alerts
            </a>
            <a class="sidebar-link <?= $currentPage === 'manage_technicians.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/manage_technicians.php">
                <i class="bi bi-people"></i> Technicians
            </a>
            <a class="sidebar-link <?= $currentPage === 'reports.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/reports.php">
                <i class="bi bi-bar-chart-line"></i> Reports
            </a>
        <?php else: ?>
            <a class="sidebar-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/technician/dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a class="sidebar-link <?= $currentPage === 'my_requests.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/technician/my_requests.php">
                <i class="bi bi-clipboard2-check"></i> My Requests
            </a>
            <a class="sidebar-link <?= $currentPage === 'equipment_profile.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/technician/equipment_profile.php">
                <i class="bi bi-tools"></i> Equipment Profiles
            </a>
        <?php endif; ?>
    </nav>
</aside>

<?php

// =====================================================
// Session & Authentication
// =====================================================

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    return [
        'id'   => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'role' => $_SESSION['user_role'],
    ];
}

function isAdmin(): bool {
    return isLoggedIn() && $_SESSION['user_role'] === 'admin';
}

function isTechnician(): bool {
    return isLoggedIn() && $_SESSION['user_role'] === 'technician';
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/auth/login.php');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . APP_URL . '/technician/dashboard.php');
        exit;
    }
}

function redirectByRole(): void {
    if (isAdmin()) {
        header('Location: ' . APP_URL . '/admin/dashboard.php');
    } else {
        header('Location: ' . APP_URL . '/technician/dashboard.php');
    }
    exit;
}

// =====================================================
// Flash Messages
// =====================================================

function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (!isset($_SESSION['flash'])) return null;
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function displayFlash(): void {
    $flash = getFlash();
    if ($flash) {
        echo '<div class="alert alert-' . htmlspecialchars($flash['type']) . ' glass-alert" role="alert">'
            . htmlspecialchars($flash['message']) . '</div>';
    }
}

// =====================================================
// Data Sanitization
// =====================================================

function clean(string $data): string {
    return htmlspecialchars(strip_tags(trim($data)));
}

// =====================================================
// Formatting Functions
// =====================================================

function formatDate(?string $date, string $format = 'Y-m-d'): string {
    if (!$date) return '—';
    return date($format, strtotime($date));
}

function calculatePercentage(float $value, float $total): float {
    if ($total <= 0) return 0;
    return round(($value / $total) * 100, 1);
}

// =====================================================
// Badge / Label Functions
// =====================================================

function getStatusBadge(string $status): string {
    $map = [
        'Operational'      => 'badge-status-good',
        'Under Maintenance' => 'badge-status-warning',
        'Broken'           => 'badge-status-danger',
        'Retired'          => 'badge-status-muted',
        'Open'             => 'badge-status-warning',
        'In Progress'      => 'badge-status-info',
        'Completed'        => 'badge-status-good',
    ];
    return $map[$status] ?? 'badge-status-muted';
}

function getPriorityBadge(string $priority): string {
    $map = [
        'High'   => 'badge-priority-high',
        'Medium' => 'badge-priority-medium',
        'Low'    => 'badge-priority-low',
    ];
    return $map[$priority] ?? 'badge-priority-low';
}

function getRiskLevelBadge(string $level): string {
    $map = [
        'High'   => 'badge-risk-high',
        'Medium' => 'badge-risk-medium',
        'Low'    => 'badge-risk-low',
    ];
    return $map[$level] ?? 'badge-risk-low';
}

// =====================================================
// Statistics Functions
// =====================================================

function getAdminStats(): array {
    global $pdo;

    return [
        'total_equipment'      => (int) $pdo->query("SELECT COUNT(*) FROM equipment")->fetchColumn(),
        'operational_equipment' => (int) $pdo->query("SELECT COUNT(*) FROM equipment WHERE status = 'Operational'")->fetchColumn(),
        'broken_equipment'     => (int) $pdo->query("SELECT COUNT(*) FROM equipment WHERE status = 'Broken'")->fetchColumn(),
        'open_requests'        => (int) $pdo->query("SELECT COUNT(*) FROM maintenance_requests WHERE status = 'Open'")->fetchColumn(),
        'in_progress_requests' => (int) $pdo->query("SELECT COUNT(*) FROM maintenance_requests WHERE status = 'In Progress'")->fetchColumn(),
        'low_stock_parts'      => (int) $pdo->query("SELECT COUNT(*) FROM spare_parts_alerts WHERE quantity_available <= alert_threshold")->fetchColumn(),
        'total_technicians'    => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'technician'")->fetchColumn(),
    ];
}

function getTechnicianStats(int $userId): array {
    global $pdo;

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM equipment WHERE responsible_id = ?");
    $stmt->execute([$userId]);
    $myEquipment = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM maintenance_requests WHERE assigned_to = ?");
    $stmt->execute([$userId]);
    $myRequests = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM maintenance_requests WHERE assigned_to = ? AND status != 'Completed'");
    $stmt->execute([$userId]);
    $pendingRequests = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM maintenance_requests WHERE assigned_to = ? AND status = 'Completed'");
    $stmt->execute([$userId]);
    $completedRequests = (int) $stmt->fetchColumn();

    return [
        'my_equipment'        => $myEquipment,
        'my_requests'         => $myRequests,
        'pending_requests'    => $pendingRequests,
        'completed_requests'  => $completedRequests,
    ];
}

function getEquipmentByStatus(): array {
    global $pdo;
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM equipment GROUP BY status");
    $rows = $stmt->fetchAll();

    $labels = [];
    $values = [];
    $colors = [
        'Operational'       => '#4CAF87',
        'Under Maintenance' => '#F2A65A',
        'Broken'            => '#E4572E',
        'Retired'           => '#6B7280',
    ];
    $chartColors = [];

    foreach ($rows as $row) {
        $labels[] = $row['status'];
        $values[] = (int) $row['count'];
        $chartColors[] = $colors[$row['status']] ?? '#6B7280';
    }

    return ['labels' => $labels, 'values' => $values, 'colors' => $chartColors];
}

function getRequestsByMonth(): array {
    global $pdo;
    $stmt = $pdo->query("
        SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
        FROM maintenance_requests
        GROUP BY month
        ORDER BY month ASC
        LIMIT 12
    ");
    $rows = $stmt->fetchAll();

    return [
        'labels' => array_column($rows, 'month'),
        'values' => array_map('intval', array_column($rows, 'count')),
    ];
}

// =====================================================
// Dropdown Data Functions
// =====================================================

function getTechniciansForDropdown(): array {
    global $pdo;
    return $pdo->query("SELECT id, name FROM users WHERE role = 'technician' ORDER BY name")->fetchAll();
}

function getEquipmentForDropdown(): array {
    global $pdo;
    return $pdo->query("SELECT id, name FROM equipment ORDER BY name")->fetchAll();
}

function getEquipmentTypes(): array {
    return ['Heavy Machinery', 'Fleet Vehicle', 'Power Equipment', 'Warehouse Equipment', 'Other'];
}

function getPriorities(): array {
    return ['High', 'Medium', 'Low'];
}

function getRequestStatuses(): array {
    return ['Open', 'In Progress', 'Completed'];
}

function getEquipmentStatuses(): array {
    return ['Operational', 'Under Maintenance', 'Broken', 'Retired'];
}

function getRiskLevels(): array {
    return ['Low', 'Medium', 'High'];
}

function getFrequencies(): array {
    return ['Weekly', 'Monthly', 'Quarterly', 'Yearly'];
}

<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pageTitle = 'Dashboard';
$stats = getAdminStats();
$statusChart = getEquipmentByStatus();
$monthlyChart = getRequestsByMonth();

$recentRequests = $pdo->query("
    SELECT mr.*, e.name AS equipment_name, u.name AS technician_name
    FROM maintenance_requests mr
    JOIN equipment e ON mr.equipment_id = e.id
    LEFT JOIN users u ON mr.assigned_to = u.id
    ORDER BY mr.created_at DESC LIMIT 5
")->fetchAll();

$lowStockParts = $pdo->query("
    SELECT sp.*, e.name AS equipment_name
    FROM spare_parts_alerts sp
    JOIN equipment e ON sp.equipment_id = e.id
    WHERE sp.quantity_available <= sp.alert_threshold
    ORDER BY sp.quantity_available ASC LIMIT 5
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<h2 class="mb-4">Admin Dashboard</h2>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="glass-panel stat-card">
            <div class="stat-value"><?= $stats['total_equipment'] ?></div>
            <div class="stat-label">Total Equipment</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="glass-panel stat-card">
            <div class="stat-value"><?= $stats['operational_equipment'] ?></div>
            <div class="stat-label">Operational</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="glass-panel stat-card">
            <div class="stat-value"><?= $stats['broken_equipment'] ?></div>
            <div class="stat-label">Broken</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="glass-panel stat-card">
            <div class="stat-value"><?= $stats['open_requests'] ?></div>
            <div class="stat-label">Open Requests</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="glass-panel stat-card">
            <div class="stat-value"><?= $stats['in_progress_requests'] ?></div>
            <div class="stat-label">In Progress</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="glass-panel stat-card">
            <div class="stat-value"><?= $stats['low_stock_parts'] ?></div>
            <div class="stat-label">Low Stock Parts</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="glass-panel stat-card">
            <div class="stat-value"><?= $stats['total_technicians'] ?></div>
            <div class="stat-label">Technicians</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="glass-panel stat-card d-flex flex-column align-items-center justify-content-center">
            <div id="fleetHealthGauge" class="gauge-ring"></div>
            <div class="stat-label mt-1">Fleet Health</div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row g-3 mb-4">
    <div class="col-md-5">
        <div class="glass-panel p-4">
            <h6 class="mb-3">Equipment by Status</h6>
            <canvas id="statusChart" height="220"></canvas>
        </div>
    </div>
    <div class="col-md-7">
        <div class="glass-panel p-4">
            <h6 class="mb-3">Maintenance Requests per Month</h6>
            <canvas id="monthlyChart" height="220"></canvas>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row g-3">
    <div class="col-md-7">
        <div class="glass-panel p-4">
            <h6 class="mb-3">Recent Maintenance Requests</h6>
            <div class="table-responsive">
                <table class="table table-dark table-borderless align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Equipment</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Technician</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentRequests as $req): ?>
                        <tr>
                            <td><?= htmlspecialchars($req['equipment_name']) ?></td>
                            <td><span class="<?= getPriorityBadge($req['priority']) ?>"><?= $req['priority'] ?></span></td>
                            <td><span class="<?= getStatusBadge($req['status']) ?>"><?= $req['status'] ?></span></td>
                            <td><?= htmlspecialchars($req['technician_name'] ?? '—') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="glass-panel p-4">
            <h6 class="mb-3"><i class="bi bi-exclamation-triangle text-warning"></i> Low Stock Spare Parts</h6>
            <?php foreach ($lowStockParts as $part): ?>
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary-subtle">
                <div>
                    <div><?= htmlspecialchars($part['part_name']) ?></div>
                    <small class="text-muted"><?= htmlspecialchars($part['equipment_name']) ?></small>
                </div>
                <span class="badge-status-danger px-2 py-1 rounded-pill"><?= $part['quantity_available'] ?> left</span>
            </div>
            <?php endforeach; ?>
            <?php if (empty($lowStockParts)): ?>
                <p class="text-muted mb-0">No low stock alerts. All good.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Fleet health gauge
const total = <?= $stats['total_equipment'] ?: 1 ?>;
const operational = <?= $stats['operational_equipment'] ?>;
renderGauge('fleetHealthGauge', Math.round((operational / total) * 100), '#4CAF87');

// Equipment by Status (Doughnut)
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($statusChart['labels']) ?>,
        datasets: [{
            data: <?= json_encode($statusChart['values']) ?>,
            backgroundColor: <?= json_encode($statusChart['colors']) ?>,
            borderWidth: 0
        }]
    },
    options: {
        plugins: { legend: { position: 'bottom', labels: { color: '#A89A8C' } } }
    }
});

// Requests per Month (Bar)
new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($monthlyChart['labels']) ?>,
        datasets: [{
            label: 'Requests',
            data: <?= json_encode($monthlyChart['values']) ?>,
            backgroundColor: '#F2A65A',
            borderRadius: 6
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { color: '#A89A8C' }, grid: { display: false } },
            y: { ticks: { color: '#A89A8C' }, grid: { color: 'rgba(255,255,255,0.05)' } }
        }
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

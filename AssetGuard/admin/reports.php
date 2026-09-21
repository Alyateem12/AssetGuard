<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pageTitle = 'Reports';

// Equipment by risk level
$riskData = $pdo->query("SELECT risk_level, COUNT(*) as count FROM equipment GROUP BY risk_level")->fetchAll();

// Requests by status
$statusData = $pdo->query("SELECT status, COUNT(*) as count FROM maintenance_requests GROUP BY status")->fetchAll();

// Technician performance
$techPerformance = $pdo->query("
    SELECT u.name,
        COUNT(mr.id) AS total_assigned,
        SUM(CASE WHEN mr.status = 'Completed' THEN 1 ELSE 0 END) AS completed
    FROM users u
    LEFT JOIN maintenance_requests mr ON mr.assigned_to = u.id
    WHERE u.role = 'technician'
    GROUP BY u.id, u.name
    ORDER BY completed DESC
")->fetchAll();

// Overdue preventive schedules
$overdueCount = $pdo->query("
    SELECT COUNT(*) FROM maintenance_schedule WHERE next_due_date < CURDATE()
")->fetchColumn();

$monthlyChart = getRequestsByMonth();

include __DIR__ . '/../includes/header.php';
?>

<h2 class="mb-4">Reports & Analytics</h2>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="glass-panel p-4">
            <h6 class="mb-3">Equipment by Risk Level</h6>
            <canvas id="riskChart" height="200"></canvas>
        </div>
    </div>
    <div class="col-md-4">
        <div class="glass-panel p-4">
            <h6 class="mb-3">Requests by Status</h6>
            <canvas id="statusChart" height="200"></canvas>
        </div>
    </div>
    <div class="col-md-4">
        <div class="glass-panel p-4 d-flex flex-column align-items-center justify-content-center text-center">
            <div class="stat-value text-danger"><?= $overdueCount ?></div>
            <div class="stat-label">Overdue Preventive Tasks</div>
            <a href="manage_schedule.php" class="btn btn-sm btn-outline-warning mt-3">Review Schedule</a>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="glass-panel p-4">
            <h6 class="mb-3">Requests Trend (Monthly)</h6>
            <canvas id="trendChart" height="120"></canvas>
        </div>
    </div>
</div>

<div class="glass-panel p-4">
    <h6 class="mb-3">Technician Performance</h6>
    <div class="table-responsive">
        <table class="table table-dark table-borderless align-middle mb-0">
            <thead>
                <tr>
                    <th>Technician</th>
                    <th>Total Assigned</th>
                    <th>Completed</th>
                    <th>Completion Rate</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($techPerformance as $t):
                    $rate = calculatePercentage((float) $t['completed'], (float) $t['total_assigned']);
                ?>
                <tr>
                    <td><?= htmlspecialchars($t['name']) ?></td>
                    <td><?= $t['total_assigned'] ?></td>
                    <td><?= $t['completed'] ?></td>
                    <td>
                        <div class="progress" style="height: 8px; background: rgba(255,255,255,0.08);">
                            <div class="progress-bar" style="width: <?= $rate ?>%; background: var(--color-accent);"></div>
                        </div>
                        <small class="text-muted"><?= $rate ?>%</small>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
new Chart(document.getElementById('riskChart'), {
    type: 'pie',
    data: {
        labels: <?= json_encode(array_column($riskData, 'risk_level')) ?>,
        datasets: [{
            data: <?= json_encode(array_map('intval', array_column($riskData, 'count'))) ?>,
            backgroundColor: ['#4CAF87', '#F2A65A', '#E4572E'],
            borderWidth: 0
        }]
    },
    options: { plugins: { legend: { position: 'bottom', labels: { color: '#A89A8C' } } } }
});

new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($statusData, 'status')) ?>,
        datasets: [{
            data: <?= json_encode(array_map('intval', array_column($statusData, 'count'))) ?>,
            backgroundColor: ['#F2A65A', '#5AA9E6', '#4CAF87'],
            borderWidth: 0
        }]
    },
    options: { plugins: { legend: { position: 'bottom', labels: { color: '#A89A8C' } } } }
});

new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($monthlyChart['labels']) ?>,
        datasets: [{
            label: 'Requests',
            data: <?= json_encode($monthlyChart['values']) ?>,
            borderColor: '#F2A65A',
            backgroundColor: 'rgba(242, 166, 90, 0.15)',
            fill: true,
            tension: 0.35
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

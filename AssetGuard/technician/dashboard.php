<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

if (isAdmin()) {
    redirectByRole();
}

$pageTitle = 'Dashboard';
$user = getCurrentUser();
$stats = getTechnicianStats($user['id']);

$myRequests = $pdo->prepare("
    SELECT mr.*, e.name AS equipment_name
    FROM maintenance_requests mr
    JOIN equipment e ON mr.equipment_id = e.id
    WHERE mr.assigned_to = ?
    ORDER BY mr.status = 'Open' DESC, mr.priority = 'High' DESC, mr.scheduled_date ASC
    LIMIT 5
");
$myRequests->execute([$user['id']]);
$myRequests = $myRequests->fetchAll();

$myEquipment = $pdo->prepare("SELECT * FROM equipment WHERE responsible_id = ?");
$myEquipment->execute([$user['id']]);
$myEquipment = $myEquipment->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<h2 class="mb-4">Welcome, <?= htmlspecialchars($user['name']) ?></h2>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="glass-panel stat-card">
            <div class="stat-value"><?= $stats['my_equipment'] ?></div>
            <div class="stat-label">My Equipment</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="glass-panel stat-card">
            <div class="stat-value"><?= $stats['my_requests'] ?></div>
            <div class="stat-label">Total Requests</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="glass-panel stat-card">
            <div class="stat-value"><?= $stats['pending_requests'] ?></div>
            <div class="stat-label">Pending</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="glass-panel stat-card d-flex flex-column align-items-center justify-content-center">
            <div id="completionGauge" class="gauge-ring"></div>
            <div class="stat-label mt-1">Completion Rate</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- My Requests -->
    <div class="col-md-7">
        <div class="glass-panel p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">My Maintenance Requests</h6>
                <a href="my_requests.php" class="btn btn-sm btn-outline-warning">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-dark table-borderless align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Equipment</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Scheduled</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myRequests as $req): ?>
                        <tr>
                            <td><?= htmlspecialchars($req['equipment_name']) ?></td>
                            <td><span class="<?= getPriorityBadge($req['priority']) ?>"><?= $req['priority'] ?></span></td>
                            <td><span class="<?= getStatusBadge($req['status']) ?>"><?= $req['status'] ?></span></td>
                            <td><?= formatDate($req['scheduled_date']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($myRequests)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">No requests assigned to you yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- My Equipment -->
    <div class="col-md-5">
        <div class="glass-panel p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">My Equipment</h6>
                <a href="equipment_profile.php" class="btn btn-sm btn-outline-warning">View All</a>
            </div>
            <?php foreach ($myEquipment as $eq): ?>
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary-subtle">
                <div>
                    <div><?= htmlspecialchars($eq['name']) ?></div>
                    <small class="text-muted"><?= htmlspecialchars($eq['location']) ?></small>
                </div>
                <span class="<?= getStatusBadge($eq['status']) ?>"><?= $eq['status'] ?></span>
            </div>
            <?php endforeach; ?>
            <?php if (empty($myEquipment)): ?>
                <p class="text-muted mb-0">No equipment assigned to you yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const total = <?= $stats['my_requests'] ?: 1 ?>;
const completed = <?= $stats['completed_requests'] ?>;
renderGauge('completionGauge', Math.round((completed / total) * 100), '#4CAF87');
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

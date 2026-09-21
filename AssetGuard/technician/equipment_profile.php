<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

if (isAdmin()) {
    redirectByRole();
}

$pageTitle = 'Equipment Profiles';
$user = getCurrentUser();

$stmt = $pdo->prepare("SELECT * FROM equipment WHERE responsible_id = ? ORDER BY name");
$stmt->execute([$user['id']]);
$equipmentList = $stmt->fetchAll();

// If a specific equipment is selected, load its full profile
$selected = null;
$history = [];
$schedule = [];
if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $check = $pdo->prepare("SELECT * FROM equipment WHERE id = ? AND responsible_id = ?");
    $check->execute([$id, $user['id']]);
    $selected = $check->fetch();

    if ($selected) {
        $h = $pdo->prepare("SELECT * FROM maintenance_history WHERE equipment_id = ? ORDER BY performed_date DESC");
        $h->execute([$id]);
        $history = $h->fetchAll();

        $s = $pdo->prepare("SELECT * FROM maintenance_schedule WHERE equipment_id = ? ORDER BY next_due_date ASC");
        $s->execute([$id]);
        $schedule = $s->fetchAll();
    }
}

include __DIR__ . '/../includes/header.php';
?>

<h2 class="mb-4">Equipment Profiles</h2>

<?php if (!$selected): ?>
<div class="row g-3">
    <?php foreach ($equipmentList as $eq): ?>
    <div class="col-md-4">
        <a href="?id=<?= $eq['id'] ?>" class="text-decoration-none">
            <div class="glass-panel p-4 h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="mb-0 text-white"><?= htmlspecialchars($eq['name']) ?></h6>
                    <span class="<?= getRiskLevelBadge($eq['risk_level']) ?>"><?= $eq['risk_level'] ?></span>
                </div>
                <p class="text-muted small mb-2"><?= htmlspecialchars($eq['type']) ?> — <?= htmlspecialchars($eq['location']) ?></p>
                <span class="<?= getStatusBadge($eq['status']) ?>"><?= $eq['status'] ?></span>
            </div>
        </a>
    </div>
    <?php endforeach; ?>

    <?php if (empty($equipmentList)): ?>
    <div class="col-12">
        <div class="glass-panel p-4 text-center text-muted">No equipment assigned to you yet.</div>
    </div>
    <?php endif; ?>
</div>

<?php else: ?>

<a href="equipment_profile.php" class="btn btn-sm btn-outline-warning mb-3"><i class="bi bi-arrow-left"></i> Back to All Equipment</a>

<div class="glass-panel p-4 mb-4">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h4 class="mb-1"><?= htmlspecialchars($selected['name']) ?></h4>
            <p class="text-muted mb-0"><?= htmlspecialchars($selected['type']) ?> — <?= htmlspecialchars($selected['location']) ?></p>
        </div>
        <div class="text-end">
            <span class="<?= getStatusBadge($selected['status']) ?> me-2"><?= $selected['status'] ?></span>
            <span class="<?= getRiskLevelBadge($selected['risk_level']) ?>"><?= $selected['risk_level'] ?> Risk</span>
        </div>
    </div>
    <p class="text-muted small mt-3 mb-0">Purchased: <?= formatDate($selected['purchase_date']) ?></p>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="glass-panel p-4">
            <h6 class="mb-3"><i class="bi bi-clock-history"></i> Maintenance History</h6>
            <?php foreach ($history as $h): ?>
            <div class="py-2 border-bottom border-secondary-subtle">
                <div class="d-flex justify-content-between">
                    <span><?= htmlspecialchars($h['notes']) ?></span>
                    <small class="text-muted"><?= formatDate($h['performed_date']) ?></small>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($history)): ?>
                <p class="text-muted mb-0">No maintenance history recorded yet.</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-6">
        <div class="glass-panel p-4">
            <h6 class="mb-3"><i class="bi bi-calendar3"></i> Preventive Schedule</h6>
            <?php foreach ($schedule as $s): ?>
            <div class="py-2 border-bottom border-secondary-subtle">
                <div class="d-flex justify-content-between">
                    <span><?= $s['frequency'] ?></span>
                    <small class="text-muted">Next: <?= formatDate($s['next_due_date']) ?></small>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($schedule)): ?>
                <p class="text-muted mb-0">No preventive schedule set for this equipment.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>

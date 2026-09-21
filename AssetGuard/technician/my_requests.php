<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

if (isAdmin()) {
    redirectByRole();
}

$pageTitle = 'My Requests';
$user = getCurrentUser();

// ===== Handle Status Update =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int) $_POST['id'];
    $status = clean($_POST['status']);
    $completedDate = $status === 'Completed' ? date('Y-m-d') : null;

    // Make sure the technician can only update their own requests
    $check = $pdo->prepare("SELECT * FROM maintenance_requests WHERE id = ? AND assigned_to = ?");
    $check->execute([$id, $user['id']]);
    $request = $check->fetch();

    if ($request) {
        $stmt = $pdo->prepare("UPDATE maintenance_requests SET status = ?, completed_date = COALESCE(?, completed_date) WHERE id = ?");
        $stmt->execute([$status, $completedDate, $id]);

        if ($status === 'Completed') {
            $log = $pdo->prepare("SELECT COUNT(*) FROM maintenance_history WHERE request_id = ?");
            $log->execute([$id]);
            if ((int) $log->fetchColumn() === 0) {
                $insert = $pdo->prepare("INSERT INTO maintenance_history (equipment_id, request_id, performed_by, notes, performed_date) VALUES (?, ?, ?, ?, ?)");
                $insert->execute([$request['equipment_id'], $id, $user['id'], $request['title'], date('Y-m-d')]);
            }
        }
        setFlash('success', 'Request status updated.');
    }
    header('Location: my_requests.php');
    exit;
}

// ===== Fetch data =====
$stmt = $pdo->prepare("
    SELECT mr.*, e.name AS equipment_name, e.location
    FROM maintenance_requests mr
    JOIN equipment e ON mr.equipment_id = e.id
    WHERE mr.assigned_to = ?
    ORDER BY mr.status != 'Completed' DESC, mr.priority = 'High' DESC, mr.scheduled_date ASC
");
$stmt->execute([$user['id']]);
$requests = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<h2 class="mb-4">My Maintenance Requests</h2>

<div class="row g-3">
    <?php foreach ($requests as $req): ?>
    <div class="col-md-6">
        <div class="glass-panel p-4 h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <h6 class="mb-0"><?= htmlspecialchars($req['title']) ?></h6>
                <span class="<?= getPriorityBadge($req['priority']) ?>"><?= $req['priority'] ?></span>
            </div>
            <p class="text-muted small mb-2">
                <i class="bi bi-gear-wide-connected"></i> <?= htmlspecialchars($req['equipment_name']) ?>
                &nbsp;|&nbsp;
                <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($req['location']) ?>
            </p>
            <?php if ($req['description']): ?>
            <p class="small mb-3"><?= htmlspecialchars($req['description']) ?></p>
            <?php endif; ?>
            <p class="small text-muted mb-3">
                Scheduled: <?= formatDate($req['scheduled_date']) ?>
            </p>

            <form method="POST" action="" class="d-flex align-items-center gap-2">
                <input type="hidden" name="id" value="<?= $req['id'] ?>">
                <select name="status" class="form-select form-select-sm glass-input" style="width: auto;">
                    <?php foreach (getRequestStatuses() as $s): ?>
                    <option value="<?= $s ?>" <?= $req['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-sm btn-brand">Update</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if (empty($requests)): ?>
    <div class="col-12">
        <div class="glass-panel p-4 text-center text-muted">No maintenance requests assigned to you.</div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pageTitle = 'Maintenance Requests';

// ===== Handle Delete =====
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM maintenance_requests WHERE id = ?");
    $stmt->execute([(int) $_GET['delete']]);
    setFlash('success', 'Request deleted.');
    header('Location: manage_requests.php');
    exit;
}

// ===== Handle Create / Update =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $equipmentId   = (int) $_POST['equipment_id'];
    $assignedTo    = $_POST['assigned_to'] ?: null;
    $title         = clean($_POST['title']);
    $description   = clean($_POST['description']);
    $priority      = clean($_POST['priority']);
    $status        = clean($_POST['status']);
    $scheduledDate = $_POST['scheduled_date'] ?: null;
    $completedDate = $_POST['completed_date'] ?: null;
    $id            = $_POST['id'] ?? null;

    if ($id) {
        $stmt = $pdo->prepare("UPDATE maintenance_requests SET equipment_id=?, assigned_to=?, title=?, description=?, priority=?, status=?, scheduled_date=?, completed_date=? WHERE id=?");
        $stmt->execute([$equipmentId, $assignedTo, $title, $description, $priority, $status, $scheduledDate, $completedDate, $id]);

        // Auto-log to history when marked Completed
        if ($status === 'Completed') {
            $check = $pdo->prepare("SELECT COUNT(*) FROM maintenance_history WHERE request_id = ?");
            $check->execute([$id]);
            if ((int) $check->fetchColumn() === 0) {
                $log = $pdo->prepare("INSERT INTO maintenance_history (equipment_id, request_id, performed_by, notes, performed_date) VALUES (?, ?, ?, ?, ?)");
                $log->execute([$equipmentId, $id, $assignedTo, $title, $completedDate ?: date('Y-m-d')]);
            }
        }
        setFlash('success', 'Request updated.');
    } else {
        $stmt = $pdo->prepare("INSERT INTO maintenance_requests (equipment_id, assigned_to, title, description, priority, status, scheduled_date, completed_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$equipmentId, $assignedTo, $title, $description, $priority, $status, $scheduledDate, $completedDate]);
        setFlash('success', 'Request created.');
    }
    header('Location: manage_requests.php');
    exit;
}

// ===== Fetch data =====
$requests = $pdo->query("
    SELECT mr.*, e.name AS equipment_name, u.name AS technician_name
    FROM maintenance_requests mr
    JOIN equipment e ON mr.equipment_id = e.id
    LEFT JOIN users u ON mr.assigned_to = u.id
    ORDER BY mr.created_at DESC
")->fetchAll();

$editRequest = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM maintenance_requests WHERE id = ?");
    $stmt->execute([(int) $_GET['edit']]);
    $editRequest = $stmt->fetch();
}

$equipmentOptions = getEquipmentForDropdown();
$technicians = getTechniciansForDropdown();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Maintenance Requests</h2>
    <button class="btn btn-brand" data-bs-toggle="collapse" data-bs-target="#requestForm">
        <i class="bi bi-plus-lg"></i> New Request
    </button>
</div>

<!-- Add/Edit Form -->
<div class="collapse <?= $editRequest ? 'show' : '' ?> mb-4" id="requestForm">
    <div class="glass-panel p-4">
        <h6 class="mb-3"><?= $editRequest ? 'Edit Request' : 'New Maintenance Request' ?></h6>
        <form method="POST" action="">
            <?php if ($editRequest): ?>
                <input type="hidden" name="id" value="<?= $editRequest['id'] ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Equipment</label>
                    <select name="equipment_id" class="form-select glass-input" required>
                        <?php foreach ($equipmentOptions as $eq): ?>
                        <option value="<?= $eq['id'] ?>" <?= ($editRequest['equipment_id'] ?? null) == $eq['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($eq['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Assign To</label>
                    <select name="assigned_to" class="form-select glass-input">
                        <option value="">— Unassigned —</option>
                        <?php foreach ($technicians as $tech): ?>
                        <option value="<?= $tech['id'] ?>" <?= ($editRequest['assigned_to'] ?? null) == $tech['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($tech['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control glass-input" required
                           value="<?= htmlspecialchars($editRequest['title'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control glass-input" rows="2"><?= htmlspecialchars($editRequest['description'] ?? '') ?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select glass-input">
                        <?php foreach (getPriorities() as $p): ?>
                        <option value="<?= $p ?>" <?= ($editRequest['priority'] ?? '') === $p ? 'selected' : '' ?>><?= $p ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select glass-input">
                        <?php foreach (getRequestStatuses() as $s): ?>
                        <option value="<?= $s ?>" <?= ($editRequest['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Scheduled Date</label>
                    <input type="date" name="scheduled_date" class="form-control glass-input"
                           value="<?= $editRequest['scheduled_date'] ?? '' ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Completed Date</label>
                    <input type="date" name="completed_date" class="form-control glass-input"
                           value="<?= $editRequest['completed_date'] ?? '' ?>">
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-brand">
                    <i class="bi bi-check-lg"></i> <?= $editRequest ? 'Update' : 'Create' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Requests Table -->
<div class="glass-panel p-4">
    <div class="table-responsive">
        <table class="table table-dark table-borderless align-middle mb-0">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Equipment</th>
                    <th>Technician</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Scheduled</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $req): ?>
                <tr>
                    <td><?= htmlspecialchars($req['title']) ?></td>
                    <td><?= htmlspecialchars($req['equipment_name']) ?></td>
                    <td><?= htmlspecialchars($req['technician_name'] ?? '—') ?></td>
                    <td><span class="<?= getPriorityBadge($req['priority']) ?>"><?= $req['priority'] ?></span></td>
                    <td><span class="<?= getStatusBadge($req['status']) ?>"><?= $req['status'] ?></span></td>
                    <td><?= formatDate($req['scheduled_date']) ?></td>
                    <td class="text-end">
                        <a href="?edit=<?= $req['id'] ?>" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></a>
                        <a href="?delete=<?= $req['id'] ?>" class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('Delete this request?');"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($requests)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No maintenance requests yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

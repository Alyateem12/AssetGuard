<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pageTitle = 'Maintenance Schedule';

// ===== Handle Delete =====
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM maintenance_schedule WHERE id = ?");
    $stmt->execute([(int) $_GET['delete']]);
    setFlash('success', 'Schedule entry deleted.');
    header('Location: manage_schedule.php');
    exit;
}

// ===== Handle Create / Update =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $equipmentId  = (int) $_POST['equipment_id'];
    $frequency    = clean($_POST['frequency']);
    $lastDone     = $_POST['last_done_date'] ?: null;
    $nextDue      = $_POST['next_due_date'];
    $notes        = clean($_POST['notes']);
    $id           = $_POST['id'] ?? null;

    if ($id) {
        $stmt = $pdo->prepare("UPDATE maintenance_schedule SET equipment_id=?, frequency=?, last_done_date=?, next_due_date=?, notes=? WHERE id=?");
        $stmt->execute([$equipmentId, $frequency, $lastDone, $nextDue, $notes, $id]);
        setFlash('success', 'Schedule updated.');
    } else {
        $stmt = $pdo->prepare("INSERT INTO maintenance_schedule (equipment_id, frequency, last_done_date, next_due_date, notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$equipmentId, $frequency, $lastDone, $nextDue, $notes]);
        setFlash('success', 'Schedule created.');
    }
    header('Location: manage_schedule.php');
    exit;
}

// ===== Fetch data =====
$schedules = $pdo->query("
    SELECT ms.*, e.name AS equipment_name
    FROM maintenance_schedule ms
    JOIN equipment e ON ms.equipment_id = e.id
    ORDER BY ms.next_due_date ASC
")->fetchAll();

$editSchedule = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM maintenance_schedule WHERE id = ?");
    $stmt->execute([(int) $_GET['edit']]);
    $editSchedule = $stmt->fetch();
}

$equipmentOptions = getEquipmentForDropdown();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Maintenance Schedule</h2>
    <button class="btn btn-brand" data-bs-toggle="collapse" data-bs-target="#scheduleForm">
        <i class="bi bi-plus-lg"></i> New Schedule
    </button>
</div>

<!-- Add/Edit Form -->
<div class="collapse <?= $editSchedule ? 'show' : '' ?> mb-4" id="scheduleForm">
    <div class="glass-panel p-4">
        <h6 class="mb-3"><?= $editSchedule ? 'Edit Schedule' : 'New Preventive Schedule' ?></h6>
        <form method="POST" action="">
            <?php if ($editSchedule): ?>
                <input type="hidden" name="id" value="<?= $editSchedule['id'] ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Equipment</label>
                    <select name="equipment_id" class="form-select glass-input" required>
                        <?php foreach ($equipmentOptions as $eq): ?>
                        <option value="<?= $eq['id'] ?>" <?= ($editSchedule['equipment_id'] ?? null) == $eq['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($eq['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Frequency</label>
                    <select name="frequency" class="form-select glass-input">
                        <?php foreach (getFrequencies() as $f): ?>
                        <option value="<?= $f ?>" <?= ($editSchedule['frequency'] ?? '') === $f ? 'selected' : '' ?>><?= $f ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Last Done</label>
                    <input type="date" name="last_done_date" class="form-control glass-input"
                           value="<?= $editSchedule['last_done_date'] ?? '' ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Next Due Date</label>
                    <input type="date" name="next_due_date" class="form-control glass-input" required
                           value="<?= $editSchedule['next_due_date'] ?? '' ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control glass-input" rows="2"><?= htmlspecialchars($editSchedule['notes'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-brand">
                    <i class="bi bi-check-lg"></i> <?= $editSchedule ? 'Update' : 'Save' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Schedule Table -->
<div class="glass-panel p-4">
    <div class="table-responsive">
        <table class="table table-dark table-borderless align-middle mb-0">
            <thead>
                <tr>
                    <th>Equipment</th>
                    <th>Frequency</th>
                    <th>Last Done</th>
                    <th>Next Due</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($schedules as $s):
                    $isOverdue = strtotime($s['next_due_date']) < strtotime(date('Y-m-d'));
                    $isSoon = !$isOverdue && strtotime($s['next_due_date']) <= strtotime('+7 days');
                ?>
                <tr>
                    <td><?= htmlspecialchars($s['equipment_name']) ?></td>
                    <td><?= $s['frequency'] ?></td>
                    <td><?= formatDate($s['last_done_date']) ?></td>
                    <td><?= formatDate($s['next_due_date']) ?></td>
                    <td>
                        <?php if ($isOverdue): ?>
                            <span class="badge-status-danger px-2 py-1 rounded-pill">Overdue</span>
                        <?php elseif ($isSoon): ?>
                            <span class="badge-status-warning px-2 py-1 rounded-pill">Due Soon</span>
                        <?php else: ?>
                            <span class="badge-status-good px-2 py-1 rounded-pill">On Track</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <a href="?edit=<?= $s['id'] ?>" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></a>
                        <a href="?delete=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('Delete this schedule entry?');"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($schedules)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No preventive schedules set yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

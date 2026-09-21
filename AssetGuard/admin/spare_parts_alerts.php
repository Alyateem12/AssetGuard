<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pageTitle = 'Spare Parts Alerts';

// ===== Handle Delete =====
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM spare_parts_alerts WHERE id = ?");
    $stmt->execute([(int) $_GET['delete']]);
    setFlash('success', 'Part removed.');
    header('Location: spare_parts_alerts.php');
    exit;
}

// ===== Handle Create / Update =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $equipmentId = (int) $_POST['equipment_id'];
    $partName    = clean($_POST['part_name']);
    $quantity    = (int) $_POST['quantity_available'];
    $threshold   = (int) $_POST['alert_threshold'];
    $id          = $_POST['id'] ?? null;

    if ($id) {
        $stmt = $pdo->prepare("UPDATE spare_parts_alerts SET equipment_id=?, part_name=?, quantity_available=?, alert_threshold=? WHERE id=?");
        $stmt->execute([$equipmentId, $partName, $quantity, $threshold, $id]);
        setFlash('success', 'Part updated.');
    } else {
        $stmt = $pdo->prepare("INSERT INTO spare_parts_alerts (equipment_id, part_name, quantity_available, alert_threshold) VALUES (?, ?, ?, ?)");
        $stmt->execute([$equipmentId, $partName, $quantity, $threshold]);
        setFlash('success', 'Part added.');
    }
    header('Location: spare_parts_alerts.php');
    exit;
}

// ===== Fetch data =====
$parts = $pdo->query("
    SELECT sp.*, e.name AS equipment_name
    FROM spare_parts_alerts sp
    JOIN equipment e ON sp.equipment_id = e.id
    ORDER BY (sp.quantity_available <= sp.alert_threshold) DESC, sp.quantity_available ASC
")->fetchAll();

$editPart = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM spare_parts_alerts WHERE id = ?");
    $stmt->execute([(int) $_GET['edit']]);
    $editPart = $stmt->fetch();
}

$equipmentOptions = getEquipmentForDropdown();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Spare Parts Alerts</h2>
    <button class="btn btn-brand" data-bs-toggle="collapse" data-bs-target="#partForm">
        <i class="bi bi-plus-lg"></i> Add Part
    </button>
</div>

<!-- Add/Edit Form -->
<div class="collapse <?= $editPart ? 'show' : '' ?> mb-4" id="partForm">
    <div class="glass-panel p-4">
        <h6 class="mb-3"><?= $editPart ? 'Edit Part' : 'New Spare Part' ?></h6>
        <form method="POST" action="">
            <?php if ($editPart): ?>
                <input type="hidden" name="id" value="<?= $editPart['id'] ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Equipment</label>
                    <select name="equipment_id" class="form-select glass-input" required>
                        <?php foreach ($equipmentOptions as $eq): ?>
                        <option value="<?= $eq['id'] ?>" <?= ($editPart['equipment_id'] ?? null) == $eq['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($eq['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Part Name</label>
                    <input type="text" name="part_name" class="form-control glass-input" required
                           value="<?= htmlspecialchars($editPart['part_name'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Quantity Available</label>
                    <input type="number" min="0" name="quantity_available" class="form-control glass-input" required
                           value="<?= $editPart['quantity_available'] ?? 0 ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Alert Threshold</label>
                    <input type="number" min="0" name="alert_threshold" class="form-control glass-input" required
                           value="<?= $editPart['alert_threshold'] ?? 1 ?>">
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-brand">
                    <i class="bi bi-check-lg"></i> <?= $editPart ? 'Update' : 'Save' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Parts Table -->
<div class="glass-panel p-4">
    <div class="table-responsive">
        <table class="table table-dark table-borderless align-middle mb-0">
            <thead>
                <tr>
                    <th>Part Name</th>
                    <th>Equipment</th>
                    <th>Available</th>
                    <th>Threshold</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($parts as $p):
                    $low = $p['quantity_available'] <= $p['alert_threshold'];
                ?>
                <tr>
                    <td><?= htmlspecialchars($p['part_name']) ?></td>
                    <td><?= htmlspecialchars($p['equipment_name']) ?></td>
                    <td><?= $p['quantity_available'] ?></td>
                    <td><?= $p['alert_threshold'] ?></td>
                    <td>
                        <?php if ($low): ?>
                            <span class="badge-status-danger px-2 py-1 rounded-pill"><i class="bi bi-exclamation-triangle"></i> Low Stock</span>
                        <?php else: ?>
                            <span class="badge-status-good px-2 py-1 rounded-pill">Sufficient</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <a href="?edit=<?= $p['id'] ?>" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></a>
                        <a href="?delete=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('Remove this part?');"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($parts)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No spare parts tracked yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

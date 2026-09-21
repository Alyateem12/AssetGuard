<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pageTitle = 'Manage Equipment';

// ===== Handle Delete =====
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM equipment WHERE id = ?");
    $stmt->execute([(int) $_GET['delete']]);
    setFlash('success', 'Equipment deleted.');
    header('Location: manage_equipment.php');
    exit;
}

// ===== Handle Create / Update =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = clean($_POST['name']);
    $type        = clean($_POST['type']);
    $location    = clean($_POST['location']);
    $status      = clean($_POST['status']);
    $riskLevel   = clean($_POST['risk_level']);
    $responsible = (int) $_POST['responsible_id'];
    $purchaseDate = $_POST['purchase_date'] ?: null;
    $id          = $_POST['id'] ?? null;

    if ($id) {
        $stmt = $pdo->prepare("UPDATE equipment SET name=?, type=?, location=?, status=?, risk_level=?, responsible_id=?, purchase_date=? WHERE id=?");
        $stmt->execute([$name, $type, $location, $status, $riskLevel, $responsible, $purchaseDate, $id]);
        setFlash('success', 'Equipment updated.');
    } else {
        $stmt = $pdo->prepare("INSERT INTO equipment (name, type, location, status, risk_level, responsible_id, purchase_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $type, $location, $status, $riskLevel, $responsible, $purchaseDate]);
        setFlash('success', 'Equipment added.');
    }
    header('Location: manage_equipment.php');
    exit;
}

// ===== Fetch data =====
$equipmentList = $pdo->query("
    SELECT e.*, u.name AS responsible_name
    FROM equipment e
    LEFT JOIN users u ON e.responsible_id = u.id
    ORDER BY e.created_at DESC
")->fetchAll();

$editEquipment = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM equipment WHERE id = ?");
    $stmt->execute([(int) $_GET['edit']]);
    $editEquipment = $stmt->fetch();
}

$technicians = getTechniciansForDropdown();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Manage Equipment</h2>
    <button class="btn btn-brand" data-bs-toggle="collapse" data-bs-target="#equipmentForm">
        <i class="bi bi-plus-lg"></i> Add Equipment
    </button>
</div>

<!-- Add/Edit Form -->
<div class="collapse <?= $editEquipment ? 'show' : '' ?> mb-4" id="equipmentForm">
    <div class="glass-panel p-4">
        <h6 class="mb-3"><?= $editEquipment ? 'Edit Equipment' : 'New Equipment' ?></h6>
        <form method="POST" action="">
            <?php if ($editEquipment): ?>
                <input type="hidden" name="id" value="<?= $editEquipment['id'] ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Equipment Name</label>
                    <input type="text" name="name" class="form-control glass-input" required
                           value="<?= htmlspecialchars($editEquipment['name'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select glass-input" required>
                        <?php foreach (getEquipmentTypes() as $type): ?>
                        <option value="<?= $type ?>" <?= ($editEquipment['type'] ?? '') === $type ? 'selected' : '' ?>><?= $type ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Location</label>
                    <input type="text" name="location" class="form-control glass-input"
                           value="<?= htmlspecialchars($editEquipment['location'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Responsible Technician</label>
                    <select name="responsible_id" class="form-select glass-input">
                        <option value="">— None —</option>
                        <?php foreach ($technicians as $tech): ?>
                        <option value="<?= $tech['id'] ?>" <?= ($editEquipment['responsible_id'] ?? null) == $tech['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($tech['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select glass-input">
                        <?php foreach (getEquipmentStatuses() as $status): ?>
                        <option value="<?= $status ?>" <?= ($editEquipment['status'] ?? '') === $status ? 'selected' : '' ?>><?= $status ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Risk Level</label>
                    <select name="risk_level" class="form-select glass-input">
                        <?php foreach (getRiskLevels() as $risk): ?>
                        <option value="<?= $risk ?>" <?= ($editEquipment['risk_level'] ?? '') === $risk ? 'selected' : '' ?>><?= $risk ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Purchase Date</label>
                    <input type="date" name="purchase_date" class="form-control glass-input"
                           value="<?= $editEquipment['purchase_date'] ?? '' ?>">
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-brand">
                    <i class="bi bi-check-lg"></i> <?= $editEquipment ? 'Update' : 'Save' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Equipment Table -->
<div class="glass-panel p-4">
    <div class="table-responsive">
        <table class="table table-dark table-borderless align-middle mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Location</th>
                    <th>Responsible</th>
                    <th>Status</th>
                    <th>Risk</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($equipmentList as $eq): ?>
                <tr>
                    <td><?= htmlspecialchars($eq['name']) ?></td>
                    <td><?= htmlspecialchars($eq['type']) ?></td>
                    <td><?= htmlspecialchars($eq['location']) ?></td>
                    <td><?= htmlspecialchars($eq['responsible_name'] ?? '—') ?></td>
                    <td><span class="<?= getStatusBadge($eq['status']) ?>"><?= $eq['status'] ?></span></td>
                    <td><span class="<?= getRiskLevelBadge($eq['risk_level']) ?>"><?= $eq['risk_level'] ?></span></td>
                    <td class="text-end">
                        <a href="?edit=<?= $eq['id'] ?>" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></a>
                        <a href="?delete=<?= $eq['id'] ?>" class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('Delete this equipment?');"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($equipmentList)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No equipment added yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

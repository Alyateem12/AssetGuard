<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pageTitle = 'Manage Technicians';

// ===== Handle Delete =====
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'technician'");
    $stmt->execute([(int) $_GET['delete']]);
    setFlash('success', 'Technician removed.');
    header('Location: manage_technicians.php');
    exit;
}

// ===== Handle Create / Update =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = clean($_POST['name']);
    $email = clean($_POST['email']);
    $id    = $_POST['id'] ?? null;

    if ($id) {
        if (!empty($_POST['password'])) {
            $hashed = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, password=? WHERE id=? AND role='technician'");
            $stmt->execute([$name, $email, $hashed, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=? WHERE id=? AND role='technician'");
            $stmt->execute([$name, $email, $id]);
        }
        setFlash('success', 'Technician updated.');
    } else {
        $hashed = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'technician')");
        $stmt->execute([$name, $email, $hashed]);
        setFlash('success', 'Technician added.');
    }
    header('Location: manage_technicians.php');
    exit;
}

// ===== Fetch data =====
$technicians = $pdo->query("
    SELECT u.*,
        (SELECT COUNT(*) FROM equipment WHERE responsible_id = u.id) AS equipment_count,
        (SELECT COUNT(*) FROM maintenance_requests WHERE assigned_to = u.id AND status != 'Completed') AS pending_requests
    FROM users u
    WHERE u.role = 'technician'
    ORDER BY u.name
")->fetchAll();

$editTech = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'technician'");
    $stmt->execute([(int) $_GET['edit']]);
    $editTech = $stmt->fetch();
}

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Manage Technicians</h2>
    <button class="btn btn-brand" data-bs-toggle="collapse" data-bs-target="#techForm">
        <i class="bi bi-plus-lg"></i> Add Technician
    </button>
</div>

<!-- Add/Edit Form -->
<div class="collapse <?= $editTech ? 'show' : '' ?> mb-4" id="techForm">
    <div class="glass-panel p-4">
        <h6 class="mb-3"><?= $editTech ? 'Edit Technician' : 'New Technician' ?></h6>
        <form method="POST" action="">
            <?php if ($editTech): ?>
                <input type="hidden" name="id" value="<?= $editTech['id'] ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control glass-input" required
                           value="<?= htmlspecialchars($editTech['name'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control glass-input" required
                           value="<?= htmlspecialchars($editTech['email'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Password <?= $editTech ? '(leave blank to keep unchanged)' : '' ?></label>
                    <input type="password" name="password" class="form-control glass-input" <?= $editTech ? '' : 'required' ?>>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-brand">
                    <i class="bi bi-check-lg"></i> <?= $editTech ? 'Update' : 'Save' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Technicians Table -->
<div class="glass-panel p-4">
    <div class="table-responsive">
        <table class="table table-dark table-borderless align-middle mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Assigned Equipment</th>
                    <th>Pending Requests</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($technicians as $tech): ?>
                <tr>
                    <td><?= htmlspecialchars($tech['name']) ?></td>
                    <td><?= htmlspecialchars($tech['email']) ?></td>
                    <td><?= $tech['equipment_count'] ?></td>
                    <td><?= $tech['pending_requests'] ?></td>
                    <td class="text-end">
                        <a href="?edit=<?= $tech['id'] ?>" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></a>
                        <a href="?delete=<?= $tech['id'] ?>" class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('Remove this technician?');"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($technicians)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">No technicians added yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

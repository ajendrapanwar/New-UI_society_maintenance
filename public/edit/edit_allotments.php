<?php
require_once __DIR__ . '/../../core/config.php';

/* ===== ACCESS CONTROL ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . 'logout.php');
    exit();
}

$errors = [];

/* ===== HANDLE UPDATE ===== */
if (isset($_POST['edit_allotment'])) {

    $id            = trim($_POST['id'] ?? '');
    $user_id       = trim($_POST['user_id'] ?? '');
    $flat_id       = trim($_POST['flat_id'] ?? '');
    $move_in_date  = trim($_POST['move_in_date'] ?? '');
    $move_out_date = trim($_POST['move_out_date'] ?? '');

    /* ===== VALIDATION ===== */
    if ($id === '' || !is_numeric($id)) {
        $errors['id'] = 'Invalid allotment ID';
    }

    if ($user_id === '') {
        $errors['user_id'] = 'Please select a user';
    }

    if ($flat_id === '') {
        $errors['flat_id'] = 'Please select a flat';
    }

    if ($move_in_date === '') {
        $errors['move_in_date'] = 'Move-in date is required';
    }

    if (
        $move_out_date !== '' &&
        strtotime($move_out_date) < strtotime($move_in_date)
    ) {
        $errors['move_out_date'] = 'Move-out date cannot be before move-in date';
    }

    /* ===== UPDATE ===== */
    if (empty($errors)) {

        $stmt = $pdo->prepare(
            "UPDATE allotments
             SET user_id = ?, flat_id = ?, move_in_date = ?, move_out_date = ?
             WHERE id = ?"
        );

        $stmt->execute([
            $user_id,
            $flat_id,
            $move_in_date,
            $move_out_date ?: null,
            $id
        ]);

        $_SESSION['success'] = 'Allotment updated successfully';
        header('Location: ' . BASE_URL . 'allotments.php');
        exit();
    }
}

/* ===== FETCH RECORD ===== */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ' . BASE_URL . 'allotments.php');
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM allotments WHERE id = ?");
$stmt->execute([$_GET['id']]);
$allotment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$allotment) {
    header('Location: ' . BASE_URL . 'allotments.php');
    exit();
}

/* ===== DATA ===== */
$users = $pdo->query(
    "SELECT id, name FROM users ORDER BY name"
)->fetchAll(PDO::FETCH_ASSOC);

$flats = $pdo->query(
    "SELECT id, block_number, flat_number FROM flats ORDER BY block_number, flat_number"
)->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../../resources/layout/header.php';
?>

<div class="container-fluid px-4 mb-4">
    <h1 class="mt-4">Edit Allotment</h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>allotments.php">Allotments</a></li>
        <li class="breadcrumb-item active">Edit</li>
    </ol>

    <div class="col-md-5">

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Edit Allotment</h5>
            </div>

            <div class="card-body">
                <form method="post">

                    <!-- USER -->
                    <div class="mb-3">
                        <label class="form-label">User</label>
                        <select name="user_id"
                            class="form-select <?= isset($errors['user_id']) ? 'is-invalid' : '' ?>">
                            <option value="">-- Select User --</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>"
                                    <?= (($_POST['user_id'] ?? $allotment['user_id']) == $user['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['user_id'])): ?>
                            <div class="invalid-feedback"><?= $errors['user_id'] ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- FLAT -->
                    <div class="mb-3">
                        <label class="form-label">Flat</label>
                        <select name="flat_id"
                            class="form-select <?= isset($errors['flat_id']) ? 'is-invalid' : '' ?>">
                            <option value="">-- Select Flat --</option>
                            <?php foreach ($flats as $flat): ?>
                                <option value="<?= $flat['id'] ?>"
                                    <?= (($_POST['flat_id'] ?? $allotment['flat_id']) == $flat['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($flat['block_number']) ?> -
                                    <?= htmlspecialchars($flat['flat_number']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['flat_id'])): ?>
                            <div class="invalid-feedback"><?= $errors['flat_id'] ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- MOVE IN -->
                    <div class="mb-3">
                        <label class="form-label">Move In Date</label>
                        <input type="date" name="move_in_date"
                            class="form-control <?= isset($errors['move_in_date']) ? 'is-invalid' : '' ?>"
                            value="<?= htmlspecialchars($_POST['move_in_date'] ?? $allotment['move_in_date']) ?>">
                        <?php if (isset($errors['move_in_date'])): ?>
                            <div class="invalid-feedback"><?= $errors['move_in_date'] ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- MOVE OUT -->
                    <div class="mb-3">
                        <label class="form-label">Move Out Date</label>
                        <input type="date" name="move_out_date"
                            class="form-control <?= isset($errors['move_out_date']) ? 'is-invalid' : '' ?>"
                            value="<?= htmlspecialchars($_POST['move_out_date'] ?? $allotment['move_out_date']) ?>">
                        <?php if (isset($errors['move_out_date'])): ?>
                            <div class="invalid-feedback"><?= $errors['move_out_date'] ?></div>
                        <?php endif; ?>
                    </div>

                    <input type="hidden" name="id" value="<?= $allotment['id'] ?>">

                    <button type="submit" name="edit_allotment" class="btn btn-primary">
                        Update Allotment
                    </button>
                    <a href="<?= BASE_URL ?>allotments.php" class="btn btn-secondary">
                        Back
                    </a>

                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../resources/layout/footer.php'; ?>

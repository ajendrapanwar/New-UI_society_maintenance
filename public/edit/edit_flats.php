<?php

require_once __DIR__ . '/../../core/config.php';

// ===== ACCESS CONTROL =====
// if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
//     header('Location: ' . BASE_URL . 'logout.php');
//     exit();
// }

// Admin access check
requireRole(['admin']);

$errors = [];

// ===== FETCH FLAT DATA =====
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ' . BASE_URL . 'flats.php');
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM flats WHERE id = ?");
$stmt->execute([$_GET['id']]);
$flat = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$flat) {
    header('Location: ' . BASE_URL . 'flats.php');
    exit();
}


// ===== HANDLE FORM SUBMIT =====
if (isset($_POST['edit_flats'])) {

    $floor        = trim($_POST['floor'] ?? '');
    $flat_number  = trim($_POST['flat_number'] ?? '');
    $block_number = trim($_POST['block_number'] ?? '');
    $flat_type    = trim($_POST['flat_type'] ?? '');
    $id           = (int) $_POST['id'];

    // ===== VALIDATION =====
    if ($floor === '') {
        $errors['floor'] = 'Floor Number is required';
    } elseif (!is_numeric($floor)) {
        $errors['floor'] = 'Floor must be a number';
    }

    if ($flat_number === '') {
        $errors['flat_number'] = 'Flat Number is required';
    }

    if ($block_number === '') {
        $errors['block_number'] = 'Wing/Block is required';
    }

    if ($flat_type === '') {
        $errors['flat_type'] = 'Please select flat type';
    }

    // ===== CHECK DUPLICATE FLAT (exclude current flat) =====
    if (empty($errors)) {
        $check = $pdo->prepare("
        SELECT id FROM flats 
        WHERE floor = ? AND flat_number = ? AND block_number = ? AND id != ?
    ");
        $check->execute([$floor, $flat_number, $block_number, $id]);
        if ($check->fetch()) {
            $errors['duplicate'] = "This flat already exists on floor {$floor}, flat number {$flat_number}, block {$block_number}";
        }
    }

    // ===== UPDATE IF NO ERRORS =====
    if (empty($errors)) {
        $stmt = $pdo->prepare("
        UPDATE flats 
        SET flat_number = ?, floor = ?, block_number = ?, flat_type = ?
        WHERE id = ?
    ");
        $stmt->execute([
            $flat_number,
            $floor,
            $block_number,
            $flat_type,
            $id
        ]);

        $_SESSION['success'] = 'Flat data updated successfully';
        header('Location: ' . BASE_URL . 'flats.php');
        exit();
    }
}

include(__DIR__ . '/../../resources/layout/header.php');
?>

<div class="container-fluid px-4 mb-4">
    <h1 class="mt-4">Edit Flat</h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>flats.php">Flats Management</a></li>
        <li class="breadcrumb-item active">Edit Flat</li>
    </ol>

    <div class="col-md-6">

        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']);
                                                unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Edit Flat Data</h5>
            </div>

            <div class="card-body">

                <?php if (isset($errors['duplicate'])): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($errors['duplicate']) ?></div>
                <?php endif; ?>

                <form method="post">

                    <!-- Floor -->
                    <div class="mb-2">
                        <label class="form-label">Floor</label>
                        <input type="text" name="floor"
                            class="form-control <?= isset($errors['floor']) ? 'is-invalid' : '' ?>"
                            value="<?= htmlspecialchars($_POST['floor'] ?? $flat['floor']) ?>">
                        <?php if (isset($errors['floor'])): ?>
                            <div class="invalid-feedback"><?= $errors['floor'] ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Flat Number -->
                    <div class="mb-2">
                        <label class="form-label">Flat Number</label>
                        <input type="text" name="flat_number"
                            class="form-control <?= isset($errors['flat_number']) ? 'is-invalid' : '' ?>"
                            value="<?= htmlspecialchars($_POST['flat_number'] ?? $flat['flat_number']) ?>">
                        <?php if (isset($errors['flat_number'])): ?>
                            <div class="invalid-feedback"><?= $errors['flat_number'] ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Wing/Block -->
                    <div class="mb-2">
                        <label class="form-label">Wing/Block</label>
                        <input type="text" name="block_number"
                            class="form-control <?= isset($errors['block_number']) ? 'is-invalid' : '' ?>"
                            value="<?= htmlspecialchars($_POST['block_number'] ?? $flat['block_number']) ?>">
                        <?php if (isset($errors['block_number'])): ?>
                            <div class="invalid-feedback"><?= $errors['block_number'] ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Type -->
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="flat_type"
                            class="form-select <?= isset($errors['flat_type']) ? 'is-invalid' : '' ?>">
                            <option value="" disabled <?= !isset($_POST['flat_type']) && !$flat['flat_type'] ? 'selected' : '' ?>>Select Type</option>
                            <?php foreach ($flatTypes as $type): ?>
                                <option value="<?= htmlspecialchars($type) ?>"
                                    <?= ((isset($_POST['flat_type']) ? $_POST['flat_type'] : $flat['flat_type']) === $type) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($type) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['flat_type'])): ?>
                            <div class="invalid-feedback"><?= $errors['flat_type'] ?></div>
                        <?php endif; ?>
                    </div>

                    <input type="hidden" name="id" value="<?= $flat['id'] ?>">

                    <button type="submit" name="edit_flats" class="btn btn-primary">
                        Update Flat
                    </button>
                    <a href="<?= BASE_URL ?>flats.php" class="btn btn-secondary">
                        Back
                    </a>

                </form>
            </div>
        </div>
    </div>
</div>

<?php include(__DIR__ . '/../../resources/layout/footer.php'); ?>
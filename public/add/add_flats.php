<?php

require_once __DIR__ . '/../../core/config.php';

// ===== ACCESS CONTROL =====
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . 'logout.php');
    exit();
}

$errors = [];
$success = '';

// ===== HANDLE FORM SUBMIT =====
if (isset($_POST['add_flats'])) {

    $floor        = trim($_POST['floor'] ?? '');
    $flat_number  = trim($_POST['flat_number'] ?? '');
    $block_number = trim($_POST['block_number'] ?? '');
    $flat_type    = trim($_POST['flat_type'] ?? '');
    $created_at   = date('Y-m-d H:i:s');

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

    // ===== INSERT IF NO ERRORS =====
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO flats 
            (flat_number, floor, block_number, flat_type, created_at)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $flat_number,
            $floor,
            $block_number,
            $flat_type,
            $created_at
        ]);

        $_SESSION['success'] = 'New flat added successfully';
        header('Location: ' . BASE_URL . 'flats.php');
        exit();
    }
}

include(__DIR__ . '/../../resources/layout/header.php');
?>

<div class="container-fluid px-4 mb-4">
    <h1 class="mt-4">Add Flat</h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>flats.php">Flats Management</a></li>
        <li class="breadcrumb-item active">Add Flat</li>
    </ol>

    <div class="col-md-6">

        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Add Flat</h5>
            </div>

            <div class="card-body">
                <form method="post">

                    <!-- Floor -->
                    <div class="mb-2">
                        <label class="form-label">Floor</label>
                        <input type="text" name="floor" 
                               class="form-control <?= isset($errors['floor']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars($_POST['floor'] ?? '') ?>">
                        <?php if (isset($errors['floor'])): ?>
                            <div class="invalid-feedback"><?= $errors['floor'] ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Flat Number -->
                    <div class="mb-2">
                        <label class="form-label">Flat Number</label>
                        <input type="text" name="flat_number" 
                               class="form-control <?= isset($errors['flat_number']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars($_POST['flat_number'] ?? '') ?>">
                        <?php if (isset($errors['flat_number'])): ?>
                            <div class="invalid-feedback"><?= $errors['flat_number'] ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Wing/Block -->
                    <div class="mb-2">
                        <label class="form-label">Wing/Block</label>
                        <input type="text" name="block_number"
                               class="form-control <?= isset($errors['block_number']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars($_POST['block_number'] ?? '') ?>">
                        <?php if (isset($errors['block_number'])): ?>
                            <div class="invalid-feedback"><?= $errors['block_number'] ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Type -->
                    <div class="mb-2">
                        <label class="form-label">Type</label>
                        <select name="flat_type" 
                                class="form-select <?= isset($errors['flat_type']) ? 'is-invalid' : '' ?>">
                            <option value="" disabled <?= !isset($_POST['flat_type']) ? 'selected' : '' ?>>Select Type</option>
                            <?php foreach ($flatTypes as $type): ?>
                                <option value="<?= htmlspecialchars($type) ?>"
                                    <?= (isset($_POST['flat_type']) && $_POST['flat_type'] === $type) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($type) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['flat_type'])): ?>
                            <div class="invalid-feedback"><?= $errors['flat_type'] ?></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" name="add_flats" class="btn btn-primary">
                        Add Flat
                    </button>

                </form>
            </div>
        </div>
    </div>
</div>

<?php include(__DIR__ . '/../../resources/layout/footer.php'); ?>

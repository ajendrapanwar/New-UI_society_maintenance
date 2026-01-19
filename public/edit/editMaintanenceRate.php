<?php

require_once __DIR__ . '/../../core/config.php';

/* ===== ACCESS CONTROL ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . 'logout.php');
    exit();
}

$errors = [];
$id = $_GET['id'] ?? 0;

/* ===== FETCH RECORD ===== */
$stmt = $pdo->prepare("SELECT * FROM maintenance_rates WHERE id = ?");
$stmt->execute([$id]);
$rateData = $stmt->fetch();

if (!$rateData) {
    header('Location: ' . BASE_URL . 'maintanenceRate.php');
    exit();
}

$flat_type = $rateData['flat_type'];
$rate      = $rateData['rate'];

/* ===== HANDLE UPDATE ===== */
if (isset($_POST['update_rate'])) {

    $flat_type = trim($_POST['flat_type'] ?? '');
    $rate      = trim($_POST['rate'] ?? '');

    /* ===== VALIDATION ===== */
    if ($flat_type === '') {
        $errors['flat_type'] = 'Please select flat type';
    }

    if ($rate === '') {
        $errors['rate'] = 'Rate is required';
    } elseif (!is_numeric($rate)) {
        $errors['rate'] = 'Rate must be numeric';
    }

    /* ===== DUPLICATE CHECK (EXCEPT CURRENT) ===== */
    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "SELECT id FROM maintenance_rates 
             WHERE flat_type = ? AND id != ?"
        );
        $stmt->execute([$flat_type, $id]);

        if ($stmt->rowCount() > 0) {
            $errors['flat_type'] = 'This flat type already exists';
        }
    }

    /* ===== UPDATE ===== */
    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "UPDATE maintenance_rates SET flat_type = ?, rate = ? WHERE id = ?"
        );
        $stmt->execute([$flat_type, $rate, $id]);

        $_SESSION['success'] = 'Maintenance rate updated successfully';
        header('Location: ' . BASE_URL . 'maintanenceRate.php');
        exit();
    }
}

include(__DIR__ . '/../../resources/layout/header.php');
?>

<div class="container-fluid px-4 mb-4">
    <h1 class="mt-4">Edit Maintenance Rate</h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item">
            <a href="<?= BASE_URL ?>dashboard.php">Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="<?= BASE_URL ?>maintanenceRate.php">Maintenance Rate</a>
        </li>
        <li class="breadcrumb-item active">Edit Maintenance Rate</li>
    </ol>

    <div class="col-md-6">

        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Edit Maintenance Rate</h5>
            </div>

            <div class="card-body">
                <form method="post">

                    <!-- Flat Type -->
                    <div class="mb-2">
                        <label class="form-label">Flat Type</label>
                        <select name="flat_type"
                                class="form-select <?= isset($errors['flat_type']) ? 'is-invalid' : '' ?>">
                            <option value="" disabled>Select Flat Type</option>
                            <?php foreach ($flatTypes as $type): ?>
                                <option value="<?= htmlspecialchars($type) ?>"
                                    <?= $flat_type === $type ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($type) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <?php if (isset($errors['flat_type'])): ?>
                            <div class="invalid-feedback">
                                <?= $errors['flat_type'] ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Rate -->
                    <div class="mb-2">
                        <label class="form-label">Rate (₹)</label>
                        <input type="text"
                               name="rate"
                               class="form-control <?= isset($errors['rate']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars($rate) ?>">

                        <?php if (isset($errors['rate'])): ?>
                            <div class="invalid-feedback">
                                <?= $errors['rate'] ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" name="update_rate" class="btn btn-primary">
                        Update Rate
                    </button>

                    <a href="<?= BASE_URL ?>maintanenceRate.php" class="btn btn-secondary">
                        Back
                    </a>

                </form>
            </div>
        </div>
    </div>
</div>

<?php include(__DIR__ . '/../../resources/layout/footer.php'); ?>

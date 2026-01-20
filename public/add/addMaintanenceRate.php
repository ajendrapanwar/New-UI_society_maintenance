<?php

require_once __DIR__ . '/../../core/config.php';

/* ===== ACCESS CONTROL ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . 'logout.php');
    exit();
}

$errors = [];
$flat_type = '';
$rate = '';
$overdue_fine = '';

/* ===== HANDLE FORM SUBMIT ===== */
if (isset($_POST['add_rate'])) {

    $flat_type = trim($_POST['flat_type'] ?? '');
    $rate      = trim($_POST['rate'] ?? '');
    $overdue_fine  = trim($_POST['overdue_fine'] ?? '');

    /* ===== VALIDATION ===== */
    if ($flat_type === '') {
        $errors['flat_type'] = 'Please select flat type';
    }

    if ($rate === '') {
        $errors['rate'] = 'Rate is required';
    } elseif (!is_numeric($rate)) {
        $errors['rate'] = 'Rate must be numeric';
    }

    if ($overdue_fine === '') {
        $errors['overdue_fine'] = 'Overdue fine is required';
    } elseif (!is_numeric($overdue_fine)) {
        $errors['overdue_fine'] = 'Overdue fine must be numeric';
    } elseif ($overdue_fine < 0) {
        $errors['overdue_fine'] = 'Overdue fine cannot be negative';
    }


    /* ===== DUPLICATE CHECK ===== */
    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "SELECT id FROM maintenance_rates WHERE flat_type = ?"
        );
        $stmt->execute([$flat_type]);

        if ($stmt->rowCount() > 0) {
            $errors['flat_type'] = 'This flat type already exists';
        }
    }

    /* ===== INSERT ===== */
    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "INSERT INTO maintenance_rates (flat_type, rate, overdue_fine)
     VALUES (?, ?, ?)"
        );
        $stmt->execute([$flat_type, $rate, $overdue_fine]);


        $_SESSION['success'] = 'Maintenance rate added successfully';
        header('Location: ' . BASE_URL . 'maintanenceRate.php');
        exit();
    }
}

include(__DIR__ . '/../../resources/layout/header.php');
?>

<div class="container-fluid px-4 mb-4">
    <h1 class="mt-4">Add Maintenance Rate</h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item">
            <a href="<?= BASE_URL ?>dashboard.php">Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="<?= BASE_URL ?>maintanenceRate.php">Maintenance Rate</a>
        </li>
        <li class="breadcrumb-item active">Add Maintenance Rate</li>
    </ol>

    <div class="col-md-6">

        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_SESSION['success']);
                unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Add Maintenance Rate</h5>
            </div>

            <div class="card-body">
                <form method="post">

                    <!-- Flat Type -->
                    <div class="mb-2">
                        <label class="form-label">Flat Type</label>
                        <select name="flat_type"
                            class="form-select <?= isset($errors['flat_type']) ? 'is-invalid' : '' ?>">
                            <option value="" disabled <?= $flat_type === '' ? 'selected' : '' ?>>
                                Select Flat Type
                            </option>
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

                    <!-- Overdue Fine -->
                    <div class="mb-2">
                        <label class="form-label">Overdue Fine (₹)</label>
                        <input type="text"
                            name="overdue_fine"
                            class="form-control <?= isset($errors['overdue_fine']) ? 'is-invalid' : '' ?>"
                            value="<?= htmlspecialchars($overdue_fine) ?>">

                        <?php if (isset($errors['overdue_fine'])): ?>
                            <div class="invalid-feedback">
                                <?= $errors['overdue_fine'] ?>
                            </div>
                        <?php endif; ?>
                    </div>


                    <button type="submit" name="add_rate" class="btn btn-primary">
                        Add Rate
                    </button>

                </form>
            </div>
        </div>
    </div>
</div>

<?php include(__DIR__ . '/../../resources/layout/footer.php'); ?>
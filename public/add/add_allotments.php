<?php
require_once __DIR__ . '/../../core/config.php';

/* ================= ACCESS CONTROL ================= */
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . 'logout.php');
    exit();
}

$errors = [];

/* ================= HANDLE FORM SUBMIT ================= */
if (isset($_POST['add_allotment'])) {

    $user_id      = trim($_POST['user_id'] ?? '');
    $flat_id      = trim($_POST['flat_id'] ?? '');
    $move_in_date = trim($_POST['move_in_date'] ?? '');
    $created_at   = date('Y-m-d H:i:s');

    /* ================= VALIDATION ================= */
    if ($user_id === '') {
        $errors['user_id'] = 'Please select a user';
    }

    if ($flat_id === '') {
        $errors['flat_id'] = 'Please select a flat';
    }

    if ($move_in_date === '') {
        $errors['move_in_date'] = 'Move-in date is required';
    }

    /* ================= INSERT ================= */
    if (empty($errors)) {

        $stmt = $pdo->prepare(
            "INSERT INTO allotments 
             (user_id, flat_id, move_in_date, created_at)
             VALUES (?, ?, ?, ?)"
        );

        $stmt->execute([
            $user_id,
            $flat_id,
            $move_in_date,
            $created_at
        ]);

        $_SESSION['success'] = 'Allotment added successfully';
        header('Location: ' . BASE_URL . 'allotments.php');
        exit();
    }
}

/* ================= FETCH USERS WITHOUT ALLOTMENT ================= */
$users = $pdo->query(
    "SELECT u.id, u.name
     FROM users u
     LEFT JOIN allotments a ON u.id = a.user_id
     WHERE a.user_id IS NULL
     ORDER BY u.name"
)->fetchAll(PDO::FETCH_ASSOC);

/* ================= FETCH FLATS NOT ALLOTTED ================= */
$flats = $pdo->query(
    "SELECT f.id, f.block_number, f.flat_number
     FROM flats f
     LEFT JOIN allotments a ON f.id = a.flat_id
     WHERE a.flat_id IS NULL
     ORDER BY f.block_number, f.flat_number"
)->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../../resources/layout/header.php';
?>

<div class="container-fluid px-4 mb-4">
    <h1 class="mt-4">Add Allotment</h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item">
            <a href="<?= BASE_URL ?>dashboard.php">Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <a href="<?= BASE_URL ?>allotments.php">Allotments</a>
        </li>
        <li class="breadcrumb-item active">Add</li>
    </ol>

    <div class="col-md-5">

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Add Allotment</h5>
            </div>

            <div class="card-body">
                <form method="post">

                    <!-- USER -->
                    <div class="mb-2">
                        <label class="form-label">User</label>
                        <select name="user_id"
                            class="form-select <?= isset($errors['user_id']) ? 'is-invalid' : '' ?>">
                            <option value="">-- Select User --</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>"
                                    <?= (($_POST['user_id'] ?? '') == $user['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['user_id'])): ?>
                            <div class="invalid-feedback"><?= $errors['user_id'] ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- FLAT -->
                    <div class="mb-2">
                        <label class="form-label">Flat</label>
                        <select name="flat_id"
                            class="form-select <?= isset($errors['flat_id']) ? 'is-invalid' : '' ?>">
                            <option value="">-- Select Flat --</option>
                            <?php foreach ($flats as $flat): ?>
                                <option value="<?= $flat['id'] ?>"
                                    <?= (($_POST['flat_id'] ?? '') == $flat['id']) ? 'selected' : '' ?>>
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
                            value="<?= htmlspecialchars($_POST['move_in_date'] ?? '') ?>">
                        <?php if (isset($errors['move_in_date'])): ?>
                            <div class="invalid-feedback"><?= $errors['move_in_date'] ?></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" name="add_allotment" class="btn btn-primary">
                        Add Allotment
                    </button>

                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../resources/layout/footer.php'; ?>

<?php
require_once __DIR__ . '/../../core/config.php';

/* =======================
   ACCESS CONTROL
======================= */
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . 'logout.php');
    exit();
}

$errors = [];

/* =======================
   UPDATE ALLOTMENT
======================= */
if (isset($_POST['edit_allotment'])) {

    $id            = $_POST['id'] ?? '';
    $user_id       = $_POST['user_id'] ?? '';
    $flat_id       = $_POST['flat_id'] ?? '';
    $move_in_date  = $_POST['move_in_date'] ?? '';
    $move_out_date = $_POST['move_out_date'] ?? null;

    if (empty($id) || !is_numeric($id)) {
        $errors[] = 'Invalid allotment ID';
    }

    if (empty($user_id)) {
        $errors[] = 'User is required';
    }

    if (empty($flat_id)) {
        $errors[] = 'Flat is required';
    }

    if (empty($move_in_date)) {
        $errors[] = 'Move-in date is required';
    }

    if (
        !empty($move_out_date) &&
        strtotime($move_out_date) < strtotime($move_in_date)
    ) {
        $errors[] = 'Move-out date cannot be before move-in date';
    }

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

/* =======================
   FETCH ALLOTMENT
======================= */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ' . BASE_URL . 'allotments.php');
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM allotments WHERE id = ?");
$stmt->execute([$_GET['id']]);
$allotments = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$allotments) {
    header('Location: ' . BASE_URL . 'allotments.php');
    exit();
}

/* =======================
   FETCH USERS & FLATS
======================= */
$users = $pdo->query(
    "SELECT id, name FROM users ORDER BY name"
)->fetchAll(PDO::FETCH_ASSOC);

$flats = $pdo->query(
    "SELECT id, block_number, flat_number FROM flats ORDER BY block_number, flat_number"
)->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../../resources/layout/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Edit Allotment</h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>allotments.php">Allotments</a></li>
        <li class="breadcrumb-item active">Edit</li>
    </ol>

    <div class="col-md-4">

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Edit Allotment</h5>
            </div>

            <div class="card-body">
                <form method="post">

                    <div class="mb-3">
                        <label>User</label>
                        <select class="form-control" name="user_id" required>
                            <option value="">-- Select User --</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>"
                                    <?= $user['id'] == $allotments['user_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Flat</label>
                        <select class="form-control" name="flat_id" required>
                            <option value="">-- Select Flat --</option>
                            <?php foreach ($flats as $flat): ?>
                                <option value="<?= $flat['id'] ?>"
                                    <?= $flat['id'] == $allotments['flat_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($flat['block_number']) ?>
                                    - <?= htmlspecialchars($flat['flat_number']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Move In Date</label>
                        <input type="date" class="form-control" name="move_in_date"
                               value="<?= htmlspecialchars($allotments['move_in_date']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label>Move Out Date</label>
                        <input type="date" class="form-control" name="move_out_date"
                               value="<?= htmlspecialchars($allotments['move_out_date']) ?>">
                    </div>

                    <input type="hidden" name="id" value="<?= $allotments['id'] ?>">

                    <button type="submit" name="edit_allotment" class="btn btn-primary">
                        Update Allotment
                    </button>

                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../resources/layout/footer.php'; ?>

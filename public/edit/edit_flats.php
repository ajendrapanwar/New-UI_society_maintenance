<?php

require_once __DIR__ . '/../../core/config.php';


//    ACCESS CONTROL

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . 'logout.php');
    exit();
}

$errors = [];


//    UPDATE FLAT

if (isset($_POST['edit_flats'])) {

    $flat_number  = trim($_POST['flat_number']);
    $floor        = trim($_POST['floor']);
    $block_number = trim($_POST['block_number']);
    $flat_type    = trim($_POST['flat_type']);
    $id           = (int) $_POST['id'];

    if ($flat_number === '') {
        $errors[] = 'Flat Number is required';
    }

    if ($floor === '') {
        $errors[] = 'Floor Number is required';
    }

    if ($flat_type === '') {
        $errors[] = 'Please select flat type';
    }

    if (empty($errors)) {

        $sql = "UPDATE flats 
                SET flat_number = ?, floor = ?, block_number = ?, flat_type = ?
                WHERE id = ?";

        $pdo->prepare($sql)->execute([
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

//    FETCH FLAT DATA

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

include(__DIR__ . '/../../resources/layout/header.php');
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Edit Flat</h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>flats.php">Flats Management</a></li>
        <li class="breadcrumb-item active">Edit Flat</li>
    </ol>

    <div class="col-md-4">

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Edit Flat Data</h5>
            </div>

            <div class="card-body">
                <form method="post">

                    <div class="mb-3">
                        <label class="form-label">Flat Number</label>
                        <input type="text" class="form-control" name="flat_number"
                               value="<?= htmlspecialchars($_POST['flat_number'] ?? $flat['flat_number']) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Floor</label>
                        <input type="number" class="form-control" name="floor"
                               value="<?= htmlspecialchars($_POST['floor'] ?? $flat['floor']) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Block Number</label>
                        <input type="text" class="form-control" name="block_number"
                               value="<?= htmlspecialchars($_POST['block_number'] ?? $flat['block_number']) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="flat_type" class="form-control">
                            <option value="">Select Type</option>
                            <?php foreach ($flatTypes as $t): ?>
                                <option value="<?= $t ?>"
                                    <?= (($_POST['flat_type'] ?? $flat['flat_type']) === $t) ? 'selected' : '' ?>>
                                    <?= $t ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <input type="hidden" name="id" value="<?= $flat['id'] ?>">

                    <button type="submit" name="edit_flats" class="btn btn-primary">
                        Update Flat
                    </button>

                </form>
            </div>
        </div>
    </div>
</div>

<?php include(__DIR__ . '/../../resources/layout/footer.php'); ?>

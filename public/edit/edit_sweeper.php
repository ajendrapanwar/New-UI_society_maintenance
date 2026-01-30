<?php
require_once __DIR__ . '/../../core/config.php';

requireRole(['admin']); // Only admin

$errors = [];
$name = $mobile = $dob = $gender = $joiningDate = $address = $salary = '';
$sweeper = null;

/* ===== FETCH SWEEPER ===== */
if (isset($_GET['id']) && ctype_digit($_GET['id'])) {

    $stmt = $pdo->prepare("SELECT * FROM sweepers WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $sweeper = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sweeper) {
        header('Location: ../sweepers.php');
        exit;
    }

    // Prefill values
    $name        = $sweeper['name'];
    $mobile      = $sweeper['mobile'];
    $dob         = $sweeper['dob'];
    $gender      = $sweeper['gender'];
    $joiningDate = $sweeper['joining_date'];
    $address     = $sweeper['address'];
    $salary      = $sweeper['salary'];
} else {
    header('Location: ../sweepers.php');
    exit;
}

/* ===== HANDLE UPDATE ===== */
if (isset($_POST['edit_sweeper'])) {

    $id          = (int)$_POST['id'];
    $name        = trim($_POST['name'] ?? '');
    $mobile      = trim($_POST['mobile'] ?? '');
    $dob         = $_POST['dob'] ?? '';
    $gender      = $_POST['gender'] ?? '';
    $joiningDate = $_POST['joining_date'] ?? '';
    $address     = trim($_POST['address'] ?? '');
    $salary      = $_POST['salary'] ?? '';

    /* ===== VALIDATION ===== */
    if ($name === '') $errors['name'] = 'Please enter sweeper name';

    if ($mobile === '') {
        $errors['mobile'] = 'Please enter mobile number';
    } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $errors['mobile'] = 'Mobile must be 10 digits';
    }

    if ($dob === '' || strtotime($dob) >= strtotime(date('Y-m-d'))) {
        $errors['dob'] = 'Please select valid DOB';
    }

    if (!in_array($gender, ['Male', 'Female', 'Other'], true)) {
        $errors['gender'] = 'Please select gender';
    }

    if ($joiningDate === '') {
        $errors['joining_date'] = 'Please select joining date';
    }

    if ($address === '') {
        $errors['address'] = 'Please enter address';
    }

    if ($salary === '' || !is_numeric($salary) || $salary < 0) {
        $errors['salary'] = 'Please enter valid salary';
    }

    /* ===== DUPLICATE MOBILE CHECK ===== */
    if (empty($errors)) {
        $check = $pdo->prepare("
            SELECT id FROM sweepers 
            WHERE mobile = ? AND id != ?
        ");
        $check->execute([$mobile, $id]);
        if ($check->fetch()) {
            $errors['mobile'] = 'Mobile number already exists';
        }
    }

    /* ===== UPDATE ===== */
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE sweepers SET
                name = ?,
                mobile = ?,
                dob = ?,
                gender = ?,
                joining_date = ?,
                address = ?,
                salary = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $name,
            $mobile,
            $dob,
            $gender,
            $joiningDate,
            $address,
            $salary,
            $id
        ]);

        $_SESSION['success'] = 'Sweeper updated successfully';
        header('Location: ../sweeper.php');
        exit;
    }
}

include(__DIR__ . '/../../resources/layout/header.php');
?>

<div class="container-fluid px-4 mb-4">
    <h1 class="mt-4">Edit Sweeper</h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>sweeper.php">Sweeper List</a></li>
        <li class="breadcrumb-item active">Edit Sweeper</li>
    </ol>

    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Edit Sweeper</h5>
                </div>

                <div class="card-body">
                    <form method="post" novalidate>

                        <div class="row">

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Sweeper Name</label>
                                <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($name) ?>">
                                <?php if (isset($errors['name'])): ?><small class="text-danger"><?= $errors['name'] ?></small><?php endif; ?>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mobile</label>
                                <input type="text" class="form-control" name="mobile" value="<?= htmlspecialchars($mobile) ?>">
                                <?php if (isset($errors['mobile'])): ?><small class="text-danger"><?= $errors['mobile'] ?></small><?php endif; ?>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" name="dob" value="<?= htmlspecialchars($dob) ?>">
                                <?php if (isset($errors['dob'])): ?><small class="text-danger"><?= $errors['dob'] ?></small><?php endif; ?>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Gender</label>
                                <select class="form-select" name="gender">
                                    <option value="">Select</option>
                                    <option value="Male" <?= $gender === 'Male' ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= $gender === 'Female' ? 'selected' : '' ?>>Female</option>
                                    <option value="Other" <?= $gender === 'Other' ? 'selected' : '' ?>>Other</option>
                                </select>
                                <?php if (isset($errors['gender'])): ?><small class="text-danger"><?= $errors['gender'] ?></small><?php endif; ?>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Joining Date</label>
                                <input type="date" class="form-control" name="joining_date" value="<?= htmlspecialchars($joiningDate) ?>">
                                <?php if (isset($errors['joining_date'])): ?><small class="text-danger"><?= $errors['joining_date'] ?></small><?php endif; ?>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Salary</label>
                                <input type="number" class="form-control" name="salary" value="<?= htmlspecialchars($salary) ?>" min="0">
                                <?php if (isset($errors['salary'])): ?><small class="text-danger"><?= $errors['salary'] ?></small><?php endif; ?>
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="2"><?= htmlspecialchars($address) ?></textarea>
                                <?php if (isset($errors['address'])): ?><small class="text-danger"><?= $errors['address'] ?></small><?php endif; ?>
                            </div>

                        </div>

                        <input type="hidden" name="id" value="<?= $sweeper['id'] ?>">

                        <button type="submit" name="edit_sweeper" class="btn btn-primary">
                            Update Sweeper
                        </button>
                        <a href="<?= BASE_URL ?>sweeper.php" class="btn btn-secondary">Back</a>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include(__DIR__ . '/../../resources/layout/footer.php'); ?>

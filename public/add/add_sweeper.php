<?php
require_once __DIR__ . '/../../core/config.php';
requireRole(['admin']);

$errors = [];
$name = $mobile = $gender = $joiningDate = $address = $dob = '';
$salary = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name        = trim($_POST['name'] ?? '');
    $mobile      = trim($_POST['mobile'] ?? '');
    $dob         = $_POST['dob'] ?? '';
    $gender      = $_POST['gender'] ?? '';
    $joiningDate = $_POST['joining_date'] ?? '';
    $address     = trim($_POST['address'] ?? '');
    $salary      = trim($_POST['salary'] ?? '');

    /* ===== VALIDATION ===== */
    if ($name === '') {
        $errors['name'] = 'Please enter sweeper name';
    }

    if ($mobile === '') {
        $errors['mobile'] = 'Please enter mobile number';
    } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $errors['mobile'] = 'Mobile must be 10 digits';
    }

    if ($dob === '' || strtotime($dob) >= strtotime(date('Y-m-d'))) {
        $errors['dob'] = 'Please select a valid DOB';
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
        $errors['salary'] = 'Salary must be a valid number';
    }

    /* ===== DUPLICATE MOBILE CHECK ===== */
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM sweepers WHERE mobile = ?");
        $stmt->execute([$mobile]);
        if ($stmt->fetch()) {
            $errors['mobile'] = 'Mobile already exists';
        }
    }

    /* ===== INSERT ===== */
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO sweepers
            (name, mobile, dob, gender, joining_date, address, salary, created_at)
            VALUES (?,?,?,?,?,?,?,NOW())
        ");

        $stmt->execute([
            $name,
            $mobile,
            $dob,
            $gender,
            $joiningDate,
            $address,
            $salary
        ]);

        $_SESSION['success'] = 'Sweeper added successfully';
        header('Location: ../sweeper.php');
        exit;
    }
}

include(__DIR__ . '/../../resources/layout/header.php');
?>

<div class="container-fluid px-4 mb-4">
    <h1 class="mt-4">Add Sweeper</h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>sweeper.php">Sweeper List</a></li>
        <li class="breadcrumb-item active">Add Sweeper</li>
    </ol>

    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Add Sweeper</h5>
                </div>

                <div class="card-body">
                    <form method="post" novalidate>
                        <div class="row">

                            <!-- NAME -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Sweeper Name</label>
                                <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($name) ?>">
                                <small class="text-danger"><?= $errors['name'] ?? '' ?></small>
                            </div>

                            <!-- MOBILE -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mobile</label>
                                <input type="text" class="form-control" name="mobile" value="<?= htmlspecialchars($mobile) ?>">
                                <small class="text-danger"><?= $errors['mobile'] ?? '' ?></small>
                            </div>

                            <!-- DOB -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" name="dob" value="<?= htmlspecialchars($dob) ?>">
                                <small class="text-danger"><?= $errors['dob'] ?? '' ?></small>
                            </div>

                            <!-- GENDER -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Gender</label>
                                <select class="form-select" name="gender">
                                    <option value="">Select</option>
                                    <option value="Male" <?= $gender === 'Male' ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= $gender === 'Female' ? 'selected' : '' ?>>Female</option>
                                    <option value="Other" <?= $gender === 'Other' ? 'selected' : '' ?>>Other</option>
                                </select>
                                <small class="text-danger"><?= $errors['gender'] ?? '' ?></small>
                            </div>

                            <!-- JOINING DATE -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Joining Date</label>
                                <input type="date" class="form-control" name="joining_date" value="<?= htmlspecialchars($joiningDate) ?>">
                                <small class="text-danger"><?= $errors['joining_date'] ?? '' ?></small>
                            </div>

                            <!-- SALARY -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Salary</label>
                                <input type="number" step="0.01" class="form-control" name="salary" value="<?= htmlspecialchars($salary) ?>">
                                <small class="text-danger"><?= $errors['salary'] ?? '' ?></small>
                            </div>

                            <!-- ADDRESS -->
                            <div class="col-12 mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="2"><?= htmlspecialchars($address) ?></textarea>
                                <small class="text-danger"><?= $errors['address'] ?? '' ?></small>
                            </div>

                        </div>

                        <button type="submit" class="btn btn-primary">
                            Add Sweeper
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include(__DIR__ . '/../../resources/layout/footer.php'); ?>
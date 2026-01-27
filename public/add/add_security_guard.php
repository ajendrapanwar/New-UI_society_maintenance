<?php
require_once __DIR__ . '/../../core/config.php';
requireRole(['admin']);

$errors = [];
$name = $mobile = $gender = $shift = $joiningDate = $address = $dob = '';
$salary = '';

/* ===== HANDLE SUBMIT ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name        = trim($_POST['name'] ?? '');
    $mobile      = trim($_POST['mobile'] ?? '');
    $dob         = $_POST['user_dob'] ?? '';
    $gender      = $_POST['gender'] ?? '';
    $shift       = $_POST['shift'] ?? '';
    $joiningDate = $_POST['joining_date'] ?? '';
    $address     = trim($_POST['address'] ?? '');
    $salary      = trim($_POST['salary'] ?? '');

    /* ===== VALIDATION ===== */
    if ($name === '') $errors['name'] = 'Please enter guard name';

    if ($mobile === '') {
        $errors['mobile'] = 'Please enter mobile number';
    } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $errors['mobile'] = 'Mobile must be 10 digits';
    }

    if ($dob === '') {
        $errors['dob'] = 'Please select date of birth';
    } elseif (strtotime($dob) >= strtotime(date('Y-m-d'))) {
        $errors['dob'] = 'DOB must be a past date';
    }

    if (!in_array($gender, ['Male', 'Female', 'Other'])) {
        $errors['gender'] = 'Please select gender';
    }

    if (!in_array($shift, ['day', 'night', 'rotational'])) {
        $errors['shift'] = 'Please select shift';
    }

    if ($joiningDate === '') {
        $errors['joining_date'] = 'Please select joining date';
    }

    if ($address === '') {
        $errors['address'] = 'Please enter address';
    }

    if ($salary === '') {
        $errors['salary'] = 'Please enter salary';
    } elseif (!is_numeric($salary) || $salary < 0) {
        $errors['salary'] = 'Salary must be a valid number';
    }

    /* ===== DUPLICATE MOBILE ===== */
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM security_guards WHERE mobile = ?");
        $stmt->execute([$mobile]);

        if ($stmt->fetch()) {
            $errors['mobile'] = 'Mobile already exists';
        }
    }

    /* ===== INSERT ===== */
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO security_guards
            (name, mobile, dob, gender, shift, joining_date, address, salary, created_at)
            VALUES (?,?,?,?,?,?,?,?,NOW())
        ");
        $stmt->execute([
            $name,
            $mobile,
            $dob,
            $gender,
            $shift,
            $joiningDate,
            $address,
            $salary
        ]);

        $_SESSION['success'] = 'Security guard added successfully';
        header('Location: ../guards.php');
        exit;
    }
}

include(__DIR__ . '/../../resources/layout/header.php');
?>

<div class="container-fluid px-4 mb-4">
    <h1 class="mt-4">Add Guard</h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>guards.php">Guards List</a></li>
        <li class="breadcrumb-item active">Add Guard</li>
    </ol>

    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Add Guard</h5>
                </div>

                <div class="card-body">
                    <form method="post" novalidate>

                        <div class="row">

                            <!-- NAME -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Guard Name</label>
                                <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($name) ?>">
                                <?php if (isset($errors['name'])): ?>
                                    <small class="text-danger"><?= $errors['name'] ?></small>
                                <?php endif; ?>
                            </div>

                            <!-- MOBILE -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mobile</label>
                                <input type="text" class="form-control" name="mobile" value="<?= htmlspecialchars($mobile) ?>">
                                <?php if (isset($errors['mobile'])): ?>
                                    <small class="text-danger"><?= $errors['mobile'] ?></small>
                                <?php endif; ?>
                            </div>

                            <!-- DOB -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" name="user_dob" value="<?= htmlspecialchars($dob) ?>">
                                <?php if (isset($errors['dob'])): ?>
                                    <small class="text-danger"><?= $errors['dob'] ?></small>
                                <?php endif; ?>
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
                                <?php if (isset($errors['gender'])): ?>
                                    <small class="text-danger"><?= $errors['gender'] ?></small>
                                <?php endif; ?>
                            </div>

                            <!-- SHIFT -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Shift</label>
                                <select class="form-select" name="shift">
                                    <option value="">Select</option>
                                    <option value="day" <?= $shift === 'day' ? 'selected' : '' ?>>Day</option>
                                    <option value="night" <?= $shift === 'night' ? 'selected' : '' ?>>Night</option>
                                    <option value="rotational" <?= $shift === 'rotational' ? 'selected' : '' ?>>Rotational</option>
                                </select>
                                <?php if (isset($errors['shift'])): ?>
                                    <small class="text-danger"><?= $errors['shift'] ?></small>
                                <?php endif; ?>
                            </div>

                            <!-- JOINING DATE -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Joining Date</label>
                                <input type="date" class="form-control" name="joining_date" value="<?= htmlspecialchars($joiningDate) ?>">
                                <?php if (isset($errors['joining_date'])): ?>
                                    <small class="text-danger"><?= $errors['joining_date'] ?></small>
                                <?php endif; ?>
                            </div>

                            <!-- SALARY -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Salary</label>
                                <input type="number" step="0.01" class="form-control" name="salary" value="<?= htmlspecialchars($salary) ?>">
                                <?php if (isset($errors['salary'])): ?>
                                    <small class="text-danger"><?= $errors['salary'] ?></small>
                                <?php endif; ?>
                            </div>

                            <!-- ADDRESS -->
                            <div class="col-12 mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="2"><?= htmlspecialchars($address) ?></textarea>
                                <?php if (isset($errors['address'])): ?>
                                    <small class="text-danger"><?= $errors['address'] ?></small>
                                <?php endif; ?>
                            </div>

                        </div>

                        <button type="submit" name="add_guard" class="btn btn-primary">
                            Add Guard
                        </button>

                    </form>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include(__DIR__ . '/../../resources/layout/footer.php'); ?>

<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once __DIR__ . '/../../core/config.php';

requireRole(['admin']);

$errors = [];
$monthError = '';
$yearError  = '';
$readingError = '';
$amountError  = '';
$fileError    = '';


if (isset($_POST['add_electricity_bill'])) {

    $month   = $_POST['bill_month'] ?? '';
    $year    = $_POST['bill_year'] ?? '';
    $reading = $_POST['reading'] ?? '';
    $amount  = $_POST['amount'] ?? '';

    /* ===== VALIDATION ===== */

    // Month
    if (!ctype_digit($month) || $month < 1 || $month > 12) {
        $monthError = "Please select a valid month";
        $errors[] = 1;
    }

    // Year
    if (!ctype_digit($year)) {
        $yearError = "Please select a valid year";
        $errors[] = 1;
    }

    // Meter Reading
    if ($reading === '' || !is_numeric($reading) || $reading <= 0) {
        $readingError = "Meter reading is required";
        $errors[] = 1;
    }

    // Amount
    if ($amount === '' || !is_numeric($amount) || $amount <= 0) {
        $amountError = "Amount is required";
        $errors[] = 1;
    }

    // File
    if (!isset($_FILES['bill_file']) || $_FILES['bill_file']['error'] !== 0) {
        $fileError = "Bill file is required";
        $errors[] = 1;
    }


    /* ===== DUPLICATE MONTH CHECK ===== */
    if (!$errors) {
        $check = $pdo->prepare(
            "SELECT id FROM electricity_bills WHERE month = ? AND year = ?"
        );
        $check->execute([$month, $year]);

        if ($check->fetch()) {
            $monthError = "Electricity bill already exists for this month & year";
            $yearError  = "Electricity bill already exists for this month & year";
            $errors[] = "Duplicate month/year"; // global fail flag
        }
    }


    /* ===== FILE UPLOAD ===== */
    if (!$errors) {

        $uploadDir = __DIR__ . '/../uploads/electricity_bills/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $ext = strtolower(pathinfo($_FILES['bill_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];

        if (!in_array($ext, $allowed)) {
            $errors[] = 'Invalid file type';
        } else {

            $fileName = "electricity_{$year}_{$month}_" . time() . "." . $ext;
            $filePath = $uploadDir . $fileName;

            if (!move_uploaded_file($_FILES['bill_file']['tmp_name'], $filePath)) {
                $errors[] = 'File upload failed';
            }
        }
    }

    /* ===== INSERT ===== */
    if (!$errors) {

        $stmt = $pdo->prepare("
            INSERT INTO electricity_bills
            (month, year, reading, amount, bill_file)
            VALUES (?,?,?,?,?)
        ");

        $stmt->execute([$month, $year, $reading, $amount, $fileName]);

        $_SESSION['success'] = 'Electricity bill added successfully';
        header('Location:' . BASE_URL . 'electricity_bills.php');
        exit;
    }
}

include(__DIR__ . '/../../resources/layout/header.php');
?>

<div class="container-fluid px-4 mb-4">
    <h1 class="mt-4">Add Electricity Bill</h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="../electricity_bills.php">Electricity Bills
            </a></li>
        <li class="breadcrumb-item active">Add User</li>
    </ol>

    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Add Electricity Bill</h5>
                </div>
                <div class="card-body">

                    <form method="post" enctype="multipart/form-data" novalidate>

                        <div class="row">

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Month</label>
                                <select name="bill_month" class="form-control <?= $monthError ? 'is-invalid' : '' ?>" required>
                                    <option value="">Select Month</option>
                                    <?php for ($m = 1; $m <= 12; $m++): ?>
                                        <option value="<?= $m ?>" <?= ($month == $m) ? 'selected' : '' ?>>
                                            <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>

                                <?php if ($monthError): ?>
                                    <div class="invalid-feedback"><?= $monthError ?></div>
                                <?php endif; ?>
                            </div>


                            <div class="col-md-6 mb-3">
                                <label class="form-label">Year</label>
                                <select name="bill_year" class="form-control <?= $yearError ? 'is-invalid' : '' ?>" required>
                                    <option value="">Select Year</option>
                                    <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                                        <option value="<?= $y ?>" <?= ($year == $y) ? 'selected' : '' ?>><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>

                                <?php if ($yearError): ?>
                                    <div class="invalid-feedback"><?= $yearError ?></div>
                                <?php endif; ?>
                            </div>


                            <div class="col-md-6 mb-3">
                                <label class="form-label">Meter Reading</label>
                                <input type="number" step="0.01" name="reading"
                                    value="<?= htmlspecialchars($reading ?? '') ?>"
                                    class="form-control <?= $readingError ? 'is-invalid' : '' ?>">

                                <?php if ($readingError): ?>
                                    <div class="invalid-feedback"><?= $readingError ?></div>
                                <?php endif; ?>
                            </div>


                            <div class="col-md-6 mb-3">
                                <label class="form-label">Amount</label>
                                <input type="number" step="0.01" name="amount"
                                    value="<?= htmlspecialchars($amount ?? '') ?>"
                                    class="form-control <?= $amountError ? 'is-invalid' : '' ?>">

                                <?php if ($amountError): ?>
                                    <div class="invalid-feedback"><?= $amountError ?></div>
                                <?php endif; ?>
                            </div>


                            <div class="col-md-6 mb-3">
                                <label class="form-label">Upload Bill</label>
                                <input type="file" name="bill_file"
                                    class="form-control <?= $fileError ? 'is-invalid' : '' ?>"
                                    accept=".jpg,.jpeg,.png,.pdf">

                                <?php if ($fileError): ?>
                                    <div class="invalid-feedback"><?= $fileError ?></div>
                                <?php endif; ?>
                            </div>

                        </div>

                        <button type="submit" name="add_electricity_bill" class="btn btn-primary">Submit</button>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include(__DIR__ . '/../../resources/layout/footer.php'); ?>
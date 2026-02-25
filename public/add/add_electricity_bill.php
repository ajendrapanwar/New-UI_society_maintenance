<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/helpers.php';

requireRole(['admin']);

$errors = [];
$monthError = '';
$yearError  = '';
$readingError = '';
$amountError  = '';
$fileError    = '';
$receiptError = '';

$month   = '';
$year    = '';
$reading = '';
$amount  = '';

$fileName = '';
$receiptFileName = '';

if (isset($_POST['add_electricity_bill'])) {

    $month   = $_POST['bill_month'] ?? '';
    $year    = $_POST['bill_year'] ?? '';
    $reading = $_POST['reading'] ?? '';
    $amount  = $_POST['amount'] ?? '';

    /* ===== VALIDATION ===== */

    // Month Validation
    if (!ctype_digit($month) || $month < 1 || $month > 12) {
        $monthError = "Please select a valid month";
        $errors[] = 1;
    }

    // Year Validation
    if (!ctype_digit($year)) {
        $yearError = "Please select a valid year";
        $errors[] = 1;
    }

    // Meter Reading Validation
    if ($reading === '' || !is_numeric($reading) || $reading <= 0) {
        $readingError = "Meter reading is required and must be greater than 0";
        $errors[] = 1;
    }

    // Amount Validation
    if ($amount === '' || !is_numeric($amount) || $amount <= 0) {
        $amountError = "Amount is required and must be greater than 0";
        $errors[] = 1;
    }

    // Bill File Required
    if (!isset($_FILES['bill_file']) || $_FILES['bill_file']['error'] !== 0) {
        $fileError = "Electricity bill file is required";
        $errors[] = 1;
    }

    // Receipt File (Optional but validate if uploaded)
    if (isset($_FILES['bill_receipt']) && $_FILES['bill_receipt']['error'] !== 4) {
        if ($_FILES['bill_receipt']['error'] !== 0) {
            $receiptError = "Receipt upload failed";
            $errors[] = 1;
        }
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
            $errors[] = 1;
        }
    }

    /* ===== FILE UPLOAD ===== */
    if (!$errors) {

        $uploadDir = __DIR__ . '/../uploads/electricity_bills/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];

        // ===== MAIN BILL FILE (REQUIRED) =====
        $billExt = strtolower(pathinfo($_FILES['bill_file']['name'], PATHINFO_EXTENSION));

        if (!in_array($billExt, $allowed)) {
            $fileError = "Invalid bill file type (Allowed: jpg, jpeg, png, pdf)";
            $errors[] = 1;
        } elseif ($_FILES['bill_file']['size'] > 5 * 1024 * 1024) { // 5MB limit
            $fileError = "Bill file size must be less than 5MB";
            $errors[] = 1;
        } else {
            $fileName = "electricity_bill_{$year}_{$month}_" . time() . "." . $billExt;
            $billPath = $uploadDir . $fileName;

            if (!move_uploaded_file($_FILES['bill_file']['tmp_name'], $billPath)) {
                $fileError = "Failed to upload bill file";
                $errors[] = 1;
            }
        }

        // ===== RECEIPT FILE (OPTIONAL) =====
        if (isset($_FILES['bill_receipt']) && $_FILES['bill_receipt']['error'] !== 4) {

            $receiptExt = strtolower(pathinfo($_FILES['bill_receipt']['name'], PATHINFO_EXTENSION));

            if (!in_array($receiptExt, $allowed)) {
                $receiptError = "Invalid receipt file type (Allowed: jpg, jpeg, png, pdf)";
                $errors[] = 1;
            } elseif ($_FILES['bill_receipt']['size'] > 5 * 1024 * 1024) {
                $receiptError = "Receipt file size must be less than 5MB";
                $errors[] = 1;
            } else {
                $receiptFileName = "electricity_receipt_{$year}_{$month}_" . time() . "." . $receiptExt;
                $receiptPath = $uploadDir . $receiptFileName;

                if (!move_uploaded_file($_FILES['bill_receipt']['tmp_name'], $receiptPath)) {
                    $receiptError = "Failed to upload receipt file";
                    $errors[] = 1;
                }
            }
        }
    }

    /* ===== INSERT INTO DATABASE ===== */
    if (!$errors) {

        $stmt = $pdo->prepare("
            INSERT INTO electricity_bills
            (month, year, reading, amount, bill_file, bill_receipt)
            VALUES (?,?,?,?,?,?)
        ");

        if ($stmt->execute([$month, $year, $reading, $amount, $fileName, $receiptFileName])) {
            flash_set('success', 'Electricity bill added successfully');
            header('Location:' . BASE_URL . 'electricity_bills.php');
            exit;
        } else {
            flash_set('err', 'Database error! Electricity bill not added.');
            header('Location: ' . BASE_URL . 'add/add_electricity_bill.php');
            exit();
        }
    }
}

include(__DIR__ . '/../../resources/layout/header.php');
?>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>


<div class="container-fluid px-4 mb-4">
    <h1 class="mt-4">Add Electricity Bill</h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>electricity_bills.php">Electricity Bills</a></li>
        <li class="breadcrumb-item active">Add Electricity Bill</li>
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

                            <!-- Month -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Month <span class="text-danger">*</span></label>
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

                            <!-- Year -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Year <span class="text-danger">*</span></label>
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

                            <!-- Meter Reading -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Meter Reading <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="reading"
                                    value="<?= htmlspecialchars($reading ?? '') ?>"
                                    class="form-control <?= $readingError ? 'is-invalid' : '' ?>">
                                <?php if ($readingError): ?>
                                    <div class="invalid-feedback"><?= $readingError ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Amount -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Amount <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="amount"
                                    value="<?= htmlspecialchars($amount ?? '') ?>"
                                    class="form-control <?= $amountError ? 'is-invalid' : '' ?>">
                                <?php if ($amountError): ?>
                                    <div class="invalid-feedback"><?= $amountError ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Upload Bill -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Upload Electricity Bill <span class="text-danger">*</span></label>
                                <input type="file" name="bill_file"
                                    class="form-control <?= $fileError ? 'is-invalid' : '' ?>"
                                    accept=".jpg,.jpeg,.png,.pdf">
                                <?php if ($fileError): ?>
                                    <div class="invalid-feedback"><?= $fileError ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Upload Receipt -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Upload Bill Receipt (Optional)</label>
                                <input type="file" name="bill_receipt"
                                    class="form-control <?= $receiptError ? 'is-invalid' : '' ?>"
                                    accept=".jpg,.jpeg,.png,.pdf">
                                <?php if ($receiptError): ?>
                                    <div class="invalid-feedback"><?= $receiptError ?></div>
                                <?php endif; ?>
                            </div>

                        </div>

                        <button type="submit" name="add_electricity_bill" class="btn btn-primary">Submit</button>
                        <a href="<?= BASE_URL ?>electricity_bills.php" class="btn btn-secondary mx-2">Back</a>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include(__DIR__ . '/../../resources/layout/footer.php'); ?>

<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

requireRole(['admin','cashier']);

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

include __DIR__ . '/../resources/layout/header.php';
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Electricity Management</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">

    <style>
        table.dataTable td {
            vertical-align: middle !important;
            white-space: nowrap;
        }
    </style>

</head>

<body>


    <div class="main-wrapper">

        <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

        <main id="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="fw-800 m-0">Electricity Bills</h1>
                <button class="btn btn-brand shadow-sm" data-bs-toggle="modal" data-bs-target="#addBillModal">
                    <i class="fa-solid fa-plus me-2"></i> Record New Bill
                </button>
            </div>

            <!-- FILTERS - NEW UI STYLE, OLD LOGIC -->
            <div class="filter-box shadow-sm p-3 mb-4">
                <div class="row g-3 align-items-end">
                    <!-- YEAR -->
                    <div class="col-md-2">
                        <label class="small fw-bold text-muted">YEAR</label>
                        <select id="filter-year" class="form-select border-0 bg-light">
                            <option value="">All Years</option>
                            <?php
                            $currentYear = date('Y');
                            for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
                                echo "<option value='$y'>$y</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- MONTH -->
                    <div class="col-md-2">
                        <label class="small fw-bold text-muted">MONTH</label>
                        <select id="filter-month" class="form-select border-0 bg-light">
                            <option value="">All Months</option>
                            <?php
                            for ($m = 1; $m <= 12; $m++) {
                                echo "<option value='$m'>" . date('F', mktime(0, 0, 0, $m, 1)) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- STATUS -->
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted">STATUS</label>
                        <select id="filter-status" class="form-select border-0 bg-light">
                            <option value="">All Status</option>
                            <option value="paid" class="text-success">Paid</option>
                            <option value="pending" class="text-danger">Pending</option>
                            <option value="partial" class="text-danger">Partial</option>
                        </select>
                    </div>

                    <!-- RESET / APPLY BUTTON -->
                    <div class="col-md-2 d-grid">
                        <button id="reset-filters" class="btn btn-outline-dark fw-bold py-2" style="border-radius:10px;">
                            <i class="fa fa-rotate-left"></i> Reset
                        </button>
                    </div>
                </div>
            </div>


            <div class="data-card shadow-sm border-0">
                <div class="table-responsive">
                    <table id="electricityTable" class="table table-hover w-100">
                        <thead>
                            <tr>
                                <th>Month/Year</th>
                                <th>Reading</th>
                                <th>Total Amount</th>
                                <th>Paid</th>
                                <th>Pending</th>
                                <th>Status</th>
                                <th>Bill</th>
                                <th>Receipt</th>
                                <th>Last Paid</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

    </div>


    <!-- VIEW FILE MODAL -->
    <div class="modal fade" id="viewFileModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">View Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <iframe id="fileFrame" src="" width="100%" height="500px" style="border:none;"></iframe>
                </div>
            </div>
        </div>
    </div>



    <!-- PAY ELECTRICITY BILL MODAL -->
    <div class="modal fade" id="payBillModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">

                <div class="modal-header border-0 p-4">
                    <h5 class="modal-title fw-800">Pay Electricity Bill</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4 pt-0">

                    <form id="payBillForm" class="row g-3">

                        <input type="hidden" name="bill_id" id="bill_id">
                        <input type="hidden" id="total_amount_hidden">
                        <input type="hidden" id="paid_amount_hidden">

                        <!-- TOTAL AMOUNT -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">
                                TOTAL AMOUNT
                            </label>
                            <input type="text"
                                id="total_amount"
                                class="form-control bg-light border-0"
                                readonly>
                        </div>

                        <!-- ALREADY PAID -->
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted">
                                ALREADY PAID
                            </label>
                            <input type="text"
                                id="already_paid"
                                class="form-control bg-light border-0"
                                readonly>
                        </div>

                        <!-- PENDING AMOUNT -->
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted">
                                PENDING AMOUNT
                            </label>
                            <input type="text"
                                id="pending_amount"
                                class="form-control bg-light border-0"
                                readonly>
                        </div>

                        <!-- PAY AMOUNT -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">
                                PAY AMOUNT <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                step="0.01"
                                name="paid_amount"
                                id="pay_amount"
                                class="form-control bg-light border-0"
                                placeholder="Enter amount to pay"
                                required>
                        </div>

                        <!-- PAYMENT MODE -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">
                                PAYMENT MODE
                            </label>
                            <select name="payment_mode"
                                class="form-select bg-light border-0">
                                <option value="cash">Cash</option>
                                <option value="online">Online</option>
                            </select>
                        </div>

                        <!-- SUBMIT BUTTON -->
                        <div class="col-12">
                            <button type="submit"
                                class="btn btn-brand w-100 py-3 mt-3">
                                Pay Now
                            </button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>



    <!-- ADD ELECTRICITY BILL MODAL -->
    <div class="modal fade" id="addBillModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius:20px;">

                <div class="modal-header border-0 p-4">
                    <h5 class="modal-title fw-800">Record New Electricity Bill</h5>
                    <button type="button" class="btn-close"
                        data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4 pt-0">

                    <?php if (isset($errors['duplicate'])): ?>
                        <div class="alert alert-danger">
                            <?= $errors['duplicate'] ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST"
                        enctype="multipart/form-data"
                        class="row g-3">

                        <!-- MONTH -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">
                                BILL MONTH <span class="text-danger">*</span>
                            </label>

                            <select name="bill_month"
                                class="form-select bg-light border-0 <?= !empty($monthError) ? 'is-invalid' : '' ?>">
                                <option value="">Select Month</option>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= $m ?>"
                                        <?= (isset($_POST['bill_month']) && $_POST['bill_month'] == $m) ? 'selected' : '' ?>>
                                        <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                                    </option>
                                <?php endfor; ?>
                            </select>

                            <?php if (!empty($monthError)): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $monthError ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- YEAR -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">
                                BILL YEAR <span class="text-danger">*</span>
                            </label>

                            <select name="bill_year"
                                class="form-select bg-light border-0 <?= !empty($yearError) ? 'is-invalid' : '' ?>">
                                <option value="">Select Year</option>
                                <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                                    <option value="<?= $y ?>"
                                        <?= (isset($_POST['bill_year']) && $_POST['bill_year'] == $y) ? 'selected' : '' ?>>
                                        <?= $y ?>
                                    </option>
                                <?php endfor; ?>
                            </select>

                            <?php if (!empty($yearError)): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $yearError ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- METER READING -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">
                                METER READING (Units) <span class="text-danger">*</span>
                            </label>

                            <input type="number"
                                step="0.01"
                                name="reading"
                                value="<?= htmlspecialchars($_POST['reading'] ?? '') ?>"
                                class="form-control bg-light border-0 <?= !empty($readingError) ? 'is-invalid' : '' ?>"
                                placeholder="Enter meter reading">

                            <?php if (!empty($readingError)): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $readingError ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- TOTAL AMOUNT -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">
                                TOTAL AMOUNT (₹) <span class="text-danger">*</span>
                            </label>

                            <input type="number"
                                step="0.01"
                                name="amount"
                                value="<?= htmlspecialchars($_POST['amount'] ?? '') ?>"
                                class="form-control bg-light border-0 <?= !empty($amountError) ? 'is-invalid' : '' ?>"
                                placeholder="Enter total bill amount">

                            <?php if (!empty($amountError)): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $amountError ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- BILL FILE -->
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-muted">
                                UPLOAD ELECTRICITY BILL <span class="text-danger">*</span>
                            </label>

                            <input type="file"
                                name="bill_file"
                                accept=".jpg,.jpeg,.png,.pdf"
                                class="form-control bg-light border-0 <?= !empty($fileError) ? 'is-invalid' : '' ?>">

                            <small class="text-muted">
                                Allowed: JPG, PNG, PDF (Max: 5MB)
                            </small>

                            <?php if (!empty($fileError)): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $fileError ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- RECEIPT FILE (OPTIONAL) -->
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-muted">
                                UPLOAD PAYMENT RECEIPT (Optional)
                            </label>

                            <input type="file"
                                name="bill_receipt"
                                accept=".jpg,.jpeg,.png,.pdf"
                                class="form-control bg-light border-0 <?= !empty($receiptError) ? 'is-invalid' : '' ?>">

                            <?php if (!empty($receiptError)): ?>
                                <div class="invalid-feedback d-block">
                                    <?= $receiptError ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- SUBMIT -->
                        <div class="col-12">
                            <button type="submit"
                                name="add_electricity_bill"
                                class="btn btn-brand w-100 py-3 mt-3">
                                Save Bill
                            </button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>


    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.2/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.2/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {

            let table = $('#electricityTable').DataTable({
                processing: true,
                serverSide: true,
                searching: false,
                pageLength: 5,
                lengthMenu: [5, 10, 25, 50],
                ajax: {
                    url: '<?= BASE_URL ?>action.php',
                    type: 'POST',
                    data: function(d) {
                        d.action = 'fetch_electricity_bills';
                        d.month = $('#filter-month').val();
                        d.year = $('#filter-year').val();
                        d.status = $('#filter-status').val();
                    }
                },
                columns: [{
                        data: 'month_year'
                    },
                    {
                        data: 'reading'
                    },
                    {
                        data: 'amount'
                    },
                    {
                        data: 'paid'
                    },
                    {
                        data: 'pending'
                    },
                    {
                        data: 'status'
                    },
                    {
                        data: 'bill_file',
                        orderable: false
                    },
                    {
                        data: 'receipt_file',
                        orderable: false
                    },
                    {
                        data: 'last_paid'
                    },
                    {
                        data: 'action',
                        orderable: false
                    }
                ],
                order: [
                    [0, 'desc']
                ]
            });

            /* ================= FILTERS ================= */
            $('#filter-month, #filter-year, #filter-status').on('change', function() {
                table.ajax.reload();
            });

            $('#reset-filters').on('click', function() {
                $('#filter-month').val('');
                $('#filter-year').val('');
                $('#filter-status').val('');
                table.ajax.reload();
            });

            /* ================= PAY BILL MODAL ================= */
            $(document).on('click', '.pay-bill', function() {
                let id = $(this).data('id');
                $('#bill_id').val(id);

                $.ajax({
                    url: '<?= BASE_URL ?>action.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'get_electricity_bill',
                        id: id
                    },
                    success: function(res) {

                        let total = parseFloat(res.amount || 0);
                        let paid = parseFloat(res.paid_amount || 0);
                        let pending = parseFloat(res.pending || 0);

                        $('#total_amount').val('₹ ' + total.toFixed(2));
                        $('#already_paid').val('₹ ' + paid.toFixed(2));
                        $('#pending_amount').val('₹ ' + pending.toFixed(2));

                        $('#total_amount_hidden').val(total);
                        $('#paid_amount_hidden').val(paid);
                        $('#pay_amount').val('');

                        $('#payBillModal').modal('show');
                    }
                });
            });

            /* ================= SUBMIT PAYMENT ================= */
            $('#payBillForm').on('submit', function(e) {
                e.preventDefault();

                let pay = parseFloat($('#pay_amount').val() || 0);
                let total = parseFloat($('#total_amount_hidden').val() || 0);
                let paid = parseFloat($('#paid_amount_hidden').val() || 0);
                let pending = total - paid;

                if (pay <= 0) {
                    alert("Enter a valid payment amount!");
                    return;
                }

                if (pay > pending) {
                    alert("You cannot pay more than pending amount!");
                    return;
                }

                $.ajax({
                    url: '<?= BASE_URL ?>action.php',
                    type: 'POST',
                    data: $(this).serialize() + '&action=pay_electricity_bill',
                    success: function() {
                        $('#payBillModal').modal('hide');
                        table.ajax.reload(null, false);
                    }
                });
            });

            /* ================= VIEW FILE ================= */
            $(document).on('click', '.view-file', function() {
                let file = $(this).data('file');

                if (!file) {
                    alert("File not found!");
                    return;
                }

                $('#fileFrame').attr(
                    'src',
                    '<?= BASE_URL ?>uploads/electricity_bills/' + file
                );

                $('#viewFileModal').modal('show');
            });

        });
    </script>

    <?php if (!empty($errors)): ?>
        <script>
            var addBillModal = new bootstrap.Modal(
                document.getElementById('addBillModal')
            );
            addBillModal.show();
        </script>
    <?php endif; ?>

    <?php if (
        !empty($monthError) ||
        !empty($yearError) ||
        !empty($readingError) ||
        !empty($amountError) ||
        !empty($fileError) ||
        !empty($receiptError)
    ): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var addBillModal = new bootstrap.Modal(
                    document.getElementById('addBillModal')
                );
                addBillModal.show();
            });
        </script>
    <?php endif; ?>

</body>

</html>

<?php include __DIR__ . '/../resources/layout/footer.php'; ?>
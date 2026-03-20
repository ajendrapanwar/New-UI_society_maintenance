<?php
require_once __DIR__ . '/../../core/config.php';

/* ================= ACCESS CONTROL ================= */
requireRole(['admin', 'cashier', 'user']);

$userId = $_SESSION['user_id'];

/* ================= VALIDATE BILL ID ================= */
$billId = $_GET['bill_id'] ?? '';
if (!ctype_digit($billId)) {
    die('Invalid Bill ID');
}


/* ================= FETCH BILL + PAYMENT INFO ================= */

// If admin or cashier → allow viewing any bill
if (in_array($_SESSION['user_role'], ['admin', 'cashier'])) {

    $stmt = $pdo->prepare("
            SELECT 
                mb.id AS bill_id,
                mb.bill_month,
                mb.bill_year,
                mb.status,
                mb.due_date,
                mb.amount AS base_amount,

                mp.payment_mode,
                mp.payment_method,
                mp.paid_on,

                f.flat_number,
                f.block_number,
                f.flat_type,

                mr.overdue_fine
            FROM maintenance_bills mb
            JOIN flats f ON f.id = mb.flat_id
            LEFT JOIN maintenance_payments mp 
                ON mp.maintenance_bill_id = mb.id
            LEFT JOIN maintenance_rates mr 
                ON mr.flat_type = f.flat_type
            WHERE mb.id = ?
        ");

    $stmt->execute([$billId]);
} else {

    // Normal user → only own bill (security)
    $stmt = $pdo->prepare("
            SELECT 
                mb.id AS bill_id,
                mb.bill_month,
                mb.bill_year,
                mb.status,
                mb.due_date,
                mb.amount AS base_amount,

                mp.payment_mode,
                mp.payment_method,
                mp.paid_on,

                f.flat_number,
                f.block_number,
                f.flat_type,

                mr.overdue_fine
            FROM maintenance_bills mb
            JOIN flats f ON f.id = mb.flat_id
            LEFT JOIN maintenance_payments mp 
                ON mp.maintenance_bill_id = mb.id
            LEFT JOIN maintenance_rates mr 
                ON mr.flat_type = f.flat_type
            WHERE mb.id = ? AND mb.user_id = ?
        ");

    $stmt->execute([$billId, $userId]);
}
$bill = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$bill) {
    die('Bill not found.');
}

/* ================= CALCULATE OVERDUE ================= */
$fineAmount = 0;
$isOverdue = false;

if ($bill['status'] !== 'paid') {
    $dueWithGrace = strtotime($bill['due_date'] . ' +7 days');
    if (time() > $dueWithGrace) {
        $fineAmount = $bill['overdue_fine'] ?? 0;
        $isOverdue = true;
    }
}

$totalAmount = ($bill['base_amount'] ?? 0) + $fineAmount;

/* ================= FORMAT PAYMENT METHOD ================= */
$paymentText = '-';

if ($bill['payment_mode'] === 'online') {
    $method = $bill['payment_method']
        ? strtoupper(str_replace('_', ' ', $bill['payment_method']))
        : '-';
    $paymentText = 'Online (' . $method . ')';
} elseif ($bill['payment_mode']) {
    $paymentText = ucfirst($bill['payment_mode']);
}

include __DIR__ . '/../../resources/layout/header.php';
?>


<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Detail</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../../assets/css/styles.css">

</head>

<body>

    <div class="main-wrapper">

        <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

        <main id="main-content">

            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="fw-800 m-0">Bill Details</h1>

                <!-- Back Button -->
                <a href="javascript:history.back()" class="btn btn-outline-dark btn-sm">
                    <i class="fa-solid fa-angle-left me-1"></i> Back
                </a>
            </div>

            <!-- Bill Card -->
            <div class="data-card shadow-sm border-0" style="max-width: 600px;">

                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-800 m-0">
                        <i class="fa-solid fa-file-invoice me-2 text-primary"></i>
                        Bill Information
                    </h5>

                    <span class="badge 
            <?= $bill['status'] === 'paid'
                ? 'bg-success'
                : ($isOverdue ? 'bg-danger' : 'bg-warning') ?>">
                        <?= ucfirst($bill['status']) ?>
                    </span>
                </div>

                <hr class="my-3">

                <!-- Info Rows (Clean & Compact) -->
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted fw-semibold">Flat</span>
                    <span class="fw-bold">
                        <?= htmlspecialchars($bill['block_number'] . ' - ' . $bill['flat_number']) ?>
                    </span>
                </div>

                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted fw-semibold">Month / Year</span>
                    <span class="fw-bold">
                        <?= date('F', mktime(0, 0, 0, $bill['bill_month'], 1)) . ' ' . $bill['bill_year'] ?>
                    </span>
                </div>

                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted fw-semibold">Base Amount</span>
                    <span class="fw-bold">
                        ₹<?= number_format($bill['base_amount'], 2) ?>
                    </span>
                </div>

                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted fw-semibold">Fine</span>
                    <span class="fw-bold text-danger">
                        ₹<?= number_format($fineAmount, 2) ?>
                    </span>
                </div>

                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted fw-semibold">Total Amount</span>
                    <span class="fw-bold fs-5 text-success">
                        ₹<?= number_format($totalAmount, 2) ?>
                    </span>
                </div>

                <?php if ($bill['status'] === 'paid'): ?>

                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted fw-semibold">Paid On</span>
                        <span class="fw-bold">
                            <?= date('d-m-Y H:i', strtotime($bill['paid_on'])) ?>
                        </span>
                    </div>

                    <div class="d-flex justify-content-between py-2">
                        <span class="text-muted fw-semibold">Payment Method</span>
                        <span class="fw-bold">
                            <?= $paymentText ?>
                        </span>
                    </div>

                <?php else: ?>

                    <div class="d-flex justify-content-between py-2">
                        <span class="text-muted fw-semibold">Overdue</span>
                        <span class="fw-bold <?= $isOverdue ? 'text-danger' : 'text-warning' ?>">
                            <?= $isOverdue ? 'Yes' : 'No' ?>
                        </span>
                    </div>

                    <div class="alert alert-warning mt-3 mb-0 border-0">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i>
                        This bill is not yet paid
                    </div>

                <?php endif; ?>

            </div>

        </main>

    </div>

</body>

</html>

<?php include __DIR__ . '/../../resources/layout/footer.php'; ?>
<?php
require_once __DIR__ . '/../../core/config.php';

/* ================= ACCESS CONTROL ================= */
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header('Location: ' . BASE_URL . 'logout.php');
    exit();
}

$userId = $_SESSION['user_id'];

/* ================= VALIDATE BILL ID ================= */
$billId = $_GET['bill_id'] ?? '';
if (!ctype_digit($billId)) {
    die('Invalid Bill ID');
}

/* ================= FETCH BILL + USER INFO ================= */
$stmt = $pdo->prepare("
    SELECT 
        mb.id AS bill_id,
        mb.bill_month,
        mb.bill_year,
        mb.status,
        mb.due_date,
        mb.amount AS base_amount,
        mp.payment_mode,
        mp.payment_id,
        mp.paid_on,
        f.flat_number,
        f.block_number,
        f.flat_type,
        mr.overdue_fine
    FROM maintenance_bills mb
    JOIN flats f ON f.id = mb.flat_id
    LEFT JOIN maintenance_payments mp ON mp.maintenance_bill_id = mb.id
    LEFT JOIN maintenance_rates mr ON mr.flat_type = f.flat_type
    WHERE mb.id = ? AND mb.user_id = ?
");
$stmt->execute([$billId, $userId]);
$bill = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$bill) {
    die('Bill not found.');
}

/* ================= CALCULATE OVERDUE FINE ================= */
$fineAmount = 0;
$isOverdue = false;
if ($bill['status'] !== 'paid') {
    $dueWithGrace = strtotime($bill['due_date'] . ' +7 days');
    if (time() > $dueWithGrace) {
        $fineAmount = $bill['overdue_fine'];
        $isOverdue = true;
    }
}
$totalAmount = $bill['base_amount'] + $fineAmount;

include __DIR__ . '/../../resources/layout/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Bill Details</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="view_userMaintanenceBill.php">Bills History</a></li>
        <li class="breadcrumb-item active">Bill Details</li>
    </ol>

    <div class="card col-md-6">
        <div class="card-header"><strong>Bill Information</strong></div>
        <div class="card-body">
            <div class="mb-2"><b>Flat:</b> <?= htmlspecialchars($bill['block_number'] . ' - ' . $bill['flat_number']) ?></div>
            <div class="mb-2"><b>Month / Year:</b> <?= date('F', mktime(0,0,0,$bill['bill_month'],1)) . ' ' . $bill['bill_year'] ?></div>
            <div class="mb-2"><b>Base Amount:</b> ₹<?= number_format($bill['base_amount'], 2) ?></div>
            <div class="mb-2"><b>Fine:</b> ₹<?= number_format($fineAmount, 2) ?></div>
            <div class="mb-2"><b>Total:</b> ₹<?= number_format($totalAmount, 2) ?></div>
            <div class="mb-2"><b>Status:</b>
                <span class="badge bg-<?= $bill['status']==='paid' ? 'success' : ($isOverdue ? 'danger' : 'warning') ?>">
                    <?= ucfirst($bill['status']) ?>
                </span>
            </div>

            <?php if ($bill['status'] === 'paid'): ?>
                <div class="mb-2"><b>Paid On:</b> <?= date('d-m-Y', strtotime($bill['paid_on'])) ?></div>
                <div class="mb-2"><b>Payment Method:</b> <?= ucfirst($bill['payment_mode']) ?></div>
                <div class="mb-2"><b>Payment ID:</b> <?= $bill['payment_id'] ?? '-' ?></div>
            <?php else: ?>
                <div class="mb-2"><b>Overdue:</b> <?= $isOverdue ? 'Yes' : 'No' ?></div>
                <div class="alert alert-warning">This bill is not yet paid.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../resources/layout/footer.php'; ?>

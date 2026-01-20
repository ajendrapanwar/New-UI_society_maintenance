<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../core/config.php';

/* ================= ACCESS CONTROL ================= */
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . 'logout.php');
    exit();
}

$isAdmin = $_SESSION['user_role'] === 'admin';

/* ================= GET ALLOTMENT ID ================= */
$allotmentId = $_GET['id'] ?? '';

if (!ctype_digit($allotmentId)) {
    die('Invalid Allotment ID');
}

/* ================= GET USER + FLAT INFO ================= */
$stmt = $pdo->prepare("
    SELECT 
        u.id AS user_id,
        u.name,
        u.email,
        a.flat_id,
        f.flat_number,
        f.block_number,
        f.flat_type
    FROM allotments a
    JOIN users u ON u.id = a.user_id
    JOIN flats f ON f.id = a.flat_id
    WHERE a.id = ?
");
$stmt->execute([$allotmentId]);
$user = $stmt->fetch();

if (!$user) {
    die('User not found for this allotment');
}

$userId = $user['user_id'];
$flatId = $user['flat_id'];
$flatType = $user['flat_type'];

/* ================= FETCH MAINTENANCE RATE ================= */
$rateStmt = $pdo->prepare("SELECT rate, overdue_fine FROM maintenance_rates WHERE flat_type = ?");
$rateStmt->execute([$flatType]);
$rateData = $rateStmt->fetch();
$baseAmount = $rateData['rate'] ?? 0;
$overdueFineRate = $rateData['overdue_fine'] ?? 0;

/* ================= FETCH MAINTENANCE BILLS ================= */
$stmt = $pdo->prepare("
    SELECT 
        mb.id AS bill_id,
        mb.bill_month,
        mb.bill_year,
        mb.amount,
        mb.fine_amount,
        mb.total_amount,
        mb.status,
        mb.due_date,
        mp.payment_mode,
        mp.payment_id,
        mp.paid_on
    FROM maintenance_bills mb
    LEFT JOIN maintenance_payments mp ON mp.maintenance_bill_id = mb.id
    WHERE mb.user_id = ? AND mb.flat_id = ?
    ORDER BY mb.bill_year DESC, mb.bill_month DESC
");
$stmt->execute([$userId, $flatId]);
$bills = $stmt->fetchAll();

include __DIR__ . '/../../resources/layout/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Maintenance Bill Records</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">User Bill History</li>
    </ol>

    <!-- USER INFO -->
    <div class="card mb-4">
        <div class="card-body">
            <strong>User:</strong> <?= htmlspecialchars($user['name']) ?><br>
            <strong>Email:</strong> <?= htmlspecialchars($user['email']) ?><br>
            <strong>Flat:</strong> <?= htmlspecialchars($user['flat_number']) ?><br>
            <strong>Block:</strong> <?= htmlspecialchars($user['block_number']) ?><br>
            <strong>Flat Type:</strong> <?= htmlspecialchars($flatType) ?>
        </div>
    </div>

    <!-- SUCCESS MESSAGE -->
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success'];
                                            unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <!-- BILLS TABLE -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Month / Year</th>
                            <th>Amount</th>
                            <th>Fine</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Payment ID</th>
                            <th>Mode</th>
                            <th>Paid On</th>
                            <th>Overdue</th>
                            <?php if ($isAdmin): ?><th>Action</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$bills): ?>
                            <tr>
                                <td colspan="<?= $isAdmin ? 10 : 9 ?>" class="text-center text-muted">
                                    No maintenance records found
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($bills as $bill):
                            $monthName = date('F', mktime(0, 0, 0, $bill['bill_month'], 1));

                            // Calculate overdue fine if status not paid and due + 7 days < today
                            $fineAmount = $bill['fine_amount'];
                            $isOverdue = false;
                            if ($bill['status'] !== 'paid') {
                                $dueWithGrace = strtotime($bill['due_date'] . ' +7 days');
                                if (time() > $dueWithGrace && $fineAmount == 0) {
                                    $fineAmount = $overdueFineRate;
                                    $isOverdue = true;

                                    // Update bill fine in DB (only once)
                                    $totalAmount = $bill['amount'] + $fineAmount;
                                    $updateStmt = $pdo->prepare("
                                        UPDATE maintenance_bills
                                        SET fine_amount = ?, total_amount = ?, status = 'overdue'
                                        WHERE id = ?
                                    ");
                                    $updateStmt->execute([$fineAmount, $totalAmount, $bill['bill_id']]);
                                }
                            }

                            $totalAmount = $bill['amount'] + $fineAmount;
                        ?>
                            <tr>
                                <td><?= $monthName . ' ' . $bill['bill_year'] ?></td>
                                <td>₹<?= number_format($bill['amount'], 2) ?></td>
                                <td>₹<?= number_format($fineAmount, 2) ?></td>
                                <td><strong>₹<?= number_format($totalAmount, 2) ?></strong></td>
                                <td>
                                    <span class="badge bg-<?=
                                                            $bill['status'] === 'paid' ? 'success' : ($isOverdue ? 'danger' : 'warning')
                                                            ?>">
                                        <?= ucfirst($bill['status']) ?>
                                    </span>
                                </td>
                                <td><?= $bill['payment_id'] ?? '-' ?></td>
                                <td><?= ucfirst($bill['payment_mode'] ?? '-') ?></td>
                                <td><?= $bill['paid_on'] ? date('d-m-Y H:i', strtotime($bill['paid_on'])) : '-' ?></td>
                                <td><?= $isOverdue ? '<span class="text-danger">Yes</span>' : 'No' ?></td>
                                <?php if ($isAdmin): ?>
                                    <td>
                                        <?php if ($bill['status'] !== 'paid'): ?>
                                            <a href="<?= BASE_URL ?>action.php?action=mark_cash_payment&bill_id=<?= $bill['bill_id'] ?>"
                                                class="btn btn-sm btn-success"
                                                onclick="return confirm('Are you sure to mark this bill as CASH paid?')">
                                                Cash Paid
                                            </a>

                                        <?php else: ?>
                                            <span class="text-muted">Paid</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>

                            </tr>
                        <?php endforeach; ?>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../resources/layout/footer.php'; ?>
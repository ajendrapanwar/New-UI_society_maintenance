<?php
require_once __DIR__ . '/../../core/config.php';

/* ================= ACCESS CONTROL ================= */
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header('Location: ' . BASE_URL . 'logout.php');
    exit();
}

$userId = $_SESSION['user_id'];

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
    WHERE u.id = ?
    ORDER BY a.id DESC
    LIMIT 1
");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    die('No allotment found for your account.');
}

$flatId = $user['flat_id'];
$flatType = $user['flat_type'];

/* ================= FETCH MAINTENANCE RATE ================= */
$rateStmt = $pdo->prepare("SELECT rate, overdue_fine FROM maintenance_rates WHERE flat_type = ?");
$rateStmt->execute([$flatType]);
$rateData = $rateStmt->fetch();

$baseAmount = $rateData['rate'] ?? 0;
$overdueFineRate = $rateData['overdue_fine'] ?? 0;

/* ================= FETCH USER MAINTENANCE BILLS ================= */
$stmt = $pdo->prepare("
    SELECT 
        mb.id AS bill_id,
        mb.bill_month,
        mb.bill_year,
        mb.status,
        mb.due_date,
        mp.payment_mode,
        mp.payment_id,
        mp.paid_on
    FROM maintenance_bills mb
    LEFT JOIN maintenance_payments mp 
        ON mp.maintenance_bill_id = mb.id
    WHERE mb.user_id = ?
      AND mb.flat_id = ?
    ORDER BY mb.bill_year DESC, mb.bill_month DESC
");
$stmt->execute([$userId, $flatId]);
$bills = $stmt->fetchAll();

include __DIR__ . '/../../resources/layout/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">My Maintenance Bill History</h1>

    <!-- USER INFO -->
    <div class="card mb-4">
        <div class="card-body">
            <strong>Name:</strong> <?= htmlspecialchars($user['name']) ?><br>
            <strong>Email:</strong> <?= htmlspecialchars($user['email']) ?><br>
            <strong>Flat:</strong> <?= htmlspecialchars($user['flat_number']) ?><br>
            <strong>Block:</strong> <?= htmlspecialchars($user['block_number']) ?><br>
            <strong>Flat Type:</strong> <?= htmlspecialchars($flatType) ?>
        </div>
    </div>

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
                            <th>Over Due</th>
                            <th width="150">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$bills): ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted">No maintenance records found</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($bills as $bill):
                            $monthName = date('F', mktime(0, 0, 0, $bill['bill_month'], 1));

                            // Calculate overdue fine if due_date + 7 days < today
                            $fineAmount = 0;
                            $isOverdue = false;
                            if ($bill['status'] !== 'paid') {
                                $dueWithGrace = strtotime($bill['due_date'] . ' +7 days');
                                if (time() > $dueWithGrace) {
                                    $fineAmount = $overdueFineRate;
                                    $isOverdue = true;
                                }
                            }

                            $totalAmount = $baseAmount + $fineAmount;
                        ?>
                            <tr>
                                <td><?= $monthName . ' ' . $bill['bill_year'] ?></td>
                                <td>₹<?= number_format($baseAmount, 2) ?></td>
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
                                <td><?= $bill['paid_on'] ? date('d-m-Y', strtotime($bill['paid_on'])) : '-' ?></td>

                                <td><?= $isOverdue ? '<span class="text-danger">Yes</span>' : 'No' ?></td>

                                <!-- Action: View if paid, Pay if unpaid -->
                                <td>
                                    <?php if ($bill['status'] === 'paid'): ?>
                                        <a href="pay_Details.php?bill_id=<?= $bill['bill_id'] ?>" class="btn btn-sm btn-info">
                                            View
                                        </a>
                                    <?php else: ?>
                                        <a href="pay_bill.php?bill_id=<?= $bill['bill_id'] ?>" class="btn btn-sm btn-success">
                                            Pay Now
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../resources/layout/footer.php'; ?>

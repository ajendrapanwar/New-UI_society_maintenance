<?php
require_once __DIR__ . '/../core/config.php';

// ACCESS CONTROL – ADMIN ONLY
requireRole(['admin']);

// DATE CONTEXT
$today        = date('Y-m-d');
$currentDay   = date('j');   // 1–31
$currentMonth = date('n');   // 1–12
$currentYear  = date('Y');

$reason = ""; // Variable to store why no bills were generated
$generated = 0;

/* ===========================================================
   1️⃣ GENERATE NEXT MONTH BILLS
   👉 Allowed only from 28–31
   =========================================================== */

if ($currentDay >= 28) {

    // FIX: Calculate Next Month Correctly (Prevents Jan 30 -> Mar overflow)
    // We calculate based on the 'first day of next month' to be safe.
    $targetMonth = date('n', strtotime('first day of +1 month'));
    $targetYear  = date('Y', strtotime('first day of +1 month'));

    // UPDATED QUERY: Removed move_out_date check
    $stmt = $pdo->prepare("
        SELECT a.user_id, a.flat_id, f.flat_type
        FROM allotments a
        JOIN flats f ON f.id = a.flat_id
    ");
    $stmt->execute();
    $allotments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // CHECK: Are there any allotments at all?
    if (empty($allotments)) {
        $reason = "No active allotments found in the database.";
    } else {

        $skipCount = 0;

        foreach ($allotments as $row) {

            $userId   = $row['user_id'];
            $flatId   = $row['flat_id'];
            $flatType = $row['flat_type'];

            // Prevent duplicate bill
            $check = $pdo->prepare("
                SELECT COUNT(*) FROM maintenance_bills
                WHERE user_id = ? AND flat_id = ?
                  AND bill_month = ? AND bill_year = ?
            ");
            $check->execute([$userId, $flatId, $targetMonth, $targetYear]);

            if ($check->fetchColumn() > 0) {
                $skipCount++;
                continue;
            }

            // Fetch rate
            $rateStmt = $pdo->prepare("
                SELECT rate FROM maintenance_rates WHERE flat_type = ?
            ");
            $rateStmt->execute([$flatType]);
            $rate = $rateStmt->fetchColumn() ?? 0;

            // Due date = 7th of bill month
            // Using mktime is safer for constructing dates from integers
            $dueDate = date('Y-m-d', mktime(0, 0, 0, $targetMonth, 7, $targetYear));

            // Insert bill
            $insert = $pdo->prepare("
                INSERT INTO maintenance_bills
                (user_id, flat_id, bill_month, bill_year,
                 amount, fine_amount, total_amount,
                 status, due_date, created_at)
                VALUES (?, ?, ?, ?, ?, 0, ?, 'pending', ?, NOW())
            ");
            $insert->execute([
                $userId,
                $flatId,
                $targetMonth,
                $targetYear,
                $rate,
                $rate,
                $dueDate
            ]);

            $generated++;
        }

        // Logic to explain why 0 bills were generated
        if ($generated == 0 && $skipCount > 0) {
            $reason = "Bills for next month (" . date('F', mktime(0, 0, 0, $targetMonth, 1)) . " $targetYear) have already been generated for all flats.";
        } elseif ($generated == 0 && $skipCount == 0) {
            $reason = "Generation skipped due to an unknown data error (check rates).";
        }
    }
} else {
    // Reason if date is wrong
    $reason = "Billing is locked. You can only generate bills between the 28th and 31st of the month.";
}

/* ===========================================================
   2️⃣ APPLY OVERDUE FINE (ONE-TIME ONLY)
   👉 From 8th of the bill month
   =========================================================== */

$overdueUpdated = 0;

if ($currentDay >= 8) {

    $overdueStmt = $pdo->prepare("
        SELECT mb.id, mb.amount, f.flat_type
        FROM maintenance_bills mb
        JOIN flats f ON f.id = mb.flat_id
        WHERE mb.status = 'pending'
          AND mb.fine_amount = 0
          AND mb.bill_month = ?
          AND mb.bill_year = ?
    ");
    $overdueStmt->execute([$currentMonth, $currentYear]);
    $bills = $overdueStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($bills as $bill) {

        // Get fine
        $fineStmt = $pdo->prepare("
            SELECT overdue_fine
            FROM maintenance_rates
            WHERE flat_type = ?
        ");
        $fineStmt->execute([$bill['flat_type']]);
        $fine = $fineStmt->fetchColumn() ?? 0;

        if ($fine <= 0) continue;

        $total = $bill['amount'] + $fine;

        // ONE-TIME UPDATE
        $update = $pdo->prepare("
            UPDATE maintenance_bills
            SET fine_amount = ?,
                total_amount = ?,
                status = 'overdue'
            WHERE id = ?
        ");
        $update->execute([$fine, $total, $bill['id']]);

        $overdueUpdated++;
    }
}

//  ===========================================================
//    RESULT DISPLAY
//    ===========================================================

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Billing Job Result</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fa fa-clipboard-list me-2"></i>Maintenance Job Result</h4>
            </div>
            <div class="card-body">

                <!-- BILL GENERATION RESULT -->
                <div class="alert <?php echo $generated > 0 ? 'alert-success' : 'alert-warning'; ?>" role="alert">
                    <h5 class="alert-heading">
                        <?php if ($generated > 0): ?>
                            <i class="fa fa-check-circle me-2"></i> Success
                        <?php else: ?>
                            <i class="fa fa-exclamation-triangle me-2"></i> No Bills Generated
                        <?php endif; ?>
                    </h5>
                    <hr>

                    <?php if ($generated > 0): ?>
                        <p><strong>Bills Generated for:</strong> <?php echo date('F Y', mktime(0, 0, 0, $targetMonth, 1, $targetYear)); ?></p>
                        <p><strong>Total Bills Created:</strong> <?php echo $generated; ?></p>
                        <p class="mb-0">Bills have been successfully created.</p>
                    <?php else: ?>
                        <p><strong>Reason:</strong> <?php echo $reason; ?></p>
                    <?php endif; ?>
                </div>

                <!-- OVERDUE UPDATE RESULT -->
                <div class="alert <?php echo $overdueUpdated > 0 ? 'alert-info' : 'alert-secondary'; ?> mt-3" role="alert">
                    <h5 class="alert-heading"><i class="fa fa-clock me-2"></i> Overdue Status</h5>
                    <p>
                        <strong>Bills Marked Overdue:</strong> <?php echo $overdueUpdated; ?>
                    </p>
                    <?php if ($currentDay < 8): ?>
                        <small class="text-muted">(Overdue fines are auto-applied starting the 8th of the month)</small>
                    <?php endif; ?>
                </div>

                <div class="text-center mt-4">
                    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                </div>
            </div>
            <div class="card-footer text-muted text-center">
                Run Date: <?php echo $today; ?>
            </div>
        </div>
    </div>
</body>

</html>
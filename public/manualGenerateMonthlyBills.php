<?php
require_once __DIR__ . '/../core/config.php';


//    ACCESS CONTROL – ADMIN ONLY
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    exit('Unauthorized access');
}


//    DATE CONTEXT
$today        = date('Y-m-d');
$currentDay   = date('j');   // 1–31
$currentMonth = date('n');   // 1–12
$currentYear  = date('Y');

/* ===========================================================
   1️⃣ GENERATE NEXT MONTH BILLS
   👉 Allowed only from 28–31
   =========================================================== */
$generated = 0;

if ($currentDay >= 28) {

    // Target = NEXT MONTH
    $targetMonth = date('n', strtotime('+1 month'));
    $targetYear  = date('Y', strtotime('+1 month'));

    $stmt = $pdo->prepare("
        SELECT a.user_id, a.flat_id, f.flat_type
        FROM allotments a
        JOIN flats f ON f.id = a.flat_id
        WHERE a.move_out_date IS NULL OR a.move_out_date >= CURDATE()
    ");
    $stmt->execute();
    $allotments = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            continue;
        }

        // Fetch rate
        $rateStmt = $pdo->prepare("
            SELECT rate FROM maintenance_rates WHERE flat_type = ?
        ");
        $rateStmt->execute([$flatType]);
        $rate = $rateStmt->fetchColumn() ?? 0;

        // Due date = 7th of bill month
        $dueDate = date('Y-m-d', strtotime("$targetYear-$targetMonth-07"));

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

/* ===========================================================
   RESULT
   =========================================================== */

echo "<h3>Maintenance Job Result</h3>";
echo "📌 Bills Generated (Next Month): <b>$generated</b><br>";
echo "⚠️ Bills Marked Overdue (One-Time Fine): <b>$overdueUpdated</b><br>";
echo "🕒 Run Date: $today";

<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '../../core/config.php';


// PHPMailer Load
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '../../vendor/PHPMailer/PHPMailer.php';
require_once __DIR__ . '../../vendor/PHPMailer/SMTP.php';
require_once __DIR__ . '../../vendor/PHPMailer/Exception.php';


// ACCESS CONTROL – ADMIN ONLY
requireRole(['admin']);

// ================= SMTP CONFIG =================
$SMTP_EMAIL = "fakeclint01@gmail.com";
$SMTP_PASS  = "xjpv lacb osnv cwmj";

// $TEST_MODE = true; 


// DATE CONTEXT
$today        = date('Y-m-d');
$currentDay   = date('j');
$currentMonth = date('n');
$currentYear  = date('Y');

$reason = "";
$generated = 0;

/* ===========================================================
   1️⃣ GENERATE NEXT MONTH BILLS (Only 28-31)
   =========================================================== */

// if ($TEST_MODE || $currentDay >= 28) {

if ($currentDay >= 28) {

    $targetMonth = date('n', strtotime('first day of +1 month'));
    $targetYear  = date('Y', strtotime('first day of +1 month'));

    $stmt = $pdo->prepare("
        SELECT a.user_id, a.flat_id, f.flat_type
        FROM allotments a
        JOIN flats f ON f.id = a.flat_id
    ");
    $stmt->execute();
    $allotments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($allotments)) {
        $reason = "No active allotments found.";
    } else {

        $skipCount = 0;

        foreach ($allotments as $row) {

            $userId   = $row['user_id'];
            $flatId   = $row['flat_id'];
            $flatType = $row['flat_type'];

            // Prevent duplicate bill
            $check = $pdo->prepare("
                SELECT COUNT(*) FROM maintenance_bills
                WHERE user_id=? AND flat_id=? AND bill_month=? AND bill_year=?
            ");
            $check->execute([$userId, $flatId, $targetMonth, $targetYear]);

            if ($check->fetchColumn() > 0) {
                $skipCount++;
                continue;
            }

            // Get rate
            $rateStmt = $pdo->prepare("SELECT rate FROM maintenance_rates WHERE flat_type=?");
            $rateStmt->execute([$flatType]);
            $rate = $rateStmt->fetchColumn() ?? 0;

            $dueDate = date('Y-m-d', mktime(0, 0, 0, $targetMonth, 7, $targetYear));

            // Insert Bill
            $insert = $pdo->prepare("
                INSERT INTO maintenance_bills
                (user_id, flat_id, bill_month, bill_year, amount, fine_amount, total_amount, status, due_date, created_at)
                VALUES (?, ?, ?, ?, ?, 0, ?, 'pending', ?, NOW())
            ");
            $insert->execute([$userId, $flatId, $targetMonth, $targetYear, $rate, $rate, $dueDate]);

            $generated++;
        }

        if ($generated == 0 && $skipCount > 0) {
            $reason = "Bills already generated for next month.";
        } elseif ($generated == 0) {
            $reason = "Unknown error (check maintenance rates).";
        }
    }
} else {
    $reason = "Billing allowed only after 28th.";
}

/* ===========================================================
   2️⃣ APPLY OVERDUE FINE
   =========================================================== */

$overdueUpdated = 0;

if ($currentDay >= 8) {

    $overdueStmt = $pdo->prepare("
        SELECT mb.id, mb.amount, f.flat_type
        FROM maintenance_bills mb
        JOIN flats f ON f.id = mb.flat_id
        WHERE mb.status='pending' AND mb.fine_amount=0
        AND mb.bill_month=? AND mb.bill_year=?
    ");
    $overdueStmt->execute([$currentMonth, $currentYear]);
    $bills = $overdueStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($bills as $bill) {

        $fineStmt = $pdo->prepare("SELECT overdue_fine FROM maintenance_rates WHERE flat_type=?");
        $fineStmt->execute([$bill['flat_type']]);
        $fine = $fineStmt->fetchColumn() ?? 0;

        if ($fine <= 0) continue;

        $total = $bill['amount'] + $fine;

        $update = $pdo->prepare("
            UPDATE maintenance_bills
            SET fine_amount=?, total_amount=?, status='overdue'
            WHERE id=?
        ");
        $update->execute([$fine, $total, $bill['id']]);

        $overdueUpdated++;
    }
}

/* ===========================================================
   3️⃣ SEND EMAIL TO ALL USERS AFTER BILL GENERATION
   =========================================================== */

if ($generated > 0) {

    // Fetch users
    $stmt = $pdo->prepare("
        SELECT u.email, u.name, f.flat_number, f.block_number
        FROM allotments a
        JOIN users u ON a.user_id=u.id
        JOIN flats f ON a.flat_id=f.id
        WHERE u.email IS NOT NULL AND u.email!=''
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $SMTP_EMAIL;
        $mail->Password = $SMTP_PASS;
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('YOUR_GMAIL@gmail.com', 'Society Maintenance System');
        $mail->isHTML(true);

        foreach ($users as $user) {

            $mail->clearAddresses();
            $mail->addAddress($user['email'], $user['name']);

            $flat = $user['block_number'] . "-" . $user['flat_number'];
            $name = $user['name'];
            $monthYear = date('F Y', mktime(0, 0, 0, $targetMonth, 1, $targetYear));

            $mail->Subject = "Maintenance Bill Generated - $monthYear";

            $mail->Body = "
            <div style='background:#f4f6f9;padding:30px;font-family:Segoe UI,Arial,sans-serif'>

                <div style='max-width:700px;margin:auto;background:#ffffff;border-radius:12px;
                            box-shadow:0 8px 25px rgba(0,0,0,0.08);overflow:hidden;border:1px solid #e5e5e5'>

                    <!-- HEADER -->
                    <div style='background:linear-gradient(135deg,#0d6efd,#6610f2);
                                padding:20px;text-align:center;color:white'>
                        <h2 style='margin:0;font-size:22px;font-weight:600'>🏢 Maintenance Bill Generated</h2>
                        <p style='margin:5px 0 0;font-size:13px;opacity:0.9'>$monthYear</p>
                    </div>

                    <!-- BODY -->
                    <div style='padding:25px;color:#333;font-size:15px'>

                        <p style='font-size:16px;margin-bottom:10px;'>Hello <b>$name</b>,</p>

                        <p>Your monthly maintenance bill has been generated. Please check the details below:</p>

                        <table style='width:100%;border-collapse:collapse;font-size:14px;margin-top:15px'>
                            <tr style='background:#f8f9fa'>
                                <td style='padding:12px;border:1px solid #ddd;font-weight:bold;width:30%'>Flat</td>
                                <td style='padding:12px;border:1px solid #ddd'>$flat</td>
                            </tr>

                            <tr>
                                <td style='padding:12px;border:1px solid #ddd;font-weight:bold'>Bill Month</td>
                                <td style='padding:12px;border:1px solid #ddd'>$monthYear</td>
                            </tr>

                            <tr style='background:#f8f9fa'>
                                <td style='padding:12px;border:1px solid #ddd;font-weight:bold'>Status</td>
                                <td style='padding:12px;border:1px solid #ddd;color:#dc3545;font-weight:bold'>Pending</td>
                            </tr>

                            <tr>
                                <td style='padding:12px;border:1px solid #ddd;font-weight:bold'>Due Date</td>
                                <td style='padding:12px;border:1px solid #ddd'>$dueDate</td>
                            </tr>
                        </table>

                        <!-- PAY BUTTON -->
                        <div style='text-align:center;margin-top:25px'>
                            <a href='http://localhost/society_maintenance/public/dashboard.php'
                            style='background:#0d6efd;color:white;text-decoration:none;
                                    padding:12px 25px;border-radius:6px;font-size:15px;font-weight:bold;
                                    display:inline-block'>
                                Pay Now
                            </a>
                        </div>

                        <p style='margin-top:20px;font-size:14px;color:#555'>
                            Please login to your dashboard to view and pay your bill before the due date.
                        </p>

                    </div>

                    <!-- FOOTER -->
                    <div style='background:#f1f1f1;padding:12px;text-align:center;font-size:12px;color:#777'>
                        Society Maintenance System © " . date('Y') . "<br>
                        This is an auto-generated email. Do not reply.
                    </div>

                </div>

            </div>";


            $mail->send();
        }
    } catch (Exception $e) {
        error_log("Mail Error: " . $mail->ErrorInfo);
    }
}

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
        <!-- mx-auto centers the card, max-width: 550px makes it small -->
        <div class="card shadow-sm mx-auto" style="max-width: 550px; margin-top:4rem">
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
                    <a href="dashboard.php" class="btn btn-dark">Back to Dashboard</a>
                </div>
            </div>
            <div class="card-footer text-muted text-center">
                Run Date: <?php echo $today; ?>
            </div>
        </div>
    </div>
</body>

</html>
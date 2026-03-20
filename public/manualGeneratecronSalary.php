<?php
require_once __DIR__ . '/../core/config.php';

requireRole(['admin']); // Optional if cron is public, but required for manual access

// DATE CONTEXT
$today        = date('Y-m-d');
$currentDay   = date('j');   // 1–31
$currentMonth = date('n');   // 1–12
$currentYear  = date('Y');

// RESULT COUNTERS
$generated = 0;
$reason = ""; // Reason for failure
$totalSkipped = 0; // To track duplicates

/* ===========================================================
   1️⃣ GENERATE NEXT MONTH SALARY (ONLY 28–31)
   ======================================================= */

if ($currentDay >= 28) {

    // FIX: Calculate Next Month Correctly (Prevents Jan 30 -> Mar overflow)
    $targetMonth = date('n', strtotime('first day of +1 month'));
    $targetYear  = date('Y', strtotime('first day of +1 month'));

    $totalStaffFound = 0;

    /* ================= SWEEPERS ================= */
    $stmt = $pdo->query("SELECT id, salary FROM sweepers");
    $workers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($workers)) {
        $totalStaffFound += count($workers);
        foreach ($workers as $w) {
            // Prevent duplicate
            $check = $pdo->prepare("
                SELECT COUNT(*) FROM sweeper_salary
                WHERE sweeper_id=? AND salary_month=? AND salary_year=?
            ");
            $check->execute([$w['id'], $targetMonth, $targetYear]);

            if ($check->fetchColumn() > 0) {
                $totalSkipped++;
                continue;
            }

            // Insert salary bill
            $insert = $pdo->prepare("
                INSERT INTO sweeper_salary (sweeper_id, salary_month, salary_year, salary_amount, status, generated_at)
                VALUES (?, ?, ?, ?, 'unpaid', NOW())
            ");
            $insert->execute([$w['id'], $targetMonth, $targetYear, $w['salary']]);

            $generated++;
        }
    }

    /* ================= SECURITY GUARDS ================= */
    $stmt = $pdo->query("SELECT id, salary FROM security_guards");
    $guards = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($guards)) {
        $totalStaffFound += count($guards);
        foreach ($guards as $g) {
            $check = $pdo->prepare("
                SELECT COUNT(*) FROM guard_salary
                WHERE guard_id=? AND salary_month=? AND salary_year=?
            ");
            $check->execute([$g['id'], $targetMonth, $targetYear]);

            if ($check->fetchColumn() > 0) {
                $totalSkipped++;
                continue;
            }

            $insert = $pdo->prepare("
                INSERT INTO guard_salary (guard_id, salary_month, salary_year, salary_amount, status, generated_at)
                VALUES (?, ?, ?, ?, 'unpaid', NOW())
            ");
            $insert->execute([$g['id'], $targetMonth, $targetYear, $g['salary']]);

            $generated++;
        }
    }

    /* ================= GARBAGE COLLECTORS ================= */
    $stmt = $pdo->query("SELECT id, salary FROM garbage_collectors");
    $collectors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($collectors)) {
        $totalStaffFound += count($collectors);
        foreach ($collectors as $c) {
            $check = $pdo->prepare("
                SELECT COUNT(*) FROM garbage_salary
                WHERE collector_id=? AND salary_month=? AND salary_year=?
            ");
            $check->execute([$c['id'], $targetMonth, $targetYear]);

            if ($check->fetchColumn() > 0) {
                $totalSkipped++;
                continue;
            }

            $insert = $pdo->prepare("
                INSERT INTO garbage_salary (collector_id, salary_month, salary_year, salary_amount, status, generated_at)
                VALUES (?, ?, ?, ?, 'unpaid', NOW())
            ");
            $insert->execute([$c['id'], $targetMonth, $targetYear, $c['salary']]);

            $generated++;
        }
    }

    // DETERMINE REASON IF GENERATED == 0
    if ($generated == 0) {
        if ($totalStaffFound == 0) {
            $reason = "No staff (Sweepers, Guards, or Collectors) found in the database.";
        } elseif ($totalSkipped > 0) {
            $reason = "Salary for next month (" . date('F', mktime(0, 0, 0, $targetMonth, 1)) . " $targetYear) has already been generated for all staff.";
        } else {
            $reason = "Unknown error. Check database connections.";
        }
    }
} else {
    // Reason if date is wrong
    $reason = "Billing is locked. You can only generate salary between the 28th and 31st of the month.";
}

// ===========================================================
//    RESULT DISPLAY
//    =========================================================== 
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Salary Generation Result</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-light">
    <div class="container mt-5">
        <!-- mx-auto centers the card, max-width: 550px makes it small -->
        <div class="card shadow-sm mx-auto" style="max-width: 550px; margin-top:7rem">
            <div class="card-header bg-dark text-white">
                <h4 class="mb-0"><i class="fa fa-money-bill-wave me-2"></i>Salary Generation Result</h4>
            </div>
            <div class="card-body">

                <!-- BILL GENERATION RESULT -->
                <div class="alert <?php echo $generated > 0 ? 'alert-success' : 'alert-warning'; ?>" role="alert">
                    <h5 class="alert-heading">
                        <?php if ($generated > 0): ?>
                            <i class="fa fa-check-circle me-2"></i> Success
                        <?php else: ?>
                            <i class="fa fa-exclamation-triangle me-2"></i> No Salaries Generated
                        <?php endif; ?>
                    </h5>
                    <hr>

                    <?php if ($generated > 0): ?>
                        <p><strong>Salaries Generated for:</strong> <?php echo date('F Y', mktime(0, 0, 0, $targetMonth, 1, $targetYear)); ?></p>
                        <p><strong>Total Slips Created:</strong> <?php echo $generated; ?></p>
                        <p class="mb-0">Salary slips for Sweepers, Guards, and Garbage Collectors have been created successfully.</p>
                    <?php else: ?>
                        <p><strong>Reason:</strong> <?php echo $reason; ?></p>
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
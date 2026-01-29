<?php
require_once __DIR__ . '/../core/config.php';

requireRole(['admin']); // optional if cron is public

// DATE CONTEXT
$today        = date('Y-m-d');
$currentDay   = date('j');
$currentMonth = date('n');
$currentYear  = date('Y');

// RESULT COUNTERS
$generated = 0;

// =======================================================
// 1️⃣ GENERATE NEXT MONTH SALARY (ONLY 28–31)
// =======================================================
if ($currentDay >= 28) {

    // TARGET NEXT MONTH
    $targetMonth = date('n', strtotime('+1 month'));
    $targetYear  = date('Y', strtotime('+1 month'));

    /* ================= SWEEPERS ================= */
    $stmt = $pdo->query("SELECT id, salary FROM sweepers");
    $workers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($workers as $w) {

        // prevent duplicate
        $check = $pdo->prepare("
            SELECT COUNT(*) FROM sweeper_salary
            WHERE sweeper_id=? AND month=? AND year=?
        ");
        $check->execute([$w['id'], $targetMonth, $targetYear]);

        if ($check->fetchColumn() > 0) continue;

        // insert salary bill
        $insert = $pdo->prepare("
            INSERT INTO sweeper_salary (sweeper_id, month, year, salary, status, generated_at)
            VALUES (?, ?, ?, ?, 'unpaid', NOW())
        ");
        $insert->execute([$w['id'], $targetMonth, $targetYear, $w['salary']]);

        $generated++;
    }


    /* ================= SECURITY GUARDS ================= */
    $stmt = $pdo->query("SELECT id, salary FROM security_guards");
    $guards = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($guards as $g) {

        $check = $pdo->prepare("
            SELECT COUNT(*) FROM guard_salary
            WHERE guard_id=? AND month=? AND year=?
        ");
        $check->execute([$g['id'], $targetMonth, $targetYear]);

        if ($check->fetchColumn() > 0) continue;

        $insert = $pdo->prepare("
            INSERT INTO guard_salary (guard_id, month, year, salary, status, generated_at)
            VALUES (?, ?, ?, ?, 'unpaid', NOW())
        ");
        $insert->execute([$g['id'], $targetMonth, $targetYear, $g['salary']]);

        $generated++;
    }


    /* ================= GARBAGE COLLECTORS ================= */
    $stmt = $pdo->query("SELECT id, salary FROM garbage_collectors");
    $collectors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($collectors as $c) {

        $check = $pdo->prepare("
            SELECT COUNT(*) FROM garbage_salary
            WHERE collector_id=? AND month=? AND year=?
        ");
        $check->execute([$c['id'], $targetMonth, $targetYear]);

        if ($check->fetchColumn() > 0) continue;

        $insert = $pdo->prepare("
            INSERT INTO garbage_salary (collector_id, month, year, salary, status, generated_at)
            VALUES (?, ?, ?, ?, 'unpaid', NOW())
        ");
        $insert->execute([$c['id'], $targetMonth, $targetYear, $c['salary']]);

        $generated++;
    }
}

// =======================================================
// RESULT OUTPUT
// =======================================================
echo "<h3>Salary Cron Job Result</h3>";
echo "📌 Salary Bills Generated (Next Month): <b>$generated</b><br>";
echo "🕒 Run Date: $today";

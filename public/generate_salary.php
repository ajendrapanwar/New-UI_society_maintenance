<?php
require_once __DIR__ . '/../core/config.php';

// ================= DATE =================
$today = date('Y-m-d');
$currentDay   = date('j');
$currentMonth = date('n');
$currentYear  = date('Y');

echo "Cron Salary Generator Running...<br>";

// RUN ONLY ON 1ST DAY
if ($currentDay != 1) {
    exit("Not 1st day. No salary generated.");
}

$totalGenerated = 0;

/* =====================================================
   1️⃣ SWEEPER SALARY
===================================================== */

$stmt = $pdo->query("SELECT id, salary FROM sweepers WHERE status='active'");
$sweepers = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($sweepers as $s) {

    // prevent duplicate
    $check = $pdo->prepare("
        SELECT COUNT(*) FROM sweeper_salary 
        WHERE sweeper_id=? AND month=? AND year=?
    ");
    $check->execute([$s['id'], $currentMonth, $currentYear]);
    if ($check->fetchColumn() > 0) continue;

    $insert = $pdo->prepare("
        INSERT INTO sweeper_salary (sweeper_id, month, year, salary, status, generated_at)
        VALUES (?, ?, ?, ?, 'unpaid', NOW())
    ");
    $insert->execute([$s['id'], $currentMonth, $currentYear, $s['salary']]);

    $totalGenerated++;
}

/* =====================================================
   2️⃣ SECURITY GUARD SALARY
===================================================== */

$stmt = $pdo->query("SELECT id, salary FROM security_guards WHERE status='active'");
$guards = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($guards as $g) {

    $check = $pdo->prepare("
        SELECT COUNT(*) FROM guard_salary 
        WHERE guard_id=? AND month=? AND year=?
    ");
    $check->execute([$g['id'], $currentMonth, $currentYear]);
    if ($check->fetchColumn() > 0) continue;

    $insert = $pdo->prepare("
        INSERT INTO guard_salary (guard_id, month, year, salary, status, generated_at)
        VALUES (?, ?, ?, ?, 'unpaid', NOW())
    ");
    $insert->execute([$g['id'], $currentMonth, $currentYear, $g['salary']]);

    $totalGenerated++;
}

/* =====================================================
   3️⃣ GARBAGE COLLECTOR SALARY
===================================================== */

$stmt = $pdo->query("SELECT id, salary FROM garbage_collectors WHERE status='active'");
$collectors = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($collectors as $c) {

    $check = $pdo->prepare("
        SELECT COUNT(*) FROM garbage_salary 
        WHERE collector_id=? AND month=? AND year=?
    ");
    $check->execute([$c['id'], $currentMonth, $currentYear]);
    if ($check->fetchColumn() > 0) continue;

    $insert = $pdo->prepare("
        INSERT INTO garbage_salary (collector_id, month, year, salary, status, generated_at)
        VALUES (?, ?, ?, ?, 'unpaid', NOW())
    ");
    $insert->execute([$c['id'], $currentMonth, $currentYear, $c['salary']]);

    $totalGenerated++;
}

echo "✅ Salary Generated: $totalGenerated records<br>";
echo "Run Date: $today";

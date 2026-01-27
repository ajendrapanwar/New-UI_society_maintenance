<?php
require_once __DIR__ . '/../core/config.php';

// ACCESS CONTROL
requireRole(['admin', 'user']);

$flat_id = '';

// Get total flats
$stmt = $pdo->query("SELECT COUNT(*) AS total_flats FROM flats");
$total_flats = $stmt->fetch(PDO::FETCH_ASSOC)['total_flats'];

// Get pending/overdue bills
if ($_SESSION['user_role'] === 'admin') {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS total_bills 
        FROM maintenance_bills 
        WHERE status IN ('pending','overdue')
    ");
    $stmt->execute();
    $total_bills = $stmt->fetch(PDO::FETCH_ASSOC)['total_bills'];
} else {
    $stmt = $pdo->prepare("SELECT flat_id FROM allotments WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $flat_id = $stmt->fetch(PDO::FETCH_ASSOC)['flat_id'] ?? null;

    if ($flat_id) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS total_bills 
            FROM maintenance_bills 
            WHERE status IN ('pending','overdue') AND flat_id = ?
        ");
        $stmt->execute([$flat_id]);
        $total_bills = $stmt->fetch(PDO::FETCH_ASSOC)['total_bills'];
    } else {
        $total_bills = 0;
    }
}

// Total allotments
$stmt = $pdo->query("SELECT COUNT(*) AS total_allotments FROM allotments");
$total_allotments = $stmt->fetch(PDO::FETCH_ASSOC)['total_allotments'];
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Dashboard</h1>
    <div class="row g-4">
        <?php if ($_SESSION['user_role'] === 'admin'): ?>
            <div class="col-xl-3 col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Total Flats</div>
                            <div class="display-6 fw-bold"><?= $total_flats ?></div>
                        </div>
                        <i class="fa fa-building fa-2x text-primary"></i>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Pending / Overdue Bills</div>
                            <div class="display-6 fw-bold"><?= $total_bills ?></div>
                        </div>
                        <i class="fa fa-file-invoice fa-2x text-warning"></i>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Total Allotments</div>
                            <div class="display-6 fw-bold"><?= $total_allotments ?></div>
                        </div>
                        <i class="fa fa-house-user fa-2x text-warning"></i>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <div class="col-xl-3 col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Pending / Overdue Bills</div>
                            <div class="display-6 fw-bold"><?= $total_bills ?></div>
                        </div>
                        <i class="fa fa-file-invoice fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

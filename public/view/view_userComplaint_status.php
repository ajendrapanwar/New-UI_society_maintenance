<?php
require_once __DIR__ . '/../../core/config.php';
requireRole(['user']);

/* Logged User ID */
$user_id = $_SESSION['user_id'];

/* FETCH ONLY LOGGED USER COMPLAINTS */
$stmt = $pdo->prepare("
    SELECT * 
    FROM complaints 
    WHERE user_id = ?
    ORDER BY id DESC
");
$stmt->execute([$user_id]);
$complaints = $stmt->fetchAll();

include __DIR__ . '/../../resources/layout/header.php';
?>

<div class="sidebar-overlay" onclick="toggleSidebar()"></div>


<div class="container-fluid px-4">
    <h1 class="mt-4">My Complaint Status</h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Complaint Status</li>
    </ol>

    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col-6">
                    <h5 class="card-title">Complaint Status List</h5>
                </div>
                <div class="col-6 text-end">
                    <a href="<?= BASE_URL ?>add/add_complaint.php" class="btn btn-success btn-sm">+ Raise Complaint</a>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Resolve Note</th>
                            <th>Resolved At</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($complaints)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-danger fw-bold">
                                    No Complaint Found !!!
                                </td>
                            </tr>
                        <?php else: ?>

                            <?php foreach ($complaints as $c): ?>
                                <tr>
                                    <td><?= $c['id'] ?></td>
                                    <td><?= htmlspecialchars($c['subject']) ?></td>
                                    <td><?= htmlspecialchars($c['message']) ?></td>

                                    <!-- STATUS -->
                                    <td>
                                        <?php
                                        if ($c['status'] == 'pending') echo "<span class='badge bg-warning'>Pending</span>";
                                        if ($c['status'] == 'processing') echo "<span class='badge bg-info'>Processing</span>";
                                        if ($c['status'] == 'completed') echo "<span class='badge bg-success'>Completed</span>";
                                        ?>
                                    </td>

                                    <!-- CREATED DATE -->
                                    <td><?= $c['created_at'] ?></td>

                                    <!-- RESOLVE NOTE -->
                                    <td><?= !empty($c['resolve_note']) ? htmlspecialchars($c['resolve_note']) : '-' ?></td>

                                    <!-- RESOLVED DATE -->
                                    <td><?= !empty($c['resolved_at']) ? $c['resolved_at'] : '-' ?></td>
                                </tr>
                            <?php endforeach; ?>

                        <?php endif; ?>
                    </tbody>
                </table>

            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../resources/layout/footer.php'; ?>
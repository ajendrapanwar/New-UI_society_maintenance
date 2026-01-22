<?php
require_once __DIR__ . '/../../core/config.php';

/* ================= ACCESS CONTROL ================= */
// if (
//     !isset($_SESSION['user_id']) ||
//     !in_array($_SESSION['user_role'], ['admin', 'cashier'])
// ) {
//     header('Location: ' . BASE_URL . 'logout.php');
//     exit();
// }

// Admin access check
requireRole(['admin', 'cashier']);

$allotmentId = $_GET['id'] ?? '';

if (!ctype_digit($allotmentId)) {
    die('Invalid Allotment ID');
}

/* ================= USER + FLAT INFO ================= */
$stmt = $pdo->prepare("
    SELECT 
        u.name,
        u.email,
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
    die('User not found');
}

include __DIR__ . '/../../resources/layout/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Maintenance Bill Records</h1>

    <ol class="breadcrumb mb-4">
        <?php if ($_SESSION['user_role'] !== 'cashier'): ?>
            <li class="breadcrumb-item">
                <a href="<?= BASE_URL ?>dashboard.php">Dashboard</a>
            </li>
        <?php endif; ?>

        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>maintanenceRecords.php">Maintenance Records</a></li>
        <li class="breadcrumb-item active">User Bills</li>
    </ol>

    <!-- USER INFO -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body d-flex flex-wrap gap-4">
            <div><strong>Name:</strong> <?= htmlspecialchars($user['name']) ?></div>
            <div><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></div>
            <div><strong>Flat:</strong> <?= htmlspecialchars($user['flat_number']) ?></div>
            <div><strong>Block:</strong> <?= htmlspecialchars($user['block_number']) ?></div>
            <div><strong>Type:</strong> <?= htmlspecialchars($user['flat_type']) ?></div>
        </div>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= $_SESSION['success'];
            unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <!-- DATATABLE -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="bills-table" class="table table-bordered table-striped">
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
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../resources/layout/footer.php'; ?>

<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.2/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.2/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(function() {
        $('#bills-table').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 5,
            lengthMenu: [5, 10, 25, 50],
            order: [
                [0, 'desc']
            ],

            ajax: {
                url: '<?= BASE_URL ?>action.php',
                type: 'POST',
                data: {
                    action: 'fetch_user_bills',
                    allotment_id: '<?= $allotmentId ?>'
                }
            },

            columns: [{
                    data: 'month_year'
                },
                {
                    data: 'amount'
                },
                {
                    data: 'fine'
                },
                {
                    data: 'total'
                },
                {
                    data: 'status'
                },
                {
                    data: 'payment_id'
                },
                {
                    data: 'payment_mode'
                },
                {
                    data: 'paid_on'
                },
                {
                    data: 'overdue'
                },
                {
                    data: 'action',
                    orderable: false,
                    searchable: false
                }
            ]
        });
    });
</script>
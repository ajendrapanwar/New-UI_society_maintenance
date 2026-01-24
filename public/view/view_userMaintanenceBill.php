<?php
require_once __DIR__ . '/../../core/config.php';

/* ================= ACCESS CONTROL ================= */
// if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
//     header('Location: ' . BASE_URL . 'logout.php');
//     exit();
// }

requireRole(['user']);

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

include __DIR__ . '/../../resources/layout/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">My Maintenance Bill History</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Bills History</li>
    </ol>

    <!-- USER INFO -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body d-flex flex-wrap gap-4">
            <div><strong>Name:</strong> <?= htmlspecialchars($user['name']) ?></div>
            <div><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></div>
            <div><strong>Flat:</strong> <?= htmlspecialchars($user['flat_number']) ?></div>
            <div><strong>Block:</strong> <?= htmlspecialchars($user['block_number']) ?></div>
            <div><strong>Flat Type:</strong> <?= htmlspecialchars($flatType) ?></div>
        </div>
    </div>

    <!-- BILLS TABLE -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="user-bills-table" class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Month / Year</th>
                            <th>Amount</th>
                            <th>Fine</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Mode</th>
                            <th>Paid On</th>
                            <th>Overdue</th>
                            <th width="150">Action</th>
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
        $('#user-bills-table').DataTable({
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
                    action: 'fetch_user_bills_user',
                    user_id: '<?= $userId ?>',
                    flat_id: '<?= $flatId ?>'
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
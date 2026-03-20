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


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Records</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../../assets/css/styles.css">
</head>

<body>

    <div class="main-wrapper">
        <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

        <main id="main-content">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="fw-800 m-0">Maintenance Ledger</h1>

                <!-- Back Button -->
                <a href="javascript:history.back()" class="btn btn-outline-dark btn-sm">
                    <i class="fa-solid fa-angle-left me-1"></i> Back
                </a>
            </div>

            <div class="data-card shadow-sm border-0">
                <div class="card-body d-flex flex-wrap align-items-center gap-3">
                    <!-- User Info -->
                    <div><strong>Name:</strong> <?= htmlspecialchars($user['name']) ?></div>
                    <div><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></div>
                    <div><strong>Flat:</strong> <?= htmlspecialchars($user['flat_number']) ?></div>
                    <div><strong>Block:</strong> <?= htmlspecialchars($user['block_number']) ?></div>
                    <div><strong>Type:</strong> <?= htmlspecialchars($user['flat_type']) ?></div>

                </div>
            </div>

            <div class="data-card shadow-sm border-0">
                <div class="table-responsive">
                    <table id="bills-table" class="table table-hover w-100">
                        <thead>
                            <tr>
                                <th>Month / Year</th>
                                <th>Amount</th>
                                <th>Fine</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Mode</th>
                                <th>Paid On</th>
                                <th>Overdue</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.2/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {

            $('#bills-table').DataTable({
                dom: '<"d-flex justify-content-between mb-4"lf>rt<"d-flex justify-content-between mt-4"ip>',
                processing: true,
                serverSide: true,
                responsive: true,
                pageLength: 5,
                lengthMenu: [5, 10, 25, 50],

                language: {
                    search: "",
                    searchPlaceholder: "Search records..."
                },


                order: [
                    [0, 'desc']
                ],

                ajax: {
                    url: '<?= BASE_URL ?>action.php',
                    type: 'POST',
                    data: function(d) {
                        d.action = 'fetch_user_bills';
                        d.allotment_id = '<?= $allotmentId ?>';
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
                ],

                columnDefs: [{
                    targets: [3, 4, 8],
                    orderable: false
                }]
            });

        });
    </script>

</body>

</html>

<?php include __DIR__ . '/popup.php'; ?>

<?php include __DIR__ . '/../../resources/layout/footer.php'; ?>
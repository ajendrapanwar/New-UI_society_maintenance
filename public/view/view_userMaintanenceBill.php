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
                <h1 class="fw-800 m-0">Maintenance Bill History</h1>

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
                    <table id="user-bills-table" class="table table-hover w-100">
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

            const table = $('#user-bills-table').DataTable({
                dom: '<"d-flex justify-content-between mb-4"lf>rt<"d-flex justify-content-between mt-4"ip>',
                processing: true,
                serverSide: true,
                pageLength: 5,
                lengthMenu: [5, 10, 25, 50],
                language: {
                    search: "",
                    searchPlaceholder: "Search records..."
                },
                ajax: {
                    url: '<?= BASE_URL ?>action.php',
                    type: 'POST',
                    data: {
                        action: 'fetch_user_bills_user'
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
                        orderable: false
                    }
                ]
            });

            // Handle Pay Now Button Click
            $(document).on('click', '.pay-now-btn', function() {
                const btn = $(this);
                const options = {
                    "key": "rzp_test_SFWqAzBj2hWG9s", // Use your key
                    "amount": btn.data('amount'),
                    "currency": "INR",
                    "name": "Society Management",
                    "description": "Payment for " + btn.data('month'),
                    "handler": function(response) {
                        verifyPayment(response, btn.data('bill-id'));
                    },
                    "prefill": {
                        "name": "<?= $user['name'] ?>",
                        "email": "<?= $user['email'] ?>"
                    },
                    "theme": {
                        "color": "#3399cc"
                    }
                };
                const rzp = new Razorpay(options);
                rzp.open();
            });

            // Function to verify and save to DB
            function verifyPayment(razorResponse, billId) {
                $.ajax({
                    url: '<?= BASE_URL ?>action.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'capture_payment',
                        bill_id: billId,
                        razorpay_payment_id: razorResponse.razorpay_payment_id,
                        payment_mode: 'online',
                        payment_method: 'upi' // Note: This matches your ENUM
                    },
                    success: function(res) {
                        if (res.status === 'success') {
                            alert('Payment successful and recorded!');
                            table.ajax.reload();
                        } else {
                            alert('Error updating record: ' + res.message);
                        }
                    }
                });
            }
        });
    </script>

    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>


</body>

</html>


<?php include __DIR__ . '/../../resources/layout/footer.php'; ?>
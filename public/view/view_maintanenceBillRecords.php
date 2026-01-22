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
<style>

    table.dataTable td {
        vertical-align: middle !important;
        white-space: nowrap;
    }

    table.dataTable .btn {
        line-height: 1.2;
    }
</style>


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









<div class="modal fade" id="onlinePaymentModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form id="onlinePaymentForm" class="modal-content" enctype="multipart/form-data">
      <div class="modal-header">
        <h5 class="modal-title">Online Payment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" name="bill_id" id="online_bill_id">

        <div class="mb-2">
          <label class="form-label">Payment Method</label>
          <select name="payment_mode" class="form-select" required>
            <option value="">Select</option>
            <option value="upi">UPI</option>
            <option value="credit_card">Credit Card</option>
            <option value="debit_card">Debit Card</option>
            <option value="netbanking">Net Banking</option>
          </select>
        </div>

        <div class="mb-2">
          <label class="form-label">Upload Proof</label>
          <input type="file" name="proof" class="form-control" accept="image/*,.pdf" required>
        </div>

        <div class="mb-2">
          <label class="form-label">Note</label>
          <textarea name="note" class="form-control" required></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Submit</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
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

<script>
$(document).on('submit', '.payment-form', function(e) {
    e.preventDefault();

    let billId = $(this).data('bill');
    let type = $(this).find('.payment-type').val();

    if (!type) {
        alert('Please select payment type');
        return;
    }

    if (type === 'cash') {
        if (confirm('Mark payment as CASH?')) {
            window.location.href =
                '<?= BASE_URL ?>action.php?action=mark_cash_payment&bill_id=' + billId;
        }
    }

    if (type === 'online') {
        $('#online_bill_id').val(billId);
        $('#onlinePaymentModal').modal('show');
    }
});

$('#onlinePaymentForm').on('submit', function(e) {
    e.preventDefault();

    let formData = new FormData(this);
    formData.append('action', 'mark_online_payment');

    $.ajax({
        url: '<?= BASE_URL ?>action.php',
        method: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function() {
            location.reload();
        },
        error: function() {
            alert('Payment failed');
        }
    });
});
</script>


<!-- THIS FOR AUTO CLCIK THE CASH OR ONLINE PAYMENT -->
<script>
$(document).on('change', '.payment-type', function () {
    let billId = $(this).data('bill');
    let type   = $(this).val();

    if (!type) return;

    if (type === 'cash') {
        if (confirm('Mark payment as CASH?')) {
            window.location.href =
                '<?= BASE_URL ?>action.php?action=mark_cash_payment&bill_id=' + billId;
        } else {
            $(this).val('');
        }
    }

    if (type === 'online') {
        $('#online_bill_id').val(billId);
        $('#onlinePaymentModal').modal('show');
        $(this).val('');
    }
});
</script>

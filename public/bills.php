<?php
require_once __DIR__ . '/../core/config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'user'])) {
    header('Location: ' . BASE_URL . 'logout.php');
    exit();
}

include('../resources/layout/header.php');
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Bills Management</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Bills Management</li>
    </ol>

    <?php
    if (isset($_SESSION['success'])) {
        echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
        unset($_SESSION['success']);
    }
    ?>

    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col col-6">
                    <h5 class="card-title">Bills Management</h5>
                </div>
                <div class="col col-6">
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                        <div class="float-end">
                            <a href="add/add_bill.php" class="btn btn-success btn-sm">Add</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="bills-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Bill Title</th>
                            <th>Flat Number</th>
                            <th>Bill Amount</th>
                            <th>Month</th>
                            <th>Paid Amount</th>
                            <th>Updated At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.2/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.2.0/css/bootstrap.min.css">
<script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.2/js/dataTables.bootstrap5.min.js"></script>

<?php include('../resources/layout/footer.php'); ?>

<script>
$(document).ready(function() {
    $('#bills-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'action.php',
            type: 'POST',
            data: { action: 'fetch_bills' }
        },
        columns: [
            { data: 'id' },
            { data: 'bill_title' },
            { data: 'flat_number' },
            { data: 'bill_amount' },
            { data: 'month' },
            { data: 'paid_amount' },
            { data: 'updated_at' },
            { data: 'actions', orderable: false, searchable: false }
        ]
    });

    $(document).on('click', '.delete_btn', function() {
        if (confirm("Are you sure you want to remove this bill?")) {
            window.location.href = 'bills.php?action=delete&id=' + $(this).data('id');
        }
    });
});
</script>

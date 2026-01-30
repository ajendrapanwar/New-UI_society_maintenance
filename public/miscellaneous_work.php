<?php

include('../core/config.php');

// VIEW ACCESS: Admin and Cashier can view the list
requireRole(['admin','cashier']);

// Handle Delete
if (isset($_GET['action'], $_GET['id']) &&
    $_GET['action'] === 'delete' &&
    is_numeric($_GET['id'])
) {
    // SECURITY: Only Admin can perform the delete action
    requireRole(['admin']); 

    $stmt = $pdo->prepare("DELETE FROM miscellaneous_works WHERE id = ?");
    $stmt->execute([$_GET['id']]);

    $_SESSION['success'] = 'Work record has been removed successfully.';
    header('Location: ' . BASE_URL . 'miscellaneous_work.php');
    exit;
}

include('../resources/layout/header.php');
?>

<div class="container-fluid px-4">  
    <h1 class="mt-4">Miscellaneous Work</h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Miscellaneous Work</li>
    </ol>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col-6"><h5 class="card-title">Miscellaneous Work List</h5></div>
                <div class="col-6 text-end">
                    <a href="<?= BASE_URL ?>add/add_miscellaneous_work.php" class="btn btn-success btn-sm">Add Miscellaneous Work</a>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="misc-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Work Title</th>
                            <th>Description</th>
                            <th>Worker Name</th>
                            <th>Contact</th>
                            <th>Amount</th>
                            <th>Month</th>
                            <th>Year</th>
                            <th>Date</th>
                            <!-- ACTION COLUMN: Only show if Admin -->
                            <?php if($_SESSION['user_role'] === 'admin'): ?>
                                <th width="100">Action</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- DataTables CSS & JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.2/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.2/js/dataTables.bootstrap5.min.js"></script>

<?php include('../resources/layout/footer.php'); ?>

<script>
    $(document).ready(function () {

        // Define base columns
        var tableColumns = [
            { data: 'id' },
            { data: 'work_title' },
            { data: 'description' },   
            { data: 'worker_name' },
            { data: 'contact_number' },   
            { data: 'amount' },
            { data: 'month' },   
            { data: 'year' },    
            { data: 'created_at' }
        ];

        // Add Action column ONLY if Admin
        <?php if($_SESSION['user_role'] === 'admin'): ?>
        tableColumns.push({
            data: null,
            orderable: false,
            render: function (data) {
                return `
                    <button class="btn btn-sm btn-danger delete_btn" data-id="${data.id}">Delete</button>
                `;
            }
        });
        <?php endif; ?>

        $('#misc-table').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 5,    
            lengthMenu: [5, 10, 25, 50],        
            ajax: {
                url: '<?= BASE_URL ?>action.php',
                type: 'POST',
                data: { action: 'fetch_misc_works' }
            },
            columns: tableColumns // Use the dynamically built columns array
        });

        // Handle Delete Button Click
        $(document).on('click', '.delete_btn', function () {
            if (confirm('Are you sure you want to delete this work record?')) {
                window.location.href =
                    '<?= BASE_URL ?>miscellaneous_work.php?action=delete&id=' + $(this).data('id');
            }
        });
    });
</script>  
<?php
require_once __DIR__ . '/../core/config.php';

// Everyone can view page, but only admin can do actions
$userRole = $_SESSION['user_role'] ?? '';

// Handle delete only if admin
if ($userRole === 'admin' &&
    isset($_GET['action'], $_GET['id']) &&
    $_GET['action'] === 'delete' &&
    ctype_digit($_GET['id'])
) {
    $stmt = $pdo->prepare("DELETE FROM security_guards WHERE id = ?");
    $stmt->execute([$_GET['id']]);

    $_SESSION['success'] = 'Guard removed successfully';
    header('Location: ' . BASE_URL . 'guards.php');
    exit;
}

include('../resources/layout/header.php');
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Security Guards</h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>salary_security.php">All Security Guard</a></li>
        <li class="breadcrumb-item active">All Guard</li>
    </ol>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <h5 class="mb-0">Guards List</h5>

            <?php if ($userRole === 'admin'): ?>
                <a href="<?= BASE_URL ?>add/add_security_guard.php" class="btn btn-success btn-sm">
                    Add Guard
                </a>
            <?php endif; ?>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="guards-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Mobile</th>
                            <th>DOB</th>
                            <th>Gender</th>
                            <th>Shift</th>
                            <th>Joining Date</th>
                            <th>Salary</th>
                            <?php if ($userRole === 'admin'): ?>
                                <th width="160">Action</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.2/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.2/js/dataTables.bootstrap5.min.js"></script>

<?php include('../resources/layout/footer.php'); ?>

<script>
$(document).ready(function() {
    var isAdmin = '<?= $userRole ?>' === 'admin';

    var columns = [
        { data: 'id' },
        { data: 'name' },
        { data: 'mobile' },
        { data: 'dob' },
        { data: 'gender' },
        { data: 'shift' },
        { data: 'joining_date' },
        { data: 'salary' }
    ];

    if (isAdmin) {
        columns.push({
            data: null,
            orderable: false,
            render: function(data) {
                return `
                    <a href="<?= BASE_URL ?>edit/edit_guard.php?id=${data.id}" class="btn btn-sm btn-primary">Edit</a>
                    <button class="btn btn-sm btn-danger delete-btn" data-id="${data.id}">Delete</button>
                `;
            }
        });
    }

    $('#guards-table').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 5,
        lengthMenu: [5, 10, 25, 50],
        ajax: {
            url: '<?= BASE_URL ?>action.php',
            type: 'POST',
            data: { action: 'fetch_guards' }
        },
        columns: columns
    });

    $(document).on('click', '.delete-btn', function() {
        if (confirm('Are you sure you want to delete this guard?')) {
            window.location.href =
                '<?= BASE_URL ?>guards.php?action=delete&id=' + $(this).data('id');
        }
    });

});
</script>

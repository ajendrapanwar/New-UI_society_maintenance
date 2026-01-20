<?php
require_once __DIR__ . '/../core/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . 'logout.php');
    exit;
}

// Handle Delete
if (isset($_GET['action'], $_GET['id']) &&
    $_GET['action'] === 'delete' &&
    is_numeric($_GET['id'])
) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$_GET['id']]);

    $_SESSION['success'] = 'User has been removed successfully.';
    header('Location: ' . BASE_URL . 'users.php');
    exit;
}

include('../resources/layout/header.php');
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">User Management</h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">User Management</li>
    </ol>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col-6"><h5 class="card-title">Users Management</h5></div>
                <div class="col-6 text-end">
                    <a href="<?= BASE_URL ?>add/add_users.php" class="btn btn-success btn-sm">Add User</a>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>DOB</th>
                            <th>Gender</th>
                            <th>Role</th>
                            <th width="150">Action</th>
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

<?php include __DIR__ . '/../resources/layout/footer.php'; ?>

<script>
    $(document).ready(function () {

        $('#users-table').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 5,    
            lengthMenu: [5, 10, 25, 50],        
            ajax: {
                url: '<?= BASE_URL ?>action.php',
                type: 'POST',
                data: { action: 'fetch_users' }
            },
            columns: [
                { data: 'id' },
                { data: 'name' },
                { data: 'email' },
                { data: 'mobile' },   
                { data: 'dob' },      
                { data: 'gender' },   
                { data: 'role' },
                {
                    data: null,
                    orderable: false,
                    render: function (data) {
                        return `
                            <a href="<?= BASE_URL ?>edit/edit_user.php?id=${data.id}" class="btn btn-sm btn-primary">Edit</a>
                            <button class="btn btn-sm btn-danger delete_btn" data-id="${data.id}">Delete</button>
                        `;
                    }
                }
            ]
        });

        $(document).on('click', '.delete_btn', function () {
            if (confirm('Are you sure you want to delete this user?')) {
                window.location.href =
                    '<?= BASE_URL ?>users.php?action=delete&id=' + $(this).data('id');
            }
        });
    });
</script>
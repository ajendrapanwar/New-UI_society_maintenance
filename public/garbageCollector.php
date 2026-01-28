<?php
require_once __DIR__ . '/../core/config.php';

$userRole = $_SESSION['user_role'] ?? '';

// DELETE (ADMIN ONLY)
if (
    $userRole === 'admin' &&
    isset($_GET['action'], $_GET['id']) &&
    $_GET['action'] === 'delete' &&
    ctype_digit($_GET['id'])
) {
    $stmt = $pdo->prepare("DELETE FROM garbage_collectors WHERE id = ?");
    $stmt->execute([$_GET['id']]);

    $_SESSION['success'] = 'Garbage collector removed successfully';
    header('Location: ' . BASE_URL . 'garbageCollector.php');
    exit;
}

include('../resources/layout/header.php');
?>


<div class="container-fluid px-4">
    <h1 class="mt-4">Garbage Collector</h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>salary_garbageCollector.php">Garbage Collector Bills</a></li>
        <li class="breadcrumb-item active">View Garbage Collector</li>
    </ol>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <h5 class="mb-0">Garbage Collector List</h5>

            <?php if ($userRole === 'admin'): ?>
                <a href="<?= BASE_URL ?>add/add_garbageCollector.php" class="btn btn-success btn-sm">
                    Add Garbage Collector
                </a>
            <?php endif; ?>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="collector-table">
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
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT * FROM garbage_collectors ORDER BY id DESC");
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                        ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['mobile']) ?></td>
                                <td><?= htmlspecialchars($row['dob']) ?></td>
                                <td><?= htmlspecialchars($row['gender']) ?></td>
                                <td><?= ucfirst($row['shift']) ?></td>
                                <td><?= htmlspecialchars($row['joining_date']) ?></td>
                                <td>₹<?= number_format($row['salary'], 2) ?></td>

                                <?php if ($userRole === 'admin'): ?>
                                    <td>
                                        <a href="<?= BASE_URL ?>edit/edit_garbageCollector.php?id=<?= $row['id'] ?>"
                                            class="btn btn-sm btn-primary">
                                            Edit
                                        </a>
                                        <a href="<?= BASE_URL ?>garbageCollector.php?action=delete&id=<?= $row['id'] ?>"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Delete this garbage collector?')">
                                            Delete
                                        </a>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
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
        $('#collector-table').DataTable({
            pageLength: 10,
            lengthMenu: [5, 10, 25, 50],
            order: [
                [0, 'desc']
            ]
        });
    });
</script>
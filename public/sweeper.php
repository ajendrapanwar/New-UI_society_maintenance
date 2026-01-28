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
    $stmt = $pdo->prepare("DELETE FROM sweepers WHERE id = ?");
    $stmt->execute([$_GET['id']]);

    $_SESSION['success'] = 'Sweeper removed successfully';
    header('Location: ' . BASE_URL . 'sweepers.php');
    exit;
}

include('../resources/layout/header.php');
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Sweeper</h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>salary_sweeper.php">Sweeper Bills</a></li>
        <li class="breadcrumb-item active">View Sweeper</li>
    </ol>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <h5 class="mb-0">Sweeper List</h5>

            <?php if ($userRole === 'admin'): ?>
                <a href="<?= BASE_URL ?>add/add_sweeper.php" class="btn btn-success btn-sm">
                    Add Sweeper
                </a>
            <?php endif; ?>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="sweeper-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Mobile</th>
                            <th>DOB</th>
                            <th>Gender</th>
                            <th>Joining Date</th>
                            <th>Salary</th>
                            <?php if ($userRole === 'admin'): ?>
                                <th width="160">Action</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT * FROM sweepers ORDER BY id DESC");
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                        ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['mobile']) ?></td>
                                <td><?= htmlspecialchars($row['dob']) ?></td>
                                <td><?= htmlspecialchars($row['gender']) ?></td>
                                <td><?= htmlspecialchars($row['joining_date']) ?></td>
                                <td>₹<?= number_format($row['salary'], 2) ?></td>

                                <?php if ($userRole === 'admin'): ?>
                                    <td>
                                        <a href="<?= BASE_URL ?>edit/edit_sweeper.php?id=<?= $row['id'] ?>"
                                            class="btn btn-sm btn-primary">
                                            Edit
                                        </a>
                                        <a href="<?= BASE_URL ?>sweepers.php?action=delete&id=<?= $row['id'] ?>"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Delete this sweeper?')">
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

<script>
    $(document).ready(function () {
        $('#sweeper-table').DataTable({
            pageLength: 10,
            order: [[0, 'desc']]
        });
    });
</script>

<?php include('../resources/layout/footer.php'); ?>

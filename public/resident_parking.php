<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

requireRole(['admin']);

/* DELETE */
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] == 'delete') {
    $stmt = $pdo->prepare("DELETE FROM resident_parking WHERE id=?");
    $stmt->execute([$_GET['id']]);

    // $_SESSION['success'] = "Resident parking deleted successfully";
    flash_set('success', 'Resident parking deleted successfully');
    header("Location: resident_parking.php");
    exit();
}

/* FETCH DATA */
$stmt = $pdo->query("
    SELECT 
        rp.id,
        f.flat_number,
        f.block_number,
        u.name AS user_name,
        rp.mobile,
        rp.vehicle_count,
        rp.vehicle1,
        rp.vehicle2,
        rp.created_at
    FROM resident_parking rp
    JOIN flats f ON rp.flat_id = f.id
    LEFT JOIN users u ON rp.user_id = u.id
    ORDER BY rp.id DESC
");
$parkings = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../resources/layout/header.php';
?>
<style>
    /* Force action buttons in one line */
    .action-btns {
        display: flex;
        gap: 6px;
        white-space: nowrap;
    }

    /* Optional: make buttons smaller and clean */
    .action-btns .btn {
        padding: 3px 8px;
        font-size: 13px;
    }
</style>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident Parking</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>

<body>

    <div class="main-wrapper">

        <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

        <main id="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="fw-800 m-0">Resident Parking</h1>
                <button class="btn btn-brand shadow-sm" data-bs-toggle="modal" data-bs-target="#addParkingModal">Assign Slot</button>
            </div>
            <div class="data-card border-0 shadow-sm">
                <table class="table table-hover datatable w-100">
                    <thead>
                        <tr>
                            <th>Flat No</th>
                            <th>Owner</th>
                            <th>Vehicle Type</th>
                            <th>Vehicle No</th>
                            <th>Slot No</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </main>

    </div>


    <!-- <div class="container-fluid px-4">
    <h1 class="mt-4">Resident Parking</h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Resident Parking</li>
    </ol>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between">
            <h5 class="card-title mb-0">Resident Parking List</h5>
            <a href="add/add_resident_parking.php" class="btn btn-success btn-sm">+ Add Parking</a>
        </div>

        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped" id="parking-table">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Flat No</th>
                        <th>Block</th>
                        <th>Resident Name</th>
                        <th>Mobile</th>
                        <th>No of Vehicles</th>
                        <th>Vehicle 1</th>
                        <th>Vehicle 2</th>
                        <th>Created At</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($parkings as $p): ?>
                        <tr>
                            <td><?= $p['id'] ?></td>
                            <td><?= $p['flat_number'] ?></td>
                            <td><?= $p['block_number'] ?></td>
                            <td><?= $p['user_name'] ?></td>
                            <td><?= $p['mobile'] ?></td>
                            <td><?= $p['vehicle_count'] ?></td>
                            <td><?= $p['vehicle1'] ?></td>
                            <td><?= $p['vehicle2'] ?: '-' ?></td>
                            <td><?= $p['created_at'] ?></td>
                            <td>
                                <div class="action-btns">
                                    <a href="edit/edit_resident_parking.php?id=<?= $p['id'] ?>"
                                        class="btn btn-sm btn-primary">
                                        Edit
                                    </a>

                                    <a href="resident_parking.php?action=delete&id=<?= $p['id'] ?>"
                                        onclick="return confirm('Delete this parking record?')"
                                        class="btn btn-sm btn-danger">
                                        Delete
                                    </a>
                                </div>
                            </td>

                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        </div>
    </div>
    </div> -->

    <!-- DATATABLES -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.2/css/dataTables.bootstrap5.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.2/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#parking-table').DataTable();
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php include '../resources/layout/footer.php'; ?>
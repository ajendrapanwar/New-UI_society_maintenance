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
                    <tbody>
                        <tr>
                            <td>A-402</td>
                            <td>Rajesh Kumar</td>
                            <td>4 Wheeler</td>
                            <td>CH-01-AX-1234</td>
                            <td>P-102</td>
                            <td><button class="btn btn-sm btn-light border"><i class="fa fa-edit"></i></button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>

    </div>


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
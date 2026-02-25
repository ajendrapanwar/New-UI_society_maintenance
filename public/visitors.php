<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

requireRole(['admin']);

/* ================= MARK VISITOR OUT ================= */
if (isset($_GET['action']) && $_GET['action'] == 'out' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("UPDATE visitor_entries SET out_time = NOW() WHERE id = ?");
    $stmt->execute([$_GET['id']]);

    // $_SESSION['success'] = "Visitor marked OUT successfully";
    flash_set('success', 'Visitor marked OUT successfully');
    header('Location: ' . BASE_URL . 'visitors.php');
    exit();
}

/* ================= FETCH VISITOR DATA ================= */
$stmt = $pdo->query("
    SELECT 
        v.*,
        f.flat_number, 
        f.block_number,
        DATE_FORMAT(v.in_time, '%d-%m-%Y %h:%i %p') AS in_time_fmt,
        DATE_FORMAT(v.out_time, '%d-%m-%Y %h:%i %p') AS out_time_fmt
    FROM visitor_entries v
    LEFT JOIN flats f ON v.flat_id = f.id
    ORDER BY v.id DESC
");

$visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../resources/layout/header.php';
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Logs</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">

    <style>
        /* Force action buttons in one line */
        .action-btns {
            display: flex;
            gap: 6px;
            white-space: nowrap;
        }

        /* Optional: make buttons smaller */
        .action-btns .btn {
            padding: 3px 8px;
            font-size: 13px;
        }

        #visitor-table td,
        #visitor-table th {
            white-space: nowrap !important;
        }
    </style>

</head>

<body>


    <div class="main-wrapper">

        <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

        <main id="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="fw-800 m-0">Visitor Management</h1>
                <button class="btn btn-brand shadow-sm" data-bs-toggle="modal" data-bs-target="#checkInModal">
                    <i class="fa-solid fa-plus me-2"></i> New Check-In
                </button>
            </div>

            <div class="data-card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover datatable w-100 align-middle">
                        <thead>
                            <tr>
                                <th>Visitor Details</th>
                                <th>Mobile Number</th>
                                <th>Vehicle No</th>
                                <th>Visiting Flat</th>
                                <th>Entry/Exit Time</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <span class="fw-bold d-block">Amit Verma</span>
                                    <span class="cat-badge cat-guest">Guest</span>
                                </td>
                                <td><span class="fw-bold"><i class="fa fa-phone small me-1"></i> +91 98765 43210</span></td>
                                <td><span class="vehicle-tag">CH-01-AX-4432</span></td>
                                <td class="fw-bold">B-402</td>
                                <td>
                                    <small class="text-muted d-block">In: 10:30 AM</small>
                                    <small class="text-muted d-block">Out: --:--</small>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-danger fw-bold">Check Out</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

    </div>



    <!-- <div class="container-fluid px-4">
    <h1 class="mt-4">Visitors</h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Visitors</li>
    </ol>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between">
            <h5 class="card-title mb-0">Visitors List</h5>
            <a href="add/add_visitor.php" class="btn btn-success btn-sm">+ Add Visitor</a>
        </div>

        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped" id="visitor-table">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>Vehicle</th>
                        <th>Flat</th>
                        <th>Visit Type</th>
                        <th>Purpose</th>
                        <th>IN Time</th>
                        <th>OUT Time</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($visitors as $v): ?>
                        <tr>
                            <td><?= $v['id'] ?></td>
                            <td><?= htmlspecialchars($v['visitor_name']) ?></td>
                            <td><?= htmlspecialchars($v['mobile']) ?></td>
                            <td><?= htmlspecialchars($v['vehicle_no']) ?></td>

                            <td>
                                <?php if ($v['flat_number']): ?>
                                    Block <?= $v['block_number'] ?> - Flat <?= $v['flat_number'] ?>
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </td>


                            <td><?= htmlspecialchars($v['visit_type']) ?></td>
                            <td>
                                <?php
                                if ($v['visit_type'] == 'Other') {
                                    echo htmlspecialchars($v['purpose'] ?? '');
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>


                            <td><?= $v['in_time_fmt'] ?></td>

                            <td>
                                <?php if ($v['out_time']): ?>
                                    <span class="text-success"><?= $v['out_time_fmt'] ?></span>
                                <?php else: ?>
                                    <span class="badge bg-warning">Inside</span>
                                <?php endif; ?>
                            </td>


                            <td>
                                <div class="action-btns">
                                    <?php if (!$v['out_time']): ?>
                                        <a href="visitors.php?action=out&id=<?= $v['id'] ?>"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Mark visitor OUT?')">
                                            OUT
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-success">Exited</span>
                                    <?php endif; ?>
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
            $('#visitor-table').DataTable();
        });
    </script>

    <?php include '../resources/layout/footer.php'; ?>
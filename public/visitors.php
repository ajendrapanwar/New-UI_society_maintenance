<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

requireRole(['admin']);

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
            </div>

            <div class="data-card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover datatable w-100 align-middle" id="visitor-table">
                        <thead>
                            <tr>
                                <th>Name/Visit Type</th>
                                <th>Mobile</th>
                                <th>Vehicle</th>
                                <th>Image</th>
                                <th>IN Time</th>
                                <th>OUT Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($visitors)): ?>
                                <?php foreach ($visitors as $v): ?>
                                    <tr>
                                        <!-- NAME + FLATE + TYPE -->
                                        <td style="padding:12px 8px; line-height:1.8;">

                                            <!-- Visitor Name -->
                                            <span class="fw-bold d-block" style="margin:2px 0;">
                                                <?= htmlspecialchars($v['visitor_name']) ?>
                                            </span>

                                            <!-- Flat Number -->
                                            <small class="text-muted d-block" style="margin-left: 2px;">
                                                <?php if (!empty($v['flat_number'])): ?>
                                                    <?= htmlspecialchars($v['block_number']) ?>-<?= htmlspecialchars($v['flat_number']) ?>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </small>

                                            <!-- Visit Type / Purpose -->
                                            <?php if (!empty($v['visit_type'])): ?>

                                                <?php if ($v['visit_type'] === 'Other' && !empty($v['purpose'])): ?>

                                                    <!-- Show ONLY Purpose -->
                                                    <span class="cat-badge cat-guest">
                                                        <?= htmlspecialchars($v['purpose']) ?>
                                                    </span>

                                                <?php else: ?>

                                                    <!-- Normal Visit Type -->
                                                    <span class="cat-badge cat-guest">
                                                        <?= htmlspecialchars($v['visit_type']) ?>
                                                    </span>

                                                <?php endif; ?>

                                            <?php endif; ?>

                                        </td>

                                        <!-- MOBILE -->
                                        <td>
                                            <span class="fw-bold">
                                                <?= htmlspecialchars($v['mobile']) ?>
                                            </span>
                                        </td>

                                        <!-- VEHICLE -->
                                        <td>
                                            <?php if (!empty($v['vehicle_no'])): ?>
                                                <span class="vehicle-tag">
                                                    <?= htmlspecialchars($v['vehicle_no']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <div class="visitor-photo-link">
                                                <?php
                                                if (!empty($v['photo'])) {
                                                    echo '<a href="uploads/visitors/' . htmlspecialchars($v['photo']) . '" 
                                            class="text-primary fw-bold"
                                            style="text-decoration:none;font-size:14px;"
                                            target="_blank">
                                            <i class="fa-solid fa-image"></i> View File
                                        </a>';
                                                } else {
                                                    echo '<span class="text-muted small">--</span>';
                                                }
                                                ?>
                                            </div>
                                        </td>

                                        <!-- IN TIME -->
                                        <td>
                                            <small class="text-muted d-block">
                                                <?= $v['in_time_fmt'] ?>
                                            </small>
                                        </td>

                                        <!-- OUT TIME -->
                                        <td>
                                            <?php if ($v['out_time']): ?>
                                                <small class="text-success d-block">
                                                    <?= $v['out_time_fmt'] ?>
                                                </small>
                                            <?php else: ?>
                                                <span>--</span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?php if ($v['out_time']): ?>
                                                <span class="badge bg-success">Exited</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Inside</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">
                                        No visitor records found
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>



    <!-- DATATABLES -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.2/css/dataTables.bootstrap5.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.2/js/dataTables.bootstrap5.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Initialize DataTable
            $('#visitor-table').DataTable();

        });
    </script>

    <?php include '../resources/layout/footer.php'; ?>
<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

requireRole(['user']);

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

/* ================= MARK VISITOR OUT (ONLY OWN FLAT) ================= */
if (isset($_GET['action']) && $_GET['action'] == 'out' && isset($_GET['id'])) {

    $visitor_id = $_GET['id'];

    // Check if visitor belongs to logged-in user's flat
    $check = $pdo->prepare("
        SELECT v.id 
        FROM visitor_entries v
        JOIN allotments a ON v.flat_id = a.flat_id
        WHERE v.id = ? AND a.user_id = ?
    ");
    $check->execute([$visitor_id, $user_id]);

    if ($check->rowCount() > 0) {

        $stmt = $pdo->prepare("
            UPDATE visitor_entries 
            SET out_time = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$visitor_id]);

        flash_set('success', 'Visitor marked OUT successfully');
    } else {
        flash_set('err', 'Unauthorized action!');
    }

    header('Location: ' . BASE_URL . 'visitors.php');
    exit();
}


/* ================= FETCH ONLY LOGIN USER VISITORS ================= */

$stmt = $pdo->prepare("
    SELECT 
        v.*,
        f.flat_number, 
        f.block_number,
        DATE_FORMAT(v.in_time, '%d-%m-%Y %h:%i %p') AS in_time_fmt,
        DATE_FORMAT(v.out_time, '%d-%m-%Y %h:%i %p') AS out_time_fmt
    FROM visitor_entries v
    LEFT JOIN flats f ON v.flat_id = f.id
    INNER JOIN allotments a ON a.flat_id = f.id
    WHERE a.user_id = ?
    ORDER BY v.id DESC
");

$stmt->execute([$user_id]);
$visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../resources/layout/header.php';
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Logs</title>

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
                <h1 class="fw-800 m-0">Guest History</h1>
            </div>

            <div class="data-card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover datatable w-100 align-middle" id="visitor-table">
                        <thead>
                            <tr>
                                <th>Name/Visit Type</th>
                                <th>Mobile</th>
                                <th>Vehicle</th>
                                <th>Flat</th>
                                <th>Purpose</th>
                                <th>IN Time</th>
                                <th>OUT Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($visitors)): ?>
                                <?php foreach ($visitors as $v): ?>
                                    <tr>
                                        <!-- NAME + TYPE -->
                                        <td style="padding-bottom: 0.8rem" ;>
                                            <span class="fw-bold d-block" style=" margin: 6px auto; padding-left: 2px;">
                                                <?= htmlspecialchars($v['visitor_name']) ?>
                                            </span>

                                            <?php if (!empty($v['visit_type'])): ?>
                                                <span class="cat-badge cat-guest">
                                                    <?= htmlspecialchars($v['visit_type']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- MOBILE -->
                                        <td>
                                            <span class="fw-bold">
                                                <i class="fa fa-phone small me-1"></i>
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

                                        <!-- FLAT -->
                                        <td class="fw-bold">
                                            <?php if ($v['flat_number']): ?>
                                                <?= htmlspecialchars($v['block_number']) ?>-<?= htmlspecialchars($v['flat_number']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- PURPOSE -->
                                        <td>
                                            <?php if ($v['visit_type'] === 'Other'): ?>
                                                <?= htmlspecialchars($v['purpose'] ?? '-') ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
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
                                                <span class="badge bg-warning">Inside</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
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
            $('#visitor-table').DataTable({
                dom: '<"d-flex justify-content-between mb-4"lf>rt<"d-flex justify-content-between mt-4"ip>',
                processing: true,
                pageLength: 5,
                lengthMenu: [5, 10, 25, 50],

                language: {
                    search: "",
                    searchPlaceholder: "Search records..."
                },
            });

        });
    </script>

    <?php include '../resources/layout/footer.php'; ?>
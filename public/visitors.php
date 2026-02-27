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



/* ================= ADD VISITOR (CHECK-IN) ================= */

$errors = [];
$name = $mobile = $vehicle = $flat_id = $purpose = $visit_type = "";

/* FETCH FLATS FOR MODAL */
$flats = $pdo->query("
        SELECT f.id, f.flat_number, f.block_number
        FROM allotments a
        JOIN flats f ON a.flat_id = f.id
        ORDER BY f.block_number, f.flat_number
    ")->fetchAll(PDO::FETCH_ASSOC);


/* FORM SUBMIT */
if (isset($_POST['submit'])) {

    $name       = trim($_POST['visitor_name']);
    $mobile     = trim($_POST['mobile']);
    $vehicle    = strtoupper(trim($_POST['vehicle_no']));
    $flat_id    = $_POST['flat_id'] ?? '';
    $visit_type = $_POST['visit_type'] ?? '';
    $purpose    = trim($_POST['purpose'] ?? '');

    /* ========= VALIDATION ========= */

    if ($name == '') {
        $errors['visitor_name'] = "Enter visitor name";
    }

    if ($mobile == '') {
        $errors['mobile'] = "Enter mobile number";
    } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $errors['mobile'] = "Enter valid 10 digit mobile number";
    }

    if ($vehicle == '') {
        $errors['vehicle_no'] = "Enter vehicle number";
    } elseif (!preg_match('/^[A-Z0-9- ]{4,20}$/', $vehicle)) {
        $errors['vehicle_no'] = "Enter valid vehicle number";
    }

    if ($flat_id == '') {
        $errors['flat_id'] = "Select flat";
    }

    if ($visit_type == '') {
        $errors['visit_type'] = "Select visit type";
    }

    if ($visit_type === 'Other' && $purpose == '') {
        $errors['purpose'] = "Enter purpose";
    }

    /* ========= CHECK VEHICLE IN RESIDENT PARKING ========= */
    $checkVehicle = $pdo->prepare("
        SELECT id FROM resident_parking 
        WHERE vehicle1 = ? OR vehicle2 = ?
    ");
    $checkVehicle->execute([$vehicle, $vehicle]);

    if ($checkVehicle->rowCount() > 0) {
        $errors['vehicle_no'] = "This vehicle is already registered in resident parking";
    }

    /* ========= INSERT VISITOR ========= */
    if (empty($errors)) {

        $stmt = $pdo->prepare("
            INSERT INTO visitor_entries
            (visitor_name, mobile, vehicle_no, flat_id, visit_type, purpose)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        if ($stmt->execute([
            $name,
            $mobile,
            $vehicle,
            $flat_id,
            $visit_type,
            $purpose
        ])) {

            flash_set('success', 'Visitor Entry Added Successfully');
            header('Location: ' . BASE_URL . 'visitors.php');
            exit();
        } else {

            flash_set('err', 'Database error! Visitor not added.');
            header('Location: ' . BASE_URL . 'visitors.php');
            exit();
        }
    }
}




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
                <button class="btn btn-brand btn-sm shadow-sm px-3"
                    data-bs-toggle="modal"
                    data-bs-target="#addMiscModal">
                    <i class="fa-solid fa-plus me-1"></i>
                    <span class="d-none d-sm-inline">New Check-In</span>
                    <span class="d-inline d-sm-none">Check-In</span>
                </button>
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
                                <th>Action</th>
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

                                        <!-- ACTION -->
                                        <td class="text-end">
                                            <div class="action-btns">
                                                <?php if (!$v['out_time']): ?>
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-danger fw-bold checkout-btn"
                                                        data-id="<?= $v['id'] ?>"
                                                        data-name="<?= htmlspecialchars($v['visitor_name']) ?>"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#checkoutVisitorModal">
                                                        Check Out
                                                    </button>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Exited</span>
                                                <?php endif; ?>
                                            </div>
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



    <!-- ADD VISITOR MODAL -->
    <div class="modal fade" id="checkInModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius:20px; max-width:720px; margin:auto;">

                <div class="modal-header border-0 p-4">
                    <h5 class="modal-title fw-800">New Visitor Check-In</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4 pt-0">

                    <!-- IMPORTANT: autocomplete off + fake fields to block chrome autofill -->
                    <form method="POST" class="row g-3" autocomplete="off">

                        <!-- HIDDEN DUMMY FIELDS (Stops Chrome Autofill) -->
                        <input type="text" name="fake_username" style="display:none">
                        <input type="password" name="fake_password" style="display:none">

                        <!-- NAME -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">
                                VISITOR NAME <span class="text-danger">*</span>
                            </label>

                            <input type="text"
                                name="visitor_name"
                                id="visitor_name"
                                autocomplete="nope"
                                autocorrect="off"
                                autocapitalize="off"
                                spellcheck="false"
                                class="form-control bg-light border-0"
                                placeholder="Enter name"
                                value="<?= htmlspecialchars($name ?? '') ?>">

                            <small class="text-danger"><?= $errors['visitor_name'] ?? '' ?></small>
                        </div>

                        <!-- MOBILE -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">
                                MOBILE <span class="text-danger">*</span>
                            </label>

                            <input type="tel"
                                name="mobile"
                                autocomplete="new-password"
                                inputmode="numeric"
                                maxlength="10"
                                class="form-control bg-light border-0"
                                placeholder="10 digit number"
                                value="<?= htmlspecialchars($mobile ?? '') ?>">

                            <small class="text-danger"><?= $errors['mobile'] ?? '' ?></small>
                        </div>

                        <!-- VEHICLE -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">
                                VEHICLE NO <span class="text-danger">*</span>
                            </label>

                            <input type="text"
                                name="vehicle_no"
                                autocomplete="off"
                                autocapitalize="characters"
                                class="form-control bg-light border-0"
                                placeholder="e.g. MH12AB1234"
                                value="<?= htmlspecialchars($vehicle ?? '') ?>">

                            <small class="text-danger"><?= $errors['vehicle_no'] ?? '' ?></small>
                        </div>

                        <!-- FLAT -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">
                                VISITING FLAT <span class="text-danger">*</span>
                            </label>

                            <select name="flat_id" class="form-select bg-light border-0" autocomplete="off">
                                <option value="">Select Flat</option>
                                <?php foreach ($flats as $f): ?>
                                    <option value="<?= $f['id'] ?>"
                                        <?= ($flat_id ?? '') == $f['id'] ? 'selected' : '' ?>>
                                        Block <?= $f['block_number'] ?> - Flat <?= $f['flat_number'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <small class="text-danger"><?= $errors['flat_id'] ?? '' ?></small>
                        </div>

                        <!-- VISIT TYPE -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">
                                VISIT TYPE <span class="text-danger">*</span>
                            </label>

                            <select name="visit_type" id="visit_type"
                                class="form-select bg-light border-0" autocomplete="off">
                                <option value="">Select Type</option>
                                <option value="Guest" <?= ($visit_type ?? '') == 'Guest' ? 'selected' : '' ?>>Guest</option>
                                <option value="Delivery Boy" <?= ($visit_type ?? '') == 'Delivery Boy' ? 'selected' : '' ?>>Delivery Boy</option>
                                <option value="Electrician" <?= ($visit_type ?? '') == 'Electrician' ? 'selected' : '' ?>>Electrician</option>
                                <option value="Plumber" <?= ($visit_type ?? '') == 'Plumber' ? 'selected' : '' ?>>Plumber</option>
                                <option value="Other" <?= ($visit_type ?? '') == 'Other' ? 'selected' : '' ?>>Other</option>
                            </select>

                            <small class="text-danger"><?= $errors['visit_type'] ?? '' ?></small>
                        </div>

                        <!-- PURPOSE -->
                        <div class="col-12" id="purposeBox" style="display:none;">
                            <label class="form-label small fw-bold text-muted">
                                PURPOSE
                            </label>

                            <input type="text"
                                name="purpose"
                                autocomplete="off"
                                class="form-control bg-light border-0"
                                placeholder="Enter purpose"
                                value="<?= htmlspecialchars($purpose ?? '') ?>">

                            <small class="text-danger"><?= $errors['purpose'] ?? '' ?></small>
                        </div>

                        <!-- SUBMIT -->
                        <div class="col-12">
                            <button type="submit"
                                name="submit"
                                class="btn btn-brand w-100 py-3 mt-3">
                                Check-In Visitor
                            </button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>


    <!-- CHECK OUT Confirmation MODAL -->
    <div class="modal fade" id="checkoutVisitorModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius:18px;">

                <div class="modal-body text-center p-4">

                    <!-- Icon -->
                    <div style="
                    width:60px;
                    height:60px;
                    border-radius:50%;
                    background:#fff3cd;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    margin:0 auto 15px;
                    font-size:26px;">
                        🚪
                    </div>

                    <!-- Title -->
                    <h5 class="fw-bold mb-2 text-danger">Check Out Visitor</h5>

                    <!-- Message -->
                    <p class="text-muted mb-4">
                        Are you sure you want to mark
                        <span class="fw-bold" id="checkoutVisitorName">this visitor</span> as OUT?<br>
                        <small class="text-danger">This action will record exit time.</small>
                    </p>

                    <!-- Buttons -->
                    <div class="d-flex gap-3 justify-content-center">
                        <button type="button"
                            class="btn btn-light px-4 py-2"
                            data-bs-dismiss="modal"
                            style="border-radius:10px;">
                            Cancel
                        </button>

                        <a href="#"
                            id="confirmCheckoutBtn"
                            class="btn btn-danger px-4 py-2 fw-bold"
                            style="border-radius:10px;">
                            Yes, Check Out
                        </a>
                    </div>

                </div>
            </div>
        </div>
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

            // Handle Check Out Modal
            $(document).on('click', '.checkout-btn', function() {
                var visitorId = $(this).data('id');
                var visitorName = $(this).data('name');

                $('#checkoutVisitorName').text(visitorName);
                $('#confirmCheckoutBtn').attr(
                    'href',
                    'visitors.php?action=out&id=' + visitorId
                );
            });

            // Toggle Purpose Field
            const visitType = document.getElementById('visit_type');
            const purposeBox = document.getElementById('purposeBox');

            function togglePurpose() {
                if (visitType && visitType.value === 'Other') {
                    purposeBox.style.display = 'block';
                } else if (purposeBox) {
                    purposeBox.style.display = 'none';
                }
            }

            if (visitType) {
                visitType.addEventListener('change', togglePurpose);
                togglePurpose();
            }

            // Auto open modal if validation error exists
            <?php if (!empty($errors)): ?>
                var checkInModal = new bootstrap.Modal(document.getElementById('checkInModal'));
                checkInModal.show();
            <?php endif; ?>

        });
    </script>

    <?php include '../resources/layout/footer.php'; ?>
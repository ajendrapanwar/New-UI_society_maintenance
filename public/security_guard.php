<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

requireRole(['security_guard']);


/* PHPMailer */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../vendor/PHPMailer/SMTP.php';
require_once __DIR__ . '/../vendor/PHPMailer/Exception.php';

/* ================= SECURITY GUARD INFO ================= */
$guardName = $_SESSION['user_name']
    ?? $_SESSION['name']
    ?? 'Security Guard';
$initials = strtoupper(substr(trim($guardName), 0, 1));


/* ================= ADD VISITOR ================= */
$errors = [];
$name = $mobile = $vehicle = $flat_id = $purpose = $visit_type = "";
$photoName = null;

/* ================= FETCH FLATS ================= */
$flats = $pdo->query("
    SELECT f.id, f.flat_number, f.block_number
    FROM allotments a
    JOIN flats f ON a.flat_id = f.id
    ORDER BY f.block_number, f.flat_number
")->fetchAll(PDO::FETCH_ASSOC);



/* ================= FORM SUBMIT ================= */
if (isset($_POST['submit'])) {

    $name       = trim($_POST['visitor_name'] ?? '');
    $mobile     = trim($_POST['mobile'] ?? '');
    $vehicle    = strtoupper(trim($_POST['vehicle_no'] ?? ''));
    $flat_id    = $_POST['flat_id'] ?? '';
    $visit_type = $_POST['visit_type'] ?? '';
    $purpose    = trim($_POST['purpose'] ?? '');
    $entry_datetime = $_POST['entry_datetime'] ?? '';

    if ($visit_type !== 'Other') {
        $purpose = '--';
    }

    /* ========= VALIDATION ========= */

    if ($name == '') {
        $errors['visitor_name'] = "Enter visitor name";
    }

    if ($mobile == '') {
        $errors['mobile'] = "Enter mobile number";
    } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $errors['mobile'] = "Enter valid 10 digit mobile number";
    }

    if ($vehicle != '' && !preg_match('/^[A-Z0-9- ]{4,20}$/', $vehicle)) {
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

    if ($entry_datetime == '') {
        $errors['entry_datetime'] = "Select entry date and time";
    } else {
        $current_time = date('Y-m-d\TH:i');
        if ($entry_datetime > $current_time) {
            $errors['entry_datetime'] = "Entry time cannot be in the future";
        }
    }



    /* ========= CHECK VEHICLE IN RESIDENT PARKING ========= */

    if (!empty($vehicle)) {

        $checkVehicle = $pdo->prepare("
            SELECT id FROM resident_parking 
            WHERE vehicle1 = ? OR vehicle2 = ?
        ");

        $checkVehicle->execute([$vehicle, $vehicle]);

        if ($checkVehicle->rowCount() > 0) {
            $errors['vehicle_no'] = "This vehicle is already registered in resident parking";
        }
    }



    /* ========= PHOTO UPLOAD ========= */
    if (isset($_FILES['visitor_photo']) && $_FILES['visitor_photo']['error'] == 0) {

        $uploadDir = __DIR__ . "/uploads/visitors/";

        // create folder if not exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileTmp  = $_FILES['visitor_photo']['tmp_name'];
        $fileName = $_FILES['visitor_photo']['name'];

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($ext, $allowed)) {

            $photoName = "visitor_" . time() . "_" . rand(1000, 9999) . "." . $ext;

            $targetPath = $uploadDir . $photoName;

            if (move_uploaded_file($fileTmp, $targetPath)) {

                // success
            } else {
                $errors['visitor_photo'] = "Image upload failed (permission issue)";
            }
        } else {
            $errors['visitor_photo'] = "Only JPG, PNG, WEBP allowed";
        }
    }



    /* ========= INSERT VISITOR ========= */

    if (empty($errors)) {

        $stmt = $pdo->prepare("
        INSERT INTO visitor_entries
        (visitor_name, mobile, vehicle_no, photo, flat_id, visit_type, purpose, in_time)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

        if ($stmt->execute([
            $name,
            $mobile,
            $vehicle,
            $photoName,
            $flat_id,
            $visit_type,
            $purpose,
            $entry_datetime ?: date('Y-m-d H:i:s')
        ])) {
            flash_set('success', 'Visitor Entry Added Successfully');
            header('Location: ' . BASE_URL . 'security_guard.php');
            exit();
        } else {

            flash_set('err', 'Database error! Visitor not added.');

            header('Location: ' . BASE_URL . 'security_guard.php');
            exit();
        }
    }
}



/* ================= FETCH  EXITED VISITOR ONLY  ================= */
$stmt = $pdo->query("
    SELECT 
        v.*,
        f.flat_number, 
        f.block_number,
        DATE_FORMAT(v.in_time, '%d-%m-%Y %h:%i %p') AS in_time_fmt,
        DATE_FORMAT(v.out_time, '%d-%m-%Y %h:%i %p') AS out_time_fmt
        FROM visitor_entries v
        LEFT JOIN flats f ON v.flat_id = f.id
        WHERE v.out_time IS NOT NULL
        ORDER BY v.out_time DESC
    ");
$exit_visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* ================= FETCH VISITORS STILL INSIDE ================= */
$stmt = $pdo->query("
    SELECT 
        v.*,
        f.flat_number,
        f.block_number,
        DATE_FORMAT(v.in_time,'%d-%m-%Y %h:%i %p') AS in_time_fmt,
        DATE_FORMAT(v.out_time,'%d-%m-%Y %h:%i %p') AS out_time_fmt
    FROM visitor_entries v
    LEFT JOIN flats f ON v.flat_id = f.id
    WHERE v.out_time IS NULL
    ORDER BY v.in_time DESC
");
$visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* ================= VEHICLE SEARCH AJAX ================= */
if (isset($_POST['vehicle_search'])) {

    $search = trim($_POST['vehicle_search']);
    $like = "%$search%";

    $stmt = $pdo->prepare("

    SELECT 
    'Visitor' AS type,
    v.visitor_name AS name,
    v.mobile,
    v.vehicle_no AS vehicle,
    v.visit_type,
    f.block_number,
    f.flat_number
    FROM visitor_entries v
    LEFT JOIN flats f ON v.flat_id = f.id
    WHERE v.vehicle_no LIKE ?
    AND v.out_time IS NULL

    UNION ALL

    SELECT 
    'Resident' AS type,
    u.name,
    rp.mobile,
    rp.vehicle1,
    'Resident Parking',
    f.block_number,
    f.flat_number
    FROM resident_parking rp
    JOIN flats f ON rp.flat_id = f.id
    LEFT JOIN users u ON rp.user_id = u.id
    WHERE rp.vehicle1 LIKE ?

    UNION ALL

    SELECT 
    'Resident' AS type,
    u.name,
    rp.mobile,
    rp.vehicle2,
    'Resident Parking',
    f.block_number,
    f.flat_number
    FROM resident_parking rp
    JOIN flats f ON rp.flat_id = f.id
    LEFT JOIN users u ON rp.user_id = u.id
    WHERE rp.vehicle2 LIKE ?

    UNION ALL

    SELECT 
    'Tenant',
    t.tenant_name,
    t.mobile_no,
    t.vehicle_no,
    'Tenant Parking',
    f.block_number,
    f.flat_number
    FROM tenants t
    JOIN flats f ON t.flat_id = f.id
    WHERE t.vehicle_no LIKE ?
    AND t.status='active'

    ");

    $stmt->execute([$like, $like, $like, $like]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($results) {

        foreach ($results as $r) {

            echo '
                    <div class="vehicle-card shadow-sm mb-3">

                        <div class="d-flex align-items-center justify-content-between">

                            <div class="d-flex align-items-center gap-3">

                                <div class="vehicle-icon">
                                    <i class="fa-solid fa-car"></i>
                                </div>

                                <div>

                                    <div class="vehicle-number">' . $r['vehicle'] . '</div>

                                    <div class="vehicle-owner">' . $r['name'] . '</div>

                                    <div class="vehicle-flat">
                                        Block ' . $r['block_number'] . ' - Flat ' . $r['flat_number'] . ' • ' . $r['visit_type'] . '
                                    </div>

                                    <div class="vehicle-mobile">
                                        <i class="fa fa-phone me-1"></i>' . $r['mobile'] . '
                                    </div>

                                </div>

                            </div>

                            <div>';

            if ($r['type'] == "Visitor") {
                echo '<span class="vehicle-badge badge-visitor">Visitor</span>';
            } elseif ($r['type'] == "Tenant") {
                echo '<span class="vehicle-badge badge-tenant">Tenant</span>';
            } else {
                echo '<span class="vehicle-badge badge-resident">Resident</span>';
            }

            echo '

                            </div>

                        </div>

                    </div>
                ';
        }
    } else {

        echo '<div class="alert alert-danger text-center">No Vehicle Found</div>';
    }

    exit;
}


/* ================= MARK VISITOR OUT ================= */
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'out') {

    $visitor_id = intval($_GET['id']);

    if ($visitor_id > 0) {

        $stmt = $pdo->prepare("
            UPDATE visitor_entries
            SET out_time = NOW()
            WHERE id = ? AND out_time IS NULL
        ");

        $stmt->execute([$visitor_id]);

        if ($stmt->rowCount()) {
            flash_set('success', 'Visitor marked OUT successfully');
        } else {
            flash_set('err', 'Visitor already checked out or not found');
        }
    }

    header('Location: ' . BASE_URL . 'security_guard.php');
    exit;
}


/* ================= EMERGENCY SOS EMAIL ================= */
if (isset($_POST['sos_alert'])) {

    $stmt = $pdo->prepare("
        SELECT email, name 
        FROM users 
        WHERE role IN ('admin','user','cashier')
        AND email IS NOT NULL
    ");
    $stmt->execute();
    $receivers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($receivers) {

        try {

            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'fakeclint01@gmail.com';
            $mail->Password   = 'xjpv lacb osnv cwmj';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('fakeclint01@gmail.com', 'Society Security System');

            foreach ($receivers as $r) {
                $mail->addAddress($r['email'], $r['name']);
            }

            $mail->isHTML(true);
            $mail->Subject = "EMERGENCY ALERT - Security Gate";

            $mail->Body = "
            <div style='font-family:Arial;background:#f3f4f6;padding:20px'>
                <div style='max-width:600px;margin:auto;background:#fff;padding:25px;border-radius:8px'>

                    <h2 style='color:#dc3545'>🚨 Emergency Alert Triggered</h2>

                    <p>Hello,</p>

                    <p>An <b>Emergency SOS</b> has been triggered from the security gate.</p>

                    <p><b>Guard:</b> {$guardName}</p>

                    <p><b>Time:</b> " . date("d M Y h:i A") . "</p>

                    <p style='color:#dc3545;font-weight:bold'>
                    Please take immediate action if required.
                    </p>

                </div>
            </div>
            ";

            $mail->send();
        } catch (Exception $e) {

            error_log("SOS Email Error: " . $mail->ErrorInfo);
        }
    }

    echo "sent";
    exit;
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Dashboard</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">

    <style>
        :root {
            --brand-color: #4F47E5;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            margin: 0;
        }

        .full-width-container {
            width: 100%;
            padding: 25px;
        }

        /* Action Cards */
        .action-card {
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 24px;
            padding: 40px 20px;
            text-align: center;
            transition: 0.3s;
            cursor: pointer;
            height: 100%;
        }

        .action-card:hover {
            border-color: var(--brand-color);
            transform: translateY(-5px);
        }

        .action-card i {
            font-size: 3rem;
            color: var(--brand-color);
            margin-bottom: 20px;
            display: block;
        }

        .action-card h5 {
            font-weight: 800;
            font-size: 1.25rem;
        }

        .emergency-card {
            background: #fff1f2;
            border: 2px solid #fecdd3;
            color: #e11d48;
        }

        .emergency-card i {
            color: #e11d48;
        }

        /* Table & Badges */
        .gate-log-card {
            border-radius: 24px;
            background: white;
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.04);
            padding: 30px;
        }

        .badge-gate {
            padding: 6px 12px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
        }

        .status-inside {
            background: #dcfce7;
            color: #166534;
        }

        .status-exited {
            background: #f1f5f9;
            color: #64748b;
        }

        .vehicle-plate-sm {
            font-family: monospace;
            background: #f1f5f9;
            padding: 4px 10px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            font-weight: 800;
            color: #1e293b;
            letter-spacing: 1px;
        }

        .camera-box {
            width: 100%;
            height: 250px;
            background: #f8fafc;
            border: 2px dashed #cbd5e1;
            border-radius: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .time-text {
            font-weight: 700;
            color: var(--brand-color);
            display: block;
        }

        .date-text {
            font-size: 0.75rem;
            color: #64748b;
        }

        .btn-brand {
            background-color: var(--brand-color);
            color: white;
            border-radius: 10px;
            font-weight: 600;
            padding: 0.6rem 1.5rem;
            border: none;
            transition: 0.2s;
        }

        .btn-brand:hover {
            background-color: var(--brand-hover);
            color: white;
        }


        #clearBtn {
            background: #ffffff;
            border-radius: 10px;
            border: 1px solid #dee2e6;
            font-weight: 600;
            padding: 6px 14px;
            transition: all 0.25s ease;
        }

        #clearBtn:hover {
            background: #212529;
            color: #fff;
            border-color: #212529;
        }

        .vehicle-card {
            border-radius: 18px;
            border: none;
            background: #ffffff;
            padding: 18px 20px;
            transition: all .25s ease;
        }

        .vehicle-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }

        .vehicle-number {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 1px;
            color: #212529;
        }

        .vehicle-owner {
            font-weight: 700;
            font-size: 15px;
        }

        .vehicle-flat {
            font-size: 13px;
            color: #6c757d;
        }

        .vehicle-mobile {
            font-size: 13px;
            color: #495057;
        }

        .vehicle-badge {
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
        }

        .badge-visitor {
            background: #fff4e6;
            color: #d9480f;
        }

        .badge-resident {
            background: #e7f5ff;
            color: #1864ab;
        }

        .badge-tenant {
            background: #e6fcf5;
            color: #087f5b;
        }

        .vehicle-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: #f1f3f5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .dropdown-item.text-danger:focus,
        .dropdown-item.text-danger:hover,
        .dropdown-item.text-danger:active {
            background-color: transparent !important;
            color: #dc3545 !important;
        }


        /* Emergency SOS Dropdown Item */
        .dropdown-item.sos-item {
            background-color: transparent !important;
            /* ensures default bg stays transparent */
            color: #dc3545 !important;
            /* text-danger red */
        }

        .dropdown-item.sos-item:hover,
        .dropdown-item.sos-item:focus {
            background-color: #f8d7da !important;
            /* light red on hover/focus */
            color: #dc3545 !important;
        }
    </style>

</head>

<body>

    <div class="full-width-container">
        <!-- Header -->
        <header class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h1 class="fw-800 m-0" style="color: var(--brand-color);">Security Command Center</h1>
                <p class="text-muted m-0"><i class="fa fa-shield-halved me-2"></i>Main Gate Post-01 | Real-time Operations</p>
            </div>
            <div class="d-flex align-items-center gap-4">

                <div class="text-end d-none d-md-block">
                    <p class="m-0 fw-bold fs-5"><?= htmlspecialchars($guardName) ?></p>
                    <p class="m-0 small text-success fw-bold">
                        <i class="fa fa-circle small me-1"></i> ONLINE
                    </p>
                </div>

                <div class="dropdown">

                    <div class="avatar-circle shadow-sm"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        style="width:50px;height:50px;background:var(--brand-color);color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;cursor:pointer;">
                        <?= $initials ?>
                    </div>

                    <ul class="dropdown-menu dropdown-menu-end user-dropdown shadow border-0 p-0 mt-2">
                        <!-- Emergency SOS -->
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2 text-danger sos-item"
                                href="javascript:void(0);"
                                onclick="openSOSModal()">
                                <div class="menu-icon bg-danger-soft me-3">
                                    <i class="fas fa-truck-medical text-danger"></i>
                                </div>
                                <span>Emergency SOS</span>
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2 text-danger" href="<?= BASE_URL ?>logout.php">
                                <div class="menu-icon bg-danger-soft me-3">
                                    <i class="fas fa-sign-out-alt text-danger"></i>
                                </div>
                                <span>Logout</span>
                            </a>
                        </li>
                    </ul>

                </div>

            </div>
        </header>

        <!-- Boxes -->
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="action-card shadow-sm" data-bs-toggle="modal" data-bs-target="#visitorEntryModal">
                    <i class="fa-solid fa-user-plus"></i>
                    <h5>Visitor Entry</h5>
                </div>
            </div>
            <div class="col-md-4">
                <div class="action-card shadow-sm" data-bs-toggle="modal" data-bs-target="#vehicleFinderModal">
                    <i class="fa-solid fa-car-rear"></i>
                    <h5>Find Vehicle</h5>
                </div>
            </div>
            <div class="col-md-4">
                <div class="action-card shadow-sm" data-bs-toggle="modal" data-bs-target="#visitorHistoryModal">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <h5>Visitor History</h5>
                </div>
            </div>
        </div>

        <!-- Live Traffic Log -->
        <div class="gate-log-card">
            <h4 class="fw-800 mb-4">Live Traffic Log</h4>
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="visitor-table">
                    <thead class="text-muted small uppercase">
                        <tr>
                            <th>Visitor Details</th>
                            <th>Mobile</th>
                            <th>Vehicle Number</th>
                            <th>Entry (Date/Time)</th>
                            <th>Exit (Date/Time)</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="visitorLog">
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

                                    <td><?= htmlspecialchars($v['mobile']) ?></td>

                                    <td>
                                        <?php if (!empty($v['vehicle_no'])): ?>
                                            <span class="vehicle-plate-sm">
                                                <?= htmlspecialchars($v['vehicle_no']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">N/A</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <span class="time-text"><?= $v['in_time_fmt'] ?></span>
                                    </td>

                                    <td>
                                        <?php if ($v['out_time']): ?>
                                            <span class="time-text text-success"><?= $v['out_time_fmt'] ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small">--</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if ($v['out_time']): ?>
                                            <span class="badge-gate status-exited">Exited</span>
                                        <?php else: ?>
                                            <span class="badge-gate status-inside">Inside</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-end">
                                        <?php if (!$v['out_time']): ?>
                                            <button
                                                class="checkout-btn"
                                                style="background-color: var(--brand-color);color: white;border-radius: 10px;font-weight: 600; padding: 0.5rem 1rem;border: none;transition: 0.2s;"
                                                data-id="<?= $v['id'] ?>"
                                                data-name="<?= htmlspecialchars($v['visitor_name']) ?>"
                                                data-bs-toggle="modal"
                                                data-bs-target="#checkoutVisitorModal">
                                                Mark Exit
                                            </button>
                                        <?php else: ?>
                                            <span class="badge bg-success">Exited</span>
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

    </div>


    <!-- Add visitor popup -->
    <div class="modal fade" id="visitorEntryModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 30px;">
                <div class="modal-header border-0 p-4 pb-0">
                    <h5 class="modal-title fw-800">New Entry Registration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form method="POST" class="row g-4" enctype="multipart/form-data">
                        <div class="col-md-5">
                            <label class="form-label small fw-bold text-muted">VISITOR PHOTO</label>

                            <!-- <div class="camera-box">
                                <input
                                    type="file"
                                    name="visitor_photo"
                                    id="visitorPhotoInput"
                                    accept="image/*"
                                    capture="environment"
                                    style="display:none;">

                                <div class="camera-box" id="cameraBox">
                                    <i class="fa-solid fa-camera fa-2x mb-2"></i>
                                    <small class="text-muted">Uplaod Photots</small>
                                </div>
                            </div> -->
                            <div class="camera-box" id="cameraBox">
                                <input
                                    type="file"
                                    name="visitor_photo"
                                    id="visitorPhotoInput"
                                    accept="image/*"
                                    capture="environment"
                                    style="display:none;">

                                <i class="fa-solid fa-camera fa-2x mb-2"></i>
                                <small class="text-muted">Upload Photo</small>
                            </div>

                        </div>
                        <div class="col-md-7">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-muted">VISITOR FULL NAME <span class="text-danger">*</span></label>
                                    <input type="text"
                                        name="visitor_name"
                                        class="form-control bg-light border-0 py-3"
                                        placeholder="Enter Full Name"
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
                                <div class="col-md-6" id="purposeBox" style="display:none;">
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

                                <div class="col-12">
                                    <label class="form-label small fw-bold text-muted">VEHICLE NUMBER (IF ANY)</label>
                                    <input type="text"
                                        name="vehicle_no"
                                        class="form-control bg-light border-0 py-3 fw-bold"
                                        placeholder="XX 00 XX 0000"
                                        style="letter-spacing:1px;font-family:monospace;"
                                        value="<?= htmlspecialchars($vehicle ?? '') ?>">
                                    <small class="text-danger"><?= $errors['vehicle_no'] ?? '' ?></small>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-muted">
                                        ENTRY DATE & TIME <span class="text-danger">*</span>
                                    </label>
                                    <input type="datetime-local"
                                        name="entry_datetime"
                                        class="form-control bg-light border-0 py-3"
                                        id="entryDateTime"
                                        value="<?= htmlspecialchars($_POST['entry_datetime'] ?? '') ?>">
                                    <small class="text-danger"><?= $errors['entry_datetime'] ?? '' ?></small>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 mt-4">
                            <button type="submit" name="submit" class="btn btn-brand w-100  shadow-lg fw-800 fs-5">
                                AUTHORIZE ENTRY
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


    <!-- Find Vehicle -->
    <div class="modal fade" id="vehicleFinderModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius:30px;">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Vehicle Finder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">


                    <div class="d-flex align-items-center gap-2" style="background:#f8f9fa;border-radius:16px;padding:8px 20px;box-shadow:0 4px 10px rgba(0,0,0,0.05);">
                        <i class="fa-solid fa-magnifying-glass text-muted" style="font-size:18px;margin-left:5px;"></i>

                        <input
                            type="text"
                            id="vehicleSearch"
                            class="form-control border-0"
                            placeholder="DL 3C AB 1234"
                            autocomplete="off"
                            style="background:transparent;font-weight:700;letter-spacing:2px;text-transform:uppercase;text-align:center;font-size:18px;padding:12px;box-shadow:none;outline:none;">

                        <button id="clearBtn" class="btn btn-sm" style="display:none;">Clear</button>
                    </div>

                    <div class="mt-2" id="vehicleResults"></div>

                </div>
            </div>
        </div>
    </div>


    <!-- Visitor History Modal -->
    <div class="modal fade" id="visitorHistoryModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        Visitor Entry History
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="table-responsive">
                        <table class="table table-hover datatable w-100 align-middle" id="visitor-history-table">

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

                                <?php if (!empty($exit_visitors)): ?>
                                    <?php foreach ($exit_visitors as $v): ?>
                                        <tr>

                                            <!-- NAME -->
                                            <td>
                                                <span class="fw-bold d-block">
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
                                                <?= htmlspecialchars($v['mobile']) ?>
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
                                                    N/A
                                                <?php endif; ?>
                                            </td>

                                            <!-- PURPOSE -->
                                            <td>
                                                <?= htmlspecialchars($v['purpose'] ?? '-') ?>
                                            </td>

                                            <!-- IN TIME -->
                                            <td>
                                                <small class="text-muted">
                                                    <?= $v['in_time_fmt'] ?>
                                                </small>
                                            </td>

                                            <!-- OUT TIME -->
                                            <td>
                                                <?php if ($v['out_time']): ?>
                                                    <small class="text-success">
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
                                        <td colspan="7" class="text-center text-muted py-4">
                                            No visitor records found
                                        </td>
                                    </tr>
                                <?php endif; ?>

                            </tbody>

                        </table>
                    </div>

                </div>

            </div>
        </div>
    </div>


    <!-- EMERGENCY SOS Confirmation MODAL -->
    <div class="modal fade" id="sosConfirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius:18px;">

                <div class="modal-body text-center p-4">

                    <!-- Icon -->
                    <div style="
                width:60px;
                height:60px;
                border-radius:50%;
                background:#ffe5e5;
                display:flex;
                align-items:center;
                justify-content:center;
                margin:0 auto 15px;
                font-size:26px;">
                        🚨
                    </div>

                    <!-- Title -->
                    <h5 class="fw-bold mb-2 text-danger">Emergency SOS</h5>

                    <!-- Message -->
                    <p class="text-muted mb-4">
                        Are you sure you want to trigger <b>Emergency Alert</b>?<br>
                        <small class="text-danger">
                            Email notification will be sent to Admin & Staff.
                        </small>
                    </p>

                    <!-- Buttons -->
                    <div class="d-flex gap-3 justify-content-center">

                        <button type="button"
                            class="btn btn-light px-4 py-2"
                            data-bs-dismiss="modal"
                            style="border-radius:10px;">
                            Cancel
                        </button>

                        <button type="button"
                            id="confirmSOSBtn"
                            class="btn btn-danger px-4 py-2 fw-bold"
                            style="border-radius:10px;">
                            Yes, Send Alert
                        </button>

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
        $('#visitor-history-table').DataTable({
            dom: '<"d-flex justify-content-between mb-4"lf>rt<"d-flex justify-content-between mt-4"ip>',
            pageLength: 5,
            lengthMenu: [5, 10, 25, 50],

            language: {
                search: "",
                searchPlaceholder: "Search records..."
            },
            order: [
                [5, "desc"]
            ]
        });


        $(document).ready(function() {
            $("#vehicleSearch").keyup(function() {
                let vehicle = $(this).val();

                if (vehicle.length === 0) {
                    $("#vehicleResults").html('');
                    $("#clearBtn").hide();
                    return;
                }

                $("#clearBtn").show();

                $.ajax({
                    url: "",
                    method: "POST",
                    data: {
                        vehicle_search: vehicle
                    },
                    success: function(data) {
                        $("#vehicleResults").html(data);
                    }
                });
            });

            $("#clearBtn").click(function() {
                $("#vehicleSearch").val('');
                $("#vehicleResults").html('');
                $(this).hide();
            });
        });

        // OPEN CHECKOUT MODAL
        $(document).on('click', '.checkout-btn', function() {

            var visitorId = $(this).data('id');
            var visitorName = $(this).data('name');

            $('#checkoutVisitorName').text(visitorName);

            $('#confirmCheckoutBtn').attr(
                'href',
                'security_guard.php?action=out&id=' + visitorId
            );
        });
    </script>

    <script>
        // const input = document.getElementById('visitorPhotoInput');
        // const cameraBox = document.getElementById('cameraBox');

        // cameraBox.addEventListener('click', () => {
        //     input.click();
        // });

        // input.addEventListener('change', function() {

        //     const file = this.files[0];

        //     if (file) {

        //         const reader = new FileReader();

        //         reader.onload = function(e) {

        //             cameraBox.innerHTML =
        //                 `<img src="${e.target.result}" 
        //         style="width:100%;height:100%;object-fit:cover;border-radius:18px;">`;

        //         };

        //         reader.readAsDataURL(file);
        //     }

        // });



        const input = document.getElementById('visitorPhotoInput');
        const cameraBox = document.getElementById('cameraBox');

        cameraBox.addEventListener('click', () => {
            input.click();
        });

        input.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    cameraBox.innerHTML = `
                <img src="${e.target.result}" 
                style="width:100%;height:100%;object-fit:cover;border-radius:18px;">
            `;
                };
                reader.readAsDataURL(file);
            }
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

        function openSOSModal() {
            var sosModal = new bootstrap.Modal(document.getElementById('sosConfirmModal'));
            sosModal.show();
        }

        document.getElementById("confirmSOSBtn").addEventListener("click", function() {

            fetch("security_guard.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: "sos_alert=1"
                })
                .then(res => res.text())
                .then(data => {

                    alert("Emergency Alert Sent!");


                    location.reload();

                });

        });
    </script>

    <?php if (!empty($errors)): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var visitorEntryModal = new bootstrap.Modal(document.getElementById('visitorEntryModal'));
                visitorEntryModal.show();
            });
        </script>
    <?php endif; ?>

    <?php include '../resources/layout/footer.php'; ?>
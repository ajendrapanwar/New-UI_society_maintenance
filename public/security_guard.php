<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

requireRole(['security_guard']);


/* ================= SECURITY GUARD INFO ================= */

$guardName = $_SESSION['user_name']
    ?? $_SESSION['name']
    ?? 'Security Guard';

$initials = strtoupper(substr(trim($guardName), 0, 1));

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
    </style>

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
                <div class="action-card emergency-card shadow-sm" onclick="triggerSOS()">
                    <i class="fa-solid fa-truck-medical"></i>
                    <h5>Emergency SOS</h5>
                </div>
            </div>
        </div>
        

        <div class="gate-log-card">
            <h4 class="fw-800 mb-4">Live Traffic Log</h4>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="text-muted small uppercase">
                        <tr>
                            <th>Visitor Details</th>
                            <th>Vehicle Number</th>
                            <th>Flat No.</th>
                            <th>Entry (Date/Time)</th>
                            <th>Exit (Date/Time)</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="visitorLog">
                        <tr>
                            <td>
                                <span class="fw-bold d-block">Rahul Singh</span>
                                <span class="badge bg-primary-subtle text-primary border-0 small">Zomato</span>
                            </td>
                            <td><span class="vehicle-plate-sm">DL 3C AB 1234</span></td>
                            <td><span class="fw-bold fs-6">B-402</span></td>
                            <td>
                                <span class="time-text">04:45 PM</span>
                                <span class="date-text">03 Mar 2026</span>
                            </td>
                            <td><span class="text-muted small">-- Inside --</span></td>
                            <td><span class="badge-gate status-inside">Inside</span></td>
                            <td class="text-end">
                                <button class="btn btn-brand px-4 fw-bold" onclick="markExit(this)">Mark Exit</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="visitorEntryModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 30px;">
                <div class="modal-header border-0 p-4 pb-0">
                    <h5 class="modal-title fw-800">New Entry Registration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form class="row g-4">
                        <div class="col-md-5">
                            <label class="form-label small fw-bold text-muted">VISITOR PHOTO</label>
                            <div class="camera-box" id="cameraView" onclick="takePhoto()">
                                <i class="fa-solid fa-camera mb-2 fs-1 text-muted"></i>
                                <span class="small fw-bold text-muted">TAP TO CAPTURE</span>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-muted">VISITOR FULL NAME</label>
                                    <input type="text" class="form-control bg-light border-0 py-3" placeholder="Enter Full Name" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold text-muted">FLAT NO.</label>
                                    <input type="text" class="form-control bg-light border-0 py-3" placeholder="e.g. C-101" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold text-muted">PERSON TYPE</label>
                                    <select class="form-select bg-light border-0 py-3">
                                        <option>Visitor</option>
                                        <option>Delivery Boy</option>
                                        <option>Zomato</option>
                                        <option>Swiggy</option>
                                        <option>Maintenance</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-muted">VEHICLE NUMBER (IF ANY)</label>
                                    <input type="text" class="form-control bg-light border-0 py-3 fw-bold" placeholder="XX 00 XX 0000" style="letter-spacing: 1px; font-family: monospace;">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-muted">ENTRY DATE & TIME</label>
                                    <input type="datetime-local" class="form-control bg-light border-0 py-3" id="entryDateTime">
                                </div>
                            </div>
                        </div>
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-brand w-100 py-3 shadow-lg fw-800 fs-5">AUTHORIZE ENTRY</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>




    <div class="modal fade" id="vehicleFinderModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 30px;">
                <div class="modal-body p-5 text-center">
                    <i class="fa-solid fa-car-magnifying-glass fs-1 mb-4" style="color: var(--brand-color);"></i>
                    <h3 class="fw-800 mb-4">Vehicle Lookup</h3>
                    <input type="text" class="form-control form-control-lg bg-light border-0 text-center fw-800 py-3 mb-4" placeholder="DL 3C AB 1234" style="letter-spacing: 2px; text-transform: uppercase;">
                    <button class="btn btn-brand w-100 py-3 mt-4 fw-bold">SEARCH DATABASE</button>
                </div>
            </div>
        </div>
    </div>




    <!-- DATATABLES -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.2/css/dataTables.bootstrap5.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.2/js/dataTables.bootstrap5.min.js"></script>

    <!-- <script>
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
    </script> -->

    <script>
        window.onload = function() {
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            document.getElementById('entryDateTime').value = now.toISOString().slice(0, 16);
        };

        function takePhoto() {
            const box = document.getElementById('cameraView');
            box.innerHTML = '<i class="fa-solid fa-spinner fa-spin fs-1 text-primary"></i>';
            setTimeout(() => {
                box.innerHTML = '<i class="fa-solid fa-user-check fs-1 text-success mb-2"></i><span class="small fw-bold text-success">PHOTO CAPTURED</span>';
                box.style.borderColor = '#10b981';
                box.style.background = '#f0fdf4';
            }, 800);
        }

        function markExit(btn) {
            if (confirm("Confirm Visitor Departure?")) {
                const now = new Date();
                const time = now.toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                const date = now.toLocaleDateString([], {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                });
                const row = btn.closest('tr');
                row.querySelector('.status-inside').className = 'badge-gate status-exited';
                row.querySelector('.status-exited').innerText = 'Exited';
                row.cells[4].innerHTML = `<span class="time-text text-danger">${time}</span><span class="date-text">${date}</span>`;
                btn.disabled = true;
                btn.innerText = "OUT";
            }
        }

        function triggerSOS() {
            alert("EMERGENCY: SOS Alert broadcasted to Admin and Residents!");
        }
    </script>


    <?php include '../resources/layout/footer.php'; ?>
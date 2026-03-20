<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

requireRole(['admin']);

$errors = [];
$flat_id = $user_id = $name = $mobile = $vehicle1 = $vehicle2 = '';
$vehicle_count = 1;

/* ================= FETCH AVAILABLE FLATS (FOR ADD MODAL) ================= */
$flats = $pdo->query("
    SELECT f.id, f.flat_number, f.block_number, u.name, u.mobile, a.user_id
    FROM allotments a
    JOIN flats f ON a.flat_id = f.id
    JOIN users u ON a.user_id = u.id
    LEFT JOIN resident_parking rp ON rp.flat_id = f.id
    WHERE rp.flat_id IS NULL
    ORDER BY f.block_number, f.flat_number
")->fetchAll(PDO::FETCH_ASSOC);


/* ================= ADD PARKING ================= */
if (isset($_POST['add_parking'])) {

    $flat_id = $_POST['flat_id'] ?? '';
    $user_id = $_POST['user_id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $vehicle_count = $_POST['vehicle_count'] ?? 1;
    $vehicle1 = trim($_POST['vehicle1'] ?? '');
    $vehicle2 = trim($_POST['vehicle2'] ?? '');

    // VALIDATION
    if (!$flat_id) $errors['flat'] = "Select Flat No";
    if (!preg_match('/^[0-9]{10}$/', $mobile)) $errors['mobile'] = "Enter valid 10 digit mobile";

    if ($vehicle_count == 1 && $vehicle1 == '') {
        $errors['vehicle1'] = "Enter Vehicle No";
    }

    if ($vehicle_count == 2) {
        if ($vehicle1 == '') $errors['vehicle1'] = "Enter Vehicle 1 No";
        if ($vehicle2 == '') $errors['vehicle2'] = "Enter Vehicle 2 No";
    }

    // DUPLICATE CHECK
    if (empty($errors)) {
        $check = $pdo->prepare("SELECT id FROM resident_parking WHERE flat_id=?");
        $check->execute([$flat_id]);
        if ($check->fetch()) {
            $errors['flat'] = "This flat already has parking";
        }
    }

    // INSERT
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO resident_parking 
            (flat_id,user_id,name,mobile,vehicle_count,vehicle1,vehicle2,created_at)
            VALUES(?,?,?,?,?,?,?,NOW())
        ");

        if ($stmt->execute([$flat_id, $user_id, $name, $mobile, $vehicle_count, $vehicle1, $vehicle2])) {
            flash_set('success', 'Resident Parking Added Successfully');
            header("Location: resident_parking.php");
            exit();
        }
    }
}


/* ================= EDIT PARKING ================= */
if (isset($_POST['edit_parking'])) {

    $id = $_POST['id'];
    $vehicle_count = $_POST['vehicle_count'];
    $vehicle1 = trim($_POST['vehicle1']);
    $vehicle2 = trim($_POST['vehicle2']);

    if ($vehicle_count == 1 && $vehicle1 == '') {
        $errors['vehicle1'] = "Enter Vehicle No";
    }

    if ($vehicle_count == 2) {
        if ($vehicle1 == '') $errors['vehicle1'] = "Enter Vehicle 1 No";
        if ($vehicle2 == '') $errors['vehicle2'] = "Enter Vehicle 2 No";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE resident_parking 
            SET vehicle_count=?, vehicle1=?, vehicle2=? 
            WHERE id=?
        ");

        if ($stmt->execute([$vehicle_count, $vehicle1, $vehicle2, $id])) {
            flash_set('success', 'Parking Updated Successfully');
            header("Location: resident_parking.php");
            exit();
        }
    }
}


/* ================= DELETE ================= */
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] == 'delete') {
    $stmt = $pdo->prepare("DELETE FROM resident_parking WHERE id=?");
    $stmt->execute([$_GET['id']]);

    flash_set('success', 'Resident parking deleted successfully');
    header("Location: resident_parking.php");
    exit();
}


/* ================= AJAX FETCH (DATATABLE) ================= */
if (isset($_POST['action']) && $_POST['action'] === 'fetch_parking') {

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

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["data" => $data]);
    exit();
}

include '../resources/layout/header.php';
?>



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
                <div class="table-responsive">
                    <table class="table table-hover w-100" id="parking-table">
                        <thead>
                            <tr>
                                <th>Flat No</th>
                                <th>Block</th>
                                <th>Resident Name</th>
                                <th>Mobile</th>
                                <th>No of Vehicles</th>
                                <th>Vehicle 1</th>
                                <th>Vehicle 2</th>
                                <th>Created Date</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </main>

    </div>




    <!-- ================= ADD PARKING MODAL ================= -->
    <div class="modal fade" id="addParkingModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius:20px;">

                <div class="modal-header border-0 p-4">
                    <h5 class="modal-title fw-800">Assign Parking Slot</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4 pt-0">

                    <form method="POST" class="row g-3">

                        <!-- FLAT SELECT -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">
                                FLAT <span class="text-danger">*</span>
                            </label>
                            <select name="flat_id" id="flatSelect"
                                class="form-select bg-light border-0 <?= isset($errors['flat']) ? 'is-invalid' : '' ?>">
                                <option value="">Select Flat</option>
                                <?php foreach ($flats as $f): ?>
                                    <option value="<?= $f['id'] ?>"
                                        data-name="<?= htmlspecialchars($f['name']) ?>"
                                        data-mobile="<?= htmlspecialchars($f['mobile']) ?>"
                                        data-user="<?= $f['user_id'] ?>"
                                        <?= ($flat_id == $f['id']) ? 'selected' : '' ?>>
                                        <?= $f['block_number'] ?> - <?= $f['flat_number'] ?> (<?= htmlspecialchars($f['name']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-danger"><?= $errors['flat'] ?? '' ?></small>
                        </div>

                        <input type="hidden" name="user_id" id="user_id">

                        <!-- NAME -->
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted">Resident Name</label>
                            <input type="text" name="name" id="residentName" class="form-control bg-light border-0" readonly>
                        </div>

                        <!-- MOBILE -->
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted">Mobile</label>
                            <input type="text" name="mobile" id="residentMobile"
                                class="form-control bg-light border-0 <?= isset($errors['mobile']) ? 'is-invalid' : '' ?>"
                                value="<?= htmlspecialchars($mobile) ?>" readonly>
                            <small class="text-danger"><?= $errors['mobile'] ?? '' ?></small>
                        </div>

                        <!-- VEHICLE COUNT -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">Number of Vehicles</label>
                            <select name="vehicle_count" id="vehicleCount" class="form-select bg-light border-0">
                                <option value="1">1 Vehicle</option>
                                <option value="2">2 Vehicles</option>
                            </select>
                        </div>

                        <!-- VEHICLE 1 -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">Vehicle 1 Number</label>
                            <input type="text" name="vehicle1"
                                class="form-control bg-light border-0 <?= isset($errors['vehicle1']) ? 'is-invalid' : '' ?>"
                                value="<?= htmlspecialchars($vehicle1) ?>"
                                placeholder="e.g. MH12AB1234">
                            <small class="text-danger"><?= $errors['vehicle1'] ?? '' ?></small>
                        </div>

                        <!-- VEHICLE 2 -->
                        <div class="col-12" id="vehicle2Field" style="display:none;">
                            <label class="form-label small fw-bold text-muted">Vehicle 2 Number</label>
                            <input type="text" name="vehicle2"
                                class="form-control bg-light border-0 <?= isset($errors['vehicle2']) ? 'is-invalid' : '' ?>"
                                value="<?= htmlspecialchars($vehicle2) ?>"
                                placeholder="Optional">
                            <small class="text-danger"><?= $errors['vehicle2'] ?? '' ?></small>
                        </div>

                        <div class="col-12">
                            <button type="submit" name="add_parking" class="btn btn-brand w-100 py-3 mt-3">
                                Save Parking
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= EDIT MODAL ================= -->
    <div class="modal fade" id="editParkingModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius:20px;">

                <div class="modal-header border-0 p-4">
                    <h5 class="modal-title fw-800">Edit Parking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4 pt-0">
                    <form method="POST" class="row g-3">

                        <input type="hidden" name="id" id="edit_id">

                        <!-- VEHICLE COUNT -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">Number of Vehicles</label>
                            <select name="vehicle_count" id="edit_vehicle_count"
                                class="form-select bg-light border-0 <?= isset($errors['vehicle_count']) ? 'is-invalid' : '' ?>">
                                <option value="1">1 Vehicle</option>
                                <option value="2">2 Vehicles</option>
                            </select>
                        </div>

                        <!-- VEHICLE 1 -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">
                                Vehicle 1 <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                name="vehicle1"
                                id="edit_vehicle1"
                                value="<?= htmlspecialchars($vehicle1 ?? '') ?>"
                                class="form-control bg-light border-0 <?= isset($errors['vehicle1']) ? 'is-invalid' : '' ?>">
                            <small class="text-danger"><?= $errors['vehicle1'] ?? '' ?></small>
                        </div>

                        <!-- VEHICLE 2 -->
                        <div class="col-12" id="edit_vehicle2Field">
                            <label class="form-label small fw-bold text-muted">
                                Vehicle 2
                            </label>
                            <input type="text"
                                name="vehicle2"
                                id="edit_vehicle2"
                                value="<?= htmlspecialchars($vehicle2 ?? '') ?>"
                                class="form-control bg-light border-0 <?= isset($errors['vehicle2']) ? 'is-invalid' : '' ?>">
                            <small class="text-danger"><?= $errors['vehicle2'] ?? '' ?></small>
                        </div>

                        <div class="col-12">
                            <button type="submit" name="edit_parking" class="btn btn-brand w-100 py-3 mt-3">
                                Update Parking
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- DELETE PARKING MODAL -->
    <div class="modal fade" id="deleteParkingModal" tabindex="-1">
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
                        🚗
                    </div>

                    <!-- Title -->
                    <h5 class="fw-bold mb-2 text-danger">Delete Parking Record</h5>

                    <!-- Message -->
                    <p class="text-muted mb-4">
                        Are you sure you want to delete this parking record?<br>
                        <small class="text-danger">This action cannot be undone.</small>
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
                            id="confirmDeleteBtn"
                            class="btn btn-danger px-4 py-2 fw-bold"
                            style="border-radius:10px;">
                            Yes, Delete
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>



    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {

            /* ================= DATATABLE ================= */
            $('#parking-table').DataTable({
				dom: '<"d-flex justify-content-between mb-4"lf>rt<"d-flex justify-content-between mt-4"ip>',
                processing: true,
                pageLength: 5,
                lengthMenu: [5, 10, 25, 50],
                language: {
                    search: "",
                    searchPlaceholder: "Search records..."
                },
                ajax: {
                    url: 'resident_parking.php',
                    type: 'POST',
                    data: {
                        action: 'fetch_parking'
                    }
                },
                columns: [{
                        data: "flat_number",
                        render: d => `<span class="fw-bold">${d}</span>`
                    },
                    {
                        data: "block_number",
                        render: d => `Block ${d}`
                    },
                    {
                        data: "user_name"
                    },
                    {
                        data: "mobile"
                    },
                    {
                        data: "vehicle_count"
                    },
                    {
                        data: "vehicle1"
                    },
                    {
                        data: "vehicle2",
                        render: d => d ? d : "-"
                    },
                    {
                        data: "created_at",
                        render: function(d) {
                            if (!d) return "-";
                            let date = new Date(d);
                            return date.toLocaleDateString('en-GB', {
                                day: '2-digit',
                                month: 'short',
                                year: 'numeric'
                            });
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        className: "text-end",
                        render: function(data) {
                            return `
                <div class="action-btns">
                    <button class="btn btn-sm btn-light border editBtn"
                        data-id="${data.id}"
                        data-count="${data.vehicle_count}"
                        data-v1="${data.vehicle1}"
                        data-v2="${data.vehicle2}">
                        <i class="fa fa-pen"></i>
                    </button>

                    <button class="btn btn-sm btn-light border text-danger deleteBtn"
                            data-id="${data.id}" title="Delete">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>`;
                        }
                    }
                ]
            });

            /* AUTO FILL NAME & MOBILE */
            $('#flatSelect').change(function() {
                let selected = $(this).find(':selected');
                $('#residentName').val(selected.data('name') || '');
                $('#residentMobile').val(selected.data('mobile') || '');
                $('#user_id').val(selected.data('user') || '');
            });

            /* VEHICLE FIELD TOGGLE (ADD) */
            $('#vehicleCount').change(function() {
                if ($(this).val() == '2') {
                    $('#vehicle2Field').show();
                } else {
                    $('#vehicle2Field').hide();
                }
            });

            /* EDIT MODAL OPEN */
            $(document).on('click', '.editBtn', function() {
                $('#edit_id').val($(this).data('id'));
                $('#edit_vehicle_count').val($(this).data('count'));
                $('#edit_vehicle1').val($(this).data('v1'));
                $('#edit_vehicle2').val($(this).data('v2'));

                if ($(this).data('count') == 2) {
                    $('#edit_vehicle2Field').show();
                } else {
                    $('#edit_vehicle2Field').hide();
                }

                new bootstrap.Modal(document.getElementById('editParkingModal')).show();
            });

            /* VEHICLE FIELD TOGGLE (EDIT) */
            $('#edit_vehicle_count').change(function() {
                if ($(this).val() == '2') {
                    $('#edit_vehicle2Field').show();
                } else {
                    $('#edit_vehicle2Field').hide();
                }
            });


            /* ============ MODERN DELETE ============ */
            let deleteParkingId = null;

            // Open modern delete modal
            $(document).on('click', '.deleteBtn', function() {
                deleteParkingId = $(this).data('id');
                new bootstrap.Modal(document.getElementById('deleteParkingModal')).show();
            });

            // Confirm delete
            $('#confirmDeleteBtn').on('click', function() {

                if (!deleteParkingId) return;

                // Disable button + loading text
                $(this).prop('disabled', true).text('Deleting...');

                // Redirect to same file (your existing PHP delete logic)
                window.location.href =
                    '<?= BASE_URL ?>resident_parking.php?action=delete&id=' + deleteParkingId;
            });

        });
    </script>

    <?php if (!empty($errors) && isset($_POST['add_parking'])): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var addModal = new bootstrap.Modal(document.getElementById('addParkingModal'));
                addModal.show();
            });
        </script>
    <?php endif; ?>

    <?php if (!empty($errors) && isset($_POST['edit_parking'])): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var editModal = new bootstrap.Modal(document.getElementById('editParkingModal'));
                editModal.show();
            });
        </script>
    <?php endif; ?>

</body>

</html>

<?php include '../resources/layout/footer.php'; ?>
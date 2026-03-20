<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

// Everyone can view page, but only admin can do actions
$userRole = $_SESSION['user_role'] ?? '';



/* ================= ADD GARBAGE COLLECTOR (MODAL) ================= */
$errors = [];
$name = $mobile = $gender = $shift = $joiningDate = $address = $dob = '';
$salary = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_collector']) && $userRole === 'admin') {

    $name        = trim($_POST['name'] ?? '');
    $mobile      = trim($_POST['mobile'] ?? '');
    $dob         = $_POST['user_dob'] ?? '';
    $gender      = $_POST['gender'] ?? '';
    $shift       = $_POST['shift'] ?? '';
    $joiningDate = $_POST['joining_date'] ?? '';
    $address     = trim($_POST['address'] ?? '');
    $salary      = trim($_POST['salary'] ?? '');

    /* ===== VALIDATION ===== */
    if ($name === '') {
        $errors['name'] = 'Please enter collector name';
    }

    if ($mobile === '') {
        $errors['mobile'] = 'Please enter mobile number';
    } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $errors['mobile'] = 'Mobile must be 10 digits';
    }

    if ($dob === '' || strtotime($dob) >= strtotime(date('Y-m-d'))) {
        $errors['dob'] = 'Please select a valid DOB';
    }

    if (!in_array($gender, ['Male', 'Female', 'Other'], true)) {
        $errors['gender'] = 'Please select gender';
    }

    if (!in_array($shift, ['day', 'evening'], true)) {
        $errors['shift'] = 'Please select shift';
    }

    if ($joiningDate === '') {
        $errors['joining_date'] = 'Please select joining date';
    }

    if ($address === '') {
        $errors['address'] = 'Please enter address';
    }

    if ($salary === '' || !is_numeric($salary) || $salary < 0) {
        $errors['salary'] = 'Salary must be a valid number';
    }

    /* ===== DUPLICATE MOBILE CHECK ===== */
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM garbage_collectors WHERE mobile = ?");
        $stmt->execute([$mobile]);
        if ($stmt->fetch()) {
            $errors['mobile'] = 'Mobile already exists';
        }
    }

    /* ===== INSERT ===== */
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO garbage_collectors
            (name, mobile, dob, gender, shift, joining_date, address, salary, created_at)
            VALUES (?,?,?,?,?,?,?,?,NOW())
        ");

        if ($stmt->execute([$name, $mobile, $dob, $gender, $shift, $joiningDate, $address, $salary])) {
            flash_set('success', 'Garbage collector added successfully');
            header('Location: ' . BASE_URL . 'garbageCollector.php');
            exit;
        } else {
            $errors['db'] = 'Database error! Garbage collector not added.';
        }
    }
}


/* ================= UPDATE GARBAGE COLLECTOR (MODAL) ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_collector']) && $userRole === 'admin') {

    $id          = (int)($_POST['id'] ?? 0);
    $name        = trim($_POST['name'] ?? '');
    $mobile      = trim($_POST['mobile'] ?? '');
    $dob         = $_POST['user_dob'] ?? '';
    $gender      = $_POST['gender'] ?? '';
    $shift       = $_POST['shift'] ?? '';
    $joiningDate = $_POST['joining_date'] ?? '';
    $address     = trim($_POST['address'] ?? '');
    $salary      = trim($_POST['salary'] ?? '');

    /* ===== VALIDATION ===== */
    if ($name === '') {
        $errors['edit_name'] = 'Please enter collector name';
    }

    if ($mobile === '') {
        $errors['edit_mobile'] = 'Please enter mobile number';
    } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $errors['edit_mobile'] = 'Mobile must be 10 digits';
    }

    if ($dob === '' || strtotime($dob) >= strtotime(date('Y-m-d'))) {
        $errors['edit_dob'] = 'Please select a valid DOB';
    }

    if (!in_array($gender, ['Male', 'Female', 'Other'], true)) {
        $errors['edit_gender'] = 'Please select gender';
    }

    if (!in_array($shift, ['day', 'evening'], true)) {
        $errors['edit_shift'] = 'Please select shift';
    }

    if ($joiningDate === '') {
        $errors['edit_joining_date'] = 'Please select joining date';
    }

    if ($address === '') {
        $errors['edit_address'] = 'Please enter address';
    }

    if ($salary === '' || !is_numeric($salary) || $salary < 0) {
        $errors['edit_salary'] = 'Salary must be valid number';
    }

    /* ===== DUPLICATE MOBILE CHECK ===== */
    if (empty($errors)) {
        $check = $pdo->prepare("SELECT id FROM garbage_collectors WHERE mobile = ? AND id != ?");
        $check->execute([$mobile, $id]);
        if ($check->fetch()) {
            $errors['edit_mobile'] = 'Mobile already exists';
        }
    }

    /* ===== UPDATE ===== */
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE garbage_collectors SET
                name = ?,
                mobile = ?,
                dob = ?,
                gender = ?,
                shift = ?,
                joining_date = ?,
                address = ?,
                salary = ?
            WHERE id = ?
        ");

        if ($stmt->execute([$name, $mobile, $dob, $gender, $shift, $joiningDate, $address, $salary, $id])) {
            flash_set('success', 'Garbage collector updated successfully');
            header('Location: ' . BASE_URL . 'garbageCollector.php');
            exit;
        } else {
            $errors['edit_db'] = 'Database error! Not updated.';
        }
    }
}


// DELETE (ADMIN ONLY)
if (
    $userRole === 'admin' &&
    isset($_GET['action'], $_GET['id']) &&
    $_GET['action'] === 'delete' &&
    ctype_digit($_GET['id'])
) {
    $stmt = $pdo->prepare("DELETE FROM garbage_collectors WHERE id = ?");
    $stmt->execute([$_GET['id']]);

    // $_SESSION['success'] = 'Garbage collector removed successfully';
    flash_set('success', 'Garbage collector removed successfully');
    header('Location: ' . BASE_URL . 'garbageCollector.php');
    exit;
}

include('../resources/layout/header.php');
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Garbage Collector</title>

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
                <h1 class="page-title m-0">Garbage Collector Inventory</h1>
                <!----------- Admin Only ----------->
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <button class="btn btn-brand shadow-sm" data-bs-toggle="modal" data-bs-target="#addCollectorModal">
                        <i class="fa-solid fa-plus me-2"></i> Add Garbage Collector
                    </button>
                <?php endif; ?>
            </div>

            <div class="data-card shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table table-hover w-100" id="collector-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Mobile</th>
                                <th>DOB</th>
                                <th>Gender</th>
                                <th>Shift</th>
                                <th>Joining Date</th>
                                <th>Salary</th>
                                <?php if ($userRole === 'admin'): ?>
                                    <th>Action</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>


    <!-- ADD GARBAGE COLLECTOR MODAL -->
    <div class="modal fade" id="addCollectorModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius:20px;">

                <div class="modal-header border-0 p-4">
                    <h5 class="modal-title fw-bold">Add Garbage Collector</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4 pt-0">

                    <?php if (isset($errors['db'])): ?>
                        <div class="alert alert-danger"><?= $errors['db'] ?></div>
                    <?php endif; ?>

                    <form method="POST" class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Collector Name *</label>
                            <input type="text" name="name"
                                value="<?= htmlspecialchars($name) ?>"
                                class="form-control bg-light border-0">
                            <small class="text-danger"><?= $errors['name'] ?? '' ?></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Mobile *</label>
                            <input type="text" name="mobile"
                                value="<?= htmlspecialchars($mobile) ?>"
                                class="form-control bg-light border-0">
                            <small class="text-danger"><?= $errors['mobile'] ?? '' ?></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Date of Birth *</label>
                            <input type="date" name="user_dob"
                                value="<?= htmlspecialchars($dob) ?>"
                                class="form-control bg-light border-0">
                            <small class="text-danger"><?= $errors['dob'] ?? '' ?></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Gender *</label>
                            <select name="gender" class="form-select bg-light border-0">
                                <option value="">Select</option>
                                <option value="Male" <?= $gender === 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= $gender === 'Female' ? 'selected' : '' ?>>Female</option>
                                <option value="Other" <?= $gender === 'Other' ? 'selected' : '' ?>>Other</option>
                            </select>
                            <small class="text-danger"><?= $errors['gender'] ?? '' ?></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Shift *</label>
                            <select name="shift" class="form-select bg-light border-0">
                                <option value="">Select</option>
                                <option value="day" <?= $shift === 'day' ? 'selected' : '' ?>>Day</option>
                                <option value="evening" <?= $shift === 'evening' ? 'selected' : '' ?>>Evening</option>
                            </select>
                            <small class="text-danger"><?= $errors['shift'] ?? '' ?></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Joining Date *</label>
                            <input type="date" name="joining_date"
                                value="<?= htmlspecialchars($joiningDate) ?>"
                                class="form-control bg-light border-0">
                            <small class="text-danger"><?= $errors['joining_date'] ?? '' ?></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Salary *</label>
                            <input type="number" step="0.01" name="salary"
                                value="<?= htmlspecialchars($salary) ?>"
                                class="form-control bg-light border-0">
                            <small class="text-danger"><?= $errors['salary'] ?? '' ?></small>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Address *</label>
                            <textarea name="address" rows="2"
                                class="form-control bg-light border-0"><?= htmlspecialchars($address) ?></textarea>
                            <small class="text-danger"><?= $errors['address'] ?? '' ?></small>
                        </div>

                        <div class="col-12">
                            <button type="submit" name="add_collector"
                                class="btn btn-brand w-100 py-3 mt-2">
                                Save Garbage Collector
                            </button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>


    <!-- EDIT GARBAGE COLLECTOR MODAL -->
    <div class="modal fade" id="editCollectorModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius:20px;">

                <div class="modal-header border-0 p-4">
                    <h5 class="modal-title fw-bold">Edit Garbage Collector</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4 pt-0">
                    <form method="POST" class="row g-3">

                        <input type="hidden" name="id" id="edit_id">

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Name *</label>
                            <input type="text" name="name" id="edit_name"
                                value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                                class="form-control bg-light border-0">

                            <small class="text-danger"><?= $errors['edit_name'] ?? '' ?></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Mobile *</label>
                            <input type="text" name="mobile" id="edit_mobile"
                                value="<?= htmlspecialchars($_POST['mobile'] ?? '') ?>"
                                class="form-control bg-light border-0">

                            <small class="text-danger"><?= $errors['edit_mobile'] ?? '' ?></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Date of Birth *</label>
                            <input type="date" name="user_dob" id="edit_dob"
                                value="<?= htmlspecialchars($_POST['user_dob'] ?? '') ?>"
                                class="form-control bg-light border-0">

                            <small class="text-danger"><?= $errors['edit_dob'] ?? '' ?></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Gender *</label>
                            <select name="gender" id="edit_gender" class="form-select bg-light border-0">
                                <option value="">Select</option>
                                <option value="Male" <?= ($_POST['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= ($_POST['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                                <option value="Other" <?= ($_POST['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                            </select>

                            <small class="text-danger"><?= $errors['edit_gender'] ?? '' ?></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Shift *</label>
                            <select name="shift" id="edit_shift" class="form-select bg-light border-0">
                                <option value="">Select</option>
                                <option value="day" <?= ($_POST['shift'] ?? '') === 'day' ? 'selected' : '' ?>>Day</option>
                                <option value="evening" <?= ($_POST['shift'] ?? '') === 'evening' ? 'selected' : '' ?>>Evening</option>
                            </select>

                            <small class="text-danger"><?= $errors['edit_shift'] ?? '' ?></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Joining Date *</label>
                            <input type="date" name="joining_date" id="edit_joining_date"
                                value="<?= htmlspecialchars($_POST['joining_date'] ?? '') ?>"
                                class="form-control bg-light border-0">

                            <small class="text-danger"><?= $errors['edit_joining_date'] ?? '' ?></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Salary *</label>
                            <input type="number" step="0.01" name="salary" id="edit_salary"
                                value="<?= htmlspecialchars($_POST['salary'] ?? '') ?>"
                                class="form-control bg-light border-0">

                            <small class="text-danger"><?= $errors['edit_salary'] ?? '' ?></small>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Address *</label>
                            <textarea name="address" id="edit_address" rows="2"
                                class="form-control bg-light border-0"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>

                            <small class="text-danger"><?= $errors['edit_address'] ?? '' ?></small>
                        </div>

                        <div class="col-12">
                            <button type="submit" name="edit_collector" class="btn btn-brand w-100 py-3 mt-2">
                                Update Garbage Collector
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- DELETE Confirmation MODAL -->
    <div class="modal fade" id="deleteCollectorModal" tabindex="-1">
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
                        🗑️
                    </div>

                    <!-- Title -->
                    <h5 class="fw-bold mb-2 text-danger">Delete Garbage Collector</h5>

                    <!-- Message -->
                    <p class="text-muted mb-4">
                        Are you sure you want to delete this garbage collector?<br>
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
                            id="confirmDeleteCollectorBtn"
                            class="btn btn-danger px-4 py-2 fw-bold"
                            style="border-radius:10px;">
                            Yes, Delete
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>


    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.2/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.2/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {

            var isAdmin = '<?= $userRole ?>' === 'admin';

            var columns = [{
                    data: 'name'
                },
                {
                    data: 'mobile'
                },
                {
                    data: 'dob'
                },
                {
                    data: 'gender'
                },
                {
                    data: 'shift'
                },
                {
                    data: 'joining_date'
                },
                {
                    data: 'salary'
                }
            ];

            if (isAdmin) {
                columns.push({
                    data: null,
                    orderable: false,
                    render: function(data) {
                        return `
                        <button class="btn btn-sm btn-light border edit_collector_btn"
									data-id="${data.id}" title="Edit">
								<i class="fa fa-pen"></i>
							</button>
							<button class="btn btn-sm btn-light border text-danger delete_btn mt-1"
									data-id="${data.id}" title="Delete">
								<i class="fa fa-trash"></i>
							</button>
                    `;
                    }
                });
            }

            $('#collector-table').DataTable({
                dom: '<"d-flex justify-content-between mb-4"lf>rt<"d-flex justify-content-between mt-4"ip>',
                processing: true,
                serverSide: true,
                pageLength: 5,
                lengthMenu: [5, 10, 25, 50],
                language: {
                    search: "",
                    searchPlaceholder: "Search records..."
                },
                ajax: {
                    url: '<?= BASE_URL ?>action.php',
                    type: 'POST',
                    data: {
                        action: 'fetch_garbage_collectors'
                    }
                },
                columns: columns
            });

            let deleteId = null;

            // When delete icon clicked
            $(document).on('click', '.delete_btn', function() {
                deleteId = $(this).data('id');
                var deleteModal = new bootstrap.Modal(document.getElementById('deleteCollectorModal'));
                deleteModal.show();
            });

            // When confirm button clicked
            $('#confirmDeleteCollectorBtn').on('click', function() {
                if (deleteId) {
                    window.location.href =
                        '<?= BASE_URL ?>garbageCollector.php?action=delete&id=' + deleteId;
                }
            });


            // EDIT BUTTON CLICK
            $(document).on('click', '.edit_collector_btn', function() {
                let rowData = $('#collector-table').DataTable().row($(this).parents('tr')).data();

                if (rowData) {
                    $('#edit_id').val(rowData.id);
                    $('#edit_name').val(rowData.name);
                    $('#edit_mobile').val(rowData.mobile);
                    $('#edit_dob').val(rowData.dob);
                    $('#edit_gender').val(rowData.gender);
                    $('#edit_shift').val(rowData.shift);
                    $('#edit_joining_date').val(rowData.joining_date);
                    $('#edit_salary').val(rowData.salary);
                    $('#edit_address').val(rowData.address);

                    var editModal = new bootstrap.Modal(document.getElementById('editCollectorModal'));
                    editModal.show();
                }
            });

        });
    </script>

    <script>
        <?php if (!empty($errors) && isset($_POST['edit_collector'])): ?>
            var editModal = new bootstrap.Modal(document.getElementById('editCollectorModal'));
            editModal.show();
        <?php elseif (!empty($errors) && isset($_POST['add_collector'])): ?>
            var addModal = new bootstrap.Modal(document.getElementById('addCollectorModal'));
            addModal.show();
        <?php endif; ?>
    </script>

</body>

</html>

<?php include('../resources/layout/footer.php'); ?>
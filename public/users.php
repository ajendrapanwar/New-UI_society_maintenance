<?php

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

// Admin access check
requireRole(['admin']);


// ================= ADD USER FROM  =================
$errors = [];
$name = $email = $mobile = $dob = $gender = $role = '';
$password = '';

if (isset($_POST['add_user'])) {

    // Map values (SAME AS OLD CODE)
    $name = trim($_POST['u_name_random'] ?? '');
    $email    = trim($_POST['user_email'] ?? '');
    $password = $_POST['user_password'] ?? '';
    $mobile   = trim($_POST['user_mobile'] ?? '');
    $dob      = $_POST['user_dob'] ?? '';
    $gender   = $_POST['user_gender'] ?? '';
    $role     = $_POST['user_role'] ?? '';

    /* ===== VALIDATION (SAME OLD) ===== */
    if ($name === '') $errors['name'] = 'Please enter name';

    if ($email === '') {
        $errors['email'] = 'Please enter email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email';
    }

    if ($password === '') {
        $errors['password'] = 'Please enter password';
    }

    if ($mobile === '') {
        $errors['mobile'] = 'Please enter mobile';
    } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $errors['mobile'] = 'Mobile must be 10 digits';
    }

    if ($dob === '') {
        $errors['dob'] = 'Please select date of birth';
    }

    if (!in_array($gender, ['Male', 'Female', 'Other'])) {
        $errors['gender'] = 'Please select gender';
    }

    if (!in_array($role, ['admin', 'cashier', 'user'])) {
        $errors['role'] = 'Please select role';
    }

    /* ===== DUPLICATE CHECK (SAME OLD) ===== */
    if (empty($errors)) {
        $check = $pdo->prepare("SELECT email, mobile FROM users WHERE email = ? OR mobile = ?");
        $check->execute([$email, $mobile]);
        $existing = $check->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            if ($existing['email'] === $email) {
                $errors['email'] = 'Email already exists';
            }
            if ($existing['mobile'] === $mobile) {
                $errors['mobile'] = 'Mobile already exists';
            }
        }
    }

    /* ===== INSERT USER (SAME OLD) ===== */
    if (empty($errors)) {

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, mobile, dob, gender, role, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        if ($stmt->execute([$name, $email, $hashedPassword, $mobile, $dob, $gender, $role])) {
            flash_set('success', 'New user added successfully');
            header('Location: ' . BASE_URL . 'users.php');
            exit();
        } else {
            flash_set('err', 'Database error! User not added.');
        }
    }
}


// ================= Handle Delete =================
if (
    isset($_GET['action'], $_GET['id']) &&
    $_GET['action'] === 'delete' &&
    is_numeric($_GET['id'])
) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$_GET['id']]);

    // $_SESSION['success'] = 'User has been removed successfully.';
    flash_set('success', 'User has been removed successfully');
    header('Location: ' . BASE_URL . 'users.php');
    exit;
}

include('../resources/layout/header.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>

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
                <h1 class="page-title m-0">User Management</h1>
                <button class="btn btn-brand shadow-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fa-solid fa-user-plus me-2"></i> Add User
                </button>
            </div>

            <div class="data-card shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table table-hover w-100" id="users-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email/Phone</th>
                                <th>Role</th>
                                <th>Created Date</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </main>
    </div>


    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius:20px;">

                <div class="modal-header border-0 p-4">
                    <h5 class="modal-title fw-800">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4 pt-0">

                    <form method="POST" class="row g-3" autocomplete="off">

                        <!-- Anti Autofill Trap -->
                        <input type="text" name="prevent_autofill" autocomplete="username" style="position:absolute;left:-9999px;">
                        <input type="password" name="prevent_autofill_pass" autocomplete="new-password" style="position:absolute;left:-9999px;">

                        <!-- NAME -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">
                                FULL NAME <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                name="u_name_random"
                                class="form-control bg-light border-0"
                                autocomplete="off"
                                autocapitalize="off"
                                spellcheck="false"
                                placeholder="Enter full name">
                            <small class="text-danger"><?= $errors['name'] ?? '' ?></small>
                        </div>

                        <!-- EMAIL -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">
                                EMAIL <span class="text-danger">*</span>
                            </label>
                            <input type="email"
                                name="user_email"
                                value="<?= htmlspecialchars($email) ?>"
                                class="form-control bg-light border-0"
                                autocomplete="new-email"
                                placeholder="Enter email">
                            <small class="text-danger"><?= $errors['email'] ?? '' ?></small>
                        </div>

                        <!-- MOBILE -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">
                                MOBILE <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                name="user_mobile"
                                value="<?= htmlspecialchars($mobile) ?>"
                                class="form-control bg-light border-0"
                                inputmode="numeric"
                                autocomplete="off"
                                placeholder="10 digit mobile">
                            <small class="text-danger"><?= $errors['mobile'] ?? '' ?></small>
                        </div>

                        <!-- PASSWORD -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">
                                PASSWORD <span class="text-danger">*</span>
                            </label>
                            <input type="password"
                                name="user_password"
                                class="form-control bg-light border-0"
                                autocomplete="new-password"
                                placeholder="Enter password">
                            <small class="text-danger"><?= $errors['password'] ?? '' ?></small>
                        </div>

                        <!-- DOB -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">
                                DATE OF BIRTH <span class="text-danger">*</span>
                            </label>
                            <input type="date"
                                name="user_dob"
                                value="<?= htmlspecialchars($dob) ?>"
                                class="form-control bg-light border-0"
                                autocomplete="off">
                            <small class="text-danger"><?= $errors['dob'] ?? '' ?></small>
                        </div>

                        <!-- GENDER -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">
                                GENDER <span class="text-danger">*</span>
                            </label>
                            <select name="user_gender" class="form-select bg-light border-0" autocomplete="off">
                                <option value="">Select Gender</option>
                                <option value="Male" <?= $gender === 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= $gender === 'Female' ? 'selected' : '' ?>>Female</option>
                                <option value="Other" <?= $gender === 'Other' ? 'selected' : '' ?>>Other</option>
                            </select>
                            <small class="text-danger"><?= $errors['gender'] ?? '' ?></small>
                        </div>

                        <!-- ROLE -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">
                                ROLE <span class="text-danger">*</span>
                            </label>
                            <select name="user_role" class="form-select bg-light border-0" autocomplete="off">
                                <option value="">Select Role</option>
                                <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>Resident</option>
                                <option value="cashier" <?= $role === 'cashier' ? 'selected' : '' ?>>Cashier</option>
                                <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                            <small class="text-danger"><?= $errors['role'] ?? '' ?></small>
                        </div>

                        <div class="col-12">
                            <button type="submit" name="add_user" class="btn btn-brand w-100 py-3 mt-3">
                                Save User
                            </button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>


    <!-- Edit User Popup -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius:20px;">
                <div class="modal-header border-0 p-4 pb-0">
                    <h5 class="modal-title fw-800">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <form id="editUserForm" class="row g-3">

                        <input type="hidden" name="id" id="edit_id">

                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">FULL NAME *</label>
                            <input type="text" name="user_name" id="edit_name" class="form-control bg-light border-0">
                            <small class="text-danger" id="error_name"></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">EMAIL *</label>
                            <input type="email" name="user_email" id="edit_email" class="form-control bg-light border-0">
                            <small class="text-danger" id="error_email"></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">PHONE *</label>
                            <input type="text" name="user_mobile" id="edit_mobile" class="form-control bg-light border-0">
                            <small class="text-danger" id="error_mobile"></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">NEW PASSWORD (Optional)</label>
                            <input type="password" name="user_password" id="edit_password" class="form-control bg-light border-0">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">DOB *</label>
                            <input type="date" name="user_dob" id="edit_dob" class="form-control bg-light border-0">
                            <small class="text-danger" id="error_dob"></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">GENDER *</label>
                            <select name="user_gender" id="edit_gender" class="form-select bg-light border-0">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                            <small class="text-danger" id="error_gender"></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">ROLE *</label>
                            <select name="user_role" id="edit_role" class="form-select bg-light border-0">
                                <option value="">Select Role</option>
                                <option value="user">Resident</option>
                                <option value="cashier">Cashier</option>
                                <option value="admin">Admin</option>
                            </select>
                            <small class="text-danger" id="error_role"></small>
                        </div>

                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-brand w-100 py-3">
                                Update User
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>



    <!-- DELETE Confirmation MODAL -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1">
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
                    <h5 class="fw-bold mb-2 text-danger">Delete User</h5>

                    <!-- Message -->
                    <p class="text-muted mb-4">
                        Are you sure you want to delete this user?<br>
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

            $('#users-table').DataTable({
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
                        action: 'fetch_users'
                    }
                },
                columns: [{
                        data: null,
                        render: function(data) {
                            let initials = data.name ?
                                data.name.split(' ').map(n => n[0]).join('').toUpperCase() :
                                'U';

                            return `
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar-circle" style="width:32px;height:32px;font-size:0.7rem;">
                                ${initials}
                            </div>
                            <span class="fw-bold">${data.name}</span>
                        </div>
                    `;
                        }
                    },
                    {
                        data: null,
                        render: function(data) {
                            return `
                        ${data.email}<br>
                        <small class="text-muted">${data.mobile ?? ''}</small>
                    `;
                        }
                    },
                    {
                        data: 'role',
                        render: function(role) {
                            let roleClass = 'role-resident';

                            if (role === 'admin') roleClass = 'role-admin';
                            else if (role === 'cashier') roleClass = 'role-cashier';

                            return `<span class="role-badge ${roleClass}">${role}</span>`;
                        }
                    },
                    {
                        data: 'created_at',
                        render: function(date) {
                            if (!date) return '-';
                            return new Date(date).toLocaleDateString('en-GB', {
                                day: '2-digit',
                                month: 'short',
                                year: 'numeric'
                            });
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        className: 'text-end',
                        render: function(data) {
                            return `
                        <button class="btn btn-sm btn-light border edit_btn"
                                data-id="${data.id}" title="Edit">
                            <i class="fa fa-pen"></i>
                        </button>
                        <button class="btn btn-sm btn-light border text-danger delete_btn" 
                                data-id="${data.id}" title="Delete">
                            <i class="fa fa-trash"></i>
                        </button>
                    `;
                        }
                    }
                ]
            });

            // Delete
            let deleteUserId = null;

            // Open modern delete modal
            $(document).on('click', '.delete_btn', function() {
                deleteUserId = $(this).data('id');
                $('#deleteConfirmModal').modal('show');
            });

            // Confirm delete button click
            $('#confirmDeleteBtn').on('click', function() {

                if (!deleteUserId) return;

                $(this).prop('disabled', true).text('Deleting...');

                window.location.href =
                    '<?= BASE_URL ?>users.php?action=delete&id=' + deleteUserId;
            });

            // OPEN EDIT MODAL
            $(document).on('click', '.edit_btn', function() {

                let userId = $(this).data('id');

                $.ajax({
                    url: '<?= BASE_URL ?>action.php',
                    type: 'POST',
                    data: {
                        action: 'get_user',
                        id: userId
                    },
                    success: function(response) {

                        let data = JSON.parse(response);

                        $('#edit_id').val(data.id);
                        $('#edit_name').val(data.name);
                        $('#edit_email').val(data.email);
                        $('#edit_mobile').val(data.mobile);
                        $('#edit_dob').val(data.dob);
                        $('#edit_gender').val(data.gender);
                        $('#edit_role').val(data.role);

                        new bootstrap.Modal(document.getElementById('editUserModal')).show();
                    }
                });

            });

            // SUBMIT EDIT FORM
            $('#editUserForm').submit(function(e) {
                e.preventDefault();

                // Clear old errors
                $('#error_name').text('');
                $('#error_email').text('');
                $('#error_mobile').text('');
                $('#error_dob').text('');
                $('#error_gender').text('');
                $('#error_role').text('');

                $.ajax({
                    url: '<?= BASE_URL ?>action.php',
                    type: 'POST',
                    data: $(this).serialize() + '&action=update_user',
                    success: function(response) {

                        let res = JSON.parse(response);

                        // VALIDATION ERRORS (LIKE OLD PHP)
                        if (res.status === 'validation_error') {

                            if (res.errors.name) {
                                $('#error_name').text(res.errors.name);
                            }
                            if (res.errors.email) {
                                $('#error_email').text(res.errors.email);
                            }
                            if (res.errors.mobile) {
                                $('#error_mobile').text(res.errors.mobile);
                            }
                            if (res.errors.dob) {
                                $('#error_dob').text(res.errors.dob);
                            }
                            if (res.errors.gender) {
                                $('#error_gender').text(res.errors.gender);
                            }
                            if (res.errors.role) {
                                $('#error_role').text(res.errors.role);
                            }

                            return;
                        }

                        // SUCCESS

                        if (res.status === 'success') {

                            bootstrap.Modal.getInstance(
                                document.getElementById('editUserModal')
                            ).hide();

                            // Reload the page to show PHP flash
                            window.location.href = '<?= BASE_URL ?>users.php';

                        } else {
                            alert(res.message);
                        }
                    }
                });
            });


        });
    </script>

    <?php if (!empty($errors)): ?>
        <script>
            var addUserModal = new bootstrap.Modal(document.getElementById('addUserModal'));
            addUserModal.show();
        </script>
    <?php endif; ?>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Prevent Chrome autofill completely
            const fakeInput = document.createElement("input");
            fakeInput.type = "text";
            fakeInput.style.display = "none";
            fakeInput.autocomplete = "off";
            document.body.prepend(fakeInput);

            // Blur autofill focus trick
            setTimeout(() => {
                fakeInput.focus();
                fakeInput.blur();
            }, 50);
        });
    </script>

</body>

</html>


<?php include('../resources/layout/footer.php'); ?>
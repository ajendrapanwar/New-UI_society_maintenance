<?php

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

requireRole(['admin']);

$errors = [];

/* ADD TYPE */
if (isset($_POST['add_type'])) {

    $type_name = trim($_POST['type_name'] ?? '');

    if ($type_name === '') {
        $errors['type_name'] = "Flat type is required";
    }

    if (empty($errors)) {

        $check = $pdo->prepare("SELECT id FROM flat_types WHERE type_name=?");
        $check->execute([$type_name]);

        if ($check->fetch()) {
            $errors['duplicate'] = "Flat type already exists";
        }
    }

    if (empty($errors)) {

        $stmt = $pdo->prepare("
            INSERT INTO flat_types (type_name, created_at)
            VALUES (?, NOW())
        ");

        $stmt->execute([$type_name]);

        flash_set('success', 'Flat type added successfully');
        header("Location: " . BASE_URL . "flat_types.php");
        exit;
    }
}


/* DELETE */
if (
    isset($_GET['action'], $_GET['id']) &&
    $_GET['action'] == 'delete' &&
    is_numeric($_GET['id'])
) {

    $stmt = $pdo->prepare("DELETE FROM flat_types WHERE id=?");
    $stmt->execute([$_GET['id']]);

    flash_set('success', 'Flat type deleted');
    header("Location: " . BASE_URL . "flat_types.php");
    exit;
}

include('../resources/layout/header.php');

?>




<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Flats</title>

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
                <h1 class="page-title m-0">Flat Types</h1>

                <button class="btn btn-brand"
                    data-bs-toggle="modal"
                    data-bs-target="#addTypeModal">

                    <i class="fa fa-plus me-2"></i> Add Type
                </button>
            </div>


            <div class="data-card shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table table-hover w-100" id="flat-type-table">
                        <thead>
                            <tr>
                                <th>Flat Type</th>
                                <th>Created</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </main>
    </div>




    <!-- Add Flat Type Popup -->
    <div class="modal fade" id="addTypeModal">
        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content border-0 shadow-lg">

                <div class="modal-header">
                    <h5 class="fw-800">Add Flat Type</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <form method="POST">

                        <!-- DUPLICATE ERROR -->
                        <?php if (isset($errors['duplicate'])): ?>
                            <div class="alert alert-danger">
                                <?= htmlspecialchars($errors['duplicate']) ?>
                            </div>
                        <?php endif; ?>

                        <label class="form-label small fw-bold text-muted">
                            FLAT TYPE <span class="text-danger">*</span>
                        </label>

                        <input type="text"
                            name="type_name"
                            value="<?= htmlspecialchars($_POST['type_name'] ?? '') ?>"
                            class="form-control bg-light border-0"
                            placeholder="Example: 2 BHK Flat">

                        <?php if (isset($errors['type_name'])): ?>
                            <small class="text-danger">
                                <?= htmlspecialchars($errors['type_name']) ?>
                            </small>
                        <?php endif; ?>


                        <button type="submit"
                            name="add_type"
                            class="btn btn-brand w-100 mt-4">

                            Save Type

                        </button>

                    </form>

                </div>
            </div>
        </div>
    </div>

    <!-- Delete Flat Type Popup -->
    <div class="modal fade" id="deleteTypeModal">
        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content border-0 shadow-lg">

                <div class="modal-body text-center p-4">

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

                        🏠

                    </div>

                    <h5 class="fw-bold text-danger mb-2">Delete Flat Type</h5>

                    <p class="text-muted mb-4">
                        Are you sure you want to delete this type?
                    </p>

                    <div class="d-flex gap-3 justify-content-center">

                        <button class="btn btn-light"
                            data-bs-dismiss="modal">
                            Cancel
                        </button>

                        <button class="btn btn-danger"
                            id="confirmDeleteBtn">
                            Yes Delete
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

            $('#flat-type-table').DataTable({
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
                    url: 'action.php',
                    type: 'POST',
                    data: {
                        action: 'fetch_flat_types'
                    }
                },
                columns: [{
                        data: "type_name",
                        render: function(data) {
                            return `<span class="fw-bold">${data}</span>`;
                        }
                    },
                    {
                        data: "created_at",
                        render: function(data) {
                            let d = new Date(data);
                            return d.toLocaleDateString('en-GB', {
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
                                <button class="btn btn-sm btn-light border text-danger delete_btn"
                                data-id="${data.id}">
                                <i class="fa fa-trash"></i>
                                </button>
                                `;
                        }
                    }
                ]
            });


            /* DELETE */

            let deleteId = null;

            $(document).on('click', '.delete_btn', function() {
                deleteId = $(this).data('id');
                $('#deleteTypeModal').modal('show');
            });

            $('#confirmDeleteBtn').click(function() {
                window.location.href =
                    '<?= BASE_URL ?>flat_types.php?action=delete&id=' + deleteId;
            });

        });
    </script>

    <?php if (!empty($errors)): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {

                var modal = new bootstrap.Modal(
                    document.getElementById('addTypeModal')
                );

                modal.show();

            });
        </script>
    <?php endif; ?>


</body>

</html>

<?php include('../resources/layout/footer.php'); ?>
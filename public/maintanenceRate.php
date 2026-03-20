<?php

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';


// Admin access check
requireRole(['admin']);


/* ===== ADD RATE FROM MODAL ===== */
$errors = [];
$flat_type = '';
$rate = '';
$overdue_fine = '';

if (isset($_POST['add_rate'])) {

    $flat_type   = trim($_POST['flat_type'] ?? '');
    $rate        = trim($_POST['rate'] ?? '');
    $overdue_fine = trim($_POST['overdue_fine'] ?? '');

    /* ===== VALIDATION ===== */
    if ($flat_type === '') {
        $errors['flat_type'] = 'Please select flat type';
    }

    if ($rate === '') {
        $errors['rate'] = 'Rate is required';
    } elseif (!is_numeric($rate)) {
        $errors['rate'] = 'Rate must be numeric';
    }

    if ($overdue_fine === '') {
        $errors['overdue_fine'] = 'Overdue fine is required';
    } elseif (!is_numeric($overdue_fine)) {
        $errors['overdue_fine'] = 'Overdue fine must be numeric';
    } elseif ($overdue_fine < 0) {
        $errors['overdue_fine'] = 'Overdue fine cannot be negative';
    }

    /* ===== DUPLICATE CHECK ===== */
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM maintenance_rates WHERE flat_type = ?");
        $stmt->execute([$flat_type]);

        if ($stmt->rowCount() > 0) {
            $errors['flat_type'] = 'This flat type already exists';
        }
    }

    /* ===== INSERT ===== */
    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "INSERT INTO maintenance_rates (flat_type, rate, overdue_fine)
             VALUES (?, ?, ?)"
        );

        if ($stmt->execute([$flat_type, $rate, $overdue_fine])) {
            flash_set('success', 'Maintenance rate added successfully');
            header('Location: maintanenceRate.php');
            exit();
        } else {
            flash_set('err', 'Database error! Maintenance rate not added.');
        }
    }
}


/* ===== FETCH ALL MAINTENANCE RATES ===== */
$stmt = $pdo->prepare("SELECT * FROM maintenance_rates ORDER BY flat_type ASC");
$stmt->execute();
$ratesList = $stmt->fetchAll();

/* ===== BULK UPDATE (LIKE OLD EDIT LOGIC) ===== */
if (isset($_POST['update_rates'])) {

    $ids           = $_POST['id'] ?? [];
    $rates         = $_POST['rate'] ?? [];
    $overdue_fines = $_POST['overdue_fine'] ?? [];

    if (!empty($ids)) {

        foreach ($ids as $index => $id) {

            $id = (int)$id;
            $rate_val = trim($rates[$index] ?? '');
            $fine_val = trim($overdue_fines[$index] ?? '');

            // VALIDATION (same as old code)
            if ($rate_val === '' || !is_numeric($rate_val)) {
                continue;
            }

            if ($fine_val === '' || !is_numeric($fine_val) || $fine_val < 0) {
                continue;
            }

            // UPDATE (same logic as old edit page)
            $stmt = $pdo->prepare(
                "UPDATE maintenance_rates 
                 SET rate = ?, overdue_fine = ?
                 WHERE id = ?"
            );

            $stmt->execute([$rate_val, $fine_val, $id]);
        }

        flash_set('success', 'Maintenance rates updated successfully');
        header('Location: maintanenceRate.php');
        exit();
    }
}


/* ===== DELETE ===== */
if (
    isset($_GET['action'], $_GET['id']) &&
    $_GET['action'] === 'delete' &&
    is_numeric($_GET['id'])
) {
    $stmt = $pdo->prepare("DELETE FROM maintenance_rates WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);


    flash_set('success', 'Maintenance rate deleted successfully');
    header('Location: maintanenceRate.php');
    exit();
}

include('../resources/layout/header.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Rates</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">

    <style>
        /* Force horizontal scroll on small screens */
        @media (max-width: 991px) {

            .data-card {
                overflow-x: auto !important;
            }

            .table-responsive {
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch;
            }

            .rate-table {
                min-width: 900px;
                /* force table wider than mobile */
                white-space: nowrap;
            }

            .rate-table input {
                min-width: 120px;
            }

        }
    </style>

</head>

<body>


    <div class="main-wrapper">
        <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

        <main id="main-content">

            <form method="POST">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">

                    <h1 class="fw-800 m-0">Monthly Fixed Rates</h1>

                    <div class="sm-w-100 mt-3 mt-md-0">
                        <div class="d-flex flex-column flex-md-row gap-2">

                            <button type="submit"
                                name="update_rates"
                                class="btn btn-brand shadow-sm">
                                <i class="fa-solid fa-floppy-disk me-2"></i> Update Rates
                            </button>

                            <button type="button"
                                class="btn btn-brand shadow-sm"
                                data-bs-toggle="modal"
                                data-bs-target="#addRateModal">
                                <i class="fa-solid fa-plus me-2"></i> Add Rates
                            </button>

                        </div>
                    </div>

                </div>

                <div class="row g-4">
                    <div class="col-lg-12">
                        <div class="data-card shadow-sm border-0">
                            <h5 class="fw-bold mb-4">Maintenance Cost per Unit Type</h5>

                            <div class="table-responsive">
                                <table class="table rate-table align-middle">
                                    <thead>
                                        <tr>
                                            <th style="width: 30%;">Unit Type</th>
                                            <th>Monthly Fixed Rate</th>
                                            <th>LATE FEE (AFTER DUE DATE)</th>
                                            <th class="text-center" style="width: 120px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                        <?php if (!empty($ratesList)): ?>
                                            <?php foreach ($ratesList as $index => $row): ?>
                                                <tr>

                                                    <!-- HIDDEN ID (REQUIRED FOR UPDATE) -->
                                                    <input type="hidden" name="id[]" value="<?= $row['id'] ?>">

                                                    <!-- UNIT TYPE -->
                                                    <td>
                                                        <span class="fw-bold">
                                                            <?= htmlspecialchars($row['flat_type']) ?>
                                                        </span>
                                                    </td>

                                                    <!-- MONTHLY RATE (EDITABLE) -->
                                                    <td>
                                                        <div class="input-group" style="max-width: 220px;">
                                                            <span class="input-group-text">₹</span>
                                                            <input type="number"
                                                                name="rate[]"
                                                                class="form-control fw-bold text-primary bg-light border-0"
                                                                value="<?= htmlspecialchars($row['rate']) ?>">
                                                        </div>
                                                    </td>

                                                    <!-- OVERDUE FINE (EDITABLE) -->
                                                    <td>
                                                        <div class="input-group" style="max-width: 250px;">
                                                            <span class="input-group-text">₹</span>
                                                            <input type="number"
                                                                name="overdue_fine[]"
                                                                class="form-control bg-light border-0"
                                                                value="<?= htmlspecialchars($row['overdue_fine']) ?>">
                                                            <span class="input-group-text">Flat Fee</span>
                                                        </div>
                                                    </td>


                                                    <!-- ACTION BUTTON -->
                                                    <td class="text-center">
                                                        <button type="button"
                                                            class="btn btn-sm btn-light border text-danger delete_btn mt-1"
                                                            data-id="<?= $row['id'] ?>">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </td>

                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3" class="text-center text-muted py-4">
                                                    No maintenance rates found
                                                </td>
                                            </tr>
                                        <?php endif; ?>

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>


    <!-- Add Maintenance Rate Popup -->
    <div class="modal fade" id="addRateModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">

                <div class="modal-header border-0 p-4">
                    <h5 class="modal-title fw-800">New Maintenance Rate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4 pt-0">

                    <?php if (isset($errors['duplicate'])): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($errors['duplicate']) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="row g-3">

                        <!-- FLAT TYPE -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">
                                FLAT TYPE <span class="text-danger">*</span>
                            </label>

                            <select name="flat_type"
                                class="form-select bg-light border-0">

                                <option value="">Select Type</option>

                                <?php foreach ($flatTypes as $type): ?>
                                    <option value="<?= htmlspecialchars($type) ?>"
                                        <?= ($flat_type === $type) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($type) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <?php if (isset($errors['flat_type'])): ?>
                                <small class="text-danger">
                                    <?= $errors['flat_type'] ?>
                                </small>
                            <?php endif; ?>
                        </div>

                        <!-- MONTHLY RATE -->
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted">
                                MONTHLY RATE (₹) <span class="text-danger">*</span>
                            </label>

                            <input type="number"
                                name="rate"
                                value="<?= htmlspecialchars($rate) ?>"
                                class="form-control bg-light border-0"
                                placeholder="e.g. 1500">

                            <?php if (isset($errors['rate'])): ?>
                                <small class="text-danger">
                                    <?= $errors['rate'] ?>
                                </small>
                            <?php endif; ?>
                        </div>

                        <!-- OVERDUE FINE -->
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted">
                                OVERDUE FINE (₹) <span class="text-danger">*</span>
                            </label>

                            <input type="number"
                                name="overdue_fine"
                                value="<?= htmlspecialchars($overdue_fine) ?>"
                                class="form-control bg-light border-0"
                                placeholder="e.g. 250">

                            <?php if (isset($errors['overdue_fine'])): ?>
                                <small class="text-danger">
                                    <?= $errors['overdue_fine'] ?>
                                </small>
                            <?php endif; ?>
                        </div>

                        <!-- SAVE BUTTON -->
                        <div class="col-12">
                            <button type="submit"
                                name="add_rate"
                                class="btn btn-brand w-100 py-3 mt-3">
                                Save Maintenance Rate
                            </button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>


    <!-- Modern Delete Confirmation Modal -->
    <div class="modal fade" id="deleteRateModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius:18px;">

                <div class="modal-body text-center p-4">

                    <!-- Icon -->
                    <div class="mb-3">
                        <div style="
                        width:70px;
                        height:70px;
                        margin:auto;
                        border-radius:50%;
                        background:#ffeaea;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        font-size:28px;
                        color:#dc3545;
                        box-shadow:0 8px 20px rgba(220,53,69,0.15);
                    ">
                            <i class="fa fa-trash"></i>
                        </div>
                    </div>

                    <!-- Title -->
                    <h5 class="fw-bold mb-2">Delete Maintenance Rate?</h5>

                    <!-- Subtitle -->
                    <p class="text-muted mb-4">
                        This action cannot be undone. Are you sure you want to delete this rate?
                    </p>

                    <!-- Hidden ID -->
                    <input type="hidden" id="delete_rate_id">

                    <!-- Buttons -->
                    <div class="d-flex gap-3 justify-content-center">
                        <button type="button"
                            class="btn btn-light px-4 py-2"
                            data-bs-dismiss="modal"
                            style="border-radius:10px; min-width:110px;">
                            Cancel
                        </button>

                        <button type="button"
                            id="confirmDeleteRateBtn"
                            class="btn btn-danger px-4 py-2"
                            style="
                            border-radius:10px;
                            min-width:110px;
                            box-shadow:0 6px 18px rgba(220,53,69,0.25);
                        ">
                            Yes, Delete
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.2/js/dataTables.bootstrap5.min.js"></script>


    <script>
        $(function() {

            // Open modal on delete button click
            $(document).on('click', '.delete_btn', function() {
                const id = $(this).data('id');
                $('#delete_rate_id').val(id);

                // Show modal
                const deleteModal = new bootstrap.Modal(
                    document.getElementById('deleteRateModal')
                );
                deleteModal.show();
            });

            // Confirm delete
            $('#confirmDeleteRateBtn').on('click', function() {
                const id = $('#delete_rate_id').val();

                // Optional: loading state
                $(this).html('<span class="spinner-border spinner-border-sm me-2"></span>Deleting...');
                $(this).prop('disabled', true);

                // Redirect to delete
                window.location.href = '<?= BASE_URL ?>maintanenceRate.php?action=delete&id=' + id;
            });

        });
    </script>


    <?php if (!empty($errors)): ?>
        <script>
            var myModal = new bootstrap.Modal(document.getElementById('addRateModal'));
            myModal.show();
        </script>
    <?php endif; ?>

</body>

</html>

<?php include('../resources/layout/footer.php'); ?>
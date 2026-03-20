<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

// ACCESS CONTROL 
requireRole(['admin', 'cashier']);


// Handle Add Misc Work (Modal Form)
$errors = [];
$work_title = $worker_name = $contact_number = $amount = $description = '';

if (isset($_POST['add_misc_work'])) {

    $work_title     = trim($_POST['work_title'] ?? '');
    $worker_name    = trim($_POST['worker_name'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $amount         = $_POST['amount'] ?? '';
    $description    = trim($_POST['description'] ?? '');

    $targetMonth = date('n');
    $targetYear  = date('Y');

    // Validation
    if ($work_title === '') $errors['work_title'] = 'Please enter work title';
    if ($worker_name === '') $errors['worker_name'] = 'Please enter worker name';
    if ($contact_number === '') $errors['contact_number'] = 'Please enter contact number';
    elseif (!preg_match('/^[0-9]{10}$/', $contact_number)) $errors['contact_number'] = 'Mobile must be 10 digits';
    if ($amount === '') $errors['amount'] = 'Please enter amount';
    elseif (!is_numeric($amount) || $amount < 0) $errors['amount'] = 'Please enter a valid amount';
    if ($description === '') $errors['description'] = 'Please enter description';

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO miscellaneous_works 
            (work_title, worker_name, contact_number, amount, description, month, year, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'paid')
        ");
        if ($stmt->execute([$work_title, $worker_name, $contact_number, $amount, $description, $targetMonth, $targetYear])) {
            flash_set('success', 'Miscellaneous work added successfully');
            header("Location: miscellaneous_work.php");
            exit;
        } else {
            flash_set('err', 'Database error! Miscellaneous work not added.');
            header("Location: miscellaneous_work.php");
            exit;
        }
    }
}


// DELETE (Admin Only)
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'delete' && is_numeric($_GET['id'])) {
    requireRole(['admin']);
    $stmt = $pdo->prepare("DELETE FROM miscellaneous_works WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    flash_set('success', 'Record deleted successfully');
    header("Location: miscellaneous_work.php");
    exit;
}

include('../resources/layout/header.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Miscellaneous Work</title>

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
                <h1 class="fw-800 m-0">Miscellaneous Work</h1>
                <button class="btn btn-brand btn-sm shadow-sm px-3"
                    data-bs-toggle="modal"
                    data-bs-target="#addMiscModal">
                    <i class="fa-solid fa-plus me-1"></i>
                    <span class="d-none d-sm-inline">Add Expense</span>
                    <span class="d-inline d-sm-none">Add</span>
                </button>
            </div>


            <!-- FILTERS - NEW UI STYLE, OLD LOGIC -->
            <div class="filter-box shadow-sm p-3 mb-4">
                <div class="row g-3 align-items-end">
                    <!-- YEAR -->
                    <div class="col-md-2">
                        <label class="small fw-bold text-muted">YEAR</label>
                        <select id="filter-year" class="form-select border-0 bg-light">
                            <option value="">All Years</option>
                            <?php
                            $currentYear = date('Y');
                            for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
                                echo "<option value='$y'>$y</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- MONTH -->
                    <div class="col-md-2">
                        <label class="small fw-bold text-muted">MONTH</label>
                        <select id="filter-month" class="form-select border-0 bg-light">
                            <option value="">All Months</option>
                            <?php
                            for ($m = 1; $m <= 12; $m++) {
                                echo "<option value='$m'>" . date('F', mktime(0, 0, 0, $m, 1)) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- RESET / APPLY BUTTON -->
                    <div class="col-md-2 d-grid">
                        <button id="reset-filters" class="btn btn-outline-dark fw-bold py-2" style="border-radius:10px;">
                            <i class="fa fa-rotate-left"></i> Reset
                        </button>
                    </div>
                </div>
            </div>


            <div class="data-card shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table table-hover w-100" id="misc-table">
                        <thead>
                            <tr>
                                <th>Month/Year</th>
                                <th>Work Description</th>
                                <th>Vendor/Person</th>
                                <th>Contact</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Paid On</th>
                                <?php if ($_SESSION['user_role'] === 'admin'): ?>
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



    <!-- ADD MISC WORK MODAL -->
    <div class="modal fade" id="addMiscModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius:20px;">
                <div class="modal-header border-0 p-4">
                    <h5 class="modal-title fw-800">Add Miscellaneous Work</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 pt-0">
                    <form method="POST" class="row g-3">

                        <!-- Work Title -->
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted">Work Title <span class="text-danger">*</span></label>
                            <input type="text" name="work_title" value="<?= htmlspecialchars($work_title) ?>" class="form-control bg-light border-0">
                            <?php if (isset($errors['work_title'])): ?><small class="text-danger"><?= $errors['work_title'] ?></small><?php endif; ?>
                        </div>

                        <!-- Worker Name -->
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted">Vendor/Person <span class="text-danger">*</span></label>
                            <input type="text" name="worker_name" value="<?= htmlspecialchars($worker_name) ?>" class="form-control bg-light border-0">
                            <?php if (isset($errors['worker_name'])): ?><small class="text-danger"><?= $errors['worker_name'] ?></small><?php endif; ?>
                        </div>

                        <!-- Contact Number -->
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted">Contact Number <span class="text-danger">*</span></label>
                            <input type="text" name="contact_number" value="<?= htmlspecialchars($contact_number) ?>" class="form-control bg-light border-0">
                            <?php if (isset($errors['contact_number'])): ?><small class="text-danger"><?= $errors['contact_number'] ?></small><?php endif; ?>
                        </div>

                        <!-- Amount -->
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted">Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="amount" value="<?= htmlspecialchars($amount) ?>" class="form-control bg-light border-0">
                            <?php if (isset($errors['amount'])): ?><small class="text-danger"><?= $errors['amount'] ?></small><?php endif; ?>
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">Work Description <span class="text-danger">*</span></label>
                            <textarea name="description" rows="3" class="form-control bg-light border-0"><?= htmlspecialchars($description) ?></textarea>
                            <?php if (isset($errors['description'])): ?><small class="text-danger"><?= $errors['description'] ?></small><?php endif; ?>
                        </div>

                        <div class="col-12">
                            <button type="submit" name="add_misc_work" class="btn btn-brand w-100 py-3 mt-3">Save Work</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>



    <!-- DELETE CONFIRMATION MODAL -->
    <div class="modal fade" id="deleteMiscModal" tabindex="-1">
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
                        🛠️
                    </div>

                    <!-- Title -->
                    <h5 class="fw-bold mb-2 text-danger">Delete Work</h5>

                    <!-- Message -->
                    <p class="text-muted mb-4">
                        Are you sure you want to delete this work?<br>
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

                        <a href="#" id="confirmDeleteMiscBtn"
                            class="btn btn-danger px-4 py-2 fw-bold"
                            style="border-radius:10px;">
                            Yes, Delete
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- JS LIBRARIES -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>


    <script>
        $(document).ready(function() {
            let columns = [{
                    data: 'month_year'
                },
                {
                    data: 'work_title'
                },
                {
                    data: 'worker_name'
                },
                {
                    data: 'contact_number'
                },
                {
                    data: 'amount'
                },
                {
                    data: 'status'
                },
                {
                    data: 'paid_on'
                }
            ];
            <?php if ($_SESSION['user_role'] === 'admin') { ?>
                columns.push({
                    data: null,
                    orderable: false,
                    render: d => `<button class="btn btn-danger btn-sm delete_btn" data-id="${d.id}">Delete</button>`
                });
            <?php } ?>

            let table = $('#misc-table').DataTable({
                dom: '<"d-flex justify-content-between mb-4"lf>rt<"d-flex justify-content-between mt-4"ip>',
                processing: true,
                serverSide: true,
                searching: false,
                pageLength: 5,
                lengthMenu: [5, 10, 25, 50],
                language: {
                    search: "",
                    searchPlaceholder: "Search records..."
                },
                ajax: {
                    url: '<?= BASE_URL ?>action.php',
                    type: 'POST',
                    data: d => {
                        d.action = 'fetch_misc_works';
                        d.month = $('#filter-month').val();
                        d.year = $('#filter-year').val();
                    }
                },
                columns: columns
            });

            $('#filter-month,#filter-year').change(() => table.ajax.reload());
            $('#reset-filters').click(() => {
                $('#filter-month,#filter-year').val('');
                table.ajax.reload();
            });

            $(document).on('click', '.delete_btn', function() {
                var id = $(this).data('id');
                $('#confirmDeleteMiscBtn').attr('href', 'miscellaneous_work.php?action=delete&id=' + id);
                var deleteModal = new bootstrap.Modal(document.getElementById('deleteMiscModal'));
                deleteModal.show();
            });


        });
    </script>

    <?php if (!empty($errors)) : ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var addMiscModal = new bootstrap.Modal(document.getElementById('addMiscModal'));
                addMiscModal.show();
            });
        </script>
    <?php endif; ?>

</body>

</html>


<?php include('../resources/layout/footer.php'); ?>
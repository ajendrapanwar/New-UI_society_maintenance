<?php
include('../core/config.php');
requireRole(['admin', 'cashier']);

// DELETE (Admin Only)
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'delete' && is_numeric($_GET['id'])) {
    requireRole(['admin']);
    $stmt = $pdo->prepare("DELETE FROM miscellaneous_works WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $_SESSION['success'] = "Record deleted successfully";
    header("Location: miscellaneous_work.php");
    exit;
}

include('../resources/layout/header.php');
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Miscellaneous Work</h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Miscellaneous Work</li>
    </ol>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col-6">
                    <h5 class="card-title">Miscellaneous Work List</h5>
                </div>
                <div class="col-6 text-end">
                    <a href="<?= BASE_URL ?>add/add_miscellaneous_work.php" class="btn btn-success btn-sm">Add Miscellaneous Work</a>
                </div>
            </div>
        </div>

        <!-- FILTERS -->
        <div class="card-body py-3">
            <div class="row align-items-end g-3">
                <div class="col-md-2 col-sm-6">
                    <label class="filter-label">Month</label>
                    <select id="filter-month" class="form-select form-select-sm">
                        <option value="">All Months</option>
                        <?php
                        for ($m = 1; $m <= 12; $m++) {
                            echo "<option value='$m'>" . date('F', mktime(0, 0, 0, $m, 1)) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-2 col-sm-6">
                    <label class="filter-label">Year</label>
                    <select id="filter-year" class="form-select form-select-sm">
                        <option value="">All Years</option>
                        <?php
                        $currentYear = date('Y');
                        for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
                            echo "<option value='$y'>$y</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-4 col-md-2 col-sm-3 d-grid">
                    <button id="reset-filters" class="btn btn-outline-dark btn-sm">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </button>
                </div>

                <!-- EXPORT BUTTON (RIGHT SIDE) -->
                <div class="col-4 col-md-2 col-sm-3 d-grid ms-auto">
                    <button id="export-excel" class="btn btn-dark btn-sm">
                        <i class="bi bi-file-earmark-excel"></i> Export Excel
                    </button>
                </div>

            </div>
        </div>


        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="misc-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Month/Year</th>
                            <th>Work Title</th>
                            <th>Description</th>
                            <th>Worker Name</th>
                            <th>Contact</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <!-- ACTION COLUMN: Only show if Admin -->
                            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                <th width="100">Action</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- DataTables CSS & JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.2/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.2/js/dataTables.bootstrap5.min.js"></script>

<?php include('../resources/layout/footer.php'); ?>

<script>
    $(document).ready(function() {

        let columns = [{
                data: 'id'
            },
            {
                data: 'month_year'
            },
            {
                data: 'work_title'
            },
            {
                data: 'description'
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
                data: 'created_at'
            }
        ];

        <?php if ($_SESSION['user_role'] == 'admin') { ?>
            columns.push({
                data: null,
                orderable: false,
                render: function(d) {
                    return `<button class="btn btn-danger btn-sm delete_btn" data-id="${d.id}">Delete</button>`;
                }
            });
        <?php } ?>

        let table = $('#misc-table').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            ajax: {
                url: '<?= BASE_URL ?>action.php',
                type: 'POST',
                data: function(d) {
                    d.action = 'fetch_misc_works';
                    d.month = $('#filter-month').val();
                    d.year = $('#filter-year').val();
                }
            },
            columns: columns
        });

        $('#filter-month, #filter-year').change(function() {
            table.ajax.reload();
        });

        $('#reset-filters').click(function() {
            $('#filter-month,#filter-year').val('');
            table.ajax.reload();
        });

        $(document).on('click', '.delete_btn', function() {
            if (confirm('Delete this record?')) {
                window.location = 'miscellaneous_work.php?action=delete&id=' + $(this).data('id');
            }
        });

        $('#export-excel').click(function() {
            let month = $('#filter-month').val();
            let year = $('#filter-year').val();
            window.location = '<?= BASE_URL ?>action.php?action=export_misc_work&month=' + month + '&year=' + year;
        });

    });
</script>
<?php
require_once __DIR__ . '/../core/config.php';

/* ================= ACCESS CONTROL ================= */
requireRole(['admin', 'cashier']);

include __DIR__ . '/../resources/layout/header.php';
?>

<style>
    table.dataTable td {
        vertical-align: middle !important;
        white-space: nowrap;
    }
</style>

<div class="container-fluid px-4">
    <h1 class="mt-4">Sweeper Bills</h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Sweeper Bills</li>
    </ol>


    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <!-- TABLE -->
    <div class="card">

        <div class="card-header">
            <div class="row">
                <div class="col-6">
                    <h5 class="card-title">Sweeper</h5>
                </div>
                <div class="col-6 text-end">
                    <a href="<?= BASE_URL ?>sweeper.php" class="btn btn-primary btn-sm">View Sweeper</a>
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
                <div class="col-md-2 col-sm-6">
                    <label class="filter-label">Status</label>
                    <select id="filter-status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="paid">Paid</option>
                        <option value="unpaid">Unpaid</option>
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
            <table id="sweeper-salary-table" class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Month / Year</th>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>DOB</th>
                        <th>Salary</th>
                        <th>Status</th>
                        <th>Paid On</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../resources/layout/footer.php'; ?>

<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.2/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.2/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(function() {

        let table = $('#sweeper-salary-table').DataTable({
            processing: true,
            serverSide: true,
            searching: false,

            ajax: {
                url: "<?= BASE_URL ?>action.php",
                type: "POST",
                data: function(d) {
                    d.action = "fetch_sweeper_salary";
                    d.month = $('#filter-month').val();
                    d.year = $('#filter-year').val();
                    d.status = $('#filter-status').val();
                }
            },
            columns: [{
                    data: "month_year"
                },
                {
                    data: "name"
                },
                {
                    data: "mobile"
                },
                {
                    data: "dob"
                },
                {
                    data: "salary"
                },
                {
                    data: "status"
                },
                {
                    data: "paid_on"
                },
                {
                    data: "action"
                }
            ]
        });

        $('#filter-month,#filter-year,#filter-status').change(() => table.ajax.reload());
        $('#reset-filters').click(() => {
            $('#filter-month,#filter-year,#filter-status').val('');
            table.ajax.reload();
        });

        $(document).on('click', '.pay-salary', function() {
            let id = $(this).data('id');
            if (!confirm("Mark salary PAID?")) return;

            $.post("<?= BASE_URL ?>action.php", {
                action: "mark_sweeper_salary_paid",
                id: id
            }, function() {
                alert("Salary Paid");
                table.ajax.reload();
            });
        });

    });

    // EXPORT
    $('#export-excel').click(function() {
        let m = $('#filter-month').val();
        let y = $('#filter-year').val();
        let s = $('#filter-status').val();
        window.location.href = "<?= BASE_URL ?>action.php?action=export_sweeper_salary&month=" + m + "&year=" + y + "&status=" + s;
    });
</script>
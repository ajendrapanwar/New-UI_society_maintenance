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
    <h1 class="mt-4">All Maintenance Bills</h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item">
            <a href="<?= BASE_URL ?>dashboard.php">Dashboard</a>
        </li>
        <li class="breadcrumb-item active">All Bills</li>
    </ol>

    <!-- TABLE -->
    <div class="card">

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
                        <option value="pending">Pending</option>
                        <option value="overdue">Overdue</option>
                    </select>
                </div>

                <div class="col-md-2 col-sm-6 d-grid">
                    <button id="reset-filters" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </button>
                </div>

                <!-- EXPORT BUTTON (RIGHT SIDE) -->
                <div class="col-4 col-md-2 col-sm-3 d-grid ms-auto">
                    <button id="export-excel" class="btn btn-success btn-sm">
                        <i class="bi bi-file-earmark-excel"></i> Export Excel
                    </button>
                </div>

            </div>
        </div>



        <div class="card-body">
            <div class="table-responsive">
                <table id="bills-table" class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Flat</th>
                            <th>Block</th>
                            <th>Month / Year</th>
                            <th>Amount</th>
                            <th>Fine</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Payment Mode</th>
                            <th>Paid On</th>
                            <th>Overdue</th>
                        </tr>
                    </thead>
                </table>
            </div>
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

        let table = $('#bills-table').DataTable({
            processing: true,
            serverSide: true,
            searching: false, // ✅ DISABLE SEARCH BOX
            pageLength: 10,
            order: [
                [2, 'desc']
            ],

            ajax: {
                url: '<?= BASE_URL ?>action.php',
                type: 'POST',
                data: function(d) {
                    d.action = 'fetch_all_bills';
                    d.month = $('#filter-month').val();
                    d.year = $('#filter-year').val();
                    d.status = $('#filter-status').val();
                }
            },

            columns: [{
                    data: 'flat_number'
                },
                {
                    data: 'block_number'
                },
                {
                    data: 'month_year'
                },
                {
                    data: 'amount'
                },
                {
                    data: 'fine'
                },
                {
                    data: 'total'
                },
                {
                    data: 'status'
                },
                {
                    data: 'payment_mode'
                },
                {
                    data: 'paid_on'
                },
                {
                    data: 'overdue'
                }
            ]
        });

        $('#filter-month, #filter-year, #filter-status').on('change', function() {
            table.ajax.reload();
        });

        $('#reset-filters').on('click', function() {
            $('#filter-month, #filter-year, #filter-status').val('');
            table.ajax.reload();
        });

    });
</script>


<script>
$('#export-excel').on('click', function () {

    let month  = $('#filter-month').val();
    let year   = $('#filter-year').val();
    let status = $('#filter-status').val();

    let url = '<?= BASE_URL ?>action.php?action=export_all_bills'
        + '&month=' + month
        + '&year=' + year
        + '&status=' + status;

    window.location.href = url; // triggers download
});
</script>

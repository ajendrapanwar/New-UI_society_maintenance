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

    .badge {
        font-size: 12px;
        font-weight: bold;
        padding: 6px 12px;
    }
</style>

<div class="container-fluid px-4">
    <h1 class="mt-4">All Expense Bills</h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">All Expense Bills</li>
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
                    <h5 class="card-title">Expense Bills List</h5>
                </div>
                <div class="col-6 text-end">
                    <button id="export-excel" class="btn btn-dark btn-sm">
                        <i class="bi bi-file-earmark-excel"></i> Export Excel
                    </button>
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
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>"><?= date('F', mktime(0,0,0,$m,1)) ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-md-2 col-sm-6">
                    <label class="filter-label">Year</label>
                    <select id="filter-year" class="form-select form-select-sm">
                        <option value="">All Years</option>
                        <?php $currentYear = date('Y'); ?>
                        <?php for ($y = $currentYear; $y >= $currentYear - 5; $y--): ?>
                            <option value="<?= $y ?>"><?= $y ?></option>
                        <?php endfor; ?>
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
            </div>

            <!-- Totals -->
            <div class="row mt-5 text-center text-md-start">
                <div class="col-md-4 col-12 mb-2">
                    <strong>Grand Total:</strong> <span id="grandTotal"></span>
                </div>
                <div class="col-md-4 col-12 mb-2 text-success">
                    <strong>Total Paid Expense:</strong> <span id="paidTotal"></span>
                </div>
                <div class="col-md-4 col-12 mb-2 text-danger">
                    <strong>Total Unpaid Expense:</strong> <span id="pendingTotal"></span>
                </div>
            </div>

        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="expense-table" class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Month/Year</th>
                            <th>Name/Reading</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Source Table</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../resources/layout/footer.php'; ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.2/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.2/js/dataTables.bootstrap5.min.js"></script>

<script>
$(function() {

    let table = $('#expense-table').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        pageLength: 10,
        order: [[1,'desc']],
        ajax: {
            url: '<?= BASE_URL ?>action.php',
            type: 'POST',
            data: function(d) {
                d.action = 'fetch_all_expenses';
                d.month = $('#filter-month').val();
                d.year = $('#filter-year').val();
                d.status = $('#filter-status').val();
            },
            dataSrc: function(json) {
                // Update totals
                $('#grandTotal').text('₹' + Number(json.grandTotal).toLocaleString('en-IN', {minimumFractionDigits:2}));
                $('#paidTotal').text('₹' + Number(json.paidTotal).toLocaleString('en-IN', {minimumFractionDigits:2}));
                $('#pendingTotal').text('₹' + Number(json.unpaidTotal).toLocaleString('en-IN', {minimumFractionDigits:2}));
                return json.data;
            }
        },
        columns: [
            { data: 'id' },
            { data: 'month_year' },
            { data: 'name' },
            { data: 'amount', render: $.fn.dataTable.render.number(',', '.', 2, '₹') },
            { data: 'status' },
            { data: 'source' }
        ]
    });

    $('#filter-month, #filter-year, #filter-status').on('change', function() {
        table.ajax.reload();
    });

    $('#reset-filters').on('click', function() {
        $('#filter-month,#filter-year,#filter-status').val('');
        table.ajax.reload();
    });

    $('#export-excel').on('click', function() {
        let month = $('#filter-month').val();
        let year = $('#filter-year').val();
        let status = $('#filter-status').val();

        let url = '<?= BASE_URL ?>action.php?action=export_expense_bills' +
                  '&month=' + month + '&year=' + year + '&status=' + status;
        window.location.href = url;
    });

});
</script>

<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once __DIR__ . '/../core/config.php';
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
    <h1 class="mt-4">Electricity Bills</h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Electricity Bills</li>
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
                    <h5 class="card-title">Electricity Bills Lists</h5>
                </div>
                <div class="col-6 text-end">
                    <button id="export-excel" class="btn btn-dark btn-sm">
                        <i class="bi bi-file-earmark-excel"></i> Export Excel
                    </button>
                    <a href="<?= BASE_URL ?>add/add_electricity_bill.php" class="btn btn-success btn-sm">
                        Add Electricity Bills
                    </a>
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
                        <option value="pending">Pending</option>
                        <option value="overdue">Overdue</option>
                    </select>
                </div>

                <div class="col-4 col-md-2 col-sm-3 d-grid">
                    <button id="reset-filters" class="btn btn-outline-dark btn-sm">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </button>
                </div>
            </div>
        </div>


        <div class="container-fluid px-4">
            <table id="electricityTable" class="table table-bordered">
                <thead>
                    <tr>
                        <th>Month/Year</th>
                        <th>Reading</th>
                        <th>Total Amount</th>
                        <th>Paid</th>
                        <th>Pending</th>
                        <th>Status</th>
                        <th>Last Paid</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>

        </div>


    </div>
</div>


<!-- PAY MODAL -->
<div class="modal fade" id="payBillModal">
    <div class="modal-dialog modal-md">
        <form id="payBillForm">

            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Pay Electricity Bill</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" name="bill_id" id="bill_id">
                    <input type="hidden" id="total_amount_hidden">
                    <input type="hidden" id="paid_amount_hidden">

                    <div class="mb-2">
                        <label>Total Amount</label>
                        <input type="text" id="total_amount" class="form-control" readonly>
                    </div>

                    <div class="mb-2">
                        <label>Already Paid</label>
                        <input type="text" id="already_paid" class="form-control" readonly>
                    </div>

                    <div class="mb-2">
                        <label>Pending Amount</label>
                        <input type="text" id="pending_amount" class="form-control" readonly>
                    </div>

                    <div class="mb-2">
                        <label>Pay Amount</label>
                        <input type="number" step="0.01" name="paid_amount" id="pay_amount" class="form-control" required>
                    </div>

                    <label class="mt-2">Payment Mode</label>
                    <select name="payment_mode" class="form-control">
                        <option value="cash">Cash</option>
                        <option value="online">Online</option>
                    </select>

                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Pay Now</button>
                </div>

            </div>

        </form>
    </div>
</div>



<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.2/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.2/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(function() {

        let table = $('#electricityTable').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            ajax: {
                url: '<?= BASE_URL ?>action.php',
                type: 'POST',
                data: function(d) {
                    d.action = 'fetch_electricity_bills';
                    d.month = $('#filter-month').val();
                    d.year = $('#filter-year').val();
                    d.status = $('#filter-status').val();
                }
            },
            columns: [{
                    data: 'month_year'
                },
                {
                    data: 'reading'
                },
                {
                    data: 'amount'
                },
                {
                    data: 'paid'
                },
                {
                    data: 'pending'
                },
                {
                    data: 'status'
                },
                {
                    data: 'last_paid'
                },
                {
                    data: 'action'
                }
            ]
        });

        // Filter change
        $('#filter-month, #filter-year, #filter-status').change(function() {
            table.ajax.reload();
        });

        // Reset filters
        $('#reset-filters').click(function() {
            $('#filter-month, #filter-year, #filter-status').val('');
            table.ajax.reload();
        });

        // Open modal
        $(document).on('click', '.pay-bill', function() {
            let id = $(this).data('id');
            $('#bill_id').val(id);

            $.post('<?= BASE_URL ?>action.php', {
                action: 'get_electricity_bill',
                id: id
            }, function(res) {

                let total = parseFloat(res.amount);
                let paid = parseFloat(res.paid_amount);
                let pending = parseFloat(res.pending);

                $('#total_amount').val('₹ ' + total.toFixed(2));
                $('#already_paid').val('₹ ' + paid.toFixed(2));
                $('#pending_amount').val('₹ ' + pending.toFixed(2));

                $('#total_amount_hidden').val(total);
                $('#paid_amount_hidden').val(paid);

                $('#pay_amount').val('');
                $('#payBillModal').modal('show');

            }, 'json');
        });


        // Submit payment
        $('#payBillForm').submit(function(e) {
            e.preventDefault();

            let pay = parseFloat($('#pay_amount').val());
            let total = parseFloat($('#total_amount_hidden').val());
            let paid = parseFloat($('#paid_amount_hidden').val());
            let pending = total - paid;

            if (pay > pending) {
                alert("You cannot pay more than pending amount!");
                return false;
            }

            if (paid + pay > total) {
                alert("Payment exceeds total bill amount!");
                return false;
            }

            $.post(
                '<?= BASE_URL ?>action.php',
                $(this).serialize() + '&action=pay_electricity_bill',
                function() {
                    $('#payBillModal').modal('hide');
                    table.ajax.reload(null, false);
                }
            );
        });

        // Export Excel
        $('#export-excel').click(function() {
            let month = $('#filter-month').val();
            let year = $('#filter-year').val();
            let status = $('#filter-status').val();

            window.location.href = '<?= BASE_URL ?>action.php?action=electricity_bills_export_excel' +
                '&month=' + month +
                '&year=' + year +
                '&status=' + status;
        });


    });
</script>


<?php include __DIR__ . '/../resources/layout/footer.php'; ?>
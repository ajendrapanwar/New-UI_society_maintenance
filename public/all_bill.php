<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

/* ================= ACCESS CONTROL ================= */
requireRole(['admin', 'cashier']);

include __DIR__ . '/../resources/layout/header.php';
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collection Report | SocietyOS</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">

    <style>
        table.dataTable td {
            vertical-align: middle !important;
            white-space: nowrap;
        }
    </style>

</head>

<body>

    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <div class="main-wrapper">

        <main id="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="fw-800 m-0">Collection Analysis</h1>
                <button class="btn btn-outline-dark shadow-sm" id="export-excel">
                    <i class="bi bi-file-earmark-excel"></i> Export Excel
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

                    <!-- STATUS -->
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted">STATUS</label>
                        <select id="filter-status" class="form-select border-0 bg-light">
                            <option value="">All Status</option>
                            <option value="paid" class="text-success">Paid</option>
                            <option value="pending" class="text-danger">Pending</option>
                            <option value="overdue" class="text-danger">Overdue</option>
                        </select>
                    </div>

                    <!-- PAYMENT MODE -->
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted">PAYMENT MODE</label>
                        <select id="filter-payment-mode" class="form-select border-0 bg-light">
                            <option value="">All Modes</option>
                            <option value="cash">Cash</option>
                            <option value="online">Online</option>
                        </select>
                    </div>

                    <!-- RESET / APPLY BUTTON -->
                    <div class="col-md-2 d-grid">
                        <button id="reset-filters" class="btn btn-outline-dark fw-bold py-2" style="border-radius:10px;">
                            <i class="fa fa-rotate-left"></i> Reset
                        </button>
                    </div>
                </div>

                <!-- Totals -->
                <div class="row mt-4 text-center text-md-start">
                    <div class="col-md-4 col-12 mb-2">
                        <strong>Grand Total:</strong>
                        <span id="grandTotal"></span>
                    </div>
                    <div class="col-md-4 col-12 mb-2 text-success">
                        <strong>Total Paid:</strong>
                        <span id="paidTotal"></span>
                    </div>
                    <div class="col-md-4 col-12 mb-2 text-danger">
                        <strong>Total Pending/Overdue:</strong>
                        <span id="pendingTotal"></span>
                    </div>
                </div>
            </div>


            <div class="data-card border-0 shadow-sm">
                <table id="bills-table" class="table table-hover datatable w-100">
                    <thead>
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
                    <tbody>
                    </tbody>
                </table>
            </div>
        </main>
    </div>


    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.2/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.2/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {

            // Check if DataTable is already initialized
            if ($.fn.DataTable.isDataTable('#bills-table')) {
                $('#bills-table').DataTable().destroy();
            }

            // Initialize DataTable
            let table = $('#bills-table').DataTable({
                processing: true,
                serverSide: true,
                searching: false,
                pageLength: 5,
                lengthMenu: [5, 10, 25, 50],
                order: [
                    [2, 'desc']
                ], // default order by month_year descending
                ajax: {
                    url: '<?= BASE_URL ?>action.php',
                    type: 'POST',
                    data: function(d) {
                        d.action = 'fetch_all_bills';
                        d.month = $('#filter-month').val();
                        d.year = $('#filter-year').val();
                        d.status = $('#filter-status').val();
                        d.payment_mode = $('#filter-payment-mode').val();
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

            // Filter change events
            $('#filter-month, #filter-year, #filter-status, #filter-payment-mode').on('change', function() {
                table.ajax.reload(null, false); // reload without resetting pagination
                loadTotals();
            });

            // Reset filters
            $('#reset-filters').on('click', function() {
                $('#filter-month, #filter-year, #filter-status, #filter-payment-mode').val('');
                table.ajax.reload(null, false);
                loadTotals();
            });

            // Load totals on page load
            loadTotals();

            // Function to load totals
            function loadTotals() {
                $.ajax({
                    url: '<?= BASE_URL ?>action.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'fetch_bill_totals',
                        month: $('#filter-month').val(),
                        year: $('#filter-year').val(),
                        status: $('#filter-status').val(),
                        payment_mode: $('#filter-payment-mode').val()
                    },
                    success: function(res) {
                        $('#grandTotal').text('₹' + res.grandTotal);
                        $('#paidTotal').text('₹' + res.paidTotal);
                        $('#pendingTotal').text('₹' + res.pendingTotal);
                    },
                    error: function(err) {
                        console.error("Failed to fetch totals:", err);
                    }
                });
            }

            $('#export-excel').on('click', function() {

                let month = $('#filter-month').val();
                let year = $('#filter-year').val();
                let status = $('#filter-status').val();
                let payment_mode = $('#filter-payment-mode').val();

                let url = '<?= BASE_URL ?>action.php?action=export_all_maintenance_bills' +
                    '&month=' + month +
                    '&year=' + year +
                    '&status=' + status +
                    '&payment_mode=' + payment_mode;

                window.location.href = url;
            });

        });
    </script>

</body>

</html>

<?php include __DIR__ . '/../resources/layout/footer.php'; ?>
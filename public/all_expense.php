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
    <title>Expense Report | SocietyOS</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">


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

</head>

<body>


    <div class="main-wrapper">

        <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

        <main id="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="fw-800 m-0">Expense Report</h1>
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

                    <!-- CATEGORY -->
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted">CATEGORY</label>
                        <select id="filter-category" class="form-select border-0 bg-light py-2">
                            <option value="">All Expenses</option>
                            <option value="electricity_bills">Electricity Bills</option>
                            <option value="miscellaneous_works">Misc Repairs</option>
                            <option value="sweeper_salary">Sweeper Salary</option>
                            <option value="guard_salary">Guard Salary</option>
                            <option value="garbage_salary">Garbage Salary</option>
                        </select>
                    </div>

                    <!-- RESET -->
                    <div class="col-md-2 d-grid">
                        <button id="reset-filters" class="btn btn-outline-dark fw-bold py-2" style="border-radius:10px;">
                            <i class="fa fa-rotate-left"></i> Reset
                        </button>
                    </div>
                </div>

            </div>


            <div class="data-card border-0 shadow-sm">
                <div class="table-responsive">
                    <table id="expense-table" class="table table-hover align-middle">
                        <thead class="text-muted small uppercase">
                            <tr>
                                <th>ID</th>
                                <th>Month/Year</th>
                                <th>Name/Reading</th>
                                <th>Expense Type</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Paid On</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>



    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.2/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.2/js/dataTables.bootstrap5.min.js"></script>


    <script>
        $(function() {

            let table = $('#expense-table').DataTable({
                processing: true,
                serverSide: true,
                searching: false,
                pageLength: 5,
                lengthMenu: [5, 10, 25, 50],
                order: [
                    [2, 'desc']
                ],

                ajax: {
                    url: '<?= BASE_URL ?>action.php',
                    type: 'POST',
                    data: function(d) {
                        d.action = 'fetch_all_expenses';
                        d.month = $('#filter-month').val();
                        d.year = $('#filter-year').val();
                        d.status = $('#filter-status').val();
                        d.category = $('#filter-category').val(); // <-- ADD THIS LINE
                    }
                },

                columns: [{
                        data: 'id'
                    },
                    {
                        data: 'month_year'
                    },
                    {
                        data: 'name'
                    },
                    {
                        data: 'source'
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
                ]
            });

            // Include category in filter reload
            $('#filter-month, #filter-year, #filter-status, #filter-category').on('change', function() {
                table.ajax.reload();
                loadExpenseTotals();
            });

            $('#reset-filters').on('click', function() {
                $('#filter-month,#filter-year,#filter-status,#filter-category').val(''); // reset category too
                table.ajax.reload();
                loadExpenseTotals();
            });

            loadExpenseTotals();
        });

        // Update totals AJAX to include category
        function loadExpenseTotals() {
            $.ajax({
                url: '<?= BASE_URL ?>action.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'fetch_expense_totals',
                    month: $('#filter-month').val(),
                    year: $('#filter-year').val(),
                    status: $('#filter-status').val(),
                    category: $('#filter-category').val() // <-- ADD THIS LINE
                },
                success: function(res) {
                    $('#grandTotal').text('₹' + res.grandTotal);
                    $('#paidTotal').text('₹' + res.paidTotal);
                    $('#pendingTotal').text('₹' + res.unpaidTotal);
                }
            });
        }

        // EXPORT EXCEL with category
        $('#export-excel').on('click', function() {
            let month = $('#filter-month').val();
            let year = $('#filter-year').val();
            let status = $('#filter-status').val();
            let category = $('#filter-category').val(); 

            let url = '<?= BASE_URL ?>action.php?action=export_expense_bills' +
                '&month=' + month +
                '&year=' + year +
                '&status=' + status +
                '&category=' + category; // <-- ADD THIS LINE

            window.location.href = url;
        });


        // ===== LOAD TOTALS LIKE MAINTENANCE =====
        function loadExpenseTotals() {

            $.ajax({
                url: '<?= BASE_URL ?>action.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'fetch_expense_totals',
                    month: $('#filter-month').val(),
                    year: $('#filter-year').val(),
                    status: $('#filter-status').val()
                },
                success: function(res) {
                    $('#grandTotal').text('₹' + res.grandTotal);
                    $('#paidTotal').text('₹' + res.paidTotal);
                    $('#pendingTotal').text('₹' + res.unpaidTotal);
                }
            });
        }
    </script>

</body>

</html>

<?php include __DIR__ . '/../resources/layout/footer.php'; ?>
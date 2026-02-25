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
                <button class="btn btn-outline-dark fw-bold px-4" style="border-radius:10px;">
                    <i class="fa-solid fa-file-excel me-2"></i> Export CSV
                </button>
            </div>

            <div class="filter-box shadow-sm">
                <form class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted">CATEGORY</label>
                        <select class="form-select border-0 bg-light py-2">
                            <option>All Expenses</option>
                            <option>Staff Salary</option>
                            <option>Utility Bills</option>
                            <option>Misc Repairs</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted">PAYMENT STATUS</label>
                        <select class="form-select border-0 bg-light py-2">
                            <option>All Status</option>
                            <option>Paid / Cleared</option>
                            <option>Unpaid / Pending</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted">MONTH/YEAR</label>
                        <input type="month" class="form-control border-0 bg-light py-2" value="2026-02">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold" style="background:var(--brand); border-radius:10px; border:none;">Update Report</button>
                    </div>
                </form>
            </div>

            <div class="data-card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="text-muted small uppercase">
                            <tr>
                                <th>Date</th>
                                <th>Expense Details</th>
                                <th>Category</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th class="text-end">Ref. No</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>18 Feb 2026</td>
                                <td><span class="fw-bold">Electrician - Block B Wiring</span></td>
                                <td><span class="badge bg-light text-dark">Misc Work</span></td>
                                <td class="fw-bold">₹4,200</td>
                                <td><span class="status-badge status-paid">Paid</span></td>
                                <td class="text-end text-muted">#EXP-101</td>
                            </tr>
                            <tr>
                                <td>20 Feb 2026</td>
                                <td><span class="fw-bold">Monthly Garbage Collection</span></td>
                                <td><span class="badge bg-light text-dark">Staff Salary</span></td>
                                <td class="fw-bold">₹8,000</td>
                                <td><span class="status-badge status-unpaid">Unpaid</span></td>
                                <td class="text-end text-muted">#EXP-102</td>
                            </tr>
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

            $('#filter-month, #filter-year, #filter-status').on('change', function() {
                table.ajax.reload();
                loadExpenseTotals();
            });

            $('#reset-filters').on('click', function() {
                $('#filter-month,#filter-year,#filter-status').val('');
                table.ajax.reload();
                loadExpenseTotals();
            });

            // Load totals on page load
            loadExpenseTotals();
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


        // EXPORT
        $('#export-excel').on('click', function() {

            let month = $('#filter-month').val();
            let year = $('#filter-year').val();
            let status = $('#filter-status').val();

            let url = '<?= BASE_URL ?>action.php?action=export_expense_bills' +
                '&month=' + month +
                '&year=' + year +
                '&status=' + status;

            window.location.href = url;
        });
    </script>



</body>

</html>

<?php include __DIR__ . '/../resources/layout/footer.php'; ?>
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
            <h1 class="fw-800 mb-4">Collection Analysis</h1>

            <div class="filter-box shadow-sm">
                <form class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="small fw-bold text-muted">YEAR</label>
                        <select class="form-select border-0 bg-light">
                            <option>2026</option>
                            <option>2025</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold text-muted">MONTH</label>
                        <select class="form-select border-0 bg-light">
                            <option>February</option>
                            <option>January</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted">STATUS</label>
                        <select class="form-select border-0 bg-light">
                            <option>All Status</option>
                            <option class="text-success">Paid</option>
                            <option class="text-danger">Pending</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted">FLAT TYPE</label>
                        <select class="form-select border-0 bg-light">
                            <option>All Types</option>
                            <option>1 BHK</option>
                            <option>2 BHK</option>
                            <option>3 BHK</option>
                            <option>Villa</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100 py-2 fw-bold" style="border-radius:10px;">Apply Filters</button>
                    </div>
                </form>
            </div>

            <div class="data-card border-0 shadow-sm">
                <table class="table table-hover datatable w-100">
                    <thead>
                        <tr>
                            <th>Receipt No</th>
                            <th>Flat</th>
                            <th>Resident</th>
                            <th>Month</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>#REC-992</td>
                            <td>A-402</td>
                            <td>Rajesh Kumar</td>
                            <td>Feb 2026</td>
                            <td>₹3,500</td>
                            <td><span class="badge bg-success">Paid</span></td>
                        </tr>
                        <tr>
                            <td>#REC-993</td>
                            <td>B-101</td>
                            <td>Sanjeev Sharma</td>
                            <td>Feb 2026</td>
                            <td>₹2,500</td>
                            <td><span class="badge bg-warning">Pending</span></td>
                        </tr>
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
        $(function() {

            let table = $('#bills-table').DataTable({
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
                loadTotals();
            });

            $('#reset-filters').on('click', function() {
                $('#filter-month, #filter-year, #filter-status').val('');
                table.ajax.reload();
                loadTotals();
            });

            // Load totals on page load
            loadTotals();


        });


        function loadTotals() {

            $.ajax({
                url: '<?= BASE_URL ?>action.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'fetch_bill_totals',
                    month: $('#filter-month').val(),
                    year: $('#filter-year').val(),
                    status: $('#filter-status').val()
                },
                success: function(res) {
                    $('#grandTotal').text('₹' + res.grandTotal);
                    $('#paidTotal').text('₹' + res.paidTotal);
                    $('#pendingTotal').text('₹' + res.pendingTotal);
                }
            });
        }



        $('#export-excel').on('click', function() {

            let month = $('#filter-month').val();
            let year = $('#filter-year').val();
            let status = $('#filter-status').val();

            let url = '<?= BASE_URL ?>action.php?action=export_all_maintenance_bills' +
                '&month=' + month +
                '&year=' + year +
                '&status=' + status;

            window.location.href = url;
        });
    </script>




</body>

</html>

<?php include __DIR__ . '/../resources/layout/footer.php'; ?>
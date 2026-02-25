<?php
require_once __DIR__ . '/../core/config.php';

/* ================= ACCESS CONTROL ================= */
requireRole(['admin', 'cashier']);

include __DIR__ . '/../resources/layout/header.php';
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sweeper Salary Management</title>

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

    <div class="main-wrapper">

        <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

        <main id="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="fw-800 m-0">Sweeper Payroll</h1>
                <button class="btn btn-brand shadow-sm" data-bs-toggle="modal" data-bs-target="#paySweeperModal">
                    <i class="fa-solid fa-hand-holding-dollar me-2"></i> Process Payment
                </button>
            </div>

            <div class="data-card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover datatable w-100 align-middle">
                        <thead>
                            <tr>
                                <th>Staff Name</th>
                                <th>Month</th>
                                <th>Base Salary</th>
                                <th>Leaves</th>
                                <th>Deductions</th>
                                <th>Net Payable</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="fw-bold">Ravi Kumar</span></td>
                                <td>Feb 2026</td>
                                <td>₹9,000</td>
                                <td>2 Days</td>
                                <td class="text-danger">- ₹500</td>
                                <td class="fw-bold">₹8,500</td>
                                <td><span class="status-paid">Paid</span></td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-light border"><i class="fa fa-receipt"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="fw-bold">Sita Devi</span></td>
                                <td>Feb 2026</td>
                                <td>₹9,000</td>
                                <td>0 Days</td>
                                <td>₹0</td>
                                <td class="fw-bold">₹9,000</td>
                                <td><span class="status-unpaid">Pending</span></td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-brand">Pay Now</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
        
    </div>


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
                pageLength: 5,
                lengthMenu: [5, 10, 25, 50],
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


</body>

</html>

<?php include __DIR__ . '/../resources/layout/footer.php'; ?>
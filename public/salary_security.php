<?php
require_once __DIR__ . '/../core/config.php';
requireRole(['admin', 'cashier']);

include __DIR__ . '/../resources/layout/header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Payroll</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables -->
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
                <h1 class="fw-800 m-0">Security Payroll</h1>
                <div class="col-6 text-end">
                    <a href="<?= BASE_URL ?>guards.php" class="btn btn-brand shadow-sm">
                        <i class="fa fa-eye me-1"></i> Guard Staffs
                    </a>
                </div>
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
                            <option value="unpaid" class="text-danger">Unpaid</option>
                        </select>
                    </div>

                    <!-- RESET / APPLY BUTTON -->
                    <div class="col-md-2 d-grid">
                        <button id="reset-filters" class="btn btn-outline-dark fw-bold py-2" style="border-radius:10px;">
                            <i class="fa fa-rotate-left"></i> Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- TABLE CARD -->
            <div class="data-card shadow-sm border-0">
                <div class="table-responsive">
                    <table id="guard-salary-table" class="table table-hover w-100">
                        <thead>
                            <tr>
                                <th>Month / Year</th>
                                <th>Staff Name</th>
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
        </main>
    </div>

    <!-- PAY MODAL -->
    <div class="modal fade" id="cashConfirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius:18px;">
                <div class="modal-body text-center p-4">
                    <div style="width:60px;height:60px;border-radius:50%;background:#fff3cd;
                    display:flex;align-items:center;justify-content:center;
                    margin:0 auto 15px;font-size:26px;">
                        💵
                    </div>
                    <h5 class="fw-bold mb-2">Confirm Payment</h5>
                    <p class="text-muted mb-4">
                        Are you sure, you want to mark this payment as <b>Paid</b>?
                    </p>
                    <div class="d-flex gap-3 justify-content-center">
                        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <button type="button" id="confirmCashBtn" class="btn btn-success px-4 fw-bold">
                            Yes, Confirm
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JS -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(function() {

            let selectedGuardId = null;

            //  SERVER SIDE DATATABLE (CONNECTED TO action.php)
            let table = $('#guard-salary-table').DataTable({
                processing: true,
                serverSide: true,
                searching: false,
                pageLength: 5,
                lengthMenu: [5, 10, 25, 50],
                ajax: {
                    url: "<?= BASE_URL ?>action.php",
                    type: "POST",
                    data: function(d) {
                        d.action = "fetch_guard_salary"; // IMPORTANT
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

            // FILTER CHANGE
            $('#filter-month, #filter-year, #filter-status').on('change', function() {
                table.ajax.reload();
            });

            // RESET FILTER
            $('#reset-filters').on('click', function() {
                $('#filter-month, #filter-year, #filter-status').val('');
                table.ajax.reload();
            });

            // STORE ID WHEN CLICK PAY
            $(document).on('click', '.pay-salary', function() {
                selectedGuardId = $(this).data('id');
                $('#cashConfirmModal').modal('show');
            });

            // CONFIRM PAYMENT
            $('#confirmCashBtn').on('click', function() {
                if (!selectedGuardId) return;

                $.post("<?= BASE_URL ?>action.php", {
                    action: "mark_guard_salary_paid",
                    id: selectedGuardId
                }, function(res) {
                    let response = JSON.parse(res);

                    if (response.status === 'success') {
                        $('#cashConfirmModal').modal('hide');
                        table.ajax.reload(null, false); // reload without page reset
                    }
                });
            });

        });
    </script>

</body>

</html>

<?php include __DIR__ . '/../resources/layout/footer.php'; ?>
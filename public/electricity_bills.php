<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

requireRole(['admin', 'cashier']);

include __DIR__ . '/../resources/layout/header.php';
?>





<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Electricity Management</title>

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
                <h1 class="fw-800 m-0">Electricity Bills</h1>
                <button class="btn btn-brand shadow-sm" data-bs-toggle="modal" data-bs-target="#addBillModal">
                    <i class="fa-solid fa-plus me-2"></i> Record New Bill
                </button>
            </div>

            <div class="data-card shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table table-hover datatable w-100">
                        <thead>
                            <tr>
                                <th>Meter Name/No</th>
                                <th>Billing Cycle</th>
                                <th>Prev Reading</th>
                                <th>Curr Reading</th>
                                <th>Total Units</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-bold">Main Gate & Street Lights</td>
                                <td>Jan - Feb 2026</td>
                                <td>14,250</td>
                                <td>15,100</td>
                                <td>850 Units</td>
                                <td class="fw-bold text-primary">₹6,800</td>
                                <td><span class="bill-paid">Paid</span></td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-light border"><i class="fa fa-eye"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Clubhouse & Pool</td>
                                <td>Jan - Feb 2026</td>
                                <td>42,100</td>
                                <td>43,500</td>
                                <td>1,400 Units</td>
                                <td class="fw-bold text-primary">₹11,200</td>
                                <td><span class="bill-pending">Awaiting Payment</span></td>
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


    <!-- VIEW FILE MODAL -->
    <!-- <div class="modal fade" id="viewFileModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">View Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <iframe id="fileFrame" src="" width="100%" height="500px" style="border:none;"></iframe>
                </div>
            </div>
        </div>
    </div> -->



    <!-- PAY MODAL -->
    <!-- <div class="modal fade" id="payBillModal">
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
    </div> -->



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
                pageLength: 5,
                lengthMenu: [5, 10, 25, 50],
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
                        data: 'bill_file'
                    },
                    {
                        data: 'receipt_file'
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


            // VIEW FILE (Bill / Receipt)
            $(document).on('click', '.view-file', function() {
                let file = $(this).data('file');

                if (!file) {
                    alert("File not found!");
                    return;
                }

                $('#fileFrame').attr('src', '<?= BASE_URL ?>uploads/electricity_bills/' + file);
                $('#viewFileModal').modal('show');
            });



        });
    </script>

</body>

</html>

<?php include __DIR__ . '/../resources/layout/footer.php'; ?>
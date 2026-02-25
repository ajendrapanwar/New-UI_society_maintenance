<?php

requireRole(['admin', 'cashier']);

?>


<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../../assets/css/styles.css">

</head>

<body>

    <!-- ONLINE PAYMENT POPUP -->
    <div class="modal fade" id="onlinePaymentModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form id="onlinePaymentForm"
                class="modal-content border-0 shadow-lg"
                enctype="multipart/form-data"
                style="border-radius: 20px;">

                <!-- HEADER -->
                <div class="modal-header border-0 p-4">
                    <h5 class="modal-title fw-800">Online Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <!-- BODY -->
                <div class="modal-body p-4 pt-0">

                    <input type="hidden" name="bill_id" id="online_bill_id">

                    <div class="row g-3">

                        <!-- PAYMENT METHOD -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">
                                PAYMENT METHOD <span class="text-danger">*</span>
                            </label>
                            <select name="payment_method"
                                class="form-select bg-light border-0 py-3">
                                <option value="">Select Method</option>
                                <option value="upi">UPI</option>
                                <option value="credit_card">Credit Card</option>
                                <option value="debit_card">Debit Card</option>
                                <option value="netbanking">Net Banking</option>
                            </select>
                            <small class="text-danger error-payment_method"></small>
                        </div>

                        <!-- UPLOAD PROOF -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">
                                UPLOAD PAYMENT PROOF <span class="text-danger">*</span>
                            </label>
                            <input type="file"
                                name="proof"
                                class="form-control bg-light border-0 py-3"
                                accept="image/*,.pdf">
                            <small class="text-danger error-proof"></small>
                        </div>

                        <!-- NOTE -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">
                                NOTE <span class="text-danger">*</span>
                            </label>
                            <textarea name="note"
                                class="form-control bg-light border-0"
                                rows="3"
                                placeholder="Enter payment note..."></textarea>
                            <small class="text-danger error-note"></small>
                        </div>

                        <!-- SUBMIT BUTTON -->
                        <div class="col-12">
                            <button type="submit"
                                class="btn btn-brand w-100 py-3 mt-3">
                                Submit Payment
                            </button>
                        </div>

                    </div>
                </div>

            </form>
        </div>
    </div>



    <!-- MODERN CASH CONFIRM MODAL -->
    <div class="modal fade" id="cashConfirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius:18px;">

                <div class="modal-body text-center p-4">

                    <!-- Icon -->
                    <div style="
                    width:60px;
                    height:60px;
                    border-radius:50%;
                    background:#fff3cd;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    margin:0 auto 15px;
                    font-size:26px;">
                        💵
                    </div>

                    <!-- Title -->
                    <h5 class="fw-bold mb-2">Confirm Cash Payment</h5>

                    <!-- Message -->
                    <p class="text-muted mb-4">
                        Are you sure you want to mark this payment as <b>Cash</b>?
                    </p>

                    <!-- Buttons -->
                    <div class="d-flex gap-3 justify-content-center">
                        <button type="button"
                            class="btn btn-light px-4 py-2"
                            data-bs-dismiss="modal"
                            style="border-radius:10px;">
                            Cancel
                        </button>

                        <button type="button"
                            id="confirmCashBtn"
                            class="btn btn-success px-4 py-2 fw-bold"
                            style="border-radius:10px;">
                            Yes, Confirm
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>



    <script>
        // Store selected bill id globally
        let selectedBillId = null;

        /* ===== PAYMENT TYPE CHANGE (CASH / ONLINE) ===== */
        $(document).on('change', '.payment-type', function() {
            let billId = $(this).data('bill');
            let type = $(this).val();

            if (!type) return;

            // ===== CASH PAYMENT (SHOW MODERN CONFIRM MODAL) =====
            if (type === 'cash') {
                selectedBillId = billId; // save bill id
                $('#cashConfirmModal').modal('show'); // open center popup
                $(this).val(''); // reset dropdown
            }

            // ===== ONLINE PAYMENT (SHOW ONLINE MODAL) =====
            if (type === 'online') {
                $('#online_bill_id').val(billId);
                $('#onlinePaymentModal').modal('show');
                $(this).val(''); // reset dropdown
            }
        });

        /* ===== CONFIRM CASH PAYMENT BUTTON CLICK ===== */
        $(document).on('click', '#confirmCashBtn', function() {
            if (!selectedBillId) return;

            // Optional: disable button to prevent double click
            $(this).prop('disabled', true).text('Processing...');

            window.location.href =
                '<?= BASE_URL ?>action.php?action=mark_cash_payment&maintenance_bill_id=' + selectedBillId;
        });

        /* ===== ONLINE PAYMENT FORM SUBMIT (AJAX) ===== */
        $(document).on('submit', '#onlinePaymentForm', function(e) {
            e.preventDefault();

            // Clear previous errors
            $('.error-payment_method').text('');
            $('.error-proof').text('');
            $('.error-note').text('');

            let formData = new FormData(this);
            formData.append('action', 'mark_online_payment');

            // Disable submit button while processing
            let submitBtn = $(this).find('button[type="submit"]');
            submitBtn.prop('disabled', true).text('Submitting...');

            $.ajax({
                url: '<?= BASE_URL ?>action.php',
                method: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',

                success: function(res) {
                    if (res.status === 'error') {

                        // Show validation errors under fields
                        if (res.errors) {
                            $.each(res.errors, function(key, value) {
                                $('.error-' + key).text(value);
                            });
                        }

                        submitBtn.prop('disabled', false).text('Submit Payment');

                    } else {
                        // Close modal and reload page
                        $('#onlinePaymentModal').modal('hide');

                        // Optional success delay for smooth UX
                        setTimeout(function() {
                            location.reload();
                        }, 300);
                    }
                },

                error: function(xhr) {
                    // Modern fallback error (instead of ugly alert)
                    console.error(xhr.responseText);
                    alert('Something went wrong while processing payment.');
                    submitBtn.prop('disabled', false).text('Submit Payment');
                }
            });
        });

        /* ===== RESET BUTTON STATE WHEN MODAL CLOSES ===== */
        $('#onlinePaymentModal').on('hidden.bs.modal', function() {
            $('#onlinePaymentForm')[0].reset();
            $('.text-danger').text('');
            $('#onlinePaymentForm button[type="submit"]')
                .prop('disabled', false)
                .text('Submit Payment');
        });

        $('#cashConfirmModal').on('hidden.bs.modal', function() {
            selectedBillId = null;
            $('#confirmCashBtn')
                .prop('disabled', false)
                .text('Yes, Confirm');
        });
    </script>



</body>

</html>
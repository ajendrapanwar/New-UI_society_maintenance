<?php
// popup.php
// if (!isset($_SESSION['user_id'])) {
//     exit;
// }
requireRole(['admin', 'cashier']);
?>

<!-- ONLINE PAYMENT POPUP -->
<div class="modal fade" id="onlinePaymentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form id="onlinePaymentForm" class="modal-content" enctype="multipart/form-data">
            <div class="modal-header">
                <h5 class="modal-title">Online Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" name="bill_id" id="online_bill_id">

                <div class="mb-2">
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method" class="form-select" required>
                        <option value="">Select</option>
                        <option value="upi">UPI</option>
                        <option value="credit_card">Credit Card</option>
                        <option value="debit_card">Debit Card</option>
                        <option value="netbanking">Net Banking</option>
                    </select>

                </div>

                <div class="mb-2">
                    <label class="form-label">Upload Proof</label>
                    <input type="file" name="proof" class="form-control" accept="image/*,.pdf" required>
                </div>

                <div class="mb-2">
                    <label class="form-label">Note</label>
                    <textarea name="note" class="form-control" required></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="submit" class="btn btn-success">Submit</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- POPUP JS LOGIC -->
<script>
    /* ===== PAYMENT TYPE CHANGE ===== */
    $(document).on('change', '.payment-type', function() {
        let billId = $(this).data('bill');
        let type = $(this).val();

        if (!type) return;

        if (type === 'cash') {
            if (confirm('Mark payment as CASH?')) {
                window.location.href =
                    '<?= BASE_URL ?>action.php?action=mark_cash_payment&maintenance_bill_id=' + billId;
            } else {
                $(this).val('');
            }
        }

        if (type === 'online') {
            $('#online_bill_id').val(billId);
            $('#onlinePaymentModal').modal('show');
            $(this).val('');
        }
    });

    /* ===== ONLINE PAYMENT SUBMIT ===== */
    $('#onlinePaymentForm').on('submit', function(e) {
        e.preventDefault();

        let formData = new FormData(this);
        formData.append('action', 'mark_online_payment');

        $.ajax({
            url: '<?= BASE_URL ?>action.php',
            method: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function() {
                location.reload();
            },
            error: function(xhr) {
                alert('Payment failed:\n' + xhr.responseText);
            }

        });
    });
</script>
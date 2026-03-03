<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('opcache.enable', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../core/config.php';

header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// ================= ACCESS CONTROL =================
if (
	!isset($_SESSION['user_id']) ||
	!in_array($_SESSION['user_role'], ['admin', 'cashier', 'user'])
) {
	header('Location: ' . BASE_URL . 'logout.php');
	exit();
}


// ================= INDIAN SHORT CURRENCY FORMAT =================
function formatIndianAmount($amount)
{
	$amount = (float)($amount ?? 0);

	if ($amount >= 10000000) { // 1 Crore+
		$value = $amount / 10000000;
		return rtrim(rtrim(number_format($value, 1), '0'), '.') . ' CR';
	} elseif ($amount >= 100000) { // 1 Lakh+
		$value = $amount / 100000;
		return rtrim(rtrim(number_format($value, 1), '0'), '.') . ' LKH';
	} elseif ($amount >= 1000) { // 1 Thousand+
		$value = $amount / 1000;
		return rtrim(rtrim(number_format($value, 1), '0'), '.') . ' K';
	} else {
		return number_format($amount, 2);
	}
}


// ================= TOTAL FLATS =================
$stmt = $pdo->query("SELECT COUNT(*) AS total_flats FROM flats");
$total_flats = $stmt->fetch(PDO::FETCH_ASSOC)['total_flats'] ?? 0;


// ================= TOTAL ALLOTMENTS =================
$stmt = $pdo->query("SELECT COUNT(*) AS total_allotments FROM allotments");
$total_allotments = $stmt->fetch(PDO::FETCH_ASSOC)['total_allotments'] ?? 0;


// ================= GET USER FLAT (IF NORMAL USER) =================
$flat_id = null;
if ($_SESSION['user_role'] === 'user') {
	$stmt = $pdo->prepare("SELECT flat_id FROM allotments WHERE user_id = ?");
	$stmt->execute([$_SESSION['user_id']]);
	$flat_id = $stmt->fetch(PDO::FETCH_ASSOC)['flat_id'] ?? null;
}


// ================= TOTAL COLLECTION (PAID MAINTENANCE) =================
if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'cashier') {

	$stmt = $pdo->prepare("
        SELECT SUM(total_amount) AS total_collection
        FROM maintenance_bills
        WHERE status = 'paid'
    ");
	$stmt->execute();
	$total_collection = $stmt->fetch(PDO::FETCH_ASSOC)['total_collection'] ?? 0;
} else {

	if ($flat_id) {
		$stmt = $pdo->prepare("
            SELECT SUM(total_amount) AS total_collection
            FROM maintenance_bills
            WHERE status = 'paid' AND flat_id = ?
        ");
		$stmt->execute([$flat_id]);
		$total_collection = $stmt->fetch(PDO::FETCH_ASSOC)['total_collection'] ?? 0;
	} else {
		$total_collection = 0;
	}
}


// ================= TOTAL PENDING MAINTENANCE =================
if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'cashier') {

	$stmt = $pdo->prepare("
        SELECT SUM(total_amount) AS pending_amount
        FROM maintenance_bills
        WHERE status IN ('pending','overdue')
    ");
	$stmt->execute();
	$total_pending_amount = $stmt->fetch(PDO::FETCH_ASSOC)['pending_amount'] ?? 0;
} else {

	if ($flat_id) {
		$stmt = $pdo->prepare("
            SELECT SUM(total_amount) AS pending_amount
            FROM maintenance_bills
            WHERE status IN ('pending','overdue') AND flat_id = ?
        ");
		$stmt->execute([$flat_id]);
		$total_pending_amount = $stmt->fetch(PDO::FETCH_ASSOC)['pending_amount'] ?? 0;
	} else {
		$total_pending_amount = 0;
	}
}


// ================= TOTAL PAID EXPENSE =================
$total_paid_expense = 0;
if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'cashier') {

	$sql = "
        SELECT SUM(amount) FROM electricity_bills WHERE status='paid'
        UNION ALL
        SELECT SUM(amount) FROM miscellaneous_works WHERE status='paid'
        UNION ALL
        SELECT SUM(salary_amount) FROM sweeper_salary WHERE status='paid'
        UNION ALL
        SELECT SUM(salary_amount) FROM guard_salary WHERE status='paid'
        UNION ALL
        SELECT SUM(salary_amount) FROM garbage_salary WHERE status='paid'
    ";

	$stmt = $pdo->query($sql);
	$sum = 0;

	while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
		$sum += (float)($row[0] ?? 0);
	}

	$total_paid_expense = $sum;
}

// ================= FINAL FORMATTING =================
$total_collection     = formatIndianAmount($total_collection);
$total_pending_amount = formatIndianAmount($total_pending_amount);
$total_paid_expense   = formatIndianAmount($total_paid_expense);


/* ================= FETCH ALL ALLOTTED FLATS (FOR CASHIER) ================= */
$allottedFlats = [];
if ($_SESSION['user_role'] === 'cashier') {

	$stmt = $pdo->query("
        SELECT 
            a.flat_id,
            f.block_number,
            f.flat_number,
            f.flat_type,
            u.name,
            u.email
        FROM allotments a
        JOIN flats f ON a.flat_id = f.id
        JOIN users u ON a.user_id = u.id
        ORDER BY f.block_number, f.flat_number
    ");

	$allottedFlats = $stmt->fetchAll(PDO::FETCH_ASSOC);
}


include('../resources/layout/header.php');
?>


<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Professional Dashboard</title>

	<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
	<link rel="stylesheet" href="../assets/css/styles.css">

</head>

<body>

	<!--------------------- ADMIN VIEW ---------------------->
	<?php if ($_SESSION['user_role'] === 'admin'): ?>

		<div class="main-wrapper">
			<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

			<main id="main-content">
				<!-- Generate -->
				<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
					<!-- Dashboard Title -->
					<div class="mb-4">
						<h2 class="fw-800 m-0">My Dashboard</h2>
					</div>
					<!-- Buttons -->
					<div class="col-lg-6 col-md-12 text-lg-end text-start">
						<div class="d-flex gap-2 justify-content-lg-end flex-nowrap">

							<?php if ($_SESSION['user_role'] === 'admin'): ?>

								<?php if (date('j') >= 28): ?>
									<a href="manualGenerateMonthlyBills.php"
										class="btn btn-brand shadow-sm"
										onclick="return confirm('Are you sure you want to run the maintenance billing job?');">
										<i class="fa fa-gears me-1"></i> Generate Bill
									</a>
								<?php else: ?>
									<button class="btn btn-outline-secondary" disabled>
										<i class="fa fa-lock me-1"></i> Generate Bill (28th - 31st)
									</button>
								<?php endif; ?>

								<?php if (date('j') >= 28): ?>
									<a href="manualGeneratecronSalary.php"
										class="btn btn-brand shadow-sm"
										onclick="return confirm('Are you sure you want to run the Salary billing job?');">
										<i class="fa fa-gears me-1"></i> Generate Salary
									</a>
								<?php else: ?>
									<button class="btn btn-outline-secondary" disabled>
										<i class="fa fa-lock me-1"></i> Generate Salary (28th - 31st)
									</button>
								<?php endif; ?>

							<?php endif; ?>

						</div>
					</div>
				</div>
				<!-- Boxes1 -->
				<div class="row g-3 mb-5">
					<div class="col-6 col-lg-3">
						<div class="stat-card">
							<p class="text-muted small fw-bold mb-1">REVENUE</p>
							<h3 class="fw-bold m-0 text-success">₹ <?= $total_collection ?></h3>
						</div>
					</div>
					<div class="col-6 col-lg-3">
						<div class="stat-card border-danger">
							<p class="text-danger small fw-bold mb-1">PENDING</p>
							<h3 class="fw-bold m-0">₹ <?= $total_pending_amount ?></h3>
						</div>
					</div>
					<div class="col-6 col-lg-3">
						<div class="stat-card">
							<p class="text-muted small fw-bold mb-1">EXPENSES</p>
							<h3 class="fw-bold m-0 text-danger">₹ <?= $total_paid_expense ?></h3>
						</div>
					</div>
				</div>
				<!-- Boxes2 -->
				<div class="row g-3 mb-5">
					<div class="col-6 col-lg-3">
						<div class="stat-card">
							<p class="text-muted small fw-bold mb-1">Total Flats</p>
							<h3 class="fw-bold m-0"><?= $total_flats ?></h3>
						</div>
					</div>
					<div class="col-6 col-lg-3">
						<div class="stat-card">
							<p class="text-muted small fw-bold mb-1">Total Allotments</p>
							<h3 class="fw-bold m-0"><?= $total_allotments ?></h3>
						</div>
					</div>
				</div>
			</main>

		</div>

	<?php elseif ($_SESSION['user_role'] === 'cashier'): ?>

		<div class="main-wrapper">
			<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

			<main id="main-content">
				<!-- Generate -->
				<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
					<!-- Dashboard Title -->
					<div class="mb-4">
						<h2 class="fw-800 m-0">My Dashboard</h2>
					</div>
					<!-- Buttons -->
					<div class="col-lg-6 col-md-12 text-lg-end text-start">
						<div class="d-flex gap-2 justify-content-lg-end flex-nowrap">

							<?php if ($_SESSION['user_role'] === 'admin'): ?>

								<?php if (date('j') >= 28): ?>
									<a href="manualGenerateMonthlyBills.php"
										class="btn btn-brand shadow-sm"
										onclick="return confirm('Are you sure you want to run the maintenance billing job?');">
										<i class="fa fa-gears me-1"></i> Generate Bill
									</a>
								<?php else: ?>
									<button class="btn btn-outline-secondary" disabled>
										<i class="fa fa-lock me-1"></i> Generate Bill (28th - 31st)
									</button>
								<?php endif; ?>

								<?php if (date('j') >= 28): ?>
									<a href="manualGeneratecronSalary.php"
										class="btn btn-brand shadow-sm"
										onclick="return confirm('Are you sure you want to run the Salary billing job?');">
										<i class="fa fa-gears me-1"></i> Generate Salary
									</a>
								<?php else: ?>
									<button class="btn btn-outline-secondary" disabled>
										<i class="fa fa-lock me-1"></i> Generate Salary (28th - 31st)
									</button>
								<?php endif; ?>

							<?php endif; ?>

						</div>
					</div>
				</div>

				<!-- Boxes1 -->
				<div class="row g-3 mb-3">
					<div class="col-6 col-lg-3">
						<div class="stat-card">
							<p class="text-muted small fw-bold mb-1">REVENUE</p>
							<h3 class="fw-bold m-0 text-success">₹ <?= $total_collection ?></h3>
						</div>
					</div>
					<div class="col-6 col-lg-3">
						<div class="stat-card border-danger">
							<p class="text-danger small fw-bold mb-1">PENDING</p>
							<h3 class="fw-bold m-0">₹ <?= $total_pending_amount ?></h3>
						</div>
					</div>
					<div class="col-6 col-lg-3">
						<div class="stat-card">
							<p class="text-muted small fw-bold mb-1">EXPENSES</p>
							<h3 class="fw-bold m-0 text-danger">₹ <?= $total_paid_expense ?></h3>
						</div>
					</div>
				</div>

				<!-- Payment Terminal -->
				<div class="col-lg-12">
					<div class="data-card border-0 shadow-sm">
						<h4 class="fw-800 mb-4">Maintenance Ledger</h4>

						<!-- SELECT FLAT -->
						<div class="row">
							<div class="col-md-8 mb-4">
								<label class="form-label fw-bold text-muted small uppercase">
									Select Allotted Flat
								</label>

								<select id="flatSelect" class="form-select form-select-lg">
									<option value="">-- Select Flat --</option>

									<?php foreach ($allottedFlats as $flat): ?>
										<option
											value="<?= $flat['flat_id'] ?>"
											data-name="<?= htmlspecialchars($flat['name']) ?>"
											data-email="<?= htmlspecialchars($flat['email']) ?>"
											data-flat="<?= htmlspecialchars($flat['flat_number']) ?>"
											data-block="<?= htmlspecialchars($flat['block_number']) ?>"
											data-type="<?= htmlspecialchars($flat['flat_type']) ?>">
											Block <?= htmlspecialchars($flat['block_number']) ?>
											- <?= htmlspecialchars($flat['flat_number']) ?>
											(<?= htmlspecialchars($flat['name']) ?>)
										</option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>

						<!-- USER INFO CARD -->
						<div id="userInfoCard" class="data-card shadow-sm border-0 mb-4" style="display:none;">
							<div class="card-body d-flex flex-wrap align-items-center gap-3">
								<div><strong>Name:</strong> <span id="infoName"></span></div>
								<div><strong>Email:</strong> <span id="infoEmail"></span></div>
								<div><strong>Flat:</strong> <span id="infoFlat"></span></div>
								<div><strong>Block:</strong> <span id="infoBlock"></span></div>
								<div><strong>Type:</strong> <span id="infoType"></span></div>
							</div>
						</div>

						<!-- LEDGER TABLE -->
						<div id="ledgerTableBox" class="data-card shadow-sm border-0" style="display:none;">
							<div class="table-responsive">
								<table id="bills-table" class="table table-hover w-100">
									<thead>
										<tr>
											<th>Month / Year</th>
											<th>Amount</th>
											<th>Fine</th>
											<th>Total</th>
											<th>Status</th>
											<th>Mode</th>
											<th>Paid On</th>
											<th>Overdue</th>
											<th class="text-end">Action</th>
										</tr>
									</thead>
									<tbody></tbody>
								</table>
							</div>
						</div>

					</div>
				</div>

			</main>
		</div>

		<!--------------------- USER VIEW ---------------------->
	<?php else: ?>

		<div class="main-wrapper">
			<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

			<main id="main-content">
				<div class="mb-4">
					<h2 class="fw-800 m-0">My Dashboard</h2>
				</div>

				<div class="row g-3 mb-5">
					<div class="col-6 col-lg-3">
						<div class="stat-card border-danger">
							<p class="text-danger small fw-bold mb-1">PENDING</p>
							<h3 class="fw-bold m-0">₹ <?= $total_pending_amount ?></h3>
						</div>
					</div>
				</div>
			</main>
		</div>

	<?php endif; ?>



	<!-- ONLINE PAYMENT MODAL -->
	<div class="modal fade" id="onlinePaymentModal" tabindex="-1">
		<div class="modal-dialog modal-dialog-centered">
			<form id="onlinePaymentForm"
				class="modal-content border-0 shadow-lg"
				enctype="multipart/form-data"
				style="border-radius:20px;">

				<div class="modal-header border-0 p-4">
					<h5 class="modal-title fw-800">Online Payment</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>

				<div class="modal-body p-4 pt-0">

					<input type="hidden" name="bill_id" id="online_bill_id">

					<div class="mb-3">
						<label class="form-label small fw-bold text-muted">
							PAYMENT METHOD *
						</label>
						<select name="payment_method" class="form-select bg-light border-0 py-3">
							<option value="">Select Method</option>
							<option value="upi">UPI</option>
							<option value="credit_card">Credit Card</option>
							<option value="debit_card">Debit Card</option>
							<option value="netbanking">Net Banking</option>
						</select>
						<small class="text-danger error-payment_method"></small>
					</div>

					<div class="mb-3">
						<label class="form-label small fw-bold text-muted">
							UPLOAD PAYMENT PROOF *
						</label>
						<input type="file"
							name="proof"
							class="form-control bg-light border-0 py-3"
							accept="image/*,.pdf">
						<small class="text-danger error-proof"></small>
					</div>

					<div class="mb-3">
						<label class="form-label small fw-bold text-muted">
							NOTE *
						</label>
						<textarea name="note"
							class="form-control bg-light border-0"
							rows="3"></textarea>
						<small class="text-danger error-note"></small>
					</div>

					<button type="submit" class="btn btn-brand w-100 py-3 mt-3">
						Submit Payment
					</button>

				</div>
			</form>
		</div>
	</div>

	<!-- CASH CONFIRM MODAL -->
	<div class="modal fade" id="cashConfirmModal" tabindex="-1">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content border-0 shadow-lg" style="border-radius:18px;">
				<div class="modal-body text-center p-4">

					<div style="width:60px;height:60px;border-radius:50%;
                background:#fff3cd;display:flex;align-items:center;
                justify-content:center;margin:0 auto 15px;font-size:26px;">
						💵
					</div>

					<h5 class="fw-bold mb-2">Confirm Cash Payment</h5>

					<p class="text-muted mb-4">
						Are you sure you want to mark this payment as <b>Cash</b>?
					</p>

					<div class="d-flex gap-3 justify-content-center">
						<button type="button"
							class="btn btn-light px-4"
							data-bs-dismiss="modal">
							Cancel
						</button>

						<button type="button"
							id="confirmCashBtn"
							class="btn btn-success px-4 fw-bold">
							Yes, Confirm
						</button>
					</div>

				</div>
			</div>
		</div>
	</div>


	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
	<script src="https://cdn.datatables.net/1.13.2/js/dataTables.bootstrap5.min.js"></script>

	<script>
		$(document).ready(function() {

			let billsTable = null;

			$('#flatSelect').on('change', function() {

				const selected = this.options[this.selectedIndex];
				const flatId = this.value;

				if (!flatId) {
					$('#userInfoCard').hide();
					$('#ledgerTableBox').hide();
					return;
				}

				// Fill user info
				$('#infoName').text(selected.dataset.name);
				$('#infoEmail').text(selected.dataset.email);
				$('#infoFlat').text(selected.dataset.flat);
				$('#infoBlock').text(selected.dataset.block);
				$('#infoType').text(selected.dataset.type);

				$('#userInfoCard').show();
				$('#ledgerTableBox').show();

				// Destroy old table if exists
				if (billsTable !== null) {
					billsTable.destroy();
					$('#bills-table tbody').empty();
				}

				billsTable = $('#bills-table').DataTable({
					dom: '<"d-flex justify-content-between mb-4"lf>rt<"d-flex justify-content-between mt-4"ip>',
					processing: true,
					serverSide: true,
					responsive: true,
					pageLength: 5,
					lengthMenu: [5, 10, 25, 50],
					order: [
						[0, 'desc']
					],

					ajax: {
						url: '<?= BASE_URL ?>action.php',
						type: 'POST',
						data: function(d) {
							d.action = 'fetch_user_bills_by_flat';
							d.flat_id = flatId;
						}
					},

					columns: [{
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
						},
						{
							data: 'action',
							orderable: false,
							searchable: false
						}
					]
				});

			});

		});



		let selectedBillId = null;

		/* ===== PAYMENT TYPE CHANGE ===== */
		$(document).on('change', '.payment-type', function() {

			let billId = $(this).data('bill');
			let type = $(this).val();

			if (!type) return;

			if (type === 'cash') {
				selectedBillId = billId;
				$('#cashConfirmModal').modal('show');
			}

			if (type === 'online') {
				$('#online_bill_id').val(billId);
				$('#onlinePaymentModal').modal('show');
			}

			$(this).val('');
		});


		/* ===== CONFIRM CASH PAYMENT ===== */
		$(document).on('click', '#confirmCashBtn', function() {

			if (!selectedBillId) return;

			$(this).prop('disabled', true).text('Processing...');

			window.location.href =
				'<?= BASE_URL ?>action.php?action=mark_cash_payment&maintenance_bill_id=' +
				selectedBillId;
		});


		/* ===== ONLINE PAYMENT SUBMIT (AJAX) ===== */
		$(document).on('submit', '#onlinePaymentForm', function(e) {

			e.preventDefault();

			$('.error-payment_method').text('');
			$('.error-proof').text('');
			$('.error-note').text('');

			let formData = new FormData(this);
			formData.append('action', 'mark_online_payment');

			let btn = $(this).find('button[type="submit"]');
			btn.prop('disabled', true).text('Submitting...');

			$.ajax({
				url: '<?= BASE_URL ?>action.php',
				method: 'POST',
				data: formData,
				contentType: false,
				processData: false,
				dataType: 'json',

				success: function(res) {

					if (res.status === 'error') {

						if (res.errors) {
							$.each(res.errors, function(key, value) {
								$('.error-' + key).text(value);
							});
						}

						btn.prop('disabled', false).text('Submit Payment');

					} else {

						$('#onlinePaymentModal').modal('hide');

						setTimeout(function() {
							$('#bills-table').DataTable().ajax.reload(null, false);
						}, 300);
					}
				},

				error: function() {
					alert('Payment processing failed.');
					btn.prop('disabled', false).text('Submit Payment');
				}
			});
		});


		/* ===== RESET WHEN MODAL CLOSES ===== */
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

<?php include('../resources/layout/footer.php'); ?>
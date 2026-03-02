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
					<h2 class="fw-800 m-0">Dashboard Overview</h2>
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
					<h2 class="fw-800 m-0">Dashboard Overview</h2>
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


                <div class="col-lg-8">
                    <div class="data-card border-0 shadow-sm">
                        <h4 class="fw-800 mb-4">Post Maintenance Payment</h4>
                        
                        <form id="paymentForm" class="row g-4">
                            <div class="col-md-12">
                                <label class="form-label fw-bold text-muted small uppercase">Search Flat / Member</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="fa fa-search"></i></span>
                                    <input type="text" id="flatSearch" class="form-control form-control-lg border-start-0" placeholder="Type Flat No (e.g. B-402)..." autocomplete="off">
                                </div>
                                <div id="searchFeedback" class="mt-2 small fw-bold text-primary"></div>
                            </div>

                            <div id="outstandingBox" class="col-md-12 hidden" style="display:none;">
                                <div class="bg-primary bg-opacity-10 p-4 rounded-4 border border-primary border-opacity-25">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="m-0 text-muted small uppercase fw-bold">Outstanding Balance</p>
                                            <h2 class="m-0 fw-black text-primary" id="fetchedAmount">₹ 0.00</h2>
                                        </div>
                                        <div class="text-end">
                                            <p class="m-0 fw-bold" id="memberName">---</p>
                                            <p class="m-0 text-muted small">Unit: <span id="unitDisplay">---</span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted small">Amount to Pay</label>
                                <input type="number" id="payAmount" class="form-control form-control-lg fw-bold" placeholder="Enter amount">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted small">Payment Mode</label>
                                <select class="form-select form-select-lg">
                                    <option>Cash</option>
                                    <option>UPI / QR</option>
                                    <option>Cheque</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-brand btn-lg w-80 py-3 mt-2 shadow-lg">
                                    <i class="fa-solid fa-receipt me-2"></i> Confirm & Print Receipt
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

			</main>
		</div>

		<!--------------------- USER VIEW ---------------------->
	<?php else: ?>

		<div class="main-wrapper">
			<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
			
			<main id="main-content">
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


</body>


</html>

<?php include('../resources/layout/footer.php'); ?>
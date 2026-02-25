<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('opcache.enable', 0);
error_reporting(E_ALL);


require_once __DIR__ . '/../core/config.php';

header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// ACCESS CONTROL
if (
	!isset($_SESSION['user_id']) ||
	!in_array($_SESSION['user_role'], ['admin', 'cashier', 'user'])
) {
	header('Location: ' . BASE_URL . 'logout.php');
	exit();
}


// Get total flats
$sql = "SELECT COUNT(*) AS total_flats FROM flats";
$stmt = $pdo->query($sql);
$total_flats = $stmt->fetch(PDO::FETCH_ASSOC)['total_flats'];

$flat_id = '';

// Get total pending/overdue bills
if ($_SESSION['user_role'] === 'admin') {
	// Admin sees all pending/overdue bills
	$stmt = $pdo->prepare("
			SELECT COUNT(*) AS total_bills 
			FROM maintenance_bills 
			WHERE status IN ('pending','overdue')
		");
	$stmt->execute();
	$total_bills = $stmt->fetch(PDO::FETCH_ASSOC)['total_bills'];
} else {
	// Normal user sees only their pending/overdue bills
	$stmt = $pdo->prepare("SELECT flat_id FROM allotments WHERE user_id = ?");
	$stmt->execute([$_SESSION['user_id']]);
	$flat_id = $stmt->fetch(PDO::FETCH_ASSOC)['flat_id'] ?? null;

	if ($flat_id) {
		$stmt = $pdo->prepare("
				SELECT COUNT(*) AS total_bills 
				FROM maintenance_bills 
				WHERE status IN ('pending','overdue') AND flat_id = ?
			");
		$stmt->execute([$flat_id]);
		$total_bills = $stmt->fetch(PDO::FETCH_ASSOC)['total_bills'];
	} else {
		$total_bills = 0;
	}
}

// Get total allotments
$sql = "SELECT COUNT(*) AS total_allotments FROM allotments";
$stmt = $pdo->query($sql);
$total_allotments = $stmt->fetch(PDO::FETCH_ASSOC)['total_allotments'];

// ================= TOTAL COLLECTION (PAID TOTAL) =================

// Admin = all flats paid total
if ($_SESSION['user_role'] === 'admin' || 'cashier') {

	$stmt = $pdo->prepare("
				SELECT SUM(total_amount) AS total_collection
				FROM maintenance_bills
				WHERE status = 'paid'
			");
	$stmt->execute();
	$total_collection = $stmt->fetch(PDO::FETCH_ASSOC)['total_collection'] ?? 0;
}
// User = only his flat paid total
else {
	$stmt = $pdo->prepare("SELECT flat_id FROM allotments WHERE user_id = ?");
	$stmt->execute([$_SESSION['user_id']]);
	$flat_id = $stmt->fetch(PDO::FETCH_ASSOC)['flat_id'] ?? null;

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

$total_collection = number_format($total_collection, 2);

// ================= TOTAL PAID EXPENSE (ADMIN ONLY) =================
$total_paid_expense = 0;

if ($_SESSION['user_role'] === 'admin' || 'cashier') {

	$sql = "
			SELECT SUM(amount) AS total_paid FROM electricity_bills WHERE status='paid'
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
		$sum += $row[0];
	}

	$total_paid_expense = number_format($sum, 2);
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
				<!-- Boxes -->
				<div class="row g-3 mb-5">
					<div class="col-6 col-lg-3">
						<div class="stat-card">
							<p class="text-muted small fw-bold mb-1">REVENUE</p>
							<h3 class="fw-bold m-0">₹8.4L</h3>
						</div>
					</div>
					<div class="col-6 col-lg-3">
						<div class="stat-card border-danger">
							<p class="text-danger small fw-bold mb-1">PENDING</p>
							<h3 class="fw-bold m-0">₹1.2L</h3>
						</div>
					</div>
					<div class="col-6 col-lg-3">
						<div class="stat-card">
							<p class="text-muted small fw-bold mb-1">EXPENSES</p>
							<h3 class="fw-bold m-0">₹2.1L</h3>
						</div>
					</div>
					<div class="col-6 col-lg-3">
						<div class="stat-card">
							<p class="text-muted small fw-bold mb-1">VISITORS</p>
							<h3 class="fw-bold m-0">08 Today</h3>
						</div>
					</div>
				</div>
				<!-- Table -->
				<div class="data-card">
					<div class="table-responsive">
						<table class="table table-hover datatable w-100">
							<thead>
								<tr>
									<th>ID</th>
									<th>Details</th>
									<th>Status</th>
									<th class="text-end">Action</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td>#1024</td>
									<td>Maintenance - Unit B204</td>
									<td><span class="badge bg-success">Paid</span></td>
									<td class="text-end"><button class="btn btn-sm btn-outline-secondary">View</button></td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</main>

		</div>

	<?php elseif ($_SESSION['user_role'] === 'cashier'): ?>

		<div class="main-wrapper">

			<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

			<main id="main-content">
				<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
					<h2 class="fw-800 m-0">Dashboard Overview</h2>
					<button class="btn btn-brand shadow-sm"><i class="fa-solid fa-plus me-2"></i> New Entry</button>
				</div>

				<div class="row g-3 mb-5">
					<div class="col-6 col-lg-3">
						<div class="stat-card">
							<p class="text-muted small fw-bold mb-1">REVENUE</p>
							<h3 class="fw-bold m-0">₹8.4L</h3>
						</div>
					</div>
					<div class="col-6 col-lg-3">
						<div class="stat-card border-danger">
							<p class="text-danger small fw-bold mb-1">PENDING</p>
							<h3 class="fw-bold m-0">₹1.2L</h3>
						</div>
					</div>
					<div class="col-6 col-lg-3">
						<div class="stat-card">
							<p class="text-muted small fw-bold mb-1">EXPENSES</p>
							<h3 class="fw-bold m-0">₹2.1L</h3>
						</div>
					</div>
					<div class="col-6 col-lg-3">
						<div class="stat-card">
							<p class="text-muted small fw-bold mb-1">VISITORS</p>
							<h3 class="fw-bold m-0">08 Today</h3>
						</div>
					</div>
				</div>

				<div class="data-card">
					<div class="table-responsive">
						<table class="table table-hover datatable w-100">
							<thead>
								<tr>
									<th>ID</th>
									<th>Details</th>
									<th>Status</th>
									<th class="text-end">Action</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td>#1024</td>
									<td>Maintenance - Unit B204</td>
									<td><span class="badge bg-success">Paid</span></td>
									<td class="text-end"><button class="btn btn-sm btn-outline-secondary">View</button></td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</main>

		</div>

		<!--------------------- USER VIEW ---------------------->
	<?php else: ?>

		<div class="main-wrapper">

			<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

			<main id="main-content">
				<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
					<h2 class="fw-800 m-0">Dashboard Overview</h2>
					<button class="btn btn-brand shadow-sm"><i class="fa-solid fa-plus me-2"></i> New Entry</button>
				</div>

				<div class="row g-3 mb-5">
					<div class="col-6 col-lg-3">
						<div class="stat-card">
							<p class="text-muted small fw-bold mb-1">REVENUE</p>
							<h3 class="fw-bold m-0">₹8.4L</h3>
						</div>
					</div>
					<div class="col-6 col-lg-3">
						<div class="stat-card border-danger">
							<p class="text-danger small fw-bold mb-1">PENDING</p>
							<h3 class="fw-bold m-0">₹1.2L</h3>
						</div>
					</div>
					<div class="col-6 col-lg-3">
						<div class="stat-card">
							<p class="text-muted small fw-bold mb-1">EXPENSES</p>
							<h3 class="fw-bold m-0">₹2.1L</h3>
						</div>
					</div>
					<div class="col-6 col-lg-3">
						<div class="stat-card">
							<p class="text-muted small fw-bold mb-1">VISITORS</p>
							<h3 class="fw-bold m-0">08 Today</h3>
						</div>
					</div>
				</div>

				<div class="data-card">
					<div class="table-responsive">
						<table class="table table-hover datatable w-100">
							<thead>
								<tr>
									<th>ID</th>
									<th>Details</th>
									<th>Status</th>
									<th class="text-end">Action</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td>#1024</td>
									<td>Maintenance - Unit B204</td>
									<td><span class="badge bg-success">Paid</span></td>
									<td class="text-end"><button class="btn btn-sm btn-outline-secondary">View</button></td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</main>

		</div>

	<?php endif; ?>


</body>


</html>

<?php include('../resources/layout/footer.php'); ?>
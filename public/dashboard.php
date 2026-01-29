<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once __DIR__ . '/../core/config.php';

header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// ACCESS CONTROL
if (
	!isset($_SESSION['user_id']) ||
	!in_array($_SESSION['user_role'], ['admin', 'user'])
) {
	header('Location: ' . BASE_URL . 'logout.php');
	exit();
}


// Get total flats
$sql = "SELECT COUNT(*) AS total_flats FROM flats";
$stmt = $pdo->query($sql);
$total_flats = $stmt->fetch(PDO::FETCH_ASSOC)['total_flats'];

$flat_id = '';

// Get total bills
// $sql = "SELECT COUNT(*) AS total_bills FROM bills";
// if ($_SESSION['user_role'] == 'user') {
// 	$stmt = $pdo->prepare('SELECT flat_id FROM allotments WHERE user_id = ?');
// 	$stmt->execute([$_SESSION['user_id']]);
// 	$flat_id = $stmt->fetch(PDO::FETCH_ASSOC)['flat_id'];
// 	$sql .= " WHERE flat_id = '" . $flat_id . "'";
// }
// $stmt = $pdo->query($sql);
// $total_bills = $stmt->fetch(PDO::FETCH_ASSOC)['total_bills'];

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

// // Get total visitors
// $sql = "SELECT COUNT(*) AS total_visitors FROM visitors";
// if ($_SESSION['user_role'] == 'user') {
// 	$sql .= " WHERE flat_id = '" . $flat_id . "'";
// }
// $stmt = $pdo->query($sql);
// $total_visitors = $stmt->fetch(PDO::FETCH_ASSOC)['total_visitors'];

// // Get total unresolved complaints
// $sql = "SELECT COUNT(*) AS total_unresolved_complaints FROM complaints WHERE status = 'unresolved'";
// if ($_SESSION['user_role'] == 'user') {
// 	$sql .= " AND flat_id = '" . $flat_id . "'";
// }
// $stmt = $pdo->query($sql);
// $total_unresolved_complaints = $stmt->fetch(PDO::FETCH_ASSOC)['total_unresolved_complaints'];

// // Get total in progress complaints
// $sql = "SELECT COUNT(*) AS total_in_progress_complaints FROM complaints WHERE status = 'in_progress'";
// if ($_SESSION['user_role'] == 'user') {
// 	$sql .= " AND flat_id = '" . $flat_id . "'";
// }
// $stmt = $pdo->query($sql);
// $total_in_progress_complaints = $stmt->fetch(PDO::FETCH_ASSOC)['total_in_progress_complaints'];

// // Get total resolved complaints
// $sql = "SELECT COUNT(*) AS total_resolved_complaints FROM complaints WHERE status = 'resolved'";
// if ($_SESSION['user_role'] == 'user') {
// 	$sql .= " AND flat_id = '" . $flat_id . "'";
// }
// $stmt = $pdo->query($sql);
// $total_resolved_complaints = $stmt->fetch(PDO::FETCH_ASSOC)['total_resolved_complaints'];

// // Get total complaints
// $sql = "SELECT COUNT(*) AS total_complaints FROM complaints";
// if ($_SESSION['user_role'] == 'user') {
// 	$sql .= " WHERE flat_id = '" . $flat_id . "'";
// }
// $stmt = $pdo->query($sql);
// $total_complaints = $stmt->fetch(PDO::FETCH_ASSOC)['total_complaints'];


include('../resources/layout/header.php');

?>

<div class="container-fluid px-4">

	<!-- Header -->
	<div class="d-flex flex-wrap justify-content-between align-items-center mt-4 mb-3 gap-2">
		<h1 class="mb-0 fw-semibold">Dashboard</h1>

		<!-- <?php if ($_SESSION['user_role'] === 'admin'): ?>
			<?php if (date('j') >= 28): ?>
				<a href="manualGenerateMonthlyBills.php.php"
					class="btn btn-success"
					onclick="return confirm('Are you sure you want to run the maintenance billing job?');">
					<i class="fa fa-gears me-1"></i> Generate Bill
				</a>
			<?php else: ?>
				<button class="btn btn-outline-secondary" disabled>
					<i class="fa fa-lock me-1"></i> Generate Bill (After 28th)
				</button>
			<?php endif; ?>
		<?php endif; ?> -->

		<!-- <?php if ($_SESSION['user_role'] === 'admin'): ?>
			<?php if (date('j') >= 28): ?>
				<a href="manualGeneratecronSalary.php"
					class="btn btn-success"
					onclick="return confirm('Are you sure you want to run the maintenance billing job?');">
					<i class="fa fa-gears me-1"></i> Generate Salary
				</a>
			<?php else: ?>
				<button class="btn btn-outline-secondary" disabled>
					<i class="fa fa-lock me-1"></i> Generate Salary (After 28th)
				</button>
			<?php endif; ?>
		<?php endif; ?> -->

	</div>

	<!-- Breadcrumb -->
	<nav aria-label="breadcrumb">
		<ol class="breadcrumb mb-4">
			<li class="breadcrumb-item active">Dashboard</li>
		</ol>
	</nav>
	

	<!-- Cards -->
	<div class="row g-4">
		<!--------------------- ADMIN VIEW ---------------------->
		<?php if ($_SESSION['user_role'] === 'admin'): ?>
			<!-- Total Flats -->
			<div class="col-xl-3 col-md-6">
				<div class="card shadow-sm h-100">
					<div class="card-body d-flex justify-content-between align-items-center">
						<div>
							<div class="text-muted small">Total Flats</div>
							<div class="display-6 fw-bold"><?= $total_flats ?></div>
						</div>
						<i class="fa fa-building fa-2x text-primary"></i>
					</div>
				</div>
			</div>

			<!-- Total Pending / Overdue Bills -->
			<div class="col-xl-3 col-md-6">
				<div class="card shadow-sm h-100">
					<div class="card-body d-flex justify-content-between align-items-center">
						<div>
							<div class="text-muted small">Pending / Overdue Bills</div>
							<div class="display-6 fw-bold"><?= $total_bills ?></div>
						</div>
						<i class="fa fa-file-invoice fa-2x text-warning"></i>
					</div>
				</div>
			</div>

			<!-- Total Allotments -->
			<div class="col-xl-3 col-md-6">
				<div class="card shadow-sm h-100">
					<div class="card-body d-flex justify-content-between align-items-center">
						<div>
							<div class="text-muted small">Total Allotments</div>
							<div class="display-6 fw-bold"><?= $total_allotments ?></div>
						</div>
						<i class="fa fa-house-user fa-2x text-warning"></i>
					</div>
				</div>
			</div>

			<!-- In Process -->
			<!-- <div class="col-xl-3 col-md-6">
				<div class="card shadow-sm h-100">
					<div class="card-body d-flex justify-content-between align-items-center">
						<div>
							<div class="text-muted small">In-Process Complaints</div>
							<div class="display-6 fw-bold"><?= $total_in_progress_complaints ?></div>
						</div>
						<i class="fa fa-spinner fa-2x text-info"></i>
					</div>
				</div>
			</div> -->

			<!-- Visitors -->
			<!-- <div class="col-xl-3 col-md-6">
				<div class="card shadow-sm h-100">
					<div class="card-body d-flex justify-content-between align-items-center">
						<div>
							<div class="text-muted small">Total Visitors</div>
							<div class="display-6 fw-bold"><?= $total_visitors ?></div>
						</div>
						<i class="fa fa-users fa-2x text-secondary"></i>
					</div>
				</div>
			</div> -->

			<!-- Unresolved -->
			<!-- <div class="col-xl-3 col-md-6">
				<div class="card shadow-sm h-100">
					<div class="card-body d-flex justify-content-between align-items-center">
						<div>
							<div class="text-muted small">Unresolved</div>
							<div class="display-6 fw-bold"><?= $total_unresolved_complaints ?></div>
						</div>
						<i class="fa fa-exclamation-triangle fa-2x text-danger"></i>
					</div>
				</div>
			</div> -->

			<!-- Resolved -->
			<!-- <div class="col-xl-3 col-md-6">
				<div class="card shadow-sm h-100">
					<div class="card-body d-flex justify-content-between align-items-center">
						<div>
							<div class="text-muted small">Resolved</div>
							<div class="display-6 fw-bold"><?= $total_resolved_complaints ?></div>
						</div>
						<i class="fa fa-check-circle fa-2x text-success"></i>
					</div>
				</div>
			</div> -->

			<!-- Total Complaints -->
			<!-- <div class="col-xl-3 col-md-6">
				<div class="card shadow-sm h-100">
					<div class="card-body d-flex justify-content-between align-items-center">
						<div>
							<div class="text-muted small">Total Complaints</div>
							<div class="display-6 fw-bold"><?= $total_complaints ?></div>
						</div>
						<i class="fa fa-list fa-2x text-primary"></i>
					</div>
				</div>
			</div> -->

			<!--------------------- USER VIEW ---------------------->
		<?php else: ?>
			<!-- User Pending / Overdue Bills -->
			<div class="col-xl-3 col-md-6">
				<div class="card shadow-sm h-100">
					<div class="card-body d-flex justify-content-between align-items-center">
						<div>
							<div class="text-muted small">Pending / Overdue Bills</div>
							<div class="display-6 fw-bold"><?= $total_bills ?></div>
						</div>
						<i class="fa fa-file-invoice fa-2x text-warning"></i>
					</div>
				</div>
			</div>
		<?php endif; ?>
	</div>
</div>


<?php
include('../resources/layout/footer.php');
?>